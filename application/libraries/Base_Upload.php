<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

use google\appengine\api\cloud_storage\CloudStorageTools;

class Base_Upload extends CI_Upload {
	public $bucket_name = null;
	protected $token = false;

	function __construct( $config = array() ) {
		parent::__construct( $config );
	}

	public function do_upload( $field = 'userfile' ) {
		if ( ENVIRONMENT == 'development' ) {
			return parent::do_upload( $field );
		} else {
			$file = $_FILES[ $field ];

			if ( ! isset( $file ) ) {
				$this->set_error( 'upload_no_file_selected', 'debug' );

				return false;
			}

			$this->upload_path = $this->bucket_name . '/user_files/';

			$this->file_temp   = $file['tmp_name'];
			$this->file_size   = $file['size'];
			$this->file_type   = preg_replace( '/^(.+?);.*$/', '\\1', $file['type'] );
			$this->file_type   = strtolower( trim( stripslashes( $this->file_type ), '"' ) );
			$this->file_ext    = pathinfo( $file['name'], PATHINFO_EXTENSION );
			$this->client_name = $this->file_name;

			if ( $this->file_size > 0 ) {
				$this->file_size = round( $this->file_size / 1024, 2 );
			}

			$options = [
				'gs' => [
					'enable_cache'              => false,
					'read_cache_expiry_seconds' => 0,
					'Cache-Control'             => '0'
				]
			];
			$context = stream_context_create( $options );
			if ( ! file_put_contents( $this->upload_path . $this->file_name . '.' . $this->file_ext, file_get_contents( $file['tmp_name'] ), 0, $context ) ) {
				$this->set_error( 'upload_unable_to_write_file', 'error' );

				return false;
			} else {
				$this->set_image_properties( $this->upload_path . $this->file_name . '.' . $this->file_ext );
				$this->file_name = $this->file_name . '.' . $this->file_ext;

				return true;
			}
		}
	}
	public function do_upload_multiple( $field = 'userfile',$index=0 ) {
		if ( ENVIRONMENT == 'development' ) {
			return parent::do_upload_multiple_file( $field,$index);
		} else {
			$file = $_FILES[ $field ];

			if ( ! isset( $file ) ) {
				$this->set_error( 'upload_no_file_selected', 'debug' );

				return false;
			}

			$this->upload_path = $this->bucket_name . '/user_files/';

			$this->file_temp   = $file['tmp_name'][$index];
			$this->file_size   = $file['size'][$index];
			$this->file_type   = preg_replace( '/^(.+?);.*$/', '\\1', $file['type'][$index] );
			$this->file_type   = strtolower( trim( stripslashes( $this->file_type ), '"' ) );
			$this->file_ext    = pathinfo( $file['name'][$index], PATHINFO_EXTENSION );
			$this->client_name = $this->file_name;

			if ( $this->file_size > 0 ) {
				$this->file_size = round( $this->file_size / 1024, 2 );
			}

			$options = [
				'gs' => [
					'enable_cache'              => false,
					'read_cache_expiry_seconds' => 0,
					'Cache-Control'             => '0',
					'acl' => 'public-read'
				]
			];
			$context = stream_context_create( $options );
			if ( ! file_put_contents( $this->upload_path . $this->file_name . '.' . $this->file_ext, file_get_contents( $file['tmp_name'][$index] ), 0, $context ) ) {
				$this->set_error( 'upload_unable_to_write_file', 'error' );

				return false;
			} else {
				$this->set_image_properties( $this->upload_path . $this->file_name . '.' . $this->file_ext );
				$this->file_name = $this->file_name . '.' . $this->file_ext;

				return true;
			}
		}
	}
	public function get_file($path = '') {
		return file_get_contents($path);
	}

	public function get_upload_path(){
		if ( ENVIRONMENT == 'development' ) {
			return $this->upload_path;
		} else {
			return $this->bucket_name;
		}
	}

	public function serve( $file = false, $defaults = false ) {
		$options = (!$defaults)? [ 'size' => 400, 'crop' => true, 'secure_url' => true ] : $defaults;
		$path    = ( ! $file ) ? $this->upload_path . $this->file_name : $file;

		if ( ENVIRONMENT != 'development' ) {
			return CloudStorageTools::getImageServingUrl( $path, $options );
		} else {
			return $path;
		}
	}

	/**
	 * generate_token
	 *
	 * Generate a random 16 byte length token
	 *
	 * @access    public
	 *
	 * @params integer bytes
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function generate_token( $bytes = 16 ) {
		if ( function_exists( 'random_bytes' ) ) {
			$token = random_bytes( $bytes );
		} else if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$token = openssl_random_pseudo_bytes( $bytes );
		} else {
			return false;
		}

		return bin2hex( $token );
	}

	/**
	 * get_public_url
	 *
	 * Get the public url depending on the active environment
	 *
	 * @access    public
	 *
	 * @param string $file
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_public_url( $path = '' ){
		if ( ENVIRONMENT == 'development' ) {
			return $path;
		} else {
			return CloudStorageTools::getPublicUrl( $path, false );
		}
	}


	public function serve_file( $path = '' ) {
		if ( ENVIRONMENT == 'development' ) {
			return $path;
		} else {
			return CloudStorageTools::serve( $path );
		}
	}
	
}