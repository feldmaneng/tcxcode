<?php
namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    public static function secret(): string
    {
        $s = env('app.JWT_SECRET');
        if (!$s) throw new \RuntimeException('JWT_SECRET not configured');
        return $s;
    }

    public static function issue(array $claims, int $ttlSeconds = 900): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
        ]);
        return JWT::encode($payload, self::secret(), 'HS256');
    }

    public static function verify(string $token): array
    {
        $decoded = JWT::decode($token, new Key(self::secret(), 'HS256'));
        return (array) $decoded;
    }
}
