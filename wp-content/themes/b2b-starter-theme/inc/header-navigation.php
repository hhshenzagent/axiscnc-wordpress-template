<?php
namespace AxisCNC\HeaderNavigation;
defined('ABSPATH') || exit;

/** Reuse the configured provider and its language list, as one inline control. */
function language_switcher():void{
    if(!shortcode_exists('gtranslate'))return;
    echo '<div class="header-language notranslate" translate="no" role="group" aria-label="Website language"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c5 5 5 13 0 18-5-5-5-13 0-18Z"/></svg>';
    echo do_shortcode('[gtranslate widget_look="dropdown"]');
    echo '</div>';
}

add_action('wp',static function():void{
    if(!\AxisCNC\SuiteTheme\enabled())return;
    // The header shortcode owns the sole switcher on these native templates.
    remove_action('wp_footer','gtranslate_display_floating');
    remove_action('wp_footer','gtranslate_add_inline_selector');
});

add_action('wp_enqueue_scripts',static function():void{
    if(!\AxisCNC\SuiteTheme\enabled())return;
    $file='assets/css/header-navigation.css';
    wp_enqueue_style('axis-header-navigation',get_theme_file_uri($file),['axis-approved-design'],(string)filemtime(get_theme_file_path($file)));
},1006);
