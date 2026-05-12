<?php

namespace App\Core;

/**
 * CAPTCHA verification service.
 * Supports Cloudflare Turnstile and Google reCAPTCHA v2/v3.
 */
class CaptchaService
{
    private const TURNSTILE_VERIFY_URL  = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private const RECAPTCHA_VERIFY_URL  = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Verify the CAPTCHA token submitted with the form.
     * Returns true if valid or if CAPTCHA is disabled.
     */
    public static function verify(?string $token): bool
    {
        $provider = SecuritySettings::captchaProvider();

        if ($provider === 'none') {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        $secretKey = SecuritySettings::captchaSecretKey();
        if (empty($secretKey)) {
            return true; // misconfigured – skip silently
        }

        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

        return match ($provider) {
            'cloudflare' => self::verifyTurnstile($token, $secretKey, $remoteIp),
            'recaptcha'  => self::verifyRecaptcha($token, $secretKey, $remoteIp),
            default      => true,
        };
    }

    // -------------------------------------------------------------------------

    private static function verifyTurnstile(string $token, string $secret, string $ip): bool
    {
        $data = self::httpPost(self::TURNSTILE_VERIFY_URL, [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        return (bool) ($data['success'] ?? false);
    }

    private static function verifyRecaptcha(string $token, string $secret, string $ip): bool
    {
        $version = SecuritySettings::recaptchaVersion();

        $data = self::httpPost(self::RECAPTCHA_VERIFY_URL, [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        if (!($data['success'] ?? false)) {
            return false;
        }

        // For v3, require a minimum score of 0.5
        if ($version === 'v3') {
            $score = (float) ($data['score'] ?? 0.0);
            return $score >= 0.5;
        }

        return true;
    }

    /**
     * POST form data to a URL and return the decoded JSON response.
     */
    private static function httpPost(string $url, array $fields): array
    {
        $postBody = http_build_query($fields);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n"
                           . 'Content-Length: ' . strlen($postBody) . "\r\n",
                'content' => $postBody,
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return [];
        }

        return json_decode($response, true) ?? [];
    }
}
