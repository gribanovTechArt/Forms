<?php

class Component_Forms_API implements Core_ModuleInterface
{
	public function exists($name)
	{
		return CMS::orm()->forms->name($name)->count();
	}

	public function create($name, $data)
	{
		$form = CMS::orm()->forms->name($name)->select_first();
		if($form) {
			return false;
		}
		$form = CMS::orm()->forms->make_entity();
		$form->name = $name;

		$form->assign_data($data);
		$form->insert();
	}
}
