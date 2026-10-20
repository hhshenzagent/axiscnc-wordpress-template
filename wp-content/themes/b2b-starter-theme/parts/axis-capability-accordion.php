<?php
defined('ABSPATH') || exit;
$page_id = (int) get_option('page_on_front');
$rows = get_field('axis_capability_items', $page_id);
$items = array_values(array_filter(is_array($rows) ? $rows : [], static function($row): bool {
    return is_array($row) && trim((string)($row['label'] ?? '')) !== '' && trim((string)($row['headline'] ?? '')) !== '';
}));
if (!$items) return;
$eyebrow = trim((string)get_field('axis_capability_eyebrow', $page_id));
$title = trim((string)get_field('axis_capability_title', $page_id));
$intro = trim((string)get_field('axis_capability_intro', $page_id));
?>
<section id="machining-capabilities" class="axis-capabilities" data-axis-capabilities>
<div class="shell axis-capabilities-inner">
<?php if ($eyebrow || $title || $intro): ?><div class="axis-capabilities-head"><div><?php if($eyebrow): ?><p class="axis-capabilities-eyebrow"><?php echo esc_html($eyebrow); ?></p><?php endif; ?><?php if($title): ?><h2><?php echo esc_html($title); ?></h2><?php endif; ?></div><?php if($intro): ?><p><?php echo esc_html($intro); ?></p><?php endif; ?></div><?php endif; ?>
<div class="axis-capabilities-workspace"><div class="axis-capabilities-choices" aria-label="Machining capabilities">
<?php foreach($items as $index => $item): ?>
<button type="button" class="axis-capabilities-choice" id="capability-choice-<?php echo (int)$index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="capability-panel-<?php echo (int)$index; ?>">
<span class="axis-capabilities-number" aria-hidden="true"><?php echo esc_html(sprintf('%02d', $index+1)); ?></span><span class="axis-capabilities-choice-copy"><span class="axis-capabilities-label"><?php echo esc_html($item['label']); ?></span><?php if(!empty($item['summary']) || !empty($item['description'])): ?><span class="axis-capabilities-summary"><?php echo esc_html(trim(($item['summary'] ?? '') . (!empty($item['summary']) && !empty($item['description']) ? '. ' : '') . ($item['description'] ?? ''))); ?></span><?php endif; ?></span>
</button><?php endforeach; ?></div>
<div class="axis-capabilities-panels"><?php foreach($items as $index => $item): ?><div class="axis-capabilities-panel" id="capability-panel-<?php echo (int)$index; ?>" role="region" aria-labelledby="capability-choice-<?php echo (int)$index; ?>" <?php if($index !== 0) echo 'hidden'; ?>>
<?php if(!empty($item['image']) && wp_attachment_is_image((int)$item['image'])): ?><figure class="axis-capabilities-media"><?php echo wp_get_attachment_image((int)$item['image'], 'large', false, ['loading'=>'lazy','sizes'=>'(max-width: 640px) calc(100vw - 32px), (max-width: 900px) calc(100vw - 40px), (max-width: 1000px) calc(100vw - 318px), (max-width: 1304px) calc(100vw - 442px), 862px']); ?><?php if(!empty($item['image_caption'])): ?><figcaption><?php echo esc_html($item['image_caption']); ?></figcaption><?php endif; ?></figure><?php endif; ?>
<div class="axis-capabilities-panel-copy"><h3><?php echo esc_html($item['headline']); ?></h3><?php $cta = $item['cta'] ?? []; if(is_array($cta) && !empty($cta['url']) && !empty($cta['title'])): ?><a class="axis-capabilities-cta" href="<?php echo esc_url($cta['url']); ?>" <?php if(($cta['target'] ?? '') === '_blank') echo 'target="_blank" rel="noopener"'; ?>><?php echo esc_html($cta['title']); ?><span aria-hidden="true">↗</span></a><?php endif; ?></div>
</div><?php endforeach; ?></div></div></div></section>
