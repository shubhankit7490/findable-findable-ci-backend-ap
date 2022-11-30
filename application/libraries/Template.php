<?php
if ( ! defined( 'BASEPATH' ) ) {
	exit( 'No direct script access allowed' );
}

require_once( APPPATH . 'libraries/vendor/smarty/smarty/libs/Smarty.class.php' );

class Template extends Smarty {
	public function __construct() {
		parent::__construct();

		$this->setTemplateDir( APPPATH . "views/templates" );
		//$this->setCompileDir( APPPATH . "views/compiled");
		$this->assign( 'APPPATH', APPPATH );
		$this->assign( 'BASEPATH', BASEPATH );
		$this->assign( 'BASEURL', base_url() );

		if ( ENVIRONMENT == 'development' ) {
			$this->compile_dir = APPPATH . "views/compiled/";
		} else {
			$this->compile_dir = "gs://staging.findable-api.appspot.com/template_files/compiled/";
			$this->cache_dir   = "gs://staging.findable-api.appspot.com/template_files/cache/";
		}

		// Assign CodeIgniter object by reference to CI
		if ( method_exists( $this, 'assignByRef' ) ) {
			$ci =& get_instance();
			$this->assignByRef( "ci", $ci );
		}
	}

	function view( $template, $data = array(), $return = false ) {

		foreach ( $data as $key => $val ) {
			$this->assign( $key, $val );
		}
		if ( $return == false ) {
			$CI =& get_instance();
			if ( method_exists( $CI->output, 'set_output' ) ) {
				$CI->output->set_output( $this->fetch( $template . '.html' ) );
			} else {
				$CI->output->final_output = $this->fetch( $template . 'html' );
			}

			return;
		} else {
			return $this->fetch( $template . '.html' );
		}
	}

	function recompile() {
		$this->clearAllCache();
		$this->clearCompiledTemplate();
		$this->force_compile = true;
		$this->compile_check = true;

		return $this;
	}
}