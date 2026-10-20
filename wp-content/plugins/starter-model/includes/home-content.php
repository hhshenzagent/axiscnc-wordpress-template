<?php
/** Complete Home editor. Existing field identities and the four layout values are preserved. */
namespace Starter\Model;
defined('ABSPATH') || exit;
require_once __DIR__ . '/home-support.php';

function register_home_fields(callable $field, callable $tab): void {
    $rich = ['tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1, 'delay' => 0];
    $image = ['return_format' => 'id', 'preview_size' => 'medium'];
    $corporate = [[['field' => 'field_home_template', 'operator' => '==', 'value' => 'corporate']]];
    $only = static function (array $definition) use ($corporate): array {
        $definition['conditional_logic'] = $corporate;
        return $definition;
    };
    $fields = [
        $tab('field_home_tab_hero', 'tab_home_hero', 'Hero'),
        $field('field_home_template', 'home_template', 'Homepage layout', 'select', [
            'choices' => ['corporate' => 'Default manufacturer homepage', 'product-led' => 'Product-led catalogue', 'conversion' => 'Conversion / RFQ first', 'industrial' => 'Industrial capability first'],
            'default_value' => 'corporate', 'instructions' => 'Choose the homepage presentation. Existing layouts and saved content are retained.',
        ]),
        $field('field_home_overline', 'home_overline', 'Hero eyebrow', 'text'),
        $field('field_home_description', 'home_description', 'Hero description', 'textarea', ['rows' => 4, 'instructions' => 'The page title is the live heading. For the default layout, the featured image fills the banner. Choose a wide photograph with quiet dark space on the left for white text and the subject on the right.']),
        $tab('field_home_tab_sections', 'tab_home_sections', 'Product ranges'),
        $field('field_home_show_apps', 'show_applications', 'Show product ranges', 'true_false', ['default_value' => 1]),
        $field('field_home_apps_title', 'applications_title', 'Product ranges title', 'text'),
        $field('field_home_show_products', 'show_featured_products', 'Show featured products', 'true_false', ['default_value' => 0, 'instructions' => 'Optional separate product selection.']),
        $field('field_home_products_title', 'featured_products_title', 'Products title', 'text'),
        $field('field_home_products_count', 'featured_products_count', 'Products count', 'number', ['default_value' => 6, 'min' => 1, 'max' => 12]),
        $tab('field_home_tab_company', 'tab_home_company', 'Company introduction'),
        $field('field_home_company_title', 'home_company_title', 'Section title', 'text'),
        $field('field_home_company_body', 'home_company_body', 'Company introduction', 'wysiwyg', $rich + ['instructions' => 'Coherent company copy with verified facts. Leave empty to hide this module.']),
        $field('field_home_company_image', 'home_company_image', 'Company image', 'image', $image),
        $field('field_home_company_link', 'home_company_link', 'About page link', 'link', ['return_format' => 'array']),
    ];

    $fields = array_merge($fields, [
        $only($tab('field_axis_capability_tab', 'tab_axis_capability', 'CNC 手风琴 / CNC accordion')),
        $only($field('field_axis_capability_eyebrow', 'axis_capability_eyebrow', '小标题 / Eyebrow', 'text')),
        $only($field('field_axis_capability_title', 'axis_capability_title', '区域标题 / Section title', 'text')),
        $only($field('field_axis_capability_intro', 'axis_capability_intro', '区域简介 / Introduction', 'textarea', ['rows' => 3])),
        $only($field('field_axis_capability_items', 'axis_capability_items', '切换项目 / Accordion items', 'repeater', [
            'layout' => 'block', 'min' => 0, 'max' => 8, 'collapsed' => 'field_axis_capability_label', 'button_label' => '添加项目 / Add capability',
            'instructions' => '每个项目的图片可独立替换：点击图片右上角 × 移除旧图，再点 Add Image 选择媒体库图片或上传新图，最后点击页面 Update。可以拖动排序或删除项目。按钮名称和右侧标题均有内容时才显示；删除全部项目后隐藏此区域。',
            'sub_fields' => [
                $field('field_axis_capability_label', 'label', '左侧按钮名称 / Button label', 'text'),
                $field('field_axis_capability_summary', 'summary', '简短说明 / Short summary', 'text'),
                $field('field_axis_capability_headline', 'headline', '右侧标题 / Panel headline', 'text'),
                $field('field_axis_capability_description', 'description', '展开说明 / Description', 'textarea', ['rows' => 3]),
                $field('field_axis_capability_image', 'image', '项目图片 / Image', 'image', $image + ['instructions' => '选择或上传此项目的图片。建议横向或方形产品图；每个项目单独保存，不影响首页 banner 或其他图片。']),
                $field('field_axis_capability_caption', 'image_caption', '图片说明 / Image caption', 'text', ['instructions' => 'AI 图片请保留 AI concept 标记；真实图片可以改为实际产品说明。']),
                $field('field_axis_capability_cta', 'cta', '按钮文字和链接 / Call to action', 'link', ['return_format' => 'array']),
            ],
        ])),
    ]);

    foreach (['delivery' => 'Manufacturing & delivery', 'quality' => 'Quality & documents'] as $name => $label) {
        $scope = $name === 'quality' ? static function(array $definition): array { $definition['conditional_logic'] = [[['field' => 'field_home_template', 'operator' => '==', 'value' => '__legacy_quality']]]; return $definition; } : $only;
        $fields[] = $scope($tab('field_home_tab_' . $name, 'tab_home_' . $name, $label));
        $fields[] = $scope($field('field_home_' . $name . '_title', 'home_' . $name . '_title', 'Section title', 'text'));
        $fields[] = $scope($field('field_home_' . $name . '_body', 'home_' . $name . '_body', 'Content', 'wysiwyg', $rich + ['instructions' => 'Use verified information. Clear the content to hide the entire section.']));
        $fields[] = $scope($field('field_home_' . $name . '_image', 'home_' . $name . '_image', 'Photograph', 'image', $image));
        $fields[] = $scope($field('field_home_' . $name . '_link', 'home_' . $name . '_link', 'Optional page link', 'link', ['return_format' => 'array']));
        if ($name === 'quality') $fields[] = $scope($field('field_home_documents', 'home_documents', 'Documents', 'repeater', [
            'layout' => 'row', 'button_label' => 'Add document', 'collapsed' => 'field_home_document_title',
            'instructions' => 'Add approved documents. Both label and file are required to display a link. A sample PDF is not a certificate.',
            'sub_fields' => [
                $field('field_home_document_title', 'title', 'Link label', 'text'),
                $field('field_home_document_file', 'file', 'File', 'file', ['return_format' => 'id']),
            ],
        ]));
    }
    $fields = array_merge($fields, [
        $only($tab('field_home_tab_clients', 'tab_home_clients', 'Company evidence')),
        $only($field('field_home_proof_title', 'home_proof_title', 'Banner title', 'text')),
        $only($field('field_home_proof_intro', 'home_proof_intro', 'Brief introduction', 'textarea', ['rows' => 3, 'instructions' => 'A concise introduction to your company capability. Optional; a title or background alone does not display an empty banner.'])),
        $only($field('field_home_proof_image', 'home_proof_image', 'Decorative background', 'image', $image + ['instructions' => 'Choose a very pale wide image so the live text and logos stay readable. This image contains no company figures or text.'])),
        $only($field('field_home_proof_source', 'home_proof_source', 'Shared company information', 'message', [
            'message' => 'Add or remove factory facts and certifications in <a href="' . esc_url(admin_url('edit.php?post_type=starter_profile')) . '">Business Profile</a>. Home displays those same saved values in the evidence banner after Quality & documents. Use verified figures and certifications only; empty or incomplete content is omitted.',
            'esc_html' => 0,
        ])),
        $only($field('field_home_show_facts', 'home_show_facts', 'Show factory facts', 'true_false', ['default_value' => 1])),
        $only($field('field_home_show_certifications', 'home_show_certifications', 'Show certifications', 'true_false', ['default_value' => 1])),
        $only($field('field_home_clients', 'home_clients', 'Client logos', 'repeater', [
            'layout' => 'row', 'button_label' => 'Add logo', 'collapsed' => 'field_home_client_name',
            'instructions' => 'Upload authorized client logos, reorder or remove them. Links are optional. Demo artwork must identify itself as a placeholder. If the introduction, logos, facts and certifications are all empty, the entire banner is hidden.',
            'sub_fields' => [
                $field('field_home_client_name', 'name', 'Client name', 'text'),
                $field('field_home_client_image', 'image', 'Logo', 'image', $image),
                $field('field_home_client_url', 'url', 'Optional website', 'url'),
            ],
        ])),
        $only($tab('field_home_tab_applications', 'tab_home_applications', 'Applications')),
        $only($field('field_home_applications_title', 'home_applications_title', 'Section title', 'text')),
        $only($field('field_home_application_items', 'home_application_items', 'Applications', 'repeater', [
            'layout' => 'block', 'button_label' => 'Add application', 'collapsed' => 'field_home_application_title',
            'instructions' => 'Add, reorder or remove application cards. A title plus image or useful description is required.',
            'sub_fields' => [
                $field('field_home_application_title', 'title', 'Title', 'text'),
                $field('field_home_application_image', 'image', 'Image', 'image', $image),
                $field('field_home_application_text', 'text', 'Description', 'textarea', ['rows' => 3]),
                $field('field_home_application_link', 'link', 'Optional page link', 'link', ['return_format' => 'array']),
            ],
        ])),
        $only($tab('field_home_tab_process', 'tab_home_process', 'Cooperation')),
        $only($field('field_home_process_title', 'home_process_title', 'Section title', 'text')),
        $only($field('field_home_process_items', 'home_process_items', 'Cooperation steps', 'repeater', [
            'layout' => 'row', 'button_label' => 'Add step', 'collapsed' => 'field_home_process_label',
            'instructions' => 'Add, remove and reorder steps. Only a complete title and description appears.',
            'sub_fields' => [
                $field('field_home_process_label', 'title', 'Step title', 'text'),
                $field('field_home_process_text', 'text', 'Description', 'textarea', ['rows' => 3]),
            ],
        ])),
        $only($tab('field_home_tab_articles', 'tab_home_articles', 'Articles')),
        $only($field('field_home_articles_title', 'home_articles_title', 'Section title', 'text')),
        $only($field('field_home_articles', 'home_articles', 'Selected articles', 'relationship', ['post_type' => ['post'], 'return_format' => 'id', 'filters' => ['search'], 'instructions' => 'Choose existing blog articles and drag to reorder. Only published articles appear. Empty selection hides the section.'])),
        $only($tab('field_home_tab_faq', 'tab_home_faq', 'FAQs & inquiry')),
        $only($field('field_home_faq_title', 'home_faq_title', 'Section title', 'text')),
        $only($field('field_home_faq_items', 'home_faq_items', 'Frequently asked questions', 'repeater', [
            'layout' => 'row', 'button_label' => 'Add question', 'collapsed' => 'field_home_faq_question',
            'instructions' => 'Complete question and answer pairs appear. Remove the last item to hide the whole FAQ.',
            'sub_fields' => [
                $field('field_home_faq_question', 'question', 'Question', 'text'),
                $field('field_home_faq_answer', 'answer', 'Answer', 'textarea', ['rows' => 4]),
            ],
        ])),
        $only($field('field_home_show_inquiry', 'home_show_inquiry', 'Show inquiry form', 'true_false', ['default_value' => 1, 'instructions' => 'Uses the shared site inquiry form, including its saved fields and delivery settings.'])),
        array_merge($tab('field_home_tab_cta', 'tab_home_cta', 'Inquiry'), ['conditional_logic' => [[['field' => 'field_home_template', 'operator' => '!=', 'value' => 'corporate']]]]),
        $field('field_home_cta_title', 'home_cta_title', 'Inquiry heading', 'text', ['conditional_logic' => [[['field' => 'field_home_template', 'operator' => '!=', 'value' => 'corporate']]]]),
        $field('field_home_cta_text', 'home_cta_text', 'Inquiry introduction', 'textarea', ['rows' => 3, 'conditional_logic' => [[['field' => 'field_home_template', 'operator' => '!=', 'value' => 'corporate']]]]),
        $field('field_home_cta_button', 'home_cta_button', 'CTA button for other layouts', 'text', ['conditional_logic' => [[['field' => 'field_home_template', 'operator' => '!=', 'value' => 'corporate']]]]),
        $field('field_home_apps_count', 'applications_count', 'Applications count', 'number', ['default_value' => 12, 'min' => 1, 'max' => 12]),
        $field('field_home_show_industries', 'show_industries', 'Show industries', 'true_false', ['default_value' => 0]),
        $field('field_home_industries_title', 'industries_title', 'Industries title', 'text'),
        $field('field_home_industries_count', 'industries_count', 'Industries count', 'number', ['default_value' => 3, 'min' => 1, 'max' => 12]),
        $field('field_home_show_guides', 'show_guides', 'Show guides', 'true_false', ['default_value' => 0]),
        $field('field_home_guides_title', 'guides_title', 'Guides title', 'text'),
        $field('field_home_guides_count', 'guides_count', 'Guides count', 'number', ['default_value' => 3, 'min' => 1, 'max' => 12]),
    ]);
    $support_index = array_search('field_home_tab_faq', array_column($fields, 'key'), true);
    if ($support_index === false) $support_index = count($fields);
    array_splice($fields, $support_index, 0, home_support_fields($field, $tab));
    acf_add_local_field_group([
        'key' => 'group_home_content', 'title' => 'Homepage content & sections', 'show_in_rest' => true,
        'label_placement' => 'top', 'instruction_placement' => 'label', 'fields' => $fields,
        'location' => [[['param' => 'page_type', 'operator' => '==', 'value' => 'front_page']]],
    ]);
}

