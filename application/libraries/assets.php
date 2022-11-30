<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Assets
{
	public $css = NULL;
	public $js = NULL;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->config->load('assets');
		$this->ci->load->library('carabiner');
	}

	public function load($view)
	{
		$_assets = $this->ci->config->item('static');
		$_assets = array_merge_recursive($_assets, $this->ci->config->item($view));

		foreach($_assets['css'] as $key => $style)
			$this->ci->carabiner->css($style);

		foreach($_assets['js'] as $key => $script)
			$this->ci->carabiner->js($script);

		$this->css = $this->ci->carabiner->display_string('css');
		$this->js = $this->ci->carabiner->display_string('js');

		return $this;
	}
}