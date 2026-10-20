<?php
namespace Starter\Model;
defined('ABSPATH') || exit;

/** Keep owned Classic ACF snapshots on the new revision created by a REST save. */
final class AcfRestRevisions {
    private static ?array $context = null;
    private static ?array $failure = null;

    public static function boot(): void {
        add_action('init', [self::class, 'register'], 20);
        add_filter('wp_save_post_revision_post_has_changed', [self::class, 'changed'], 20, 3);
        add_action('_wp_put_post_revision', [self::class, 'copy'], 20, 2);
        add_action('wp_after_insert_post', [self::class, 'finish'], 99);
        add_filter('rest_request_after_callbacks', [self::class, 'response'], 99, 3);
    }

    public static function register(): void {
        $types = apply_filters('starter_acf_revision_post_types', ['page', 'post', 'starter_product', 'starter_category', 'starter_industry', 'starter_guide']);
        foreach (array_unique($types) as $type) {
            if (is_string($type) && post_type_exists($type)) {
                add_action('rest_after_insert_' . $type, [self::class, 'prepare'], 99, 3);
            }
        }
    }

    private static function snapshot(int $id): ?array {
        $meta = acf_get_meta($id);
        if (!is_array($meta)) return null;
        ksort($meta);
        return $meta;
    }

    public static function prepare(\WP_Post $post, \WP_REST_Request $request, bool $creating): void {
        $type = get_post_type_object($post->post_type);
        // Core skips revisions on first insert; with no revision, preview reads the parent.
        if ($creating || !$type || !in_array($request->get_method(), ['POST', 'PUT', 'PATCH'], true)
            || !current_user_can('edit_post', $post->ID) || !wp_revisions_enabled($post)
            || !function_exists('acf_get_meta') || !function_exists('acf_copy_metadata')
            || did_action('acf/save_post') || apply_filters('acf/revisions/skip_legacy_metabox_handling', false)
            || self::$context !== null) return;

        $base = '/' . trim($type->rest_namespace ?: 'wp/v2', '/') . '/' . ($type->rest_base ?: $post->post_type);
        if (!in_array($request->get_route(), [$base, $base . '/' . $post->ID], true)) return;
        $snapshot = self::snapshot($post->ID);
        $revisions = wp_get_post_revisions($post->ID);
        $latest = reset($revisions);
        // Ordinary body-only posts with no ACF history need no extra work.
        if ($snapshot === [] && (!$latest || self::snapshot($latest->ID) === [])) return;
        self::$context = ['post' => $post->ID, 'request' => $request, 'snapshot' => $snapshot,
            'oldIds' => array_keys($revisions), 'newId' => null, 'failed' => $snapshot === null];
    }

    public static function changed(bool $changed, \WP_Post $latest, \WP_Post $post): bool {
        if (self::$context === null || $post->ID !== self::$context['post'] || self::$context['failed']) return $changed;
        return $changed || self::$context['snapshot'] !== self::snapshot($latest->ID);
    }

    public static function copy(int $revision_id, int $post_id): void {
        if (self::$context === null || $post_id !== self::$context['post']) return;
        $revision = get_post($revision_id);
        if (!$revision || $revision->post_type !== 'revision' || (int) $revision->post_parent !== $post_id
            || in_array($revision_id, self::$context['oldIds'], true) || self::$context['newId'] !== null
            || wp_is_post_autosave($revision_id) || self::$context['failed']) return;
        self::$context['newId'] = $revision_id;
        if (self::snapshot($post_id) !== self::$context['snapshot']) {
            self::$context['failed'] = true;
            return;
        }
        // ACF's installed metadata helper includes values and field-key references.
        // Copy to this new ID, never overwrite an earlier revision with current data.
        acf_copy_metadata($post_id, $revision_id);
        self::$context['failed'] = self::snapshot($revision_id) !== self::$context['snapshot'];
    }

    public static function finish(int $post_id): void {
        if (self::$context === null || $post_id !== self::$context['post']) return;
        $context = self::$context;
        self::$context = null;
        $revisions = wp_get_post_revisions($post_id);
        $latest = reset($revisions);
        if ($context['failed'] || !$latest || self::snapshot($post_id) !== $context['snapshot']
            || self::snapshot($latest->ID) !== $context['snapshot']) {
            self::$failure = $context;
        }
    }

    public static function response($response, array $handler, \WP_REST_Request $request) {
        if (self::$failure === null || self::$failure['request'] !== $request) return $response;
        $failure = self::$failure;
        self::$failure = null;
        if (is_wp_error($response)) return $response;
        return new \WP_Error('starter_acf_revision_incomplete',
            'Content was saved but its ACF revision is incomplete. Read back this record before retrying.',
            ['status' => 500, 'post_id' => $failure['post'], 'revision_id' => $failure['newId'], 'post_saved' => true]);
    }
}

