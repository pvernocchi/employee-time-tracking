<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

class SmtpMailer
{
    /** @var string Application secret used to encrypt the SMTP password at rest. */
    private static string $encryptionSecret = '';

    private array $settings;
    private string $logFile;

    public function __construct(string $basePath = '')
    {
        $this->logFile = ($basePath !== '' ? rtrim($basePath, '/') : dirname(__DIR__, 2)) . '/logs/smtp.log';
        $this->settings = $this->loadSettings();
    }

    /**
     * Set the application secret used to encrypt/decrypt the SMTP password.
     * Call this once during application boot (e.g. index.php) before any
     * SmtpMailer instance is created.
     */
    public static function setSecret(string $secret): void
    {
        self::$encryptionSecret = $secret;
    }

    /**
     * Encrypt a value using AES-256-CBC when a secret is available.
     * Returns the plain value if no secret is configured.
     *
     * The returned string is base64-encoded "iv:ciphertext".
     */
    public static function encryptPassword(string $value): string
    {
        if (self::$encryptionSecret === '' || $value === '') {
            return $value;
        }

        $key = hash('sha256', self::$encryptionSecret, true); // 32-byte key
        $iv  = random_bytes(16);
        $cipher = openssl_encrypt($value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($cipher === false) {
            return $value;
        }

        return base64_encode($iv . $cipher);
    }

    /**
     * Decrypt a value previously encrypted with encryptPassword().
     * Returns the original value if no secret is configured or decryption fails.
     */
    public static function decryptPassword(string $value): string
    {
        if (self::$encryptionSecret === '' || $value === '') {
            return $value;
        }

        $raw = base64_decode($value, true);
        if ($raw === false || strlen($raw) < 17) {
            // Not an encrypted value – return as-is (backwards compat)
            return $value;
        }

        $iv     = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $key    = hash('sha256', self::$encryptionSecret, true);

        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $plain !== false ? $plain : $value;
    }

    /**
     * Load SMTP settings from the database.
     */
    private function loadSettings(): array
    {
        $defaults = [
            'smtp_enabled'     => '0',
            'smtp_host'        => 'localhost',
            'smtp_port'        => '587',
            'smtp_encryption'  => 'tls',
            'smtp_auth'        => '0',
            'smtp_username'    => '',
            'smtp_password'    => '',
            'smtp_from_email'  => '',
            'smtp_from_name'   => '',
            'smtp_log_enabled' => '0',
        ];

        try {
            $db = Database::getInstance();
            $rows = $db->fetchAll('SELECT setting_key, setting_value FROM smtp_settings');
            $loaded = [];
            foreach ($rows as $row) {
                $loaded[$row['setting_key']] = $row['setting_value'];
            }
            $settings = array_merge($defaults, $loaded);

            // Decrypt the stored SMTP password at runtime
            $settings['smtp_password'] = self::decryptPassword($settings['smtp_password']);

            return $settings;
        } catch (\Throwable) {
            return $defaults;
        }
    }

    /**
     * Returns true if SMTP sending is enabled and a host is configured.
     */
    public function isEnabled(): bool
    {
        return $this->settings['smtp_enabled'] === '1' && $this->settings['smtp_host'] !== '';
    }

    /**
     * Returns true if SMTP logging is enabled.
     */
    public function isLoggingEnabled(): bool
    {
        return $this->settings['smtp_log_enabled'] === '1';
    }

    /**
     * Send an email via the configured SMTP server.
     *
     * @param string|array<string,string> $to Recipient email or ['email' => 'name'] map
     * @param string $subject Email subject
     * @param string $body HTML body
     * @param string $altBody Plain-text fallback body
     *
     * @throws MailerException When the email cannot be sent
     */
    public function send(string|array $to, string $subject, string $body, string $altBody = ''): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->settings['smtp_host'];
            $mail->Port       = (int) $this->settings['smtp_port'];
            $mail->SMTPAuth   = $this->settings['smtp_auth'] === '1';
            $mail->Username   = $this->settings['smtp_username'];
            $mail->Password   = $this->settings['smtp_password'];

            // Encryption / TLS configuration
            switch ($this->settings['smtp_encryption']) {
                case 'ssl':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    break;
                case 'tls':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                default:
                    $mail->SMTPSecure = '';
                    $mail->SMTPAutoTLS = false;
                    break;
            }

            // Enable debug output to log if logging is enabled
            if ($this->isLoggingEnabled()) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function (string $str, int $level): void {
                    $this->log('[DEBUG level=' . $level . '] ' . trim($str));
                };
            } else {
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
            }

            // From
            $fromEmail = $this->settings['smtp_from_email'];
            $fromName  = $this->settings['smtp_from_name'];
            if ($fromEmail !== '') {
                $mail->setFrom($fromEmail, $fromName);
            }

            // Recipients
            if (is_string($to)) {
                $mail->addAddress($to);
            } else {
                foreach ($to as $email => $name) {
                    if (is_int($email)) {
                        $mail->addAddress($name);
                    } else {
                        $mail->addAddress($email, $name);
                    }
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($body);

            $mail->send();

            $this->log('Message sent to ' . $this->recipientsToString($to));

            return true;
        } catch (MailerException $e) {
            $this->log('ERROR sending to ' . $this->recipientsToString($to) . ' | ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract email addresses from a $to value for logging purposes.
     * Handles both indexed arrays (values are emails) and associative arrays (keys are emails).
     */
    private function recipientsToString(string|array $to): string
    {
        if (is_string($to)) {
            return $to;
        }

        $emails = [];
        foreach ($to as $key => $value) {
            $emails[] = is_int($key) ? $value : $key;
        }

        return implode(', ', $emails);
    }

    /**
     * Write a timestamped entry to the SMTP log file (only when logging is enabled).
     */
    public function log(string $message): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Return the current settings array.
     * Note: smtp_password is the decrypted value; it is not exposed in the view.
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Persist SMTP settings to the database.
     * The smtp_password value will be encrypted before storage if a secret is set.
     *
     * @param array<string,string> $settings Key → value map of smtp_* settings
     */
    public static function saveSettings(array $settings): void
    {
        $db = Database::getInstance();

        $allowed = [
            'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption',
            'smtp_auth', 'smtp_username', 'smtp_password',
            'smtp_from_email', 'smtp_from_name', 'smtp_log_enabled',
        ];

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }

            $value = $settings[$key];

            // Encrypt the password before storing
            if ($key === 'smtp_password') {
                $value = self::encryptPassword($value);
            }

            $db->query(
                'INSERT INTO smtp_settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                [$key, $value]
            );
        }
    }
}
