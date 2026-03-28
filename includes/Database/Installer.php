<?php

namespace WDB\Database;

if (! defined('ABSPATH')) {
    exit;
}

final class Installer
{
    public static function activate(): void
    {
        self::create_tables();
        self::register_roles();
        \WDB\Frontend\Forms::register_public_routes();
        flush_rewrite_rules();
        self::set_version();
    }

    public static function maybe_upgrade(): void
    {
        if (self::contact_public_whatsapp_column_exists() && self::contact_public_phone_column_exists() && self::jobs_table_exists() && self::alumni_job_column_exists()) {
            return;
        }

        self::create_tables();
        self::set_version();
    }

    private static function create_tables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'wdb_';

        $sql = [];

        $sql[] = "CREATE TABLE {$prefix}addresses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            alamat_lengkap TEXT NOT NULL,
            provinsi_code VARCHAR(32) NOT NULL,
            provinsi_name VARCHAR(191) NOT NULL,
            kabupaten_code VARCHAR(32) NOT NULL,
            kabupaten_name VARCHAR(191) NOT NULL,
            kecamatan_code VARCHAR(32) NOT NULL,
            kecamatan_name VARCHAR(191) NOT NULL,
            desa_code VARCHAR(32) NOT NULL,
            desa_name VARCHAR(191) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provinsi_code (provinsi_code),
            KEY kabupaten_code (kabupaten_code),
            KEY kecamatan_code (kecamatan_code),
            KEY desa_code (desa_code)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$prefix}contacts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(191) DEFAULT NULL,
            nomor_hp VARCHAR(50) DEFAULT NULL,
            nomor_hp_tampil_publik TINYINT(1) NOT NULL DEFAULT 0,
            nomor_whatsapp VARCHAR(50) DEFAULT NULL,
            whatsapp_sama_dengan_hp TINYINT(1) NOT NULL DEFAULT 0,
            whatsapp_tampil_publik TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email),
            KEY nomor_hp (nomor_hp)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$prefix}socials (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            url_profil VARCHAR(255) DEFAULT NULL,
            instagram VARCHAR(255) DEFAULT NULL,
            facebook VARCHAR(255) DEFAULT NULL,
            tiktok VARCHAR(255) DEFAULT NULL,
            youtube VARCHAR(255) DEFAULT NULL,
            website VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$prefix}jobs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            pekerjaan VARCHAR(191) NOT NULL,
            nama_lembaga VARCHAR(191) DEFAULT NULL,
            jabatan VARCHAR(191) DEFAULT NULL,
            address_id BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY address_id (address_id),
            KEY pekerjaan (pekerjaan)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$prefix}alumni (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            nama_lengkap VARCHAR(191) NOT NULL,
            tempat_lahir VARCHAR(191) NOT NULL,
            tanggal_lahir DATE DEFAULT NULL,
            alumni_tahun SMALLINT UNSIGNED NOT NULL,
            jenis_kelamin VARCHAR(20) NOT NULL,
            job_id BIGINT UNSIGNED DEFAULT NULL,
            address_id BIGINT UNSIGNED DEFAULT NULL,
            contact_id BIGINT UNSIGNED DEFAULT NULL,
            social_id BIGINT UNSIGNED DEFAULT NULL,
            pasphoto_id BIGINT UNSIGNED DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY job_id (job_id),
            KEY address_id (address_id),
            KEY contact_id (contact_id),
            KEY social_id (social_id),
            KEY status (status)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$prefix}pesantren (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            nama_pesantren VARCHAR(191) NOT NULL,
            berdiri_sejak DATE DEFAULT NULL,
            luas_area VARCHAR(191) DEFAULT NULL,
            nama_pimpinan VARCHAR(191) DEFAULT NULL,
            nomor_hp_pimpinan VARCHAR(50) DEFAULT NULL,
            jenjang_pendidikan VARCHAR(20) NOT NULL,
            jenis_pondok VARCHAR(20) NOT NULL,
            photo_andalan_id BIGINT UNSIGNED DEFAULT NULL,
            jumlah_santri_total INT UNSIGNED NOT NULL DEFAULT 0,
            santri_putra INT UNSIGNED NOT NULL DEFAULT 0,
            santri_putri INT UNSIGNED NOT NULL DEFAULT 0,
            jumlah_guru_total INT UNSIGNED NOT NULL DEFAULT 0,
            asatidz INT UNSIGNED NOT NULL DEFAULT 0,
            asatidzah INT UNSIGNED NOT NULL DEFAULT 0,
            address_id BIGINT UNSIGNED DEFAULT NULL,
            contact_id BIGINT UNSIGNED DEFAULT NULL,
            social_id BIGINT UNSIGNED DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY address_id (address_id),
            KEY contact_id (contact_id),
            KEY social_id (social_id),
            KEY jenjang_pendidikan (jenjang_pendidikan),
            KEY jenis_pondok (jenis_pondok),
            KEY status (status)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$prefix}regions_provinces (
            id VARCHAR(32) NOT NULL,
            name VARCHAR(191) NOT NULL,
            PRIMARY KEY  (id),
            KEY name (name)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$prefix}regions_regencies (
            id VARCHAR(32) NOT NULL,
            province_id VARCHAR(32) NOT NULL,
            name VARCHAR(191) NOT NULL,
            PRIMARY KEY  (id),
            KEY province_id (province_id),
            KEY name (name)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$prefix}regions_districts (
            id VARCHAR(32) NOT NULL,
            regency_id VARCHAR(32) NOT NULL,
            name VARCHAR(191) NOT NULL,
            PRIMARY KEY  (id),
            KEY regency_id (regency_id),
            KEY name (name)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE {$prefix}regions_villages (
            id VARCHAR(32) NOT NULL,
            district_id VARCHAR(32) NOT NULL,
            name VARCHAR(191) NOT NULL,
            PRIMARY KEY  (id),
            KEY district_id (district_id),
            KEY name (name)
        ) {$charset_collate};";

        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    private static function set_version(): void
    {
        update_option('wdb_plugin_version', WDB_PLUGIN_VERSION);
    }

    private static function contact_public_whatsapp_column_exists(): bool
    {
        global $wpdb;

        $column = $wpdb->get_var(
            "SHOW COLUMNS FROM {$wpdb->prefix}wdb_contacts LIKE 'whatsapp_tampil_publik'"
        );

        return is_string($column) && '' !== $column;
    }

    private static function contact_public_phone_column_exists(): bool
    {
        global $wpdb;

        $column = $wpdb->get_var(
            "SHOW COLUMNS FROM {$wpdb->prefix}wdb_contacts LIKE 'nomor_hp_tampil_publik'"
        );

        return is_string($column) && '' !== $column;
    }

    private static function jobs_table_exists(): bool
    {
        global $wpdb;

        $table = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->prefix . 'wdb_jobs')
        );

        return is_string($table) && '' !== $table;
    }

    private static function alumni_job_column_exists(): bool
    {
        global $wpdb;

        $column = $wpdb->get_var(
            "SHOW COLUMNS FROM {$wpdb->prefix}wdb_alumni LIKE 'job_id'"
        );

        return is_string($column) && '' !== $column;
    }

    public static function register_roles(): void
    {
        $subscriber = get_role('subscriber');
        $capabilities = $subscriber ? $subscriber->capabilities : ['read' => true];

        $capabilities['upload_files'] = true;

        add_role('alumni', 'Alumni', $capabilities);

        $role = get_role('alumni');

        if ($role && ! $role->has_cap('upload_files')) {
            $role->add_cap('upload_files');
        }
    }
}
