<?php
Core::load('Component.Forms.Router');

class Component_Forms extends CMS_Component implements Core_ModuleInterface
{
	static $vars = array();
	static $config;
//
	static function initialize($config = array())
	{
		parent::initialize($config);
		CMS::cached_run('Component.Forms', 'create_test_form');

//		CMS::register_object('forms', 'Component.Forms.API');
	}
//
//	public static function Router()
//	{
//		return Core::make('Component.Forms.App.Router');
//	}

	/**
	 * Метод для создания тестовой формы при после установки компонента.
	 * Вызывается только один раз.
	 */
	public function create_test_form()
	{
		/**@var Component_Forms_DB_Items $orm */
		$orm = CMS::orm()->forms;
		if ($orm->count() === 0) {
			$data = CMS::component('Forms')->config('test_form');
			$form = $orm->make_entity();
			$form->name = $data->name;
			$form->title = $data->title;
			$form->parms = $data->parms;
			$form->parmsrc = $data->parmsrc;
			$form->insert();
		}
	}
}