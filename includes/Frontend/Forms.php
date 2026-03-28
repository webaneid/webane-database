<?php

namespace WDB\Frontend;

if (! defined('ABSPATH')) {
    exit;
}

final class Forms
{
    private const PUBLIC_ROUTES_VERSION = '0.0.1-thank-you';
    private const NAME_TAG_SYNC_VERSION = '0.0.2-name-tags';

    public static function boot(): void
    {
        add_action('init', [self::class, 'register_public_routes']);
        add_action('init', [self::class, 'maybe_sync_name_tags'], 25);
        add_action('init', [self::class, 'maybe_flush_public_routes'], 20);
        add_filter('query_vars', [self::class, 'register_query_vars']);
        add_action('template_redirect', [self::class, 'handle_public_routes']);
        add_action('wp_ajax_wdb_frontend_get_regions', [self::class, 'ajax_get_regions']);
        add_action('wp_ajax_nopriv_wdb_frontend_get_regions', [self::class, 'ajax_get_regions']);
        add_action('admin_post_wdb_save_pesantren', [self::class, 'handle_pesantren_submit']);
        add_action('admin_post_nopriv_wdb_save_pesantren', [self::class, 'handle_pesantren_submit']);
        add_action('admin_post_wdb_save_alumni', [self::class, 'handle_alumni_submit']);
        add_action('admin_post_nopriv_wdb_save_alumni', [self::class, 'handle_alumni_submit']);
        add_action('admin_post_wdb_save_address', [self::class, 'handle_address_submit']);
        add_action('admin_post_wdb_save_contact', [self::class, 'handle_contact_submit']);
        add_action('admin_post_wdb_save_social', [self::class, 'handle_social_submit']);
        add_action('admin_post_wdb_update_dashboard_alumni', [self::class, 'handle_dashboard_alumni_update']);
        add_action('admin_post_wdb_update_dashboard_pesantren', [self::class, 'handle_dashboard_pesantren_update']);
        add_shortcode('wdb_pesantren_form', [self::class, 'render_pesantren_form']);
        add_shortcode('wdb_alumni_form', [self::class, 'render_alumni_form']);
        add_shortcode('wdb_address_form', [self::class, 'render_address_form']);
        add_shortcode('wdb_contact_form', [self::class, 'render_contact_form']);
        add_shortcode('wdb_social_form', [self::class, 'render_social_form']);
        add_shortcode('wdb_dashboard', [self::class, 'render_dashboard']);
    }

    public static function maybe_flush_public_routes(): void
    {
        if (get_option('wdb_public_routes_version') === self::PUBLIC_ROUTES_VERSION) {
            return;
        }

        flush_rewrite_rules(false);
        update_option('wdb_public_routes_version', self::PUBLIC_ROUTES_VERSION);
    }

    public static function maybe_sync_name_tags(): void
    {
        if (get_option('wdb_name_tag_sync_version') === self::NAME_TAG_SYNC_VERSION) {
            return;
        }

        global $wpdb;

        $alumni_names = $wpdb->get_col("SELECT DISTINCT nama_lengkap FROM {$wpdb->prefix}wdb_alumni WHERE nama_lengkap <> ''");
        $pesantren_names = $wpdb->get_col("SELECT DISTINCT nama_pesantren FROM {$wpdb->prefix}wdb_pesantren WHERE nama_pesantren <> ''");

        foreach (array_merge($alumni_names ?: [], $pesantren_names ?: []) as $name) {
            self::ensure_name_tag_exists((string) $name);
        }

        update_option('wdb_name_tag_sync_version', self::NAME_TAG_SYNC_VERSION);
    }

    public static function register_public_routes(): void
    {
        $routes = self::get_public_menu_definitions();

        foreach ($routes as $route => $definition) {
            add_rewrite_rule(
                '^' . preg_quote($route, '/') . '/?$',
                'index.php?wdb_public_page=' . $route,
                'top'
            );
        }

        add_rewrite_rule(
            '^detail-alumni/([0-9]+)/?$',
            'index.php?wdb_public_page=detail-alumni&wdb_record_id=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^detail-pesantren/([0-9]+)/?$',
            'index.php?wdb_public_page=detail-pesantren&wdb_record_id=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^terima-kasih/?$',
            'index.php?wdb_public_page=terima-kasih',
            'top'
        );
    }

    public static function register_query_vars(array $vars): array
    {
        $vars[] = 'wdb_public_page';
        $vars[] = 'wdb_record_id';

        return $vars;
    }

    public static function handle_public_routes(): void
    {
        $page = (string) get_query_var('wdb_public_page');

        if ('' === $page) {
            return;
        }

        $record_id = absint(get_query_var('wdb_record_id'));

        if ($record_id <= 0 && isset($_GET['id'])) {
            $record_id = absint(wp_unslash($_GET['id']));
        }

        self::render_public_page($page, $record_id);
        exit;
    }

    public static function get_public_menu_definitions(): array
    {
        return [
            'form-pesantren' => ['title' => 'Form Pesantren'],
            'form-alumni' => ['title' => 'Form Alumni'],
            'arsip-alumni' => ['title' => 'Arsip Alumni'],
            'arsip-pesantren' => ['title' => 'Arsip Pesantren'],
            'data-statistik' => ['title' => 'Data Statistik'],
            'login-alumni' => ['title' => 'Login Alumni'],
            'dashboard-alumni' => ['title' => 'Dashboard Alumni'],
            'detail-alumni' => ['title' => 'Detail Alumni'],
            'detail-pesantren' => ['title' => 'Detail Pesantren'],
            'pencarian-data' => ['title' => 'Pencarian Data'],
        ];
    }

    public static function get_public_menu_links(): array
    {
        $items = [];

        foreach (self::get_public_menu_definitions() as $route => $definition) {
            $items[] = [
                'title' => $definition['title'],
                'url' => home_url('/' . $route . '/'),
            ];
        }

        return $items;
    }

    public static function render_pesantren_form(): string
    {
        self::enqueue_form_assets();

        $action_url = admin_url('admin-post.php');

        ob_start();
        echo self::get_message_html();
        echo '<div class="wdb-form-shell wdb-form-shell--pesantren"><form class="wdb-form" method="post" action="' . esc_url($action_url) . '" enctype="multipart/form-data">';
        wp_nonce_field('wdb_frontend_pesantren');
        echo '<input type="hidden" name="action" value="wdb_save_pesantren">';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url(self::get_current_url()) . '">';
        echo self::render_pesantren_fields();
        echo self::render_address_fields();
        echo self::render_contact_fields(false, 'pesantren');
        echo self::render_social_fields();
        echo '<p><button class="wdb-button" type="submit">Simpan</button></p>';
        echo '</form></div>';

        return (string) ob_get_clean();
    }

    public static function render_alumni_form(): string
    {
        self::enqueue_form_assets();

        $action_url = admin_url('admin-post.php');

        ob_start();
        echo self::get_message_html();
        echo '<div class="wdb-form-shell wdb-form-shell--alumni"><form class="wdb-form" method="post" action="' . esc_url($action_url) . '" enctype="multipart/form-data">';
        wp_nonce_field('wdb_frontend_alumni');
        echo '<input type="hidden" name="action" value="wdb_save_alumni">';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url(self::get_current_url()) . '">';
        echo self::render_alumni_fields();
        echo self::render_alumni_auth_fields();
        echo self::render_address_fields();
        echo self::render_contact_fields(false, 'alumni');
        echo self::render_job_fields();
        echo self::render_social_fields();
        echo '<p><button class="wdb-button" type="submit">Simpan</button></p>';
        echo '</form></div>';

        return (string) ob_get_clean();
    }

    public static function render_address_form(): string
    {
        if (! is_user_logged_in()) {
            return Auth::render_login_form();
        }

        self::enqueue_form_assets();

        $action_url = admin_url('admin-post.php');

        ob_start();
        echo self::get_message_html();
        echo '<div class="wdb-form-shell"><form class="wdb-form" method="post" action="' . esc_url($action_url) . '">';
        wp_nonce_field('wdb_frontend_address');
        echo '<input type="hidden" name="action" value="wdb_save_address">';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url(self::get_current_url()) . '">';
        echo '<p><label for="alamat_lengkap">Alamat Lengkap</label><br><textarea name="alamat_lengkap" id="alamat_lengkap" rows="4" required></textarea></p>';
        echo self::render_region_select_fields();
        echo '<p><button class="wdb-button" type="submit">Simpan Alamat</button></p>';
        echo '</form></div>';

        return (string) ob_get_clean();
    }

    public static function render_contact_form(): string
    {
        if (! is_user_logged_in()) {
            return Auth::render_login_form();
        }

        wp_enqueue_style('wdb-main', WDB_PLUGIN_URL . 'assets/css/main.css', [], \WDB\Core\Plugin::asset_version('assets/css/main.css'));

        $action_url = admin_url('admin-post.php');

        ob_start();
        echo self::get_message_html();
        echo '<div class="wdb-form-shell"><form class="wdb-form" method="post" action="' . esc_url($action_url) . '">';
        wp_nonce_field('wdb_frontend_contact');
        echo '<input type="hidden" name="action" value="wdb_save_contact">';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url(self::get_current_url()) . '">';
        echo self::render_contact_fields(false, 'alumni');
        echo '<p><button class="wdb-button" type="submit">Simpan Kontak</button></p>';
        echo '</form></div>';

        return (string) ob_get_clean();
    }

    public static function render_social_form(): string
    {
        if (! is_user_logged_in()) {
            return Auth::render_login_form();
        }

        wp_enqueue_style('wdb-main', WDB_PLUGIN_URL . 'assets/css/main.css', [], \WDB\Core\Plugin::asset_version('assets/css/main.css'));

        $action_url = admin_url('admin-post.php');

        ob_start();
        echo self::get_message_html();
        echo '<div class="wdb-form-shell"><form class="wdb-form" method="post" action="' . esc_url($action_url) . '">';
        wp_nonce_field('wdb_frontend_social');
        echo '<input type="hidden" name="action" value="wdb_save_social">';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url(self::get_current_url()) . '">';
        echo self::render_social_fields();
        echo '<p><button class="wdb-button" type="submit">Simpan Sosial Media</button></p>';
        echo '</form></div>';

        return (string) ob_get_clean();
    }

    public static function render_dashboard(): string
    {
        if (! is_user_logged_in()) {
            return Auth::render_login_form();
        }

        self::enqueue_form_assets();

        global $wpdb;

        $user_id = get_current_user_id();
        $alumni = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'wdb_alumni WHERE user_id = %d ORDER BY id DESC LIMIT 1',
                $user_id
            ),
            ARRAY_A
        );
        $pesantren_list = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . 'wdb_pesantren WHERE user_id = %d ORDER BY id DESC',
                $user_id
            ),
            ARRAY_A
        );
        $attachments = get_posts(
            [
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'author' => $user_id,
                'post_mime_type' => 'image',
                'orderby' => 'date',
                'order' => 'DESC',
                'posts_per_page' => 24,
            ]
        );
        $logout_url = wp_logout_url(self::get_current_url());
        $alumni_exists = is_array($alumni);
        $pesantren_count = count($pesantren_list);
        $attachment_count = count($attachments);
        $module_cards = [
            [
                'key' => 'profil',
                'label' => 'Profil Alumni',
                'count' => $alumni_exists ? '1 profil' : 'Belum lengkap',
                'description' => 'Kelola identitas alumni, alamat, kontak, pekerjaan, dan sosial media.',
                'status' => $alumni_exists ? 'Aktif' : 'Perlu dilengkapi',
                'ready' => true,
            ],
            [
                'key' => 'pesantren',
                'label' => 'Pesantren',
                'count' => number_format_i18n($pesantren_count) . ' data',
                'description' => 'Tambah dan kelola pesantren yang Anda miliki dari dashboard ini.',
                'status' => $pesantren_count > 0 ? 'Aktif' : 'Siap dipakai',
                'ready' => true,
            ],
            [
                'key' => 'usaha',
                'label' => 'Profil Usaha',
                'count' => 'Segera hadir',
                'description' => 'Struktur modul usaha disiapkan agar bisa masuk ke dashboard yang sama.',
                'status' => 'Coming soon',
                'ready' => false,
            ],
            [
                'key' => 'pendidikan',
                'label' => 'Pendidikan',
                'count' => 'Segera hadir',
                'description' => 'Slot modul pendidikan sudah disiapkan untuk pengembangan berikutnya.',
                'status' => 'Coming soon',
                'ready' => false,
            ],
        ];

        ob_start();
        echo '<div class="wdb-dashboard-shell">';
        echo '<section class="wdb-dashboard-hero">';
        echo '<div class="wdb-dashboard-hero__content">';
        echo '<p class="wdb-dashboard-hero__eyebrow">Dashboard Alumni</p>';
        echo '<h2 class="wdb-dashboard-hero__title">Kelola data pribadi dan modul organisasi dari satu workspace</h2>';
        echo '<p class="wdb-dashboard-hero__text">Struktur dashboard ini disiapkan untuk berkembang ke pesantren, usaha, pendidikan, dan modul lain tanpa mengubah fondasi area kerja Anda.</p>';
        echo '</div>';
        echo '<div class="wdb-dashboard-hero__actions">';
        echo '<a class="wdb-button" href="' . esc_url(home_url('/form-pesantren/')) . '">Tambah Pesantren</a>';
        echo '<a class="wdb-button wdb-button--secondary" href="' . esc_url($logout_url) . '">Logout</a>';
        echo '</div>';
        echo '</section>';
        echo self::get_message_html();
        echo '<section class="wdb-dashboard-overview">';
        echo '<div class="wdb-dashboard-stats">';
        echo self::render_dashboard_stat_tile('Profil Alumni', $alumni_exists ? 'Siap' : 'Kosong', $alumni_exists ? 'Profil alumni sudah tersedia.' : 'Lengkapi profil alumni Anda terlebih dahulu.');
        echo self::render_dashboard_stat_tile('Pesantren Saya', number_format_i18n($pesantren_count), $pesantren_count > 0 ? 'Data pesantren milik Anda yang tersimpan.' : 'Belum ada data pesantren.');
        echo self::render_dashboard_stat_tile('Attachment Gambar', number_format_i18n($attachment_count), $attachment_count > 0 ? 'Gambar milik Anda yang sudah terupload.' : 'Belum ada gambar yang diupload.');
        echo '</div>';
        echo '<div class="wdb-dashboard-modules">';
        echo '<div class="wdb-dashboard-panel-card">';
        echo '<h3 class="wdb-dashboard-panel-card__title">Modul Workspace</h3>';
        echo '<div class="wdb-dashboard-module-grid">';
        foreach ($module_cards as $module_card) {
            echo self::render_dashboard_module_card($module_card);
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
        echo '<div class="wdb-tabs" data-wdb-tabs>';
        echo '<div class="wdb-tab-list wdb-dashboard-tab-list">';
        echo '<button class="wdb-tab-button" type="button" data-wdb-tab-button="ringkasan" aria-pressed="true">Ringkasan</button>';
        echo '<button class="wdb-tab-button" type="button" data-wdb-tab-button="profil" aria-pressed="false">Profil Alumni</button>';
        echo '<button class="wdb-tab-button" type="button" data-wdb-tab-button="pesantren" aria-pressed="false">Pesantren Saya</button>';
        echo '<button class="wdb-tab-button" type="button" data-wdb-tab-button="media" aria-pressed="false">Media Saya</button>';
        echo '</div>';
        echo '<div class="wdb-tab-panel wdb-dashboard-panel" data-wdb-tab-panel="ringkasan" id="wdb-dashboard-panel-ringkasan">';
        echo '<div class="wdb-dashboard-panel-card">';
        echo '<h3 class="wdb-dashboard-panel-card__title">Pusat Kerja Alumni</h3>';
        echo '<p class="wdb-dashboard-panel-card__text">Gunakan modul `Profil Alumni` untuk data pribadi, `Pesantren Saya` untuk lembaga, dan `Media Saya` untuk galeri upload. Modul `Usaha` dan `Pendidikan` disiapkan di struktur dashboard ini untuk pengembangan berikutnya.</p>';
        echo '</div>';
        echo '</div>';
        echo '<div class="wdb-tab-panel wdb-dashboard-panel" data-wdb-tab-panel="profil" id="wdb-dashboard-panel-profil">';
        echo '<div class="wdb-dashboard-panel-card">';
        echo '<h3 class="wdb-dashboard-panel-card__title">Profil Alumni</h3>';

        if (is_array($alumni)) {
            echo self::render_dashboard_alumni_form(
                $alumni,
                self::get_related_record('addresses', isset($alumni['address_id']) ? (int) $alumni['address_id'] : 0),
                self::get_related_record('contacts', isset($alumni['contact_id']) ? (int) $alumni['contact_id'] : 0),
                self::get_related_record('jobs', isset($alumni['job_id']) ? (int) $alumni['job_id'] : 0),
                self::get_related_record('socials', isset($alumni['social_id']) ? (int) $alumni['social_id'] : 0)
            );
        } else {
            echo '<div class="wdb-dashboard-empty"><p>Data alumni belum tersedia.</p><a class="wdb-button" href="' . esc_url(home_url('/form-alumni/')) . '">Isi Form Alumni</a></div>';
        }

        echo '</div>';
        echo '</div>';
        echo '<div class="wdb-tab-panel wdb-dashboard-panel" data-wdb-tab-panel="pesantren" id="wdb-dashboard-panel-pesantren">';
        echo '<div class="wdb-dashboard-panel-card">';
        echo '<div class="wdb-dashboard-panel-card__head">';
        echo '<h3 class="wdb-dashboard-panel-card__title">Pesantren Saya</h3>';
        echo '<a class="wdb-button" href="' . esc_url(home_url('/form-pesantren/')) . '">Tambah Pesantren Baru</a>';
        echo '</div>';

        if (empty($pesantren_list)) {
            echo '<div class="wdb-dashboard-empty"><p>Belum ada data pesantren.</p><a class="wdb-button" href="' . esc_url(home_url('/form-pesantren/')) . '">Tambah Pesantren Pertama</a></div>';
        } else {
            foreach ($pesantren_list as $pesantren) {
                echo self::render_dashboard_pesantren_form(
                    $pesantren,
                    self::get_related_record('addresses', isset($pesantren['address_id']) ? (int) $pesantren['address_id'] : 0),
                    self::get_related_record('contacts', isset($pesantren['contact_id']) ? (int) $pesantren['contact_id'] : 0),
                    self::get_related_record('socials', isset($pesantren['social_id']) ? (int) $pesantren['social_id'] : 0)
                );
            }
        }

        echo '</div>';
        echo '</div>';
        echo '<div class="wdb-tab-panel wdb-dashboard-panel" data-wdb-tab-panel="media" id="wdb-dashboard-panel-media">';
        echo '<div class="wdb-dashboard-panel-card">';
        echo '<h3 class="wdb-dashboard-panel-card__title">Media Saya</h3>';
        echo self::render_attachment_gallery($attachments);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        return (string) ob_get_clean();
    }

    private static function render_public_page(string $page, int $record_id = 0): void
    {
        $title = 'Webane Database';
        $content = '';
        $subtitle = self::get_public_header_subtitle();

        if ('form-pesantren' === $page) {
            $title = 'Form Data Pesantren';
            $content = self::render_pesantren_form();
        }

        if ('form-alumni' === $page) {
            $title = 'Form Data Alumni';
            $content = self::render_alumni_form();
        }

        if ('arsip-alumni' === $page) {
            $title = 'Data Alumni';
            $content = self::render_public_alumni_archive();
        }

        if ('arsip-pesantren' === $page) {
            $title = 'Data Pesantren';
            $content = self::render_public_pesantren_archive();
        }

        if ('data-statistik' === $page) {
            $title = 'Data Statistik';
            $content = self::render_public_statistics();
        }

        if ('login-alumni' === $page) {
            $title = 'Login Alumni';
            $content = Auth::render_login_form();
        }

        if ('dashboard-alumni' === $page) {
            $title = 'Dashboard Alumni';
            $content = self::render_dashboard();
        }

        if ('detail-alumni' === $page) {
            $title = 'Detail Alumni';
            $content = self::render_public_alumni_detail($record_id);
        }

        if ('detail-pesantren' === $page) {
            $title = 'Detail Pesantren';
            $content = self::render_public_pesantren_detail($record_id);
        }

        if ('pencarian-data' === $page) {
            $title = 'Pencarian Data';
            $content = self::render_public_search();
        }

        if ('terima-kasih' === $page) {
            $title = 'Terima Kasih';
            $content = self::render_public_thank_you();
        }

        if (in_array($page, ['form-pesantren', 'form-alumni'], true)) {
            self::render_public_standalone_shell($title, $content, true, $subtitle);

            return;
        }

        if ('login-alumni' === $page) {
            self::render_public_standalone_shell($title, $content, false);

            return;
        }

        self::render_public_theme_shell($title, $content, ! in_array($page, ['detail-alumni', 'detail-pesantren'], true), $subtitle);
    }

    private static function render_public_theme_shell(string $title, string $content, bool $show_header = true, string $subtitle = ''): void
    {
        self::enqueue_form_assets();
        status_header(200);
        nocache_headers();

        get_header();
        echo '<main class="wdb-theme-main">';
        echo '<div class="wdb-theme-container ane-container container mx-auto px-4">';
        if ($show_header) {
            echo '<div class="wdb-page-header">';
            echo '<div class="wdb-page-header__content"><h1 class="wdb-page-header__title">' . esc_html($title) . '</h1>';
            if ('' !== trim($subtitle)) {
                echo '<p class="wdb-page-header__subtitle">' . esc_html($subtitle) . '</p>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo $content;
        echo '</div>';
        echo '</main>';
        get_footer();
    }

    private static function render_public_standalone_shell(string $title, string $content, bool $show_header = true, string $subtitle = ''): void
    {
        self::enqueue_form_assets();
        status_header(200);
        nocache_headers();
        ob_start();
        language_attributes();
        $language_attributes = trim((string) ob_get_clean());

        echo '<!DOCTYPE html>';
        echo '<html ' . $language_attributes . '>';
        echo '<head>';
        echo '<meta charset="' . esc_attr(get_bloginfo('charset')) . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . esc_html($title . ' - ' . get_bloginfo('name')) . '</title>';
        wp_head();
        echo '</head>';
        echo '<body class="wdb-public-route">';
        echo '<main class="wdb-standalone-main">';
        if ($show_header) {
            echo '<div class="wdb-page-header wdb-page-header--with-action">';
            echo '<div class="wdb-page-header__content"><h1 class="wdb-page-header__title">' . esc_html($title) . '</h1>';
            if ('' !== trim($subtitle)) {
                echo '<p class="wdb-page-header__subtitle">' . esc_html($subtitle) . '</p>';
            }
            echo '</div>';
            echo '<a class="wdb-button" href="' . esc_url(home_url('/')) . '">Beranda</a>';
            echo '</div>';
        }
        echo $content;
        echo '</main>';
        wp_footer();
        echo '</body>';
        echo '</html>';
    }

    private static function get_public_header_subtitle(): string
    {
        $institution = get_option('wdb_institution_info', []);

        if (! is_array($institution)) {
            return '';
        }

        return isset($institution['name']) ? trim((string) $institution['name']) : '';
    }

    private static function render_public_alumni_archive(): string
    {
        global $wpdb;

        $term = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $like = '%' . $wpdb->esc_like($term) . '%';
        $query = "SELECT alumni.id, alumni.nama_lengkap, alumni.alumni_tahun, addresses.kecamatan_name, addresses.kabupaten_name
            FROM {$wpdb->prefix}wdb_alumni AS alumni
            LEFT JOIN {$wpdb->prefix}wdb_addresses AS addresses ON addresses.id = alumni.address_id
            WHERE alumni.status = 'published'";

        if ('' !== $term) {
            $query .= $wpdb->prepare(' AND alumni.nama_lengkap LIKE %s', $like);
        }

        $query .= ' ORDER BY alumni.alumni_tahun DESC, alumni.id DESC LIMIT 100';
        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $html = '<div class="wdb-list-card">';
        $html .= self::render_archive_search_form(home_url('/arsip-alumni/'), $term, 'Cari alumni');
        $html .= '<div class="wdb-list-mobile">';

        if (empty($rows)) {
            $html .= '<div class="wdb-list-mobile__item"><p>Tidak ada data alumni.</p></div></div>';
            $html .= '<div class="wdb-list-table-wrap"><table class="wdb-list-table"><thead><tr><th>Nama</th><th>Alamat</th><th>Alumni</th><th>Aksi</th></tr></thead><tbody></tbody></table></div></div>';

            return $html;
        }

        foreach ($rows as $row) {
            $address = self::format_public_alumni_address_summary($row);
            $html .= '<div class="wdb-list-mobile__item">';
            $html .= '<div class="wdb-list-mobile__head"><div class="wdb-list-mobile__title-group"><p class="wdb-list-mobile__title">' . esc_html((string) $row['nama_lengkap']) . '</p><p class="wdb-list-mobile__subtitle">' . esc_html($address) . '</p></div></div>';
            $html .= '<div class="wdb-list-mobile__meta"><span>Alumni ' . esc_html((string) $row['alumni_tahun']) . '</span></div>';
            $html .= '<div class="wdb-list-mobile__actions"><a href="' . esc_url(home_url('/detail-alumni/' . (int) $row['id'] . '/')) . '">Lihat detail</a></div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '<div class="wdb-list-table-wrap">';
        $html .= '<table class="wdb-list-table">';
        $html .= '<thead><tr><th>Nama</th><th>Alamat</th><th>Alumni</th><th>Aksi</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($rows as $row) {
            $address = self::format_public_alumni_address_summary($row);
            $html .= '<tr>';
            $html .= '<td class="wdb-list-table__highlight">' . esc_html((string) $row['nama_lengkap']) . '</td>';
            $html .= '<td>' . esc_html($address) . '</td>';
            $html .= '<td>' . esc_html((string) $row['alumni_tahun']) . '</td>';
            $html .= '<td><a href="' . esc_url(home_url('/detail-alumni/' . (int) $row['id'] . '/')) . '">Lihat detail</a></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div></div>';

        return $html;
    }

    private static function render_public_pesantren_archive(): string
    {
        global $wpdb;

        $term = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $like = '%' . $wpdb->esc_like($term) . '%';
        $query = "SELECT pesantren.id, pesantren.nama_pesantren, pesantren.berdiri_sejak, pesantren.jenjang_pendidikan, pesantren.jenis_pondok, pesantren.jumlah_santri_total, pesantren.nama_pimpinan,
            addresses.desa_name, addresses.kecamatan_name, addresses.kabupaten_name, addresses.provinsi_name
            FROM {$wpdb->prefix}wdb_pesantren AS pesantren
            LEFT JOIN {$wpdb->prefix}wdb_addresses AS addresses ON addresses.id = pesantren.address_id
            WHERE pesantren.status = 'published'";

        if ('' !== $term) {
            $query .= $wpdb->prepare(' AND (pesantren.nama_pesantren LIKE %s OR pesantren.nama_pimpinan LIKE %s)', $like, $like);
        }

        $query .= ' ORDER BY pesantren.id DESC LIMIT 100';
        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $html = '<div class="wdb-list-card">';
        $html .= self::render_archive_search_form(home_url('/arsip-pesantren/'), $term, 'Cari pesantren');
        $html .= '<div class="wdb-list-mobile">';

        if (empty($rows)) {
            $html .= '<div class="wdb-list-mobile__item"><p>Tidak ada data pesantren.</p></div></div>';
            $html .= '<div class="wdb-list-table-wrap"><table class="wdb-list-table"><thead><tr><th>Nama Pesantren</th><th>Berdiri</th><th>Pimpinan</th><th>Jenjang</th><th>Jumlah Santri</th><th>Alamat</th><th>Aksi</th></tr></thead><tbody></tbody></table></div></div>';

            return $html;
        }

        foreach ($rows as $row) {
            $address = self::format_public_address_summary($row);
            $year = self::format_public_year((string) ($row['berdiri_sejak'] ?? ''));
            $html .= '<div class="wdb-list-mobile__item">';
            $html .= '<div class="wdb-list-mobile__head"><div class="wdb-list-mobile__title-group"><p class="wdb-list-mobile__title">' . esc_html((string) $row['nama_pesantren']) . '</p><p class="wdb-list-mobile__subtitle">' . esc_html((string) ($row['nama_pimpinan'] ?? '-')) . '</p></div></div>';
            $html .= '<div class="wdb-list-mobile__meta"><span>' . esc_html($year) . '</span><span class="wdb-list-mobile__dot">•</span><span>' . esc_html((string) ($row['nama_pimpinan'] ?? '-')) . '</span></div>';
            $html .= '<div class="wdb-list-mobile__meta"><span>' . esc_html(strtoupper((string) $row['jenjang_pendidikan'])) . '</span><span class="wdb-list-mobile__dot">•</span><span>' . esc_html((string) $row['jumlah_santri_total']) . ' santri</span></div>';
            $html .= '<div class="wdb-list-mobile__meta"><span>' . esc_html($address) . '</span></div>';
            $html .= '<div class="wdb-list-mobile__actions"><a href="' . esc_url(home_url('/detail-pesantren/' . (int) $row['id'] . '/')) . '">Lihat detail</a></div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '<div class="wdb-list-table-wrap">';
        $html .= '<table class="wdb-list-table">';
        $html .= '<thead><tr><th>Nama Pesantren</th><th>Berdiri</th><th>Pimpinan</th><th>Jenjang</th><th>Jumlah Santri</th><th>Alamat</th><th>Aksi</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($rows as $row) {
            $address = self::format_public_address_summary($row);
            $year = self::format_public_year((string) ($row['berdiri_sejak'] ?? ''));
            $html .= '<tr>';
            $html .= '<td class="wdb-list-table__highlight">' . esc_html((string) $row['nama_pesantren']) . '</td>';
            $html .= '<td>' . esc_html($year) . '</td>';
            $html .= '<td>' . esc_html((string) ($row['nama_pimpinan'] ?? '-')) . '</td>';
            $html .= '<td>' . esc_html(strtoupper((string) $row['jenjang_pendidikan'])) . '</td>';
            $html .= '<td>' . esc_html((string) $row['jumlah_santri_total']) . '</td>';
            $html .= '<td>' . esc_html($address) . '</td>';
            $html .= '<td><a class="wdb-list-table__icon-action" href="' . esc_url(home_url('/detail-pesantren/' . (int) $row['id'] . '/')) . '" aria-label="Lihat detail pesantren"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5.23 0 9.27 4.11 10.68 6.02a1.6 1.6 0 010 1.96C21.27 14.89 17.23 19 12 19s-9.27-4.11-10.68-6.02a1.6 1.6 0 010-1.96C2.73 9.11 6.77 5 12 5zm0 2C8.18 7 4.96 9.8 3.45 12 4.96 14.2 8.18 17 12 17s7.04-2.8 8.55-5C19.04 9.8 15.82 7 12 7zm0 2.5A2.5 2.5 0 1112 14.5 2.5 2.5 0 0112 9.5z"/></svg></a></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div></div>';

        return $html;
    }

    private static function format_public_year(string $date): string
    {
        if ('' === $date) {
            return '-';
        }

        $timestamp = strtotime($date);

        if (false === $timestamp) {
            return '-';
        }

        return gmdate('Y', $timestamp);
    }

    private static function render_public_statistics(): string
    {
        global $wpdb;

        $published_pesantren = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wdb_pesantren WHERE status = 'published'");
        $published_alumni = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wdb_alumni WHERE status = 'published'");
        $pesantren_totals = $wpdb->get_row(
            "SELECT
                COALESCE(SUM(santri_putra), 0) AS santri_putra,
                COALESCE(SUM(santri_putri), 0) AS santri_putri,
                COALESCE(SUM(jumlah_santri_total), 0) AS jumlah_santri_total,
                COALESCE(SUM(asatidz), 0) AS asatidz,
                COALESCE(SUM(asatidzah), 0) AS asatidzah,
                COALESCE(SUM(jumlah_guru_total), 0) AS jumlah_guru_total
            FROM {$wpdb->prefix}wdb_pesantren
            WHERE status = 'published'",
            ARRAY_A
        ) ?: [];
        $total_santri = (int) ($pesantren_totals['jumlah_santri_total'] ?? 0);
        $total_guru = (int) ($pesantren_totals['jumlah_guru_total'] ?? 0);
        $santri_putra = (int) ($pesantren_totals['santri_putra'] ?? 0);
        $santri_putri = (int) ($pesantren_totals['santri_putri'] ?? 0);
        $asatidz = (int) ($pesantren_totals['asatidz'] ?? 0);
        $asatidzah = (int) ($pesantren_totals['asatidzah'] ?? 0);
        $jenjang_rows = $wpdb->get_results(
            "SELECT jenjang_pendidikan AS label, COUNT(*) AS total
            FROM {$wpdb->prefix}wdb_pesantren
            WHERE status = 'published' AND jenjang_pendidikan <> ''
            GROUP BY jenjang_pendidikan
            ORDER BY total DESC, label ASC",
            ARRAY_A
        ) ?: [];
        $jenis_pondok_rows = $wpdb->get_results(
            "SELECT jenis_pondok AS label, COUNT(*) AS total
            FROM {$wpdb->prefix}wdb_pesantren
            WHERE status = 'published' AND jenis_pondok <> ''
            GROUP BY jenis_pondok
            ORDER BY total DESC, label ASC",
            ARRAY_A
        ) ?: [];
        $alumni_year_rows = $wpdb->get_results(
            "SELECT CAST(alumni_tahun AS UNSIGNED) AS tahun, COUNT(*) AS total
            FROM {$wpdb->prefix}wdb_alumni
            WHERE status = 'published' AND alumni_tahun <> ''
            GROUP BY CAST(alumni_tahun AS UNSIGNED)
            ORDER BY CAST(alumni_tahun AS UNSIGNED) DESC
            LIMIT 10",
            ARRAY_A
        ) ?: [];
        $jenis_kelamin_rows = $wpdb->get_results(
            "SELECT jenis_kelamin AS label, COUNT(*) AS total
            FROM {$wpdb->prefix}wdb_alumni
            WHERE status = 'published' AND jenis_kelamin <> ''
            GROUP BY jenis_kelamin
            ORDER BY total DESC, label ASC",
            ARRAY_A
        ) ?: [];
        $pekerjaan_rows = $wpdb->get_results(
            "SELECT jobs.pekerjaan AS label, COUNT(*) AS total
            FROM {$wpdb->prefix}wdb_alumni AS alumni
            INNER JOIN {$wpdb->prefix}wdb_jobs AS jobs ON jobs.id = alumni.job_id
            WHERE alumni.status = 'published' AND jobs.pekerjaan <> ''
            GROUP BY jobs.pekerjaan
            ORDER BY total DESC, label ASC
            LIMIT 8",
            ARRAY_A
        ) ?: [];
        $region_context = self::get_public_region_distribution_context();
        $pesantren_region_rows = self::get_public_region_distribution_rows('pesantren', $region_context);
        $alumni_region_rows = self::get_public_region_distribution_rows('alumni', $region_context);
        $santri_composition = [
            ['label' => 'Santri Putra', 'value' => $santri_putra],
            ['label' => 'Santri Putri', 'value' => $santri_putri],
        ];
        $guru_composition = [
            ['label' => 'Asatidz', 'value' => $asatidz],
            ['label' => 'Asatidzah', 'value' => $asatidzah],
        ];

        $html = '<div class="wdb-stats-page">';
        $html .= '<section class="wdb-stats-hero">';
        $html .= '<div class="wdb-stats-hero__content">';
        $html .= '<p class="wdb-stats-hero__eyebrow">Informasi Publik Organisasi</p>';
        $html .= '<h2 class="wdb-stats-hero__title">Statistik pesantren dan alumni yang sudah dipublikasikan</h2>';
        $html .= '<p class="wdb-stats-hero__text">Halaman ini merangkum kekuatan jaringan organisasi dari sisi lembaga, alumni, santri, guru, persebaran wilayah, dan pekerjaan.</p>';
        $html .= '</div>';
        $html .= '<div class="wdb-stats-hero__badge">' . esc_html(number_format_i18n($published_pesantren + $published_alumni)) . '<span>data utama publik</span></div>';
        $html .= '</section>';
        $html .= '<section class="wdb-stats-grid">';
        $html .= self::render_public_stat_card('Pesantren Publik', number_format_i18n($published_pesantren), 'Data pesantren yang sudah published.');
        $html .= self::render_public_stat_card('Alumni Publik', number_format_i18n($published_alumni), 'Data alumni yang sudah published.');
        $html .= self::render_public_stat_card('Total Santri', number_format_i18n($total_santri), 'Akumulasi santri dari seluruh pesantren publik.');
        $html .= self::render_public_stat_card('Total Guru', number_format_i18n($total_guru), 'Akumulasi guru dari seluruh pesantren publik.');
        $html .= '</section>';
        $html .= '<section class="wdb-stats-section">';
        $html .= '<div class="wdb-stats-section__head"><h2>Komposisi Pesantren</h2><p>Melihat struktur lembaga, sebaran santri, dan tenaga pengajar.</p></div>';
        $html .= '<div class="wdb-stats-chart-grid">';
        $html .= self::render_public_stats_chart('Jenjang Pendidikan', self::prepare_public_stats_rows($jenjang_rows, 'format_pesantren_level_label'), 'Belum ada data jenjang pendidikan.');
        $html .= self::render_public_stats_chart('Jenis Pondok', self::prepare_public_stats_rows($jenis_pondok_rows, 'format_pesantren_type_label'), 'Belum ada data jenis pondok.');
        $html .= self::render_public_stats_chart('Komposisi Santri', $santri_composition, 'Belum ada data santri.');
        $html .= self::render_public_stats_chart('Komposisi Guru', $guru_composition, 'Belum ada data guru.');
        $html .= '</div>';
        $html .= '</section>';
        $html .= '<section class="wdb-stats-section">';
        $html .= '<div class="wdb-stats-section__head"><h2>Komposisi Alumni</h2><p>Melihat angkatan, gender, dan profil pekerjaan alumni yang tersedia.</p></div>';
        $html .= '<div class="wdb-stats-chart-grid">';
        $html .= self::render_public_stats_chart('Alumni per Tahun', self::prepare_public_year_rows($alumni_year_rows), 'Belum ada data tahun alumni.');
        $html .= self::render_public_stats_chart('Jenis Kelamin Alumni', self::prepare_public_stats_rows($jenis_kelamin_rows, 'format_gender_label'), 'Belum ada data jenis kelamin alumni.');
        $html .= self::render_public_stats_chart('Pekerjaan Alumni', self::prepare_public_stats_rows($pekerjaan_rows), 'Belum ada data pekerjaan alumni.');
        $html .= '</div>';
        $html .= '</section>';
        $html .= '<section class="wdb-stats-section">';
        $html .= '<div class="wdb-stats-section__head"><h2>Persebaran Wilayah</h2><p>' . esc_html((string) ($region_context['description'] ?? 'Menunjukkan wilayah yang paling aktif terisi pada data lembaga dan alumni.')) . '</p></div>';
        $html .= '<div class="wdb-stats-chart-grid">';
        $html .= self::render_public_stats_chart('Top ' . (string) ($region_context['title_label'] ?? 'Provinsi') . ' Pesantren', self::prepare_public_stats_rows($pesantren_region_rows), 'Belum ada data wilayah pesantren.');
        $html .= self::render_public_stats_chart('Top ' . (string) ($region_context['title_label'] ?? 'Provinsi') . ' Alumni', self::prepare_public_stats_rows($alumni_region_rows), 'Belum ada data wilayah alumni.');
        $html .= '</div>';
        $html .= '</section>';
        $html .= '</div>';

        return $html;
    }

    private static function render_public_alumni_detail(int $record_id): string
    {
        global $wpdb;

        if ($record_id <= 0) {
            return '<div class="wdb-form-shell"><p>Pilih data alumni dari arsip alumni atau pencarian data.</p></div>';
        }

        $record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wdb_alumni WHERE id = %d AND status = 'published'",
                $record_id
            ),
            ARRAY_A
        );

        if (! is_array($record)) {
            return '<div class="wdb-form-shell"><p>Data alumni tidak ditemukan.</p></div>';
        }

        $address = self::get_related_record('addresses', (int) ($record['address_id'] ?? 0));
        $contact = self::get_related_record('contacts', (int) ($record['contact_id'] ?? 0));
        $social = self::get_related_record('socials', (int) ($record['social_id'] ?? 0));
        $html = '<div class="wdb-detail-page">';
        $html .= '<section class="wdb-detail-card">';
        $html .= '<div class="wdb-detail-profile">';
        $html .= '<div class="wdb-detail-profile__content">';
        $html .= '<h1 class="wdb-detail-title">' . esc_html((string) ($record['nama_lengkap'] ?? '')) . '</h1>';
        $html .= self::render_public_detail_grid(
            [
                'Nama Lengkap' => (string) ($record['nama_lengkap'] ?? ''),
                'Alumni' => (string) ($record['alumni_tahun'] ?? ''),
                'Alamat' => self::format_public_alumni_address_summary($address ?: []),
            ]
        );
        $html .= self::render_public_alumni_whatsapp_action($record, $contact);
        $html .= self::render_public_social_icon_links($social);
        $html .= '</div>';
        $html .= '<div class="wdb-detail-profile__media">';
        $html .= self::render_public_record_image((int) ($record['pasphoto_id'] ?? 0), \WDB\Core\Plugin::get_alumni_pasphoto_size(), '3 / 4', 220);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</section>';
        $html .= self::render_public_alumni_pesantren_relation($record);
        $html .= self::render_public_alumni_news((string) ($record['nama_lengkap'] ?? ''));
        $html .= self::render_public_related_alumni($record, $address);
        $html .= '</div>';

        return $html;
    }

    private static function render_public_pesantren_detail(int $record_id): string
    {
        global $wpdb;

        if ($record_id <= 0) {
            return '<div class="wdb-form-shell"><p>Pilih data pesantren dari arsip pesantren atau pencarian data.</p></div>';
        }

        $record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wdb_pesantren WHERE id = %d AND status = 'published'",
                $record_id
            ),
            ARRAY_A
        );

        if (! is_array($record)) {
            return '<div class="wdb-form-shell"><p>Data pesantren tidak ditemukan.</p></div>';
        }

        $address = self::get_related_record('addresses', (int) ($record['address_id'] ?? 0));
        $contact = self::get_related_record('contacts', (int) ($record['contact_id'] ?? 0));
        $social = self::get_related_record('socials', (int) ($record['social_id'] ?? 0));
        $html = '<div class="wdb-detail-page">';
        $html .= self::render_public_featured_image((int) ($record['photo_andalan_id'] ?? 0), \WDB\Core\Plugin::get_pesantren_photo_size(), '16 / 9');
        $html .= '<section class="wdb-detail-card">';
        $html .= '<h1 class="wdb-detail-title">' . esc_html((string) ($record['nama_pesantren'] ?? '')) . '</h1>';
        $html .= self::render_public_detail_grid(
            [
                'Berdiri Sejak' => (string) ($record['berdiri_sejak'] ?? ''),
                'Luas Area' => (string) ($record['luas_area'] ?? ''),
                'Nama Pimpinan' => (string) ($record['nama_pimpinan'] ?? ''),
                'Jenjang Pendidikan' => strtoupper((string) ($record['jenjang_pendidikan'] ?? '')),
                'Jenis Pondok' => ucfirst((string) ($record['jenis_pondok'] ?? '')),
                'Jumlah Santri' => (string) ($record['jumlah_santri_total'] ?? '0'),
                'Jumlah Guru' => (string) ($record['jumlah_guru_total'] ?? '0'),
                'Alamat' => self::format_public_address_summary($address ?: []),
            ]
        );
        $html .= self::render_public_pesantren_contact_bar($record, $contact);
        $html .= self::render_public_social_icon_links($social);
        $html .= '</section>';
        $html .= self::render_public_pesantren_news((string) ($record['nama_pesantren'] ?? ''));
        $html .= self::render_public_related_pesantren($record, $address);
        $html .= '</div>';

        return $html;
    }

    private static function render_public_search(): string
    {
        global $wpdb;

        $term = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $html = '<div class="wdb-form-shell">';
        $html .= '<form method="get" action="' . esc_url(home_url('/pencarian-data/')) . '" class="wdb-form">';
        $html .= '<p><label>Kata Kunci</label><br><input type="text" name="q" value="' . esc_attr($term) . '" placeholder="Cari alumni atau pesantren"></p>';
        $html .= '<p><button class="wdb-button" type="submit">Cari</button></p>';
        $html .= '</form>';

        if ('' === $term) {
            $html .= '<p style="margin-top:18px;">Masukkan kata kunci untuk mencari data alumni atau pesantren.</p></div>';

            return $html;
        }

        $like = '%' . $wpdb->esc_like($term) . '%';
        $alumni = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, nama_lengkap, tempat_lahir, alumni_tahun, jenis_kelamin, pasphoto_id
                FROM {$wpdb->prefix}wdb_alumni
                WHERE status = 'published' AND (nama_lengkap LIKE %s OR tempat_lahir LIKE %s)
                ORDER BY alumni_tahun DESC, id DESC
                LIMIT 20",
                $like,
                $like
            ),
            ARRAY_A
        ) ?: [];
        $pesantren = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, nama_pesantren, jenjang_pendidikan, jenis_pondok, jumlah_santri_total, photo_andalan_id
                FROM {$wpdb->prefix}wdb_pesantren
                WHERE status = 'published' AND (nama_pesantren LIKE %s OR nama_pimpinan LIKE %s)
                ORDER BY id DESC
                LIMIT 20",
                $like,
                $like
            ),
            ARRAY_A
        ) ?: [];

        $html .= '<div style="margin-top:24px;">';
        $html .= '<h2 style="margin:0 0 12px;font-size:22px;">Hasil Alumni</h2>';
        $html .= empty($alumni) ? '<p>Tidak ada hasil alumni.</p>' : '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">' . implode('', array_map([self::class, 'render_public_alumni_card'], $alumni)) . '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-top:24px;">';
        $html .= '<h2 style="margin:0 0 12px;font-size:22px;">Hasil Pesantren</h2>';
        $html .= empty($pesantren) ? '<p>Tidak ada hasil pesantren.</p>' : '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">' . implode('', array_map([self::class, 'render_public_pesantren_card'], $pesantren)) . '</div>';
        $html .= '</div></div>';

        return $html;
    }

    private static function render_archive_search_form(string $action_url, string $term, string $placeholder): string
    {
        $html = '<form method="get" action="' . esc_url($action_url) . '" class="wdb-list-search">';
        $html .= '<div class="wdb-list-search__field"><label for="wdb-archive-search">Pencarian</label><input id="wdb-archive-search" type="text" name="q" value="' . esc_attr($term) . '" placeholder="' . esc_attr($placeholder) . '"></div>';
        $html .= '<div class="wdb-list-search__actions"><button class="wdb-button" type="submit">Cari</button></div>';
        $html .= '</form>';

        return $html;
    }

    private static function render_public_thank_you(): string
    {
        $html = '<div class="wdb-form-shell">';
        $html .= '<p>Terimakasih, data telah tersimpan, informasi lebih lanjut dapat menghubungi <a class="wdb-button" href="#">WhatsApp</a></p>';
        $html .= '</div>';

        return $html;
    }

    public static function handle_pesantren_submit(): void
    {
        check_admin_referer('wdb_frontend_pesantren');

        global $wpdb;

        $author_id = is_user_logged_in() ? get_current_user_id() : 0;
        $address_id = self::create_address_record();
        $contact_id = self::create_contact_record();
        $social_id = self::create_social_record();
        $photo_andalan_id = self::upload_image_field('photo_andalan_file', $author_id);

        $nama_pesantren = isset($_POST['nama_pesantren']) ? sanitize_text_field(wp_unslash($_POST['nama_pesantren'])) : '';

        $wpdb->insert(
            $wpdb->prefix . 'wdb_pesantren',
            [
                'user_id' => is_user_logged_in() ? get_current_user_id() : null,
                'nama_pesantren' => $nama_pesantren,
                'berdiri_sejak' => self::nullable_text('berdiri_sejak'),
                'luas_area' => self::nullable_text('luas_area'),
                'nama_pimpinan' => self::nullable_text('nama_pimpinan'),
                'nomor_hp_pimpinan' => self::nullable_text('nomor_hp_pimpinan'),
                'jenjang_pendidikan' => isset($_POST['jenjang_pendidikan']) ? sanitize_text_field(wp_unslash($_POST['jenjang_pendidikan'])) : '',
                'jenis_pondok' => isset($_POST['jenis_pondok']) ? sanitize_text_field(wp_unslash($_POST['jenis_pondok'])) : '',
                'photo_andalan_id' => $photo_andalan_id,
                'santri_putra' => self::positive_int('santri_putra'),
                'santri_putri' => self::positive_int('santri_putri'),
                'jumlah_santri_total' => self::positive_int('santri_putra') + self::positive_int('santri_putri'),
                'asatidz' => self::positive_int('asatidz'),
                'asatidzah' => self::positive_int('asatidzah'),
                'jumlah_guru_total' => self::positive_int('asatidz') + self::positive_int('asatidzah'),
                'address_id' => $address_id,
                'contact_id' => $contact_id,
                'social_id' => $social_id,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s']
        );

        self::ensure_name_tag_exists($nama_pesantren);
        self::redirect_to_thank_you();
    }

    public static function handle_alumni_submit(): void
    {
        check_admin_referer('wdb_frontend_alumni');

        global $wpdb;

        $user_id = is_user_logged_in() ? get_current_user_id() : null;

        if (! is_user_logged_in() && self::should_create_alumni_account()) {
            $user_id = self::create_alumni_user();
        }

        if (! is_user_logged_in() && self::should_create_alumni_account() && 0 === $user_id) {
            self::redirect_with_message('account_error');
        }

        $address_id = self::create_address_record();
        $contact_id = self::create_contact_record();
        $job_id = self::create_job_record();
        $social_id = self::create_social_record();
        $pasphoto_id = self::upload_image_field('pasphoto_file', (int) $user_id);

        $nama_lengkap = isset($_POST['nama_lengkap']) ? sanitize_text_field(wp_unslash($_POST['nama_lengkap'])) : '';

        $wpdb->insert(
            $wpdb->prefix . 'wdb_alumni',
            [
                'user_id' => $user_id,
                'nama_lengkap' => $nama_lengkap,
                'tempat_lahir' => isset($_POST['tempat_lahir']) ? sanitize_text_field(wp_unslash($_POST['tempat_lahir'])) : '',
                'tanggal_lahir' => self::nullable_text('tanggal_lahir'),
                'alumni_tahun' => self::positive_int('alumni_tahun'),
                'jenis_kelamin' => isset($_POST['jenis_kelamin']) ? sanitize_text_field(wp_unslash($_POST['jenis_kelamin'])) : '',
                'job_id' => $job_id,
                'address_id' => $address_id,
                'contact_id' => $contact_id,
                'social_id' => $social_id,
                'pasphoto_id' => $pasphoto_id,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s']
        );

        self::ensure_name_tag_exists($nama_lengkap);
        self::redirect_to_thank_you();
    }

    public static function handle_dashboard_alumni_update(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(self::get_current_url()));
            exit;
        }

        check_admin_referer('wdb_dashboard_alumni_update');

        global $wpdb;

        $record_id = isset($_POST['alumni_id']) ? absint(wp_unslash($_POST['alumni_id'])) : 0;
        $user_id = get_current_user_id();
        $record = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id FROM ' . $wpdb->prefix . 'wdb_alumni WHERE id = %d AND user_id = %d',
                $record_id,
                $user_id
            ),
            ARRAY_A
        );

        if (! is_array($record)) {
            self::redirect_with_message('update_error');
        }

        $address_id = self::save_dashboard_address(isset($_POST['address_id']) ? absint(wp_unslash($_POST['address_id'])) : 0);
        $contact_id = self::save_dashboard_contact(isset($_POST['contact_id']) ? absint(wp_unslash($_POST['contact_id'])) : 0);
        $job_id = self::save_dashboard_job(isset($_POST['job_id']) ? absint(wp_unslash($_POST['job_id'])) : 0);
        $social_id = self::save_dashboard_social(isset($_POST['social_id']) ? absint(wp_unslash($_POST['social_id'])) : 0);
        $pasphoto_id = self::resolve_uploaded_image('pasphoto_file', 'existing_pasphoto_id', $user_id);

        $nama_lengkap = isset($_POST['nama_lengkap']) ? sanitize_text_field(wp_unslash($_POST['nama_lengkap'])) : '';

        $wpdb->update(
            $wpdb->prefix . 'wdb_alumni',
            [
                'nama_lengkap' => $nama_lengkap,
                'tempat_lahir' => isset($_POST['tempat_lahir']) ? sanitize_text_field(wp_unslash($_POST['tempat_lahir'])) : '',
                'tanggal_lahir' => self::nullable_text('tanggal_lahir'),
                'alumni_tahun' => self::positive_int('alumni_tahun'),
                'jenis_kelamin' => isset($_POST['jenis_kelamin']) ? sanitize_text_field(wp_unslash($_POST['jenis_kelamin'])) : '',
                'job_id' => $job_id,
                'address_id' => $address_id,
                'contact_id' => $contact_id,
                'social_id' => $social_id,
                'pasphoto_id' => $pasphoto_id,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $record_id],
            ['%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s'],
            ['%d']
        );

        self::ensure_name_tag_exists($nama_lengkap);
        self::redirect_with_message('updated');
    }

    public static function handle_dashboard_pesantren_update(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(self::get_current_url()));
            exit;
        }

        check_admin_referer('wdb_dashboard_pesantren_update');

        global $wpdb;

        $record_id = isset($_POST['pesantren_id']) ? absint(wp_unslash($_POST['pesantren_id'])) : 0;
        $user_id = get_current_user_id();
        $record = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT id FROM ' . $wpdb->prefix . 'wdb_pesantren WHERE id = %d AND user_id = %d',
                $record_id,
                $user_id
            ),
            ARRAY_A
        );

        if (! is_array($record)) {
            self::redirect_with_message('update_error');
        }

        $santri_putra = self::positive_int('santri_putra');
        $santri_putri = self::positive_int('santri_putri');
        $asatidz = self::positive_int('asatidz');
        $asatidzah = self::positive_int('asatidzah');
        $address_id = self::save_dashboard_address(isset($_POST['address_id']) ? absint(wp_unslash($_POST['address_id'])) : 0);
        $contact_id = self::save_dashboard_contact(isset($_POST['contact_id']) ? absint(wp_unslash($_POST['contact_id'])) : 0);
        $social_id = self::save_dashboard_social(isset($_POST['social_id']) ? absint(wp_unslash($_POST['social_id'])) : 0);
        $photo_andalan_id = self::resolve_uploaded_image('photo_andalan_file', 'existing_photo_andalan_id', $user_id);

        $nama_pesantren = isset($_POST['nama_pesantren']) ? sanitize_text_field(wp_unslash($_POST['nama_pesantren'])) : '';

        $wpdb->update(
            $wpdb->prefix . 'wdb_pesantren',
            [
                'nama_pesantren' => $nama_pesantren,
                'berdiri_sejak' => self::nullable_text('berdiri_sejak'),
                'luas_area' => self::nullable_text('luas_area'),
                'nama_pimpinan' => self::nullable_text('nama_pimpinan'),
                'nomor_hp_pimpinan' => self::nullable_text('nomor_hp_pimpinan'),
                'jenjang_pendidikan' => isset($_POST['jenjang_pendidikan']) ? sanitize_text_field(wp_unslash($_POST['jenjang_pendidikan'])) : '',
                'jenis_pondok' => isset($_POST['jenis_pondok']) ? sanitize_text_field(wp_unslash($_POST['jenis_pondok'])) : '',
                'photo_andalan_id' => $photo_andalan_id,
                'santri_putra' => $santri_putra,
                'santri_putri' => $santri_putri,
                'jumlah_santri_total' => $santri_putra + $santri_putri,
                'asatidz' => $asatidz,
                'asatidzah' => $asatidzah,
                'jumlah_guru_total' => $asatidz + $asatidzah,
                'address_id' => $address_id,
                'contact_id' => $contact_id,
                'social_id' => $social_id,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $record_id],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s'],
            ['%d']
        );

        self::ensure_name_tag_exists($nama_pesantren);
        self::redirect_with_message('updated');
    }

    public static function handle_address_submit(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(self::get_current_url()));
            exit;
        }

        check_admin_referer('wdb_frontend_address');

        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wdb_addresses',
            [
                'alamat_lengkap' => isset($_POST['alamat_lengkap']) ? sanitize_textarea_field(wp_unslash($_POST['alamat_lengkap'])) : '',
                'provinsi_code' => isset($_POST['provinsi_code']) ? sanitize_text_field(wp_unslash($_POST['provinsi_code'])) : '',
                'provinsi_name' => isset($_POST['provinsi_name']) ? sanitize_text_field(wp_unslash($_POST['provinsi_name'])) : '',
                'kabupaten_code' => isset($_POST['kabupaten_code']) ? sanitize_text_field(wp_unslash($_POST['kabupaten_code'])) : '',
                'kabupaten_name' => isset($_POST['kabupaten_name']) ? sanitize_text_field(wp_unslash($_POST['kabupaten_name'])) : '',
                'kecamatan_code' => isset($_POST['kecamatan_code']) ? sanitize_text_field(wp_unslash($_POST['kecamatan_code'])) : '',
                'kecamatan_name' => isset($_POST['kecamatan_name']) ? sanitize_text_field(wp_unslash($_POST['kecamatan_name'])) : '',
                'desa_code' => isset($_POST['desa_code']) ? sanitize_text_field(wp_unslash($_POST['desa_code'])) : '',
                'desa_name' => isset($_POST['desa_name']) ? sanitize_text_field(wp_unslash($_POST['desa_name'])) : '',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        self::redirect_with_message('created');
    }

    public static function handle_contact_submit(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(self::get_current_url()));
            exit;
        }

        check_admin_referer('wdb_frontend_contact');

        self::create_contact_record();
        self::redirect_with_message('created');
    }

    public static function handle_social_submit(): void
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(self::get_current_url()));
            exit;
        }

        check_admin_referer('wdb_frontend_social');

        self::create_social_record();
        self::redirect_with_message('created');
    }

    public static function ajax_get_regions(): void
    {
        check_ajax_referer('wdb_frontend_regions_nonce', 'nonce');

        global $wpdb;

        $level = isset($_GET['level']) ? sanitize_key(wp_unslash($_GET['level'])) : '';
        $parent_id = isset($_GET['parent_id']) ? sanitize_text_field(wp_unslash($_GET['parent_id'])) : '';

        if ('provinces' === $level) {
            wp_send_json_success(['items' => $wpdb->get_results('SELECT id, name FROM ' . $wpdb->prefix . 'wdb_regions_provinces ORDER BY name ASC', ARRAY_A) ?: []]);
        }

        if ('regencies' === $level) {
            wp_send_json_success(['items' => $wpdb->get_results($wpdb->prepare('SELECT id, name FROM ' . $wpdb->prefix . 'wdb_regions_regencies WHERE province_id = %s ORDER BY name ASC', $parent_id), ARRAY_A) ?: []]);
        }

        if ('districts' === $level) {
            wp_send_json_success(['items' => $wpdb->get_results($wpdb->prepare('SELECT id, name FROM ' . $wpdb->prefix . 'wdb_regions_districts WHERE regency_id = %s ORDER BY name ASC', $parent_id), ARRAY_A) ?: []]);
        }

        if ('villages' === $level) {
            wp_send_json_success(['items' => $wpdb->get_results($wpdb->prepare('SELECT id, name FROM ' . $wpdb->prefix . 'wdb_regions_villages WHERE district_id = %s ORDER BY name ASC', $parent_id), ARRAY_A) ?: []]);
        }

        wp_send_json_error(['message' => 'Invalid level'], 400);
    }

    private static function enqueue_form_assets(): void
    {
        wp_enqueue_style('wdb-main', WDB_PLUGIN_URL . 'assets/css/main.css', [], \WDB\Core\Plugin::asset_version('assets/css/main.css'));
        wp_enqueue_script('wdb-frontend', WDB_PLUGIN_URL . 'assets/js/frontend.js', [], \WDB\Core\Plugin::asset_version('assets/js/frontend.js'), true);
        wp_enqueue_script('wdb-autocomplete', WDB_PLUGIN_URL . 'assets/js/autocomplete.js', [], \WDB\Core\Plugin::asset_version('assets/js/autocomplete.js'), true);
        wp_enqueue_script('wdb-address-fields', WDB_PLUGIN_URL . 'assets/js/address-fields.js', [], \WDB\Core\Plugin::asset_version('assets/js/address-fields.js'), true);
        wp_localize_script(
            'wdb-address-fields',
            'wdbAddressFields',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wdb_frontend_regions_nonce'),
                'action' => 'wdb_frontend_get_regions',
            ]
        );
    }

    private static function render_pesantren_fields(array $data = []): string
    {
        $html = '';
        $html .= '<fieldset><legend>Data Primary Pesantren</legend>';
        $html .= '<p><label>Nama Pesantren</label><br><input type="text" name="nama_pesantren" value="' . esc_attr((string) ($data['nama_pesantren'] ?? '')) . '" placeholder="Contoh: Makhad Ane" required></p>';
        $html .= '<p><label>Berdiri Sejak</label><br><input type="date" name="berdiri_sejak" value="' . esc_attr((string) ($data['berdiri_sejak'] ?? '')) . '"></p>';
        $html .= '<p><label>Luas Area</label><br><input type="text" name="luas_area" value="' . esc_attr((string) ($data['luas_area'] ?? '')) . '" placeholder="Contoh: 2 hektar"></p>';
        $html .= '<p><label>Nama Pimpinan</label><br><input type="text" name="nama_pimpinan" value="' . esc_attr((string) ($data['nama_pimpinan'] ?? '')) . '" placeholder="Contoh: KH. Ahmad"></p>';
        $html .= '<p><label>Nomor HP Pimpinan</label><br><input type="text" name="nomor_hp_pimpinan" value="' . esc_attr((string) ($data['nomor_hp_pimpinan'] ?? '')) . '" placeholder="Contoh: 08xxxxxxxxxx"><br><span class="wdb-field-note">Tidak ditampilkan publik.</span></p>';
        $html .= '<p><label>Jenjang Pendidikan</label><br>' . self::render_autocomplete_select(
            'jenjang_pendidikan',
            [
                'kmi' => 'KMI',
                'diknas' => 'DIKNAS',
                'kemenag' => 'KEMENAG',
                'lainnya' => 'Lainnya',
            ],
            (string) ($data['jenjang_pendidikan'] ?? ''),
            true,
            'Cari jenjang pendidikan'
        ) . '</p>';
        $html .= '<p><label>Jenis Pondok</label><br>' . self::render_autocomplete_select(
            'jenis_pondok',
            [
                'wakaf' => 'Wakaf',
                'keluarga' => 'Keluarga',
            ],
            (string) ($data['jenis_pondok'] ?? ''),
            true,
            'Cari jenis pondok'
        ) . '</p>';
        $html .= '</fieldset>';

        $html .= '<fieldset><legend>Photo Andalan</legend>';
        $html .= self::render_image_upload_field('photo_andalan_file', 'Photo Andalan', (string) ($data['photo_andalan_id'] ?? ''), 'existing_photo_andalan_id');
        $html .= '</fieldset>';

        $html .= '<fieldset><legend>Data Pondok</legend>';
        $html .= '<p><label>Santri Putra</label><br><input type="number" name="santri_putra" min="0" value="' . esc_attr((string) ($data['santri_putra'] ?? '0')) . '" placeholder="0"></p>';
        $html .= '<p><label>Santri Putri</label><br><input type="number" name="santri_putri" min="0" value="' . esc_attr((string) ($data['santri_putri'] ?? '0')) . '" placeholder="0"></p>';
        $html .= '<p><label>Jumlah Santri Total</label><br><input type="number" name="jumlah_santri_total" min="0" value="' . esc_attr((string) ($data['jumlah_santri_total'] ?? '0')) . '" readonly></p>';
        $html .= '<p><label>Asatidz</label><br><input type="number" name="asatidz" min="0" value="' . esc_attr((string) ($data['asatidz'] ?? '0')) . '" placeholder="0"></p>';
        $html .= '<p><label>Asatidzah</label><br><input type="number" name="asatidzah" min="0" value="' . esc_attr((string) ($data['asatidzah'] ?? '0')) . '" placeholder="0"></p>';
        $html .= '<p><label>Jumlah Guru Total</label><br><input type="number" name="jumlah_guru_total" min="0" value="' . esc_attr((string) ($data['jumlah_guru_total'] ?? '0')) . '" readonly></p>';
        $html .= '</fieldset>';

        return $html;
    }

    private static function render_autocomplete_select(string $name, array $options, string $selected_value = '', bool $required = false, string $placeholder = 'Cari data'): string
    {
        $html = '<select name="' . esc_attr($name) . '" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="' . esc_attr($placeholder) . '"' . ($required ? ' required' : '') . '>';
        $html .= '<option value=""></option>';

        foreach ($options as $option_value => $option_label) {
            $html .= '<option value="' . esc_attr((string) $option_value) . '"' . selected($selected_value, (string) $option_value, false) . '>' . esc_html((string) $option_label) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    private static function render_birthplace_regency_select(string $selected_value = '', bool $required = false): string
    {
        $options = self::get_birthplace_regency_options();
        $html = '<select name="tempat_lahir" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari kabupaten atau kota"' . ($required ? ' required' : '') . '>';
        $html .= '<option value=""></option>';

        if ('' !== $selected_value && ! isset($options[$selected_value])) {
            $html .= '<option value="' . esc_attr($selected_value) . '" selected>' . esc_html($selected_value) . '</option>';
        }

        foreach ($options as $option_value => $option_label) {
            $html .= '<option value="' . esc_attr($option_value) . '"' . selected($selected_value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    private static function get_birthplace_regency_options(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            'SELECT regencies.name AS regency_name, provinces.name AS province_name
            FROM ' . $wpdb->prefix . 'wdb_regions_regencies AS regencies
            LEFT JOIN ' . $wpdb->prefix . 'wdb_regions_provinces AS provinces ON provinces.id = regencies.province_id
            ORDER BY regencies.name ASC, provinces.name ASC',
            ARRAY_A
        ) ?: [];
        $options = [];

        foreach ($rows as $row) {
            $label = self::format_birthplace_regency_label(
                (string) ($row['regency_name'] ?? ''),
                (string) ($row['province_name'] ?? '')
            );

            if ('' !== $label) {
                $options[$label] = $label;
            }
        }

        return $options;
    }

    private static function format_birthplace_regency_label(string $regency_name, string $province_name): string
    {
        $regency_name = self::normalize_birthplace_label_part($regency_name);
        $province_name = self::normalize_birthplace_label_part($province_name);

        if ('' === $province_name) {
            return $regency_name;
        }

        if ('' === $regency_name) {
            return $province_name;
        }

        return $regency_name . ' (' . $province_name . ')';
    }

    private static function normalize_birthplace_label_part(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private static function render_alumni_fields(array $data = []): string
    {
        $html = '';
        $html .= '<p><label>Nama Lengkap</label><br><input type="text" name="nama_lengkap" value="' . esc_attr((string) ($data['nama_lengkap'] ?? '')) . '" placeholder="Contoh: Ahmad Fauzi" required></p>';
        $html .= '<p><label>Tempat Lahir</label><br>' . self::render_birthplace_regency_select((string) ($data['tempat_lahir'] ?? ''), true) . '</p>';
        $html .= '<p><label>Tanggal Lahir</label><br><input type="date" name="tanggal_lahir" value="' . esc_attr((string) ($data['tanggal_lahir'] ?? '')) . '"></p>';
        $html .= '<p><label>Alumni Tahun</label><br><input type="number" name="alumni_tahun" min="0" value="' . esc_attr((string) ($data['alumni_tahun'] ?? '')) . '" placeholder="Contoh: 2015" required></p>';
        $html .= '<p><label>Jenis Kelamin</label><br>' . self::render_autocomplete_select(
            'jenis_kelamin',
            [
                'laki-laki' => 'Laki-laki',
                'perempuan' => 'Perempuan',
            ],
            (string) ($data['jenis_kelamin'] ?? ''),
            true,
            'Cari jenis kelamin'
        ) . '</p>';
        $html .= self::render_image_upload_field('pasphoto_file', 'Pasphoto', (string) ($data['pasphoto_id'] ?? ''), 'existing_pasphoto_id');

        return $html;
    }

    private static function render_dashboard_stat_tile(string $label, string $value, string $note): string
    {
        return '<article class="wdb-dashboard-stat"><p class="wdb-dashboard-stat__label">' . esc_html($label) . '</p><p class="wdb-dashboard-stat__value">' . esc_html($value) . '</p><p class="wdb-dashboard-stat__note">' . esc_html($note) . '</p></article>';
    }

    private static function render_dashboard_module_card(array $module): string
    {
        $key = (string) ($module['key'] ?? '');
        $label = (string) ($module['label'] ?? '');
        $count = (string) ($module['count'] ?? '');
        $description = (string) ($module['description'] ?? '');
        $status = (string) ($module['status'] ?? '');
        $ready = ! empty($module['ready']);
        $class = 'wdb-dashboard-module-card' . ($ready ? '' : ' is-disabled');
        $html = '<article class="' . esc_attr($class) . '">';
        $html .= '<div class="wdb-dashboard-module-card__top"><p class="wdb-dashboard-module-card__title">' . esc_html($label) . '</p><span class="wdb-dashboard-module-card__status">' . esc_html($status) . '</span></div>';
        $html .= '<p class="wdb-dashboard-module-card__count">' . esc_html($count) . '</p>';
        $html .= '<p class="wdb-dashboard-module-card__text">' . esc_html($description) . '</p>';

        if ($ready && '' !== $key) {
            $html .= '<a class="wdb-dashboard-module-card__action" href="#wdb-dashboard-panel-' . esc_attr($key) . '" data-wdb-tab-shortcut="' . esc_attr($key) . '">Buka Modul</a>';
        } else {
            $html .= '<span class="wdb-dashboard-module-card__action is-muted">Segera hadir</span>';
        }

        $html .= '</article>';

        return $html;
    }

    private static function render_dashboard_alumni_form(array $alumni, ?array $address, ?array $contact, ?array $job, ?array $social): string
    {
        $action_url = admin_url('admin-post.php');

        ob_start();
        echo '<div class="wdb-dashboard-form-wrap"><form class="wdb-form" method="post" action="' . esc_url($action_url) . '" enctype="multipart/form-data">';
        wp_nonce_field('wdb_dashboard_alumni_update');
        echo '<input type="hidden" name="action" value="wdb_update_dashboard_alumni">';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url(self::get_current_url()) . '">';
        echo '<input type="hidden" name="alumni_id" value="' . esc_attr((string) $alumni['id']) . '">';
        echo '<input type="hidden" name="address_id" value="' . esc_attr((string) ($alumni['address_id'] ?? '')) . '">';
        echo '<input type="hidden" name="contact_id" value="' . esc_attr((string) ($alumni['contact_id'] ?? '')) . '">';
        echo '<input type="hidden" name="job_id" value="' . esc_attr((string) ($alumni['job_id'] ?? '')) . '">';
        echo '<input type="hidden" name="social_id" value="' . esc_attr((string) ($alumni['social_id'] ?? '')) . '">';
        echo self::render_alumni_fields($alumni);
        echo self::render_dashboard_address_fields($address);
        echo self::render_dashboard_contact_fields($contact, 'alumni');
        echo self::render_dashboard_job_fields($job);
        echo self::render_dashboard_social_fields($social);
        echo '<p><button class="wdb-button" type="submit">Update Profil</button></p>';
        echo '</form></div>';

        return (string) ob_get_clean();
    }

    private static function render_dashboard_pesantren_form(array $pesantren, ?array $address, ?array $contact, ?array $social): string
    {
        $action_url = admin_url('admin-post.php');

        ob_start();
        echo '<div class="wdb-dashboard-form-wrap wdb-dashboard-form-wrap--subtle">';
        echo '<form class="wdb-form" method="post" action="' . esc_url($action_url) . '" enctype="multipart/form-data">';
        wp_nonce_field('wdb_dashboard_pesantren_update');
        echo '<input type="hidden" name="action" value="wdb_update_dashboard_pesantren">';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url(self::get_current_url()) . '">';
        echo '<input type="hidden" name="pesantren_id" value="' . esc_attr((string) $pesantren['id']) . '">';
        echo '<input type="hidden" name="address_id" value="' . esc_attr((string) ($pesantren['address_id'] ?? '')) . '">';
        echo '<input type="hidden" name="contact_id" value="' . esc_attr((string) ($pesantren['contact_id'] ?? '')) . '">';
        echo '<input type="hidden" name="social_id" value="' . esc_attr((string) ($pesantren['social_id'] ?? '')) . '">';
        echo self::render_pesantren_fields($pesantren);
        echo self::render_dashboard_address_fields($address);
        echo self::render_dashboard_contact_fields($contact, 'pesantren');
        echo self::render_dashboard_social_fields($social);
        echo '<p><button class="wdb-button" type="submit">Update Pesantren</button></p>';
        echo '</form>';
        echo '</div>';

        return (string) ob_get_clean();
    }

    private static function render_address_fields(string $prefix = '', string $legend = 'Alamat', string $note = 'Alamat yang ditampilkan publik hanya kecamatan dan kabupaten.', string $label = 'Alamat Lengkap', bool $required = true, string $value = '', array $selected = []): string
    {
        return '<fieldset><legend>' . esc_html($legend) . '</legend><p class="wdb-field-note">' . esc_html($note) . '</p><p><label>' . esc_html($label) . '</label><br><textarea name="' . esc_attr($prefix . 'alamat_lengkap') . '" rows="4" placeholder="Contoh: Jl. Webane No. 10"' . ($required ? ' required' : '') . '>' . esc_textarea($value) . '</textarea></p>' . self::render_region_select_fields($selected, $prefix) . '</fieldset>';
    }

    private static function render_region_select_fields(array $selected = [], string $prefix = ''): string
    {
        $selected = self::apply_focus_address_defaults($selected);
        $selected_json = wp_json_encode(
            [
                'provinsi_code' => (string) ($selected['provinsi_code'] ?? ''),
                'kabupaten_code' => (string) ($selected['kabupaten_code'] ?? ''),
                'kecamatan_code' => (string) ($selected['kecamatan_code'] ?? ''),
                'desa_code' => (string) ($selected['desa_code'] ?? ''),
            ]
        );

        $html = '';
        $html .= '<div data-wdb-region-scope>';
        $html .= '<p><label>Provinsi</label><br><select data-wdb-region-select="provinsi" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari provinsi"><option value="">Pilih Provinsi</option></select></p>';
        $html .= '<p><label>Kabupaten</label><br><select data-wdb-region-select="kabupaten" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari kabupaten"><option value="">Pilih Kabupaten</option></select></p>';
        $html .= '<p><label>Kecamatan</label><br><select data-wdb-region-select="kecamatan" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari kecamatan"><option value="">Pilih Kecamatan</option></select></p>';
        $html .= '<p><label>Desa</label><br><select data-wdb-region-select="desa" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari desa"><option value="">Pilih Desa</option></select></p>';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'provinsi_code') . '" data-wdb-region-code="provinsi" value="' . esc_attr((string) ($selected['provinsi_code'] ?? '')) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'provinsi_name') . '" data-wdb-region-name="provinsi" value="' . esc_attr((string) ($selected['provinsi_name'] ?? '')) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'kabupaten_code') . '" data-wdb-region-code="kabupaten" value="' . esc_attr((string) ($selected['kabupaten_code'] ?? '')) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'kabupaten_name') . '" data-wdb-region-name="kabupaten" value="' . esc_attr((string) ($selected['kabupaten_name'] ?? '')) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'kecamatan_code') . '" data-wdb-region-code="kecamatan" value="' . esc_attr((string) ($selected['kecamatan_code'] ?? '')) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'kecamatan_name') . '" data-wdb-region-name="kecamatan" value="' . esc_attr((string) ($selected['kecamatan_name'] ?? '')) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'desa_code') . '" data-wdb-region-code="desa" value="' . esc_attr((string) ($selected['desa_code'] ?? '')) . '">';
        $html .= '<input type="hidden" name="' . esc_attr($prefix . 'desa_name') . '" data-wdb-region-name="desa" value="' . esc_attr((string) ($selected['desa_name'] ?? '')) . '">';
        $html .= '<script type="application/json" data-wdb-region-selected>' . $selected_json . '</script>';
        $html .= '</div>';

        return $html;
    }

    private static function apply_focus_address_defaults(array $selected): array
    {
        if (! empty($selected['provinsi_code']) || ! empty($selected['kabupaten_code'])) {
            return $selected;
        }

        $focus = get_option('wdb_focus_address', []);

        if (! is_array($focus) || (empty($focus['provinsi_code']) && empty($focus['kabupaten_code']))) {
            return $selected;
        }

        $selected['provinsi_code'] = (string) ($focus['provinsi_code'] ?? '');
        $selected['provinsi_name'] = (string) ($focus['provinsi_name'] ?? '');
        $selected['kabupaten_code'] = (string) ($focus['kabupaten_code'] ?? '');
        $selected['kabupaten_name'] = (string) ($focus['kabupaten_name'] ?? '');

        return $selected;
    }

    private static function render_job_fields(?array $job = null): string
    {
        $job = is_array($job) ? $job : [];
        $job_address = self::get_related_record('addresses', isset($job['address_id']) ? (int) $job['address_id'] : 0);
        $job_address = is_array($job_address) ? $job_address : [];
        $html = '<fieldset><legend>Pekerjaan</legend>';
        $html .= '<input type="hidden" name="job_id" value="' . esc_attr((string) ($job['id'] ?? '')) . '">';
        $html .= '<input type="hidden" name="job_address_id" value="' . esc_attr((string) ($job['address_id'] ?? '')) . '">';
        $html .= '<p><label>Pekerjaan</label><br>' . self::render_autocomplete_select('job_pekerjaan', self::get_job_options(), (string) ($job['pekerjaan'] ?? ''), false, 'Cari pekerjaan') . '</p>';
        $html .= '<p><label>Nama Lembaga / Instansi / Usaha</label><br><input type="text" name="job_nama_lembaga" value="' . esc_attr((string) ($job['nama_lembaga'] ?? '')) . '" placeholder="Contoh: PT Webane Indonesia"></p>';
        $html .= '<p><label>Jabatan / Bidang Usaha</label><br><input type="text" name="job_jabatan" value="' . esc_attr((string) ($job['jabatan'] ?? '')) . '" placeholder="Contoh: Software Engineer"></p>';
        $html .= '</fieldset>';
        $html .= self::render_address_fields('job_', 'Alamat Lembaga / Instansi', 'Alamat lembaga / instansi bisa memakai komponen alamat yang sama.', 'Alamat Lembaga / Instansi', false, (string) ($job_address['alamat_lengkap'] ?? ''), $job_address);

        return $html;
    }

    private static function render_dashboard_job_fields(?array $job): string
    {
        return self::render_job_fields($job);
    }

    private static function render_contact_fields(bool $require_email, string $context = 'alumni'): string
    {
        $required = $require_email ? ' required' : '';
        $html = '<fieldset><legend>Kontak</legend><div data-wdb-contact-sync>';
        $html .= '<p class="wdb-field-note">' . ('pesantren' === $context ? 'Nomor telepon dan tombol WhatsApp hanya tampil publik jika Anda izinkan.' : 'Nomor telepon tidak ditampilkan publik. Tombol WhatsApp hanya tampil jika Anda izinkan.') . '</p>';
        $html .= '<p><label>Email</label><br><input type="email" name="email" placeholder="Contoh: nama@domain.com"' . $required . '></p>';
        $html .= '<p><label>Nomor HP</label><br><input type="text" name="nomor_hp" placeholder="Contoh: 08xxxxxxxxxx" data-wdb-contact-hp></p>';
        $html .= '<p><label>Nomor WhatsApp</label><br><input type="text" name="nomor_whatsapp" placeholder="Contoh: 08xxxxxxxxxx" data-wdb-contact-wa></p>';
        $html .= '<p><label><input type="checkbox" name="whatsapp_sama_dengan_hp" value="1" data-wdb-contact-sync-toggle> WhatsApp sama dengan HP</label></p>';
        if ('pesantren' === $context) {
            $html .= '<p><label><input type="checkbox" name="nomor_hp_tampil_publik" value="1"> Tampilkan nomor telepon di halaman publik</label></p>';
        }
        $html .= '<p><label><input type="checkbox" name="whatsapp_tampil_publik" value="1"> Tampilkan tombol WhatsApp di halaman publik</label></p>';
        $html .= '</div></fieldset>';

        return $html;
    }

    private static function render_social_fields(): string
    {
        return '<fieldset><legend>Sosial Media</legend><p><label>URL Profil</label><br><input type="url" name="url_profil" placeholder="Contoh: https://contoh.com/profil"></p><p><label>Instagram</label><br><input type="url" name="instagram" placeholder="Contoh: https://instagram.com/username"></p><p><label>Facebook</label><br><input type="url" name="facebook" placeholder="Contoh: https://facebook.com/username"></p><p><label>Tiktok</label><br><input type="url" name="tiktok" placeholder="Contoh: https://tiktok.com/@username"></p><p><label>Youtube</label><br><input type="url" name="youtube" placeholder="Contoh: https://youtube.com/@channel"></p><p><label>Website</label><br><input type="url" name="website" placeholder="Contoh: https://websiteanda.com"></p></fieldset>';
    }

    private static function render_alumni_auth_fields(): string
    {
        if (is_user_logged_in()) {
            return '';
        }

        return '<fieldset><legend>Akun Alumni</legend><p>Isi password jika ingin akun login alumni. Email pada bagian kontak akan dipakai untuk login. Kosongkan password jika hanya ingin kirim data tanpa login.</p><p><label>Password</label><br><input type="password" name="account_password" placeholder="Kosongkan jika tidak ingin login"></p></fieldset>';
    }

    private static function render_dashboard_address_fields(?array $address): string
    {
        $address = is_array($address) ? $address : [];

        return '<fieldset class="wdb-dashboard-fieldset"><legend>Alamat</legend><p class="wdb-field-note">Alamat yang ditampilkan publik hanya kecamatan dan kabupaten.</p><p><label>Alamat Lengkap</label><br><textarea name="alamat_lengkap" rows="3" placeholder="Contoh: Jl. Webane No. 10">' . esc_textarea((string) ($address['alamat_lengkap'] ?? '')) . '</textarea></p>' . self::render_region_select_fields($address) . '</fieldset>';
    }

    private static function render_dashboard_contact_fields(?array $contact, string $context = 'alumni'): string
    {
        $html = '<fieldset class="wdb-dashboard-fieldset"><legend>Kontak</legend><div data-wdb-contact-sync>';
        $html .= '<p class="wdb-field-note">' . ('pesantren' === $context ? 'Nomor telepon dan tombol WhatsApp hanya tampil publik jika Anda izinkan.' : 'Nomor telepon tidak ditampilkan publik. Tombol WhatsApp hanya tampil jika Anda izinkan.') . '</p>';
        $html .= '<p><label>Email</label><br><input type="email" name="email" value="' . esc_attr((string) ($contact['email'] ?? '')) . '" placeholder="Contoh: nama@domain.com"></p>';
        $html .= '<p><label>Nomor HP</label><br><input type="text" name="nomor_hp" value="' . esc_attr((string) ($contact['nomor_hp'] ?? '')) . '" placeholder="Contoh: 08xxxxxxxxxx" data-wdb-contact-hp></p>';
        $html .= '<p><label>Nomor WhatsApp</label><br><input type="text" name="nomor_whatsapp" value="' . esc_attr((string) ($contact['nomor_whatsapp'] ?? '')) . '" placeholder="Contoh: 08xxxxxxxxxx" data-wdb-contact-wa></p>';
        $html .= '<p><label><input type="checkbox" name="whatsapp_sama_dengan_hp" value="1" data-wdb-contact-sync-toggle' . checked(! empty($contact['whatsapp_sama_dengan_hp']), true, false) . '> WhatsApp sama dengan HP</label></p>';
        if ('pesantren' === $context) {
            $html .= '<p><label><input type="checkbox" name="nomor_hp_tampil_publik" value="1"' . checked(! empty($contact['nomor_hp_tampil_publik']), true, false) . '> Tampilkan nomor telepon di halaman publik</label></p>';
        }
        $html .= '<p><label><input type="checkbox" name="whatsapp_tampil_publik" value="1"' . checked(! empty($contact['whatsapp_tampil_publik']), true, false) . '> Tampilkan tombol WhatsApp di halaman publik</label></p>';
        $html .= '</div></fieldset>';

        return $html;
    }

    private static function render_dashboard_social_fields(?array $social): string
    {
        return '<fieldset class="wdb-dashboard-fieldset"><legend>Sosial Media</legend><p><label>URL Profil</label><br><input type="url" name="url_profil" value="' . esc_attr((string) ($social['url_profil'] ?? '')) . '" placeholder="Contoh: https://contoh.com/profil"></p><p><label>Instagram</label><br><input type="url" name="instagram" value="' . esc_attr((string) ($social['instagram'] ?? '')) . '" placeholder="Contoh: https://instagram.com/username"></p><p><label>Facebook</label><br><input type="url" name="facebook" value="' . esc_attr((string) ($social['facebook'] ?? '')) . '" placeholder="Contoh: https://facebook.com/username"></p><p><label>Tiktok</label><br><input type="url" name="tiktok" value="' . esc_attr((string) ($social['tiktok'] ?? '')) . '" placeholder="Contoh: https://tiktok.com/@username"></p><p><label>Youtube</label><br><input type="url" name="youtube" value="' . esc_attr((string) ($social['youtube'] ?? '')) . '" placeholder="Contoh: https://youtube.com/@channel"></p><p><label>Website</label><br><input type="url" name="website" value="' . esc_attr((string) ($social['website'] ?? '')) . '" placeholder="Contoh: https://websiteanda.com"></p></fieldset>';
    }

    private static function get_job_options(): array
    {
        return [
            'Anggota TNI / POLRI' => 'Aparatur Negara & Keamanan - Anggota TNI / POLRI',
            'Pegawai Negeri Sipil (PNS)' => 'Aparatur Negara & Keamanan - Pegawai Negeri Sipil (PNS)',
            'Pegawai Pemerintah (PPPK)' => 'Aparatur Negara & Keamanan - Pegawai Pemerintah (PPPK)',
            'Dosen' => 'Pendidikan & Sains - Dosen',
            'Guru' => 'Pendidikan & Sains - Guru',
            'Peneliti / Ilmuwan' => 'Pendidikan & Sains - Peneliti / Ilmuwan',
            'Apoteker' => 'Tenaga Kesehatan - Apoteker',
            'Dokter Umum / Spesialis' => 'Tenaga Kesehatan - Dokter Umum / Spesialis',
            'Perawat / Bidan' => 'Tenaga Kesehatan - Perawat / Bidan',
            'Akuntan / Auditor' => 'Karyawan & Profesional - Akuntan / Auditor',
            'Karyawan Swasta' => 'Karyawan & Profesional - Karyawan Swasta',
            'Legal / Pengacara' => 'Karyawan & Profesional - Legal / Pengacara',
            'Manager / Direktur' => 'Karyawan & Profesional - Manager / Direktur',
            'Data Scientist' => 'Teknologi & Digital - Data Scientist',
            'Software Engineer / Developer' => 'Teknologi & Digital - Software Engineer / Developer',
            'UI/UX Designer' => 'Teknologi & Digital - UI/UX Designer',
            'Pedagang / Retail' => 'Wiraswasta & Usaha Mandiri - Pedagang / Retail',
            'Pemilik Bisnis / UMKM' => 'Wiraswasta & Usaha Mandiri - Pemilik Bisnis / UMKM',
            'Content Creator / Influencer' => 'Pekerja Lepas & Kreatif - Content Creator / Influencer',
            'Fotografer / Videografer' => 'Pekerja Lepas & Kreatif - Fotografer / Videografer',
            'Freelancer' => 'Pekerja Lepas & Kreatif - Freelancer',
            'Driver Ojek / Taksi Online' => 'Transportasi & Logistik - Driver Ojek / Taksi Online',
            'Kurir / Petugas Logistik' => 'Transportasi & Logistik - Kurir / Petugas Logistik',
            'Pilot / Nakhoda / Masinis' => 'Transportasi & Logistik - Pilot / Nakhoda / Masinis',
            'Nelayan' => 'Sektor Agraris - Nelayan',
            'Petani' => 'Sektor Agraris - Petani',
            'Peternak' => 'Sektor Agraris - Peternak',
            'Belum / Tidak Bekerja' => 'Umum / Lainnya - Belum / Tidak Bekerja',
            'Ibu Rumah Tangga' => 'Umum / Lainnya - Ibu Rumah Tangga',
            'Pelajar / Mahasiswa' => 'Umum / Lainnya - Pelajar / Mahasiswa',
            'Pensiunan' => 'Umum / Lainnya - Pensiunan',
        ];
    }

    private static function render_image_upload_field(string $field_name, string $label, string $attachment_id, string $hidden_field): string
    {
        $html = '<div class="wdb-upload-field">';
        $html .= '<label class="wdb-upload-field__label">' . esc_html($label) . '</label>';
        $size = 'medium';
        $hint = 'Upload gambar yang jelas.';

        if ('photo_andalan_file' === $field_name) {
            $size = \WDB\Core\Plugin::get_pesantren_photo_size();
            $hint = 'Rasio ideal 16:9 untuk photo andalan pesantren.';
        }

        if ('pasphoto_file' === $field_name) {
            $size = \WDB\Core\Plugin::get_alumni_pasphoto_size();
            $hint = 'Rasio ideal 3:4 untuk pasphoto alumni.';
        }

        $html .= '<div class="wdb-upload-field__frame">';

        if ('' !== $attachment_id) {
            $image_url = wp_get_attachment_image_url((int) $attachment_id, $size);

            if (! $image_url) {
                $image_url = wp_get_attachment_image_url((int) $attachment_id, 'medium');
            }

            if ($image_url) {
                $html .= '<img src="' . esc_url($image_url) . '" alt="" class="wdb-upload-field__preview">';
            }
        }

        $html .= '<input type="hidden" name="' . esc_attr($hidden_field) . '" value="' . esc_attr($attachment_id) . '">';
        $html .= '<input class="wdb-upload-field__input" type="file" name="' . esc_attr($field_name) . '" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">';
        $html .= '<p class="wdb-upload-field__hint">' . esc_html($hint) . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private static function render_attachment_gallery(array $attachments): string
    {
        $html = '<div class="wdb-dashboard-gallery"><h4 class="wdb-dashboard-gallery__title">Galeri Attachment Saya</h4>';

        if (empty($attachments)) {
            $html .= '<p>Belum ada attachment gambar.</p></div>';

            return $html;
        }

        $html .= '<div class="wdb-dashboard-gallery__grid">';

        foreach ($attachments as $attachment) {
            $image = wp_get_attachment_image_url($attachment->ID, 'medium');

            if (! $image) {
                continue;
            }

            $html .= '<div class="wdb-dashboard-gallery__item">';
            $html .= '<img src="' . esc_url($image) . '" alt="" class="wdb-dashboard-gallery__image">';
            $html .= '<div class="wdb-dashboard-gallery__meta">ID ' . esc_html((string) $attachment->ID) . '</div>';
            $html .= '<div class="wdb-dashboard-gallery__name">' . esc_html((string) get_the_title($attachment->ID)) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    private static function create_address_record(string $prefix = ''): ?int
    {
        global $wpdb;

        $data = [
            'alamat_lengkap' => isset($_POST[$prefix . 'alamat_lengkap']) ? sanitize_textarea_field(wp_unslash($_POST[$prefix . 'alamat_lengkap'])) : '',
            'provinsi_code' => isset($_POST[$prefix . 'provinsi_code']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'provinsi_code'])) : '',
            'provinsi_name' => isset($_POST[$prefix . 'provinsi_name']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'provinsi_name'])) : '',
            'kabupaten_code' => isset($_POST[$prefix . 'kabupaten_code']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'kabupaten_code'])) : '',
            'kabupaten_name' => isset($_POST[$prefix . 'kabupaten_name']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'kabupaten_name'])) : '',
            'kecamatan_code' => isset($_POST[$prefix . 'kecamatan_code']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'kecamatan_code'])) : '',
            'kecamatan_name' => isset($_POST[$prefix . 'kecamatan_name']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'kecamatan_name'])) : '',
            'desa_code' => isset($_POST[$prefix . 'desa_code']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'desa_code'])) : '',
            'desa_name' => isset($_POST[$prefix . 'desa_name']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'desa_name'])) : '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        if ('' === $data['alamat_lengkap'] && '' === $data['provinsi_code'] && '' === $data['kabupaten_code'] && '' === $data['kecamatan_code'] && '' === $data['desa_code']) {
            return null;
        }

        $wpdb->insert($wpdb->prefix . 'wdb_addresses', $data, ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function create_job_record(): ?int
    {
        global $wpdb;

        $job_address_id = self::create_address_record('job_');
        $data = [
            'pekerjaan' => isset($_POST['job_pekerjaan']) ? sanitize_text_field(wp_unslash($_POST['job_pekerjaan'])) : '',
            'nama_lembaga' => self::nullable_text('job_nama_lembaga'),
            'jabatan' => self::nullable_text('job_jabatan'),
            'address_id' => $job_address_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        if ('' === trim((string) $data['pekerjaan']) && '' === trim((string) ($data['nama_lembaga'] ?? '')) && '' === trim((string) ($data['jabatan'] ?? '')) && empty($job_address_id)) {
            return null;
        }

        $wpdb->insert($wpdb->prefix . 'wdb_jobs', $data, ['%s', '%s', '%s', '%d', '%s', '%s']);

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function create_contact_record(): ?int
    {
        global $wpdb;

        $whatsapp_same = isset($_POST['whatsapp_sama_dengan_hp']) ? 1 : 0;
        $phone_public = isset($_POST['nomor_hp_tampil_publik']) ? 1 : 0;
        $whatsapp_public = isset($_POST['whatsapp_tampil_publik']) ? 1 : 0;
        $nomor_hp = self::nullable_text('nomor_hp');

        $wpdb->insert(
            $wpdb->prefix . 'wdb_contacts',
            [
                'email' => self::nullable_email('email'),
                'nomor_hp' => $nomor_hp,
                'nomor_hp_tampil_publik' => $phone_public,
                'nomor_whatsapp' => $whatsapp_same ? $nomor_hp : self::nullable_text('nomor_whatsapp'),
                'whatsapp_sama_dengan_hp' => $whatsapp_same,
                'whatsapp_tampil_publik' => $whatsapp_public,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s']
        );

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function create_social_record(): ?int
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wdb_socials',
            [
                'url_profil' => self::nullable_url('url_profil'),
                'instagram' => self::nullable_url('instagram'),
                'facebook' => self::nullable_url('facebook'),
                'tiktok' => self::nullable_url('tiktok'),
                'youtube' => self::nullable_url('youtube'),
                'website' => self::nullable_url('website'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function save_dashboard_address(int $address_id, string $prefix = ''): ?int
    {
        global $wpdb;

        $data = [
            'alamat_lengkap' => isset($_POST[$prefix . 'alamat_lengkap']) ? sanitize_textarea_field(wp_unslash($_POST[$prefix . 'alamat_lengkap'])) : '',
            'provinsi_code' => isset($_POST[$prefix . 'provinsi_code']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'provinsi_code'])) : '',
            'provinsi_name' => isset($_POST[$prefix . 'provinsi_name']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'provinsi_name'])) : '',
            'kabupaten_code' => isset($_POST[$prefix . 'kabupaten_code']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'kabupaten_code'])) : '',
            'kabupaten_name' => isset($_POST[$prefix . 'kabupaten_name']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'kabupaten_name'])) : '',
            'kecamatan_code' => isset($_POST[$prefix . 'kecamatan_code']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'kecamatan_code'])) : '',
            'kecamatan_name' => isset($_POST[$prefix . 'kecamatan_name']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'kecamatan_name'])) : '',
            'desa_code' => isset($_POST[$prefix . 'desa_code']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'desa_code'])) : '',
            'desa_name' => isset($_POST[$prefix . 'desa_name']) ? sanitize_text_field(wp_unslash($_POST[$prefix . 'desa_name'])) : '',
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

    private static function save_dashboard_job(int $job_id): ?int
    {
        global $wpdb;

        $job_address_id = self::save_dashboard_address(isset($_POST['job_address_id']) ? absint(wp_unslash($_POST['job_address_id'])) : 0, 'job_');
        $data = [
            'pekerjaan' => isset($_POST['job_pekerjaan']) ? sanitize_text_field(wp_unslash($_POST['job_pekerjaan'])) : '',
            'nama_lembaga' => self::nullable_text('job_nama_lembaga'),
            'jabatan' => self::nullable_text('job_jabatan'),
            'address_id' => $job_address_id,
            'updated_at' => current_time('mysql'),
        ];

        if ($job_id > 0) {
            $wpdb->update($wpdb->prefix . 'wdb_jobs', $data, ['id' => $job_id], ['%s', '%s', '%s', '%d', '%s'], ['%d']);

            return $job_id;
        }

        if ('' === trim((string) $data['pekerjaan']) && '' === trim((string) ($data['nama_lembaga'] ?? '')) && '' === trim((string) ($data['jabatan'] ?? '')) && empty($job_address_id)) {
            return null;
        }

        $data['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'wdb_jobs', $data, ['%s', '%s', '%s', '%d', '%s', '%s']);

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function save_dashboard_contact(int $contact_id): ?int
    {
        global $wpdb;

        $whatsapp_same = isset($_POST['whatsapp_sama_dengan_hp']) ? 1 : 0;
        $phone_public = isset($_POST['nomor_hp_tampil_publik']) ? 1 : 0;
        $whatsapp_public = isset($_POST['whatsapp_tampil_publik']) ? 1 : 0;
        $nomor_hp = self::nullable_text('nomor_hp');
        $data = [
            'email' => self::nullable_email('email'),
            'nomor_hp' => $nomor_hp,
            'nomor_hp_tampil_publik' => $phone_public,
            'nomor_whatsapp' => $whatsapp_same ? $nomor_hp : self::nullable_text('nomor_whatsapp'),
            'whatsapp_sama_dengan_hp' => $whatsapp_same,
            'whatsapp_tampil_publik' => $whatsapp_public,
            'updated_at' => current_time('mysql'),
        ];

        if ($contact_id > 0) {
            $wpdb->update($wpdb->prefix . 'wdb_contacts', $data, ['id' => $contact_id], ['%s', '%s', '%d', '%s', '%d', '%d', '%s'], ['%d']);

            return $contact_id;
        }

        $data['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'wdb_contacts', $data, ['%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s']);

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function save_dashboard_social(int $social_id): ?int
    {
        global $wpdb;

        $data = [
            'url_profil' => self::nullable_url('url_profil'),
            'instagram' => self::nullable_url('instagram'),
            'facebook' => self::nullable_url('facebook'),
            'tiktok' => self::nullable_url('tiktok'),
            'youtube' => self::nullable_url('youtube'),
            'website' => self::nullable_url('website'),
            'updated_at' => current_time('mysql'),
        ];

        if ($social_id > 0) {
            $wpdb->update($wpdb->prefix . 'wdb_socials', $data, ['id' => $social_id], ['%s', '%s', '%s', '%s', '%s', '%s', '%s'], ['%d']);

            return $social_id;
        }

        $data['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'wdb_socials', $data, ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function upload_image_field(string $field, int $author_id): ?int
    {
        if (! isset($_FILES[$field]) || empty($_FILES[$field]['name'])) {
            return null;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload(
            $field,
            0,
            [],
            [
                'test_form' => false,
                'mimes' => [
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                ],
            ]
        );

        if (is_wp_error($attachment_id)) {
            return null;
        }

        if ($author_id > 0) {
            wp_update_post(['ID' => (int) $attachment_id, 'post_author' => $author_id]);
        }

        return (int) $attachment_id;
    }

    private static function resolve_uploaded_image(string $field, string $existing_field, int $author_id): ?int
    {
        $uploaded = self::upload_image_field($field, $author_id);

        if (null !== $uploaded) {
            return $uploaded;
        }

        return self::nullable_int($existing_field);
    }

    private static function create_alumni_user(): int
    {
        $email = self::nullable_email('email');
        $password = isset($_POST['account_password']) ? (string) wp_unslash($_POST['account_password']) : '';
        $full_name = isset($_POST['nama_lengkap']) ? sanitize_text_field(wp_unslash($_POST['nama_lengkap'])) : '';

        if (null === $email || '' === $password || email_exists($email)) {
            return 0;
        }

        $username = self::generate_unique_username($email, $full_name);
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return 0;
        }

        wp_update_user(['ID' => $user_id, 'display_name' => $full_name, 'nickname' => $full_name, 'role' => 'alumni']);
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        return (int) $user_id;
    }

    private static function should_create_alumni_account(): bool
    {
        return '' !== trim((string) (isset($_POST['account_password']) ? wp_unslash($_POST['account_password']) : ''));
    }

    private static function generate_unique_username(string $email, string $full_name): string
    {
        $email_name = strstr($email, '@', true);
        $base = '' !== $full_name ? sanitize_user($full_name, true) : sanitize_user((string) $email_name, true);

        if ('' === $base) {
            $base = 'alumni';
        }

        $username = $base;
        $suffix = 1;

        while (username_exists($username)) {
            $username = $base . $suffix;
            $suffix++;
        }

        return $username;
    }

    private static function render_public_alumni_card(array $row): string
    {
        $html = '<article style="padding:18px;border:1px solid #dbe4ee;border-radius:18px;background:#fff;">';
        $html .= self::render_public_record_image((int) ($row['pasphoto_id'] ?? 0), \WDB\Core\Plugin::get_alumni_pasphoto_size(), '3 / 4', 120);
        $html .= '<h3 style="margin:0 0 8px;font-size:20px;line-height:1.2;">' . esc_html((string) ($row['nama_lengkap'] ?? '')) . '</h3>';
        $html .= '<p style="margin:0 0 6px;color:#475569;">Tempat Lahir: ' . esc_html((string) ($row['tempat_lahir'] ?? '')) . '</p>';
        $html .= '<p style="margin:0 0 6px;color:#475569;">Alumni Tahun: ' . esc_html((string) ($row['alumni_tahun'] ?? '')) . '</p>';
        $html .= '<p style="margin:0 0 16px;color:#475569;">Jenis Kelamin: ' . esc_html((string) ($row['jenis_kelamin'] ?? '')) . '</p>';
        $html .= '<a class="wdb-button" href="' . esc_url(home_url('/detail-alumni/' . (int) $row['id'] . '/')) . '">Lihat Detail</a>';
        $html .= '</article>';

        return $html;
    }

    private static function render_public_related_alumni_card(array $row): string
    {
        $html = '<article style="padding:18px;border:1px solid #dbe4ee;border-radius:18px;background:#fff;">';
        $html .= self::render_public_record_image((int) ($row['pasphoto_id'] ?? 0), \WDB\Core\Plugin::get_alumni_pasphoto_size(), '3 / 4', 120);
        $html .= '<h3 style="margin:0 0 8px;font-size:20px;line-height:1.2;">' . esc_html((string) ($row['nama_lengkap'] ?? '')) . '</h3>';
        $html .= '<p style="margin:0 0 6px;color:#475569;">Alamat: ' . esc_html(self::format_public_alumni_address_summary($row)) . '</p>';
        $html .= '<p style="margin:0 0 16px;color:#475569;">Alumni: ' . esc_html((string) ($row['alumni_tahun'] ?? '')) . '</p>';
        $html .= '<a class="wdb-button" href="' . esc_url(home_url('/detail-alumni/' . (int) $row['id'] . '/')) . '">Lihat Detail</a>';
        $html .= '</article>';

        return $html;
    }

    private static function render_public_pesantren_card(array $row): string
    {
        $html = '<article style="padding:18px;border:1px solid #dbe4ee;border-radius:18px;background:#fff;">';
        $html .= self::render_public_record_image((int) ($row['photo_andalan_id'] ?? 0), \WDB\Core\Plugin::get_pesantren_photo_size(), '16 / 9', 180);
        $html .= '<h3 style="margin:0 0 8px;font-size:20px;line-height:1.2;">' . esc_html((string) ($row['nama_pesantren'] ?? '')) . '</h3>';
        $html .= '<p style="margin:0 0 6px;color:#475569;">Jenjang: ' . esc_html(strtoupper((string) ($row['jenjang_pendidikan'] ?? ''))) . '</p>';
        $html .= '<p style="margin:0 0 6px;color:#475569;">Jenis Pondok: ' . esc_html(ucfirst((string) ($row['jenis_pondok'] ?? ''))) . '</p>';
        $html .= '<p style="margin:0 0 16px;color:#475569;">Jumlah Santri: ' . esc_html((string) ($row['jumlah_santri_total'] ?? '0')) . '</p>';
        $html .= '<a class="wdb-button" href="' . esc_url(home_url('/detail-pesantren/' . (int) $row['id'] . '/')) . '">Lihat Detail</a>';
        $html .= '</article>';

        return $html;
    }

    private static function render_public_record_image(int $attachment_id, string $size, string $aspect_ratio, int $width = 160): string
    {
        if ($attachment_id <= 0) {
            return '';
        }

        $image_url = wp_get_attachment_image_url($attachment_id, $size);

        if (! $image_url) {
            $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
        }

        if (! $image_url) {
            return '';
        }

        return '<div style="width:' . esc_attr((string) $width) . 'px;max-width:100%;aspect-ratio:' . esc_attr($aspect_ratio) . ';overflow:hidden;border-radius:16px;margin:0 0 16px;"><img src="' . esc_url($image_url) . '" alt="" style="display:block;width:100%;height:100%;object-fit:cover;"></div>';
    }

    private static function render_public_featured_image(int $attachment_id, string $size, string $aspect_ratio): string
    {
        if ($attachment_id <= 0) {
            return '';
        }

        $image_url = wp_get_attachment_image_url($attachment_id, $size);

        if (! $image_url) {
            $image_url = wp_get_attachment_image_url($attachment_id, 'large');
        }

        if (! $image_url) {
            $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
        }

        if (! $image_url) {
            return '';
        }

        return '<div class="wdb-detail-hero" style="aspect-ratio:' . esc_attr($aspect_ratio) . ';"><img src="' . esc_url($image_url) . '" alt="" loading="lazy"></div>';
    }

    private static function render_public_detail_grid(array $items): string
    {
        $html = '<div class="wdb-detail-grid">';

        foreach ($items as $label => $value) {
            $html .= '<div class="wdb-detail-grid__item">';
            $html .= '<p class="wdb-detail-grid__label">' . esc_html($label) . '</p>';
            $html .= '<p class="wdb-detail-grid__value">' . esc_html('' !== trim((string) $value) ? (string) $value : '-') . '</p>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private static function render_public_pesantren_contact_bar(array $record, ?array $contact): string
    {
        if (! is_array($contact)) {
            return '';
        }

        $phone = trim((string) ($contact['nomor_hp'] ?? ''));
        $whatsapp = trim((string) ($contact['nomor_whatsapp'] ?? ''));
        $message = sprintf(
            'Saya mendapat informasi dari website %s, saya mau bertanya tentang %s.',
            home_url('/'),
            (string) ($record['nama_pesantren'] ?? '')
        );
        $html = '<div class="wdb-detail-contact">';

        if (! empty($contact['nomor_hp_tampil_publik']) && '' !== $phone) {
            $html .= '<div class="wdb-detail-contact__item"><span class="wdb-detail-contact__label">Nomor Telepon</span><span class="wdb-detail-contact__value">' . esc_html($phone) . '</span></div>';
        }

        if (! empty($contact['whatsapp_tampil_publik']) && '' !== $whatsapp) {
            $html .= '<div class="wdb-detail-contact__action"><a class="wdb-button" href="' . esc_url(self::build_whatsapp_url($whatsapp, $message)) . '" target="_blank" rel="noopener noreferrer">Kirim WhatsApp</a></div>';
        }

        $html .= '</div>';

        return '<div class="wdb-detail-contact"></div>' === $html ? '' : $html;
    }

    private static function render_public_alumni_whatsapp_action(array $record, ?array $contact): string
    {
        if (! is_array($contact) || empty($contact['whatsapp_tampil_publik'])) {
            return '';
        }

        $whatsapp = trim((string) ($contact['nomor_whatsapp'] ?? ''));

        if ('' === $whatsapp) {
            return '';
        }

        $message = sprintf(
            'Saya mendapat informasi dari website %s, saya mau bertanya tentang %s.',
            home_url('/'),
            (string) ($record['nama_lengkap'] ?? '')
        );

        return '<div class="wdb-detail-contact"><div class="wdb-detail-contact__action"><a class="wdb-button" href="' . esc_url(self::build_whatsapp_url($whatsapp, $message)) . '" target="_blank" rel="noopener noreferrer">Kirim WhatsApp</a></div></div>';
    }

    private static function render_public_social_icon_links(?array $social): string
    {
        if (! is_array($social)) {
            return '';
        }

        $links = [
            'url_profil' => ['label' => 'Profil', 'icon' => self::get_public_social_icon_svg('link')],
            'instagram' => ['label' => 'Instagram', 'icon' => self::get_public_social_icon_svg('instagram')],
            'facebook' => ['label' => 'Facebook', 'icon' => self::get_public_social_icon_svg('facebook')],
            'tiktok' => ['label' => 'Tiktok', 'icon' => self::get_public_social_icon_svg('tiktok')],
            'youtube' => ['label' => 'Youtube', 'icon' => self::get_public_social_icon_svg('youtube')],
            'website' => ['label' => 'Website', 'icon' => self::get_public_social_icon_svg('website')],
        ];
        $html = '';
        $items = [];

        foreach ($links as $field => $config) {
            $url = trim((string) ($social[$field] ?? ''));

            if ('' === $url) {
                continue;
            }

            $items[] = '<a class="wdb-detail-social__link" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr($config['label']) . '">' . $config['icon'] . '</a>';
        }

        if (empty($items)) {
            return '';
        }

        $html .= '<div class="wdb-detail-social">';
        $html .= '<p class="wdb-detail-social__label">Sosial Media</p>';
        $html .= '<div class="wdb-detail-social__list">' . implode('', $items) . '</div>';
        $html .= '</div>';

        return $html;
    }

    private static function render_public_pesantren_news(string $nama_pesantren): string
    {
        $posts = self::get_posts_by_exact_tag_name($nama_pesantren, 3);

        if (empty($posts)) {
            return '';
        }

        $html = '<section class="wdb-detail-card">';
        $html .= '<h2 class="wdb-detail-section-title">Berita Tentang Pesantren</h2>';
        $html .= '<div class="wdb-detail-news">';

        foreach ($posts as $post) {
            $html .= '<article class="wdb-detail-news__item">';
            $html .= '<h3 class="wdb-detail-news__title"><a href="' . esc_url(get_permalink($post)) . '">' . esc_html(get_the_title($post)) . '</a></h3>';
            $html .= '</article>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private static function render_public_alumni_pesantren_relation(array $record): string
    {
        global $wpdb;

        $user_id = (int) ($record['user_id'] ?? 0);

        if ($user_id <= 0) {
            return '';
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, nama_pesantren, jenjang_pendidikan, jenis_pondok, jumlah_santri_total, photo_andalan_id
                FROM {$wpdb->prefix}wdb_pesantren
                WHERE status = 'published' AND user_id = %d
                ORDER BY id DESC
                LIMIT 5",
                $user_id
            ),
            ARRAY_A
        ) ?: [];

        if (empty($rows)) {
            return '';
        }

        $html = '<section class="wdb-detail-card">';
        $html .= '<h2 class="wdb-detail-section-title">Relasi Pesantren</h2>';
        $html .= '<div class="wdb-detail-related-grid">';

        foreach ($rows as $row) {
            $html .= self::render_public_pesantren_card($row);
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private static function render_public_alumni_news(string $nama_alumni): string
    {
        $posts = self::get_posts_by_exact_tag_name($nama_alumni, 3);

        if (empty($posts)) {
            return '';
        }

        $html = '<section class="wdb-detail-card">';
        $html .= '<h2 class="wdb-detail-section-title">Berita Tentang Alumni</h2>';
        $html .= '<div class="wdb-detail-news">';

        foreach ($posts as $post) {
            $html .= '<article class="wdb-detail-news__item">';
            $html .= '<h3 class="wdb-detail-news__title"><a href="' . esc_url(get_permalink($post)) . '">' . esc_html(get_the_title($post)) . '</a></h3>';
            $html .= '</article>';
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private static function render_public_related_alumni(array $record, ?array $address): string
    {
        $rows = self::get_related_alumni_rows($record, $address, 5);

        if (empty($rows)) {
            return '';
        }

        $html = '<section class="wdb-detail-card">';
        $html .= '<h2 class="wdb-detail-section-title">Alumni Lain</h2>';
        $html .= '<div class="wdb-detail-related-grid">';

        foreach ($rows as $row) {
            $html .= self::render_public_related_alumni_card($row);
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private static function render_public_related_pesantren(array $record, ?array $address): string
    {
        $rows = self::get_related_pesantren_rows($record, $address, 5);

        if (empty($rows)) {
            return '';
        }

        $html = '<section class="wdb-detail-card">';
        $html .= '<h2 class="wdb-detail-section-title">Pesantren Lain</h2>';
        $html .= '<div class="wdb-detail-related-grid">';

        foreach ($rows as $row) {
            $html .= self::render_public_pesantren_card($row);
        }

        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private static function get_related_alumni_rows(array $record, ?array $address, int $limit): array
    {
        global $wpdb;

        $record_id = (int) ($record['id'] ?? 0);

        if ($record_id <= 0 || $limit <= 0) {
            return [];
        }

        $sources = [
            [
                'where' => 'alumni.alumni_tahun = %d',
                'value' => (int) ($record['alumni_tahun'] ?? 0),
                'type' => 'int',
            ],
            [
                'where' => 'addresses.kecamatan_code = %s',
                'value' => is_array($address) ? (string) ($address['kecamatan_code'] ?? '') : '',
                'type' => 'string',
            ],
            [
                'where' => 'addresses.kabupaten_code = %s',
                'value' => is_array($address) ? (string) ($address['kabupaten_code'] ?? '') : '',
                'type' => 'string',
            ],
            [
                'where' => 'addresses.provinsi_code = %s',
                'value' => is_array($address) ? (string) ($address['provinsi_code'] ?? '') : '',
                'type' => 'string',
            ],
            [
                'where' => 'alumni.jenis_kelamin = %s',
                'value' => (string) ($record['jenis_kelamin'] ?? ''),
                'type' => 'string',
            ],
        ];

        foreach ($sources as $source) {
            if (('int' === $source['type'] && (int) $source['value'] <= 0) || ('string' === $source['type'] && '' === trim((string) $source['value']))) {
                continue;
            }

            $sql = "SELECT alumni.id, alumni.nama_lengkap, alumni.alumni_tahun, alumni.pasphoto_id, addresses.kecamatan_name, addresses.kabupaten_name
                FROM {$wpdb->prefix}wdb_alumni AS alumni
                LEFT JOIN {$wpdb->prefix}wdb_addresses AS addresses ON addresses.id = alumni.address_id
                WHERE alumni.status = 'published' AND alumni.id <> %d AND " . $source['where'] . '
                ORDER BY alumni.id DESC
                LIMIT %d';

            $rows = 'int' === $source['type']
                ? $wpdb->get_results($wpdb->prepare($sql, $record_id, (int) $source['value'], $limit), ARRAY_A)
                : $wpdb->get_results($wpdb->prepare($sql, $record_id, (string) $source['value'], $limit), ARRAY_A);

            if (! empty($rows)) {
                return $rows;
            }
        }

        return [];
    }

    private static function get_related_pesantren_rows(array $record, ?array $address, int $limit): array
    {
        global $wpdb;

        $record_id = (int) ($record['id'] ?? 0);

        if ($record_id <= 0 || $limit <= 0) {
            return [];
        }

        $sources = [
            [
                'where' => 'pesantren.jenjang_pendidikan = %s',
                'value' => (string) ($record['jenjang_pendidikan'] ?? ''),
            ],
            [
                'where' => 'addresses.kecamatan_code = %s',
                'value' => is_array($address) ? (string) ($address['kecamatan_code'] ?? '') : '',
            ],
            [
                'where' => 'addresses.kabupaten_code = %s',
                'value' => is_array($address) ? (string) ($address['kabupaten_code'] ?? '') : '',
            ],
            [
                'where' => 'addresses.provinsi_code = %s',
                'value' => is_array($address) ? (string) ($address['provinsi_code'] ?? '') : '',
            ],
        ];

        foreach ($sources as $source) {
            if ('' === trim($source['value'])) {
                continue;
            }

            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT pesantren.id, pesantren.nama_pesantren, pesantren.jenjang_pendidikan, pesantren.jenis_pondok, pesantren.jumlah_santri_total, pesantren.photo_andalan_id
                    FROM {$wpdb->prefix}wdb_pesantren AS pesantren
                    LEFT JOIN {$wpdb->prefix}wdb_addresses AS addresses ON addresses.id = pesantren.address_id
                    WHERE pesantren.status = 'published' AND pesantren.id <> %d AND " . $source['where'] . '
                    ORDER BY pesantren.id DESC
                    LIMIT %d',
                    $record_id,
                    $source['value'],
                    $limit
                ),
                ARRAY_A
            ) ?: [];

            if (! empty($rows)) {
                return $rows;
            }
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, nama_pesantren, jenjang_pendidikan, jenis_pondok, jumlah_santri_total, photo_andalan_id
                FROM {$wpdb->prefix}wdb_pesantren
                WHERE status = 'published' AND id <> %d
                ORDER BY id DESC
                LIMIT %d",
                $record_id,
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    private static function get_posts_by_exact_tag_name(string $name, int $limit): array
    {
        $name = trim($name);

        if ('' === $name || $limit <= 0) {
            return [];
        }

        $term = get_term_by('name', $name, 'post_tag');

        if (! $term || is_wp_error($term)) {
            return [];
        }

        return get_posts(
            [
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'tag_id' => (int) $term->term_id,
                'orderby' => 'date',
                'order' => 'DESC',
            ]
        );
    }

    private static function build_whatsapp_url(string $phone, string $message): string
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        if ('' === (string) $normalized) {
            return '#';
        }

        return 'https://wa.me/' . rawurlencode((string) $normalized) . '?text=' . rawurlencode($message);
    }

    private static function get_public_social_icon_svg(string $type): string
    {
        $icons = [
            'link' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.59 13.41a1 1 0 010-1.41l3-3a3 3 0 114.24 4.24l-1.5 1.5a3 3 0 01-4.24 0 1 1 0 111.41-1.41 1 1 0 001.41 0l1.5-1.5a1 1 0 10-1.41-1.41l-3 3a1 1 0 01-1.41 0zm2.82-2.82a1 1 0 010 1.41l-3 3a3 3 0 11-4.24-4.24l1.5-1.5a3 3 0 014.24 0 1 1 0 11-1.41 1.41 1 1 0 00-1.41 0l-1.5 1.5a1 1 0 001.41 1.41l3-3a1 1 0 011.41 0z"/></svg>',
            'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 015 5v10a5 5 0 01-5 5H7a5 5 0 01-5-5V7a5 5 0 015-5zm0 2a3 3 0 00-3 3v10a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H7zm5 3.5A5.5 5.5 0 1112 18.5 5.5 5.5 0 0112 7.5zm0 2A3.5 3.5 0 1015.5 13 3.5 3.5 0 0012 9.5zm6-3.25a1.25 1.25 0 11-1.25 1.25A1.25 1.25 0 0118 6.25z"/></svg>',
            'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 22v-8h3l1-4h-4V7.5A1.5 1.5 0 0114.5 6H17V2.5A19.5 19.5 0 0014.11 2C11 2 9 3.89 9 7.36V10H6v4h3v8z"/></svg>',
            'tiktok' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3c.21 1.71 1.22 3.08 2.88 3.83A5.8 5.8 0 0019 7.3V10a8.2 8.2 0 01-3-.71V15a5 5 0 11-5-5c.34 0 .67.03 1 .1v2.79a2.5 2.5 0 10-1-.21V3z"/></svg>',
            'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23 12s0-3.17-.41-4.7a2.97 2.97 0 00-2.09-2.1C18.97 4.8 12 4.8 12 4.8s-6.97 0-8.5.4a2.97 2.97 0 00-2.09 2.1C1 8.83 1 12 1 12s0 3.17.41 4.7a2.97 2.97 0 002.09 2.1c1.53.4 8.5.4 8.5.4s6.97 0 8.5-.4a2.97 2.97 0 002.09-2.1C23 15.17 23 12 23 12zm-13 3.75v-7.5L16 12z"/></svg>',
            'website' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm6.92 9h-3.03a15.9 15.9 0 00-1.16-5.01A8.03 8.03 0 0118.92 11zM12 4c.93 0 2.37 2.28 2.86 7H9.14C9.63 6.28 11.07 4 12 4zM9.27 5.99A15.9 15.9 0 008.11 11H5.08a8.03 8.03 0 014.19-5.01zM5.08 13h3.03a15.9 15.9 0 001.16 5.01A8.03 8.03 0 015.08 13zM12 20c-.93 0-2.37-2.28-2.86-7h5.72C14.37 17.72 12.93 20 12 20zm2.73-1.99A15.9 15.9 0 0015.89 13h3.03a8.03 8.03 0 01-4.19 5.01z"/></svg>',
        ];

        return $icons[$type] ?? $icons['website'];
    }

    private static function render_public_related_sections(?array $address, ?array $contact, ?array $social): string
    {
        $html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:20px;">';
        $html .= self::render_public_section_card(
            'Alamat',
            [
                'Alamat Lengkap' => (string) ($address['alamat_lengkap'] ?? ''),
                'Provinsi' => (string) ($address['provinsi_name'] ?? ''),
                'Kabupaten' => (string) ($address['kabupaten_name'] ?? ''),
                'Kecamatan' => (string) ($address['kecamatan_name'] ?? ''),
                'Desa' => (string) ($address['desa_name'] ?? ''),
            ]
        );
        $html .= self::render_public_section_card(
            'Kontak',
            [
                'Email' => (string) ($contact['email'] ?? ''),
                'Nomor HP' => (string) ($contact['nomor_hp'] ?? ''),
                'Nomor WhatsApp' => (string) ($contact['nomor_whatsapp'] ?? ''),
            ]
        );
        $html .= self::render_public_section_card(
            'Sosial Media',
            [
                'URL Profil Utama' => (string) ($social['url_profil'] ?? ''),
                'Instagram' => (string) ($social['instagram'] ?? ''),
                'Facebook' => (string) ($social['facebook'] ?? ''),
                'Tiktok' => (string) ($social['tiktok'] ?? ''),
                'Youtube' => (string) ($social['youtube'] ?? ''),
                'Website' => (string) ($social['website'] ?? ''),
            ]
        );
        $html .= '</div>';

        return $html;
    }

    private static function render_public_section_card(string $title, array $items): string
    {
        $html = '<section style="padding:18px;border:1px solid #dbe4ee;border-radius:18px;background:#fff;">';
        $html .= '<h2 style="margin:0 0 14px;font-size:20px;line-height:1.2;">' . esc_html($title) . '</h2>';

        foreach ($items as $label => $value) {
            if ('' === trim((string) $value)) {
                continue;
            }

            $display_value = filter_var($value, FILTER_VALIDATE_URL) ? '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">' . esc_html($value) . '</a>' : esc_html($value);
            $html .= '<p style="margin:0 0 10px;"><strong>' . esc_html($label) . ':</strong> ' . $display_value . '</p>';
        }

        $html .= '</section>';

        return $html;
    }

    private static function format_public_address_summary(array $row): string
    {
        $parts = array_filter(
            [
                trim((string) ($row['desa_name'] ?? '')),
                trim((string) ($row['kecamatan_name'] ?? '')),
                trim((string) ($row['kabupaten_name'] ?? '')),
                trim((string) ($row['provinsi_name'] ?? '')),
            ],
            static function ($value): bool {
                return '' !== $value;
            }
        );

        return empty($parts) ? '-' : implode(', ', $parts);
    }

    private static function format_public_alumni_address_summary(array $row): string
    {
        $parts = array_filter(
            [
                trim((string) ($row['kecamatan_name'] ?? '')),
                trim((string) ($row['kabupaten_name'] ?? '')),
            ],
            static function ($value): bool {
                return '' !== $value;
            }
        );

        return empty($parts) ? '-' : implode(', ', $parts);
    }

    private static function render_public_stat_card(string $label, string $value, string $note = ''): string
    {
        $html = '<article class="wdb-stats-card">';
        $html .= '<p class="wdb-stats-card__label">' . esc_html($label) . '</p>';
        $html .= '<p class="wdb-stats-card__value">' . esc_html($value) . '</p>';

        if ('' !== trim($note)) {
            $html .= '<p class="wdb-stats-card__note">' . esc_html($note) . '</p>';
        }

        $html .= '</article>';

        return $html;
    }

    private static function render_public_stats_chart(string $title, array $rows, string $empty_message): string
    {
        $html = '<article class="wdb-stats-chart">';
        $html .= '<div class="wdb-stats-chart__head"><h3>' . esc_html($title) . '</h3></div>';

        if (empty($rows)) {
            $html .= '<p class="wdb-stats-chart__empty">' . esc_html($empty_message) . '</p>';
            $html .= '</article>';

            return $html;
        }

        $max_value = max(array_column($rows, 'value'));
        $total_value = array_sum(array_column($rows, 'value'));
        $html .= '<div class="wdb-stats-chart__rows">';

        foreach ($rows as $row) {
            $value = (int) ($row['value'] ?? 0);
            $width = $max_value > 0 ? max(8, (int) round(($value / $max_value) * 100)) : 0;
            $percentage = $total_value > 0 ? round(($value / $total_value) * 100) : 0;
            $html .= '<div class="wdb-stats-chart__row">';
            $html .= '<div class="wdb-stats-chart__meta"><span class="wdb-stats-chart__label">' . esc_html((string) ($row['label'] ?? '-')) . '</span><span class="wdb-stats-chart__value">' . esc_html(number_format_i18n($value)) . ' <small>(' . esc_html((string) $percentage) . '%)</small></span></div>';
            $html .= '<div class="wdb-stats-chart__track"><span class="wdb-stats-chart__bar" data-wdb-bar-width="' . esc_attr((string) $width) . '"></span></div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</article>';

        return $html;
    }

    private static function get_public_region_distribution_context(): array
    {
        $focus = get_option('wdb_focus_address', []);
        $focus = is_array($focus) ? $focus : [];
        $provinsi_code = trim((string) ($focus['provinsi_code'] ?? ''));
        $provinsi_name = trim((string) ($focus['provinsi_name'] ?? ''));
        $kabupaten_code = trim((string) ($focus['kabupaten_code'] ?? ''));
        $kabupaten_name = trim((string) ($focus['kabupaten_name'] ?? ''));

        if ('' !== $kabupaten_code) {
            return [
                'group_column' => 'kecamatan_name',
                'title_label' => 'Kecamatan',
                'empty_label' => 'Tanpa Kecamatan',
                'filter_column' => 'kabupaten_code',
                'filter_value' => $kabupaten_code,
                'description' => '' !== $kabupaten_name
                    ? 'Menunjukkan persebaran kecamatan dalam kabupaten ' . $kabupaten_name . '.'
                    : 'Menunjukkan persebaran kecamatan pada kabupaten focus organisasi.',
            ];
        }

        if ('' !== $provinsi_code) {
            return [
                'group_column' => 'kabupaten_name',
                'title_label' => 'Kabupaten',
                'empty_label' => 'Tanpa Kabupaten',
                'filter_column' => 'provinsi_code',
                'filter_value' => $provinsi_code,
                'description' => '' !== $provinsi_name
                    ? 'Menunjukkan persebaran kabupaten atau kota dalam provinsi ' . $provinsi_name . '.'
                    : 'Menunjukkan persebaran kabupaten atau kota pada provinsi focus organisasi.',
            ];
        }

        return [
            'group_column' => 'provinsi_name',
            'title_label' => 'Provinsi',
            'empty_label' => 'Tanpa Provinsi',
            'filter_column' => '',
            'filter_value' => '',
            'description' => 'Menunjukkan persebaran provinsi pada skala Indonesia.',
        ];
    }

    private static function get_public_region_distribution_rows(string $dataset, array $context): array
    {
        global $wpdb;

        $dataset = 'alumni' === $dataset ? 'alumni' : 'pesantren';
        $group_column = (string) ($context['group_column'] ?? 'provinsi_name');
        $title_label = (string) ($context['empty_label'] ?? 'Tanpa Wilayah');
        $filter_column = (string) ($context['filter_column'] ?? '');
        $filter_value = (string) ($context['filter_value'] ?? '');
        $allowed_group_columns = ['provinsi_name', 'kabupaten_name', 'kecamatan_name'];
        $allowed_filter_columns = ['provinsi_code', 'kabupaten_code'];

        if (! in_array($group_column, $allowed_group_columns, true)) {
            $group_column = 'provinsi_name';
        }

        if (! in_array($filter_column, $allowed_filter_columns, true)) {
            $filter_column = '';
            $filter_value = '';
        }

        $query = "SELECT COALESCE(NULLIF(addresses.{$group_column}, ''), %s) AS label, COUNT(*) AS total
            FROM {$wpdb->prefix}wdb_{$dataset} AS records
            LEFT JOIN {$wpdb->prefix}wdb_addresses AS addresses ON addresses.id = records.address_id
            WHERE records.status = 'published'";
        $params = [$title_label];

        if ('' !== $filter_column && '' !== $filter_value) {
            $query .= " AND addresses.{$filter_column} = %s";
            $params[] = $filter_value;
        }

        $query .= " GROUP BY COALESCE(NULLIF(addresses.{$group_column}, ''), %s)
            ORDER BY total DESC, label ASC
            LIMIT 8";
        $params[] = $title_label;

        return $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A) ?: [];
    }

    private static function prepare_public_stats_rows(array $rows, ?string $label_formatter = null): array
    {
        $items = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = (int) ($row['total'] ?? 0);

            if ('' === $label || $value <= 0) {
                continue;
            }

            if (null !== $label_formatter && method_exists(self::class, $label_formatter)) {
                $label = (string) self::{$label_formatter}($label);
            }

            $items[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $items;
    }

    private static function prepare_public_year_rows(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $year = (int) ($row['tahun'] ?? 0);
            $value = (int) ($row['total'] ?? 0);

            if ($year <= 0 || $value <= 0) {
                continue;
            }

            $items[] = [
                'label' => (string) $year,
                'value' => $value,
            ];
        }

        return $items;
    }

    private static function format_pesantren_level_label(string $value): string
    {
        $map = [
            'kmi' => 'KMI',
            'diknas' => 'DIKNAS',
            'kemenag' => 'KEMENAG',
            'lainnya' => 'Lainnya',
        ];

        return $map[strtolower($value)] ?? $value;
    }

    private static function format_pesantren_type_label(string $value): string
    {
        $map = [
            'wakaf' => 'Wakaf',
            'keluarga' => 'Keluarga',
        ];

        return $map[strtolower($value)] ?? $value;
    }

    private static function format_gender_label(string $value): string
    {
        $map = [
            'laki-laki' => 'Laki-laki',
            'perempuan' => 'Perempuan',
        ];

        return $map[strtolower($value)] ?? $value;
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

    private static function ensure_name_tag_exists(string $name): void
    {
        $name = trim($name);

        if ('' === $name) {
            return;
        }

        if (term_exists($name, 'post_tag')) {
            return;
        }

        wp_insert_term($name, 'post_tag');
    }

    private static function get_message_html(): string
    {
        if (! isset($_GET['wdb_message'])) {
            return '';
        }

        $message = sanitize_key(wp_unslash($_GET['wdb_message']));

        if ('created' === $message) {
            return '<p>Data berhasil disimpan.</p>';
        }

        if ('updated' === $message) {
            return '<p>Data berhasil diperbarui.</p>';
        }

        if ('account_error' === $message) {
            return '<p>Akun alumni tidak dapat dibuat. Jika ingin login, isi email kontak yang valid dan password yang belum dipakai.</p>';
        }

        if ('update_error' === $message) {
            return '<p>Data tidak dapat diperbarui.</p>';
        }

        return '';
    }

    private static function redirect_with_message(string $message): void
    {
        $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : self::get_current_url();

        wp_safe_redirect(add_query_arg(['wdb_message' => $message], $redirect_to));
        exit;
    }

    private static function redirect_to_thank_you(): void
    {
        wp_safe_redirect(home_url('/terima-kasih/'));
        exit;
    }

    private static function get_current_url(): string
    {
        global $wp;

        return home_url(add_query_arg([], $wp->request));
    }

    private static function nullable_text(string $field): ?string
    {
        if (! isset($_POST[$field])) {
            return null;
        }

        $value = sanitize_text_field(wp_unslash($_POST[$field]));

        return '' === $value ? null : $value;
    }

    private static function nullable_email(string $field): ?string
    {
        if (! isset($_POST[$field])) {
            return null;
        }

        $value = sanitize_email(wp_unslash($_POST[$field]));

        return '' === $value ? null : $value;
    }

    private static function nullable_url(string $field): ?string
    {
        if (! isset($_POST[$field])) {
            return null;
        }

        $value = esc_url_raw(wp_unslash($_POST[$field]));

        return '' === $value ? null : $value;
    }

    private static function nullable_int(string $field): ?int
    {
        if (! isset($_POST[$field])) {
            return null;
        }

        $value = absint(wp_unslash($_POST[$field]));

        return 0 === $value ? null : $value;
    }

    private static function positive_int(string $field): int
    {
        if (! isset($_POST[$field])) {
            return 0;
        }

        return max(0, absint(wp_unslash($_POST[$field])));
    }
}
