<?php
/**
 * Simple JWT (JSON Web Token) Implementation
 * No external dependencies required
 */

class JWT
{
    private static $secret_key = 'your-secret-key-change-this-in-production-2024!@#$%';
    private static $algorithm = 'HS256';

    /**
     * Encode a payload into a JWT token
     */
    public static function encode($payload, $expiry_hours = 24)
    {
        // Add standard claims
        $payload['iat'] = time(); // Issued at
        $payload['exp'] = time() + ($expiry_hours * 3600); // Expiration

        // Create header
        $header = [
            'typ' => 'JWT',
            'alg' => self::$algorithm
        ];

        // Encode header and payload
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        // Create signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", self::$secret_key, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        // Return complete token
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    /**
     * Decode and verify a JWT token
     */
    public static function decode($token)
    {
        // Split token into parts
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", self::$secret_key, true);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new Exception('Invalid token signature');
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token has expired');
        }

        return $payload;
    }

    /**
     * Verify token and return payload or false
     */
    public static function verify($token)
    {
        try {
            return self::decode($token);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
?>