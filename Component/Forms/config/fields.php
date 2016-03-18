<?php
return array(
	'forms' => array(
		'id' => array(
			'sqltype' => 'serial index',
			'caption' => 'id',
			'type' => 'hidden',
		),
		'name' => array(
			'sqltype' => 'varchar(100) index',
			'caption' => 'Идентификатор',
			'style' => 'width:200px',
		),
		'site' => array(
			'sqltype' => 'varchar(10) index',
			'default' => '__',
			'type' => 'hidden'
		),
		'title' => array(
			'sqltype' => 'varchar(200)',
			'caption' => 'Название формы',
			'style' => 'width:98%',
		),
		'parms' => array(
			'sqltype' => 'text',
			'type' => 'hidden'
		),
		'parmsrc' => array(
			'sqltype' => 'text',
			'caption' => 'Параметры',
			'type' => 'parms',
			'parse_to' => 'parms',
			'style' => 'width:98%;height:400px',
		),
	),
	'forms_posts' => array(
		'id' => array(
			'sqltype' => 'serial index',
		),
		'form_id' => array(
			'sqltype' => 'int(11) index',
		),
		'form_name' => array(
			'sqltype' => 'varchar(50) serial',
		),
		'idate' => array(
			'sqltype' => 'int(12) index',
		),
		'val' => array(
			'sqltype' => 'text',
		),
		'server_info' => array(
			'sqltype' => 'text',
		),
	)
);