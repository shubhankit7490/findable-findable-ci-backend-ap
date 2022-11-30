<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Packages extends Base_Controller {

	function __construct() {
		parent::__construct();
	}

	/**
	 * index_get
	 *
	 * Get the packages of the business
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @return    array
	 */
	public function index_get() {
		$this->load->model( 'model_packages' );
		$packages = $this->model_packages->get();

		$this->response( [
			'status'  => true,
			'message' => $packages
		], Base_Controller::HTTP_OK );
	}

	/**
	 * index_put
	 *
	 * Update a package
	 *
	 * @param integer $package_id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function index_put( $package_id ) {
		$valid = true;
		$form_validated = true;

		$package = $this->request->body;
		$package = ( is_string( $package ) ) ? json_decode( $package, true ) : $package;
		$form_validated = $this->validateRequestParameters( $package, array(
			'package_update_id',
			'package_initial_credits',
			'package_credits',
			'package_name',
			'package_price',
			'package_cash_back_percent',
			'package_users'
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
			$this->load->model('model_packages');
			$this->model_packages->package_id = $package_id;
			$this->model_packages->package_name = $package['name'];
			$this->model_packages->applicant_screening = $package['applicant_screening'];
			$this->model_packages->package_credits = $package['credits'];
			$this->model_packages->package_initial_credits = $package['initial_credits'];
			$this->model_packages->manage_Candidates = $package['manage_Candidates'];
			$this->model_packages->users = $package['users'];
			$this->model_packages->package_price = $package['price'];
			$this->model_packages->cashback_percent = $package['cashback_percent'];

			$updated = $this->model_packages->update();
			if( ! $updated ) {
				$this->response( [
					'status' => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}
}
