<?php
define('AXIS_LOCAL_IMPORT',true);define('AXIS_CONTENT_MIGRATION',true);
require '/wordpress/wp-load.php';
if(!function_exists('acf_get_field')||!class_exists('acf_field_repeater')||!defined('FLUENTFORM'))throw new RuntimeException('Bundled field and form plugins must be active.');
$user=get_user_by('login','demo_admin');if(!$user)throw new RuntimeException('Local demo administrator missing');wp_set_current_user($user->ID);
if(!get_option('axiscnc_template_imported')){
 if(get_option('axiscnc_template_import_started'))throw new RuntimeException('An earlier import stopped. Preserve .runtime and inspect before continuing.');
 require '/local-source/import.php';
 update_option('admin_email','demo@example.invalid');update_option('users_can_register','0');update_option('cron',[]);
 update_user_meta($user->ID,'show_admin_bar_front','true');update_user_meta($user->ID,'rich_editing','true');
 update_option('axis_local_initialized','2.0.0',false);
}
// Native local edits stay in this database. A changed port only relocates local URLs.
$next=rtrim(WP_HOME,'/');$old=get_option('axis_local_origin');
if($old&&$old!==$next){
 global$wpdb;$swap=function($v)use(&$swap,$old,$next){if(is_array($v)){foreach($v as$k=>$x)$v[$k]=$swap($x);return$v;}if(is_object($v)){foreach($v as$k=>$x)$v->$k=$swap($x);return$v;}return is_string($v)?str_replace($old,$next,$v):$v;};
 foreach($wpdb->get_results('SELECT meta_id,meta_value FROM '.$wpdb->postmeta)as$r){$v=$swap(maybe_unserialize($r->meta_value));$new=maybe_serialize($v);if($new!==$r->meta_value)$wpdb->update($wpdb->postmeta,['meta_value'=>$new],['meta_id'=>$r->meta_id]);}
 foreach($wpdb->get_results('SELECT option_id,option_value FROM '.$wpdb->options)as$r){$v=$swap(maybe_unserialize($r->option_value));$new=maybe_serialize($v);if($new!==$r->option_value)$wpdb->update($wpdb->options,['option_value'=>$new],['option_id'=>$r->option_id]);}
 foreach($wpdb->get_results('SELECT ID,post_content,post_excerpt FROM '.$wpdb->posts)as$r){$body=$swap($r->post_content);$excerpt=$swap($r->post_excerpt);if($body!==$r->post_content||$excerpt!==$r->post_excerpt)$wpdb->update($wpdb->posts,['post_content'=>$body,'post_excerpt'=>$excerpt],['ID'=>$r->ID]);}
 foreach(['fluentform_forms'=>['form_fields','appearance_settings','conditions'],'fluentform_form_meta'=>['value']]as$table=>$columns)foreach($wpdb->get_results('SELECT * FROM '.$wpdb->prefix.$table,ARRAY_A)as$row){$data=[];foreach($columns as$k)if(str_contains($row[$k],$old))$data[$k]=str_replace($old,$next,$row[$k]);if($data)$wpdb->update($wpdb->prefix.$table,$data,['id'=>$row['id']]);}
 wp_cache_flush();
}
update_option('axis_local_origin',$next,false);update_option('home',$next);update_option('siteurl',$next);flush_rewrite_rules(false);
echo 'AXISCNC content ready; existing local edits retained.';
