<?php
/** Minimal author introduction; optional values never leave empty UI. */
defined('ABSPATH') || exit;
require_once get_theme_file_path('inc/author-profile.php');
$profile = $args['profile'] ?? Starter\Theme\article_author_profile((int) get_the_author_meta('ID'));
if (empty($profile['name']) || empty($profile['bio'])) return;
?>
<aside class="article-author<?php echo empty($profile['photo']) ? ' has-no-photo' : ''; ?>" aria-label="About the author">
    <?php echo Starter\Theme\article_author_avatar($profile, 88, 'article-author-avatar'); ?>
    <div class="article-author-heading">
        <p class="article-author-label">About the author</p>
        <h2 class="article-author-name"><?php echo esc_html($profile['name']); ?></h2>
        <?php if ($profile['title'] !== '') : ?><p class="article-author-title"><?php echo esc_html($profile['title']); ?></p><?php endif; ?>
    </div>
    <div class="article-author-bio"><?php echo wpautop(esc_html($profile['bio'])); ?></div>
    <?php if ($profile['socials']) : ?>
        <ul class="article-author-socials" aria-label="Author social profiles">
            <?php foreach ($profile['socials'] as $social) : ?>
                <li><a href="<?php echo esc_url($social['url']); ?>" rel="me noopener noreferrer" target="_blank" aria-label="<?php echo esc_attr($profile['name'] . ' on ' . $social['label'] . ' (opens in a new tab)'); ?>"><?php echo Starter\Theme\article_author_social_icon($social['platform']); ?><span><?php echo esc_html($social['label']); ?></span></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</aside>

