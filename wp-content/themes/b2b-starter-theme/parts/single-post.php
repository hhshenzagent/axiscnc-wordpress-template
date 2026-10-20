<?php
/** Native post reading layout. The single route owns the header and footer. */
defined('ABSPATH') || exit;
require_once get_theme_file_path('inc/author-profile.php');
?>
<main id="main" class="shell article-page">
<?php while (have_posts()) : the_post();
    $article = Starter\Theme\blog_article_content();
    $has_toc = count($article['items']) >= 3;
    $author_id = (int) get_the_author_meta('ID');
    $author_name = (string) get_the_author();
    $author_profile = Starter\Theme\article_author_profile($author_id);
    $author_url = $author_id ? (string) get_author_posts_url($author_id) : '';
    $thumbnail_id = (int) get_post_thumbnail_id();
    $caption = $thumbnail_id ? trim((string) wp_get_attachment_caption($thumbnail_id)) : '';
    $category = Starter\Theme\blog_primary_category();
    $previous = get_previous_post();
    $next = get_next_post();
    $related = Starter\Theme\blog_related_posts(get_the_ID(), 3);
    $content = (string) preg_replace('/(<table\b[^>]*>.*?<\/table>)/is', '<div class="article-table-scroll" role="region" aria-label="Scrollable table" tabindex="0">$1</div>', $article['content']);
    $render_toc = static function () use ($article): void {
        echo '<ol>';
        foreach ($article['items'] as $item) {
            printf('<li class="toc-level-%d"><a href="#%s">%s</a></li>', (int) $item['level'], esc_attr($item['id']), esc_html($item['text']));
        }
        echo '</ol>';
    };
?>
    <?php Starter\Theme\breadcrumbs(); ?>
    <div class="article-reading-grid">
        <article <?php post_class('article-main'); ?>>
            <header class="article-intro">
                <?php if ($category instanceof WP_Term) : ?>
                    <a class="article-category-link" href="<?php echo esc_url((string) get_category_link($category)); ?>"><?php echo esc_html($category->name); ?></a>
                <?php endif; ?>
                <h1><?php the_title(); ?></h1>
                <?php if (has_excerpt() && trim(wp_strip_all_tags((string) get_the_excerpt())) !== '') : ?>
                    <p class="article-summary"><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>
                <div class="article-meta">
                    <?php if ($author_name !== '') : ?>
                        <?php echo Starter\Theme\article_author_avatar($author_profile, 44, 'article-meta-avatar'); ?>
                    <?php endif; ?>
                    <div class="article-meta-author">
                        <?php if ($author_name !== '') : ?>
                            <?php if ($author_url) : ?><a href="<?php echo esc_url($author_url); ?>"><?php echo esc_html($author_name); ?></a><?php else : ?><span><?php echo esc_html($author_name); ?></span><?php endif; ?>
                        <?php endif; ?>
                        <div class="article-meta-dates">
                            <span class="article-meta-published">Published <time datetime="<?php echo esc_attr((string) get_the_date('c')); ?>"><?php echo esc_html((string) get_the_date()); ?></time></span>
                            <?php if (Starter\Theme\blog_modified_is_visible()) : ?>
                                <span class="article-meta-updated">Updated <time datetime="<?php echo esc_attr((string) get_the_modified_date('c')); ?>"><?php echo esc_html((string) get_the_modified_date()); ?></time></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </header>
            <?php if ($thumbnail_id) : ?>
                <figure class="article-cover">
                    <?php the_post_thumbnail('large', ['loading' => 'eager', 'fetchpriority' => 'high']); ?>
                    <?php if ($caption !== '') : ?><figcaption><?php echo esc_html($caption); ?></figcaption><?php endif; ?>
                </figure>
            <?php endif; ?>
            <?php if ($has_toc) : ?>
                <details class="article-contents article-mobile-toc">
                    <summary><span>Table of Contents</span><span class="article-contents-chevron" aria-hidden="true"></span></summary>
                    <nav aria-label="Article contents"><?php $render_toc(); ?></nav>
                </details>
            <?php endif; ?>
            <div class="article-content prose">
                <?php echo $content; // WordPress the_content filters already applied; preserve rich media and shortcodes. ?>
            </div>
            <?php get_template_part('parts/article-author', null, ['profile' => $author_profile]); ?>
            <?php if ($previous || $next) : ?>
                <nav class="article-neighbors" aria-label="Previous and next articles">
                    <?php if ($previous) : ?><a class="article-previous" href="<?php echo esc_url(get_permalink($previous)); ?>"><span class="article-neighbor-label">← Previous article</span><span><?php echo esc_html(get_the_title($previous)); ?></span></a><?php endif; ?>
                    <?php if ($next) : ?><a class="article-next" href="<?php echo esc_url(get_permalink($next)); ?>"><span class="article-neighbor-label">Next article →</span><span><?php echo esc_html(get_the_title($next)); ?></span></a><?php endif; ?>
                </nav>
            <?php endif; ?>
        </article>
        <div class="article-sidebar">
            <?php if ($has_toc) : ?>
                <details class="article-contents article-desktop-toc" open>
                    <summary><span>Table of Contents</span><span class="article-contents-chevron" aria-hidden="true"></span></summary>
                    <nav aria-label="Article contents"><?php $render_toc(); ?></nav>
                </details>
            <?php endif; ?>
            <?php Starter\Theme\component_product_inquiry(); ?>
        </div>
        <?php if ($related) : ?>
            <section class="article-related" aria-labelledby="article-related-heading">
                <h2 id="article-related-heading">Related articles</h2>
                <?php Starter\Theme\component_related_blog_cards(['posts' => $related]); ?>
            </section>
        <?php endif; ?>
    </div>
<?php endwhile; ?>
</main>

