<?php

namespace App\Core;

/**
 * TOTP (Time-based One-Time Password) Service
 * Implements RFC 6238 TOTP using PHP built-in functions (no external library).
 */
class TotpService
{
    private const DIGITS    = 6;
    private const PERIOD    = 30;
    private const ALGORITHM = 'sha1';
    private const DRIFT     = 1; // allow ±1 period drift

    // Base32 alphabet (RFC 4648)
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a cryptographically random Base32 secret (160-bit).
     */
    public static function generateSecret(): string
    {
        $bytes = random_bytes(20); // 160 bits
        return self::base32Encode($bytes);
    }

    /**
     * Generate the current TOTP code for the given secret.
     */
    public static function getCode(string $secret, int $time = null): string
    {
        $time  ??= time();
        $counter = (int) floor($time / self::PERIOD);
        return self::hotp($secret, $counter);
    }

    /**
     * Verify a TOTP code, allowing configurable drift.
     */
    public static function verify(string $secret, string $code): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $time = time();
        for ($drift = -self::DRIFT; $drift <= self::DRIFT; $drift++) {
            $counter = (int) floor(($time + $drift * self::PERIOD) / self::PERIOD);
            if (hash_equals(self::hotp($secret, $counter), $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build the otpauth URI for QR code generation.
     */
    public static function getOtpAuthUri(string $secret, string $accountName, string $issuer): string
    {
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGORITHM),
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $accountName) . '?' . $params;
    }

    // -------------------------------------------------------------------------
    // HOTP core (RFC 4226)
    // -------------------------------------------------------------------------

    private static function hotp(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);
        // Big-endian 64-bit counter
        $msg = pack('N*', 0) . pack('N*', $counter);

        $hash   = hash_hmac(self::ALGORITHM, $msg, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $code = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
             (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------------------------------
    // Base32 encode / decode
    // -------------------------------------------------------------------------

    public static function base32Encode(string $input): string
    {
        $alphabet = self::BASE32_CHARS;
        $output   = '';
        $buffer   = 0;
        $bitsLeft = 0;

        foreach (str_split($input) as $char) {
            $buffer   = ($buffer << 8) | ord($char);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $output   .= $alphabet[($buffer >> $bitsLeft) & 0x1F];
            }
        }

        if ($bitsLeft > 0) {
            $output .= $alphabet[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        // No padding needed for authenticator apps
        return $output;
    }

    public static function base32Decode(string $input): string
    {
        $input  = strtoupper(trim($input));
        $lookup = array_flip(str_split(self::BASE32_CHARS));
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($input) as $char) {
            if ($char === '=') {
                break;
            }
            if (!isset($lookup[$char])) {
                continue; // skip invalid chars
            }
            $buffer   = ($buffer << 5) | $lookup[$char];
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output   .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
