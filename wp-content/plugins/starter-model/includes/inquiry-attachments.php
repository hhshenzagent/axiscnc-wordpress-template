<?php
/** Private, bounded inquiry uploads. Uses Fluent Forms' public field and notification APIs. */
namespace AxisTemplate\Attachments;
defined('ABSPATH') || exit;

final class Module {
    public const FIELD = 'wb_inquiry_files';
    public const NAME = 'inquiry_attachments';
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const MAX_FILES = 2;
    private static array $claimed = [];
    private static string $assets = '';

    public static function boot(string $assets): void {
        self::$assets = $assets;
        add_action('init', [self::class, 'install']);
        add_action('init', static function (): void {
            if (class_exists('FluentForm\\App\\Services\\FormBuilder\\BaseFieldManager')) {
                require_once __DIR__ . '/inquiry-attachment-field.php';
                new Field();
            }
        }, 20);
        foreach (['wp_ajax_', 'wp_ajax_nopriv_'] as $prefix) add_action($prefix . 'wb_inquiry_upload', [self::class, 'upload']);
        add_action('admin_post_wb_inquiry_download', [self::class, 'download']);
        add_action('admin_post_nopriv_wb_inquiry_download', static fn() => wp_die('Sign in to download inquiry attachments.', '', ['response' => 403]));
        add_filter('fluentform/validation_errors', [self::class, 'validate'], 30, 4);
        add_filter('fluentform/insert_response_data', [self::class, 'claim'], 30, 3);
        add_action('fluentform/before_form_actions_processing', [self::class, 'bind'], 1, 3);
        add_filter('fluentform/email_attachments', [self::class, 'mail_files'], 30, 5);
        add_filter('fluentform/response_render_' . self::FIELD, [self::class, 'render_response'], 10, 4);
        add_action('fluentform/submission_deleted', [self::class, 'delete_entry']);
        add_action('fluentform/after_deleting_submissions', static function ($ids): void { foreach ((array) $ids as $id) self::delete_entry($id); });
        add_action('wb_inquiry_attachment_cleanup', [self::class, 'cleanup']);
        add_action('admin_notices', [self::class, 'storage_notice']);
    }
    public static function table(): string { global $wpdb; return $wpdb->prefix . 'wb_inquiry_files'; }
    public static function install(): void {
        if (get_option('wb_inquiry_files_schema') !== '1') {
            global $wpdb;
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $table = self::table();
            dbDelta("CREATE TABLE $table (
                token varchar(64) NOT NULL,
                form_id bigint(20) unsigned NOT NULL,
                entry_id bigint(20) unsigned NOT NULL DEFAULT 0,
                session_hash varchar(64) NOT NULL,
                claim_id varchar(64) NOT NULL DEFAULT '',
                filename varchar(200) NOT NULL,
                mime varchar(64) NOT NULL,
                bytes bigint(20) unsigned NOT NULL,
                created_at bigint(20) unsigned NOT NULL,
                PRIMARY KEY  (token),
                KEY entry_id (entry_id),
                KEY session_created (session_hash,created_at)
            ) " . $wpdb->get_charset_collate() . ';');
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) update_option('wb_inquiry_files_schema', '1', false);
        }
        if (!wp_next_scheduled('wb_inquiry_attachment_cleanup')) wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'wb_inquiry_attachment_cleanup');
    }
    /** Private directory must resolve outside every public WordPress/document root. Never fall back to uploads. */
    public static function storage(): string {
        $root = defined('AXISCNC_TEMPLATE_INQUIRY_STORAGE') ? AXISCNC_TEMPLATE_INQUIRY_STORAGE : dirname(rtrim(ABSPATH, '/\\')) . '/axiscnc-template-private/' . get_current_blog_id();
        if (!is_string($root) || !str_starts_with($root, '/') || str_contains($root, "\0")) throw new \RuntimeException('Configure an absolute private inquiry storage directory.');
        if (!wp_mkdir_p($root)) throw new \RuntimeException('Private inquiry storage is unavailable.');
        $real = realpath($root);
        foreach ([ABSPATH, $_SERVER['DOCUMENT_ROOT'] ?? ABSPATH] as $public) {
            $public = realpath($public);
            if ($public && ($real === $public || str_starts_with($real . '/', rtrim($public, '/') . '/'))) throw new \RuntimeException('Inquiry storage must be outside the web root.');
        }
        if (!$real || !is_writable($real)) throw new \RuntimeException('Private inquiry storage is not writable.');
        @chmod($real, 0700);
        return $real;
    }
    public static function storage_notice(): void {
        if (!current_user_can('manage_options')) return;
        try { self::storage(); } catch (\RuntimeException $error) {
            echo '<div class="notice notice-error"><p>' . esc_html($error->getMessage() . ' File uploads are disabled; text inquiries still work.') . '</p></div>';
        }
    }
    public static function component(): array {
        return ['index' => 25, 'element' => self::FIELD,
            'attributes' => ['name' => self::NAME, 'type' => 'text', 'value' => '', 'class' => '', 'placeholder' => ''],
            'settings' => ['label' => 'Attachments (optional)', 'admin_field_label' => 'Attachments', 'label_placement' => '', 'help_message' => '', 'container_class' => '', 'validation_rules' => [], 'conditional_logics' => []],
            'editor_options' => ['title' => 'Inquiry attachments', 'icon_class' => 'el-icon-paperclip', 'template' => 'inputText']];
    }
    public static function has_field(int $form_id): bool {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT form_fields,status FROM {$wpdb->prefix}fluentform_forms WHERE id=%d", $form_id));
        if (!$row || $row->status !== 'published') return false;
        $definition = json_decode($row->form_fields, true);
        $walk = static function (array $fields) use (&$walk): bool {
            foreach ($fields as $field) {
                if (($field['element'] ?? '') === self::FIELD && ($field['attributes']['name'] ?? '') === self::NAME) return true;
                foreach ($field['columns'] ?? [] as $column) if ($walk($column['fields'] ?? [])) return true;
            }
            return false;
        };
        return $walk($definition['fields'] ?? []);
    }
    public static function assets(): void {
        wp_enqueue_script('wb-inquiry-attachments', self::$assets . '/js/inquiry-attachments.js', [], '1.1.0', true);
        wp_enqueue_style('wb-inquiry-attachments', self::$assets . '/css/inquiry-attachments.css', [], '1.1.0');
    }
    private static function session(): string {
        $value = $_COOKIE['wb_inquiry_upload_session'] ?? '';
        return is_string($value) && preg_match('/^[a-f0-9]{64}$/D', $value) ? hash_hmac('sha256', $value, wp_salt('auth')) : '';
    }
    public static function upload(): void {
        check_ajax_referer('wb-inquiry-upload', 'nonce');
        $form_id = absint($_POST['form_id'] ?? 0);
        if (!self::has_field($form_id)) wp_send_json_error(['message' => 'This form does not accept attachments.'], 400);
        if (($_POST['operation'] ?? '') === 'session') {
            if (!self::session()) {
                $value = bin2hex(random_bytes(32));
                setcookie('wb_inquiry_upload_session', $value, ['expires' => time() + DAY_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Strict']);
            }
            wp_send_json_success();
        }
        $session = self::session();
        if (!$session) wp_send_json_error(['message' => 'Enable cookies to upload attachments.'], 400);
        global $wpdb;
        $table = self::table();
        $token = sanitize_text_field(wp_unslash($_POST['token'] ?? ''));
        if (($_POST['operation'] ?? '') === 'remove') {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE token=%s AND session_hash=%s AND form_id=%d AND entry_id=0 AND claim_id=''", $token, $session, $form_id));
            if ($row) self::delete_rows([$row]);
            wp_send_json_success();
        }
        // A per-session upload ceiling bounds abandoned/replaced files too. IP throttle is additive (not a trust identity).
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE session_hash=%s AND created_at>%d", $session, time() - HOUR_IN_SECONDS));
        $daily_bytes = (int) $wpdb->get_var($wpdb->prepare("SELECT SUM(bytes) FROM $table WHERE created_at>%d", time() - DAY_IN_SECONDS));
        if ($daily_bytes >= 1024 * 1024 * 1024) wp_send_json_error(['message' => 'Attachments are temporarily unavailable. Please send your request without files.'], 429);
        $ip_key = 'wb_upload_rate_' . hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', wp_salt('nonce'));
        $rate = (int) get_transient($ip_key);
        if ($count >= 20 || $rate >= 40) wp_send_json_error(['message' => 'Too many uploads. Please try again later.'], 429);
        set_transient($ip_key, $rate + 1, HOUR_IN_SECONDS);
        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) wp_send_json_error(['message' => 'Upload failed. Check the file size and try again.'], 400);
        $size = filesize($file['tmp_name']);
        if (!$size || $size > self::MAX_BYTES) wp_send_json_error(['message' => 'Each file must be no larger than 5 MB.'], 400);
        $name = sanitize_file_name($file['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        // Extension alone is insufficient. Verify actual content with fileinfo and WP's image decoder.
        $mime = class_exists('finfo') ? (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
        if (!isset($types[$ext]) || $mime !== $types[$ext] || ($ext === 'pdf' && file_get_contents($file['tmp_name'], false, null, 0, 5) !== '%PDF-') || ($ext !== 'pdf' && wp_get_image_mime($file['tmp_name']) !== $mime)) wp_send_json_error(['message' => 'Upload a valid PDF, JPG or PNG file.'], 400);
        try { $root = self::storage(); } catch (\RuntimeException $error) { wp_send_json_error(['message' => 'Attachments are temporarily unavailable. Submit your request without files or contact us.'], 503); }
        $token = bin2hex(random_bytes(32));
        $directory = $root . '/' . $token;
        wp_mkdir_p($directory); @chmod($directory, 0700);
        $stem = pathinfo($name, PATHINFO_FILENAME);
        $name = (function_exists('mb_strcut') ? mb_strcut($stem, 0, 160, 'UTF-8') : substr($stem, 0, 160)) . '.' . $ext;
        $path = $directory . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) wp_send_json_error(['message' => 'Could not store attachment. Please try again.'], 500);
        @chmod($path, 0600);
        if (!$wpdb->insert($table, ['token' => $token, 'form_id' => $form_id, 'session_hash' => $session, 'filename' => $name, 'mime' => $mime, 'bytes' => $size, 'created_at' => time()])) {
            unlink($path); rmdir($directory); wp_send_json_error(['message' => 'Could not record attachment. Please try again.'], 500);
        }
        wp_send_json_success(['token' => $token, 'name' => $name, 'bytes' => $size]);
    }
    private static function pending($data, int $form_id): array {
        $raw = $data[self::NAME] ?? '';
        if ($raw === '' || $raw === null) return [];
        if (!is_string($raw)) throw new \RuntimeException('Invalid attachment selection.');
        $tokens = explode(',', $raw);
        if (count($tokens) > self::MAX_FILES || count(array_unique($tokens)) !== count($tokens)) throw new \RuntimeException('Attach at most 2 files.');
        if (!self::has_field($form_id) || !self::session()) throw new \RuntimeException('Invalid attachment form or session.');
        global $wpdb; $rows = []; $total = 0; $table = self::table();
        foreach ($tokens as $token) {
            if (!preg_match('/^[a-f0-9]{64}$/D', $token)) throw new \RuntimeException('Invalid attachment selection.');
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE token=%s AND form_id=%d AND session_hash=%s AND entry_id=0 AND claim_id='' AND created_at>%d", $token, $form_id, self::session(), time() - DAY_IN_SECONDS));
            if (!$row || !is_file(self::path($row))) throw new \RuntimeException('An attachment has expired or was already submitted. Select the file again.');
            $total += (int) $row->bytes; $rows[] = $row;
        }
        if ($total > self::MAX_BYTES * self::MAX_FILES) throw new \RuntimeException('Attachments exceed 10 MB.');
        return $rows;
    }
    public static function validate($errors, $data, $form, $fields) {
        try { self::pending($data, (int) $form->id); } catch (\RuntimeException $error) { $errors[self::NAME] = [$error->getMessage()]; }
        return $errors;
    }
    public static function claim($data, $form_id, $configs = []) {
        global $wpdb; $table = self::table(); $claim = bin2hex(random_bytes(32)); $claimed = [];
        try {
            $rows = self::pending($data, (int) $form_id);
            foreach ($rows as $row) {
                if ($wpdb->query($wpdb->prepare("UPDATE $table SET claim_id=%s WHERE token=%s AND claim_id='' AND entry_id=0", $claim, $row->token)) !== 1) throw new \RuntimeException('Attachment already submitted.');
                $claimed[] = $row;
            }
        } catch (\RuntimeException $error) {
            $wpdb->query($wpdb->prepare("UPDATE $table SET claim_id='' WHERE claim_id=%s AND entry_id=0", $claim));
            wp_send_json_error(['errors' => [self::NAME => [$error->getMessage()]]], 422);
        }
        self::$claimed[(int) $form_id] = $claimed;
        if ($claimed) $data[self::NAME] = array_map(static fn($row) => self::url($row->token), $claimed);
        else unset($data[self::NAME]);
        return $data;
    }
    public static function bind($entry_id, $data, $form): void {
        global $wpdb;
        foreach (self::$claimed[(int) $form->id] ?? [] as $row) {
            $written = $wpdb->update(self::table(), ['entry_id' => (int) $entry_id], ['token' => $row->token, 'form_id' => (int) $form->id, 'entry_id' => 0]);
            if ($written !== 1) throw new \RuntimeException('Inquiry saved, but attachment binding failed. Inspect the saved inquiry before retrying.');
        }
        unset(self::$claimed[(int) $form->id]);
    }
    private static function path($row): string {
        if (!preg_match('/^[a-f0-9]{64}$/D', $row->token) || basename($row->filename) !== $row->filename) throw new \RuntimeException('Invalid attachment record.');
        return self::storage() . '/' . $row->token . '/' . $row->filename;
    }
    public static function url(string $token): string { return add_query_arg(['action' => 'wb_inquiry_download', 'file' => $token], admin_url('admin-post.php')); }
    public static function mail_files($paths, $notification, $data, $entry, $form): array {
        if (empty($notification['wb_inquiry_attachments'])) return $paths;
        global $wpdb; $table = self::table();
        foreach ($wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE entry_id=%d AND form_id=%d", $entry->id, $form->id)) as $row) {
            $path = self::path($row);
            if (!is_file($path)) throw new \RuntimeException('Inquiry attachment missing. Restore the private file before resending this notification.');
            $paths[] = $path;
        }
        return array_values(array_unique($paths));
    }
    public static function render_response($value, $field = [], $form_id = 0, $html = false): string {
        if (!$value) return '';
        $values = is_array($value) ? $value : [$value]; $links = [];
        global $wpdb;
        foreach ($values as $url) {
            if (!is_string($url)) continue;
            parse_str(wp_parse_url($url, PHP_URL_QUERY) ?: '', $query);
            $token = $query['file'] ?? '';
            if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) continue;
            $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE token=%s AND form_id=%d AND entry_id>0', $token, $form_id));
            if (!$row) { $links[] = $html ? 'Attachment no longer retained' : 'Attachment no longer retained'; continue; }
            $links[] = $html ? '<a href="' . esc_url(self::url($token)) . '">' . esc_html($row->filename) . '</a>' : $row->filename;
        }
        return implode($html ? '<br>' : ', ', $links);
    }
    public static function download(): void {
        if (!is_user_logged_in()) wp_die('Access denied.', '', ['response' => 403]);
        global $wpdb; $token = sanitize_text_field(wp_unslash($_GET['file'] ?? ''));
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE token=%s AND entry_id>0', $token));
        if (!$row) wp_die('Attachment not found or expired.', '', ['response' => 404]);
        if (!current_user_can('manage_options') && (!function_exists('wpFluentForm') || !\FluentForm\App\Modules\Acl\Acl::hasPermission('fluentform_entries_viewer', (int) $row->form_id))) wp_die('Access denied.', '', ['response' => 403]);
        $path = self::path($row);
        if (!is_file($path)) wp_die('Attachment not found.', '', ['response' => 404]);
        nocache_headers(); header('Content-Type: ' . $row->mime); header('Content-Disposition: attachment; filename="' . str_replace('"', '', $row->filename) . '"'); header('X-Content-Type-Options: nosniff'); header('Content-Length: ' . filesize($path));
        readfile($path); exit;
    }
    private static function delete_rows(array $rows): array {
        global $wpdb;
        $result = ['removed' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            try {
                $path = self::path($row);
                if (is_file($path) && !@unlink($path)) { $result['failed']++; continue; }
                if (is_dir(dirname($path)) && !@rmdir(dirname($path))) { $result['failed']++; continue; }
                if ($wpdb->delete(self::table(), ['token' => $row->token]) === false) { $result['failed']++; continue; }
                $result['removed']++;
            } catch (\RuntimeException $error) { $result['failed']++; }
        }
        if ($result['failed']) {
            // Counts only: no private paths, filenames, tokens or inquiry data in diagnostics.
            error_log('WordPress Builder inquiry attachment cleanup failed for ' . $result['failed'] . ' record(s); indexes retained for retry.');
            do_action('axiscnc_template/inquiry_attachment_cleanup_failed', $result['failed']);
        }
        return $result;
    }
    public static function delete_entry($entry_id): void { global $wpdb; self::delete_rows($wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE entry_id=%d', $entry_id))); }
    public static function cleanup(): void {
        global $wpdb; $table = self::table();
        // Saved inquiries themselves are unaffected. Submitted files expire after 90 days; abandoned uploads after 24h.
        self::delete_rows($wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE (entry_id=0 AND created_at<%d) OR (entry_id>0 AND created_at<%d)", time() - DAY_IN_SECONDS, time() - 90 * DAY_IN_SECONDS)));
    }
}

