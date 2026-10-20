<?php
namespace AxisTemplate\Attachments;
defined('ABSPATH') || exit;
final class Field extends \FluentForm\App\Services\FormBuilder\BaseFieldManager {
    public function __construct() { parent::__construct(Module::FIELD, 'Inquiry attachments', ['file', 'upload', 'attachment'], 'general'); }
    public function getComponent() { return Module::component(); }
    public function getGeneralEditorElements() { return ['label', 'admin_field_label']; }
    public function getAdvancedEditorElements() { return ['container_class']; }
    public function render($element, $form) {
        Module::assets();
        $id = $this->makeElementId($element, $form);
        echo '<div class="ff-el-group wb-inquiry-files ' . esc_attr($element['settings']['container_class'] ?? '') . '" data-upload-url="' . esc_url(admin_url('admin-ajax.php')) . '" data-upload-nonce="' . esc_attr(wp_create_nonce('wb-inquiry-upload')) . '" data-form-id="' . (int) $form->id . '">';
        echo '<div class="ff-el-input--label"><label for="' . esc_attr($id) . '">' . esc_html($element['settings']['label'] ?? 'Attachments (optional)') . '</label></div>';
        // Fluent Forms appends server errors to this container; never to the hidden token input.
        echo '<div class="ff-el-input--content">';
        echo '<label class="wb-file-control" for="' . esc_attr($id) . '"><span class="wb-file-choose">Choose files</span><span class="wb-file-selection" aria-live="polite">No files selected</span><input id="' . esc_attr($id) . '" class="wb-inquiry-file-picker" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple></label>';
        echo '<input type="hidden" name="' . Module::NAME . '" value=""><ul class="wb-file-list"></ul><p class="wb-file-status" role="status" aria-live="polite"></p><noscript>Enable JavaScript to attach files.</noscript></div></div>';
    }
}

