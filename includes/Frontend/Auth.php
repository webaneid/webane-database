<?php

namespace WDB\Frontend;

if (! defined('ABSPATH')) {
    exit;
}

final class Auth
{
    public static function boot(): void
    {
        add_shortcode('wdb_login_form', [self::class, 'render_login_form']);
        add_action('admin_init', [self::class, 'block_alumni_admin_access']);
        add_filter('show_admin_bar', [self::class, 'hide_admin_bar_for_alumni']);
        add_filter('ajax_query_attachments_args', [self::class, 'limit_attachment_queries']);
        add_filter('map_meta_cap', [self::class, 'restrict_attachment_caps'], 10, 4);
        add_filter('upload_mimes', [self::class, 'limit_alumni_upload_mimes']);
    }

    public static function render_login_form(): string
    {
        if (is_user_logged_in()) {
            $logout_url = wp_logout_url(self::get_current_url());

            return '<div class="wdb-auth-shell"><div class="wdb-auth-card"><div class="wdb-auth-card__head"><p class="wdb-auth-card__eyebrow">Dashboard Alumni</p><h1 class="wdb-auth-card__title">Anda sudah login</h1><p class="wdb-auth-card__text">Lanjutkan ke dashboard alumni atau keluar dari akun ini.</p></div><div class="wdb-auth-actions"><a class="wdb-button" href="' . esc_url(home_url('/dashboard-alumni/')) . '">Buka Dashboard</a><a class="wdb-button wdb-button--secondary" href="' . esc_url($logout_url) . '">Logout</a></div></div></div>';
        }

        $form = wp_login_form(
            [
                'echo' => false,
                'redirect' => self::get_current_url(),
                'label_log_in' => 'Masuk Dashboard',
            ]
        );

        $html = '<div class="wdb-auth-shell">';
        $html .= '<div class="wdb-auth-card">';
        $html .= '<div class="wdb-auth-card__head">';
        $html .= '<p class="wdb-auth-card__eyebrow">Login Alumni</p>';
        $html .= '<h1 class="wdb-auth-card__title">Masuk ke dashboard alumni</h1>';
        $html .= '<p class="wdb-auth-card__text">Login untuk mengelola data alumni, pesantren, dan media milik Anda sendiri.</p>';
        $html .= '</div>';
        $html .= $form;
        $html .= '<div class="wdb-auth-actions">';
        $html .= '<a class="wdb-button wdb-button--secondary" href="' . esc_url(home_url('/form-alumni/')) . '">Isi Formulir</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private static function get_current_url(): string
    {
        global $wp;

        return home_url(add_query_arg([], $wp->request));
    }

    public static function block_alumni_admin_access(): void
    {
        global $pagenow;

        if (! is_user_logged_in() || ! is_admin() || wp_doing_ajax() || 'admin-post.php' === $pagenow) {
            return;
        }

        $user = wp_get_current_user();

        if (! in_array('alumni', (array) $user->roles, true)) {
            return;
        }

        $redirect = wp_get_referer();

        if (! $redirect) {
            $redirect = home_url('/');
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public static function hide_admin_bar_for_alumni(bool $show): bool
    {
        if (! is_user_logged_in()) {
            return $show;
        }

        $user = wp_get_current_user();

        if (in_array('alumni', (array) $user->roles, true)) {
            return false;
        }

        return $show;
    }

    public static function limit_attachment_queries(array $args): array
    {
        if (! is_user_logged_in()) {
            return $args;
        }

        $user = wp_get_current_user();

        if (! in_array('alumni', (array) $user->roles, true)) {
            return $args;
        }

        $args['author'] = get_current_user_id();

        return $args;
    }

    public static function restrict_attachment_caps(array $caps, string $cap, int $user_id, array $args): array
    {
        if (! in_array($cap, ['edit_post', 'delete_post', 'read_post'], true) || empty($args[0])) {
            return $caps;
        }

        $user = get_userdata($user_id);

        if (! $user || ! in_array('alumni', (array) $user->roles, true)) {
            return $caps;
        }

        $post = get_post((int) $args[0]);

        if (! $post || 'attachment' !== $post->post_type) {
            return $caps;
        }

        if ((int) $post->post_author !== $user_id) {
            return ['do_not_allow'];
        }

        return $caps;
    }

    public static function limit_alumni_upload_mimes(array $mimes): array
    {
        if (! is_user_logged_in()) {
            return $mimes;
        }

        $user = wp_get_current_user();

        if (! in_array('alumni', (array) $user->roles, true)) {
            return $mimes;
        }

        return [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
    }
}
