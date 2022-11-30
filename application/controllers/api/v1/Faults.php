<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Faults extends Base_Controller {

	function __construct() {
		parent::__construct();
	}

	/**
	 * index_get
	 *
	 * Get reports about faults being reported by business users
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function index_get() {
		$valid          = true;
		$form_validated = true;

		$offset = $this->get( 'offset' );

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( array('offset' => $offset), array(
			'offset'
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
			$offset = $this->is_true_null( $offset ) ? 0 : $offset;

			$this->load->model('model_fault_reporter');
			$this->load->model('model_fault_report');
			$this->load->model('model_business_reports');
			$reports = $this->model_business_reports->get_reports( $offset );

			$this->response( $reports, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * index_delete
	 *
	 * Mark a fault report as deleted
	 *
	 * @param integer $fault_id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function index_delete( $fault_id ) {
		$valid          = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model('model_business_reports');
			$this->model_business_reports->business_report_id = $fault_id;
			$deleted = $this->model_business_reports->delete();

			if( ! $deleted ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}
}
