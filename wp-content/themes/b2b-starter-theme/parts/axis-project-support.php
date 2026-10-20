<?php
/** Homepage service roles, with all copy and portraits owned by Home fields. */
defined('ABSPATH') || exit;
$page_id = (int) ($args['page_id'] ?? get_option('page_on_front'));
$lead = get_field('axis_support_lead', $page_id);
$lead = is_array($lead) && trim((string) ($lead['role'] ?? '')) !== '' ? $lead : [];
$rows = get_field('axis_support_members', $page_id);
$members = array_values(array_filter(is_array($rows) ? $rows : [], static function ($row): bool {
    return is_array($row) && trim((string) ($row['role'] ?? '')) !== '';
}));
if (!$lead && !$members) return;
$lead_has_portrait = !empty($lead['portrait']) && wp_attachment_is_image((int) $lead['portrait']);
$title = trim((string) get_field('axis_support_title', $page_id));
$eyebrow = trim((string) get_field('axis_support_eyebrow', $page_id));
$intro = trim((string) get_field('axis_support_intro', $page_id));
$disclosure = trim((string) get_field('axis_support_disclosure', $page_id));
$cta = get_field('axis_support_cta', $page_id);
$stages = get_field('axis_support_stages', $page_id);
$stages = array_values(array_filter(is_array($stages) ? $stages : [], static function ($row): bool {
    return is_array($row) && trim((string) ($row['title'] ?? '')) !== '' && trim((string) ($row['text'] ?? '')) !== '';
}));
$person_title = static function (array $person): string {
    return trim((string) ($person['name'] ?? '')) ?: trim((string) $person['role']);
};
$experience = static function (array $person): string {
    $years = (int) ($person['years'] ?? 0);
    return !empty($person['experience_verified']) && $years > 0 ? sprintf(_n('%d year in customer service', '%d years in customer service', $years, 'b2b-starter'), $years) : '';
};
?>
<section id="project-support" class="axis-support" <?php if ($title): ?>aria-labelledby="axis-support-title"<?php endif; ?>>
<div class="shell">
<?php if ($eyebrow || $title || $intro): ?><header class="axis-support-heading">
    <div><?php if ($eyebrow): ?><p class="axis-eyebrow"><?php echo esc_html($eyebrow); ?></p><?php endif; ?>
    <?php if ($title): ?><h2 id="axis-support-title"><?php echo esc_html($title); ?></h2><?php endif; ?></div>
    <?php if ($intro): ?><p class="axis-support-intro"><?php echo esc_html($intro); ?></p><?php endif; ?>
</header><?php endif; ?>
<?php if ($disclosure): ?><p class="axis-support-disclosure"><span aria-hidden="true">◇</span> <?php echo esc_html($disclosure); ?></p><?php endif; ?>
<?php if ($lead): ?><article class="axis-support-lead <?php if (!$lead_has_portrait) echo 'has-no-portrait'; ?>">
    <?php if ($lead_has_portrait): ?><div class="axis-support-portrait axis-support-lead-photo">
        <?php echo wp_get_attachment_image((int) $lead['portrait'], 'large', false, ['loading' => 'lazy', 'sizes' => '(max-width: 760px) calc(100vw - 32px), 380px']); ?>
    </div><?php endif; ?>
    <div class="axis-support-lead-copy">
        <p class="axis-support-role"><?php echo esc_html($lead['role']); ?></p>
        <h3><?php echo esc_html(trim((string) ($lead['headline'] ?? '')) ?: $person_title($lead)); ?></h3>
        <?php if (!empty($lead['name']) && !empty($lead['headline'])): ?><p class="axis-support-name"><?php echo esc_html($lead['name']); ?></p><?php endif; ?>
        <?php if (!empty($lead['focus'])): ?><p class="axis-support-focus"><?php echo esc_html($lead['focus']); ?></p><?php endif; ?>
        <?php if ($experience($lead)): ?><p class="axis-support-experience"><?php echo esc_html($experience($lead)); ?></p><?php endif; ?>
        <?php if (!empty($lead['bio'])): ?><p class="axis-support-bio"><?php echo esc_html($lead['bio']); ?></p><?php endif; ?>
        <?php if ($stages): ?><ul class="axis-support-stages"><?php foreach ($stages as $stage): ?><li><span aria-hidden="true">✓</span><div><strong><?php echo esc_html($stage['title']); ?></strong><p><?php echo esc_html($stage['text']); ?></p></div></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if (is_array($cta) && !empty($cta['title']) && !empty($cta['url'])): ?><a class="button axis-support-cta" href="<?php echo esc_url($cta['url']); ?>" <?php if (($cta['target'] ?? '') === '_blank') echo 'target="_blank" rel="noopener"'; ?>><?php echo esc_html($cta['title']); ?><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"/></svg></a><?php endif; ?>
    </div>
</article><?php endif; ?>
<?php if ($members): ?><div class="axis-support-carousel">
<div class="axis-support-controls" hidden>
    <button type="button" data-support-prev aria-label="<?php esc_attr_e('Previous service roles', 'b2b-starter'); ?>">&#8592;</button>
    <button type="button" data-support-pause aria-pressed="false"><?php esc_html_e('Pause', 'b2b-starter'); ?></button>
    <button type="button" data-support-next aria-label="<?php esc_attr_e('Next service roles', 'b2b-starter'); ?>">&#8594;</button>
</div>
<div class="axis-support-grid" tabindex="0" role="region" aria-label="<?php esc_attr_e('Service roles. Scroll horizontally to explore.', 'b2b-starter'); ?>"><?php foreach ($members as $member): ?>
<article class="axis-support-card">
    <?php if (!empty($member['portrait']) && wp_attachment_is_image((int) $member['portrait'])): ?><div class="axis-support-portrait axis-support-card-photo"><?php echo wp_get_attachment_image((int) $member['portrait'], 'large', false, ['loading' => 'lazy', 'sizes' => '(max-width: 600px) calc(100vw - 32px), (max-width: 900px) 45vw, 390px']); ?></div><?php endif; ?>
    <div class="axis-support-card-copy"><h3><?php echo esc_html($person_title($member)); ?></h3>
    <?php if (!empty($member['name'])): ?><p class="axis-support-role"><?php echo esc_html($member['role']); ?></p><?php endif; ?>
    <?php if (!empty($member['focus'])): ?><p class="axis-support-focus"><?php echo esc_html($member['focus']); ?></p><?php endif; ?>
    <?php if ($experience($member)): ?><p class="axis-support-experience"><?php echo esc_html($experience($member)); ?></p><?php endif; ?>
    <?php if (!empty($member['bio'])): ?><p class="axis-support-bio"><?php echo esc_html($member['bio']); ?></p><?php endif; ?></div>
</article><?php endforeach; ?></div></div><?php endif; ?>
</div></section>
