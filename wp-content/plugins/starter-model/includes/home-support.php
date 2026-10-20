<?php
/** Editable, illustration-first service roles in the complete Home editor. */
namespace Starter\Model;
defined('ABSPATH') || exit;

function home_support_fields(callable $field, callable $tab): array {
    $person = static function (string $prefix) use ($field): array {
        return [
            $field('field_' . $prefix . '_name', 'name', '姓名 / Name (optional)', 'text', ['instructions' => '只填写已确认的真实姓名。示意人物留空，前台以岗位名称展示。']),
            $field('field_' . $prefix . '_role', 'role', '岗位 / Role', 'text', ['instructions' => '岗位为空时，该人物不展示。']),
            $field('field_' . $prefix . '_portrait', 'portrait', '人物图片 / Portrait', 'image', ['return_format' => 'id', 'preview_size' => 'medium', 'instructions' => '× 移除旧图 → Add Image 选择或上传 → 页面 Update。建议透明背景职业半身图；真实图片须经本人授权。']),
            $field('field_' . $prefix . '_bio', 'bio', '履历或岗位介绍 / Biography or role overview', 'textarea', ['rows' => 4, 'instructions' => '示意人物只介绍岗位工作，不虚构任职公司、客户、学历或服务经历。真实履历请经本人确认。']),
            $field('field_' . $prefix . '_focus', 'focus', '服务方向 / Focus', 'text'),
            $field('field_' . $prefix . '_years', 'years', '真实服务年限 / Verified years (optional)', 'number', ['min' => 0, 'max' => 60, 'step' => 1, 'instructions' => '示意人物留空。只有资料核实且勾选下项时才显示，人物外貌年龄不是服务年限。']),
            $field('field_' . $prefix . '_verified', 'experience_verified', '已核实服务年限 / Experience verified', 'true_false', ['default_value' => 0, 'instructions' => '仅在真实资料确认后勾选；AI 示意图仍需说明来源。']),
        ];
    };
    $fields = [
        $tab('field_axis_support_tab', 'tab_axis_support', '客服团队 / Project support'),
        $field('field_axis_support_eyebrow', 'axis_support_eyebrow', '小标题 / Eyebrow', 'text'),
        $field('field_axis_support_title', 'axis_support_title', '模块标题 / Section title', 'text', ['instructions' => '删除全部有效人物后，整个区域自动隐藏。']),
        $field('field_axis_support_intro', 'axis_support_intro', '简介 / Introduction', 'textarea', ['rows' => 2]),
        $field('field_axis_support_disclosure', 'axis_support_disclosure', '示意人物说明 / Illustration disclosure', 'text', ['instructions' => '使用 AI 人物或示意岗位时保留说明；全部替换为已确认资料后才调整。']),
        $field('field_axis_support_lead', 'axis_support_lead', '客服负责人 / Lead role', 'group', ['layout' => 'block', 'sub_fields' => array_merge($person('axis_support_lead'), [
            $field('field_axis_support_lead_headline', 'headline', '负责人介绍标题 / Lead headline', 'text', ['instructions' => '可填写简短服务理念；留空时显示姓名或岗位。']),
        ])]),
        $field('field_axis_support_stages', 'axis_support_stages', '服务阶段 / Support stages', 'repeater', [
            'layout' => 'table', 'min' => 0, 'max' => 3, 'button_label' => '添加阶段 / Add stage',
            'instructions' => '阶段标题与说明完整时才展示，不填写虚构的业务成绩。',
            'sub_fields' => [
                $field('field_axis_support_stage_title', 'title', '阶段标题 / Title', 'text'),
                $field('field_axis_support_stage_text', 'text', '服务重点 / Description', 'text'),
            ],
        ]),
        $field('field_axis_support_members', 'axis_support_members', '服务岗位卡片 / Support roles', 'repeater', [
            'layout' => 'block', 'min' => 0, 'max' => 6, 'collapsed' => 'field_axis_support_member_role', 'button_label' => '添加岗位 / Add role',
            'instructions' => '可增删和拖动排序，岗位为空的行不显示。删除负责人岗位及全部卡片岗位后整个区域隐藏，不恢复旧示例。',
            'sub_fields' => $person('axis_support_member'),
        ]),
        $field('field_axis_support_cta', 'axis_support_cta', '询盘按钮 / Inquiry link', 'link', ['return_format' => 'array', 'instructions' => '例如首页 #product-inquiry；清空按钮不会影响原有询盘表单。']),
    ];
    return array_map(static function (array $definition): array {
        $definition['conditional_logic'] = [[['field' => 'field_home_template', 'operator' => '==', 'value' => 'corporate']]];
        return $definition;
    }, $fields);
}
