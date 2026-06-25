<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Helpers;

class StringHelper
{
    public static function truncate(string $str, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($str) <= $length) return $str;
        return mb_substr($str, 0, $length - mb_strlen($suffix)) . $suffix;
    }

    public static function slug(string $str): string
    {
        $str = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $str);
        $str = preg_replace('/[^a-z0-9]+/', '-', $str ?? '');
        return trim($str ?? '', '-');
    }

    public static function camelToSnake(string $str): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($str)) ?? $str);
    }

    public static function ucname(string $str): string
    {
        return mb_convert_case(mb_strtolower($str), MB_CASE_TITLE, 'UTF-8');
    }
}
