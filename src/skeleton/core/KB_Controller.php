<?php
/**
 * CodeIgniter Skeleton
 *
 * A ready-to-use CodeIgniter skeleton  with tons of new features
 * and a whole new concept of hooks (actions and filters) as well
 * as a ready-to-use and application-free theme and plugins system.
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2018, Kader Bouyakoub <bkader@mail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package 	CodeIgniter
 * @author 		Kader Bouyakoub <bkader@mail.com>
 * @copyright	Copyright (c) 2018, Kader Bouyakoub <bkader@mail.com>
 * @license 	http://opensource.org/licenses/MIT	MIT License
 * @link 		https://github.com/bkader
 * @since 		1.0.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * KB_Controller Class
 *
 * All controllers should extend this class if you want to use all skeleton
 * features OR you can create your own MY_Controller inside application/core
 * and make it extend this class. Then, all your controllers may extend your
 * custom class, MY_Controller.
 *
 * @package 	CodeIgniter
 * @subpackage 	Skeleton
 * @category 	Core Extension
 * @author 		Kader Bouyakoub <bkader@mail.com>
 * @link 		https://github.com/bkader
 * @copyright	Copyright (c) 2018, Kader Bouyakoub (https://github.com/bkader)
 * @since 		1.0.0
 * @version 	1.4.0
 */
class KB_Controller extends CI_Controller {

	/**
	 * Holds the current user's object.
	 * @var object
	 */
	protected $c_user;

	/**
	 * Holds the redirection URL.
	 * @var string
	 */
	protected $redirect = '';

	/**
	 * Holds the current module's details.
	 * @since 	1.4.0
	 * @var 	array
	 */
	protected $module;

	/**
	 * Array of data to pass to views.
	 * @var array
	 */
	protected $data = array();

	/**
	 * Class constructor
	 * @return 	void
	 */
	public function __construct()
	{
		parent::__construct();

		// Load application main library.
		$this->load->driver('kbcore');

		// Get current module's details.
		$this->module = $this->router->module_details($this->router->fetch_module());

		// Load authentication library.
		$this->c_user = $this->auth->user();
		$this->theme->set('c_user', $this->c_user, true);

		// Always hold the redirection URL for eventual use.
		if ($this->input->get_post('next'))
		{
			$this->session->set_flashdata(
				'redirect',
				rawurldecode($this->input->get_post('next'))
			);
		}

		$this->redirect = $this->session->flashdata('redirect');

		// Add all necessary meta tags.
		$this->kbcore->set_meta();

		log_message('info', 'KB_Controller Class Initialized');
	}

	// ------------------------------------------------------------------------


	/**
	 * We are remapping things just so we can handle methods that are
	 * http accessed and methods that require AJAX requests only.
	 *
	 * @since 	1.0.0
	 * @since 	1.3.3 	Added logged-in user check for safe AJAX methods.
	 *
	 * @access 	public
	 * @param 	string 	$method 	The method's name.
	 * @param 	array 	$params 	Arguments to pass to the method.
	 * @return 	mixed 	Depends on the called method.
	 */
	public function _remap($method, $params = array())
	{
		// The method is not found? Nothing to do.
		if ( ! method_exists($this, $method))
		{
			show_404();
		}
		
		// Add a class to body class if the user is logged in.
		if (true === $this->auth->online())
		{
			$this->theme->set_body_class('logged-in');
		}

		// Call the method.
		return call_user_func_array(array($this, $method), $params);
	}

	// ------------------------------------------------------------------------

	/**
	 * Prepare form validation.
	 * @access 	public
	 * @param 	array 	$rules 	array of validation rules.
	 * @author 	Kader Bouyakoub
	 * @version 1.0
	 * @return void
	 */
	public function prep_form($rules = array())
	{
		// Load form validation library if not loaded.
		if ( ! class_exists('CI_Form_validation', false))
		{
			$this->load->library('form_validation');
		}

		// Load form helper if not loaded.
		if ( ! function_exists('form_open'))
		{
			$this->load->helper('form');
		}

		// If there are any rules, set them.
		if (is_array($rules) && ! empty($rules))
		{
			$this->form_validation->set_rules($rules);
		}

		// Load inputs config file.
		if ( ! $this->config->item('inputs'))
		{
			$this->load->config('inputs', true);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * check_nonce
	 *
	 * Method for checking forms with added security nonce.
	 *
	 * @author 	Kader Bouyakoub
	 * @link 	https://github.com/bkader
	 * @since 	1.4.0
	 *
	 * @access 	protected
	 * @param 	string 	$action 	The action attached (Optional).
	 * @param 	bool 	$referrer	Whether to check referrer.
	 * @param 	string 	$name 		The name of the field used as nonce.
	 * @return 	bool
	 */
	protected function check_nonce($action = null, $referrer = true, $name = '_csknonce')
	{
		// If the action is not provided, get if from the request.
		$real_action = (null !== $req = $this->input->request('action')) ? $req : -1;
		(null === $action) && $action = $real_action;

		// Initial status.
		$status = verify_nonce($this->input->request($name), $action);

		// We check referrer only if set and nonce passed test.
		if (true === $status && true === $referrer)
		{
			/**
			 * because till this line, the $status is set to TRUE,
			 * its value is changed according the referrer check status.
			 */
			$status = $this->check_referrer();
		}

		// Otherwise, return only nonce status.
		return $status;
	}

	// ------------------------------------------------------------------------

	/**
	 * check_referrer
	 *
	 * Method for comparing the request referrer to the hidden referrer field.
	 *
	 * @author 	Kader Bouyakoub
	 * @link 	https://github.com/bkader
	 * @since 	1.4.0
	 *
	 * @uses 	CI_User_agent
	 *
	 * @access 	public
	 * @param 	string 	$referrer 	The hidden field value (optional).
	 * @param 	string 	$name 		The name of the referrer field.
	 * @return 	bool
	 */
	protected function check_referrer($referrer = null, $name = '_csk_http_referrer')
	{
		(class_exists('CI_User_agent', false)) OR $this->load->library('user_agent');

		$real_referrer = $this->agent->referrer();
		(null === $referrer) && $referrer = $this->input->request($name, true);

		return (1 === preg_match("#{$referrer}$#", $real_referrer));
	}

	// ------------------------------------------------------------------------

	/**
	 * create_csrf
	 *
	 * Method for creating CSRF token for form. You may add it as hidden field.
	 *
	 * @author 	Kader Bouyakoub
	 * @link 	https://github.com/bkader
	 * 
	 * @since 	1.0.0
	 * @since 	1.4.0 	DEPRECATED: you may want to use [create/verify]_nonce.
	 *
	 * @access 	protected
	 * @param 	none
	 * @return 	array
	 */
	protected function create_csrf()
	{
		// Make sure to load string helper.
		(function_exists('random_string')) OR $this->load->helper('string');

		// Generate key and value.
		$csrf_key   = random_string('alnum', 8);
		$csrf_value = random_string('alnum', 32);

		// Store flash data.
		$this->session->set_flashdata(array(
			'csrf_key'   => $csrf_key,
			'csrf_value' => $csrf_value,
		));

		// Return the array for later use.
		return array($csrf_key => $csrf_value);
	}

	// ------------------------------------------------------------------------

	/**
	 * create_csrf
	 *
	 * Method for checking CSRF token.
	 * 
	 * @example:
	 * 	$data[hidden] = $this->create_csrf();	// Create the CSRF.
	 * 	$this->theme->render($data);			// Pass it to views.
	 * 	echo form_open($uri, $attrs, $hidden);
	 *
	 * @author 	Kader Bouyakoub
	 * @link 	https://github.com/bkader
	 * 
	 * @since 	1.0.0
	 * @since 	1.4.0 	DEPRECATED. You may want to use verify_nonce.
	 *
	 * @access 	protected
	 * @param 	none
	 * @return 	array
	 */
	protected function check_csrf()
	{
		$user_value = $this->input->post($this->session->flashdata('csrf_key'));
		$csrf_value = $this->session->flashdata('csrf_value');
		return ($user_value && $csrf_value && $user_value === $csrf_value);
	}

	// ------------------------------------------------------------------------
	// Captcha Methods.
	// ------------------------------------------------------------------------

	/**
	 * Generate a captcha field.
	 * @access 	protected
	 * @param 	int 	$guid 	the user's ID.
	 * @return 	array 	captcha image URL and form details.
	 */
	protected function create_captcha($guid = 0)
	{
		// Not using captcha at all?
		if (get_option('use_captcha', false) === false)
		{
			return array('captcha' => null, 'image' => null);
		}

		// Using reCAPTCHA?
		if (get_option('use_recaptcha', false) === true && ! empty(get_option('recaptcha_site_key', null)))
		{
			// Add reCAPTCHA script tag.
			$this->theme->add('js', 'https://www.google.com/recaptcha/api.js', 'recaptcha');

			// Return both captcha field and empty image.
			return array(
				'captcha' => '<div class="g-recaptcha" data-sitekey="'.get_option('recaptcha_site_key').'"></div>',
				'image'   => null,
			);
		}

		// Load captcha config file.
		$this->load->config('captcha', true);

		// Load captcha helper.
		$this->load->helper('captcha');

		// Generate the new captcha.
		$cap = create_captcha($this->config->item('captcha'));

		// Insert catpcha details into database if not found.
		$var = $this->kbcore->variables->get_by(array(
			'guid'   => $guid,
			'name'   => 'captcha',
			'params' => $this->input->ip_address(),
		));

		// If not found, create it.
		if ( ! $var)
		{
			$this->kbcore->variables->add_var(
				$guid,
				'captcha',
				$cap['word'],
				$this->input->ip_address()
			);
		}
		// Found? Update it.
		else
		{
			$this->kbcore->variables->update($var->id, array(
				'value'      => $cap['word'],
				'created_at' => time(),
				'params'     => $this->input->ip_address(),
			));
		}

		return array(
			'image' => $cap['image'],
			'captcha' => array(
				'type'        => 'text',
				'name'        => 'captcha',
				'id'          => 'captcha',
				'placeholder' => 'lang:captcha',
				'maxlength'   => $this->config->item('word_length', 'captcha'),
			),
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Check captcha.
	 * @access 	protected
	 * @param 	string 	$str 	captcha word
	 * @return 	bool
	 */
	protected function check_captcha($str)
	{
		// Return true if captcha is disabled.
		if (get_option('use_captcha', false) === false)
		{
			return true;
		}

		// Using Google reCAPTCHA?
		if (get_option('use_recaptcha', false) === true && ! empty(get_option('recaptcha_site_key', null)))
		{
			// Catch reCAPTCHA field.
			$recaptcha = $this->input->post('g-recaptcha-response');

			// Not set? Set the error message and return false.
			if (empty($recaptcha))
			{
				$this->form_validation->set_message('check_captcha', lang('form_validation_required'));
				return false;
			}

			$data = array(
				'secret'   => get_option('recaptcha_private_key'),
				'remoteip' => $this->input->ip_address(),
				'response' => $recaptcha,
			);

			// cURL is enabled?
			if (function_exists('curl_init'))
			{
				$verify = curl_init();
				curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
				curl_setopt($verify, CURLOPT_POST, true);
				curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($verify);
			}
			else
			{
				// Prepare the verification URL.
				$verify_url = 'https://www.google.com/recaptcha/api/siteverify?'.http_build_query($data);

				// Catch response and decode it.
				$response = file_get_contents($verify_url);
			}

			// Decode the response.
			$response = json_decode($response, true);

			echo print_d($response);
			exit;

			// Valid captcha?
			if (isset($response['success']) && $response['success'] === true)
			{
				return true;
			}
			// Invalid captcha?
			else
			{
				$this->form_validation->set_message('check_captcha', lang('form_validation_required'));
				return false;
			}
		}

		// No captcha set?
		if (empty($str))
		{
			$this->form_validation->set_message('check_captcha', lang('form_validation_required'));
			return false;
		}

		// First, we delete old captcha
		$this->kbcore->variables->delete_by(array(
			'name'         => 'captcha',
			'created_at <' => time() - (MINUTE_IN_SECONDS * 5),
		));

		// Check if the captcha exists or not.
		$var = $this->kbcore->variables->get_by(array(
			'name'          => 'captcha',
			'BINARY(value)' => $str,
			'params'        => $this->input->ip_address(),
		));

		// Found?
		if ($var)
		{
			return true;
		}
		// Not found? Generate the error.
		else
		{
			$this->form_validation->set_message('check_captcha', lang('error_captcha'));
			return false;
		}
	}

}
