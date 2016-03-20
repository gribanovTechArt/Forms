<?php
Core::load('CMS.Controller.Table');
Core::load('Component.Forms.DB.Items');

class Component_Forms_Admin_PostsController extends CMS_Controller_Table
{
	protected $orm_name = 'forms_posts';
	protected $title_list = 'Сообщения с веб-формы';
	protected $title_edit = 'Редактирование сообщения';
	protected $title_add = 'Добавление сообщения';
	protected $norows = 'Сообщения отсутствуют';

	protected $button_add = 'Добавить сообщение';

	protected $filters = array('form_id');

	protected $list_fields = array(
		'idate' => array(
			'caption' => 'Дата/Время',
		),
	);

	protected function on_before_action()
	{
		$this->force_filters['site'] = CMS_Admin::site();
	}

	/**
	 * @param Component_Forms_DB_Post $row
	 */
	protected function on_row($row)
	{
		//TODO: получать значение так или использовать статический метод ComponentForms::admin_posts_url($row->id)
		$url = WS::env()->urls->forms->admin_posts_url($row->id);
		$row->title = "<a href='$url'>" . htmlspecialchars($row->title) . "</a>";
		$row->idate = date('d.m.Y - G:i', $row->idate);

		foreach ($this->list_fields as $name => $field) {
			if ($name != 'title') {
				$row->$name = htmlspecialchars($row->$name);
			}
		}
	}

	/**
	 * @return array
	 */
	protected function form_fields()
	{
		//TODO: возможно ли оставить так
//		if (!$this->form_fields) {
		$form = Component_Forms_DB_Item::form_from_request();
		if ($form) {
			$this->create_form_fields($form);
		}
//		}
//		var_dump($this->form_fields);die('1');
		return $this->form_fields;
	}

	/**
	 * @param Component_Forms_DB_Item $form
	 */
	protected function create_form_fields($form)
	{
		$this->form_fields = array();
		foreach ($form->fields as $field_name => $data) {
			unset($data['value']); //TODO: зачем unset для value
			if ($field_name != 'protect') {
				$this->form_fields[$field_name] = $data;
			}
		}
	}

	protected function on_before_list()
	{
		$form_id = WS::env()->request['form_id'];
		$form = CMS::orm()->forms->find($form_id);
		if ($form) {
			$this->title_list = $form->title;
			$fields = $form->admin_list;
			if (isset($fields['idate'])) {
				unset($this->list_fields['idate']);
			}
			foreach ($fields as $f => $data) {
				$this->list_fields[$f] = $data;
			}
		}
	}

	protected function mnemocode()
	{
		return "component.forms.posts.admin.{$this->action}";
	}

}
