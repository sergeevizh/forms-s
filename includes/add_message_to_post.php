<?php
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
	add_post_meta($post_id, 'email_send', '1');
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