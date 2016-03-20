<?php
/**
 * Class Component_Forms_DB_Items
 *
 * @method $this name(string $name)
 * @method $this site()
 */
class Component_Forms_DB_Items extends CMS_ORM_Mapper implements Core_ModuleInterface
{
	public function setup()
	{
		return $this
			->table('forms')
			->classname('Component.Forms.DB.Item')
			->key('id')
			->columns('id', 'name', 'site', 'title', 'parms', 'parmsrc')
			->order_by('id');
	}

	protected function map_name($name)
	{
		return $this->where('name=:name', $name);
	}

	protected function map_site()
	{
		$site = CMS::admin() ? CMS_Admin::site() : CMS::site();
		return $this->where('site=:site', $site);
	}
}

class Component_Forms_DB_Item extends CMS_ORM_Entity implements Core_ModuleInterface
{
	public function setup()
	{
		$this->name = 'newform';
		$this->site = CMS_Admin::site();
		$this->title = 'Новая форма';
		$this->fields = array();
		return $this;
	}

	/**
	 * Возвращает обьект текущей формы с id из $_GET параметра 'form_id'
	 *
	 * @return Component_Forms_DB_Item|null
	 */
	public static function form_from_request()
	{
		$form_id = WS::env()->request['form_id'];
		return CMS::orm()->forms->find($form_id);
	}

	/**
	 * Метод для обработки пользовательский полей формы
	 * Данные хранятся в колонке params в сериализованном виде
	 * Метод serialized() явно указывает на эту колонку (params)
	 */
	public function after_find()
	{
		parent::after_find();
		$this->create_fields();
	}

	/**
	 * Записывает поля для формы из параметра $data в обьект текущей формы
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function assign_data($data = array())
	{
		$data = (array) $data;
		$to_parms = $data;

		if ($data['title']) {
			$this->title = $data['title'];
			unset($to_parms['title']);
		}

		$this->parmsrc = CMS::unparse_parms($to_parms);
		$this->parms = CMS::parse_parms($this->parmsrc);

		return true;
	}

	/**
	 * Возвращает массив из названий колонок, в которых хранятся
	 * сериализованные данные
	 *
	 * @return array
	 */
	protected function serialized()
	{
		return array('parms');
	}

	/**
	 * Метод для формирования свойств итема выбраной формы из данных, которые хранятся в колонке params.
	 * Так же формирует массив полей, которые нужно отобразить в контроллере PostsController
	 */
	protected function create_fields()
	{
		if (CMS::admin()) {
			Core::load('Component.Forms.Render');
		}
		$flds = $this->parms['fields'];
		if (!is_array($flds)) {
			$flds = array();
		}
		$fields = array();
		foreach ($flds as $f => $data) {

			if (CMS::admin()) {
				$data = Component_Forms_Render::parms_for_form('admin', $data, false);
			}

			$name = $f;
			$caption = $data['caption'];
			if (isset($data['name'])) {
				$name = $data['name']; //TODO: ЧТО ТАКОЕ NAME?
				$caption = $f;
			}
			unset($data['name']);
			$data['caption'] = $caption;
			unset($data['in admin']); //TODO: ПРОБЕЛ?

			$fields[$name] = $data;
		}

		$this->fields = $fields;

		$flds = $this->parms['admin_list'];
		if (!is_array($flds)) {
			$flds = array();
		}
		$fields = array();

		foreach ($flds as $f => $data) {

			if (is_string($data)) {
				if (Core_Regexps::match('{^[a-z0-9_]+$}', $f)) { //TODO: может быть задать стандарт?
					$name = $f;
					$caption = $data;
				} else {
					$name = $data;
					$caption = $f;
				}

				$data = array('caption' => $caption);
			} else {
				if (isset($data['name'])) {
					$name = $data['name'];
					$caption = $f;
				} else {
					$name = $f;
					$caption = $data['caption'];
				}
				$data['caption'] = $caption;
				unset($data['name']);
			}
			if ($data['type'] == 'protect') {
				$name = 'protect';
			}
			if ($name == 'protect') {
				$data['type'] = 'protect';
			}
			$fields[$name] = $data;
		}
		$this->admin_list = $fields;
		foreach (CMS::orm()->forms_posts->option('columns') as $column) {
			$this->post_natives[$column] = true; //TODO: ЧТО ТЫ ТАКОЕ
		}
	}
}