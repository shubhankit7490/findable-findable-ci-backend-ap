<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Validate
{
	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->helper(array('form', 'url'));
		$this->CI->load->library('form_validation');
		$this->CI->load->config('validate');
	}

	public function signup()
	{
		$url = 'welcome/test';
		$validate = $this->CI->config->item(__FUNCTION__, 'validate');

		$this->CI->form_validation->set_rules($validate);

		if($this->CI->form_validation->run() == FALSE)
			return $this->merge_data($validate, $url);

		return FALSE;
	}

	public function login()
	{
		$url = 'welcome/test';
		$validate = $this->CI->config->item(__FUNCTION__, 'validate');

		$this->CI->form_validation->set_rules($validate);

		if($this->CI->form_validation->run() == FALSE)
			return $this->merge_data($validate, $url);

		return FALSE;
	}

	private function merge_data($validate, $url)
	{
		$data['form_open'] = form_open($url);

		foreach($validate as $vkey => $vval)
		{
			$data['values'][$vval['field']] = set_value($vval['field']);
			$data['errors'][$vval['field']] = form_error($vval['field']);
		}

		return $data;
	}
}