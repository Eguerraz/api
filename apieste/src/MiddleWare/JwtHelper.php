<?php

namespace App\MiddleWare;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private static $secretKey = 'Saint-Michel-SmartDisplay-Amiar'; // Replace with your actual secret key
    private static $algorithm = 'HS256'; // Hashing algorithm
    public static function generateToken($data, $expiry = 3600000000000000) // Generate JWT for a long time
    {
        $issuedAt = time();
        $expiration = $issuedAt + $expiry; // Token expiration time
        $payload = array(
            'iat' => $issuedAt,
            'exp' => $expiration,
            'data' => $data
        );
        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }
    public static function validateToken($token) // Validate and decode JWT
    {
        try {
            return JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
        } catch (Exception $e) {
            return null; // Return null if token is invalid
        }
    }
}
