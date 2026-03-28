<?php

namespace WDB\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    private static $image_size_map = [];

    public static function boot(): void
    {
        add_action('plugins_loaded', [self::class, 'load_textdomain']);
        add_action('init', ['WDB\\Database\\Installer', 'register_roles']);
        add_action('after_setup_theme', [self::class, 'register_image_sizes'], 99);
        \WDB\Core\Updater::boot();
        \WDB\Frontend\Auth::boot();
        \WDB\Frontend\Forms::boot();

        if (is_admin()) {
            \WDB\Admin\Menu::boot();
        }
    }

    public static function load_textdomain(): void
    {
        load_plugin_textdomain(
            'webane-database',
            false,
            dirname(plugin_basename(WDB_PLUGIN_FILE)) . '/languages'
        );
    }

    public static function register_image_sizes(): void
    {
        self::$image_size_map['pesantren_photo'] = self::resolve_image_size(
            'wdb-pesantren-photo-16x9',
            1000,
            563,
            true
        );
        self::$image_size_map['alumni_pasphoto'] = self::resolve_image_size(
            'wdb-alumni-pasphoto-3x4',
            600,
            800,
            true
        );
    }

    public static function get_pesantren_photo_size(): string
    {
        if (empty(self::$image_size_map['pesantren_photo'])) {
            self::register_image_sizes();
        }

        return (string) self::$image_size_map['pesantren_photo'];
    }

    public static function get_alumni_pasphoto_size(): string
    {
        if (empty(self::$image_size_map['alumni_pasphoto'])) {
            self::register_image_sizes();
        }

        return (string) self::$image_size_map['alumni_pasphoto'];
    }

    private static function resolve_image_size(string $fallback_name, int $width, int $height, $crop): string
    {
        $sizes = function_exists('wp_get_registered_image_subsizes') ? wp_get_registered_image_subsizes() : [];

        foreach ($sizes as $name => $size) {
            if (
                (int) ($size['width'] ?? 0) === $width &&
                (int) ($size['height'] ?? 0) === $height &&
                self::crop_matches($size['crop'] ?? false, $crop)
            ) {
                return (string) $name;
            }
        }

        add_image_size($fallback_name, $width, $height, $crop);

        return $fallback_name;
    }

    private static function crop_matches($registered_crop, $expected_crop): bool
    {
        if (is_array($registered_crop) || is_array($expected_crop)) {
            return wp_json_encode($registered_crop) === wp_json_encode($expected_crop);
        }

        return (bool) $registered_crop === (bool) $expected_crop;
    }

    public static function asset_version(string $relative_path): string
    {
        $path = WDB_PLUGIN_PATH . ltrim($relative_path, '/');

        if (file_exists($path)) {
            return (string) filemtime($path);
        }

        return WDB_PLUGIN_VERSION;
    }
}
