<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme\Support;

final class Esc
{
    public static function h(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', true);
    }

    public static function attr(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', true);
    }

    public static function url(string $str): string
    {
        return rawurlencode($str);
    }

    public static function js(string $str): string
    {
        $str = htmlspecialchars($str, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', true);
        $str = str_replace(['\\', "\n", "\r", "\t", "'", '"'], ['\\\\', '\\n', '\\r', '\\t', "\\'", '\\"'], $str);
        return $str;
    }

    public static function json($data): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }
}