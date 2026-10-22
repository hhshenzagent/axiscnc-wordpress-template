<?php
namespace AxisCNC\QuoteExperience;
defined('ABSPATH') || exit;

/** Keep one native form instance; the dialog borrows its DOM and event bindings. */
add_filter('do_shortcode_tag',static function($html,$tag,$attributes){
    if($tag==='fluentform'&&(int)($attributes['id']??0)===(int)get_option('starter_rfq_form_id'))$GLOBALS['axis_quote_native_rendered']=true;
    return $html;
},20,3);

function dialog():void{
    $id=(int)get_option('starter_rfq_form_id');
    if($id<=0||!shortcode_exists('fluentform'))return;
    echo '<dialog id="axis-rfq-dialog" class="axis-rfq-dialog" aria-labelledby="axis-rfq-title" data-form-id="'.esc_attr($id).'"><div class="axis-rfq-head"><div><p class="eyebrow">LET’S TALK ABOUT YOUR PART</p><h2 id="axis-rfq-title">Request a quote</h2><p>Share your drawing, material and quantity.</p></div><button type="button" class="axis-rfq-close" aria-label="Close inquiry">×</button></div><div id="axis-rfq-slot">';
    if(empty($GLOBALS['axis_quote_native_rendered']))echo do_shortcode('[fluentform id="'.$id.'"]');
    echo '</div>';
    if(get_option('axis_demo_mode'))echo '<p class="axis-demo-note">Demo: inquiries are saved in WordPress. Email delivery is not connected.</p>';
    echo '</dialog>';
}

add_action('wp_enqueue_scripts',static function():void{
    if(!\AxisCNC\SuiteTheme\enabled())return;
    foreach(['css'=>'style','js'=>'script']as$ext=>$kind){
        $file='assets/'.$ext.'/quote-experience.'.$ext;
        if($kind==='style')wp_enqueue_style('axis-quote-experience',get_theme_file_uri($file),['axis-approved-design'],(string)filemtime(get_theme_file_path($file)));
        else wp_enqueue_script('axis-quote-experience',get_theme_file_uri($file),[],(string)filemtime(get_theme_file_path($file)),true);
    }
},1005);

add_filter('wp_get_attachment_caption',static fn($caption)=>is_admin()?$caption:\AxisCNC\SuiteTheme\public_caption($caption),99);
add_filter('the_content',static function($content){return is_admin()?$content:\AxisCNC\SuiteTheme\without_ai_notices((string)$content);},25);
