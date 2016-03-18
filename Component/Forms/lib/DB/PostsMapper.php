<?php

class Component_Forms_DB_PostsMapper extends CMS_ORM_Mapper implements Core_ModuleInterface
{
	public function setup()
	{
		return $this
			->classname('Component.Forms.DB.Post')
			->table('forms_posts')
			->key('id')
			->columns('id', 'form_id', 'form_name', 'idate', 'val', 'server_info')
			->order_by('idate desc');
	}

	protected function map_form_id($form_id)
	{
		return $this->where('form_id=:form_id', $form_id);
	}

	protected function map_site($form_id)
	{
		$site = CMS::admin() ? CMS_Admin::site() : CMS::site();
		return $this->join('inner', 'forms', 'forms.id=form_id')->where('forms.site=:site', $site);
	}
}

class Component_Forms_DB_Post extends CMS_ORM_Entity
{
	public function setup()
	{
		$this->form = Component_Forms_DB_Item::form_from_request();
		if ($this->form) {
			$this->form_id = $this->form->id;
			$this->form_name = $this->form->name;
		} else
			$this->form_name = '';

		$this->idate = time();
		$this->server_info = '';
		return $this;
	}

	public function row_set_val($value)
	{
		$this->attrs['val'] = unserialize($value);

		if (!is_array($this->val)) $this->val = array();
		$this->form = CMS::orm()->forms->find($this->form_id);
		if ($this->form) {
			foreach ($this->form->fields as $f => $data) {
				if ($f != 'protect') $this->$f = $this->val[$f];
			}
		}
	}

	public function row_get_val()
	{
		$val = array();
		$this->form = $this->db()->forms->find($this->form_id);
		if ($this->form) {
			foreach ($this->form->fields as $f => $data) {
				if ($f != 'protect') $val[$f] = $this->attributes[$f];
			}
		}
		$this->val = serialize($val);
		return $this->val;
	}
}