<?php
define('AXIS_LOCAL_IMPORT',true);define('AXIS_CONTENT_MIGRATION',true);
require '/wordpress/wp-load.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
if(!get_option('axis_local_initialized')){
 foreach(['advanced-custom-fields-pro/acf.php','classic-editor/classic-editor.php','fluentform/fluentform.php','seo-by-rank-math/rank-math.php','gtranslate/gtranslate.php','starter-model/starter-model.php']as$plugin){
  if(!is_plugin_active($plugin)){$r=activate_plugin($plugin);if(is_wp_error($r))throw new RuntimeException($plugin.': '.$r->get_error_message());}
 }
 if(get_option('stylesheet')!=='b2b-starter-theme')switch_theme('b2b-starter-theme');
 if(!username_exists('demo_admin')){$id=wp_create_user('demo_admin','AxisCNCDemo2026!','demo@example.invalid');if(is_wp_error($id))throw new RuntimeException($id->get_error_message());(new WP_User($id))->set_role('administrator');}
}
echo 'Local dependencies ready';
