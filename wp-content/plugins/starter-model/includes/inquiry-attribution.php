<?php
/**
 * Theme-independent Fluent Forms adapter.
 * Reuse this file and its JS asset in an owned business plugin, then call Module::boot($asset_url).
 */
namespace AxisTemplate\Attribution;

defined('ABSPATH') || exit;

final class Module {
    public const VERSION = '1.0.2';
    public const PAYLOAD_FIELD = 'wb_attribution_payload';
    public const URL_LIMIT = 16384;
    private static bool $booted = false;
    private static string $asset_url = '';
    private static array $known_forms = [];

    public static function boot(string $asset_url): void {
        if (self::$booted) return;
        self::$booted = true;
        self::$asset_url = $asset_url;
        add_action('init', [self::class, 'initial_migration'], 40);
        add_action('admin_init', [self::class, 'sync_forms']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue'], 1);
        add_action('fluentform/inserted_new_form', [self::class, 'ensure_form'], 20, 1);
        add_action('fluentform/form_duplicated', [self::class, 'ensure_form'], 20, 1);
        add_action('fluentform/form_imported', [self::class, 'ensure_form'], 20, 1);
        add_filter('fluentform/form_fields_update', [self::class, 'update_definition'], 20, 2);
        add_filter('fluentform/rendering_form', [self::class, 'render_form'], 20, 1);
        add_filter('fluentform/insert_response_data', [self::class, 'normalize_submission'], 20, 3);
        add_filter('fluentform/response_render_input_hidden', [self::class, 'render_value'], 20, 4);
        add_filter('fluentform/email_body', [self::class, 'email_body'], 20, 4);
        add_filter('redirect_canonical', [self::class, 'preserve_canonical_query'], 20, 2);
    }

    /** Keep opaque query bytes when Core changes only their serialization, not their meaning. */
    public static function preserve_canonical_query($redirect_url, string $requested_url) {
        if (!self::enabled() || !is_string($redirect_url) || $redirect_url === '') return $redirect_url;
        $requested = wp_parse_url($requested_url);
        $redirect = wp_parse_url($redirect_url);
        if (!is_array($requested) || !is_array($redirect) || empty($requested['query'])
            || ($requested['query'] === ($redirect['query'] ?? ''))
            || !in_array($redirect['scheme'] ?? '', ['http', 'https'], true)
            || strcasecmp($requested['host'] ?? '', $redirect['host'] ?? '') !== 0
            || preg_match('/[\r\n]/', $requested['query'])) return $redirect_url;
        // Core may intentionally consume routing/preview variables. Do not restore those.
        parse_str($requested['query'], $before);
        parse_str($redirect['query'] ?? '', $after);
        ksort($before); ksort($after);
        if ($before !== $after) return $redirect_url;
        $fragment = isset($redirect['fragment']) ? '#' . $redirect['fragment'] : '';
        $base = explode('?', explode('#', $redirect_url, 2)[0], 2)[0];
        return $base . '?' . $requested['query'] . $fragment;
    }

    public static function labels(): array {
        return [
            'wb_first_landing_url' => 'First landing URL',
            'wb_first_referrer' => 'First referrer',
            'wb_first_touch_at' => 'First touch time (UTC)',
            'wb_last_landing_url' => 'Last non-direct landing URL',
            'wb_last_referrer' => 'Last non-direct referrer',
            'wb_last_touch_at' => 'Last non-direct touch time (UTC)',
            'wb_submission_url' => 'Inquiry submission URL',
            'wb_attribution_status' => 'Attribution capture status',
        ];
    }

    public static function is_managed_field(array $field): bool {
        $name = $field['attributes']['name'] ?? '';
        return is_string($name) && ($field['element'] ?? '') === 'input_hidden'
            && ($name === self::PAYLOAD_FIELD || array_key_exists($name, self::labels()));
    }

    private static function enabled(): bool {
        return (bool) apply_filters('axiscnc_template_attribution_enabled', true);
    }

    public static function retention_days(): int {
        return max(1, min(365, (int) apply_filters('axiscnc_template_attribution_retention_days', 90)));
    }

    public static function internal_hosts(): array {
        $host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
        $hosts = apply_filters('axiscnc_template_attribution_internal_hosts', [$host]);
        if (!is_array($hosts)) return [$host];
        return array_values(array_unique(array_filter(array_map(
            static fn($value): string => is_string($value) ? strtolower(trim($value)) : '',
            $hosts
        ))));
    }

    public static function enqueue(): void {
        if (!self::enabled() || is_admin() || is_preview() || is_feed() || is_robots() || is_trackback()) return;
        wp_enqueue_script('axiscnc-template-attribution', self::$asset_url, [], self::VERSION, false);
        wp_add_inline_script('axiscnc-template-attribution', 'window.axisTemplateAttributionConfig=' . wp_json_encode([
            'retentionDays' => self::retention_days(),
            'internalHosts' => self::internal_hosts(),
            'storageAllowed' => (bool) apply_filters('axiscnc_template_attribution_storage_allowed', true),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';', 'before');
    }

    /** Flatten only form elements, retaining duplicate names so conflicts cannot be hidden. */
    private static function elements(array $node): array {
        $result = isset($node['element']) ? [$node] : [];
        foreach ($node as $value) {
            if (is_array($value)) $result = array_merge($result, self::elements($value));
        }
        return $result;
    }

    private static function eligible(object $form, array $definition): bool {
        if (!self::enabled() || !empty($form->has_payment)) return false;
        $elements = self::elements($definition['fields'] ?? []);
        $types = array_column($elements, 'element');
        if (array_intersect($types, ['input_password', 'payment_method', 'payment_item', 'multi_payment_component', 'custom_payment_component', 'subscription_payment_component'])) return false;
        $ids = array_map('intval', (array) get_option('axiscnc_template_attribution_form_ids', []));
        $eligible = in_array((int) $form->id, $ids, true)
            || in_array('input_email', $types, true) && in_array('textarea', $types, true);
        return (bool) apply_filters('axiscnc_template_attribution_track_form', $eligible, $form);
    }

    private static function hidden_fields(): array {
        $fields = [];
        foreach (self::labels() + [self::PAYLOAD_FIELD => 'Attribution transport (internal)'] as $name => $label) {
            $fields[] = [
                'element' => 'input_hidden',
                'attributes' => ['type' => 'hidden', 'name' => $name, 'value' => '', 'class' => 'wb-attribution-field'],
                'settings' => ['admin_field_label' => $label],
                'editor_options' => ['title' => 'Hidden Field', 'icon_class' => 'ff-edit-hidden-field', 'template' => 'inputHidden'],
            ];
        }
        return $fields;
    }

    private static function extend_definition(object $form, array $definition): ?array {
        if (!isset($definition['fields']) || !is_array($definition['fields']) || !self::eligible($form, $definition)) return null;
        $existing = [];
        foreach (self::elements($definition['fields']) as $field) {
            $name = $field['attributes']['name'] ?? '';
            if (!is_string($name)) return null;
            if ($name === self::PAYLOAD_FIELD || array_key_exists($name, self::labels())) {
                if (isset($existing[$name]) || !self::is_managed_field($field)) return null;
                $existing[$name] = true;
            }
        }
        foreach (self::hidden_fields() as $field) {
            if (!isset($existing[$field['attributes']['name']])) $definition['fields'][] = $field;
        }
        return $definition;
    }

    private static function backup_form(object $form): bool {
        global $wpdb;
        $key = 'axiscnc_template_attribution_form_backup_' . (int) $form->id;
        if (get_option($key, null) !== null) return true;
        $backup = [
            'createdAt' => gmdate('c'),
            'version' => self::VERSION,
            'form_fields' => $form->form_fields,
            'notifications' => $wpdb->get_results($wpdb->prepare(
                "SELECT id, value FROM {$wpdb->prefix}fluentform_form_meta WHERE form_id=%d AND meta_key='notifications' ORDER BY id",
                (int) $form->id
            ), ARRAY_A),
        ];
        return add_option($key, $backup, '', false) || get_option($key, null) !== null;
    }

    public static function initial_migration(): void {
        if (get_option('axiscnc_template_attribution_version') !== self::VERSION) {
            if (self::sync_forms()) update_option('axiscnc_template_attribution_version', self::VERSION, false);
        }
    }

    public static function sync_forms(): bool {
        if (!defined('FLUENTFORM_VERSION') || !self::enabled()) return false;
        global $wpdb;
        $rows = $wpdb->get_results("SELECT id, form_fields, has_payment FROM {$wpdb->prefix}fluentform_forms");
        if (!is_array($rows)) return false;
        foreach ($rows as $form) self::ensure_form((int) $form->id, $form);
        return true;
    }

    public static function ensure_form(int $id, ?object $form = null): ?array {
        global $wpdb;
        if ($id < 1) return null;
        $table = $wpdb->prefix . 'fluentform_forms';
        $form = $form ?? $wpdb->get_row($wpdb->prepare("SELECT id, form_fields, has_payment FROM {$table} WHERE id=%d", $id));
        if (!$form) return null;
        $definition = json_decode((string) $form->form_fields, true);
        if (!is_array($definition)) return null;
        $extended = self::extend_definition($form, $definition);
        if ($extended === null) return null;
        if ($extended !== $definition) {
            if (!self::backup_form($form)) return null;
            if ($wpdb->update($table, ['form_fields' => wp_json_encode($extended)], ['id' => $id]) === false) return null;
        }
        self::$known_forms[$id] = true;
        return $extended;
    }

    public static function render_form(object $form): object {
        $definition = self::ensure_form((int) $form->id, $form);
        if ($definition !== null) {
            $form->form_fields = wp_json_encode($definition);
            $form->fields = $definition;
        }
        return $form;
    }

    public static function update_definition($value, int $id) {
        global $wpdb;
        $definition = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($definition)) return $value;
        $form = $wpdb->get_row($wpdb->prepare("SELECT id, form_fields, has_payment FROM {$wpdb->prefix}fluentform_forms WHERE id=%d", $id));
        if (!$form) return $value;
        $extended = self::extend_definition($form, $definition);
        if ($extended === null || $extended === $definition || !self::backup_form($form)) return $value;
        return is_string($value) ? wp_json_encode($extended) : $extended;
    }

    private static function tracked(int $id): bool {
        return isset(self::$known_forms[$id]) || self::ensure_form($id) !== null;
    }

    /** Validate an HTTP(S) URL without stripping/re-encoding legitimate query bytes. */
    public static function validate_url($value, bool $internal = false): ?string {
        if (!is_string($value) || strlen($value) > self::URL_LIMIT || preg_match('/[\x00-\x20\x7f<>"\\\\]/', $value)) return null;
        if ($value === '') return '';
        $parts = wp_parse_url($value);
        if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return null;
        if ($internal && !in_array(strtolower($parts['host']), self::internal_hosts(), true)) return null;
        return $value;
    }

    /** Repeat credential redaction server-side; the encoded browser payload is still untrusted. */
    private static function redact_url(string $value, bool &$redacted): string {
        $secrets = ['access_token', 'id_token', 'auth_token', 'authorization', 'password', 'client_secret'];
        $replace = static function (string $query) use ($secrets, &$redacted): string {
            $parts = explode('&', $query);
            foreach ($parts as &$part) {
                $separator = strpos($part, '=');
                $raw_key = $separator === false ? $part : substr($part, 0, $separator);
                $key = strtolower(rawurldecode(str_replace('+', ' ', $raw_key)));
                if (in_array($key, $secrets, true)) {
                    if ($separator === false || substr($part, $separator + 1) !== 'REDACTED') $redacted = true;
                    $part = $raw_key . '=REDACTED';
                }
            }
            unset($part);
            return implode('&', $parts);
        };
        $hash_position = strpos($value, '#');
        $main = $hash_position === false ? $value : substr($value, 0, $hash_position);
        $hash = $hash_position === false ? '' : substr($value, $hash_position + 1);
        $query_position = strpos($main, '?');
        if ($query_position !== false) $main = substr($main, 0, $query_position + 1) . $replace(substr($main, $query_position + 1));
        if (strpos($hash, '=') !== false) $hash = $replace($hash);
        return $main . ($hash_position === false ? '' : '#' . $hash);
    }

    private static function valid_time($value): bool {
        if ($value === '') return true;
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/D', $value)) return false;
        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.v\Z', $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        return $date !== false && ($errors === false || !$errors['warning_count'] && !$errors['error_count'])
            && $date->getTimestamp() <= time() + 300
            && $date->getTimestamp() > time() - self::retention_days() * DAY_IN_SECONDS;
    }

    public static function normalize_submission(array $data, int $id, array $configs = []): array {
        if (!self::tracked($id)) return $data;
        $transport = $data[self::PAYLOAD_FIELD] ?? '';
        unset($data[self::PAYLOAD_FIELD]);
        foreach (self::labels() as $key => $_label) $data[$key] = '';
        $data['wb_attribution_status'] = 'unavailable';
        if (!(bool) apply_filters('axiscnc_template_attribution_storage_allowed', true)) {
            $data['wb_attribution_status'] = 'consent_denied';
            return $data;
        }
        if (!is_string($transport) || $transport === '') return $data;
        if (strlen($transport) > 131072) { $data['wb_attribution_status'] = 'invalid_payload'; return $data; }
        $decoded = base64_decode($transport, true);
        $payload = $decoded !== false ? json_decode($decoded, true, 5) : null;
        if (!is_array($payload) || ($payload['version'] ?? null) !== 1 || !is_array($payload['data'] ?? null)) {
            $data['wb_attribution_status'] = 'invalid_payload';
            return $data;
        }
        $values = $payload['data'];
        foreach (self::labels() as $key => $_label) {
            if (!array_key_exists($key, $values) || !is_string($values[$key])) {
                $data['wb_attribution_status'] = 'invalid_payload';
                return $data;
            }
        }
        if (!preg_match('/^(captured|session_only|page_only|consent_denied|unavailable)(?:\|(redacted|url_too_long|invalid_url|first_missing))*$/D', $values['wb_attribution_status'])) {
            $data['wb_attribution_status'] = 'invalid_payload';
            return $data;
        }
        if (explode('|', $values['wb_attribution_status'])[0] === 'consent_denied') {
            $data['wb_attribution_status'] = 'consent_denied';
            return $data;
        }
        $redacted = false;
        foreach (['wb_first_landing_url', 'wb_first_referrer', 'wb_last_landing_url', 'wb_last_referrer', 'wb_submission_url'] as $key) {
            $values[$key] = self::redact_url($values[$key], $redacted);
        }
        if ($redacted && strpos($values['wb_attribution_status'], '|redacted') === false) $values['wb_attribution_status'] .= '|redacted';
        foreach (['first', 'last'] as $touch) {
            $url = self::validate_url($values['wb_' . $touch . '_landing_url'], true);
            $referrer = self::validate_url($values['wb_' . $touch . '_referrer']);
            $at = $values['wb_' . $touch . '_touch_at'];
            if ($url === null || $referrer === null || !self::valid_time($at)
                || ($url === '') !== ($at === '') || $url === '' && $referrer !== '') {
                $data['wb_attribution_status'] = 'invalid_url';
                return $data;
            }
            if ($referrer !== '' && in_array(strtolower((string) wp_parse_url($referrer, PHP_URL_HOST)), self::internal_hosts(), true)) {
                $values['wb_' . $touch . '_referrer'] = '';
            }
        }
        if (self::validate_url($values['wb_submission_url'], true) === null) {
            $data['wb_attribution_status'] = 'invalid_url';
            return $data;
        }
        foreach (self::labels() as $key => $_label) $data[$key] = $values[$key];
        return $data;
    }

    /** HTML values are escaped here; raw database/export values retain their original URL bytes. */
    public static function render_value($value, array $field, int $id, bool $is_html = false) {
        $name = $field['raw']['attributes']['name'] ?? $field['attributes']['name'] ?? '';
        if (!array_key_exists($name, self::labels()) || !self::tracked($id) || !$is_html) return $value;
        $value = is_string($value) ? $value : '';
        $display = $value === '' ? 'Not captured / not supplied' : $value;
        $html = esc_html($display);
        if ($value !== '' && (str_ends_with($name, '_url') || str_ends_with($name, '_referrer')) && self::validate_url($value) !== null) {
            // validate_url restricts schemes/shape; attribute escaping preserves encoded query bytes.
            // esc_url() additionally removes %0A/%0D, corrupting otherwise valid query values.
            $html = '<a href="' . esc_attr($value) . '" style="overflow-wrap:anywhere;word-break:break-word">' . $html . '</a>';
        }
        if ($name === 'wb_first_landing_url') {
            $html = '<span data-wb-attribution="first-url"><strong>Inquiry attribution</strong><br>' . $html . '</span>';
        }
        return $html;
    }

    public static function email_body(string $body, array $notification, array $data, object $form): string {
        if (!self::tracked((int) $form->id) || !isset($data['wb_attribution_status'])
            || strpos($body, 'data-wb-attribution="first-url"') !== false
            || strpos($body, 'data-wb-attribution="section"') !== false) return $body;
        $section = '<div data-wb-attribution="section"><h3>Inquiry attribution</h3>';
        foreach (self::labels() as $key => $label) {
            $value = isset($data[$key]) && is_string($data[$key]) ? $data[$key] : '';
            $display = $value === '' ? 'Not captured / not supplied' : $value;
            $html = esc_html($display);
            if ($value !== '' && (str_ends_with($key, '_url') || str_ends_with($key, '_referrer')) && self::validate_url($value) !== null) {
                $html = '<a href="' . esc_attr($value) . '" style="overflow-wrap:anywhere;word-break:break-word">' . $html . '</a>';
            }
            $section .= '<p><strong>' . esc_html($label) . ':</strong> ' . $html . '</p>';
        }
        return $body . $section . '</div>';
    }
}

