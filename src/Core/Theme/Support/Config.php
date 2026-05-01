<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme\Support;

final class Config
{
    private static array $data = [];
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Config file not found: {$path}");
        }

        $json = file_get_contents($path);
        if ($json === false) {
            throw new \RuntimeException("Unable to read config: {$path}");
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException("Invalid JSON in config file {$path}: " . $e->getMessage());
        }

        if (!is_array($data)) {
            throw new \RuntimeException("Invalid config structure in {$path}");
        }

        self::$data = $data;
        self::$loaded = true;
        self::applyDevSettings();
    }

    private static function applyDevSettings(): void
    {
        $displayErrors = self::get('dev.display_errors', false);
        
        if ($displayErrors === true) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            error_reporting(0);
        }
    }

    public static function get(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $value = self::$data;

        foreach ($segments as $seg) {
            if (!is_array($value) || !array_key_exists($seg, $value)) {
                return $default;
            }
            $value = $value[$seg];
        }

        return $value;
    }

    public static function set(string $key, $value): void
    {
        $segments = explode('.', $key);
        $data = &self::$data;

        $lastKey = array_pop($segments);
        foreach ($segments as $seg) {
            if (!isset($data[$seg]) || !is_array($data[$seg])) {
                $data[$seg] = [];
            }
            $data = &$data[$seg];
        }

        $data[$lastKey] = $value;
    }

    public static function all(): array
    {
        return self::$data;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }
}