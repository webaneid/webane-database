<?php

namespace WDB\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class Updater
{
    private const REPOSITORY = 'webaneid/webane-database';
    private const RELEASE_CACHE_KEY = 'wdb_github_latest_release';

    public static function boot(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [self::class, 'inject_update']);
        add_filter('plugins_api', [self::class, 'plugins_api'], 20, 3);
        add_filter('plugin_action_links_' . plugin_basename(WDB_PLUGIN_FILE), [self::class, 'plugin_action_links']);
        add_action('admin_init', [self::class, 'handle_force_check']);
        add_action('admin_notices', [self::class, 'render_force_check_notice']);
    }

    public static function inject_update($transient)
    {
        if (! is_object($transient)) {
            $transient = new \stdClass();
        }

        $release = self::get_latest_release();

        if (! is_array($release)) {
            return $transient;
        }

        $remote_version = self::normalize_version((string) ($release['tag_name'] ?? ''));
        $package_url = self::get_release_package_url($release);

        if ('' === $remote_version || '' === $package_url) {
            return $transient;
        }

        if (! version_compare($remote_version, WDB_PLUGIN_VERSION, '>')) {
            return $transient;
        }

        $plugin_file = plugin_basename(WDB_PLUGIN_FILE);
        $plugin_data = self::get_plugin_data();
        $update = (object) [
            'slug' => dirname($plugin_file),
            'plugin' => $plugin_file,
            'new_version' => $remote_version,
            'url' => 'https://github.com/' . self::REPOSITORY,
            'package' => $package_url,
            'icons' => [],
            'banners' => [],
            'banners_rtl' => [],
            'tested' => isset($plugin_data['RequiresWP']) ? (string) $plugin_data['RequiresWP'] : '',
            'requires' => '',
            'requires_php' => isset($plugin_data['RequiresPHP']) ? (string) $plugin_data['RequiresPHP'] : '',
        ];

        if (! isset($transient->response) || ! is_array($transient->response)) {
            $transient->response = [];
        }

        $transient->response[$plugin_file] = $update;

        return $transient;
    }

    public static function plugins_api($result, string $action, $args)
    {
        if ('plugin_information' !== $action || empty($args->slug) || 'webane-database' !== $args->slug) {
            return $result;
        }

        $release = self::get_latest_release();
        $plugin_data = self::get_plugin_data();
        $remote_version = is_array($release) ? self::normalize_version((string) ($release['tag_name'] ?? '')) : WDB_PLUGIN_VERSION;
        $body = is_array($release) ? (string) ($release['body'] ?? '') : '';

        return (object) [
            'name' => (string) ($plugin_data['Name'] ?? 'Webane Database'),
            'slug' => 'webane-database',
            'version' => '' !== $remote_version ? $remote_version : WDB_PLUGIN_VERSION,
            'author' => '<a href="https://webane.com">Webane Indonesia</a>',
            'author_profile' => 'https://webane.com',
            'homepage' => 'https://github.com/' . self::REPOSITORY,
            'download_link' => is_array($release) ? self::get_release_package_url($release) : '',
            'requires' => '',
            'requires_php' => '',
            'tested' => '',
            'last_updated' => is_array($release) ? (string) ($release['published_at'] ?? '') : '',
            'sections' => [
                'description' => wpautop(esc_html((string) ($plugin_data['Description'] ?? 'Database pesantren dan alumni.'))),
                'installation' => wpautop("1. Install plugin.\n2. Aktivasi plugin.\n3. Import wilayah dan mulai isi data."),
                'changelog' => wpautop(esc_html($body !== '' ? $body : 'Belum ada catatan rilis.')),
            ],
        ];
    }

    public static function plugin_action_links(array $links): array
    {
        if (! current_user_can('update_plugins')) {
            return $links;
        }

        $url = self::get_force_check_url(admin_url('plugins.php'));

        array_unshift($links, '<a href="' . esc_url($url) . '">Check Update</a>');

        return $links;
    }

    public static function handle_force_check(): void
    {
        if (! is_admin() || ! current_user_can('update_plugins')) {
            return;
        }

        $action = isset($_GET['wdb_action']) ? sanitize_key(wp_unslash($_GET['wdb_action'])) : '';
        $plugin = isset($_GET['plugin']) ? sanitize_text_field(wp_unslash($_GET['plugin'])) : '';

        if ('force_check_update' !== $action || plugin_basename(WDB_PLUGIN_FILE) !== $plugin) {
            return;
        }

        check_admin_referer('wdb_force_check_update');

        delete_site_transient('update_plugins');
        delete_site_transient(self::RELEASE_CACHE_KEY);

        if (function_exists('wp_clean_plugins_cache')) {
            wp_clean_plugins_cache(true);
        }

        require_once ABSPATH . 'wp-admin/includes/update.php';
        wp_update_plugins();

        $redirect_url = isset($_GET['wdb_redirect']) ? rawurldecode((string) wp_unslash($_GET['wdb_redirect'])) : admin_url('plugins.php');

        if (! wp_validate_redirect($redirect_url, false)) {
            $redirect_url = admin_url('plugins.php');
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'wdb_update_checked' => '1',
                ],
                $redirect_url
            )
        );
        exit;
    }

    public static function render_force_check_notice(): void
    {
        if (! is_admin() || ! current_user_can('update_plugins')) {
            return;
        }

        if (! isset($_GET['wdb_update_checked']) || '1' !== (string) wp_unslash($_GET['wdb_update_checked'])) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>Pengecekan update Webane Database selesai dijalankan.</p></div>';
    }

    private static function get_latest_release(): ?array
    {
        $cached = get_site_transient(self::RELEASE_CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $response = wp_remote_get(
            'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest',
            [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => 'Webane-Database-Updater',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ],
            ]
        );

        if (is_wp_error($response)) {
            return null;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if (200 !== $code || ! is_array($body)) {
            return null;
        }

        set_site_transient(self::RELEASE_CACHE_KEY, $body, HOUR_IN_SECONDS);

        return $body;
    }

    private static function get_release_package_url(array $release): string
    {
        $assets = isset($release['assets']) && is_array($release['assets']) ? $release['assets'] : [];

        foreach ($assets as $asset) {
            $name = isset($asset['name']) ? (string) $asset['name'] : '';

            if ('webane-database.zip' === $name) {
                return isset($asset['browser_download_url']) ? (string) $asset['browser_download_url'] : '';
            }
        }

        foreach ($assets as $asset) {
            $name = isset($asset['name']) ? (string) $asset['name'] : '';

            if ('.zip' === strtolower(substr($name, -4))) {
                return isset($asset['browser_download_url']) ? (string) $asset['browser_download_url'] : '';
            }
        }

        return '';
    }

    private static function normalize_version(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }

    public static function get_force_check_url(string $redirect_url = ''): string
    {
        $base_url = '' !== $redirect_url ? $redirect_url : admin_url('plugins.php');

        return wp_nonce_url(
            add_query_arg(
                [
                    'wdb_action' => 'force_check_update',
                    'plugin' => plugin_basename(WDB_PLUGIN_FILE),
                    'wdb_redirect' => rawurlencode($base_url),
                ],
                $base_url
            ),
            'wdb_force_check_update'
        );
    }

    private static function get_plugin_data(): array
    {
        if (! function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return get_plugin_data(WDB_PLUGIN_FILE, false, false);
    }
}
