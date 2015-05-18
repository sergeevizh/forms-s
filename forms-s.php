<?php 
/*
Plugin Name: Forms by Systemo
Description: Calback Form
Version: 20150502
GitHub Plugin https://github.com/systemo-biz/forms-cp
GitHub Branch: master
Author: http://systemo.org
*/
 include_once('includes/emailer.php');
 include_once('includes/spam_protect.php');
define ("forms_tmpl_include", 1);   // включить forms-tmpls.php = 1
if (defined ("forms_tmpl_include") && forms_tmpl_include == 1) {
	include_once('includes/forms-tmpl.php');
	add_action('init', 'cp_callback_activation'); //активация и регистрация таксономии и типа поста для хранения шаблонов форм
	register_activation_hook(__FILE__, 'cp_callback_activation');
}
//Шорткоды
include_once('includes/shortcodes/form-cp.php');
include_once('includes/shortcodes/input-cp.php');
include_once('includes/shortcodes/textarea-cp.php');
 //Добавляем данные в пост
add_action('init', 'add_message_to_posts');
function add_message_to_posts(){
	// проверяем пустая ли data_cp
	if(empty($_REQUEST['data_form_cp'])) return;
	$data_form = $_REQUEST['data_form_cp']; // если не пустая то записываем значения для  проверки существованя
	$meta_data_form = $_REQUEST['meta_data_form_cp']; // если не пустая то записываем значения для  проверки существованя
	//error_log(print_r($dara_form, true));
	// Создаем массив
	$cp_post = array(
		'post_title' => $meta_data_form['name_form'],
		'post_type' => 'message_cp',
		'post_content' => print_r($data_form, true),
		'post_author' => 1,
		);
	// Вставляем данные в БД
	$post_id = wp_insert_post( $cp_post );
	//Метка отправки на почту
	add_post_meta($post_id, 'meta_label', '1');
	// Присваиваем id поста-шаблона формы как термин таксономии текущему посту-сообщению
	if (defined ("forms_tmpl_include") && forms_tmpl_include == 1) {
		$parent_post_name = strval($_REQUEST['meta_data_form_cp']['parent_post_id']);
		wp_set_object_terms($post_id, $parent_post_name, 'form_tag_s', true);
	}
	//Записываем меты
	foreach($meta_data_form as $key => $value):
		add_post_meta($post_id, 'meta_' . $key, $value);
	endforeach;
	$content_data = null;
	foreach($data_form as $key => $value):
		add_post_meta($post_id, $key, $value);
		$content_data .= "
			<div>
				<div><strong>" . get_post_meta($post_id, 'meta_'.$key, true) . "</strong></div>".
				"<div>" . get_post_meta($post_id, $key, true) . "</div>
			</div>
			<hr/>";
	endforeach;

	$post_data = array(
		'ID' => $post_id, 
		'post_content' => $content_data,
		);
	wp_update_post( $post_data );
}
//регистрируем новый тип поста
add_action( 'init', 'form_message_add_post_type_cp' );
function form_message_add_post_type_cp() {
	$labels = array(
		'name'                => _x( 'Сообщения', 'Post Type General Name', 'text_domain' ),
		'singular_name'       => _x( 'Сообщение', 'Post Type Singular Name', 'text_domain' ),
		'menu_name'           => __( 'Сообщения', 'text_domain' ),
		'parent_item_colon'   => __( 'Parent Item:', 'text_domain' ),
		'all_items'           => __( 'Все сообщения', 'text_domain' ),
		'view_item'           => __( 'View Item', 'text_domain' ),
		'add_new_item'        => __( 'Добавить сообщение', 'text_domain' ),
		'add_new'             => __( 'Добавить сообщение', 'text_domain' ),
		'edit_item'           => __( 'Edit Item', 'text_domain' ),
		'update_item'         => __( 'Update Item', 'text_domain' ),
		'search_items'        => __( 'Search Item', 'text_domain' ),
		'not_found'           => __( 'Not found', 'text_domain' ),
		'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
	);
	$args = array(
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'author', 'comments', 'custom-fields', 'page-attributes'),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => false,
		'menu_position'       => 55,
		'can_export'          => true,
		'has_archive'         => false,
		'query_var'			=> false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'capability_type'     => 'post',
	);
	if (defined ("forms_tmpl_include") && forms_tmpl_include == 1){
		$args['taxonomies']= array( 'form_tag_s' );
	}
	
	register_post_type( 'message_cp', $args );
}
//Метка об отправке на почту
add_filter( 'manage_edit-message_cp_columns', 'set_custom_edit_message_cp_columns' );
add_action( 'manage_message_cp_posts_custom_column' , 'custom_message_cp_column',1,2);

function set_custom_edit_message_cp_columns($columns) {
    $columns['label'] = __( 'Метка отправки', '' );
    return $columns;
}

function custom_message_cp_column( $column, $post_id ) {
    if ($column=='label') {
            $label=get_post_meta($post_id,'meta_label',true);
            if ($label=='1')
                echo '<span class="dashicons dashicons-flag"></span>';
            else if($label=='2')
                echo '<span class="dashicons dashicons-yes"></span>';
    }
}

 add_action( 'wp_enqueue_scripts', 'wpb_adding_scripts' ); 
 function wpb_adding_scripts() {
//wp_register_script('jquerymask', plugins_url('js/jquery.mask.min.js', __FILE__), array('jquery'),'1.1', true);
//wp_enqueue_script('jquerymask');
}
register_activation_hook(__FILE__, 'activation_form_emailer_cp');
function activation_form_emailer_cp() {
	wp_schedule_event( time(), 'hourly', 'check_new_msg_and_send');
}
register_deactivation_hook(__FILE__, 'deactivation_form_emailer_cp');
function deactivation_form_emailer_cp() {
	wp_clear_scheduled_hook('check_new_msg_and_send');
}
