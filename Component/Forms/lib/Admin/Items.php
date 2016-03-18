<?php
Core::load('CMS.Controller.Table');

class Component_Forms_Admin_Items extends CMS_Controller_Table
{
	protected $orm_name = 'forms';
	protected $title_list = 'Веб-формы';
	protected $title_edit = 'Редактирование веб-формы';
	protected $title_add = 'Добавление веб-формы';
	protected $norows = 'Веб-формы отсутствуют';

	protected $button_add = 'Добавить веб-форму';

	protected $list_fields = array(
		'name' => array(
			'caption' => 'Идентификатор',
		),
		'title' => array(
			'caption' => 'Название формы',
			'td' => array('width' => '100%'),
		),
	);

	protected function on_before_action()
	{
		$this->force_filters['site'] = CMS_Admin::site();
	}

	protected function on_row($row)
	{
		//TODO: получать значение так или использовать статический метод ComponentForms::admin_posts_url($row->id)
		$url = WS::env()->urls->forms->admin_posts_url($row->id);
		$row->title = "<a href='$url'>$row->title</a>";
	}

	protected function mnemocode()
	{
		return "component.forms.admin.{$this->action}";
	}

}
