<?php
/** Editable content for the client-approved October 2026 page designs. */
namespace AxisCNC\ApprovedModel;
defined('ABSPATH') || exit;
function f(string $scope,string $name,string $label,string $type='text',array $more=[]):array{return array_merge(['key'=>'field_axis_design_'.$scope.'_'.$name,'name'=>$name,'label'=>$label,'type'=>$type,'required'=>0,'show_in_rest'=>true,'instructions'=>'仅编辑当前页面。清空可选内容后，对应区块隐藏。 / This page only; empty optional content is hidden.'],$more);}
function img(string $scope,string $name='image',string $label='图片 / Image'):array{return f($scope,$name,$label,'image',['return_format'=>'id','preview_size'=>'medium','instructions'=>'从媒体库选择或上传WebP。AI图片仅为示意，不作为本公司实物证据。']);}
function rich(string $scope,string $name,string $label):array{return f($scope,$name,$label,'wysiwyg',['toolbar'=>'full','tabs'=>'all','media_upload'=>1]);}
function rep(string $scope,string $name,string $label,array $fields,int $max=24):array{return f($scope,$name,$label,'repeater',['sub_fields'=>$fields,'layout'=>'block','max'=>$max,'button_label'=>'添加项目 / Add item','instructions'=>'可拖动排序；删除最后一项隐藏该模块。']);}
function link(string $scope,string $name='link',string $label='按钮或链接 / Link'):array{return f($scope,$name,$label,'link',['return_format'=>'array']);}
add_action('acf/init',static function():void{
 if(!function_exists('acf_add_local_field_group'))return;
 $layouts=[];
 foreach(['cards'=>'图片卡片 / Image cards','strip'=>'工艺图片组 / Process image strip','inspection'=>'检测图片与样例 / Inspection','split'=>'左右图文 / Split content','split_table'=>'左图右表 / Image and table','profile'=>'材料概览 / Material profile','table'=>'比较表 / Comparison table','tabs'=>'工艺切换 / Process tabs','editorial'=>'选型正文 / Editorial text','deliverables'=>'交付资料2×2 / Deliverables','inquiry'=>'简洁询盘 / Project inquiry','band'=>'横幅CTA / Quote band','steps'=>'步骤 / Steps','comparison'=>'两栏对照 / Comparison']as$type=>$label){
  $s='section_'.$type;
  $common=[f($s,'eyebrow','小标题 / Eyebrow'),f($s,'heading','区块标题 / Heading'),f($s,'intro','简短介绍 / Introduction','textarea',['rows'=>2]),f($s,'tone','背景 / Background','select',['choices'=>['white'=>'白色 / White','light'=>'浅灰 / Pale gray','dark'=>'深蓝 / Navy']]),img($s),f($s,'caption','图片说明 / Caption'),f($s,'image_side','图片位置 / Image side','select',['choices'=>['left'=>'左 / Left','right'=>'右 / Right']]),f($s,'columns','列数 / Columns','select',['choices'=>['2'=>'2','3'=>'3','4'=>'4']]),rich($s,'body','正文 / Body'),link($s),f($s,'form_id','原有Fluent Forms表单ID / Existing form ID','number',['min'=>0])];
  $items=[f($s.'_item','title','标题 / Title'),f($s.'_item','body','说明 / Description','textarea',['rows'=>3]),img($s.'_item'),link($s.'_item'),f($s.'_item','tag','筛选组 / Filter group')];
  $rows=[f($s.'_row','first','第1列 / Column 1'),f($s.'_row','second','第2列 / Column 2','textarea',['rows'=>2]),f($s.'_row','third','第3列 / Column 3','textarea',['rows'=>2]),f($s.'_row','fourth','第4列 / Column 4','textarea',['rows'=>2])];
  $common[]=rep($s,'items','项目 / Items',$items);
  $common[]=f($s,'table_headings','表头（每行一列） / Table headings, one per line','textarea',['rows'=>3]);
  $common[]=rep($s,'table_rows','表格行 / Table rows',$rows);
  $uses=['cards'=>['columns','items'],'strip'=>['image','caption','columns','items'],'split'=>['image','caption','image_side','body','items','link'],'split_table'=>['image','table_headings','table_rows'],'profile'=>['table_headings','table_rows','items'],'table'=>['table_headings','table_rows'],'tabs'=>['image_side','items'],'editorial'=>['body','link'],'deliverables'=>['items'],'inquiry'=>['form_id','caption','items'],'band'=>['link'],'steps'=>['columns','items'],'comparison'=>['items']];
  $uses['inspection']=['image','caption','columns','items','table_headings','table_rows'];
  $keep=array_merge(['eyebrow','heading','intro','tone'],$uses[$type]);$common=array_values(array_filter($common,static fn($v)=>in_array($v['name'],$keep,true)));
  if($type==='cards')$common[]=f($s,'axis_auto_children','自动列出下级材料 / List child material records','true_false',['ui'=>1,'instructions'=>'材料聚合页可启用：自动显示已发布的下级牌号；新增、草稿和回收站状态同步前台。']);
  if($type==='split'){$common[]=f($s,'heading_above','标题独立在上方 / Heading above both columns','true_false',['ui'=>1]);$common[]=f($s,'contained','深色区块外侧保留白边 / Contained dark panel','true_false',['ui'=>1]);}
  foreach($common as&$control)if($control['name']==='items'){$itemKeep=['title','body'];if(in_array($type,['cards','tabs'],true))$itemKeep=array_merge($itemKeep,['image','link']);if($type==='cards')$itemKeep[]='tag';$control['sub_fields']=array_values(array_filter($control['sub_fields'],static fn($v)=>in_array($v['name'],$itemKeep,true)));}unset($control);
  $layouts['layout_axis_design_'.$type]=['key'=>'layout_axis_design_'.$type,'name'=>$type,'label'=>$label,'display'=>'block','sub_fields'=>$common];
 }
 $hero=['tab_hero','axis_suite_kicker','axis_suite_headline','axis_suite_intro','axis_suite_hero_image','axis_suite_caption','axis_suite_cta'];
 $shared=[\AxisCNC\Suite\clone_fields('approved_hero',$hero),f('base','axis_design_breadcrumb','面包屑文字 / Breadcrumb'),f('base','axis_design_sections','已确认设计区块 / Approved page sections','flexible_content',['layouts'=>$layouts,'button_label'=>'添加区块 / Add section','instructions'=>'按最终施工图拖动排序。每个区块的文字、图片、表格和按钮都在这里编辑；标题或有效内容为空时不输出空框。']),\AxisCNC\Suite\clone_fields('approved_questions',['axis_suite_faq'])];
 foreach(['capability'=>'能力详情 / Capability','solution'=>'应用方案 / Solution','material-family'=>'材料聚合 / Material family','material-grade'=>'材料详情 / Material grade']as$type=>$label){
  $fields=$type==='capability'?array_merge($shared,[\AxisCNC\Suite\clone_fields('approved_family',['axis_suite_family'])]):[\AxisCNC\Suite\clone_fields('approved_'.$type,array_column($shared,'name'))];
  // Clone the base fields by exact registered keys, not the older Suite key prefix.
  if($type!=='capability')$fields=[
   \AxisCNC\Suite\clone_fields('approved_hero_'.$type,$hero),
   f('clone_content_'.$type,'axis_design_content_'.$type,'','clone',['clone'=>['field_axis_design_base_axis_design_breadcrumb','field_axis_design_base_axis_design_sections'],'display'=>'seamless','prefix_name'=>0,'prefix_label'=>0]),
   \AxisCNC\Suite\clone_fields('approved_questions_'.$type,['axis_suite_faq'])
  ];
  acf_add_local_field_group(['key'=>'group_axis_design_'.$type,'title'=>'AXISCNC · '.$label,'fields'=>$fields,'location'=>[[['param'=>'post_template','operator'=>'==','value'=>'page-templates/axis-approved-'.$type.'.php']]],'show_in_rest'=>true,'menu_order'=>5,'hide_on_screen'=>['the_content']]);
 }
},55);
add_filter('do_shortcode_tag',static function($output,$tag){if($tag==='fluentform'&&get_option('axis_demo_mode'))$output.='<p class="axis-demo-note">Demo inquiry — email delivery is not connected.</p>';return $output;},15,2);

/** Native page SEO controls use Rank Math's canonical metadata without its Classic submit interceptor. */
add_action('acf/init',static function():void{
 if(!function_exists('acf_add_local_field_group')||!get_theme_mod('axis_suite_enabled'))return;
 acf_add_local_field_group(['key'=>'group_axis_native_page_seo','title'=>'Search appearance / 搜索展示','show_in_rest'=>true,'fields'=>[
 f('seo','rank_math_title','搜索标题 / SEO title','text',['instructions'=>'保存到Rank Math原生标题字段。留空沿用网站默认规则。 / Canonical Rank Math title; empty uses site defaults.']),
 f('seo','rank_math_description','搜索摘要 / Meta description','textarea',['rows'=>3,'instructions'=>'保存到Rank Math原生描述字段。留空沿用默认摘要；搜索结果展示由搜索引擎决定。'])
 ],'location'=>[[['param'=>'post_type','operator'=>'==','value'=>'page']]],'menu_order'=>20]);
},60);
add_action('add_meta_boxes',static function($type):void{if(!in_array($type,array_merge(['page'],\AxisCNC\ContentTypes\types()),true)||!get_theme_mod('axis_suite_enabled'))return;foreach(['normal','advanced','side']as$context)remove_meta_box('rank_math_metabox',$type,$context);},100);
add_action('admin_enqueue_scripts',static function():void{
 $screen=get_current_screen();if(!$screen||$screen->base!=='post'||!in_array($screen->post_type,array_merge(['page'],\AxisCNC\ContentTypes\types()),true)||!get_theme_mod('axis_suite_enabled'))return;
 $scripts=wp_scripts();$depends=function($handle,$seen=[])use(&$depends,$scripts):bool{if($handle==='rank-math-editor')return true;if(isset($seen[$handle]))return false;$seen[$handle]=true;foreach($scripts->registered[$handle]->deps??[]as$d)if($depends($d,$seen))return true;return false;};
 foreach($scripts->queue as$handle)if($depends($handle))wp_dequeue_script($handle);wp_dequeue_style('rank-math-metabox');
},1000);
