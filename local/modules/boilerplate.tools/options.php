<?php

$options = [
	[
		'DIV'    => 'auth',
		'TAB'    => 'Авторизация',
		'TITLE'  => 'Авторизация',
		'ICON'   => '',
		'GROUPS' => [
			'AUTH' => [
				'TITLE'   => 'Авторизация через API',
				'OPTIONS' => [
					'ACCEPT_AUTH' => [
						'SORT'   => 100,
						'TYPE'   => 'CHECKBOX',
						'FIELDS' => [
							'TITLE'        => 'Разрешить авторизацию через API',
							'DEFAULT'      => '',
							'READONLY'     => false,
							'DISABLED'     => false,
							'AUTOCOMPLETE' => false,
							'REFRESH' => true,
							'RELOAD' => true
						],
					]
					//TODO включать или отключать различные опции аутентификации, авторизации
					//TODO добавить возможность выбора способа авторизации?
				]
			],
		],
	],
	[
		'DIV'    => 'settings',
		'TAB'    => 'Новая вкладка',
		'TITLE'  => 'Новая вкладка',
		'ICON'   => '',
		'GROUPS' => [
			'GROUP_CODE' => [
				'TITLE'   => 'Название группы',
				'OPTIONS' => [
					'PROP_ID' => [
						'SORT'   => 100,
						'TYPE'   => 'STRING',
						'FIELDS' => [
							'TITLE'        => 'Поле "Строка"',
							'DEFAULT'      => 'Значение по умолчанию',
							'NOTES'        => 'Это подсказка к полю "Строка"',
							'PLACEHOLDER'  => 'Это плейсхолдер к полю "Строка"',
							'TAG'          => 'Текст на теге',
							'READONLY'     => false,
							'DISABLED'     => false,
							'AUTOCOMPLETE' => false,
						],
					],
				]
			],
		],
	],
];

Gelion\BitrixOptions\Form::generate('boilerplate.tools', $options);
