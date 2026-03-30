<?php

namespace WDB\Admin;

if (! defined('ABSPATH')) {
    exit;
}

final class CrudPage
{
    public static function get_pages(): array
    {
        return [
            'wdb-pesantren' => [
                'title' => 'Pesantren',
                'menu_title' => 'Pesantren',
                'table' => 'pesantren',
                'singular' => 'pesantren',
                'fields' => [
                    'nama_pesantren' => ['label' => 'Nama Pesantren', 'type' => 'text', 'required' => true, 'placeholder' => 'Makhad Ane'],
                    'berdiri_sejak' => ['label' => 'Berdiri Sejak', 'type' => 'date', 'placeholder' => 'hh / bb / tttt'],
                    'luas_area' => ['label' => 'Luas Area', 'type' => 'text', 'placeholder' => '2 hektar'],
                    'nama_pimpinan' => ['label' => 'Nama Pimpinan', 'type' => 'text', 'placeholder' => 'KH. Ahmad'],
                    'nomor_hp_pimpinan' => ['label' => 'Nomor HP Pimpinan', 'type' => 'text', 'placeholder' => '08xxxxxxxxxx'],
                    'jenjang_pendidikan' => [
                        'label' => 'Jenjang Pendidikan',
                        'type' => 'select',
                        'autocomplete' => true,
                        'autocomplete_placeholder' => 'Cari jenjang pendidikan',
                        'required' => true,
                        'options' => [
                            'kmi' => 'KMI',
                            'diknas' => 'DIKNAS',
                            'kemenag' => 'KEMENAG',
                            'lainnya' => 'Lainnya',
                        ],
                    ],
                    'jenis_pondok' => [
                        'label' => 'Jenis Pondok',
                        'type' => 'select',
                        'autocomplete' => true,
                        'autocomplete_placeholder' => 'Cari jenis pondok',
                        'required' => true,
                        'options' => [
                            'wakaf' => 'Wakaf',
                            'keluarga' => 'Keluarga',
                        ],
                    ],
                    'photo_andalan_id' => ['label' => 'Photo Andalan', 'type' => 'media_image'],
                    'santri_putra' => ['label' => 'Santri Putra', 'type' => 'number'],
                    'santri_putri' => ['label' => 'Santri Putri', 'type' => 'number'],
                    'jumlah_santri_total' => ['label' => 'Jumlah Santri Total', 'type' => 'readonly_number'],
                    'asatidz' => ['label' => 'Asatidz', 'type' => 'number'],
                    'asatidzah' => ['label' => 'Asatidzah', 'type' => 'number'],
                    'jumlah_guru_total' => ['label' => 'Jumlah Guru Total', 'type' => 'readonly_number'],
                    'address_id' => ['label' => 'Alamat', 'type' => 'embedded_address'],
                    'contact_id' => ['label' => 'Kontak', 'type' => 'embedded_contact'],
                    'social_id' => ['label' => 'Sosial Media', 'type' => 'embedded_social'],
                    'status' => [
                        'label' => 'Status',
                        'type' => 'select',
                        'autocomplete' => true,
                        'autocomplete_placeholder' => 'Cari status data',
                        'options' => [
                            'draft' => 'Draft',
                            'pending' => 'Pending',
                            'published' => 'Published',
                        ],
                    ],
                ],
                'list_columns' => ['id', 'nama_pesantren', 'jenjang_pendidikan', 'jenis_pondok', 'status'],
            ],
            'wdb-alumni' => [
                'title' => 'Alumni',
                'menu_title' => 'Alumni',
                'table' => 'alumni',
                'singular' => 'alumni',
                'fields' => [
                    'nama_lengkap' => ['label' => 'Nama Lengkap', 'type' => 'text', 'required' => true, 'placeholder' => 'Ahmad Fauzi'],
                    'tempat_lahir' => ['label' => 'Tempat Lahir', 'type' => 'birthplace_regency', 'required' => true],
                    'tanggal_lahir' => ['label' => 'Tanggal Lahir', 'type' => 'date', 'placeholder' => 'hh / bb / tttt'],
                    'alumni_tahun' => ['label' => 'Alumni Tahun', 'type' => 'number', 'required' => true, 'placeholder' => '2015'],
                    'jenis_kelamin' => [
                        'label' => 'Jenis Kelamin',
                        'type' => 'select',
                        'autocomplete' => true,
                        'autocomplete_placeholder' => 'Cari jenis kelamin',
                        'required' => true,
                        'options' => [
                            'laki-laki' => 'Laki-laki',
                            'perempuan' => 'Perempuan',
                        ],
                    ],
                    'address_id' => ['label' => 'Alamat', 'type' => 'embedded_address'],
                    'contact_id' => ['label' => 'Kontak', 'type' => 'embedded_contact'],
                    'job_id' => ['label' => 'Pekerjaan', 'type' => 'embedded_job'],
                    'social_id' => ['label' => 'Sosial Media', 'type' => 'embedded_social'],
                    'pasphoto_id' => ['label' => 'Pasphoto', 'type' => 'media_image'],
                    'status' => [
                        'label' => 'Status',
                        'type' => 'select',
                        'autocomplete' => true,
                        'autocomplete_placeholder' => 'Cari status data',
                        'options' => [
                            'draft' => 'Draft',
                            'pending' => 'Pending',
                            'published' => 'Published',
                        ],
                    ],
                ],
                'list_columns' => ['id', 'nama_lengkap', 'alumni_tahun', 'jenis_kelamin', 'status'],
            ],
            'wdb-addresses' => [
                'title' => 'Alamat',
                'menu_title' => 'Alamat',
                'table' => 'addresses',
                'singular' => 'alamat',
                'fields' => [
                    'alamat_lengkap' => ['label' => 'Alamat Lengkap', 'type' => 'text', 'required' => true],
                    'wilayah' => ['label' => 'Wilayah', 'type' => 'region_address', 'required' => true],
                ],
                'list_columns' => ['id', 'alamat_lengkap', 'provinsi_name', 'kabupaten_name', 'kecamatan_name', 'desa_name'],
            ],
            'wdb-contacts' => [
                'title' => 'Kontak',
                'menu_title' => 'Kontak',
                'table' => 'contacts',
                'singular' => 'kontak',
                'fields' => [
                    'email' => ['label' => 'Email', 'type' => 'email'],
                    'nomor_hp' => ['label' => 'Nomor HP', 'type' => 'text'],
                    'nomor_hp_tampil_publik' => ['label' => 'Tampilkan Nomor HP di Publik', 'type' => 'checkbox'],
                    'nomor_whatsapp' => ['label' => 'Nomor WhatsApp', 'type' => 'text'],
                    'whatsapp_sama_dengan_hp' => ['label' => 'WhatsApp Sama Dengan HP', 'type' => 'checkbox'],
                    'whatsapp_tampil_publik' => ['label' => 'Tampilkan WhatsApp di Publik', 'type' => 'checkbox'],
                ],
                'list_columns' => ['id', 'email', 'nomor_hp', 'nomor_hp_tampil_publik', 'nomor_whatsapp', 'whatsapp_sama_dengan_hp', 'whatsapp_tampil_publik'],
            ],
            'wdb-socials' => [
                'title' => 'Sosial Media',
                'menu_title' => 'Sosial Media',
                'table' => 'socials',
                'singular' => 'sosial media',
                'fields' => [
                    'url_profil' => ['label' => 'URL Profil', 'type' => 'url'],
                    'instagram' => ['label' => 'Instagram', 'type' => 'url'],
                    'facebook' => ['label' => 'Facebook', 'type' => 'url'],
                    'tiktok' => ['label' => 'Tiktok', 'type' => 'url'],
                    'youtube' => ['label' => 'Youtube', 'type' => 'url'],
                    'website' => ['label' => 'Website', 'type' => 'url'],
                ],
                'list_columns' => ['id', 'url_profil', 'instagram', 'facebook', 'website'],
            ],
        ];
    }

    public static function handle_request(): void
    {
        if (! is_admin() || ! current_user_can(Menu::capability())) {
            return;
        }

        $page = isset($_REQUEST['page']) ? sanitize_key(wp_unslash($_REQUEST['page'])) : '';
        $pages = self::get_pages();

        if (! isset($pages[$page])) {
            return;
        }

        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['wdb_action']) && 'save' === sanitize_key(wp_unslash($_POST['wdb_action']))) {
            check_admin_referer('wdb_save_' . $page);
            self::save_record($page, $pages[$page]);
        }

        if (isset($_GET['action'], $_GET['id']) && 'delete' === sanitize_key(wp_unslash($_GET['action']))) {
            check_admin_referer('wdb_delete_' . $page . '_' . absint($_GET['id']));
            self::delete_record($page, $pages[$page], absint($_GET['id']));
        }
    }

    public static function render(string $page): void
    {
        $pages = self::get_pages();

        if (! isset($pages[$page])) {
            return;
        }

        $config = $pages[$page];
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : 'list';
        $record_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $record = $record_id > 0 ? self::get_record($config, $record_id) : null;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($config['title']) . '</h1>';

        self::render_notice();

        if ('add' === $action || ('edit' === $action && $record)) {
            self::render_form($page, $config, $record);
        } else {
            self::render_list($page, $config);
        }

        echo '</div>';
    }

    private static function render_notice(): void
    {
        if (! isset($_GET['message'])) {
            return;
        }

        $message = sanitize_key(wp_unslash($_GET['message']));
        $messages = [
            'created' => 'Data berhasil ditambahkan.',
            'updated' => 'Data berhasil diperbarui.',
            'deleted' => 'Data berhasil dihapus.',
        ];

        if (! isset($messages[$message])) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$message]) . '</p></div>';
    }

    private static function render_list(string $page, array $config): void
    {
        $add_url = add_query_arg(
            [
                'page' => $page,
                'action' => 'add',
            ],
            admin_url('admin.php')
        );

        echo '<p><a href="' . esc_url($add_url) . '" class="page-title-action">Tambah Baru</a></p>';

        $records = self::get_records($config);
        $columns = $config['list_columns'];

        echo '<table class="widefat striped">';
        echo '<thead><tr>';

        foreach ($columns as $column) {
            echo '<th>' . esc_html(self::get_column_label($config, $column)) . '</th>';
        }

        echo '<th>Aksi</th>';
        echo '</tr></thead><tbody>';

        if (empty($records)) {
            echo '<tr><td colspan="' . esc_attr((string) (count($columns) + 1)) . '">Belum ada data.</td></tr>';
        } else {
            foreach ($records as $record) {
                echo '<tr>';

                foreach ($columns as $column) {
                    echo '<td>' . esc_html(self::format_list_value($record, $column)) . '</td>';
                }

                $edit_url = add_query_arg(
                    [
                        'page' => $page,
                        'action' => 'edit',
                        'id' => (int) $record['id'],
                    ],
                    admin_url('admin.php')
                );

                $delete_url = wp_nonce_url(
                    add_query_arg(
                        [
                            'page' => $page,
                            'action' => 'delete',
                            'id' => (int) $record['id'],
                        ],
                        admin_url('admin.php')
                    ),
                    'wdb_delete_' . $page . '_' . (int) $record['id']
                );

                echo '<td>';
                echo '<a href="' . esc_url($edit_url) . '">Edit</a> | ';
                echo '<a href="' . esc_url($delete_url) . '" onclick="return confirm(\'Hapus data ini?\');">Hapus</a>';
                echo '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }

    private static function render_form(string $page, array $config, ?array $record = null): void
    {
        $is_edit = is_array($record);
        $action_url = add_query_arg(['page' => $page], admin_url('admin.php'));

        echo '<form method="post" action="' . esc_url($action_url) . '">';
        wp_nonce_field('wdb_save_' . $page);
        echo '<input type="hidden" name="wdb_action" value="save">';

        if ($is_edit) {
            echo '<input type="hidden" name="id" value="' . esc_attr((string) $record['id']) . '">';
        }

        if ('wdb-pesantren' === $page) {
            self::render_pesantren_form_sections($config, $record);
            submit_button($is_edit ? 'Update' : 'Simpan');
            echo '</form>';

            return;
        }

        if ('wdb-alumni' === $page) {
            self::render_alumni_form_sections($config, $record);
            submit_button($is_edit ? 'Update' : 'Simpan');
            echo '</form>';

            return;
        }

        echo '<table class="form-table" role="presentation"><tbody>';

        foreach ($config['fields'] as $field => $field_config) {
            $value = ('region_address' === $field_config['type'])
                ? (is_array($record) ? $record : [])
                : ($record[$field] ?? '');

            echo '<tr>';
            echo '<th scope="row"><label for="' . esc_attr($field) . '">' . esc_html($field_config['label']) . '</label></th>';
            echo '<td>' . self::render_field($field, $field_config, $value) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        submit_button($is_edit ? 'Update' : 'Simpan');
        echo '</form>';
    }

    private static function render_pesantren_form_sections(array $config, ?array $record): void
    {
        $primary_fields = [
            'nama_pesantren',
            'berdiri_sejak',
            'luas_area',
            'nama_pimpinan',
            'nomor_hp_pimpinan',
            'jenjang_pendidikan',
            'jenis_pondok',
        ];
        $photo_fields = [
            'photo_andalan_id',
        ];
        $santri_fields = [
            'santri_putra',
            'santri_putri',
            'jumlah_santri_total',
        ];
        $guru_fields = [
            'asatidz',
            'asatidzah',
            'jumlah_guru_total',
        ];
        $alamat_fields = [
            'address_id',
        ];
        $kontak_fields = [
            'contact_id',
        ];
        $pekerjaan_fields = [
            'job_id',
        ];
        $sosial_fields = [
            'social_id',
        ];
        $status_fields = [
            'status',
        ];

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 1: Data Primary Pesantren</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $primary_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 2: Photo Andalan Pesantren</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $photo_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 3: Data Pondok Pesantren</h2>';
        echo '<div class="wdb-admin-subsection">';
        echo '<h3 class="wdb-admin-subsection__title">1. Jumlah Santri</h3>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $santri_fields);
        echo '</tbody></table>';
        echo '</div>';
        echo '<div class="wdb-admin-subsection">';
        echo '<h3 class="wdb-admin-subsection__title">2. Guru</h3>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $guru_fields);
        echo '</tbody></table>';
        echo '</div>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 4: Alamat Pesantren</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $alamat_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 5: Kontak Pesantren</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $kontak_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 6: Sosial Media Pesantren</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $sosial_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 7: Status Pesantren</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $status_fields);
        echo '</tbody></table>';
        echo '</div>';
    }

    private static function render_alumni_form_sections(array $config, ?array $record): void
    {
        $primary_fields = [
            'nama_lengkap',
            'tempat_lahir',
            'tanggal_lahir',
            'alumni_tahun',
            'jenis_kelamin',
        ];
        $pasphoto_fields = [
            'pasphoto_id',
        ];
        $alamat_fields = [
            'address_id',
        ];
        $kontak_fields = [
            'contact_id',
        ];
        $pekerjaan_fields = [
            'job_id',
        ];
        $sosial_fields = [
            'social_id',
        ];
        $status_fields = [
            'status',
        ];

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 1: Data Primary Alumni</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $primary_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 2: Pasphoto Alumni</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $pasphoto_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 3: Alamat Alumni</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $alamat_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 4: Kontak Alumni</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $kontak_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 5: Pekerjaan Alumni</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $pekerjaan_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 6: Sosial Media Alumni</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $sosial_fields);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="wdb-admin-section">';
        echo '<h2 class="wdb-admin-section__title">Fase 7: Status Alumni</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        self::render_form_rows($config, $record, $status_fields);
        echo '</tbody></table>';
        echo '</div>';
    }

    private static function render_form_rows(array $config, ?array $record, array $fields): void
    {
        foreach ($fields as $field) {
            if (! isset($config['fields'][$field])) {
                continue;
            }

            $field_config = $config['fields'][$field];
            $value = ('region_address' === $field_config['type'])
                ? (is_array($record) ? $record : [])
                : ($record[$field] ?? '');

            echo '<tr>';
            echo '<th scope="row"><label for="' . esc_attr($field) . '">' . esc_html($field_config['label']) . '</label></th>';
            echo '<td>' . self::render_field($field, $field_config, $value) . '</td>';
            echo '</tr>';
        }
    }

    private static function render_field(string $field, array $config, $value): string
    {
        $required = ! empty($config['required']) ? 'required' : '';
        $name = esc_attr($field);
        $id = esc_attr($field);
        $placeholder = isset($config['placeholder']) ? ' placeholder="' . esc_attr((string) $config['placeholder']) . '"' : '';

        if ('region_address' === $config['type']) {
            return self::render_region_address_field(is_array($value) ? $value : []);
        }

        if ('embedded_address' === $config['type']) {
            return self::render_embedded_address_field((int) $value);
        }

        if ('embedded_contact' === $config['type']) {
            return self::render_embedded_contact_field((int) $value);
        }

        if ('embedded_job' === $config['type']) {
            return self::render_embedded_job_field((int) $value);
        }

        if ('embedded_social' === $config['type']) {
            return self::render_embedded_social_field((int) $value);
        }

        if ('birthplace_regency' === $config['type']) {
            return self::render_birthplace_regency_field((string) $value, ! empty($config['required']));
        }

        if ('textarea' === $config['type']) {
            return '<textarea class="large-text" rows="4" name="' . $name . '" id="' . $id . '" ' . $required . '>' . esc_textarea((string) $value) . '</textarea>';
        }

        if ('select' === $config['type']) {
            $html = '<select name="' . $name . '" id="' . $id . '" class="regular-text" ' . self::get_select_attributes($config) . ' ' . $required . '>';
            $html .= '<option value=""></option>';

            foreach ($config['options'] as $option_value => $option_label) {
                $html .= '<option value="' . esc_attr((string) $option_value) . '" ' . selected((string) $value, (string) $option_value, false) . '>' . esc_html($option_label) . '</option>';
            }

            $html .= '</select>';

            return $html;
        }

        if ('relation' === $config['type']) {
            $options = self::get_relation_options($config['relation']);
            $html = '<select name="' . $name . '" id="' . $id . '" class="regular-text">';
            $html .= '<option value=""></option>';

            foreach ($options as $option_value => $option_label) {
                $html .= '<option value="' . esc_attr((string) $option_value) . '" ' . selected((string) $value, (string) $option_value, false) . '>' . esc_html($option_label) . '</option>';
            }

            $html .= '</select>';

            return $html;
        }

        if ('checkbox' === $config['type']) {
            return '<label><input type="checkbox" name="' . $name . '" id="' . $id . '" value="1" ' . checked((string) $value, '1', false) . '> Ya</label>';
        }

        if ('media_image' === $config['type']) {
            return self::render_media_image_field($field, (string) $value);
        }

        if ('readonly_number' === $config['type']) {
            return '<input type="number" class="regular-text" name="' . $name . '" id="' . $id . '" value="' . esc_attr((string) $value) . '" readonly>';
        }

        $type = in_array($config['type'], ['text', 'number', 'email', 'url', 'date'], true) ? $config['type'] : 'text';

        return '<input type="' . esc_attr($type) . '" class="regular-text" name="' . $name . '" id="' . $id . '" value="' . esc_attr((string) $value) . '"' . $placeholder . ' ' . $required . '>';
    }

    private static function get_select_attributes(array $config): string
    {
        if (empty($config['autocomplete'])) {
            return '';
        }

        $placeholder = isset($config['autocomplete_placeholder']) ? (string) $config['autocomplete_placeholder'] : 'Cari data';

        return 'data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="' . esc_attr($placeholder) . '"';
    }

    private static function render_media_image_field(string $field, string $value): string
    {
        $size = 'medium';
        $preview_class = 'wdb-media-field__preview--default';
        $hint = 'Gunakan gambar yang jelas dan proporsional.';

        if ('photo_andalan_id' === $field) {
            $size = \WDB\Core\Plugin::get_pesantren_photo_size();
            $preview_class = 'wdb-media-field__preview--landscape';
            $hint = 'Rasio ideal 16:9 untuk photo andalan pesantren.';
        }

        if ('pasphoto_id' === $field) {
            $size = \WDB\Core\Plugin::get_alumni_pasphoto_size();
            $preview_class = 'wdb-media-field__preview--portrait';
            $hint = 'Rasio ideal 3:4 untuk pasphoto alumni.';
        }

        $image_url = '' !== $value ? wp_get_attachment_image_url((int) $value, $size) : '';

        if (! $image_url && '' !== $value) {
            $image_url = wp_get_attachment_image_url((int) $value, 'medium');
        }

        $html = '<div class="wdb-media-field" data-wdb-media-field>';
        $html .= '<input type="hidden" name="' . esc_attr($field) . '" id="' . esc_attr($field) . '" value="' . esc_attr($value) . '" data-wdb-media-input>';
        $html .= '<div class="wdb-media-field__frame">';
        $html .= '<div class="wdb-media-field__preview ' . esc_attr($preview_class) . ($image_url ? '' : ' is-empty') . '" data-wdb-media-preview>';

        if ($image_url) {
            $html .= '<img src="' . esc_url($image_url) . '" alt="" data-wdb-media-image>';
        } else {
            $html .= '<span data-wdb-media-empty>Belum ada gambar</span>';
        }

        $html .= '</div>';
        $html .= '<div class="wdb-media-field__actions">';
        $html .= '<button type="button" class="button button-primary" data-wdb-media-open>Pilih dari Media Library</button> ';
        $html .= '<button type="button" class="button button-link-delete" data-wdb-media-remove' . ($image_url ? '' : ' hidden') . '>Hapus</button>';
        $html .= '</div>';
        $html .= '<p class="wdb-media-field__hint">' . esc_html($hint) . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private static function render_embedded_address_field(int $address_id): string
    {
        $address = $address_id > 0 ? self::get_related_record('addresses', $address_id) : null;
        $address = is_array($address) ? $address : [];
        $html = '<input type="hidden" name="address_id" value="' . esc_attr((string) $address_id) . '">';
        $html .= '<p><label>Alamat Lengkap</label><br>';
        $html .= '<input type="text" class="regular-text" name="alamat_lengkap" value="' . esc_attr((string) ($address['alamat_lengkap'] ?? '')) . '" placeholder="Alamat lengkap">';
        $html .= '</p>';
        $html .= '<div style="height:8px"></div>';
        $html .= self::render_region_address_field($address);

        return $html;
    }

    private static function render_embedded_job_field(int $job_id): string
    {
        $job = $job_id > 0 ? self::get_related_record('jobs', $job_id) : null;
        $job = is_array($job) ? $job : [];
        $job_address_id = isset($job['address_id']) ? (int) $job['address_id'] : 0;
        $job_address = $job_address_id > 0 ? self::get_related_record('addresses', $job_address_id) : null;
        $job_address = is_array($job_address) ? $job_address : [];
        $html = '<input type="hidden" name="job_id" value="' . esc_attr((string) $job_id) . '">';
        $html .= '<input type="hidden" name="job_address_id" value="' . esc_attr((string) $job_address_id) . '">';
        $html .= '<p><label>Pekerjaan</label><br><select name="job_pekerjaan" class="regular-text" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari pekerjaan"><option value=""></option>';

        foreach (self::get_job_options() as $option_value => $option_label) {
            $html .= '<option value="' . esc_attr($option_value) . '"' . selected((string) ($job['pekerjaan'] ?? ''), $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }

        $html .= '</select></p>';
        $html .= '<p><label>Nama Lembaga / Instansi / Usaha</label><br><input type="text" class="regular-text" name="job_nama_lembaga" value="' . esc_attr((string) ($job['nama_lembaga'] ?? '')) . '" placeholder="Contoh: PT Webane Indonesia"></p>';
        $html .= '<p><label>Jabatan / Bidang Usaha</label><br><input type="text" class="regular-text" name="job_jabatan" value="' . esc_attr((string) ($job['jabatan'] ?? '')) . '" placeholder="Contoh: Software Engineer"></p>';
        $html .= '<p><label>Alamat Lembaga / Instansi</label><br><input type="text" class="regular-text" name="job_alamat_lengkap" value="' . esc_attr((string) ($job_address['alamat_lengkap'] ?? '')) . '" placeholder="Contoh: Jl. Webane No. 10"></p>';
        $html .= '<div style="height:8px"></div>';
        $html .= self::render_region_address_field($job_address, 'job_');

        return $html;
    }

    private static function render_embedded_contact_field(int $contact_id): string
    {
        $contact = $contact_id > 0 ? self::get_related_record('contacts', $contact_id) : null;
        $contact = is_array($contact) ? $contact : [];
        $html = '<div data-wdb-contact-sync>';
        $html .= '<input type="hidden" name="contact_id" value="' . esc_attr((string) $contact_id) . '">';
        $html .= '<p><label>Email</label><br><input type="email" class="regular-text" name="email" value="' . esc_attr((string) ($contact['email'] ?? '')) . '" placeholder="contoh@domain.com"></p>';
        $html .= '<p><label>Nomor HP</label><br><input type="text" class="regular-text" name="nomor_hp" value="' . esc_attr((string) ($contact['nomor_hp'] ?? '')) . '" placeholder="08xxxxxxxxxx" data-wdb-contact-hp></p>';
        $html .= '<label><input type="checkbox" name="nomor_hp_tampil_publik" value="1"' . checked(! empty($contact['nomor_hp_tampil_publik']), true, false) . '> Tampilkan nomor telepon di halaman publik pesantren</label><br>';
        $html .= '<p><label>Nomor WhatsApp</label><br><input type="text" class="regular-text" name="nomor_whatsapp" value="' . esc_attr((string) ($contact['nomor_whatsapp'] ?? '')) . '" placeholder="08xxxxxxxxxx" data-wdb-contact-wa></p>';
        $html .= '<label><input type="checkbox" name="whatsapp_sama_dengan_hp" value="1" data-wdb-contact-sync-toggle' . checked(! empty($contact['whatsapp_sama_dengan_hp']), true, false) . '> WhatsApp sama dengan HP</label>';
        $html .= '<br><label><input type="checkbox" name="whatsapp_tampil_publik" value="1"' . checked(! empty($contact['whatsapp_tampil_publik']), true, false) . '> Tampilkan tombol WhatsApp di halaman publik</label>';
        $html .= '</div>';

        return $html;
    }

    private static function render_embedded_social_field(int $social_id): string
    {
        $social = $social_id > 0 ? self::get_related_record('socials', $social_id) : null;
        $social = is_array($social) ? $social : [];
        $html = '<input type="hidden" name="social_id" value="' . esc_attr((string) $social_id) . '">';
        $html .= '<p><label>URL Profil</label><br><input type="url" class="regular-text" name="url_profil" value="' . esc_attr((string) ($social['url_profil'] ?? '')) . '" placeholder="https://contoh.com/profil"></p>';
        $html .= '<p><label>Instagram</label><br><input type="url" class="regular-text" name="instagram" value="' . esc_attr((string) ($social['instagram'] ?? '')) . '" placeholder="https://instagram.com/username"></p>';
        $html .= '<p><label>Facebook</label><br><input type="url" class="regular-text" name="facebook" value="' . esc_attr((string) ($social['facebook'] ?? '')) . '" placeholder="https://facebook.com/username"></p>';
        $html .= '<p><label>Tiktok</label><br><input type="url" class="regular-text" name="tiktok" value="' . esc_attr((string) ($social['tiktok'] ?? '')) . '" placeholder="https://tiktok.com/@username"></p>';
        $html .= '<p><label>Youtube</label><br><input type="url" class="regular-text" name="youtube" value="' . esc_attr((string) ($social['youtube'] ?? '')) . '" placeholder="https://youtube.com/@channel"></p>';
        $html .= '<p><label>Website</label><br><input type="url" class="regular-text" name="website" value="' . esc_attr((string) ($social['website'] ?? '')) . '" placeholder="https://domain.com"></p>';

        return $html;
    }

    private static function render_birthplace_regency_field(string $value, bool $required): string
    {
        $options = self::get_birthplace_regency_options();
        $html = '<select name="tempat_lahir" id="tempat_lahir" class="regular-text" data-wdb-autocomplete="1" data-wdb-autocomplete-placeholder="Cari kabupaten atau kota"' . ($required ? ' required' : '') . '>';
        $html .= '<option value=""></option>';

        if ('' !== $value && ! isset($options[$value])) {
            $html .= '<option value="' . esc_attr($value) . '" selected>' . esc_html($value) . '</option>';
        }

        foreach ($options as $option_value => $option_label) {
            $html .= '<option value="' . esc_attr($option_value) . '"' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    private static function save_record(string $page, array $config): void
    {
        global $wpdb;

        $data = [];
        $formats = [];

        foreach ($config['fields'] as $field => $field_config) {
            if ('region_address' === $field_config['type']) {
                continue;
            }

            if (in_array($field_config['type'], ['readonly_number', 'embedded_address', 'embedded_contact', 'embedded_job', 'embedded_social'], true)) {
                continue;
            }

            $data[$field] = self::sanitize_field_value($field_config, $field);
            $formats[] = self::get_format($field_config['type']);
        }

        if ('wdb-pesantren' === $page) {
            $data['jumlah_santri_total'] = max(0, (int) $data['santri_putra']) + max(0, (int) $data['santri_putri']);
            $data['jumlah_guru_total'] = max(0, (int) $data['asatidz']) + max(0, (int) $data['asatidzah']);
            $data['address_id'] = self::save_embedded_address(isset($_POST['address_id']) ? absint(wp_unslash($_POST['address_id'])) : 0);
            $data['contact_id'] = self::save_embedded_contact(isset($_POST['contact_id']) ? absint(wp_unslash($_POST['contact_id'])) : 0);
            $data['social_id'] = self::save_embedded_social(isset($_POST['social_id']) ? absint(wp_unslash($_POST['social_id'])) : 0);
            $formats[] = '%d';
            $formats[] = '%d';
            $formats[] = '%d';
            $formats[] = '%d';
            $formats[] = '%d';
        }

        if ('wdb-alumni' === $page) {
            $data['address_id'] = self::save_embedded_address(isset($_POST['address_id']) ? absint(wp_unslash($_POST['address_id'])) : 0);
            $data['contact_id'] = self::save_embedded_contact(isset($_POST['contact_id']) ? absint(wp_unslash($_POST['contact_id'])) : 0);
            $data['job_id'] = self::save_embedded_job(isset($_POST['job_id']) ? absint(wp_unslash($_POST['job_id'])) : 0);
            $data['social_id'] = self::save_embedded_social(isset($_POST['social_id']) ? absint(wp_unslash($_POST['social_id'])) : 0);
            $formats[] = '%d';
            $formats[] = '%d';
            $formats[] = '%d';
            $formats[] = '%d';
        }

        if ('wdb-addresses' === $page) {
            $data['provinsi_code'] = isset($_POST['provinsi_code']) ? sanitize_text_field(wp_unslash($_POST['provinsi_code'])) : '';
            $data['provinsi_name'] = isset($_POST['provinsi_name']) ? sanitize_text_field(wp_unslash($_POST['provinsi_name'])) : '';
            $data['kabupaten_code'] = isset($_POST['kabupaten_code']) ? sanitize_text_field(wp_unslash($_POST['kabupaten_code'])) : '';
            $data['kabupaten_name'] = isset($_POST['kabupaten_name']) ? sanitize_text_field(wp_unslash($_POST['kabupaten_name'])) : '';
            $data['kecamatan_code'] = isset($_POST['kecamatan_code']) ? sanitize_text_field(wp_unslash($_POST['kecamatan_code'])) : '';
            $data['kecamatan_name'] = isset($_POST['kecamatan_name']) ? sanitize_text_field(wp_unslash($_POST['kecamatan_name'])) : '';
            $data['desa_code'] = isset($_POST['desa_code']) ? sanitize_text_field(wp_unslash($_POST['desa_code'])) : '';
            $data['desa_name'] = isset($_POST['desa_name']) ? sanitize_text_field(wp_unslash($_POST['desa_name'])) : '';
            $formats[] = '%s';
            $formats[] = '%s';
            $formats[] = '%s';
            $formats[] = '%s';
            $formats[] = '%s';
            $formats[] = '%s';
            $formats[] = '%s';
            $formats[] = '%s';
        }

        $now = current_time('mysql');
        $is_edit = isset($_POST['id']) && absint($_POST['id']) > 0;

        if ($is_edit) {
            $data['updated_at'] = $now;
            $formats[] = '%s';

            $wpdb->update(
                self::get_table_name($config),
                $data,
                ['id' => absint($_POST['id'])],
                $formats,
                ['%d']
            );

            self::sync_record_name_tag($page, $data);
            self::redirect_to_page($page, 'updated');
        }

        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $formats[] = '%s';
        $formats[] = '%s';

        $wpdb->insert(self::get_table_name($config), $data, $formats);

        self::sync_record_name_tag($page, $data);
        self::redirect_to_page($page, 'created');
    }

    private static function delete_record(string $page, array $config, int $id): void
    {
        global $wpdb;

        $wpdb->delete(self::get_table_name($config), ['id' => $id], ['%d']);
        self::redirect_to_page($page, 'deleted');
    }

    private static function sanitize_field_value(array $field_config, string $field)
    {
        if ('checkbox' === $field_config['type']) {
            return isset($_POST[$field]) ? 1 : 0;
        }

        $raw = isset($_POST[$field]) ? wp_unslash($_POST[$field]) : '';

        if ('number' === $field_config['type'] || 'readonly_number' === $field_config['type'] || 'media_image' === $field_config['type']) {
            return '' === $raw ? null : max(0, (int) $raw);
        }

        if ('email' === $field_config['type']) {
            return '' === $raw ? null : sanitize_email($raw);
        }

        if ('url' === $field_config['type']) {
            return '' === $raw ? null : esc_url_raw($raw);
        }

        if ('date' === $field_config['type']) {
            return '' === $raw ? null : sanitize_text_field($raw);
        }

        if ('relation' === $field_config['type']) {
            return '' === $raw ? null : absint($raw);
        }

        if ('textarea' === $field_config['type']) {
            return sanitize_textarea_field($raw);
        }

        if ('select' === $field_config['type']) {
            return sanitize_text_field($raw);
        }

        return sanitize_text_field($raw);
    }

    private static function sync_record_name_tag(string $page, array $data): void
    {
        $name = '';

        if ('wdb-pesantren' === $page) {
            $name = isset($data['nama_pesantren']) ? (string) $data['nama_pesantren'] : '';
        }

        if ('wdb-alumni' === $page) {
            $name = isset($data['nama_lengkap']) ? (string) $data['nama_lengkap'] : '';
        }

        $name = trim($name);

        if ('' === $name || term_exists($name, 'post_tag')) {
            return;
        }

        wp_insert_term($name, 'post_tag');
    }

    private static function get_format(string $type): string
    {
        if (in_array($type, ['number', 'readonly_number', 'relation', 'checkbox', 'media_image'], true)) {
            return '%d';
        }

        return '%s';
    }

    private static function get_records(array $config): array
    {
        global $wpdb;

        $query = 'SELECT * FROM ' . self::get_table_name($config) . ' ORDER BY id DESC';

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    private static function get_record(array $config, int $id): ?array
    {
        global $wpdb;

        $query = $wpdb->prepare(
            'SELECT * FROM ' . self::get_table_name($config) . ' WHERE id = %d',
            $id
        );

        $record = $wpdb->get_row($query, ARRAY_A);

        return is_array($record) ? $record : null;
    }

    private static function get_related_record(string $relation, int $id): ?array
    {
        global $wpdb;

        if ($id <= 0) {
            return null;
        }

        $query = $wpdb->prepare(
            'SELECT * FROM ' . $wpdb->prefix . 'wdb_' . $relation . ' WHERE id = %d',
            $id
        );

        $record = $wpdb->get_row($query, ARRAY_A);

        return is_array($record) ? $record : null;
    }

    private static function get_table_name(array $config): string
    {
        global $wpdb;

        return $wpdb->prefix . 'wdb_' . $config['table'];
    }

    private static function redirect_to_page(string $page, string $message): void
    {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => $page,
                    'message' => $message,
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    private static function get_column_label(array $config, string $column): string
    {
        if ('id' === $column) {
            return 'ID';
        }

        return $config['fields'][$column]['label'] ?? $column;
    }

    private static function format_list_value(array $record, string $column): string
    {
        $value = $record[$column] ?? '';

        if (is_null($value) || '' === $value) {
            return '-';
        }

        if (is_numeric($value) && '0' === (string) $value) {
            return '0';
        }

        return (string) $value;
    }

    private static function get_relation_options(string $relation): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wdb_' . $relation;
        $query = 'SELECT * FROM ' . $table . ' ORDER BY id DESC LIMIT 500';
        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['id']] = self::build_relation_label($relation, $row);
        }

        return $options;
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

    private static function build_relation_label(string $relation, array $row): string
    {
        if ('addresses' === $relation) {
            return '#' . (int) $row['id'] . ' - ' . $row['alamat_lengkap'];
        }

        if ('contacts' === $relation) {
            $parts = array_filter([(string) ($row['email'] ?? ''), (string) ($row['nomor_hp'] ?? '')]);
            return '#' . (int) $row['id'] . ' - ' . implode(' / ', $parts);
        }

        if ('jobs' === $relation) {
            $parts = array_filter([(string) ($row['pekerjaan'] ?? ''), (string) ($row['nama_lembaga'] ?? '')]);
            return '#' . (int) $row['id'] . ' - ' . implode(' / ', $parts);
        }

        if ('socials' === $relation) {
            $parts = array_filter([(string) ($row['website'] ?? ''), (string) ($row['instagram'] ?? '')]);
            return '#' . (int) $row['id'] . ' - ' . implode(' / ', $parts);
        }

        return '#' . (int) $row['id'];
    }

    private static function save_embedded_address(int $address_id, string $prefix = ''): ?int
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

    private static function save_embedded_job(int $job_id): ?int
    {
        global $wpdb;

        $job_address_id = self::save_embedded_address(isset($_POST['job_address_id']) ? absint(wp_unslash($_POST['job_address_id'])) : 0, 'job_');
        $data = [
            'pekerjaan' => isset($_POST['job_pekerjaan']) ? sanitize_text_field(wp_unslash($_POST['job_pekerjaan'])) : '',
            'nama_lembaga' => isset($_POST['job_nama_lembaga']) ? sanitize_text_field(wp_unslash($_POST['job_nama_lembaga'])) : '',
            'jabatan' => isset($_POST['job_jabatan']) ? sanitize_text_field(wp_unslash($_POST['job_jabatan'])) : '',
            'address_id' => $job_address_id,
            'updated_at' => current_time('mysql'),
        ];

        if ($job_id > 0) {
            $wpdb->update($wpdb->prefix . 'wdb_jobs', $data, ['id' => $job_id], ['%s', '%s', '%s', '%d', '%s'], ['%d']);

            return $job_id;
        }

        if ('' === $data['pekerjaan'] && '' === $data['nama_lembaga'] && '' === $data['jabatan'] && empty($job_address_id)) {
            return null;
        }

        $data['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'wdb_jobs', $data, ['%s', '%s', '%s', '%d', '%s', '%s']);

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function save_embedded_contact(int $contact_id): ?int
    {
        global $wpdb;

        $whatsapp_same = isset($_POST['whatsapp_sama_dengan_hp']) ? 1 : 0;
        $phone_public = isset($_POST['nomor_hp_tampil_publik']) ? 1 : 0;
        $whatsapp_public = isset($_POST['whatsapp_tampil_publik']) ? 1 : 0;
        $nomor_hp = isset($_POST['nomor_hp']) ? sanitize_text_field(wp_unslash($_POST['nomor_hp'])) : '';
        $data = [
            'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
            'nomor_hp' => $nomor_hp,
            'nomor_hp_tampil_publik' => $phone_public,
            'nomor_whatsapp' => $whatsapp_same ? $nomor_hp : (isset($_POST['nomor_whatsapp']) ? sanitize_text_field(wp_unslash($_POST['nomor_whatsapp'])) : ''),
            'whatsapp_sama_dengan_hp' => $whatsapp_same,
            'whatsapp_tampil_publik' => $whatsapp_public,
            'updated_at' => current_time('mysql'),
        ];

        if ($contact_id > 0) {
            $wpdb->update($wpdb->prefix . 'wdb_contacts', $data, ['id' => $contact_id], ['%s', '%s', '%d', '%s', '%d', '%d', '%s'], ['%d']);

            return $contact_id;
        }

        if ('' === $data['email'] && '' === $data['nomor_hp'] && '' === $data['nomor_whatsapp']) {
            return null;
        }

        $data['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'wdb_contacts', $data, ['%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s']);

        return 0 !== (int) $wpdb->insert_id ? (int) $wpdb->insert_id : null;
    }

    private static function save_embedded_social(int $social_id): ?int
    {
        global $wpdb;

        $data = [
            'url_profil' => isset($_POST['url_profil']) ? esc_url_raw(wp_unslash($_POST['url_profil'])) : '',
            'instagram' => isset($_POST['instagram']) ? esc_url_raw(wp_unslash($_POST['instagram'])) : '',
            'facebook' => isset($_POST['facebook']) ? esc_url_raw(wp_unslash($_POST['facebook'])) : '',
            'tiktok' => isset($_POST['tiktok']) ? esc_url_raw(wp_unslash($_POST['tiktok'])) : '',
            'youtube' => isset($_POST['youtube']) ? esc_url_raw(wp_unslash($_POST['youtube'])) : '',
            'website' => isset($_POST['website']) ? esc_url_raw(wp_unslash($_POST['website'])) : '',
            'updated_at' => current_time('mysql'),
        ];

        if ($social_id > 0) {
            $wpdb->update($wpdb->prefix . 'wdb_socials', $data, ['id' => $social_id], ['%s', '%s', '%s', '%s', '%s', '%s', '%s'], ['%d']);

            return $social_id;
        }

        if ('' === $data['url_profil'] && '' === $data['instagram'] && '' === $data['facebook'] && '' === $data['tiktok'] && '' === $data['youtube'] && '' === $data['website']) {
            return null;
        }

        $data['created_at'] = current_time('mysql');
        $wpdb->insert($wpdb->prefix . 'wdb_socials', $data, ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

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

    private static function apply_focus_address_defaults(array $record): array
    {
        if (! empty($record['provinsi_code']) || ! empty($record['kabupaten_code'])) {
            return $record;
        }

        $focus = get_option('wdb_focus_address', []);

        if (! is_array($focus) || (empty($focus['provinsi_code']) && empty($focus['kabupaten_code']))) {
            return $record;
        }

        $record['provinsi_code'] = (string) ($focus['provinsi_code'] ?? '');
        $record['provinsi_name'] = (string) ($focus['provinsi_name'] ?? '');
        $record['kabupaten_code'] = (string) ($focus['kabupaten_code'] ?? '');
        $record['kabupaten_name'] = (string) ($focus['kabupaten_name'] ?? '');

        return $record;
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
}
