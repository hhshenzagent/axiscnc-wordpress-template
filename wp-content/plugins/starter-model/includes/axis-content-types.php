<?php
/** Native editorial identities for the site's repeatable content. */
namespace AxisCNC\ContentTypes;
defined('ABSPATH')||exit;

function definitions():array{return [
 'axis_capability'=>['name'=>'Capabilities','single'=>'Capability','base'=>'capabilities','icon'=>'dashicons-admin-tools','position'=>20,'default'=>'page-templates/axis-approved-capability.php','groups'=>['machining'=>'Machining','molding'=>'Molding','fabrication'=>'Fabrication','printing'=>'3D Printing','value-added'=>'Value-Added Services']],
 'starter_industry'=>['name'=>'Industries','single'=>'Industry','base'=>'industries','icon'=>'dashicons-building','position'=>21,'default'=>'page-templates/axis-industry.php','groups'=>[]],
 'axis_solution'=>['name'=>'Solutions','single'=>'Solution','base'=>'solutions','icon'=>'dashicons-lightbulb','position'=>22,'default'=>'page-templates/axis-approved-solution.php','groups'=>[]],
 'axis_material'=>['name'=>'Materials','single'=>'Material','base'=>'materials','icon'=>'dashicons-screenoptions','position'=>23,'default'=>'page-templates/axis-approved-material-family.php','groups'=>['cnc-metals'=>'CNC metals','cnc-plastics'=>'CNC plastics','printing-materials'=>'3D printing','molding-materials'=>'Injection molding','sheet-metals'=>'Sheet metal']],
 'axis_resource'=>['name'=>'Resources','single'=>'Resource','base'=>'resources','icon'=>'dashicons-media-document','position'=>24,'default'=>'page-templates/axis-guide.php','groups'=>[]],
];}
function types():array{return array_keys(definitions());}
function family_key(string $type):string{return $type==='axis_capability'?'axis_suite_family':'axis_content_group';}
function find_path(string $path){$path=trim($path,'/');$page=get_page_by_path($path,OBJECT,'page');if($page)return$page;foreach(definitions()as$type=>$d)if(str_starts_with($path,$d['base'].'/'))return get_page_by_path(substr($path,strlen($d['base'])+1),OBJECT,$type);return null;}
add_filter('theme_page_templates',static function($templates,$theme,$post){if(!get_option('axis_content_model_version'))return$templates;$current=$post?get_page_template_slug($post):'';foreach(['axis-capability','axis-approved-capability','axis-industry','axis-approved-solution','axis-approved-material-family','axis-approved-material-grade']as$name){$file='page-templates/'.$name.'.php';if($file!==$current)unset($templates[$file]);}return$templates;},20,3);

add_action('init',static function():void{
 foreach(definitions()as$type=>$d){register_post_type($type,[
  'labels'=>\Starter\Model\content_labels($d['single'],$d['name']),
  'public'=>true,'show_ui'=>true,'show_in_rest'=>true,'show_in_nav_menus'=>true,
  'rest_base'=>'axis-'.str_replace('_','-',$type),'has_archive'=>false,'hierarchical'=>true,
  'rewrite'=>['slug'=>$d['base'],'with_front'=>false,'hierarchical'=>true],
  'menu_icon'=>$d['icon'],'menu_position'=>$d['position'],'map_meta_cap'=>true,'capability_type'=>'post',
  'supports'=>['title','editor','excerpt','thumbnail','revisions','custom-fields','page-attributes'],
 ]);
 foreach(['axis_content_group'=>'string','axis_show_in_navigation'=>'boolean']as$key=>$format)register_post_meta($type,$key,['type'=>$format,'single'=>true,'show_in_rest'=>true,'auth_callback'=>static fn()=>current_user_can('edit_posts')]);
 }
 // Historical example parts remain available, but are distinct from services.
 $products=get_post_type_object('starter_product');if($products){$products->labels->menu_name='Example Parts';$products->menu_position=26;}
 $categories=get_post_type_object('starter_category');if($categories){$categories->show_in_menu='edit.php?post_type=starter_product';$categories->labels->menu_name='Category landing pages';}
},15);
add_filter('starter_acf_revision_post_types',static fn($types)=>array_values(array_unique(array_merge($types,types()))));

// During the scoped migration, old Pages and new CPTs must both resolve.
add_action('parse_request',static function(\WP $wp):void{
 $path=trim((string)$wp->request,'/');foreach(definitions()as$d)if(str_starts_with($path,$d['base'].'/')){
  $p=get_page_by_path($path,OBJECT,'page');if($p&&$p->post_status==='publish'&&str_starts_with(get_page_template_slug($p),'page-templates/axis-'))$wp->query_vars=['page_id'=>(int)$p->ID];break;
 }
},19);

add_action('wp_after_insert_post',static function($id,$post):void{
 $defs=definitions();if(!isset($defs[$post->post_type])||wp_is_post_revision($id))return;
 $template=get_post_meta($id,'_wp_page_template',true);if(!$template||$template==='default')update_post_meta($id,'_wp_page_template',$defs[$post->post_type]['default']);
},5,2);

add_action('add_meta_boxes',static function($type):void{
 if(!isset(definitions()[$type]))return;
 add_meta_box('axis-content-routing','Classification & navigation / 分类与导航',static function($post):void{
  $d=definitions()[$post->post_type];wp_nonce_field('axis_content_routing','axis_content_nonce');
  echo '<p>URL: <code>/'.esc_html($d['base']).'/…</code></p>';
  if($d['groups']){echo '<p><label for="axis-content-group">Group / 所属分组</label><select id="axis-content-group" name="axis_content_group" class="widefat"><option value="">Choose a group</option>';foreach($d['groups']as$key=>$title)echo '<option value="'.esc_attr($key).'"'.selected(get_post_meta($post->ID,family_key($post->post_type),true),$key,false).'>'.esc_html($title).'</option>';echo '</select></p>';}
  $shown=!metadata_exists('post',$post->ID,'axis_show_in_navigation')||get_post_meta($post->ID,'axis_show_in_navigation',true);
  echo '<p><label><input type="checkbox" name="axis_show_in_navigation" value="1" '.checked((bool)$shown,true,false).'> Include in main navigation / 加入主导航</label></p><p class="description">Only published content is visible. Drafts and trashed items disappear from navigation. Material grades normally appear under their parent material.</p>';
 },$type,'side','default');
},20);
add_action('save_post',static function($id,$post):void{
 if(!isset(definitions()[$post->post_type])||!isset($_POST['axis_content_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['axis_content_nonce'])),'axis_content_routing')||!current_user_can('edit_post',$id)||wp_is_post_revision($id))return;
 $d=definitions()[$post->post_type];$group=sanitize_key(wp_unslash($_POST['axis_content_group']??''));if($d['groups']){if($post->post_type==='axis_capability'&&function_exists('update_field'))update_field('field_axis_suite_axis_suite_family',isset($d['groups'][$group])?$group:'',$id);else update_post_meta($id,family_key($post->post_type),isset($d['groups'][$group])?$group:'');}
 update_post_meta($id,'axis_show_in_navigation',isset($_POST['axis_show_in_navigation'])?'1':'0');
},50,2);

function sync_navigation(int $id):void{
 if(defined('AXIS_CONTENT_MIGRATION')&&AXIS_CONTENT_MIGRATION)return;
 $post=get_post($id);$d=definitions()[$post->post_type??'']??null;if(!$d||$post->post_status!=='publish'||get_post_meta($id,'axis_show_in_navigation',true)==='0')return;
 $locations=get_nav_menu_locations();$menu=(int)($locations['primary']??0);if(!$menu)return;
 $items=wp_get_nav_menu_items($menu)?:[];$existing=null;foreach($items as$item)if($item->type==='post_type'&&(int)$item->object_id===$id){$existing=$item;break;}
 $base=home_url('/'.$d['base'].'/');$parent=0;$group=(string)get_post_meta($id,family_key($post->post_type),true);
 foreach($items as$item)if(untrailingslashit($item->url)===untrailingslashit($base)){$parent=(int)$item->ID;break;}
 if($post->post_type==='axis_capability'&&$group){foreach($items as$item)if((int)$item->menu_item_parent===$parent&&html_entity_decode($item->title,ENT_QUOTES,'UTF-8')===($d['groups'][$group]??'')){$parent=(int)$item->ID;break;}}
 elseif($post->post_type==='axis_material'){
  foreach($items as$item)if((int)$item->object_id!==$id&&(($post->post_parent&&(int)$item->object_id===(int)$post->post_parent)||(!$post->post_parent&&$group&&trim($item->description)==='material-group:'.$group))){$parent=(int)$item->ID;break;}
 }
 elseif($post->post_type==='axis_resource'){foreach($items as$item)if(untrailingslashit($item->url)===untrailingslashit($base)){$parent=(int)$item->ID;break;}}
 if(!$parent)return;
 if($existing){if((int)$existing->menu_item_parent!==$parent){update_post_meta($existing->ID,'_menu_item_menu_item_parent',$parent);clean_post_cache($existing->ID);}return;}
 wp_update_nav_menu_item($menu,0,['menu-item-title'=>$post->post_title,'menu-item-object'=>$post->post_type,'menu-item-object-id'=>$id,'menu-item-type'=>'post_type','menu-item-parent-id'=>$parent,'menu-item-status'=>'publish']);
}
add_action('wp_after_insert_post',static fn($id)=>sync_navigation((int)$id),100);
add_action('acf/save_post',static function($id):void{if(is_numeric($id))sync_navigation((int)$id);},100);
add_filter('wp_get_nav_menu_items',static function($items){
 if(is_admin())return$items;$byParent=[];foreach($items as$i)$byParent[(int)$i->menu_item_parent][]=$i;
 foreach($items as$item)if(get_post_meta($item->ID,'_axis_menu_family',true))foreach($byParent[$item->ID]??[]as$child)if(get_post_status((int)$child->object_id)==='publish'&&get_post_meta((int)$child->object_id,'axis_show_in_navigation',true)!=='0'){$item->axis_content_object_id=(int)$child->object_id;$item->url=$child->url;break;}
 return array_values(array_filter($items,static function($item)use($byParent):bool{
  if($item->type!=='post_type')return true;$id=(int)$item->object_id;$visible=get_post_status($id)==='publish'&&get_post_meta($id,'axis_show_in_navigation',true)!=='0';if($visible)return true;
  foreach($byParent[$item->ID]??[]as$child)if($child->type==='post_type'&&get_post_status((int)$child->object_id)==='publish'&&get_post_meta((int)$child->object_id,'axis_show_in_navigation',true)!=='0'){$item->object=$child->object;$item->object_id=$child->object_id;$item->url=$child->url;return true;}
  return false;
 }));
},30);

add_action('admin_menu',static function():void{
 foreach(definitions()as$type=>$d){$p=get_page_by_path($d['base'],OBJECT,'page');if(!$p)continue;add_submenu_page('edit.php?post_type='.$type,'Overview page / 聚合页','Overview page / 聚合页','edit_pages','axis-overview-'.$type,static function()use($p):void{echo '<div class="wrap"><h1>Overview page / 聚合页</h1><p>This is the shared directory for this content type. Detail records belong in the list above.</p><p><a class="button button-primary" href="'.esc_url(get_edit_post_link($p->ID)).'">Edit overview</a> <a class="button" href="'.esc_url(get_permalink($p)).'">View website</a></p></div>';});}
},40);
foreach(array_keys(definitions())as$type){
 add_filter('manage_'.$type.'_posts_columns',static function($cols){$cols['axis_template']='Template / 模板';$cols['axis_group']='Group / 分组';$cols['axis_url']='Website / 前台';return$cols;});
 add_action('manage_'.$type.'_posts_custom_column',static function($column,$id):void{
  if($column==='axis_template'){$templates=wp_get_theme()->get_page_templates(get_post($id),get_post_type($id));echo esc_html($templates[get_page_template_slug($id)]??get_page_template_slug($id));}
  elseif($column==='axis_group'){$d=definitions()[get_post_type($id)];$v=get_post_meta($id,family_key(get_post_type($id)),true);echo esc_html($d['groups'][$v]??($v?:'—'));}
  elseif($column==='axis_url')echo '<a href="'.esc_url(get_permalink($id)).'">'.esc_html(wp_parse_url(get_permalink($id),PHP_URL_PATH)).'</a>';
 },10,2);
}

add_filter('acf/load_field',static function($field){
 if(in_array($field['type']??'',['relationship','post_object'],true)&&in_array('page',(array)($field['post_type']??[]),true))$field['post_type']=array_values(array_unique(array_merge((array)$field['post_type'],types())));
 return$field;
});
add_filter('acf/prepare_field/name=axis_suite_family',static function($field){return is_admin()&&get_post_type()==='axis_capability'?false:$field;});
add_filter('acf/load_field_group',static function($group){
 if(($group['key']??'')==='group_axis_design_capability')foreach(['cnc','molding','sheet-metal','printing']as$variant)$group['location'][]=[['param'=>'post_template','operator'=>'==','value'=>'page-templates/axis-capability-'.$variant.'.php']];
 if(($group['key']??'')==='group_axis_native_page_seo')foreach(types()as$type)$group['location'][]=[['param'=>'post_type','operator'=>'==','value'=>$type]];
 if(($group['key']??'')==='group_starter_industry')foreach($group['location']as&$rule)$rule[]=['param'=>'post_template','operator'=>'!=','value'=>'page-templates/axis-industry.php'];
 return$group;
});
