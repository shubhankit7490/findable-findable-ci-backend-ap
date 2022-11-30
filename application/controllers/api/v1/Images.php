<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Images extends Base_Controller
{
	protected static $user = 'user';

	protected static $business = 'business';

	public function __construct() {
		parent::__construct();
	}

	public function index_post( $role )
	{
		$valid = true;
		$error = false;

		// Load the upload configurations
		$this->config->load( 'upload' );
		$config = $this->config->item( 'upload' );

		// Set the file name as the user's id
		if ( $role == self::$user ) {
			$config['file_name'] = $role . '_' . $this->get_active_user() . '_' . time();
		} else if ( $role == self::$business ) {
			$config['file_name'] = $role . '_' . $this->get_manager_business() . '_' . time();
		}

		// Load the file upload library
		$this->load->library( 'upload', $config );

		// Start uploading the file
		if ( ! $this->upload->do_upload( 'file' ) ) {
			$valid = false;
			$error = $this->upload->display_errors( '', '' );
		} else {
			$file_data = $this->upload->data();

			// Save the file in the files model
			$this->load->model( 'model_files' );
			$this->model_files->file_url = $file_data['full_path'];
			$this->model_files->create();
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => $error
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->response( [
				'id'   => $this->model_files->file_id,
				'path' => $this->upload->serve($this->model_files->file_url)
			], Base_Controller::HTTP_OK );
		}
	}
}
