<?php
/** Corporate Home controller. Empty saved fields never acquire example content. */
namespace Starter\Theme;
defined('ABSPATH') || exit;

function home_v2_value(string $name, int $post_id) {
    return function_exists('get_field') ? get_field($name, $post_id, true, true) : null;
}

function home_v2_link($value): array {
    if (!is_array($value)) return [];
    $url = esc_url_raw(trim((string) ($value['url'] ?? '')));
    $title = trim((string) ($value['title'] ?? ''));
    return $url !== '' && $title !== '' ? ['url' => $url, 'title' => $title, 'target' => ($value['target'] ?? '') === '_blank' ? '_blank' : ''] : [];
}

function home_v2_image($value): int {
    $id = is_array($value) ? (int) ($value['ID'] ?? 0) : (int) $value;
    return $id && wp_attachment_is_image($id) ? $id : 0;
}

function home_v2_rows(string $name, int $post_id): array {
    $rows = home_v2_value($name, $post_id);
    return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
}

/** Filter canonical profile values without creating a second company data source. */
function home_v2_proof(array $profile, bool $show_facts, bool $show_certifications): array {
    $facts = [];
    foreach ($show_facts ? ($profile['proof'] ?? []) : [] as $row) {
        $value = trim((string) ($row['value'] ?? ''));
        $label = trim((string) ($row['label'] ?? ''));
        if ($value !== '' && $label !== '') $facts[] = ['value' => $value, 'label' => $label];
    }
    $certifications = [];
    foreach ($show_certifications ? ($profile['certifications'] ?? []) : [] as $line) {
        [$name, $note] = array_pad(explode('|', (string) $line, 2), 2, '');
        if (trim($name) !== '') $certifications[] = ['name' => trim($name), 'note' => trim($note)];
    }
    return ['facts' => $facts, 'certifications' => $certifications];
}

function home_v2_data(int $post_id = 0): array {
    $post_id = $post_id ?: get_the_ID();
    $plain = static fn (string $name): string => trim((string) home_v2_value($name, $post_id));
    $heading = static fn (string $name, string $default): string => $plain($name) ?: $default;
    $show_facts = home_v2_value('home_show_facts', $post_id);
    $show_certifications = home_v2_value('home_show_certifications', $post_id);
    $proof = home_v2_proof(factory_profile_data(), $show_facts === null || (bool) $show_facts, $show_certifications === null || (bool) $show_certifications);
    $clients = [];
    foreach (home_v2_rows('home_clients', $post_id) as $row) {
        $image = home_v2_image($row['image'] ?? 0);
        $name = trim((string) ($row['name'] ?? ''));
        if (!$image || $name === '') continue;
        $clients[] = ['name' => $name, 'image' => $image, 'url' => esc_url_raw((string) ($row['url'] ?? ''))];
    }
    $proof['title'] = $plain('home_proof_title');
    $proof['intro'] = $plain('home_proof_intro');
    $proof['image'] = home_v2_image(home_v2_value('home_proof_image', $post_id));
    // A title or decorative background alone must not create an empty banner.
    $proof['enabled'] = $proof['intro'] !== '' || (bool) ($proof['facts'] || $clients || $proof['certifications']);
    $ranges = [];
    $show_ranges = home_v2_value('show_applications', $post_id);
    if ($show_ranges === null || $show_ranges) {
        $terms = get_terms(['taxonomy' => 'product_collection', 'hide_empty' => true]);
        if (!is_wp_error($terms)) {
            $parents = array_filter(array_map(static fn ($term): int => (int) $term->parent, $terms));
            foreach ($terms as $term) {
                if (in_array($term->term_id, $parents, true)) continue;
                $url = get_term_link($term);
                if (is_wp_error($url)) continue;
                $object = function_exists(__NAMESPACE__ . '\\category_content_object') ? category_content_object($term->term_id) : 'product_collection_' . $term->term_id;
                $image = home_v2_image(get_field('category_card_image', $object) ?: get_field('category_hero_image', $object));
                $ranges[] = ['title' => $term->name, 'url' => $url, 'image' => $image];
            }
        }
    }
    $company = [
        'title' => $heading('home_company_title', 'About our company'),
        'body' => $plain('home_company_body'), 'image' => home_v2_image(home_v2_value('home_company_image', $post_id)),
        'link' => home_v2_link(home_v2_value('home_company_link', $post_id)),
    ];
    $sections = [];
    foreach (['delivery' => 'Manufacturing & delivery', 'quality' => 'Quality & documents'] as $key => $label) {
        $body = $plain('home_' . $key . '_body');
        $sections[$key] = ['title' => $heading('home_' . $key . '_title', $label), 'body' => $body, 'enabled' => has_rich_content($body), 'image' => home_v2_image(home_v2_value('home_' . $key . '_image', $post_id)), 'link' => home_v2_link(home_v2_value('home_' . $key . '_link', $post_id))];
    }
    $documents = [];
    foreach (home_v2_rows('home_documents', $post_id) as $row) {
        $id = (int) ($row['file'] ?? 0);
        $title = trim((string) ($row['title'] ?? ''));
        $url = $id && get_post_type($id) === 'attachment' ? wp_get_attachment_url($id) : false;
        if ($url && $title !== '') $documents[] = ['title' => $title, 'url' => $url];
    }
    // Document-only content is useful; a title or image alone does not create an empty section.
    $sections['quality']['enabled'] = $sections['quality']['enabled'] || (bool) $documents;
    $applications = [];
    foreach (home_v2_rows('home_application_items', $post_id) as $row) {
        $title = trim((string) ($row['title'] ?? ''));
        $text = trim((string) ($row['text'] ?? ''));
        $image = home_v2_image($row['image'] ?? 0);
        if ($title !== '' && ($image || $text !== '')) $applications[] = ['title' => $title, 'text' => $text, 'image' => $image, 'link' => home_v2_link($row['link'] ?? [])];
    }
    $process = [];
    foreach (home_v2_rows('home_process_items', $post_id) as $row) {
        $title = trim((string) ($row['title'] ?? ''));
        $text = trim((string) ($row['text'] ?? ''));
        if ($title !== '' && $text !== '') $process[] = ['title' => $title, 'text' => $text];
    }
    $articles = [];
    foreach ((array) home_v2_value('home_articles', $post_id) as $id) {
        $article = get_post((int) $id);
        if ($article && $article->post_type === 'post' && $article->post_status === 'publish') $articles[$article->ID] = $article;
    }
    $faq = [];
    foreach (home_v2_rows('home_faq_items', $post_id) as $row) {
        $question = trim((string) ($row['question'] ?? ''));
        $answer = trim((string) ($row['answer'] ?? ''));
        if ($question !== '' && $answer !== '') $faq[] = ['label' => $question, 'value' => $answer];
    }
    $inquiry = home_v2_value('home_show_inquiry', $post_id);
    return [
        'overline' => $plain('home_overline'), 'description' => $plain('home_description'),
        'clients' => $clients, 'proof' => $proof,
        'ranges' => $ranges, 'ranges_title' => $heading('applications_title', 'Explore our product ranges'),
        'company' => $company, 'sections' => $sections, 'documents' => $documents,
        'applications' => $applications, 'applications_title' => $heading('home_applications_title', 'Find equipment for your application'),
        'process' => $process, 'process_title' => $heading('home_process_title', 'How we work together'),
        'articles' => array_values($articles), 'articles_title' => $heading('home_articles_title', 'Buying guides & insights'),
        'faq' => $faq, 'faq_title' => $heading('home_faq_title', 'FAQs'),
        'inquiry' => $inquiry === null || (bool) $inquiry,
    ];
}

function home_v2_text_link(array $link): void {
    if (!$link) return;
    printf('<a class="home-text-link" href="%s"%s>%s <span aria-hidden="true">→</span></a>', esc_url($link['url']), $link['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '', esc_html($link['title']));
}

function home_v2_media_section(array $section, string $name, bool $reverse = false, array $documents = []): void {
    if (!$section['enabled']) return;
    printf('<section class="home-band home-%s%s"><div class="shell home-split%s%s">', esc_attr($name), $reverse ? ' home-band-gray' : '', $section['image'] ? ' has-media' : '', $reverse ? ' home-split-reverse' : '');
    if ($section['image']) echo '<div class="home-photo">' . wp_get_attachment_image($section['image'], 'large', false, ['loading' => 'lazy', 'decoding' => 'async']) . '</div>';
    echo '<div class="home-copy"><h2>' . esc_html($section['title']) . '</h2>';
    if ($section['body'] !== '') echo '<div class="prose">' . $section['body'] . '</div>'; // ACF escaped before formatting.
    if ($documents) {
        echo '<ul class="home-documents">';
        foreach ($documents as $document) printf('<li><a href="%s" target="_blank" rel="noopener">%s <span aria-hidden="true">↗</span><span class="screen-reader-text"> (opens in a new tab)</span></a></li>', esc_url($document['url']), esc_html($document['title']));
        echo '</ul>';
    }
    home_v2_text_link($section['link']);
    echo '</div></div></section>';
}

