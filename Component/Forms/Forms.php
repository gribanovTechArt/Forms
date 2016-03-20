<?php
Core::load('Component.Forms.App.Router');

class Component_Forms extends CMS_Component implements Core_ModuleInterface
{
	static $vars = array();
	static $config;
//
	public static function initialize($config = array())
	{
		parent::initialize($config);
		CMS::cached_run('Component.Forms', 'create_test_form');
		CMS::register_object('forms', 'Component.Forms.App.API');
	}

	/**
	 * Создает тестовую форму после установки компонента.
	 */
	public static function create_test_form()
	{
		/**@var Component_Forms_DB_Items $orm
		 * @var Component_Forms_DB_Item $form
		 */
		$orm = CMS::orm()->forms;
		if ($orm->count() === 0) {
			$data = CMS::component('Forms')->config('test_form');
			$form = $orm->make_entity();
			$form->name = 'messages';
			$form->assign_data($data);
			$form->insert();
		}
	}
}