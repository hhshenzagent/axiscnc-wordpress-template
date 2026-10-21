<?php
namespace AxisCNC\SuiteTheme;
defined('ABSPATH') || exit;

function local_url(string $url):string{
    if(!$url)return '';
    if(str_starts_with($url,'#'))return $url;
    $parts=wp_parse_url($url);$host=$parts['host']??'';
    if(!empty($parts['scheme'])&&!in_array(strtolower($parts['scheme']),['http','https'],true))return $url;
    if(!$host||str_contains($host,'hostingersite.com')||in_array($host,['cnc.wordpress-template.top','127.0.0.1'],true)){
        $path=$parts['path']??'/';$map=['/resources/materials/'=>'/materials/','/capabilities/cnc-turning/'=>'/capabilities/cnc-machining/','/capabilities/5-axis-machining/'=>'/capabilities/cnc-machining/','/capabilities/process-planning/'=>'/solutions/','/applications/'=>'/industries/','/applications/industrial-automation/'=>'/industries/automotive/','/applications/fluid-systems/'=>'/industries/aerospace-defense/','/applications/custom-equipment/'=>'/industries/automotive/','/company/our-approach/'=>'/about/'];
        if(isset($map[$path]))$path=$map[$path];
        $applicationMap=['/applications/automation-equipment/'=>'/industries/automotive/','/applications/fluid-mechanical-systems/'=>'/industries/aerospace-and-defense/','/applications/product-development/'=>'/solutions/'];
        if(isset($applicationMap[$path]))$path=$applicationMap[$path];
        $path=str_replace('/industries/aerospace-defense/','/industries/aerospace-and-defense/',$path);
        return home_url('/'.ltrim($path,'/')).(!empty($parts['query'])?'?'.$parts['query']:'').(!empty($parts['fragment'])?'#'.$parts['fragment']:'');
    }return $url;
}
function render_home():void{
    start(); // Modules use their own native ACF identity.
    $definitions=json_decode(file_get_contents(WP_PLUGIN_DIR.'/starter-model/includes/axis-home-schema.json'),true);$visibility=[];$anchors=[];
    foreach($definitions as$definition){$visibility[$definition['name']]=$definition['axis_visibility_field'];$anchors[$definition['name']]=$definition['axis_anchor'];}
    $all=get_field('axis_home_modules');$jump=[];$visible=[];foreach(is_array($all)?$all:[]as$module){$layout=$module['acf_fc_layout'];if($layout==='product_inquiry'&&(int)($module['form_id']??0)<=0)continue;if(trim((string)($module[$visibility[$layout]??'']??''))!==''&&!empty($anchors[$layout])){$visible[]=$anchors[$layout];if(!empty($module['navigation_label']))$jump[$anchors[$layout]]=$module['navigation_label'];}}
    $GLOBALS['axis_home_known_anchors']=array_values(array_filter($anchors));$GLOBALS['axis_home_visible_anchors']=$visible;
    
    if(have_rows('axis_home_modules'))while(have_rows('axis_home_modules')){the_row();$layout=get_row_layout();
        $path=get_theme_file_path('parts/axis-home/'.$layout.'.php');
        if(!preg_match('/^[a-z_]+$/',$layout)||!is_file($path))continue;
        if(trim((string)get_sub_field($visibility[$layout]??''))==='')continue;
        if($layout==='product_inquiry'&&(int)get_sub_field('form_id')<=0)continue;        include $path;
    }
    end();
}
function home_anchor(string $anchor):string{
    $id=ltrim($anchor,'#');if(!in_array($id,$GLOBALS['axis_home_known_anchors']??[],true)||in_array($id,$GLOBALS['axis_home_visible_anchors']??[],true))return$anchor;
    $routes=['machining-capabilities'=>'/capabilities/','machining-solutions'=>'/solutions/','our-approach'=>'/about/','materials'=>'/materials/','applications'=>'/industries/','workflow'=>'/resources/workflow/','inspection'=>'/resources/inspection-handover/','customer-feedback'=>'/about/','buyer-resources'=>'/resources/','machining-faqs'=>'/resources/machining-faqs/','product-inquiry'=>'/contact/'];
    return home_url($routes[$id]??'/');
}
function record_cards(array $ids):void{
    $ids=array_filter($ids,static fn($id)=>get_post_status((int)$id)==='publish');if(!$ids)return;
    echo '<div class="suite-cards">';foreach($ids as$id){echo '<article><a href="'.esc_url(get_permalink($id)).'">';$img=metadata_exists('post',$id,'axis_suite_hero_image')?get_field('axis_suite_hero_image',$id):get_post_thumbnail_id($id);if(has_image($img)){echo '<figure>';image($img);echo '</figure>';}echo '<div><h3>'.esc_html(get_the_title($id)).' ↗</h3><p>'.esc_html(get_the_excerpt($id)).'</p></div></a></article>';}echo '</div>';
}
function render_solution():void{
    start();hero();$stages=rows('program_stages',['title','body']);$packages=rows('packages',['title','body']);
    if(has_text(value('program_intro'))){echo '<section class="suite-section shell suite-reading">';rich(value('program_intro'));echo '</section>';}
    foreach($stages as$i=>$s){echo '<section id="phase-'.($i+1).'" class="suite-section '.($i%2?'light-band':'').'"><div class="shell suite-phase'.(!has_image($s['image']??0)?' is-text-only':'').'">';if(has_image($s['image']??0)){echo '<figure>';image($s['image']);echo '</figure>';}echo '<div><p class="eyebrow">PHASE '.esc_html(str_pad((string)($i+1),2,'0',STR_PAD_LEFT)).'</p><h2>';text($s['title']);echo '</h2><p>';text($s['body']);echo '</p><dl class="suite-definition">';foreach(['inputs'=>'Inputs','work'=>'Work & decisions','output'=>'Release gate','boundary'=>'Scope & responsibility']as$key=>$label)if(!empty($s[$key])){echo '<div><dt>'.$label.'</dt><dd>';text($s[$key]);echo '</dd></div>';}echo '</dl><a class="button" href="'.esc_url(add_query_arg('phase',rawurlencode($s['title']),inquiry_url())).'">Discuss this phase ↗</a></div></div></section>';}
    if($packages){echo '<section id="packages" class="suite-section shell"><p class="eyebrow">SERVICE PACKAGE</p><h2>Choose the next useful commitment.</h2><div class="suite-cards">';foreach($packages as$p){echo '<article><div><h3>';text($p['title']);echo '</h3><p>';text($p['body']);echo '</p>';foreach(['scope'=>'Scope','gate'=>'Before release']as$key=>$label)if(has_text($p[$key]??'')){echo '<h4>'.$label.'</h4><p>';text($p[$key]);echo '</p>';}echo '</div></article>';}echo '</div></section>';}
    $ids=array_values(array_filter((array)value('industry_pages',[]),static fn($id)=>get_post_status((int)$id)==='publish'));if($ids){echo '<section class="suite-section shell"><h2>Keep the application in the brief.</h2>';record_cards($ids);echo '</section>';}faq(rows('faq',['title','body']));related();inquiry();end();
}
function render_materials():void{
    start();$images=array_values(array_filter((array)value('material_hero_gallery',[]),static fn($id)=>get_post_type((int)$id)==='attachment'));
    if(!metadata_exists('post',get_the_ID(),'axis_suite_material_hero_gallery')&&(int)value('hero_image')>0)$images=[(int)value('hero_image')];
    echo '<section class="suite-material-hero"><div class="shell'.(!$images?' is-text-only':'').'"><div><p class="eyebrow">';text(value('kicker'));echo '</p><h1>';text(value('headline')?:get_the_title());echo '</h1><p class="suite-lede">';text(value('intro'));echo '</p>';link(value('cta'));echo '</div>';
    if($images){echo '<figure class="suite-material-collage count-'.count($images).'">';foreach(array_slice($images,0,3)as$id)image($id,'large');if(public_caption(value('caption'))){echo '<figcaption>';text(value('caption'));echo '</figcaption>';}echo '</figure>';}
    echo '</div></section>';$groups=rows('material_groups',['title']); // Nested row validity is evaluated below.
    $groups=array_values(array_filter($groups,static fn($g)=>!empty(array_filter((array)$g['items'],static fn($r)=>has_text($r['name']??'')&&has_text($r['body']??'')))));
    if($groups){echo '<div class="shell suite-material-layout suite-material-layout--full"><div class="suite-material-catalog">';}
    if($groups)echo '<div class="suite-material-tools"><label for="material-search">Find a material</label><input id="material-search" type="search" placeholder="Search material, grade or application" data-material-search><p data-material-empty hidden>No matching materials. Try another term or ask about your requirements.</p></div>';
    foreach($groups as$i=>$g){echo '<section class="suite-section" id="material-group-'.$i.'" data-material-group><div class="suite-section-heading"><div><h2>';text($g['title']);echo '</h2><p>';text($g['intro']??'');echo '</p></div>';if(!empty($g['capability'])&&get_post_status((int)$g['capability'])==='publish')echo '<a class="text-link" href="'.esc_url(get_permalink($g['capability'])).'">Explore this process ↗</a>';echo '</div><div class="suite-table-scroll"><table class="suite-material-table"><thead><tr><th scope="col">Material</th><th scope="col">Description</th><th scope="col">Cost & quotation</th></tr></thead><tbody>';foreach((array)$g['items']as$r){if(!has_text($r['name']??'')||!has_text($r['body']??''))continue;echo '<tr data-material-row><th scope="row">';image($r['image']??0,'medium');text($r['name']);echo '</th><td><p>';text($r['body']);echo '</p>';if(!empty($r['grades'])){echo '<small>';text($r['grades']);echo '</small>';}if(!empty($r['limits'])){echo '<p class="suite-material-limit"><strong>Confirm: </strong>';text($r['limits']);echo '</p>';}echo '</td><td><p>';text($r['cost']??'');echo '</p><a class="text-link" href="'.esc_url(add_query_arg('material',rawurlencode($r['name']),inquiry_url())).'">Discuss material ↗</a></td></tr>';}echo '</tbody></table></div></section>';}
    if($groups)echo '</div></div>';faq(rows('faq',['title','body']));inquiry();end();
}
function render_about():void{
    start();$images=array_slice(array_values(array_filter((array)value('about_hero_gallery',[]),static fn($id)=>get_post_type((int)$id)==='attachment')),0,4);
    echo '<section class="suite-about-hero'.(!$images?' is-text-only':'').'"><div class="shell"><div class="suite-about-intro"><p class="eyebrow">';text(value('kicker'));echo '</p><h1>';text(value('headline')?:get_the_title());echo '</h1><p class="suite-lede">';text(value('intro'));echo '</p><div class="suite-about-actions">';link(value('cta'));if(value('about_video'))echo '<a class="text-link" href="'.esc_url(value('about_video')).'">Watch our company video ↗</a>';echo '</div></div>';
    if($images){echo '<figure class="suite-about-collage count-'.count($images).'">';foreach($images as$id)image($id,'large');if(public_caption(value('caption'))){echo '<figcaption>';text(value('caption'));echo '</figcaption>';}echo '</figure>';}echo '</div></section>';
    if(has_text(value('company_story'))){echo '<section class="suite-section shell suite-phase'.(!has_image(value('story_image'))?' is-text-only':'').'">';if(has_image(value('story_image'))){echo '<figure>';image(value('story_image'));if(public_caption(value('story_caption'))){echo '<figcaption>';text(value('story_caption'));echo '</figcaption>';}echo '</figure>';}echo '<div>';rich(value('company_story'));echo '</div></section>';}
    $milestones=rows('milestones',['date','title','body']);if($milestones){echo '<section class="suite-section shell"><h2>Company milestones</h2><ol class="suite-company-timeline">';foreach($milestones as$m){echo '<li><strong>';text($m['date']);echo '</strong><h3>';text($m['title']);echo '</h3><p>';text($m['body']);echo '</p></li>';}echo '</ol></section>';}
    $values=rows('values',['title','body']);if($values){echo '<section class="suite-section light-band"><div class="shell suite-values">';foreach($values as$v){echo '<article>';if(!empty($v['image'])){echo '<figure>';image($v['image'],'large');echo '</figure>';}echo '<div><h2>';text($v['title']);echo '</h2><p>';text($v['body']);echo '</p></div></article>';}echo '</div></section>';}
    $ids=array_values(array_filter((array)value('about_capabilities',[]),static fn($id)=>get_post_status((int)$id)==='publish'));if($ids){echo '<section class="suite-section shell"><h2>A process for the requirement.</h2>';record_cards($ids);echo '</section>';}
    $stages=rows('stages',['title','body']);if($stages){echo '<section class="suite-section light-band"><div class="shell">';heading('stages_heading');step_list($stages);echo '</div></section>';}
    $checks=rows('quality_checks',['title','body']);if($checks||has_text(value('quality_body'))){echo '<section class="suite-section shell">';heading('quality_heading');rich(value('quality_body'));table($checks);echo '</section>';}related();inquiry();end();
}
function render_contact():void{
    start();hero();inquiry(rows('contact_channels',['title','body']));
    $checklist=rows('contact_checklist',['title','body']);if($checklist){echo '<section class="suite-section light-band"><div class="shell"><h2>Prepare a useful first conversation.</h2>';table($checklist);echo '</div></section>';}faq(rows('faq',['title','body']));end();
}
function article_card(\WP_Post $p,string $heading='h3'):void{
    $heading=$heading==='h2'?'h2':'h3';echo '<article class="suite-blog-card"><a href="'.esc_url(get_permalink($p)).'">';if(has_post_thumbnail($p)){echo '<figure>';image(get_post_thumbnail_id($p),'large');echo '</figure>';}echo '<div><p class="eyebrow">'.esc_html(implode(' · ',wp_get_post_categories($p->ID,['fields'=>'names']))).'</p><'.$heading.'>'.esc_html($p->post_title).'</'.$heading.'><p>'.esc_html(get_the_excerpt($p)).'</p><time datetime="'.esc_attr(get_the_date('c',$p)).'">'.esc_html(get_the_date('M j, Y',$p)).'</time><span class="text-link">Read article</span></div></a></article>';
}
function render_blog():void{
    start();echo '<div class="ar-blog-index"><div class="light-band"><div class="shell ar-breadcrumb" style="padding-block:14px">Home / Blog</div></div>';
    $cat=absint($_GET['topic']??0);$search=sanitize_text_field(wp_unslash($_GET['article_search']??''));$page=max(1,absint($_GET['article_page']??1));$size=min(24,max(3,(int)value('blog_page_size',9)));$allowed=array_map('intval',(array)value('blog_categories',[]));
    if($cat&&$allowed&&!in_array($cat,$allowed,true))$cat=0;
    $base=['post_type'=>'post','post_status'=>'publish','ignore_sticky_posts'=>true];if($allowed)$base['category__in']=$allowed;
    echo '<section class="suite-section shell" id="articles"><div class="ar-blog-tools"><nav class="suite-topic-nav" aria-label="Article topics"><a '.(!$cat?'aria-current="page" ':'').'href="'.esc_url(get_permalink()).'">All</a>';
    $terms=get_terms(['taxonomy'=>'category','hide_empty'=>true,'include'=>$allowed]);if(!is_wp_error($terms))foreach($terms as$t)echo '<a '.($cat===$t->term_id?'aria-current="page" ':'').'href="'.esc_url(add_query_arg('topic',$t->term_id,get_permalink())).'">'.esc_html($t->name).'</a>';echo '</nav>';
    echo '<form class="suite-blog-search" method="get" action="'.esc_url(get_permalink()).'"><label for="article-search">Search articles</label><div><input id="article-search" name="article_search" type="search" value="'.esc_attr($search).'" placeholder="Search manufacturing guidance"><button class="button" type="submit">Search</button></div>';if($cat)echo '<input type="hidden" name="topic" value="'.$cat.'">';echo '</form></div>';
    $args=array_merge($base,['posts_per_page'=>$size,'paged'=>$page]);if($search)$args['s']=$search;if($cat){unset($args['category__in']);$args['cat']=$cat;}$q=new \WP_Query($args);
    echo '<div class="ar-blog-title"><h1>Latest insights</h1><span>'.(int)$q->found_posts.' articles</span></div>';if($q->have_posts()){echo '<div class="suite-blog-grid">';foreach($q->posts as$p)article_card($p,'h2');echo '</div><nav class="suite-pagination" aria-label="Article pages">';for($i=1;$i<=$q->max_num_pages;$i++)echo '<a '.($i===$page?'aria-current="page" ':'').'href="'.esc_url(add_query_arg(['article_page'=>$i,'topic'=>$cat?:false,'article_search'=>$search?rawurlencode($search):false],get_permalink())).'">'.$i.'</a>';echo '</nav>';}else echo '<p>No published articles in this topic yet.</p>';echo '</section></div>';end();
}
function render_industry_hub():void{start();hero();$ids=get_posts(['post_type'=>['page','starter_industry'],'post_status'=>'publish','fields'=>'ids','numberposts'=>-1,'meta_key'=>'_wp_page_template','meta_value'=>'page-templates/axis-industry.php']);if($ids){echo '<section class="suite-section shell"><h2>Start with the application requirements.</h2>';record_cards($ids);echo '</section>';}inquiry();end();}
function render_guide():void{start();hero();echo '<article class="suite-section shell suite-guide-body">';if(value('guide_note')){echo '<aside class="suite-note">';text(value('guide_note'));echo '</aside>';}while(have_posts()){the_post();the_content();}echo '</article>';faq(rows('faq',['title','body']));related();inquiry();end();}
function render_article():void{require_once __DIR__.'/axis-suite-article.php';render_article_reading();}
add_filter('template_include',static function(string $file):string{if(is_singular('post')&&get_theme_mod('axis_suite_enabled'))return get_theme_file_path('single-axis.php');return $file;},99);
// The original Starter industry CPT also owns /industries/. Prefer only an
// explicitly assigned native AXISCNC Page at the exact same path; other historical
// CPT URLs keep their existing renderer. Newly published template Pages work too.
add_action('parse_request',static function(\WP $wp):void{
    if(!get_theme_mod('axis_suite_enabled'))return;
    $path=trim((string)$wp->request,'/');if($path!=='industries'&&!str_starts_with($path,'industries/'))return;
    $p=get_page_by_path($path);if(!$p||$p->post_status!=='publish')return;
    if(!in_array(get_page_template_slug($p),['page-templates/axis-industry.php','page-templates/axis-industry-hub.php'],true))return;
    $wp->query_vars=['page_id'=>(int)$p->ID];
},20);
