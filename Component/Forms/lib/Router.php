<?php

class Component_Forms_Router extends CMS_Router implements Core_ModuleInterface
{
	protected $controllers = array(
		'Component.Forms.App.Controller.Send' => array(
			'path' => '/forms/',
			'rules' => array(
				'{^([^/]+)/$}' => array('{1}', 'action' => 'send'),
			),
		),
		'Component.Forms.App.Admin.PostsController' => array(
			'path' => '{admin:forms/posts}',
			'table-admin' => true,
		),
		'Component.Forms.App.Admin.Items' => array(
			'path' => '{admin:forms}',
			'table-admin' => true,
		),
	);

	public function send_url($id, $name)
	{
		return "/forms/$name/";
	}

	public function admin_posts_url($form_id)
	{
		return CMS::admin_path('forms/posts') . '?form_id=' . $form_id;
	}
}