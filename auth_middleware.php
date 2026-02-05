<?php
/**
 * Authentication Middleware
 * Protects routes and verifies JWT tokens
 */

require_once __DIR__ . '/jwt.php';

class AuthMiddleware
{

    /**
     * Check if user is authenticated
     * Returns user data if authenticated, redirects to login if not
     */
    public static function requireAuth()
    {
        $token = self::getTokenFromRequest();

        if (!$token) {
            self::redirectToLogin();
        }

        $payload = JWT::verify($token);

        if (!$payload) {
            self::redirectToLogin();
        }

        return $payload;
    }

    /**
     * Check if user is authenticated (API version)
     * Returns user data or sends 401 response
     */
    public static function requireAuthAPI()
    {
        $token = self::getTokenFromRequest();

        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'No token provided']);
            exit;
        }

        $payload = JWT::verify($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }

        return $payload;
    }

    /**
     * Get token from request (cookie or Authorization header)
     */
    private static function getTokenFromRequest()
    {
        // Check cookie first
        if (isset($_COOKIE['auth_token'])) {
            return $_COOKIE['auth_token'];
        }

        // Check Authorization header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Redirect to login page
     */
    private static function redirectToLogin()
    {
        header('Location: /login.php');
        exit;
    }

    /**
     * Set authentication cookie
     */
    public static function setAuthCookie($token)
    {
        setcookie('auth_token', $token, [
            'expires' => time() + (24 * 3600), // 24 hours
            'path' => '/',
            'httponly' => true,
            'secure' => false, // Set to true in production with HTTPS
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Clear authentication cookie
     */
    public static function clearAuthCookie()
    {
        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
        ]);
    }
}
?>