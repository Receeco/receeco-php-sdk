<?php

declare(strict_types=1);

namespace Receeco\Utils;

/**
 * Utility class for generating tokens and short codes.
 */
class TokenGenerator
{
    /**
     * Generate a unique receipt token.
     *
     * @return string
     */
    public static function generateReceiptToken(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $part1 = self::randomString($chars, 13);
        $part2 = self::randomString($chars, 13);
        
        return $part1 . $part2;
    }

    /**
     * Generate a 6-character short code.
     *
     * @return string
     */
    public static function generateShortCode(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return self::randomString($chars, 6);
    }

    /**
     * Generate a random string from given characters.
     *
     * @param string $chars Available characters
     * @param int $length Length of the string to generate
     * @return string
     */
    private static function randomString(string $chars, int $length): string
    {
        $result = '';
        $charsLength = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $charsLength - 1)];
        }
        
        return $result;
    }
} 