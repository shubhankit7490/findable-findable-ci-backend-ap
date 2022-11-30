<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

require APPPATH . 'libraries/REST_Controller.php';

// use namespace
use Restserver\Libraries\REST_Controller;

/*
 * ORIGINAL CLASS MODIFIED:
 * Constructor: Database is loaded right after defining $this->rest
 * _remap: $is_valid_request gets the value of $this->rbac->user_has_permission if user_has_permission is equal to false
 * _detect_api_key: Added support for role parameter and active_business_id parameter
 */

class Base_Controller extends REST_Controller {
	protected $has_permission = true;

	public function __construct() {
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, X-API-KEY');
			header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

			exit;
		}

		parent::__construct();

		$this->output->set_header('Access-Control-Allow-Origin: *');
		$this->output->set_header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, X-API-KEY');
		$this->output->set_header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

		# RBAC library - Checking for role's access to resource & CRUD operation permission
		$this->load->library( 'rbac' );
		$this->rbac->rest = $this->rest;
		$this->rbac->has_permission();

		if ( ! $this->rbac->user_has_permission ) {
			$this->has_permission = false;
			$this->response( [
				'status'  => false,
				'message' => 'Method not allowed'
			], self::HTTP_METHOD_NOT_ALLOWED );
		}

		$this->config->load( 'session_vars' );
	}

	/**
	 * validateRequestParameters
	 *
	 * Validate the given data against the given array of validation rule objects
	 *
	 * @access    public
	 *
	 * @params array data
	 *
	 * @params array validations
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function validateRequestParameters( $data, $validations = array() ) {
		$validationResult = (object) array(
			'result' => true,
			'errors' => array()
		);

		// Load the validation class & language
		$this->load->library( 'form_validation' );
		$this->lang->load( 'validation' );

		$this->form_validation->set_data( $data );

		// Load the validation rules
		$this->form_validation->rules( $validations );
		if ( $this->form_validation->run() === false ) {
			$validationResult->result = false;

			foreach ( $this->form_validation->error_array() as $field => $error ) {
				$validationResult->errors[] = (object) array(
					'field' => $field,
					'error' => $error
				);
			}
		}

		return $validationResult;
	}

	/**
	 * has_param
	 *
	 * Check if the given array has the given param and return it or return the optional default value
	 *
	 * @access    protected
	 *
	 * @params array $array
	 *
	 * @params string param
	 *
	 * @param mixed default
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    mixed
	 */
	protected function has_param( $array = array(), $param = false, $default = null ) {
		if ( isset( $array[ $param ] ) ) {
			return $array[ $param ];
		} else if ( $default !== false ) {
			return $default;
		} else {
			return false;
		}
	}

	/**
	 * check_status
	 *
	 * Check if the given user model satisfies the access restriction rule
	 * The rule can be boolean for active or active (or) pending statuses
	 * The rule can be a string for exact match or an array of options to check against
	 *
	 * @access    protected
	 *
	 * @param object $user
	 *
	 * @param boolean|string|array $active_only
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	protected function check_status($user, $active_only = false) {
		if (!isset($user->status)) {
			return false;
		} else {
			if($active_only === true) {
				return $user->status === 'active';
			} else if($active_only === false){
				return $user->status === 'active' || $user->status === 'pending';
			} else if(is_string($active_only)) {
				return $user->status === $active_only;
			} else if(is_array($active_only)){
				return in_array($user->status, $active_only);
			} else {
				return false;
			}
		}
	}

	/**
	 * is_true_null
	 *
	 * Check if the variable is of NULL type or a string 'null'
	 *
	 * @access    protected
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    integer
	 */
	protected function is_true_null( $v ) {
		return is_null($v) || strtolower($v) === 'null';
	}

	/**
	 * get_active_user
	 *
	 * Get the current active_user (on behalf of the admin is logged) or the current user (taken from the API Key)
	 *
	 * @access    protected
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    integer
	 */
	protected function get_active_user() {
		if ( $this->is_admin() && ! is_null( $this->rest->active_user_id ) ) {
			return $this->rest->active_user_id;
		} else {
			return $this->rest->user_id;
		}
	}

	/**
	 * is_applicant
	 *
	 * Check if the current user has an applicant's session key. If the optional var is passed the check of the key will be against the given var.
	 *
	 * @access    protected
	 *
	 * @params string var
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	protected function is_applicant( $var = false ) {
		return ( ! $var ) ? $this->rest->role == $this->config->item( 'session_role_applicant' ) : trim($var) == $this->config->item( 'session_role_applicant' );
	}

	/**
	 * is_recruiter
	 *
	 * Check if the current user has an recruiter's session key. If the optional var is passed the check of the key will be against the given var.
	 *
	 * @access    protected
	 *
	 * @params string var
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	protected function is_recruiter( $var = false ) {
		return ( ! $var ) ? $this->rest->role == $this->config->item( 'session_role_recruiter' ) : trim($var) == $this->config->item( 'session_role_recruiter' );
	}

	/**
	 * is_manager
	 *
	 * Check if the current user has an manager's session key. If the optional var is passed the check of the key will be against the given var.
	 *
	 * @access    protected
	 *
	 * @params string $var
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	protected function is_manager( $var = false ) {
		return ( ! $var ) ? $this->rest->role == $this->config->item( 'session_role_manager' ) : trim($var) == $this->config->item( 'session_role_manager' );
	}

	/**
	 * is_admin
	 *
	 * Check if the current user has an admin's session key. If the optional var is passed the check of the key will be against the given var.
	 *
	 * @access    protected
	 *
	 * @params string $var
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	protected function is_admin( $var = false ) {
		return ( ! $var ) ? $this->rest->role == $this->config->item( 'session_role_admin' ) : trim($var) == $this->config->item( 'session_role_admin' );
	}

	/**
	 * get_recruiter_business
	 *
	 * Get the business on behalf the recruiter is operating
	 *
	 * @access    protected
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    integer
	 */
	protected function get_recruiter_business() {
		return $this->rest->active_business_id;
	}

	/**
	 * get_manager_business
	 *
	 * Get the business of the logged manager
	 *
	 * @access    protected
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    integer
	 */
	protected function get_manager_business() {
		return $this->rest->active_business_id;
	}

	/**
	 * get_admin_business
	 *
	 * Get the active business on behalf which the admin is operating
	 *
	 * @access    protected
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    integer
	 */
	protected function get_admin_business() {
		return $this->rest->active_business_id;
	}

	/* Override the Rest controller */
	/**
	 * Parse the PUT request arguments
	 *
	 * @access protected
	 * @return void
	 */
	protected function _parse_put() {
		if ( $this->request->format ) {
			$this->request->body = $this->input->raw_input_stream;
			if ( $this->request->format === 'json' ) {
				$this->_put_args = is_array( json_decode( $this->input->raw_input_stream ) ) ? json_decode( $this->input->raw_input_stream ) : [ json_decode( $this->input->raw_input_stream ) ];
			}
		} else if ( $this->input->method() === 'put' ) {
			// If no file type is provided, then there are probably just arguments
			$this->_put_args = $this->input->input_stream();
		}

		$this->request->body = $this->input->raw_input_stream;
	}

	/**
	 * Parse the POST request arguments
	 *
	 * @access protected
	 * @return void
	 */
	protected function _parse_post() {
		$this->_post_args = $_POST;

		$this->request->body = $this->input->raw_input_stream;
	}

	/**
	 * update_profile_updated
	 *
	 * Update the given user's last updated timestamp of the profile
	 *
	 * @access    public
	 *
	 * @params integer user_id
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	protected function update_profile_updated( $user_id ) {
		// Load the profiles model
		$this->load->model( 'model_profiles' );
		$this->model_profiles->user_id = $user_id;
		$this->model_profiles->update_last_updated();
	}

	/**
	 * get_transaction_error
	 *
	 * Get the last database transaction error message
	 *
	 * @access    public
	 *
	 * @params mixed error
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    mixed
	 */
	protected function get_transaction_error( $error = false ) {
		return ( $this->db->trans_status() === false && $error === false ) ? $this->db->error()['message'] : $error;
	}

	/**
	 * get_user
	 *
	 * Get the user object from the cache or from the database if not in the cache
	 *
	 * @access    protected
	 *
	 * @param integer $user_id
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	protected function get_user( $user_id ) {
		$this->load->model( 'model_users' );

		$driver = $this->get_cache_driver();
		if($driver){
			if ( $driver->get( $user_id )) {
				$this->model_users->merge( json_decode( $driver->get( $user_id ) ) );
			} else {
				$this->model_users->user_id = $user_id;
				$this->model_users->get();

				$driver->save( $user_id, json_encode( $this->model_users->get_filtered_model() ), $this->config->item( 'cache_timeout' ) );
			}
		}else{
			$this->model_users->user_id = $user_id;
			$this->model_users->get();
		}
	}

	/**
	 * set_user
	 *
	 * Set the user object from the cache or from the database if not in the cache
	 *
	 * @access    protected
	 *
	 * @param integer $user_id
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	protected function set_user( $user_id ) {
		$this->load->model( 'model_users' );

		$driver = $this->get_cache_driver();

		$this->model_users->user_id = $user_id;
		$this->model_users->get();
		if($driver){
			$driver->save( $user_id, json_encode( $this->model_users->get_filtered_model() ), $this->config->item( 'cache_timeout' ) );
		}
		
	}

	/**
	 * get_cache_driver
	 *
	 * Get the caching driver (memcached as first priority and file caching as a second priority)
	 *
	 * @access    private
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	private function get_cache_driver() {
		$this->load->driver( 'cache' );

		if ( $this->cache->is_supported( 'memcached' ) ) {
			return $this->cache->memcached;
		} else if ( $this->cache->is_supported( 'file' ) ) {
			return $this->cache->file;
		} else {
			return false;
		}
	}

	protected function set_package( $business_id, $package_id = 1, $in_transaction = false ) {
		// Load the requested package details
		$this->load->model( 'model_packages' );
		$this->model_packages->package_id = $package_id;
		$this->model_packages->get_package();

		// Start a manual database transaction
		if (!$in_transaction) {
			$this->db->trans_begin();
		}

		// Save the purchased package in database
		$this->load->model( 'model_packages_of_business' );
		$this->model_packages_of_business->business_id      = $business_id;
		$this->model_packages_of_business->package_id       = $package_id;
		$this->model_packages_of_business->cashback_percent = $this->model_packages->cashback_percent;
		$this->model_packages_of_business->create();

		// Create the record if it's the first payment
		$this->load->model( 'model_credits' );
		$this->model_credits->business_id = $business_id;
		$this->model_credits->credit_amount         = $this->model_packages->package_credits + $this->model_packages->package_initial_credits;
		$this->model_credits->credits_from_cashback = 0;
		$this->model_credits->create();

		if (!$in_transaction) {
			if ( $this->db->trans_status() === false ) {
				// Failed to update the credits status / package to business association
				$this->db->trans_rollback();
				return false;
			} else {
				$this->db->trans_commit();
				return true;
			}
		} else {
			return $this->db->trans_status();
		}
	}

	/**
	 * De-constructor
	 *
	 * @author Chris Kacerguis
	 * @access public
	 * @return void
	 */
	public function __destruct()
	{
		// Get the current timestamp
		$this->_end_rtime = microtime(TRUE);

		if(isset($this->config) && isset($this->config->item)) {
			// Log the loading time to the log table
			if ($this->config->item('rest_enable_logging') === TRUE)
			{
				$this->_log_access_time();
			}
		}
	}
}
