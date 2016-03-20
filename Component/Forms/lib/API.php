<?php
/**
 * Class Component_Forms_API
 *
 * Класс для работы с формами через другие компоненты,
 * вызов осуществляется через CMS::objects()->forms->{method_name}
 */
class Component_Forms_API implements Core_ModuleInterface
{
	/**
	 * @param string $name
	 * @return int
	 */
	public function exists($name)
	{
		return CMS::orm()->forms->name($name)->count();
	}

	/**
	 * @param string $name
	 * @param array $data
	 *
	 * @return bool
	 */
	public function create($name = null, $data = null)
	{
		/**
		 * @var Component_Forms_App_DB_Items $orm
		 * @var Component_Forms_App_DB_Item $form
		 */
		$orm = CMS::orm()->forms;
		$form = $orm->name($name)->select_first();
		if ($form || $name === null || $data === null) {
			return false;
		}
		$form = $orm->make_entity();
		$form->name = $name;
		$form->assign_data($data);
		$form->insert();

		return true;
	}
}
