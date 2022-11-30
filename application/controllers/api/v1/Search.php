<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Search extends Base_Controller {

	function __construct() {
		parent::__construct();
	}

	/**
	 * index_get
	 *
	 * Get a search profile by its unique token
	 *
	 * @access    public
	 *
	 * @param string $token
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function index_get( $token ) {
		$valid          = true;
		$form_validated = true;

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( array('token' => $token), array(
			'token'
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
			$this->load->model('model_searches');
			$this->model_searches->search_token = $token;

			if ( ! $search = $this->model_searches->get_search_by_token() ) {
				$this->response( [
					'status' => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->load->model('model_search');
				$this->model_search->search = $search;
				$this->model_search->parse_search_models();

				// Get the parsed search object
				$search = $this->model_search->search;

				$this->response( $search, Base_Controller::HTTP_OK );
			}
		}
	}
}
