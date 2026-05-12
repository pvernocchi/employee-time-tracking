<?php

namespace App\Core;

use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;
use lbuchs\WebAuthn\Binary\ByteBuffer;

/**
 * WebAuthn service for YubiKey / hardware TPM (Windows Hello, Touch ID, …) MFA.
 */
class WebAuthnService
{
    private WebAuthn $webAuthn;
    private string $rpId;

    public function __construct()
    {
        // Use base64url encoding so ByteBuffer serializes correctly for browsers
        ByteBuffer::$useBase64UrlEncoding = true;

        $rpName     = 'Employee Time Tracker';
        $this->rpId = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Strip port if present
        if (str_contains($this->rpId, ':')) {
            $this->rpId = explode(':', $this->rpId)[0];
        }

        $this->webAuthn = new WebAuthn($rpName, $this->rpId, [
            'android-key',
            'android-safetynet',
            'apple',
            'fido-u2f',
            'none',
            'packed',
            'tpm',
        ], true); // last param = useBase64UrlEncoding
    }

    /**
     * Create a registration challenge for a user.
     * Returns the JSON-encoded challenge data to be sent to the browser.
     */
    public function createRegistrationChallenge(int $userId, string $userName, string $userDisplayName): array
    {
        $userIdBin = \hex2bin(\str_pad(\dechex($userId), 16, '0', STR_PAD_LEFT));

        $createArgs = $this->webAuthn->getCreateArgs(
            $userIdBin,
            $userName,
            $userDisplayName,
            60,    // timeout seconds
            false, // requireResidentKey
            false, // requireUserVerification
            null   // crossPlatformAttachment (null = any)
        );

        // Store challenge in session (ByteBuffer is serializable)
        $_SESSION['webauthn_challenge'] = $this->webAuthn->getChallenge();

        return json_decode(json_encode($createArgs), true);
    }

    /**
     * Verify a registration response from the browser.
     * Returns the stored credential data on success.
     *
     * @throws \RuntimeException on failure
     */
    public function verifyRegistration(array $clientDataJSON_b64, array $attestationObject_b64): array
    {
        if (empty($_SESSION['webauthn_challenge'])) {
            throw new \RuntimeException('No WebAuthn challenge in session.');
        }

        $challenge = $_SESSION['webauthn_challenge'];
        unset($_SESSION['webauthn_challenge']);

        try {
            $data = $this->webAuthn->processCreate(
                base64_decode($clientDataJSON_b64['clientDataJSON']),
                base64_decode($attestationObject_b64['attestationObject']),
                $challenge,
                false, // requireUserVerification
                true,  // requireUserPresent
                false  // failIfRootMismatch
            );
        } catch (WebAuthnException $e) {
            throw new \RuntimeException('WebAuthn registration failed: ' . $e->getMessage());
        }

        return [
            'credential_id'         => base64_encode($data->credentialId),
            'credential_public_key' => $data->credentialPublicKey, // already PEM string
            'sign_count'            => (int) ($data->signatureCounter ?? 0),
        ];
    }

    /**
     * Create an authentication challenge.
     * Returns data to be sent to the browser.
     *
     * @param array $credentials  Array of stored credential data rows from user_mfa
     */
    public function createAuthChallenge(array $credentials): array
    {
        $ids = [];
        foreach ($credentials as $cred) {
            $data = json_decode($cred['credential_data'], true);
            if (!empty($data['credential_id'])) {
                $ids[] = base64_decode($data['credential_id']); // raw bytes
            }
        }

        $getArgs = $this->webAuthn->getGetArgs(
            $ids,
            60,   // timeout
            true, // allowUsb
            true, // allowNfc
            true, // allowBle
            true, // allowHybrid
            true  // allowInternal
        );

        $_SESSION['webauthn_challenge'] = $this->webAuthn->getChallenge();

        return json_decode(json_encode($getArgs), true);
    }

    /**
     * Verify an authentication response from the browser.
     *
     * @param array  $response    Parsed JSON from browser
     * @param array  $credentials Stored credentials for this user
     * @return array              The matched credential row (with updated sign_count in credential_data)
     *
     * @throws \RuntimeException on failure
     */
    public function verifyAuthentication(array $response, array $credentials): array
    {
        if (empty($_SESSION['webauthn_challenge'])) {
            throw new \RuntimeException('No WebAuthn challenge in session.');
        }

        $challenge = $_SESSION['webauthn_challenge'];
        unset($_SESSION['webauthn_challenge']);

        // The credential id comes base64url-encoded from the browser
        $credentialIdB64 = $response['id'] ?? $response['rawId'] ?? null;
        if ($credentialIdB64 === null) {
            throw new \RuntimeException('Missing credential ID in response.');
        }
        // Normalize to standard base64
        $credentialIdBin = base64_decode(strtr($credentialIdB64, '-_', '+/') . str_repeat('=', (4 - strlen($credentialIdB64) % 4) % 4));
        $credentialIdB64Stored = base64_encode($credentialIdBin);

        // Find matching stored credential
        $matchedCred    = null;
        $storedPemKey   = null;
        $storedSignCount = 0;

        foreach ($credentials as $cred) {
            $data = json_decode($cred['credential_data'], true);
            if (isset($data['credential_id']) && $data['credential_id'] === $credentialIdB64Stored) {
                $matchedCred     = $cred;
                $storedPemKey    = $data['credential_public_key']; // PEM string
                $storedSignCount = (int) ($data['sign_count'] ?? 0);
                break;
            }
        }

        if ($matchedCred === null) {
            throw new \RuntimeException('Unknown credential.');
        }

        try {
            $this->webAuthn->processGet(
                base64_decode($response['clientDataJSON']),
                base64_decode($response['authenticatorData']),
                base64_decode($response['signature']),
                $storedPemKey,
                $challenge,
                $storedSignCount
            );
        } catch (WebAuthnException $e) {
            throw new \RuntimeException('WebAuthn authentication failed: ' . $e->getMessage());
        }

        // Return matched credential with updated sign count
        $updatedData = json_decode($matchedCred['credential_data'], true);
        $updatedData['sign_count'] = (int) ($this->webAuthn->getSignatureCounter() ?? $storedSignCount);
        $matchedCred['credential_data'] = json_encode($updatedData);

        return $matchedCred;
    }
}

