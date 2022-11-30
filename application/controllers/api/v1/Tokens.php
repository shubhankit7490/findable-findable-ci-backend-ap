<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Tokens extends Base_Controller {

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

		$type = $this->post( 'type' );

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'token_type'
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
			$this->load->model( 'model_tokens' );
			$this->model_tokens->user_id = $this->get_active_user();
			$this->model_tokens->type    = $type;

			if ( ! $this->model_tokens->get_by_type() ) {
				// generate
				$this->model_tokens->create();
			}

			$this->response( $this->model_tokens->token, Base_Controller::HTTP_OK );
		}
	}
}
