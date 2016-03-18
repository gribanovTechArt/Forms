<?php

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

//	protected function map_cou
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

	public static function form_from_request()
	{
		return CMS::orm()->forms->find((int)$_GET['form_id']);
	}

	public function after_find()
	{
		parent::after_find();
		$this->create_fields();
	}

	public function assign_data($data = array())
	{
		$to_parms = $data;
		if ($data['title']) {
			$this->title = $data['title'];
			unset($to_parms['title']);
		}

		$this->parmsrc = CMS::unparse_parms($to_parms);
		$this->parms = CMS::parse_parms($this->parmsrc);
		return;
	}

	protected function serialized()
	{
		return array('parms');
	}

	protected function create_fields()
	{
		if (CMS::admin())
			Core::load('Component.Forms.Render');
		$flds = $this->parms['fields'];

		if (!is_array($flds))
			$flds = array();
		$fields = array();
		foreach ($flds as $f => $data) {
			if (CMS::admin())
				$data = Component_Forms_Render::parms_for_form('admin', $data, false);

			$name = $f;
			$caption = $data['caption'];
			if (isset($data['name'])) {
				$name = $data['name'];
				$caption = $f;
			}
			unset($data['name']);
			$data['caption'] = $caption;
			unset($data['in admin']);

			$fields[$name] = $data;
		}
		$this->fields = $fields;

		$flds = $this->parms['admin_list'];
		if (!is_array($flds))
			$flds = array();
		$fields = array();
		foreach ($flds as $f => $data) {

			if (is_string($data)) {
				if (Core_Regexps::match('{^[a-z0-9_]+$}', $f)) {
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
			if ($data['type'] == 'protect')
				$name = 'protect';
			if ($name == 'protect')
				$data['type'] = 'protect';
			$fields[$name] = $data;
		}
		$this->admin_list = $fields;
		foreach (CMS::orm()->forms_posts->option('columns') as $column)
			$this->post_natives[$column] = true;
	}
}