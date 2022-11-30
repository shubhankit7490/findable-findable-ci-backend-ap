<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Applicants extends Base_Controller {

	function __construct() {
		parent::__construct();
	}

	/**
	 * index_post
	 *
	 * Search for applicants
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

		$offset  = $this->query( 'offset' );
		$orderby = $this->query( 'orderby' );
		$order   = $this->query( 'order' );
		$search  = $this->request->body;

		$search = ( is_string( $search ) ) ? json_decode( $search, true ) : $search;

		// Validating input parameters
		$validation_params = array(
			'offset'  => $offset,
			'search'  => json_encode( $search ),
			'orderby' => $orderby,
			'order'   => $order
		);
		$form_validated    = $this->validateRequestParameters( $validation_params, array(
			'offset',
			'search',
			'orderby',
			'order'
		) );

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		if ($this->is_admin()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}	
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

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
			if ( $this->is_recruiter() ) {
				$business_id = $this->get_recruiter_business();
			} else if ( $this->is_manager() ) {
				$business_id = $this->get_manager_business();
			} else if ( $this->is_admin() ) {
				$business_id = $this->get_admin_business();
			}

			// Load the models
			$this->load->model( 'model_searches' );
			$this->load->model( 'model_search' );

			// Determine if order sorting filters exists
			$orderby = $this->model_search->determine_orderby( $orderby, $order );
			$order = $this->model_search->determine_order( $orderby, $order );

			// Register the search
			$this->model_searches->search_json = json_encode( $search );
			$this->model_searches->insert_update_search();
			$search_token = $this->model_searches->get_search_token();

			// Perform the search
			$this->model_search->search      = $search;
			$this->model_search->user_id     = $this->rest->user_id;
			$this->model_search->business_id = $business_id;

			if ( $this->is_singular_applicant( $search ) ) {
				$applicants = $this->model_search->get_single_applicant( $offset ?: 0, $orderby ?: 'id', $order ?: 'ASC', 50 );
			} else {
				$this->load->model( 'Model_invitations_of_business' );
				$this->Model_invitations_of_business->business_id=$business_id;
				$this->Model_invitations_of_business->accepted=1;
				$this->Model_invitations_of_business->deleted=0;
				$this->Model_invitations_of_business->email=$this->model_users->email;
				$user_ids=array();
				if(!$this->Model_invitations_of_business->is_exists()){
					$this->load->model( 'model_business_users' );
					$this->model_business_users->business_id=$business_id;
				    $associates=$this->model_business_users->get_my_associate_user();
					
					foreach($associates as $user){
						array_push($user_ids, $user->user_id);
					}
				}
				else{
					array_push($user_ids, $user_id_creator);
				}
				$user_ids_array=$user_ids;
				$user_ids=implode(',',$user_ids);

				$applicants = $this->model_search->get_filtered_applicants( $offset ?: 0, $orderby ?: 'id', $order ?: 'ASC', 50 ,$user_ids);
			}
			// Order the search results
			$this->load->library( 'ArrayHelper' );
			$parsed_applicants = $this->arrayhelper->arrange_search( $applicants,$this->rest->user_id,$user_ids_array);

			// Add the search token
			$parsed_applicants['token'] = $search_token;

			// Send a success response to the client
			$this->response( $parsed_applicants, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * is_singular_applicant
	 *
	 * Determine if the search query should contain only one applicant in it's response
	 *
	 * @access    public
	 *
	 * @param array $search
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	private function is_singular_applicant( $search = [] ) {
		return isset( $search['account_id'] ) && ! empty( $search['account_id'] ) && ! is_null( $search['account_id'] );
	}
}
