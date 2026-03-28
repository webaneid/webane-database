<?php

namespace WDB\Admin;

if (! defined('ABSPATH')) {
    exit;
}

final class RegionImporter
{
    private const BASE_URL = 'https://wilayah.id/api';
    private const REGENCIES_BATCH_SIZE = 10;
    private const DISTRICTS_BATCH_SIZE = 10;
    private const VILLAGES_BATCH_SIZE = 10;

    public static function handle_request(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        if ('POST' !== $_SERVER['REQUEST_METHOD']) {
            return;
        }

        if (! isset($_POST['page'], $_POST['wdb_region_action'])) {
            return;
        }

        $page = sanitize_key(wp_unslash($_POST['page']));
        $action = sanitize_key(wp_unslash($_POST['wdb_region_action']));

        if ('wdb-regions' !== $page) {
            return;
        }

        check_admin_referer('wdb_regions_import');

        if (self::is_action_complete($action)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'wdb-regions',
                        'message' => 'already-complete',
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $offset = isset($_POST['offset']) ? absint(wp_unslash($_POST['offset'])) : 0;
        $result = [
            'action' => $action,
            'processed' => 0,
            'next_offset' => 0,
            'complete' => true,
        ];

        if ('import_provinces' === $action) {
            $result = self::import_provinces();
        }

        if ('import_regencies' === $action) {
            $result = self::import_regencies($offset);
        }

        if ('import_districts' === $action) {
            $result = self::import_districts();
        }

        if ('import_villages' === $action) {
            $result = self::import_villages();
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'wdb-regions',
                    'message' => 'imported',
                    'import_action' => $result['action'],
                    'processed' => (int) $result['processed'],
                    'next_offset' => (int) $result['next_offset'],
                    'complete' => $result['complete'] ? '1' : '0',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public static function render(): void
    {
        global $wpdb;

        $counts = [
            'provinces' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_provinces'),
            'regencies' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_regencies'),
            'districts' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_districts'),
            'villages' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_villages'),
        ];
        $progress_action = isset($_GET['import_action']) ? sanitize_key(wp_unslash($_GET['import_action'])) : '';
        $next_offset = isset($_GET['next_offset']) ? absint(wp_unslash($_GET['next_offset'])) : 0;
        $processed = isset($_GET['processed']) ? absint(wp_unslash($_GET['processed'])) : 0;
        $complete = isset($_GET['complete']) && '1' === sanitize_text_field(wp_unslash($_GET['complete']));
        $states = [
            'import_provinces' => self::get_action_state('import_provinces'),
            'import_regencies' => self::get_action_state('import_regencies'),
            'import_districts' => self::get_action_state('import_districts'),
            'import_villages' => self::get_action_state('import_villages'),
        ];

        echo '<div class="wrap">';
        echo '<h1>Wilayah Indonesia</h1>';

        if (isset($_GET['message']) && 'imported' === sanitize_key(wp_unslash($_GET['message']))) {
            if ($complete) {
                echo '<div class="notice notice-success is-dismissible"><p>Import batch selesai. Parent diproses: ' . esc_html((string) $processed) . '.</p></div>';
            } else {
                echo '<div class="notice notice-warning is-dismissible"><p>Import batch selesai. Klik lanjut untuk batch berikutnya.</p></div>';
                self::render_auto_continue_form($progress_action, $next_offset);
            }
        }

        if (isset($_GET['message']) && 'already-complete' === sanitize_key(wp_unslash($_GET['message']))) {
            echo '<div class="notice notice-info is-dismissible"><p>Level ini sudah lengkap, import ulang dikunci.</p></div>';
        }

        echo '<table class="widefat striped" style="max-width:900px">';
        echo '<thead><tr><th>Level</th><th>Jumlah</th><th>Aksi</th></tr></thead><tbody>';
        self::render_row(
            'Provinsi',
            (string) $counts['provinces'],
            'import_provinces',
            'Import Provinsi',
            self::get_row_offset('import_provinces', $progress_action, $next_offset, $complete),
            self::get_row_status('import_provinces', $progress_action, $processed, $next_offset, $complete, $states['import_provinces']),
            $states['import_provinces']['complete']
        );
        self::render_row(
            'Kabupaten',
            (string) $counts['regencies'],
            'import_regencies',
            'Import Kabupaten',
            self::get_row_offset('import_regencies', $progress_action, $next_offset, $complete),
            self::get_row_status('import_regencies', $progress_action, $processed, $next_offset, $complete, $states['import_regencies']),
            $states['import_regencies']['complete']
        );
        self::render_row(
            'Kecamatan',
            (string) $counts['districts'],
            'import_districts',
            'Import Kecamatan',
            self::get_row_offset('import_districts', $progress_action, $next_offset, $complete),
            self::get_row_status('import_districts', $progress_action, $processed, $next_offset, $complete, $states['import_districts']),
            $states['import_districts']['complete']
        );
        self::render_row(
            'Desa',
            (string) $counts['villages'],
            'import_villages',
            'Import Desa',
            self::get_row_offset('import_villages', $progress_action, $next_offset, $complete),
            self::get_row_status('import_villages', $progress_action, $processed, $next_offset, $complete, $states['import_villages']),
            $states['import_villages']['complete']
        );
        echo '</tbody></table>';
        echo '</div>';
    }

    private static function render_row(string $label, string $count, string $action, string $button_label, int $offset, string $status, bool $disabled): void
    {
        echo '<tr>';
        echo '<td>' . esc_html($label) . '</td>';
        echo '<td>' . esc_html($count);

        if ('' !== $status) {
            echo '<br><small>' . esc_html($status) . '</small>';
        }

        echo '</td>';
        echo '<td>';
        echo '<form method="post" action="' . esc_url(admin_url('admin.php?page=wdb-regions')) . '">';
        wp_nonce_field('wdb_regions_import');
        echo '<input type="hidden" name="page" value="wdb-regions">';
        echo '<input type="hidden" name="wdb_region_action" value="' . esc_attr($action) . '">';
        echo '<input type="hidden" name="offset" value="' . esc_attr((string) $offset) . '">';
        submit_button($disabled ? 'Selesai' : ($offset > 0 ? 'Lanjut ' . $button_label : $button_label), 'secondary', '', false, $disabled ? ['disabled' => 'disabled'] : []);
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    private static function import_provinces(): array
    {
        global $wpdb;

        self::truncate_region_tables();

        $items = self::request_json(self::BASE_URL . '/provinces.json');
        $table = $wpdb->prefix . 'wdb_regions_provinces';

        foreach ($items as $item) {
            $wpdb->replace(
                $table,
                [
                    'id' => (string) $item['code'],
                    'name' => (string) $item['name'],
                ],
                ['%s', '%s']
            );
        }

        return [
            'action' => 'import_provinces',
            'processed' => count($items),
            'next_offset' => 0,
            'complete' => true,
        ];
    }

    private static function import_regencies(int $offset): array
    {
        global $wpdb;

        $total = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_provinces');
        $provinces = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id FROM ' . $wpdb->prefix . 'wdb_regions_provinces ORDER BY id ASC LIMIT %d OFFSET %d',
                self::REGENCIES_BATCH_SIZE,
                $offset
            ),
            ARRAY_A
        );
        $table = $wpdb->prefix . 'wdb_regions_regencies';

        foreach ($provinces as $province) {
            $items = self::request_json(self::BASE_URL . '/regencies/' . $province['id'] . '.json');

            foreach ($items as $item) {
                $wpdb->replace(
                    $table,
                    [
                        'id' => (string) $item['code'],
                        'province_id' => (string) $province['id'],
                        'name' => (string) $item['name'],
                    ],
                    ['%s', '%s', '%s']
                );
            }
        }

        return self::build_batch_result('import_regencies', $offset, count($provinces), $total);
    }

    private static function import_districts(): array
    {
        global $wpdb;

        $regency_table = $wpdb->prefix . 'wdb_regions_regencies';
        $table = $wpdb->prefix . 'wdb_regions_districts';
        $regencies = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT regencies.id
                FROM {$regency_table} AS regencies
                LEFT JOIN {$table} AS districts ON districts.regency_id = regencies.id
                GROUP BY regencies.id
                HAVING COUNT(districts.id) = 0
                ORDER BY regencies.id ASC
                LIMIT %d",
                self::DISTRICTS_BATCH_SIZE
            ),
            ARRAY_A
        );

        foreach ($regencies as $regency) {
            $items = self::request_json(self::BASE_URL . '/districts/' . $regency['id'] . '.json');

            foreach ($items as $item) {
                $wpdb->replace(
                    $table,
                    [
                        'id' => (string) $item['code'],
                        'regency_id' => (string) $regency['id'],
                        'name' => (string) $item['name'],
                    ],
                    ['%s', '%s', '%s']
                );
            }
        }

        return self::build_pending_result('import_districts', count($regencies), count($regencies) === self::DISTRICTS_BATCH_SIZE);
    }

    private static function import_villages(): array
    {
        global $wpdb;

        $district_table = $wpdb->prefix . 'wdb_regions_districts';
        $table = $wpdb->prefix . 'wdb_regions_villages';
        $districts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT districts.id
                FROM {$district_table} AS districts
                LEFT JOIN {$table} AS villages ON villages.district_id = districts.id
                GROUP BY districts.id
                HAVING COUNT(villages.id) = 0
                ORDER BY districts.id ASC
                LIMIT %d",
                self::VILLAGES_BATCH_SIZE
            ),
            ARRAY_A
        );

        foreach ($districts as $district) {
            $items = self::request_json(self::BASE_URL . '/villages/' . $district['id'] . '.json');

            foreach ($items as $item) {
                $wpdb->replace(
                    $table,
                    [
                        'id' => (string) $item['code'],
                        'district_id' => (string) $district['id'],
                        'name' => (string) $item['name'],
                    ],
                    ['%s', '%s', '%s']
                );
            }
        }

        return self::build_pending_result('import_villages', count($districts), count($districts) === self::VILLAGES_BATCH_SIZE);
    }

    private static function build_batch_result(string $action, int $offset, int $processed, int $total): array
    {
        $next_offset = $offset + $processed;
        $complete = $next_offset >= $total || 0 === $processed;

        return [
            'action' => $action,
            'processed' => $processed,
            'next_offset' => $complete ? 0 : $next_offset,
            'complete' => $complete,
        ];
    }

    private static function build_pending_result(string $action, int $processed, bool $has_more): array
    {
        return [
            'action' => $action,
            'processed' => $processed,
            'next_offset' => $has_more ? 1 : 0,
            'complete' => ! $has_more,
        ];
    }

    private static function get_row_offset(string $action, string $progress_action, int $next_offset, bool $complete): int
    {
        if ($action !== $progress_action || $complete) {
            return 0;
        }

        return $next_offset;
    }

    private static function get_row_status(string $action, string $progress_action, int $processed, int $next_offset, bool $complete, array $state): string
    {
        if ($action !== $progress_action) {
            return $state['status'];
        }

        if ($complete) {
            return 'Batch terakhir selesai. Parent diproses: ' . $processed;
        }

        if (in_array($action, ['import_districts', 'import_villages'], true)) {
            return 'Batch selesai. Lanjutkan parent yang belum punya data.';
        }

        return 'Batch selesai. Lanjut dari offset ' . $next_offset . '.';
    }

    private static function get_action_state(string $action): array
    {
        global $wpdb;

        if ('import_provinces' === $action) {
            $count = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_provinces');

            return [
                'complete' => $count >= 38,
                'status' => $count >= 38 ? 'Sudah lengkap.' : '',
            ];
        }

        if ('import_regencies' === $action) {
            $pending = (int) $wpdb->get_var(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_provinces AS provinces WHERE NOT EXISTS (SELECT 1 FROM ' . $wpdb->prefix . 'wdb_regions_regencies AS regencies WHERE regencies.province_id = provinces.id)'
            );

            return [
                'complete' => $pending === 0 && self::has_rows('wdb_regions_provinces'),
                'status' => $pending === 0 && self::has_rows('wdb_regions_provinces') ? 'Sudah lengkap.' : '',
            ];
        }

        if ('import_districts' === $action) {
            $pending = (int) $wpdb->get_var(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_regencies AS regencies WHERE NOT EXISTS (SELECT 1 FROM ' . $wpdb->prefix . 'wdb_regions_districts AS districts WHERE districts.regency_id = regencies.id)'
            );

            return [
                'complete' => $pending === 0 && self::has_rows('wdb_regions_regencies'),
                'status' => $pending === 0 && self::has_rows('wdb_regions_regencies') ? 'Sudah lengkap.' : '',
            ];
        }

        $pending = (int) $wpdb->get_var(
            'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_districts AS districts WHERE NOT EXISTS (SELECT 1 FROM ' . $wpdb->prefix . 'wdb_regions_villages AS villages WHERE villages.district_id = districts.id)'
        );

        return [
            'complete' => $pending === 0 && self::has_rows('wdb_regions_districts'),
            'status' => $pending === 0 && self::has_rows('wdb_regions_districts') ? 'Sudah lengkap.' : '',
        ];
    }

    private static function is_action_complete(string $action): bool
    {
        $state = self::get_action_state($action);

        return ! empty($state['complete']);
    }

    private static function has_rows(string $table): bool
    {
        global $wpdb;

        return (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . $table) > 0;
    }

    private static function render_auto_continue_form(string $action, int $offset): void
    {
        if ('' === $action || $offset < 0) {
            return;
        }

        echo '<form id="wdb-region-auto-continue" method="post" action="' . esc_url(admin_url('admin.php?page=wdb-regions')) . '" style="display:none;">';
        wp_nonce_field('wdb_regions_import');
        echo '<input type="hidden" name="page" value="wdb-regions">';
        echo '<input type="hidden" name="wdb_region_action" value="' . esc_attr($action) . '">';
        echo '<input type="hidden" name="offset" value="' . esc_attr((string) $offset) . '">';
        echo '</form>';
        echo '<script>document.addEventListener("DOMContentLoaded",function(){var form=document.getElementById("wdb-region-auto-continue");if(form){form.submit();}});</script>';
    }

    private static function truncate_region_tables(): void
    {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'wdb_regions_villages',
            $wpdb->prefix . 'wdb_regions_districts',
            $wpdb->prefix . 'wdb_regions_regencies',
            $wpdb->prefix . 'wdb_regions_provinces',
        ];

        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$table}");
        }
    }

    private static function request_json(string $url): array
    {
        $response = wp_remote_get(
            $url,
            [
                'timeout' => 20,
            ]
        );

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }
}
