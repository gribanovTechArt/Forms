<?php

Core::load('Component.Forms.DB.Items');
Core::load('CMS.Fields');

class Component_Forms_Render implements Core_ModuleInterface
{
	static $initialized = false;
	static $parms;
	static $name;
	static $fields;
	static $service_fields;
	static $jsvalidate;
	static $validator;
	static $uploads;
	static $digital_protect;
	static $digital_protect_message = 'Неправильный цифровой код';
	static $js_protect_message = 'Неправильный защитный код. Возможно, у вас отключены cookie или JavaScript';
	static $js_lib_required = false;

	static function run($p, $ajax = false)
	{
		$name = trim($p);
		$form_item = self::get_form_item($name);
		if (!$form_item)
			return "FORM{{$p}}";

		$form = self::item_to_form($form_item);
		self::$name = $name;

		Core::load('Net.HTTP.Session');
		$session = Net_HTTP_Session::Store();
		$session['forms/return_for/' . md5($_SERVER['REQUEST_URI'])] = trim(Component_Forms::$vars['ok']);

		$content = CMS::render_in_page(self::template('layout', $form_item), self::get_render_parms($form, $form_item, false, $ajax));
		$content = CMS::process_insertions($content);
		return $content;
	}

	static function get_form_item($name)
	{
		$full_name = $name;
		if ($m = Core_Regexps::match_with_results('/^([^\.]+)\./', $name))
			$name = $m[1];

		$form_item = CMS::orm()->forms->name($name)->select_first();

		if ($form_item) {
			$form_item->after_find();
			$form_item->common_name = $name;
			$form_item->full_name = $full_name;
			$form_item->parms = self::parms_for_form($full_name, $form_item->parms);

			self::$parms = $form_item->parms;
		}
		return $form_item;
	}

	static function parms_string_value($value)
	{
		if (is_string($value)) {
			if ($m = Core_Regexps::match_with_results('/^\{(.+)\}$/', $value)) {
				$k = trim($m[1]);
				if (isset(Component_Forms::$vars[$k]))
					$value = Component_Forms::$vars[$k];
				else
					$value = CMS::$globals[$k];
			} else if ($m = Core_Regexps::match_with_results('/^var:(.+)$/', $value)) {
				$k = trim($m[1]);
				$value = CMS::vars()->get($k);
			}
		}
		return $value;
	}

	static function parms_for_form($name, $parms, $in = false)
	{
		if ($in && isset($parms['in']) && $parms['in'] != $name || isset($parms['not in']) && $parms['not in'] == $name)
			return false;
		$out = $parms;
		foreach ($parms as $key => $value) {
			$key = trim($key);
			$do = false;
			$del = false;
			$f = false;

			if (is_string($value)) {
				$value = self::parms_string_value($value);
				if (is_null($value))
					unset($out[$key]);
				else
					$out[$key] = $value;
			}

			if ($m = Core_Regexps::match_with_results('{^in\s+(.+)$}', $key)) {
				$fn = trim($m[1]);
				if ($mm = Core_Regexps::match_with_results('{^([^\s]+)\s+(.+)$}', $fn)) {
					$fn = trim($mm[1]);
					$f = trim($mm[2]);
				}
				$del = true;
				if ($fn == $name)
					$do = true;
			} else if ($m = Core_Regexps::match_with_results('{^not\s+in\s+(.+)$}', $key)) {
				$fn = trim($m[1]);
				if ($mm = Core_Regexps::match_with_results('{^([^\s]+)\s+(.+)$}', $fn)) {
					$fn = trim($mm[1]);
					$f = trim($mm[2]);
				}
				$del = true;
				if ($fn != $name)
					$do = true;
			}

			if ($del)
				unset($out[$key]);
			if ($do) {
				if (is_string($f))
					$out[$f] = $value;
				else if (is_array($value))
					foreach ($value as $k => $v)
						$out[$k] = self::parms_string_value($v);
				else if (CMS::check_no($value))
					return false;
			}
		}
		return $out;
	}

	static function item_to_form($form_item)
	{
		$form = Forms::Form(str_replace('.', '_', $form_item->full_name))->action(Component_Forms_Router::send_url($form_item->id, $form_item->full_name));

		self::$service_fields = self::get_service_fields($form_item->full_name);
		CMS_Fields::form_fields($form, self::$service_fields);

		self::$fields = array();

		self::$jsvalidate = array();
		self::$uploads = array();
		self::$digital_protect = false;

		foreach ($form_item->fields as $name => $field) {
			$f = self::parms_for_form($form_item->full_name, $field, true);
			if (!$f)
				continue;
			self::add_form_field($form, $name, $f);
		}
		CMS_Fields::form_fields($form, self::$fields);
		return $form;
	}

	static function add_form_field(&$form, $name, &$field)
	{
		$type = trim($field['type']);
		$value = trim($field['value']);
		$cookie = trim($field['cookie']);
		if ($field['placeholder']) {
			self::$js_lib_required = true;
		}

		$m = Core_Regexps::match_with_results('/^\{(.+)\}$/', $value);
		if ($m)
			$value = CMS::$globals[$m[1]];
		if ($cookie != '')
			$value = $_COOKIE[$cookie];
		$field['value'] = $value;

		if ($type == 'upload')
			self::$uploads[$name] = trim($field['dir']);

		self::$fields[$name] = $field;
	}

	static function get_service_fields($full_name)
	{
		return array(
			'_form_name_' => array(
				'type' => 'hidden',
				'value' => $full_name,
				'layout' => false
			),
			'_referer_' => array(
				'type' => 'hidden',
				'value' => md5($_SERVER['REQUEST_URI'])
			)
		);
	}

	static function get_render_parms($form, $form_item, $messages = false, $ajax = false)
	{
		if (self::$parms['ajax.submit'])
			$ajax = true;

		$form_attrs = array();
		if ($ajax)
			$form_attrs['data-ajax-form'] = 1;

		$parms = array(
			'id' => $form_item->id,
			'name' => $form_item->name,
			'full_name' => $form_item->name,
			'common_name' => $form_item->common_name,
			'fields' => self::$fields,
			'service_fields' => self::$service_fields,
			'form' => $form,
			'form_item' => $form_item,
			'form_attrs' => $form_attrs,
			'jsvalidate' => self::$jsvalidate,
			'data' => self::$parms,
			'header' => self::$parms['header'],
			'submit_value' => self::$parms['submit'],
			'ajax' => $ajax,
			'js_lib_required' => self::$js_lib_required,
		);
		if ($messages)
			$parms['messages'] = $messages;
		return $parms;
	}

	static function template($name, $form_item)
	{
		$cache_name = "forms:templates:$form_item->full_name:$name";
		$template = WS::env()->cache->get($cache_name);
		if(!$template) {
			$template = false;

			$template_dirs = array(
				self::name_to_path($form_item->full_name),
				self::name_to_path($form_item->name),
				CMS::component_dir('Forms', 'views')
			);

			foreach($template_dirs as $template_dir) {
				if(IO_FS::exists(self::template_path($name, $template_dir))) {
					$template = self::template_path($name, $template_dir);
					break;
				}
			}

			if(!$template)
				$template = self::template_for_compatibility($name, $form_data);

			WS::env()->cache->set($cache_name, $template);
		}

		return $template;
	}

	static function template_for_compatibility($name)
	{
		$template = false;
		switch($name) {
			case 'layout':
				$template = 'form.phtml';
				break;
			case 'inner':
				$template = 'form-inner.phtml';
				if(self::$parms['tpl.inner']) {
					$template = self::$parms['tpl.inner'];
				}
				break;
			case 'fields':
				$template = 'form-fields.phtml';
				if(self::$parms['template.form']) {
					$template = self::$parms['template.form'];
				}
				break;
		}
		return self::template_path($template);
	}

	static function template_path($template, $dir = false) {
		if (!Core_Regexps::match('{\.phtml$}', $template))
			$template = "$template.phtml";

		if(!$dir)
			$dir = CMS::component_dir('Forms', 'views');

		return $dir.'/'.$template;
	}

	static function name_to_path($name) {
		return CMS::component_dir('Forms', 'views/').str_replace('.', '_', $name);
	}

}
