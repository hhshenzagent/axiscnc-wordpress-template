<?php
/** Plugin Name: AXISCNC Local Demo */
defined('ABSPATH')||exit;
add_filter('pre_wp_mail',static fn()=>true,100);
add_filter('pre_http_request',static function($pre,$args,$url){$host=wp_parse_url($url,PHP_URL_HOST);if($host==='cnc.wordpress-template.top'||str_ends_with((string)$host,'.hostingersite.com'))return new WP_Error('local_demo','This local copy does not connect to the original business site.');return$pre;},10,3);
add_filter('automatic_updater_disabled','__return_true');
add_filter('wp_robots',static function($robots){$robots['noindex']=true;$robots['nofollow']=true;return$robots;});
add_action('admin_notices',static function(){echo '<div class="notice notice-info"><p>AXISCNC local demo. Email delivery is disabled. Your changes are saved in this copy’s .runtime directory.</p></div>';});
