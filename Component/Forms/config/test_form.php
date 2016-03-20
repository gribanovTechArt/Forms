<?php
return array(
	'title' => 'Сообщения от посетителей сайта',
	'header' => 'Отправить сообщение',
	'ok' => '/',
	'ajax_ok' => 'Ваше сообщение отправлено',
	'submit' => 'Отправить',
	'fields' => array(
		'name' => array(
			'caption' => 'Ваше имя',
			'style' => 'width:100%',
			'validate_presence' => 'Введите имя!',
			'cookie' => '/forms/messages/name',
		),
		'email' => array(
			'caption' => 'E-Mail',
			'style' => 'width:300px',
			'validate_presence' => 'Введите E-Mail!',
			'validate_email' => 'E-Mail некорректный!',
			'value' => '{order_item}',
		),
		'phone' => array(
			'caption' => 'Телефон',
			'style' => 'width:500px',
			'cookie' => 'forms/messages/phone',
		),
		'message' => array(
			'caption' => 'Текст сообщения',
			'type' => 'textarea',
			'style' => 'width:100%;height:200px'
		),
		'protect' => array(
			'caption' => 'Введите число, которое видите на картинке',
			'type' => 'protect',
			'hidden' => true,

		)
	),
	'admin_list' => array(
		'name' => 'Имя посетителя',
	),
);