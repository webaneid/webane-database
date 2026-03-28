<?php

namespace WDB\Admin;

if (! defined('ABSPATH')) {
    exit;
}

final class Menu
{
    public static function boot(): void
    {
        add_action('admin_menu', [self::class, 'register']);
        add_action('admin_init', [CrudPage::class, 'handle_request']);
        add_action('admin_init', [RegionImporter::class, 'handle_request']);
        add_action('admin_init', [self::class, 'handle_dashboard_settings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_head-nav-menus.php', [self::class, 'register_nav_menu_meta_box']);
        add_action('wp_ajax_wdb_get_regions', [self::class, 'ajax_get_regions']);
    }

    public static function register(): void
    {
        add_menu_page(
            'Webane Database',
            'Webane Database',
            'manage_options',
            'wdb-dashboard',
            [self::class, 'render_dashboard'],
            'dashicons-database',
            26
        );

        add_submenu_page(
            'wdb-dashboard',
            'Dashboard Database',
            'Dashboard Database',
            'manage_options',
            'wdb-dashboard',
            [self::class, 'render_dashboard']
        );

        add_submenu_page(
            'wdb-dashboard',
            'Pesantren',
            'Pesantren',
            'manage_options',
            'wdb-pesantren',
            [self::class, 'render_pesantren']
        );

        add_submenu_page(
            'wdb-dashboard',
            'Alumni',
            'Alumni',
            'manage_options',
            'wdb-alumni',
            [self::class, 'render_alumni']
        );

        add_submenu_page(
            'wdb-dashboard',
            'Wilayah',
            'Wilayah',
            'manage_options',
            'wdb-regions',
            [self::class, 'render_regions']
        );
    }

    public static function render_dashboard(): void
    {
        global $wpdb;

        $pesantren_total = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_pesantren');
        $alumni_total = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_alumni');
        $pesantren_status = self::get_dashboard_status_counts('pesantren');
        $alumni_status = self::get_dashboard_status_counts('alumni');
        $region_totals = self::get_dashboard_region_totals();
        $records_total = $pesantren_total + $alumni_total;
        $filled_total = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM (
                SELECT status FROM {$wpdb->prefix}wdb_pesantren
                UNION ALL
                SELECT status FROM {$wpdb->prefix}wdb_alumni
            ) AS records
            WHERE status <> 'draft'"
        );
        $progress = $records_total > 0 ? (int) round(($filled_total / $records_total) * 100) : 0;
        $institution = self::get_institution_info();
        $focus_address = self::get_focus_address();
        $address = self::get_related_record('addresses', (int) ($institution['address_id'] ?? 0));
        $contact = self::get_related_record('contacts', (int) ($institution['contact_id'] ?? 0));
        $force_update_url = \WDB\Core\Updater::get_force_check_url(admin_url('admin.php?page=wdb-dashboard'));

        echo '<div class="wrap">';
        echo '<div class="wdb-admin-page-head">';
        echo '<h1>Dashboard Database</h1>';
        echo '<a href="' . esc_url($force_update_url) . '" class="button button-primary">Check Plugin Update</a>';
        echo '</div>';
        echo self::get_notice_html();
        echo '<div class="wdb-admin-tabs" data-wdb-tabs>';
        echo '<div class="wdb-admin-tab-list">';
        echo '<button type="button" class="wdb-admin-tab-button" data-wdb-tab-button="statistik" aria-pressed="true">Statistik</button>';
        echo '<button type="button" class="wdb-admin-tab-button" data-wdb-tab-button="info-lembaga" aria-pressed="false">Info Lembaga</button>';
        echo '<button type="button" class="wdb-admin-tab-button" data-wdb-tab-button="settings" aria-pressed="false">Settings</button>';
        echo '</div>';

        echo '<div class="wdb-admin-tab-panel" data-wdb-tab-panel="statistik">';
        echo '<div class="wdb-admin-hero">';
        echo '<div class="wdb-admin-hero__content">';
        echo '<p class="wdb-admin-hero__eyebrow">Webane Database</p>';
        echo '<h2 class="wdb-admin-hero__title">Ringkasan organisasi, kualitas data, dan progress wilayah</h2>';
        echo '<p class="wdb-admin-hero__text">Pantau perkembangan pesantren, alumni, status input, dan kesiapan data wilayah dari satu dashboard admin.</p>';
        echo '</div>';
        echo '<div class="wdb-admin-hero__badge">' . esc_html(number_format_i18n($records_total)) . '<span>total data utama</span></div>';
        echo '</div>';
        echo '<div class="wdb-admin-dashboard">';
        echo self::render_dashboard_metric_card('Total Pesantren', number_format_i18n($pesantren_total), number_format_i18n((int) ($pesantren_status['published'] ?? 0)) . ' published');
        echo self::render_dashboard_metric_card('Total Alumni', number_format_i18n($alumni_total), number_format_i18n((int) ($alumni_status['published'] ?? 0)) . ' published');
        echo self::render_dashboard_metric_card('Progress Pengisian', number_format_i18n($progress) . '%', number_format_i18n($filled_total) . ' dari ' . number_format_i18n($records_total) . ' data utama');
        echo self::render_dashboard_metric_card('Wilayah Terimport', number_format_i18n((int) ($region_totals['villages'] ?? 0)), 'desa siap dipakai');
        echo '</div>';
        echo '<div class="wdb-admin-chart-grid">';
        echo self::render_dashboard_donut_card(
            'Komposisi Data Utama',
            [
                ['label' => 'Pesantren', 'value' => $pesantren_total, 'color' => '#0f172a'],
                ['label' => 'Alumni', 'value' => $alumni_total, 'color' => '#2563eb'],
            ],
            'Pesantren vs alumni'
        );
        echo self::render_dashboard_bar_card('Status Data Pesantren', self::prepare_dashboard_status_items($pesantren_status), 'Workflow data pesantren.');
        echo self::render_dashboard_bar_card('Status Data Alumni', self::prepare_dashboard_status_items($alumni_status), 'Workflow data alumni.');
        echo self::render_dashboard_bar_card(
            'Progress Import Wilayah',
            [
                ['label' => 'Provinsi', 'value' => (int) ($region_totals['provinces'] ?? 0), 'color' => '#0f172a'],
                ['label' => 'Kabupaten', 'value' => (int) ($region_totals['regencies'] ?? 0), 'color' => '#1d4ed8'],
                ['label' => 'Kecamatan', 'value' => (int) ($region_totals['districts'] ?? 0), 'color' => '#0f766e'],
                ['label' => 'Desa', 'value' => (int) ($region_totals['villages'] ?? 0), 'color' => '#7c3aed'],
            ],
            'Jumlah data wilayah yang tersedia.'
        );
        echo '</div>';
        echo '</div>';

        echo '<div class="wdb-admin-tab-panel" data-wdb-tab-panel="info-lembaga">';
        echo '<div class="wdb-admin-panel-card">';
        echo '<form method="post" action="' . esc_url(admin_url('admin.php?page=wdb-dashboard')) . '">';
        wp_nonce_field('wdb_save_dashboard_info');
        echo '<input type="hidden" name="wdb_dashboard_action" value="save_info_lembaga">';
        echo '<input type="hidden" name="address_id" value="' . esc_attr((string) ($institution['address_id'] ?? '')) . '">';
        echo '<input type="hidden" name="contact_id" value="' . esc_attr((string) ($institution['contact_id'] ?? '')) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="wdb-nama-lembaga">Nama Lembaga</label></th><td><input type="text" class="regular-text" id="wdb-nama-lembaga" name="nama_lembaga" value="' . esc_attr((string) ($institution['name'] ?? '')) . '" placeholder="Contoh: Webane Database"></td></tr>';
        echo '<tr><th scope="row">Alamat Lembaga</th><td>' . self::render_dashboard_address_fields($address) . '</td></tr>';
        echo '<tr><th scope="row">Kontak Lembaga</th><td>' . self::render_dashboard_contact_fields($contact) . '</td></tr>';
        echo '</tbody></table>';
        submit_button('Simpan Info Lembaga');
        echo '</form>';
        echo '<div class="wdb-admin-info-grid">';
        echo '<div class="wdb-admin-info-card"><p class="wdb-admin-dashboard__label">Nama Lembaga</p><p class="wdb-admin-info-card__value">' . esc_html((string) ($institution['name'] ?? '-')) . '</p></div>';
        echo '<div class="wdb-admin-info-card"><p class="wdb-admin-dashboard__label">Alamat Lembaga</p><p class="wdb-admin-info-card__value">' . esc_html(self::format_address_detail($address)) . '</p></div>';
        echo '<div class="wdb-admin-info-card"><p class="wdb-admin-dashboard__label">Kontak Lembaga</p><p class="wdb-admin-info-card__value">' . esc_html(self::format_contact_detail($contact)) . '</p></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="wdb-admin-tab-panel" data-wdb-tab-panel="settings">';
        echo '<div class="wdb-admin-panel-card">';
        echo '<h2>Settings</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin.php?page=wdb-dashboard')) . '">';
        wp_nonce_field('wdb_save_dashboard_info');
        echo '<input type="hidden" name="wdb_dashboard_action" value="save_focus_alamat">';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row">Focus Alamat</th><td>';
        echo '<p class="description">Atur provinsi dan kabupaten default agar user cukup lanjut memilih kecamatan dan desa. Jika dikosongkan, semua form kembali seperti biasa.</p>';
        echo self::render_focus_address_fields($focus_address);
        echo '</td></tr>';
        echo '</tbody></table>';
        submit_button('Simpan Focus Alamat');
        echo '</form>';
        echo '<div class="wdb-admin-info-grid">';
        echo '<div class="wdb-admin-info-card"><p class="wdb-admin-dashboard__label">Focus Provinsi</p><p class="wdb-admin-info-card__value">' . esc_html((string) ($focus_address['provinsi_name'] ?? '-')) . '</p></div>';
        echo '<div class="wdb-admin-info-card"><p class="wdb-admin-dashboard__label">Focus Kabupaten</p><p class="wdb-admin-info-card__value">' . esc_html((string) ($focus_address['kabupaten_name'] ?? '-')) . '</p></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public static function render_pesantren(): void
    {
        CrudPage::render('wdb-pesantren');
    }

    public static function render_alumni(): void
    {
        CrudPage::render('wdb-alumni');
    }

    public static function render_regions(): void
    {
        RegionImporter::render();
    }

    public static function register_nav_menu_meta_box(): void
    {
        add_meta_box(
            'add-wdb-public-links',
            'Webane Database',
            [self::class, 'render_nav_menu_meta_box'],
            'nav-menus',
            'side',
            'default'
        );
    }

    public static function render_nav_menu_meta_box(): void
    {
        $items = self::get_nav_menu_items();

        echo '<div id="posttype-wdb-public-links" class="posttypediv">';
        echo '<div id="tabs-panel-wdb-public-links-all" class="tabs-panel tabs-panel-active">';
        echo '<ul id="wdb-public-links-checklist" class="categorychecklist form-no-clear">';
        echo walk_nav_menu_tree(
            array_map('wp_setup_nav_menu_item', $items),
            0,
            (object) [
                'walker' => new \Walker_Nav_Menu_Checklist(),
            ]
        );
        echo '</ul>';
        echo '</div>';
        echo '<p class="button-controls wp-clearfix">';
        echo '<span class="list-controls hide-if-no-js">';
        echo '<input type="checkbox" id="wdb-public-links-checkall">';
        echo '<label for="wdb-public-links-checkall">Pilih Semua</label>';
        echo '</span>';
        echo '<span class="add-to-menu">';
        echo '<input type="submit" class="button submit-add-to-menu right" value="Tambahkan ke Menu" name="add-wdb-public-links-menu-item" id="submit-posttype-wdb-public-links">';
        echo '<span class="spinner"></span>';
        echo '</span>';
        echo '</p>';
        echo '</div>';
    }

    private static function get_nav_menu_items(): array
    {
        $definitions = \WDB\Frontend\Forms::get_public_menu_links();
        $items = [];
        $placeholder = -1;

        foreach ($definitions as $definition) {
            $items[] = (object) [
                'ID' => 0,
                'db_id' => 0,
                'object_id' => $placeholder,
                'post_parent' => 0,
                'type' => 'custom',
                'object' => 'custom',
                'type_label' => 'Custom Link',
                'title' => $definition['title'],
                'url' => $definition['url'],
                'target' => '',
                'attr_title' => '',
                'description' => '',
                'classes' => [],
                'xfn' => '',
                'menu_item_parent' => 0,
            ];
            $placeholder--;
        }

        return $items;
    }

    public static function enqueue_assets(string $hook): void
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if (0 !== strpos($page, 'wdb-')) {
            return;
        }

        wp_enqueue_style(
            'wdb-admin',
            WDB_PLUGIN_URL . 'assets/css/admin.css',
            [],
            \WDB\Core\Plugin::asset_version('assets/css/admin.css')
        );

        wp_enqueue_media();

        wp_enqueue_script(
            'wdb-admin',
            WDB_PLUGIN_URL . 'assets/js/admin.js',
            [],
            \WDB\Core\Plugin::asset_version('assets/js/admin.js'),
            true
        );

        wp_enqueue_script(
            'wdb-autocomplete',
            WDB_PLUGIN_URL . 'assets/js/autocomplete.js',
            [],
            \WDB\Core\Plugin::asset_version('assets/js/autocomplete.js'),
            true
        );

        if (! in_array($page, ['wdb-dashboard', 'wdb-addresses', 'wdb-pesantren', 'wdb-alumni'], true)) {
            return;
        }

        wp_enqueue_script(
            'wdb-address-fields',
            WDB_PLUGIN_URL . 'assets/js/address-fields.js',
            [],
            \WDB\Core\Plugin::asset_version('assets/js/address-fields.js'),
            true
        );

        wp_localize_script(
            'wdb-address-fields',
            'wdbAddressFields',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wdb_regions_nonce'),
            ]
        );
    }

    public static function ajax_get_regions(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        check_ajax_referer('wdb_regions_nonce', 'nonce');

        global $wpdb;

        $level = isset($_GET['level']) ? sanitize_key(wp_unslash($_GET['level'])) : '';
        $parent_id = isset($_GET['parent_id']) ? sanitize_text_field(wp_unslash($_GET['parent_id'])) : '';

        if ('provinces' === $level) {
            $table = $wpdb->prefix . 'wdb_regions_provinces';
            $query = "SELECT id, name FROM {$table} ORDER BY name ASC";
            $rows = $wpdb->get_results($query, ARRAY_A);
            wp_send_json_success(['items' => $rows ?: []]);
        }

        if ('regencies' === $level) {
            $table = $wpdb->prefix . 'wdb_regions_regencies';
            $query = $wpdb->prepare(
                "SELECT id, name FROM {$table} WHERE province_id = %s ORDER BY name ASC",
                $parent_id
            );
            $rows = $wpdb->get_results($query, ARRAY_A);
            wp_send_json_success(['items' => $rows ?: []]);
        }

        if ('districts' === $level) {
            $table = $wpdb->prefix . 'wdb_regions_districts';
            $query = $wpdb->prepare(
                "SELECT id, name FROM {$table} WHERE regency_id = %s ORDER BY name ASC",
                $parent_id
            );
            $rows = $wpdb->get_results($query, ARRAY_A);
            wp_send_json_success(['items' => $rows ?: []]);
        }

        if ('villages' === $level) {
            $table = $wpdb->prefix . 'wdb_regions_villages';
            $query = $wpdb->prepare(
                "SELECT id, name FROM {$table} WHERE district_id = %s ORDER BY name ASC",
                $parent_id
            );
            $rows = $wpdb->get_results($query, ARRAY_A);
            wp_send_json_success(['items' => $rows ?: []]);
        }

        wp_send_json_error(['message' => 'Invalid level'], 400);
    }

    public static function handle_dashboard_settings(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        if ('POST' !== $_SERVER['REQUEST_METHOD']) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $action = isset($_POST['wdb_dashboard_action']) ? sanitize_key(wp_unslash($_POST['wdb_dashboard_action'])) : '';

        if ('wdb-dashboard' !== $page || ! in_array($action, ['save_info_lembaga', 'save_focus_alamat'], true)) {
            return;
        }

        check_admin_referer('wdb_save_dashboard_info');

        if ('save_info_lembaga' === $action) {
            update_option(
                'wdb_institution_info',
                [
                    'name' => isset($_POST['nama_lembaga']) ? sanitize_text_field(wp_unslash($_POST['nama_lembaga'])) : '',
                    'address_id' => self::save_dashboard_address(isset($_POST['address_id']) ? absint(wp_unslash($_POST['address_id'])) : 0),
                    'contact_id' => self::save_dashboard_contact(isset($_POST['contact_id']) ? absint(wp_unslash($_POST['contact_id'])) : 0),
                ]
            );
        }

        if ('save_focus_alamat' === $action) {
            update_option(
                'wdb_focus_address',
                [
                    'provinsi_code' => isset($_POST['focus_provinsi_code']) ? sanitize_text_field(wp_unslash($_POST['focus_provinsi_code'])) : '',
                    'provinsi_name' => isset($_POST['focus_provinsi_name']) ? sanitize_text_field(wp_unslash($_POST['focus_provinsi_name'])) : '',
                    'kabupaten_code' => isset($_POST['focus_kabupaten_code']) ? sanitize_text_field(wp_unslash($_POST['focus_kabupaten_code'])) : '',
                    'kabupaten_name' => isset($_POST['focus_kabupaten_name']) ? sanitize_text_field(wp_unslash($_POST['focus_kabupaten_name'])) : '',
                ]
            );
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'wdb-dashboard',
                    'message' => 'updated',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    private static function get_notice_html(): string
    {
        if (! isset($_GET['message'])) {
            return '';
        }

        $message = sanitize_key(wp_unslash($_GET['message']));

        if ('updated' !== $message) {
            return '';
        }

        return '<div class="notice notice-success is-dismissible"><p>Data berhasil diperbarui.</p></div>';
    }

    private static function get_dashboard_status_counts(string $table): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            'SELECT status, COUNT(*) AS total FROM ' . $wpdb->prefix . 'wdb_' . $table . ' GROUP BY status',
            ARRAY_A
        ) ?: [];
        $counts = [
            'draft' => 0,
            'pending' => 0,
            'published' => 0,
        ];

        foreach ($rows as $row) {
            $status = isset($row['status']) ? (string) $row['status'] : '';

            if (isset($counts[$status])) {
                $counts[$status] = (int) ($row['total'] ?? 0);
            }
        }

        return $counts;
    }

    private static function get_dashboard_region_totals(): array
    {
        global $wpdb;

        return [
            'provinces' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_provinces'),
            'regencies' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_regencies'),
            'districts' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_districts'),
            'villages' => (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdb_regions_villages'),
        ];
    }

    private static function prepare_dashboard_status_items(array $counts): array
    {
        return [
            ['label' => 'Draft', 'value' => (int) ($counts['draft'] ?? 0), 'color' => '#94a3b8'],
            ['label' => 'Pending', 'value' => (int) ($counts['pending'] ?? 0), 'color' => '#f59e0b'],
            ['label' => 'Published', 'value' => (int) ($counts['published'] ?? 0), 'color' => '#16a34a'],
        ];
    }

    private static function render_dashboard_metric_card(string $label, string $value, string $meta = ''): string
    {
        $html = '<article class="wdb-admin-dashboard__card">';
        $html .= '<p class="wdb-admin-dashboard__label">' . esc_html($label) . '</p>';
        $html .= '<p class="wdb-admin-dashboard__value">' . esc_html($value) . '</p>';

        if ('' !== trim($meta)) {
            $html .= '<p class="wdb-admin-dashboard__meta">' . esc_html($meta) . '</p>';
        }

        $html .= '</article>';

        return $html;
    }

    private static function render_dashboard_donut_card(string $title, array $items, string $caption = ''): string
    {
        $total = 0;
        $segments = [];
        $legend = '';

        foreach ($items as $item) {
            $total += (int) ($item['value'] ?? 0);
        }

        $offset = 0.0;

        foreach ($items as $item) {
            $value = (int) ($item['value'] ?? 0);
            $color = (string) ($item['color'] ?? '#0f172a');
            $label = (string) ($item['label'] ?? '');
            $ratio = $total > 0 ? ($value / $total) : 0;
            $end = $offset + ($ratio * 100);
            $segments[] = $color . ' ' . number_format($offset, 2, '.', '') . '% ' . number_format($end, 2, '.', '') . '%';
            $legend .= '<div class="wdb-admin-chart__legend-item"><span class="wdb-admin-chart__legend-dot" style="background:' . esc_attr($color) . ';"></span><span class="wdb-admin-chart__legend-label">' . esc_html($label) . '</span><strong>' . esc_html(number_format_i18n($value)) . '</strong></div>';
            $offset = $end;
        }

        $background = empty($segments) ? '#e2e8f0' : 'conic-gradient(' . implode(', ', $segments) . ')';
        $html = '<article class="wdb-admin-chart-card">';
        $html .= '<div class="wdb-admin-chart-card__head"><h3>' . esc_html($title) . '</h3></div>';
        $html .= '<div class="wdb-admin-chart-card__donut-wrap">';
        $html .= '<div class="wdb-admin-chart-card__donut" style="background:' . esc_attr($background) . ';"><div class="wdb-admin-chart-card__donut-center"><strong>' . esc_html(number_format_i18n($total)) . '</strong><span>Total</span></div></div>';
        $html .= '<div class="wdb-admin-chart__legend">' . $legend . '</div>';
        $html .= '</div>';

        if ('' !== trim($caption)) {
            $html .= '<p class="wdb-admin-chart-card__caption">' . esc_html($caption) . '</p>';
        }

        $html .= '</article>';

        return $html;
    }

    private static function render_dashboard_bar_card(string $title, array $items, string $caption = ''): string
    {
        $max = 0;

        foreach ($items as $item) {
            $max = max($max, (int) ($item['value'] ?? 0));
        }

        $html = '<article class="wdb-admin-chart-card">';
        $html .= '<div class="wdb-admin-chart-card__head"><h3>' . esc_html($title) . '</h3></div>';
        $html .= '<div class="wdb-admin-chart__rows">';

        foreach ($items as $item) {
            $label = (string) ($item['label'] ?? '');
            $value = (int) ($item['value'] ?? 0);
            $color = (string) ($item['color'] ?? '#0f172a');
            $width = $max > 0 ? max(6, (int) round(($value / $max) * 100)) : 0;
            $html .= '<div class="wdb-admin-chart__row">';
            $html .= '<div class="wdb-admin-chart__meta"><span>' . esc_html($label) . '</span><strong>' . esc_html(number_format_i18n($value)) . '</strong></div>';
            $html .= '<div class="wdb-admin-chart__track"><span class="wdb-admin-chart__bar" style="width:' . esc_attr((string) $width) . '%;background:' . esc_attr($color) . ';"></span></div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        if ('' !== trim($caption)) {
            $html .= '<p class="wdb-admin-chart-card__caption">' . esc_html($caption) . '</p>';
        }

        $html .= '</article>';

        return $html;
    }

    private static function get_institution_info(): array
    {
        $option = get_option('wdb_institution_info', []);

        return is_array($option) ? $option : [];
    }

    private static function get_focus_address(): array
    {
        $option = get_option('wdb_focus_address', []);

        return is_array($option) ? $option : [];
    }

    private static function get_relation_options(string $relation): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            'SELECT * FROM ' . $wpdb->prefix . 'wdb_' . $relation . ' ORDER BY id DESC LIMIT 500',
            ARRAY_A
        ) ?: [];
        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['id']] = self::build_relation_label($relation, $row);
        }

        return $options;
    }

    private static function render_dashboard_address_fields(?array $address): string
    {
        $address = is_array($address) ? $address : [];
        $html = '<input type="text" class="regular-text" name="alamat_lengkap" value="' . esc_attr((string) ($address['alamat_lengkap'] ?? '')) . '" placeholder="Contoh: Jl. Webane No. 10"><div style="height:8px"></div>';
        $html .= self::render_region_address_field($address);

        return $html;
    }

    private static function render_focus_address_fields(array $focus): string
    {
        return self::render_region_focus_field($focus);
    }

    private static function render_dashboard_contact_fields(?array $contact): string
    {
        $contact = is_array($contact) ? $contact : [];
        $html = '<div data-wdb-contact-sync>';
        $html .= '<p><label>Email</label><br><input type="email" class="regular-text" name="email" value="' . esc_attr((string) ($contact['email'] ?? '')) . '" placeholder="contoh@domain.com"></p>';
        $html .= '<p><label>Nomor HP</label><br><input type="text" class="regular-text" name="nomor_hp" value="' . esc_attr((string) ($contact['nomor_hp'] ?? '')) . '" placeholder="08xxxxxxxxxx" data-wdb-contact-hp></p>';
        $html .= '<p><label>Nomor WhatsApp</label><br><input type="text" class="regular-text" name="nomor_whatsapp" value="' . esc_attr((string) ($contact['nomor_whatsapp'] ?? '')) . '" placeholder="08xxxxxxxxxx" data-wdb-contact-wa></p>';
        $html .= '<label><input type="checkbox" name="whatsapp_sama_dengan_hp" value="1" data-wdb-contact-sync-toggle' . checked(! empty($contact['whatsapp_sama_dengan_hp']), true, false) . '> WhatsApp sama dengan HP</label>';
        $html .= '</div>';

        return $html;
    }

    private static function save_dashboard_address(int $address_id): ?int
    {
        global $wpdb;

        $data = [
            'alamat_lengkap' => isset($_POST['alamat_lengkap']) ? sanitize_textarea_field(wp_unslash($_POST['alamat_lengkap'])) : '',
            'provinsi_code' => isset($_POST['provinsi_code']) ? sanitize_text_field(wp_unslash($_POST['provinsi_code'])) : '',
            'provinsi_name' => isset($_POST['provinsi_name']) ? sanitize_text_field(wp_unslash($_POST['provinsi_name'])) : '',
            'kabupaten_code' => isset($_POST['kabupaten_code']) ? sanitize_text_field(wp_unslash($_POST['kabupaten_code'])) : '',
            'kabupaten_name' => isset($_POST['kabupaten_name']) ? sanitize_text_field(wp_unslash($_POST['kabupaten_name'])) : '',
            'kecamatan_code' => isset($_POST['kecamatan_code']) ? sanitize_text_field(wp_unslash($_POST['kecamatan_code'])) : '',
            'kecamatan_name' => isset($_POST['kecamatan_name']) ? sanitize_text_field(wp_unslash($_POST['kecamatan_name'])) : '',
            'desa_code' => isset($_POST['desa_code']) ? sanitize_text_field(wp_unslash($_POST['desa_code'])) : '',
            'desa_name' => isset($_POST['desa_name']) ? sanitize_text_field(wp_unslash($_POST['desa_name'])) : '',
            'updated_at' => current_time('mysql'),
        ];

        if ($address_id > 0) {
            $wpdb->update($wpdb->prefix . 'wdb_addresses', $data, ['id' => $address_id], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'], ['%d']);

            return $address_id;
        }

        if ('' === $data['alamat_lengkap'] && '' === $data['provinsi_code'] && '' === $data['kabupaten_code'] && '' === $data['kecamatan_code'] && '' === $data['desa_code']) {
            return null;
        }

        $data['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'wdb_addresses', $data, ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function save_dashboard_contact(int $contact_id): ?int
    {
        global $wpdb;

        $whatsapp_same = isset($_POST['whatsapp_sama_dengan_hp']) ? 1 : 0;
        $nomor_hp = isset($_POST['nomor_hp']) ? sanitize_text_field(wp_unslash($_POST['nomor_hp'])) : '';
        $data = [
            'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
            'nomor_hp' => $nomor_hp,
            'nomor_whatsapp' => $whatsapp_same ? $nomor_hp : (isset($_POST['nomor_whatsapp']) ? sanitize_text_field(wp_unslash($_POST['nomor_whatsapp'])) : ''),
            'whatsapp_sama_dengan_hp' => $whatsapp_same,
            'updated_at' => current_time('mysql'),
        ];

        if ($contact_id > 0) {
            $wpdb->update($wpdb->prefix . 'wdb_contacts', $data, ['id' => $contact_id], ['%s', '%s', '%s', '%d', '%s'], ['%d']);

            return $contact_id;
        }

        if ('' === $data['email'] && '' === $data['nomor_hp'] && '' === $data['nomor_whatsapp']) {
            return null;
        }

        $data['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'wdb_contacts', $data, ['%s', '%s', '%s', '%d', '%s', '%s']);

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function render_region_address_field(array $record, string $prefix = ''): string
    {
        $record = self::apply_focus_address_defaults($record);
        $provinsi_code = isset($record['provinsi_code']) ? (string) $record['provinsi_code'] : '';
        $provinsi_name = isset($record['provinsi_name']) ? (string) $record['provinsi_name'] : '';
        $kabupaten_code = isset($record['kabupaten_code']) ? (string) $record['kabupaten_code'] : '';
        $kabupaten_name = isset($record['kabupaten_name']) ? (string) $record['kabupaten_name'] : '';
        $kecamatan_code = isset($record['kecamatan_code']) ? (string) $record['kecamatan_code'] : '';
        $kecamatan_name = isset($record['kecamatan_name']) ? (string) $record['kecamatan_name'] : '';
        $desa_code = isset($record['desa_code']) ? (string) $record['desa_code'] : '';
        $desa_name = isset($record['desa_name']) ? (string) $record['desa_name'] : '';

        $html = '';
        $html .= '<div class="wdb-region-field" data-wdb-region-scope>';
        $html .= '<select data-wdb-region-select="provinsi" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari provinsi" class="regular-text"><option value="">Pilih Provinsi</option></select><br><br>';
        $html .= '<select data-wdb-region-select="kabupaten" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari kabupaten" class="regular-text"><option value="">Pilih Kabupaten</option></select><br><br>';
        $html .= '<select data-wdb-region-select="kecamatan" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari kecamatan" class="regular-text"><option value="">Pilih Kecamatan</option></select><br><br>';
        $html .= '<select data-wdb-region-select="desa" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari desa" class="regular-text"><option value="">Pilih Desa</option></select>';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'provinsi_code') . '" data-wdb-region-code="provinsi" value="' . esc_attr($provinsi_code) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'provinsi_name') . '" data-wdb-region-name="provinsi" value="' . esc_attr($provinsi_name) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'kabupaten_code') . '" data-wdb-region-code="kabupaten" value="' . esc_attr($kabupaten_code) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'kabupaten_name') . '" data-wdb-region-name="kabupaten" value="' . esc_attr($kabupaten_name) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'kecamatan_code') . '" data-wdb-region-code="kecamatan" value="' . esc_attr($kecamatan_code) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'kecamatan_name') . '" data-wdb-region-name="kecamatan" value="' . esc_attr($kecamatan_name) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'desa_code') . '" data-wdb-region-code="desa" value="' . esc_attr($desa_code) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'desa_name') . '" data-wdb-region-name="desa" value="' . esc_attr($desa_name) . '">';
        $html .= '<script type="application/json" data-wdb-region-selected>' . wp_json_encode(
            [
                'provinsi_code' => $provinsi_code,
                'kabupaten_code' => $kabupaten_code,
                'kecamatan_code' => $kecamatan_code,
                'desa_code' => $desa_code,
            ]
        ) . '</script>';
        $html .= '</div>';

        return $html;
    }

    private static function render_region_focus_field(array $record, string $prefix = 'focus_'): string
    {
        $provinsi_code = isset($record['provinsi_code']) ? (string) $record['provinsi_code'] : '';
        $provinsi_name = isset($record['provinsi_name']) ? (string) $record['provinsi_name'] : '';
        $kabupaten_code = isset($record['kabupaten_code']) ? (string) $record['kabupaten_code'] : '';
        $kabupaten_name = isset($record['kabupaten_name']) ? (string) $record['kabupaten_name'] : '';
        $provinces = self::get_region_rows('provinces');
        $regencies = '' !== $provinsi_code ? self::get_region_rows('regencies', $provinsi_code) : [];

        $html = '';
        $html .= '<div class="wdb-region-field" data-wdb-region-scope>';
        $html .= '<select data-wdb-region-select="provinsi" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari provinsi" class="regular-text"><option value="">Pilih Provinsi</option>';

        foreach ($provinces as $province) {
            $html .= '<option value="' . esc_attr((string) $province['id']) . '"' . selected($provinsi_code, (string) $province['id'], false) . '>' . esc_html((string) $province['name']) . '</option>';
        }

        $html .= '</select><br><br>';
        $html .= '<select data-wdb-region-select="kabupaten" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari kabupaten" class="regular-text"><option value="">Pilih Kabupaten</option>';

        foreach ($regencies as $regency) {
            $html .= '<option value="' . esc_attr((string) $regency['id']) . '"' . selected($kabupaten_code, (string) $regency['id'], false) . '>' . esc_html((string) $regency['name']) . '</option>';
        }

        $html .= '</select>';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'provinsi_code') . '" data-wdb-region-code="provinsi" value="' . esc_attr($provinsi_code) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'provinsi_name') . '" data-wdb-region-name="provinsi" value="' . esc_attr($provinsi_name) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'kabupaten_code') . '" data-wdb-region-code="kabupaten" value="' . esc_attr($kabupaten_code) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'kabupaten_name') . '" data-wdb-region-name="kabupaten" value="' . esc_attr($kabupaten_name) . '">';
        $html .= '<script type="application/json" data-wdb-region-selected>' . wp_json_encode(
            [
                'provinsi_code' => $provinsi_code,
                'kabupaten_code' => $kabupaten_code,
                'kecamatan_code' => '',
                'desa_code' => '',
            ]
        ) . '</script>';
        $html .= '</div>';

        return $html;
    }

    private static function get_region_rows(string $level, string $parent_id = ''): array
    {
        global $wpdb;

        if ('provinces' === $level) {
            return $wpdb->get_results(
                'SELECT id, name FROM ' . $wpdb->prefix . 'wdb_regions_provinces ORDER BY name ASC',
                ARRAY_A
            ) ?: [];
        }

        if ('regencies' === $level && '' !== $parent_id) {
            return $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT id, name FROM ' . $wpdb->prefix . 'wdb_regions_regencies WHERE province_id = %s ORDER BY name ASC',
                    $parent_id
                ),
                ARRAY_A
            ) ?: [];
        }

        return [];
    }

    private static function apply_focus_address_defaults(array $record): array
    {
        if (! empty($record['provinsi_code']) || ! empty($record['kabupaten_code'])) {
            return $record;
        }

        $focus = self::get_focus_address();

        if (! is_array($focus) || (empty($focus['provinsi_code']) && empty($focus['kabupaten_code']))) {
            return $record;
        }

        $record['provinsi_code'] = (string) ($focus['provinsi_code'] ?? '');
        $record['provinsi_name'] = (string) ($focus['provinsi_name'] ?? '');
        $record['kabupaten_code'] = (string) ($focus['kabupaten_code'] ?? '');
        $record['kabupaten_name'] = (string) ($focus['kabupaten_name'] ?? '');

        return $record;
    }

    private static function build_relation_label(string $relation, array $row): string
    {
        if ('addresses' === $relation) {
            $parts = array_filter(
                [
                    (string) ($row['alamat_lengkap'] ?? ''),
                    (string) ($row['kecamatan_name'] ?? ''),
                    (string) ($row['kabupaten_name'] ?? ''),
                ]
            );

            return '#' . (int) $row['id'] . ' - ' . implode(' / ', $parts);
        }

        if ('contacts' === $relation) {
            $parts = array_filter(
                [
                    (string) ($row['email'] ?? ''),
                    (string) ($row['nomor_hp'] ?? ''),
                    (string) ($row['nomor_whatsapp'] ?? ''),
                ]
            );

            return '#' . (int) $row['id'] . ' - ' . implode(' / ', $parts);
        }

        return '#' . (int) $row['id'];
    }

    private static function get_related_record(string $table, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        global $wpdb;

        $record = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'wdb_' . $table . ' WHERE id = %d',
                $id
            ),
            ARRAY_A
        );

        return is_array($record) ? $record : null;
    }

    private static function format_address_detail(?array $address): string
    {
        if (! is_array($address)) {
            return '-';
        }

        $parts = array_filter(
            [
                trim((string) ($address['alamat_lengkap'] ?? '')),
                trim((string) ($address['desa_name'] ?? '')),
                trim((string) ($address['kecamatan_name'] ?? '')),
                trim((string) ($address['kabupaten_name'] ?? '')),
                trim((string) ($address['provinsi_name'] ?? '')),
            ]
        );

        return empty($parts) ? '-' : implode(', ', $parts);
    }

    private static function format_contact_detail(?array $contact): string
    {
        if (! is_array($contact)) {
            return '-';
        }

        $parts = array_filter(
            [
                trim((string) ($contact['email'] ?? '')),
                trim((string) ($contact['nomor_hp'] ?? '')),
                trim((string) ($contact['nomor_whatsapp'] ?? '')),
            ]
        );

        return empty($parts) ? '-' : implode(' / ', $parts);
    }
}
