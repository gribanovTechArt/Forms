<?php
Core::load('Component.Forms.DB.Items');
Core::load('Component.Forms.Render');

class Component_Forms_Controller_Send extends CMS_Controller implements Core_ModuleInterface
{
	protected $cookie_expire = 8640000;
	protected $uploads_dir = '../files/forms';
	protected $default_subject = 'Сообщение с сайта';
	protected $default_replay_subject = 'Ваше сообщение принято';
	protected $default_ajax_ok_message = 'Ваше сообщение отправлено';
	protected $mail_layout = 'mail';
	protected $mail_replay_layout = 'mail';

	protected $do_attach_uploads = true;
	protected $do_attach_uploads_replay = true;
	protected $do_send_email = true;
	protected $do_send_replay_email = true;
	protected $do_insert = true;
	protected $action_common_name;
	protected $action_full_name;
	protected $form_item;
	protected $form_parms;
	protected $form;
	protected $fields;

	protected function on_before_insert($post)
	{
	}

	protected function on_after_insert($post)
	{
	}

	protected function form_errors($form)
	{
		return false;
	}

	public function send($name)
	{
		$name = trim($name);
		if ($this->env->request->method != 'post')
			return $this->page_not_found();

		$form_item = Component_Forms_Render::get_form_item($name);
		if (!$form_item)
			return $this->page_not_found();
		$form = Component_Forms_Render::item_to_form($form_item);

		$this->form = $form;
		$this->form_item = $form_item;
		$this->form_parms = $form_item->parms;
		$this->fields = $form_item->fields;

		$this->action_common_name = $form_item->common_name;
		$this->action_full_name = str_replace('.', '_', $name);

		$messages = (array)CMS_Fields::process_form($form, $this->env->request);
		$uerrors = $this->do_action_with_return('form_errors', $form);
		if ($uerrors) {
			if (is_string($uerrors))
				$uerrors = array($uerrors);
			$messages += $uerrors;
		}

		if (Component_Forms_Render::$digital_protect) {
			$code = trim($form['protect']);
			Core::load('CMS.Protect');
			if ($code != CMS_Protect::key($name) || $code == '')
				$messages[] = Component_Forms_Render::$digital_protect_message;
		}

		if (sizeof($messages) > 0)
			return $this->render_invalid(Component_Forms_Render::template('layout', $this->form_item), Component_Forms_Render::get_render_parms($form, $form_item, $messages, $this->is_ajax_request()));

		$post = new Component_Forms_DB_Post(array(
			'form_id' => $form_item->id,
			'form_name' => $name,
			'server_info' => serialize($_SERVER)
		), CMS::orm()->forms_posts);

		foreach ($form_item->fields as $field => $data) {
			$data = Component_Forms_Render::parms_for_form($name, $data, true);
			if (!$data)
				continue;
			if ($data['type'] == 'upload')
				continue;
			if ($data['type'] == 'hr')
				continue;
			if ($data['type'] == 'subheader')
				continue;

			$field_type = CMS_Fields::type($data);
			$field_type->assign_to_object($form, $post, $field, $data);

			if (isset($data['cookie']))
				setcookie($data['cookie'], $form[$field], time() + $this->cookie_expire, '/');
		}

		$this->do_action('on_before_insert', $post);
		if ($this->do_insert) {
			$post->insert();
			$this->process_uploads($post, $form_item->fields);
			$this->do_action('on_after_insert', $post);
		}

		if ($this->do_send_email) {
			$this->send_email($post);
		}

		if ($this->do_send_replay_email) {
			$this->send_replay_email($post);
		}

		return $this->on_after_send();
	}

	protected function render_invalid($tpl, $parms)
	{
		if ($this->is_ajax_request())
			$this->no_layout();
		return $this->render($tpl, $parms);
	}

	protected function run_action($action, $arg = false)
	{
		if (!method_exists($this, $action))
			return false;
		return $this->$action($arg);
	}

	protected function do_action($action, $arg = false)
	{
		$this->run_action($action, $arg);
		$this->run_action($action . '_' . $this->action_common_name, $arg);
		if ($this->action_common_name != $this->action_full_name)
			$this->run_action($action . '_' . $this->action_full_name, $arg);
	}

	protected function do_action_with_return($action, $arg = false)
	{
		$rc = $this->run_action($action . '_' . $this->action_full_name, $arg);
		if ($rc === false)
			$rc = $this->run_action($action . '_' . $this->action_common_name, $arg);
		if ($rc === false)
			$rc = $this->run_action($action, $arg);
		return $rc;
	}

	protected function process_uploads($post, $fields)
	{
		if (count(Component_Forms_Render::$uploads) == 0)
			return;
		foreach (Component_Forms_Render::$uploads as $field => $dir) {
			if ($dir == '')
				$dir = $this->uploads_dir;
			if (is_object($this->form[$field])) {
				$path = $this->form[$field]->path;
				$original_name = $this->form[$field]->original_name;
				$ext = '';
				if ($m = Core_Regexps::match_with_results('{(\.[a-z0-9_]+)$}i', $original_name))
					$ext = $m[1];
				$dir = rtrim($dir, '/');
				$did = floor($post->id / 100) * 100;
				$dir .= "/$did/$post->id";
				if (!IO_FS::exists($dir)) {
					IO_FS::make_nested_dir($dir, 0775);
					chmod($dir, 0775);
				}
				$name = "$dir/$field$ext";
				move_uploaded_file($path, $name);
				chmod($name, 0775);
				$post->$field = $name;
			}
		}
		$post->update();
	}

	protected function mail_transport()
	{
		return Mail_Transport::php();
	}

	protected function send_email($post)
	{
		$emails = $this->mail_param('send_to');
		if (!$emails)
			return false;
		if (is_string($emails))
			$emails = array($emails => '');
		$mail = $this->do_action_with_return('create_email', $post);
		$transport = $this->mail_transport();
		foreach ($emails as $email_key => $email_val) {
			$email = $this->is_email($email_val) ? $email_val : $email_key;
			$mail->to($email);
			$transport->send($mail);
		}
	}

	protected function mail_param($s, $def = false)
	{
		if ($m = Core_Regexps::match_with_results('/^var:(.+)$/', $s)) {
			return CMS::vars()->get(trim($m[1]));
		}
		if ($m = Core_Regexps::match_with_results('/^\{(.+)\}$/', $s)) {
			if (isset(Component_Forms::$vars[$m[1]]))
				return Component_Forms::$vars[$m[1]];
			return CMS::$globals[$m[1]];
		}
		if (isset($this->form_parms[$s])) {
			$s = $this->form_parms[$s];
			if (is_string($s))
				if (isset($this->fields[$s]))
					return $this->form[$s];
			return $s;
		}
		return $def;
	}

	protected function send_template_path($tpl)
	{
		$tpl = trim($tpl);
		if ($tpl == '')
			$tpl = 'send';
		if ($tpl[0] == '.' || $tpl[0] == '/')
			return $tpl;
		if (!Core_Regexps::match('{\.phtml$}', $tpl))
			$tpl = "$tpl.phtml";
		return $this->view_path_for($tpl);
	}

	protected function create_email($post)
	{
		$subject = $this->mail_param('send_subject', $this->default_subject);
		$from = $this->mail_param('send_from', false);
		$tpl = trim($this->form_parms['send_template']);

		return $this->create_email_common($post, $subject, $from, $tpl, $this->mail_layout);
	}

	protected function send_replay_email($post)
	{
		$email = $this->mail_param('send_replay_to');
		if (!$email)
			return false;
		$mail = $this->do_action_with_return('create_replay_email', $post)->to($email);
		$transport = $this->mail_transport();
		$transport->send($mail);
	}

	protected function create_replay_email($post)
	{
		$subject = $this->mail_param('send_replay_subject', $this->default_replay_subject);
		$from = $this->mail_param('send_replay_from', false);
		$tpl = trim($this->form_parms['send_replay_template']);

		return $this->create_email_common($post, $subject, $from, $tpl, $this->mail_replay_layout);
	}

	protected function create_email_common($post, $subject, $from, $tpl, $layout)
	{
		Core::load('Mail');
		Core::load('CMS.Mail');

		$attaches_exists = false;
		$files = array();
		if ($this->do_attach_uploads) {
			foreach (Component_Forms_Render::$uploads as $field => $dir) {
				$f = trim($post->$field);
				if ($f != '') {
					$attaches_exists = true;
					$files[$f] = $this->form[$field]->original_name;
				}
			}
		}

		$html = CMS::render_mail($this->send_template_path($tpl), array(
			'post' => $post,
			'fields' => $this->fields,
			'form' => $this->form,
			'parms' => $this->form_parms,
			'subject' => $subject,
		), $layout);

		$mail_body = CMS_Mail::with_images($html);

		if ($attaches_exists) {
			$mail = Mail::Message();
			$mail->multipart_mixed();
			$mail->part($mail_body);

			foreach ($files as $path => $oname)
				$mail->file_part($path, $oname);
		} else {
			$mail = $mail_body;
		}

		$mail->subject($subject);
		if ($from)
			$mail->from($from);

		return $mail;
	}

	protected function is_ajax_request()
	{
		return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}


	protected function on_after_send()
	{
		if (!$this->is_ajax_request()) {
			$redirect = trim($this->form_item->parms['ok']);
			if ($redirect == '')
				$redirect = '/';

			$ref = $this->form['_referer_'];
			Core::load('Net.HTTP.Session');
			$session = Net_HTTP_Session::Store();
			$ret = trim($session['forms/return_for/' . $ref]);
			if ($ret != '')
				$redirect = $ret;

			return $this->redirect_to($redirect);
		} else {
			return $this->render_ajax_ok();
		}
	}

	protected function render_ajax_ok()
	{
		$this->no_layout();
		$message = $this->form_item->parms['ajax_ok'] ? $this->form_item->parms['ajax_ok'] : $this->default_ajax_ok_message;
		return $this->render(Component_Forms_Render::template('ajax-ok', $this->form_item), array(
			'message' => $message,
			'form' => $this->form_item
		));
	}

	protected function is_email($email)
	{
		return Core_Regexps::match('{^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$}', $email);
	}

}
