<?php
/** AXISCNC template renderers. All business values are native fields/ACF. */
namespace AxisCNC\SuiteTheme;
defined('ABSPATH') || exit;

function enabled():bool{$template=(string)get_page_template_slug(get_queried_object_id());return (get_theme_mod('axis_suite_enabled')&&(is_singular('post')||(is_page()&&in_array($template,['','default'],true))))||(is_singular(array_merge(['page'],\AxisCNC\ContentTypes\types()))&&str_starts_with($template,'page-templates/axis-'));}
function value(string $name,$default='') { $v=function_exists('get_field')?get_field('axis_suite_'.$name):null;return $v===null||$v===false?$default:$v; }
function rows(string $name,array $required=['title']):array{
    $v=value($name,[]);if(!is_array($v))return [];
    return array_values(array_filter($v,static function($r)use($required){if(!is_array($r))return false;foreach($required as $k)if(!isset($r[$k])||!has_text($r[$k]))return false;return true;}));
}
function text($v):void{echo esc_html((string)$v);}
function public_caption($value):string{
    $caption=(string)$value;
    return preg_match('/\b(?:AI[\s\-–]+(?:generated|illustrative)|Illustrati(?:on|ve) only)/iu',wp_strip_all_tags($caption))?'':$caption;
}
function without_ai_notices(string $html):string{
    return (string)preg_replace_callback('/<(p|figcaption|small)\b[^>]*>(.*?)<\/\1>/isu',static function($match){
        $text=trim(wp_strip_all_tags($match[2]));
        $notice=preg_match('/(?:AI[\s\-–]+(?:generated|illustrative).*(?:concept|illustrat|photo|image)|(?:image|illustrat|views).{0,100}AI[\s\-–]+generated|Illustrative only)/iu',$text);
        return strlen($text)<700&&$notice?'':$match[0];
    },$html);
}
function has_text($v):bool{return trim(html_entity_decode(wp_strip_all_tags((string)$v),ENT_QUOTES|ENT_HTML5,'UTF-8')," \n\r\t\v\0\xc2\xa0")!=='';}
function has_image($id):bool{return (int)$id>0&&get_post_type((int)$id)==='attachment'&&wp_attachment_is_image((int)$id);}
function rich($v):void{echo wp_kses_post((string)$v);}
function image($id,string $size='large',array $attrs=[]):void{if((int)$id>0&&get_post_type((int)$id)==='attachment')echo wp_get_attachment_image((int)$id,$size,false,$attrs);}
function link($v,string $class='button'):void{if(!is_array($v)||empty($v['url'])||empty($v['title']))return;echo '<a class="'.esc_attr($class).'" href="'.esc_url($v['url']).'"'.(!empty($v['target'])?' target="_blank" rel="noopener"':'').'>'.esc_html($v['title']).'<span aria-hidden="true">↗</span></a>';}
function inquiry_url():string{return add_query_arg('context',rawurlencode((string)(value('context')?:get_the_title())),home_url('/contact/'));}
function heading(string $name,string $fallback=''):void{ $v=value($name,$fallback);if(trim((string)$v)!=='')echo '<h2>'.esc_html($v).'</h2>';}
function start():void{get_header('axis');echo '<main id="main" class="axis-suite-main">';}
function end():void{echo '</main>';get_footer('axis');}
function hero(bool $quote=false):void{
    $hasMedia=has_image(value('hero_image'))||$quote;echo '<section class="suite-hero shell'.(!$hasMedia?' is-text-only':'').'"><div><p class="eyebrow">';text(value('kicker'));echo '</p><h1>';echo nl2br(esc_html((string)(value('headline')?:get_the_title())));echo '</h1><p class="suite-lede">';text(value('intro'));echo '</p>';
    echo '<div class="actions">';link(value('cta'));if($quote)echo '<a class="text-link" href="'.esc_url(inquiry_url()).'">Discuss the requirements ↗</a>';echo '</div></div>';if($hasMedia)echo '<div class="suite-hero-media">';
    if(has_image(value('hero_image'))){echo '<figure>';image(value('hero_image'),'full',['loading'=>'eager']);if(public_caption(value('caption'))){echo '<figcaption>';text(public_caption(value('caption')));echo '</figcaption>';}echo '</figure>';}
    if($quote)echo '<div class="suite-quote-box"><span class="eyebrow">START A PROJECT</span><h2>Make your requirements reviewable.</h2><p>Share the application, current drawing, material and required quantity.</p><a class="button" href="'.esc_url(inquiry_url()).'">Request a quotation ↗</a><a href="'.esc_url(home_url('/contact/')).'">Contact AXISCNC →</a></div>';if($hasMedia)echo '</div>';echo '</section>';
    $highlights=rows('highlights',['title']);if($highlights){echo '<div class="suite-hero-facts shell"><ul>';foreach($highlights as$r){echo '<li><strong>';text($r['title']);echo '</strong>';if(!empty($r['body'])){echo '<span>';text($r['body']);echo '</span>';}echo '</li>';}echo '</ul></div>';}

}
function nav(array $items):void{if(!$items)return;echo '<nav class="suite-page-nav" aria-label="On this page"><div class="shell">';foreach($items as $id=>$label)echo '<a href="#'.esc_attr($id).'">'.esc_html($label).'</a>';echo '</div></nav>';}
function table(array $items):void{if(!$items)return;echo '<div class="suite-table-scroll" tabindex="0" role="region" aria-label="Technical requirements"><table><thead><tr><th scope="col">Requirement</th><th scope="col">Specification / review basis</th></tr></thead><tbody>';foreach($items as $r){echo '<tr><th scope="row">';text($r['title']);echo '</th><td>';text($r['body']);echo '</td></tr>';}echo '</tbody></table></div>';}
function step_list(array $items):void{echo '<ol class="suite-steps">';foreach($items as $i=>$r){echo '<li><span>'.esc_html(str_pad((string)($i+1),2,'0',STR_PAD_LEFT)).'</span><h3>';text($r['title']);echo '</h3><p>';text($r['body']??'');echo '</p>';if(!empty($r['output'])){echo '<div class="suite-output"><strong>Output</strong><p>';text($r['output']);echo '</p></div>';}echo '</li>';}echo '</ol>';}
function cards(array $items):void{echo '<div class="suite-cards">';foreach($items as $r){echo '<article>';if(!empty($r['image'])){echo '<figure>';image($r['image']);echo '</figure>';}echo '<div><h3>';text($r['title']);echo '</h3><p>';text($r['body']??'');echo '</p>';link($r['link']??null,'text-link');echo '</div></article>';}echo '</div>';}
function mini_cta():void{echo '<div class="suite-mini-cta"><p>Have a drawing or an open engineering question?</p><a class="button" href="'.esc_url(inquiry_url()).'">Review this project ↗</a></div>';}
function faq(array $items):void{if(!$items)return;echo '<section class="suite-section shell" id="questions"><p class="eyebrow">QUESTIONS BEFORE RELEASE</p><h2>Clear answers. A defined next step.</h2>';foreach($items as $r){echo '<details class="suite-faq"><summary>';text($r['title']);echo '<span aria-hidden="true">+</span></summary><p>';text($r['body']);echo '</p></details>';}echo '</section>';}
function related():void{ $ids=value('related',[]);if(is_page()&&in_array(get_post_field('post_name',get_queried_object_id()),['solutions','resources'],true))$ids=get_posts(['post_type'=>get_post_field('post_name',get_queried_object_id())==='solutions'?'axis_solution':'axis_resource','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','orderby'=>'menu_order title','order'=>'ASC']);$valid=[];foreach(is_array($ids)?$ids:[] as $id){$id=is_object($id)?$id->ID:(int)$id;if(get_post_status($id)==='publish')$valid[]=$id;}if(!$valid)return;echo '<section class="suite-section shell"><p class="eyebrow">CONTINUE YOUR REVIEW</p><h2>Connect your next decision.</h2><div class="suite-related">';foreach($valid as $id)echo '<a href="'.esc_url(get_permalink($id)).'"><strong>'.esc_html(get_the_title($id)).' ↗</strong><p>'.esc_html(get_the_excerpt($id)).'</p></a>';echo '</div></section>';}
function inquiry():void{
    $id=(int)value('inquiry_form');if($id<=0||!shortcode_exists('fluentform'))return;
    echo '<section class="suite-section suite-inquiry" id="inquiry"><div class="shell"><div><p class="eyebrow">LET’S TALK ABOUT YOUR PROJECT</p>';heading('inquiry_title');echo '<p>';text(value('inquiry_intro'));echo '</p></div><div class="suite-native-form" data-axis-context="'.esc_attr((string)(value('context')?:get_the_title())).'">';echo do_shortcode('[fluentform id="'.$id.'"]');echo '</div></div></section>';
}
function render_capability():void{
    $scope=rows('standards',['title','body']);$services=rows('services',['title','body']);$stages=rows('stages',['title','body']);$materials=rows('materials',['name','body']);$finishes=rows('finishes',['title','body']);$tolerances=rows('tolerances',['title','body']);$design=rows('design',['title','body']);$quality=rows('quality_checks',['title','body']);$questions=rows('faq',['title','body']);$case=has_text(value('case_body'));
    start();hero(false);$navigation=[];foreach(['scope'=>[$scope||$services,'Capabilities'],'process'=>[$stages,'Process'],'materials'=>[$materials,'Materials'],'finishes'=>[$finishes,'Finishes'],'tolerances'=>[$tolerances,'Tolerances'],'design'=>[$design,'Design guide'],'case'=>[$case,'Project evidence'],'quality'=>[$quality||has_text(value('quality_body')),'Quality'],'questions'=>[$questions,'FAQs'],'inquiry'=>[(int)value('inquiry_form')>0,'Request a quote']]as$id=>[$valid,$label])if($valid)$navigation[$id]=$label;/* Repeated capability jump navigation removed by client request. */
    if($scope||$services){echo '<section class="suite-section shell" id="scope"><p class="eyebrow">CAPABILITY & SCOPE</p>';heading('scope_heading');echo '<div class="suite-reading">';rich(value('scope_intro'));echo '</div>';table($scope);if($services){echo '<div class="suite-section-heading"><h2>Choose the process for your part.</h2></div>';cards($services);}mini_cta();echo '</section>';}
    if($stages){echo '<section class="suite-section light-band" id="process"><div class="shell">';heading('stages_heading');step_list($stages);mini_cta();echo '</div></section>';}
    if($materials){echo '<section class="suite-section shell" id="materials">';heading('materials_heading');echo '<div class="suite-material-picker" data-suite-tabs><div role="tablist" aria-label="Material choices">';foreach($materials as $i=>$r)echo '<button role="tab" id="suite-mt-'.$i.'" aria-controls="suite-mp-'.$i.'" aria-selected="'.($i?'false':'true').'" tabindex="'.($i?'-1':'0').'">'.esc_html($r['name']).'</button>';echo '</div>';foreach($materials as $i=>$r){echo '<div class="suite-material-panel'.(empty($r['image'])?' is-text-only':'').'" role="tabpanel" id="suite-mp-'.$i.'" aria-labelledby="suite-mt-'.$i.'"'.($i?' hidden':'').'>';if(!empty($r['image'])){echo '<figure>';image($r['image']);echo '</figure>';}echo '<div><span class="eyebrow">';text($r['family']??'');echo '</span><h3>';text($r['name']);echo '</h3><p>';text($r['body']);echo '</p><dl>';foreach(['grades'=>'Grade / condition','limitations'=>'What to confirm','cost'=>'Cost & timing']as$key=>$label)if(!empty($r[$key])){echo '<div><dt>'.$label.'</dt><dd>';text($r[$key]);echo '</dd></div>';}echo '</dl><a class="button" href="'.esc_url(add_query_arg('material',rawurlencode($r['name']),inquiry_url())).'">Discuss this material ↗</a></div></div>';}echo '</div></section>';}
    if($finishes){echo '<section class="suite-section light-band" id="finishes"><div class="shell">';heading('finishes_heading');cards($finishes);echo '</div></section>';}
    if($tolerances){echo '<section class="suite-section shell" id="tolerances"><p class="eyebrow">THE FINISHED PART</p><h2>Define the acceptance requirements.</h2>';table($tolerances);echo '</section>';}
    if($design){echo '<section class="suite-section light-band" id="design"><div class="shell">';heading('design_heading');cards($design);mini_cta();echo '</div></section>';}
    if($case){echo '<section class="suite-section shell suite-case'.(!has_image(value('case_image'))?' is-text-only':'').'" id="case"><div>';heading('case_heading');rich(value('case_body'));echo '</div>';if(has_image(value('case_image'))){echo '<figure>';image(value('case_image'));echo '</figure>';}echo '</section>';}
    if($quality||has_text(value('quality_body'))){echo '<section class="suite-section shell" id="quality">';heading('quality_heading');echo '<div class="suite-reading">';rich(value('quality_body'));echo '</div><div class="suite-quality-grid">';foreach($quality as $r){echo '<article><h3>';text($r['title']);echo '</h3><p>';text($r['body']);echo '</p></article>';}echo '</div></section>';}
    $gallery=value('gallery',[]);$gallery=array_filter(is_array($gallery)?$gallery:[],static fn($id)=>has_image($id));if($gallery){echo '<section class="suite-gallery shell" aria-label="Supporting images">';foreach($gallery as$id)image($id);echo '</section>';}
    faq($questions);related();inquiry();end();
}
function render_industry():void{
    $cases=rows('use_cases',['title','body']);$priorities=rows('highlights_industry',['title','body']);$stages=rows('stages',['title','body']);$quality=rows('quality_checks',['title','body']);$questions=rows('faq',['title','body']);$ids=value('service_pages',[]);$ids=array_values(array_filter(is_array($ids)?$ids:[],static fn($id)=>get_post_status((int)$id)==='publish'));
    start();echo '<div class="suite-industry '.esc_attr((string)value('industry_variant','program')).'"><div class="suite-industry-intro">';hero();echo '</div>';$navigation=[];if($cases)$navigation['applications']='Applications';if($ids)$navigation['services']='Services';if($stages)$navigation['program']='Program stages';if($quality||has_text(value('quality_body')))$navigation['quality']='Quality';if($questions)$navigation['questions']='FAQs';nav($navigation);
    if($priorities){echo '<section class="suite-section shell suite-priorities">';foreach($priorities as$r){echo '<article><h2>';text($r['title']);echo '</h2><p>';text($r['body']);echo '</p></article>';}echo '</section>';}
    if(has_text(value('scope_intro'))){echo '<section class="suite-section shell">';heading('scope_heading');echo '<div class="suite-reading">';rich(value('scope_intro'));echo '</div></section>';}
    if($ids){echo '<section class="suite-section light-band" id="services"><div class="shell">';heading('services_heading');echo '<div class="suite-cards">';foreach($ids as$id){echo '<article><a href="'.esc_url(get_permalink($id)).'">';if(has_image(get_field('axis_suite_hero_image',$id))){echo '<figure>';image(get_field('axis_suite_hero_image',$id));echo '</figure>';}echo '<div><h3>'.esc_html(get_the_title($id)).' ↗</h3><p>'.esc_html(get_the_excerpt($id)).'</p></div></a></article>';}echo '</div></div></section>';}
    if($cases){echo '<section class="suite-section shell" id="applications"><p class="eyebrow">APPLICATIONS IN CONTEXT</p><h2>Build around the operating requirement.</h2>';cards($cases);echo '</section>';}
    if($stages){echo '<section class="suite-section light-band" id="program"><div class="shell">';heading('stages_heading');step_list($stages);echo '</div></section>';}
    if($quality||has_text(value('quality_body'))){echo '<section class="suite-section shell" id="quality">';heading('quality_heading');rich(value('quality_body'));table($quality);echo '</section>';}
    faq($questions);related();inquiry();echo '</div>';end();
}
function render_capability_hub():void{
    start();hero();
    $families=['machining'=>'Machining','molding'=>'Molding','fabrication'=>'Fabrication','printing'=>'3D Printing','value-added'=>'Value-Added Services'];
    foreach($families as$key=>$label){$pages=get_posts(['post_type'=>['page','axis_capability'],'post_status'=>'publish','numberposts'=>-1,'orderby'=>'menu_order title','order'=>'ASC','meta_query'=>[['key'=>'_wp_page_template','value'=>['page-templates/axis-capability.php','page-templates/axis-approved-capability.php','page-templates/axis-capability-cnc.php','page-templates/axis-capability-molding.php','page-templates/axis-capability-sheet-metal.php','page-templates/axis-capability-printing.php'],'compare'=>'IN'],['key'=>'axis_suite_family','value'=>$key]]]);if(!$pages)continue;echo '<section class="suite-section '.($key==='molding'||$key==='printing'?'light-band':'').'"><div class="shell"><p class="eyebrow">MANUFACTURING CAPABILITIES</p><h2>'.esc_html($label).'</h2><div class="suite-cards">';foreach($pages as$p){echo '<article><a href="'.esc_url(get_permalink($p)).'">';if(has_image(get_field('axis_suite_hero_image',$p->ID))){echo '<figure>';image(get_field('axis_suite_hero_image',$p->ID));echo '</figure>';}echo '<div><h3>'.esc_html(get_the_title($p)).' ↗</h3><p>'.esc_html(get_the_excerpt($p)).'</p></div></a></article>';}echo '</div></div></section>';}
    inquiry();end();
}
add_action('wp_enqueue_scripts',static function():void{
    if(!enabled())return;
    // Keep navigation arrows as monochrome UI glyphs rather than emoji artwork.
    remove_action('wp_head','print_emoji_detection_script',7);
    remove_action('wp_print_styles','print_emoji_styles');
    wp_dequeue_script('wp-emoji-release');
    foreach(wp_styles()->queue as$handle)if(preg_match('/^(starter|axis-)/',$handle))wp_dequeue_style($handle);
    foreach(wp_scripts()->queue as$handle)if(preg_match('/^(starter-(nav|navigation|home|article)|axis-)/',$handle))wp_dequeue_script($handle);
    foreach(['base','content']as$name){$file='assets/css/axis-suite-'.$name.'.css';wp_enqueue_style('axis-suite-'.$name,get_theme_file_uri($file),[],(string)filemtime(get_theme_file_path($file)));}
    wp_enqueue_script('axis-suite-navigation',get_theme_file_uri('assets/js/axis-suite-navigation.js'),[],(string)filemtime(get_theme_file_path('assets/js/axis-suite-navigation.js')),true);
    wp_enqueue_script('axis-suite-content',get_theme_file_uri('assets/js/axis-suite-content.js'),[],(string)filemtime(get_theme_file_path('assets/js/axis-suite-content.js')),true);
    wp_enqueue_style('axis-suite-extra',get_theme_file_uri('assets/css/axis-suite-extra.css'),['axis-suite-content'],(string)filemtime(get_theme_file_path('assets/css/axis-suite-extra.css')));
    wp_enqueue_script('axis-suite-extra',get_theme_file_uri('assets/js/axis-suite-extra.js'),[],(string)filemtime(get_theme_file_path('assets/js/axis-suite-extra.js')),true);
    if(is_singular('post')){
        $file='assets/css/axis-suite-article.css';wp_enqueue_style('axis-suite-article',get_theme_file_uri($file),['axis-suite-extra'],(string)filemtime(get_theme_file_path($file)));
        $file='assets/js/axis-suite-article.js';wp_enqueue_script('axis-suite-article',get_theme_file_uri($file),['axis-suite-content'],(string)filemtime(get_theme_file_path($file)),true);
    }
    if(get_page_template_slug(get_queried_object_id())==='page-templates/axis-home.php'){
        foreach(['home','home-explorers','solutions']as$name)wp_enqueue_style('axis-suite-home-'.$name,get_theme_file_uri('assets/css/axis-suite-'.$name.'.css'),['axis-suite-base'],(string)filemtime(get_theme_file_path('assets/css/axis-suite-'.$name.'.css')));
        wp_enqueue_script('axis-suite-home',get_theme_file_uri('assets/js/axis-suite-home.js'),[],(string)filemtime(get_theme_file_path('assets/js/axis-suite-home.js')),true);
        wp_enqueue_script('axis-suite-home-switchers',get_theme_file_uri('assets/js/axis-suite-home-switchers.js'),[],(string)filemtime(get_theme_file_path('assets/js/axis-suite-home-switchers.js')),true);
    }
},999);
add_action('customize_register',static function($wp_customize):void{
    $wp_customize->add_setting('axis_suite_utility',['default'=>'','sanitize_callback'=>'sanitize_text_field']);
    $wp_customize->add_control('axis_suite_utility',['label'=>'AXISCNC · Header service line','section'=>'contact_info','type'=>'text']);
    $wp_customize->add_section('axis_brand_typography',['title'=>'AXISCNC · Typography / 字体','priority'=>35,'description'=>'Change the site font families. Page wording and images remain in Pages.']);
    $choices=['inter'=>'Inter · current brand font','system'=>'System sans serif','arial'=>'Arial / Helvetica','georgia'=>'Georgia · serif'];
    foreach(['body'=>'Body text / 正文字体','headings'=>'Headings / 标题字体']as$key=>$label){
        $setting='axis_suite_font_'.$key;
        $wp_customize->add_setting($setting,['default'=>'inter','sanitize_callback'=>static fn($v)=>in_array($v,['inter','system','arial','georgia'],true)?$v:'inter']);
        $wp_customize->add_control($setting,['label'=>$label,'section'=>'axis_brand_typography','type'=>'select','choices'=>$choices]);
    }
});
add_action('wp_head',static function():void{
    if(!enabled())return;
    $fonts=['inter'=>'Inter,Arial,Helvetica,sans-serif','system'=>'system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif','arial'=>'Arial,Helvetica,sans-serif','georgia'=>'Georgia,"Times New Roman",serif'];
    $body=$fonts[get_theme_mod('axis_suite_font_body','inter')]??$fonts['inter'];$headings=$fonts[get_theme_mod('axis_suite_font_headings','inter')]??$fonts['inter'];
    echo '<style id="axis-brand-typography">body.axis-suite,.axis-suite .site-header{font-family:'.$body.'}.axis-suite h1,.axis-suite h2,.axis-suite h3{font-family:'.$headings.'}</style>';
},1000);
