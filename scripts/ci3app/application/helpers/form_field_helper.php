<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if(!function_exists('get_input_field')) {

	function get_input_field($type, $name, $id='', $is_required=false, $autofocus=false, $placeholder='', $validate='', $required_message='') {
		$inputField = '<input type="'.$type.'" name="'.$name.'" id="'.$id.'" data-validate="'.$validate.'" data-message-required="'.$required_message.'" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="'.$placeholder.'" required="'.$is_required.'" autofocus="'.$autofocus.'">';

		return $inputField;
	}
}

if(!function_exists('get_select_open')) {

	function get_select_open($name, $id='', $is_required=false, $autofocus=false, $validate='', $required_message='') {
		$selectOpen = '<select name="'.$name.'" id="'.$id.'" data-validate="'.$validate.'" data-message-required="'.$required_message.'" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="'.$is_required.'" autofocus="'.$autofocus.'">';

		return $selectOpen;
	}
}

if(!function_exists('get_select_close')) {

	function get_select_close() {

		return '</select>';
	}
}

if(!function_exists('get_textarea_field')) {

	function get_textarea_field($name, $rows="2", $height="h-20", $id='', $is_required=false, $autofocus=false, $placeholder='', $validate='', $required_message='') {
		$textareaField = '<textarea rows="'.$rows.'" id="'.$id.'" name="'.$name.'"  data-validate="'.$validate.'" data-message-required="'.$required_message.'" class="block p-2.5 w-full '.$height.' text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-blue-500 dark:focus:border-blue-500" required="'.$is_required.'" placeholder="'.$placeholder.'"></textarea>';

		return $textareaField;
	}
}

if(!function_exists('get_button')) {

	function get_button($type='submit', $content="Submit", $extra_classes='', $id='', $background_color='bg-blue-700', $background_hover_color='bg-blue-800', $function='') {
		$button = '<button type="'.$type.'" id="'.$id.'" class="inline-flex items-center px-5 py-3 font-bold text-center  h-20 text-gray-200 ' .$background_color. ' rounded-lg focus:ring-4 focus:ring-blue-200 text-2xl dark:focus:ring-blue-900 hover:'.$background_hover_color.' ' .$extra_classes.'" onclick="'.$function.'">'.$content.'</button>';

		return $button;
	}
}