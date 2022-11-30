<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Log extends Base_Controller {

	function __construct() {
		parent::__construct();
	}

	/**
	 * index_post
	 *
	 * Request a new operation token (activation, email)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function index_post() {
		$valid          = true;
		$form_validated = true;

		$category = $this->post( 'category' );
		$action = $this->post( 'action' );
		$params = $this->post( 'params' );

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'log_category',
			'log_action'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Write the log
			$this->load->model('model_sys_log');
			$this->model_sys_log->user_id = $this->rest->user_id;
			$this->model_sys_log->category = $category;
			$this->model_sys_log->action = $action;
			$this->model_sys_log->params = $params;
			$this->model_sys_log->add_log();

			$this->response( "", Base_Controller::HTTP_OK );
		}
	}
}
