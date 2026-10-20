<?php
/** The approved layouts render native fields; no mockup bitmap is used as a page. */
namespace AxisCNC\ApprovedTheme;
defined('ABSPATH') || exit;
function valid($v):bool{return \AxisCNC\SuiteTheme\has_text($v);}
function picture($id,string $class=''):void{if(\AxisCNC\SuiteTheme\has_image($id))echo wp_get_attachment_image((int)$id,'full',false,['class'=>$class,'sizes'=>'(max-width: 700px) 100vw, 1200px']);}
function action($v,string $class='button'):void{if(is_array($v)&&valid($v['title']??'')&&!empty($v['url']))echo '<a class="'.esc_attr($class).'" href="'.esc_url(\AxisCNC\SuiteTheme\local_url($v['url'])).'">'.esc_html($v['title']).'</a>';}
function items($s):array{return array_values(array_filter((array)($s['items']??[]),static fn($i)=>is_array($i)&&valid($i['title']??'')));}
function section_valid(array $s):bool{
 if(!valid($s['heading']??''))return false;
 $type=$s['acf_fc_layout']??'';
 if(in_array($type,['cards','strip','inspection','tabs','steps','deliverables','comparison'],true))return count(items($s))>0;
 if(in_array($type,['table','split_table','profile'],true))return count(array_filter((array)($s['table_rows']??[]),static fn($r)=>valid($r['first']??'')&&valid($r['second']??'')))>0;
 if($type==='inquiry')return (int)($s['form_id']??0)>0&&shortcode_exists('fluentform');
 if($type==='editorial')return valid($s['body']??'');
 if($type==='band')return !empty($s['link']['url'])&&!empty($s['link']['title']);
 return valid($s['body']??'')||count(items($s))>0||\AxisCNC\SuiteTheme\has_image($s['image']??0);
}
function heading(array $s):void{if(valid($s['eyebrow']??''))echo '<p class="eyebrow">'.esc_html($s['eyebrow']).'</p>';echo '<h2>'.esc_html($s['heading']).'</h2>';if(valid($s['intro']??''))echo '<p class="ar-intro">'.esc_html($s['intro']).'</p>';}
function table(array $s):void{
 $heads=array_values(array_filter(array_map('trim',explode("\n",(string)($s['table_headings']??'')))));$heads=array_slice($heads,0,4);if(count($heads)<2)$heads=['Item','Review requirements'];
 echo '<div class="ar-table-scroll" role="region" aria-label="'.esc_attr($s['heading']).'" tabindex="0"><table><thead><tr>';foreach($heads as$h)echo '<th scope="col">'.esc_html($h).'</th>';echo '</tr></thead><tbody>';
 foreach((array)($s['table_rows']??[])as$r){if(!valid($r['first']??'')||!valid($r['second']??''))continue;echo '<tr>';foreach(array_slice(['first','second','third','fourth'],0,count($heads))as$i=>$key){$tag=$i?'td':'th';echo '<'.$tag.($i?'':' scope="row"').'>'.esc_html($r[$key]??'').'</'.$tag.'>';}echo '</tr>';}
 echo '</tbody></table></div>';
}
function cards(array $s,bool $strip=false):void{
 $rows=items($s);$cols=min(4,max(2,(int)($s['columns']??3)));$filter=($s['acf_fc_layout']??'')==='cards'&&count(array_filter($rows,static fn($r)=>!empty($r['tag'])))>0;
 if($filter){echo '<div class="ar-filters" aria-label="Material grades"><button type="button" aria-pressed="true" data-grade-filter="all">All grades</button>';foreach(array_unique(array_column($rows,'tag'))as$tag)if(valid($tag))echo '<button type="button" aria-pressed="false" data-grade-filter="'.esc_attr($tag).'">'.esc_html($tag).'</button>';echo '</div>';}
 if($strip&&!empty($s['image'])){echo '<figure class="ar-process-strip">';picture($s['image']);echo '</figure>';}
 echo '<div class="ar-cards cols-'.$cols.($strip?' ar-strip-labels':'').($filter?' ar-grade-grid':'').'">';
 foreach($rows as$r){echo '<article'.($filter?' data-grade-tag="'.esc_attr($r['tag']??'').'"':'').'>';if(!$strip&&\AxisCNC\SuiteTheme\has_image($r['image']??0)){echo '<figure>';picture($r['image']);echo '</figure>';}echo '<div class="ar-card-copy"><h3>'.esc_html($r['title']).'</h3>';if(valid($r['body']??''))echo '<p>'.esc_html($r['body']).'</p>';action($r['link']??null,'ar-text-link');echo '</div></article>';}
 echo '</div>';
}
function bullets(array $s):void{echo '<ol class="ar-bullets">';foreach(items($s)as$i=>$r){echo '<li><span aria-hidden="true">'.($i+1).'</span><div><h3>'.esc_html($r['title']).'</h3><p>'.esc_html($r['body']??'').'</p></div></li>';}echo '</ol>';}
function render():void{
 \AxisCNC\SuiteTheme\start();echo '<div class="approved-design">';
 $image=get_field('axis_suite_hero_image');echo '<section class="ar-hero"><div class="shell ar-hero-grid'.(!\AxisCNC\SuiteTheme\has_image($image)?' is-text-only':'').'"><div class="ar-hero-copy">';
 $crumb=get_field('axis_design_breadcrumb');if(valid($crumb))echo '<p class="ar-breadcrumb">'.esc_html($crumb).'</p>';
 $kicker=get_field('axis_suite_kicker');if(valid($kicker))echo '<p class="eyebrow">'.esc_html($kicker).'</p>';
 echo '<h1>'.nl2br(esc_html((string)(get_field('axis_suite_headline')?:get_the_title()))).'</h1>';if(valid(get_field('axis_suite_intro')))echo '<p class="ar-lede">'.esc_html(get_field('axis_suite_intro')).'</p>';action(get_field('axis_suite_cta'));echo '</div>';
 if(\AxisCNC\SuiteTheme\has_image($image)){echo '<figure>';echo wp_get_attachment_image($image,'full',false,['loading'=>'eager','fetchpriority'=>'high','sizes'=>'(max-width:700px) 100vw, 750px']);echo '</figure>';}echo '</div>';if(\AxisCNC\SuiteTheme\has_image($image)&&valid(\AxisCNC\SuiteTheme\public_caption(get_field('axis_suite_caption'))))echo '<div class="shell ar-hero-caption"><p class="ar-image-note">'.esc_html(get_field('axis_suite_caption')).'</p></div>';echo '</section>';
 $sections=get_field('axis_design_sections');foreach(is_array($sections)?$sections:[]as$index=>$s){if(!section_valid($s))continue;$type=$s['acf_fc_layout'];$tone=in_array($s['tone']??'',['light','dark'],true)?$s['tone']:'white';$side=($s['image_side']??'left')==='right'?' image-right':'';
 echo '<section class="ar-section ar-kind-'.$type.' tone-'.esc_attr($tone).$side.(!empty($s['contained'])?' ar-contained':'').'" id="design-section-'.(int)$index.'"><div class="shell">';
 if($type==='band'){echo '<div class="ar-band-inner"><div>';heading($s);echo '</div>';action($s['link']);echo '</div>';}
 elseif($type==='editorial'){echo '<div class="ar-editorial">';heading($s);echo '<div class="ar-rich">'.wp_kses_post($s['body']).'</div>';action($s['link']??null,'ar-text-link');echo '</div>';}
 elseif(in_array($type,['cards','strip'],true)){heading($s);cards($s,$type==='strip');}
 elseif($type==='inspection'){heading($s);echo '<div class="ar-inspection-grid'.((int)($s['columns']??2)===3?' three-photos':'').'"><figure class="ar-inspection-photos">';picture($s['image']??0);echo '</figure><div class="ar-sample-report"><span>SAMPLE</span><h3>Inspection review scope</h3>';table($s);echo '</div></div><div class="ar-strip-labels ar-cards cols-3">';foreach(items($s)as$r)echo '<article><h3>'.esc_html($r['title']).'</h3><p>'.esc_html($r['body']??'').'</p></article>';echo '</div>';}
 elseif($type==='table'){heading($s);table($s);}
 elseif($type==='split_table'){heading($s);echo '<div class="ar-split-grid"><figure>';picture($s['image']??0);echo '</figure>';table($s);echo '</div>';}
 elseif($type==='profile'){echo '<div class="ar-profile-grid"><div>';heading($s);table($s);echo '</div><aside>';foreach(items($s)as$i=>$r)echo '<article class="profile-note-'.$i.'"><h3>'.esc_html($r['title']).'</h3><p>'.esc_html($r['body']??'').'</p></article>';echo '</aside></div>';}
 elseif($type==='split'){
  if(!empty($s['heading_above'])){echo '<div class="ar-heading-above">';heading($s);echo '</div>';}echo '<div class="ar-split-grid"><figure>';picture($s['image']??0);if(valid(\AxisCNC\SuiteTheme\public_caption($s['caption']??'')))echo '<figcaption>'.esc_html($s['caption']).'</figcaption>';echo '</figure><div>';if(empty($s['heading_above']))heading($s);if(valid($s['body']??''))echo '<div class="ar-rich">'.wp_kses_post($s['body']).'</div>';bullets($s);action($s['link']??null);echo '</div></div>';
 }
 elseif($type==='tabs'){$uid='ar-tabs-'.get_the_ID().'-'.$index;heading($s);echo '<div data-suite-tabs class="ar-tabs"><div role="tablist" aria-label="'.esc_attr($s['heading']).'">';foreach(items($s)as$i=>$r)echo '<button role="tab" id="'.$uid.'-tab-'.$i.'" aria-controls="'.$uid.'-panel-'.$i.'" aria-selected="'.($i?'false':'true').'" tabindex="'.($i?'-1':'0').'">'.esc_html($r['title']).'</button>';echo '</div>';foreach(items($s)as$i=>$r){echo '<div class="ar-tab-panel'.($side?' image-right':'').'" role="tabpanel" id="'.$uid.'-panel-'.$i.'" aria-labelledby="'.$uid.'-tab-'.$i.'"'.($i?' hidden':'').'><figure>';picture($r['image']??0);echo '</figure><div><h3>'.esc_html($r['title']).'</h3><div class="ar-rich">'.wp_kses_post(wpautop($r['body']??'')).'</div>';action($r['link']??null,'button ar-secondary');echo '</div></div>';}echo '</div>';}
 elseif($type==='deliverables'){heading($s);echo '<div class="ar-deliverables">';foreach(items($s)as$i=>$r){echo '<article><span class="ar-document-icon" aria-hidden="true">'.(['▤','▱','☷','◇'][$i%4]).'</span><div><h3>'.esc_html($r['title']).'</h3><p>'.esc_html($r['body']??'').'</p></div></article>';}echo '</div>';}
 elseif($type==='inquiry'){heading($s);echo '<div class="ar-inquiry-grid"><div class="suite-native-form ar-compact-form" data-axis-context="'.esc_attr(get_the_title()).'">';echo do_shortcode('[fluentform id="'.(int)$s['form_id'].'"]');echo '</div><aside><h3>'.esc_html($s['caption']??'Helpful details').'</h3>';bullets($s);echo '</aside></div>';}
 elseif($type==='steps'){heading($s);echo '<div class="ar-step-grid cols-'.min(4,max(2,(int)($s['columns']??3))).'">';foreach(items($s)as$i=>$r){echo '<article><span>'.($i+1).'</span><div><h3>'.esc_html($r['title']).'</h3><p>'.esc_html($r['body']??'').'</p></div></article>';}echo '</div>';}
 elseif($type==='comparison'){heading($s);echo '<div class="ar-comparison">';foreach(items($s)as$i=>$r){echo '<article class="compare-'.$i.'"><h3>'.esc_html($r['title']).'</h3><p>'.nl2br(esc_html($r['body']??'')).'</p></article>';}echo '</div>';}
 if(!in_array($type,['split','inquiry'],true)&&valid(\AxisCNC\SuiteTheme\public_caption($s['caption']??'')))echo '<p class="ar-image-note">'.esc_html($s['caption']).'</p>';
 echo '</div></section>';
 }
 \AxisCNC\SuiteTheme\faq(\AxisCNC\SuiteTheme\rows('faq',['title','body']));echo '</div>';\AxisCNC\SuiteTheme\end();
}
function material_menu($top,array $groups,array $tree):bool{
 if(untrailingslashit(wp_parse_url($top->url,PHP_URL_PATH)??'')!=='/materials'||!$groups)return false;
 $map=[];foreach($groups as$g){$key=trim((string)$g->description);if(str_starts_with($key,'material-group:'))$map[substr($key,15)]=['item'=>$g,'links'=>$tree[(int)$g->ID]??[]];}
 if(!$map)return false;$id='native-'.$top->ID;
 echo '<div class="mega ar-material-menu" id="mega-'.esc_attr($id).'"><div class="ar-menu-grid"><div class="ar-material-visual">';picture((int)get_theme_mod('axis_material_menu_image'));echo '<div><h2>'.esc_html(get_theme_mod('axis_material_menu_title','Materials')).'</h2><p>'.esc_html(get_theme_mod('axis_material_menu_intro','Choose by process. Compare by grade.')).'</p></div></div>';
 $list=static function(array $g):void{echo '<ul>';foreach($g['links']??[]as$item)echo '<li><a href="'.esc_url($item->url).'">'.esc_html($item->title).'</a></li>';echo '</ul>';};
 echo '<div class="ar-menu-column"><h3>CNC machining</h3><div class="ar-menu-subcolumns">';foreach(['cnc-metals','cnc-plastics']as$k){echo '<div>';if(isset($map[$k])){echo '<h4><a href="'.esc_url($map[$k]['item']->url).'">'.esc_html($map[$k]['item']->title).'</a></h4>';$list($map[$k]);}echo '</div>';}echo '</div><a class="ar-menu-all" href="'.esc_url($top->url).'">Explore all materials</a></div>';
 echo '<div class="ar-menu-column">';if(isset($map['printing-materials'])){echo '<h3><a href="'.esc_url($map['printing-materials']['item']->url).'">3D printing</a></h3>';$list($map['printing-materials']);}echo '</div><div class="ar-menu-column">';foreach(['molding-materials'=>'Injection molding','sheet-metals'=>'Sheet metal']as$k=>$title)if(isset($map[$k])){echo '<h3><a href="'.esc_url($map[$k]['item']->url).'">'.$title.'</a></h3>';$list($map[$k]);}echo '</div></div></div>';return true;
}
add_action('customize_register',static function($c):void{foreach(['title'=>'Materials','intro'=>'Choose by process. Compare by grade.']as$k=>$v){$c->add_setting('axis_material_menu_'.$k,['default'=>$v,'sanitize_callback'=>'sanitize_text_field']);$c->add_control('axis_material_menu_'.$k,['label'=>'Material menu '.$k,'section'=>'contact_info','type'=>'text']);}$c->add_setting('axis_material_menu_image',['sanitize_callback'=>'absint']);$c->add_control(new \WP_Customize_Media_Control($c,'axis_material_menu_image',['label'=>'Material menu image','section'=>'contact_info','mime_type'=>'image']));});
add_action('wp_enqueue_scripts',static function():void{if(!\AxisCNC\SuiteTheme\enabled())return;wp_enqueue_style('axis-approved-design',get_theme_file_uri('assets/css/approved-design.css'),['axis-suite-extra'],(string)filemtime(get_theme_file_path('assets/css/approved-design.css')));wp_enqueue_script('axis-approved-design',get_theme_file_uri('assets/js/approved-design.js'),['axis-suite-content'],(string)filemtime(get_theme_file_path('assets/js/approved-design.js')),true);},1001);
