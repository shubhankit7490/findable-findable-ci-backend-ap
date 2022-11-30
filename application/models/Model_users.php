<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_users extends Base_Model {
	public $user_id = null;
	public $email = null;
	public $password = null;
	public $verified = 0;
	public $search_visible = 0;
	public $status = 'pending';
	public $role = 'applicant';

	/**
	 * get_filtered_model
	 *
	 * Get the filtered version of the model which contains only the public parameters of the model
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    object
	 */
	public function get_filtered_model() {
		$array = array();
		$class = new ReflectionClass( __CLASS__ );
		$props = $class->getProperties( ReflectionProperty::IS_PUBLIC );

		foreach ( $props as $prop ) {
			if ( $prop->class == __CLASS__ ) {
				$array[ $prop->name ] = $this->{$prop->name};
			}
		}

		return (object) $array;
	}

	public function get_minimal_model() {
		return array(
			$this->tbl_users . '.user_id as id',
			$this->tbl_profiles . '.profile_firstname as firstname',
			$this->tbl_profiles . '.profile_lastname as lastname',
			$this->tbl_users . '.role as role',
			$this->tbl_users . '.status as status',
			$this->tbl_users . '.created as created',
			$this->tbl_users . '.verified_by_admin',
		);
	}

	/**
	 * get
	 *
	 * Get the user object of the current user_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function get() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_users );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->limit( 1 );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	/**
	 * get_by_email
	 *
	 * Get the user object for the current email address
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function get_by_email() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_users );
		$this->db->where( 'email', $this->email );
		$this->db->limit( 1 );

		$query = $this->db->get();
		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}
	public function get_by_email_login() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_users );
		$this->db->where( 'email', $this->email );
		$this->db->where( 'created_by',0);
		$this->db->limit( 1 );

		$query = $this->db->get();
		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}
	/**
	 * update_last_login
	 *
	 * Update the last login timestamp of the current user
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function update_last_login() {
		$this->db->set( 'last_login', 'now()', false );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->update( $this->tbl_users );
	}

	/**
	 * verify
	 *
	 * Set the status of the user to active and the verified parameter to 1
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function verify() {
		$this->db->set( 'verified', 1 );
		$this->db->set( 'status', 'active' );
		$this->db->set( 'search_visible', $this->search_visible );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->update( $this->tbl_users );

		$this->verified = 1;
		$this->status   = 'active';
	}

	/**
	 * verify
	 *
	 * update user google authentication
	 *
	 * @access    public
	 *
	 * @role    recruiter, manager
	 *
	 * @return    null
	 */
	public function googleauth($isgoogleauth,$token,$refreshtoken) {
		$this->db->set( 'is_google_auth',$isgoogleauth);
		$this->db->set( 'google_auth_token', $token );
		$this->db->set( 'refresh_token',$refreshtoken );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->update( $this->tbl_users );
		return $this->db->affected_rows() > 0;
	}
	/**
	 * create
	 *
	 * Add the model to the database
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function create() {
		$this->db->insert( $this->tbl_users, $this );
		$this->user_id = $this->db->insert_id();
	}
	public function create_user() {
		$this->db->insert( $this->tbl_users, $this );
		return $this->db->insert_id();
	}
	/**
	 * update
	 *
	 * Update the user record
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function update() {
		$_user_id = $this->user_id;

		$this->db->where( 'user_id', $this->user_id );

		unset( $this->updated );
		unset( $this->created );
		unset( $this->user_id );

		$this->db->set( $this );
		$this->db->update( $this->tbl_users );

		$this->user_id = $_user_id;

		return $this->db->affected_rows() > 0;
	}

	/**
	 * count
	 *
	 * Count the total number of users in the platform (exclude admins)
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    integer
	 */
	public function count() {
		$result = (object) [
			'total'     => 0,
			'recruiter' => 0,
			'manager'   => 0,
			'applicant' => 0
		];
		$this->db->select( 'role, COUNT(user_id) as user_count' );
		$this->db->from( $this->tbl_users );
		$this->db->where_in( 'role', [ 'applicant', 'manager', 'recruiter' ] );
		$this->db->group_by( 'role' );

		$query = $this->db->get();

		foreach ( $query->result() as $row ) {
			$result->total        += (int) $row->user_count;
			$result->{$row->role} = (int) $row->user_count;
		}

		return $result;
	}

	/**
	 * get_all_users
	 *
	 * Get the minimal profiles of all the users in the platform
	 *
	 * @param integer $offset
	 *
	 * @param datetime $start
	 *
	 * @param datetime $end
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function get_all_users( $offset = 0, $start = null, $end = null, $limit = 50, $role = null ) {
		$response = [
			'total' => 0,
			'users' => []
		];

		$minimal_profile_fields = $this->get_minimal_model();
		$file_fields            = $this->model_files->get_model();

		$fields = $this->merge_fields( $minimal_profile_fields, $file_fields );
		$select = implode( ',', $fields );
		$this->db->select( $select );
		$this->db->from( $this->tbl_users );
		$this->db->join( $this->tbl_profiles, $this->tbl_profiles . '.user_id = ' . $this->tbl_users . '.user_id' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_profiles . '.profile_image', 'left' );

		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_users . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_users . '.created <=', $end );
		}

		if ( ! is_null( $role ) ) {
			if(is_array($role)){
				$this->db->where_in( $this->tbl_users . '.role', $role );
			}else{
				$this->db->where( $this->tbl_users . '.role', $role );
			}
		}
		$query = $this->db->order_by($this->tbl_users . '.user_id','desc');
		$this->db->limit( $limit, $offset );

		$query = $this->db->get();

		// Count to total number of records
		$this->db->reset_query();

		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_users . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_users . '.created <=', $end );
		}
		if ( ! is_null( $role ) ) {
			if(is_array($role)){
				$this->db->where_in( $this->tbl_users . '.role', $role );
			}else{
				$this->db->where( $this->tbl_users . '.role', $role );
			}
		}
		$response['total'] = $this->db->count_all_results( $this->tbl_users );

		if ( $query->num_rows() > 0 ) {
			$response['users'] = $query->result('model_minimal_users_profiles');
		}

		return $response;
	}
		public function get_all_users_data( $offset = 0, $start = null, $end = null, $limit = 50, $role = null ) {
		$response = [
			'total' => 0,
			'users' => []
		];
		$minimal_profile_fields = $this->get_minimal_model();
		$file_fields            = $this->model_files->get_model();

		$fields = $this->merge_fields( $minimal_profile_fields, $file_fields );
		$select = implode( ',', $fields );
		$this->db->select( $select );
		$this->db->from( $this->tbl_users );
		$this->db->join( $this->tbl_profiles, $this->tbl_profiles . '.user_id = ' . $this->tbl_users . '.user_id' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_profiles . '.profile_image', 'left' );

		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_users . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_users . '.created <=', $end );
		}

		if ($role) {
			$this->db->where( $this->tbl_users . '.role', $role );
		}
		$query = $this->db->order_by($this->tbl_users . '.user_id','desc');
		$this->db->limit( $limit, $offset );

		$query = $this->db->get();

		// Count to total number of records
		$this->db->reset_query();

		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_users . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_users . '.created <=', $end );
		}
		$response['total'] = $this->allusercountbyrole($role);

		if ( $query->num_rows() > 0 ) {
			$response['users'] = $query->result('model_minimal_users_profiles');
		}

		return $response;
	}
	/**
	 * get_all_users
	 *
	 * Get the minimal profiles of all the users in the platform
	 *
	 * @param integer $offset
	 *
	 * @param datetime $start
	 *
	 * @param datetime $end
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public  function allusercountbyrole($role){
		$this->db->select( 'COUNT(user_id) as user_count' );
		$this->db->from( $this->tbl_users );
		if ( ! is_null( $role ) ) {
			$this->db->where( $this->tbl_users . '.role', $role );
		}
		$query = $this->db->get();
		$result= $query->row();
		return $result->user_count;
	}
	/**
	 * get_all_users
	 *
	 * Get the minimal profiles of all the users in the platform
	 *
	 * @param integer $offset
	 *
	 * @param datetime $start
	 *
	 * @param datetime $end
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function get_all_users_for_admin( $offset = 0, $start = null, $end = null, $limit = 50, $role = null ) {
		$response = [
			'total' => 0,
			'users' => []
		];

		$minimal_profile_fields = $this->get_minimal_model();
		$file_fields            = $this->model_files->get_model();

		$fields = $this->merge_fields( $minimal_profile_fields, $file_fields, [
			'users.email as email',
			'users.last_login as last_login',
			'profiles.profile_phone_number as phone_number',
			'CONCAT(cities.city_name, ", " ,countries.country_name) as location',
			'COALESCE(SUM(business_user_views.view_count),0) as view_count',
			'GROUP_CONCAT(DISTINCT `b`.`business_name` SEPARATOR ",") as businesses_applied'
		] );

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_users );
		$this->db->join( $this->tbl_profiles, $this->tbl_profiles . '.user_id = ' . $this->tbl_users . '.user_id' ,'left');
		$this->db->join( $this->tbl_cities, $this->tbl_cities . '.city_id = ' . $this->tbl_profiles . '.city_id', 'left' );
		$this->db->join( $this->tbl_states, $this->tbl_states . '.state_id = ' . $this->tbl_cities . '.state_id', 'left' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_profiles . '.profile_image', 'left' );

		// location
		$this->db->join( $this->tbl_countries, $this->tbl_countries . '.country_id = ' . $this->tbl_cities . '.country_id', 'left' );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_countries . '.continent_id', 'left' );

		// view_count
		$this->db->join( $this->tbl_business_user_views, $this->tbl_business_user_views . '.user_id = ' . $this->tbl_users . '.user_id', 'left');

		// Businesses applied to
		$this->db->join( $this->tbl_applicants_of_business . ' aob', 'aob.user_id = ' . $this->tbl_users . '.user_id', 'left');
		$this->db->join( $this->tbl_business . ' b', 'b.business_id = aob.business_id', 'left');

		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_users . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_users . '.created <=', $end );
		}

		if ( ! is_null( $role ) ) {
			if(is_array($role)){
				$this->db->where_in( $this->tbl_users . '.role', $role );
			}else{
				$this->db->where( $this->tbl_users . '.role', $role );
			}
		}
		$this->db->limit( $limit, $offset );

		$this->db->group_by('users.user_id');

		$query = $this->db->get();

		// Count to total number of records
		$this->db->reset_query();

		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_users . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_users . '.created <=', $end );
		}
		if ( ! is_null( $role ) ) {
			if(is_array($role)){
				$this->db->where_in( $this->tbl_users . '.role', $role );
			}else{
				$this->db->where( $this->tbl_users . '.role', $role );
			}
		}
		$response['total'] = $this->db->count_all_results( $this->tbl_users );

		if ( $query->num_rows() > 0 ) {
			$response['users'] = $query->result( 'model_admin_users_profiles' );
		}

		return $response;
	}
}