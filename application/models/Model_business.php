<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_business extends Base_Model {
	public $business_id = null;
	public $business_name = null;
	public $business_year_established = null;
	public $business_size = null;
	public $city_id = null;
	public $industry_id = null;
	public $business_type_id = null;
	public $business_web_address = null;
	public $business_duns_number = null;
	public $business_logo = null;
	public $status = 'active';
	public $updated = null;
	public $created = null;

	/**
	 * get_table
	 *
	 * Get the table which is handled by the model
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function get_table() {
		return $this->tbl_business;
	}

	/**
	 * get_column_prefix
	 *
	 * Get the columns prefix of the current model
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function get_column_prefix() {
		return 'business_';
	}

	/**
	 * get_dictionary_model
	 *
	 * Get the model as defined in the Swagger specs (dictionary part)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_dictionary_model() {
		return array(
			$this->tbl_business . '.business_name as name',
			$this->tbl_business . '.business_id as id'
		);
	}

	public function get_model() {
		return array(
			$this->tbl_business . '.business_name as name',
			$this->tbl_business . '.business_year_established as year_established',
			$this->tbl_business . '.business_size as size'
		);
	}

	public function get_model_extended() {
		return array(
			$this->tbl_business . '.business_name as name',
			$this->tbl_business . '.business_year_established as year_established',
			$this->tbl_business . '.business_size as size',
			$this->tbl_business . '.business_duns_number as duns',
			$this->tbl_business . '.business_web_address as web_address',
			$this->tbl_business . '.status as status'
		);
	}

	public function get_minimal_model() {
		return array(
			$this->tbl_business . '.business_id as id',
			$this->tbl_business . '.business_name as name',
			$this->tbl_business . '.status as status',
			$this->tbl_business_unique_applicants_expire . '.business_unique_expire as extended',
			$this->tbl_business . '.created as created'
		);
	}

	/**
	 * get_business
	 *
	 * Get the user object of the current business_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function get_business() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_business );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->limit( 1 );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	public function get() {
		$business_fields = $this->get_model();
		$location_fields = $this->model_location->get_model();
		$fields          = $this->merge_fields( $business_fields, $location_fields );

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_business );
		$this->db->join( $this->tbl_cities, $this->tbl_cities . '.city_id = ' . $this->tbl_business . '.city_id' );
		$this->db->join( $this->tbl_states, $this->tbl_states . '.state_id = ' . $this->tbl_cities . '.state_id', 'left' );
		$this->db->join( $this->tbl_countries, $this->tbl_countries . '.country_id = ' . $this->tbl_cities . '.country_id', 'left' );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_countries . '.continent_id', 'left' );
		$this->db->where( $this->tbl_business . '.business_id', $this->business_id );

		$query = $this->db->get();

		return $query->row();
	}

	public function getExtendedModel() {
		$business_fields     = $this->get_model_extended();
		$location_fields     = $this->model_location->get_model();
		$company_type_fields = $this->model_company_types->get_model();
		$industry_fields     = $this->model_industries->get_model();
		$image_fields        = $this->model_files->get_model();
		$fields              = $this->merge_fields( $business_fields, $location_fields, $company_type_fields, $industry_fields, $image_fields );

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_business );
		$this->db->join( $this->tbl_cities, $this->tbl_cities . '.city_id = ' . $this->tbl_business . '.city_id' );
		$this->db->join( $this->tbl_states, $this->tbl_states . '.state_id = ' . $this->tbl_cities . '.state_id', 'left' );
		$this->db->join( $this->tbl_countries, $this->tbl_countries . '.country_id = ' . $this->tbl_cities . '.country_id', 'left' );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_countries . '.continent_id', 'left' );
		$this->db->join( $this->tbl_industries, $this->tbl_industries . '.industry_id = ' . $this->tbl_business . '.industry_id', 'left' );
		$this->db->join( $this->tbl_company_types, $this->tbl_company_types . '.company_type_id = ' . $this->tbl_business . '.business_type_id', 'left' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_business . '.business_logo', 'left' );
		$this->db->where( $this->tbl_business . '.business_id', $this->business_id );

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->row( 0, 'model_business_record' );
		} else {
			return false;
		}
	}

	public function get_all_business_for_admin() {
		$business_fields     = $this->get_model_extended();
		$image_fields        = $this->model_files->get_model();

		$fields              = $this->merge_fields( $business_fields, $image_fields );

		$fields[] = 'business.business_id as business_id';
		$fields[] = 'business.created as business_created';
		$fields[] = 'business_users.user_id as owner_id';
		$fields[] = 'users.email as owner_email';
		$fields[] = 'profiles.profile_firstname as owner_firstname';
		$fields[] = 'profiles.profile_lastname as owner_lastname';
		$fields[] = 'profiles.profile_phone_number as owner_phone_number';
		$fields[] = 'COUNT(DISTINCT business_user_purchase.business_user_purchase_id) as purchase_count';
		$fields[] = 'COUNT(DISTINCT bu.business_user_id) as recruiters_count';

		$select = implode( ',', $fields );

		// $this->db->simple_query( "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))" );

		$this->db->select( $select );
		$this->db->from( $this->tbl_business );
		$this->db->join( $this->tbl_business_users, $this->tbl_business_users . '.business_id = ' . $this->tbl_business . '.business_id AND ' . $this->tbl_business_users . '.business_admin = 1');
		$this->db->join( $this->tbl_users, $this->tbl_users . '.user_id = ' . $this->tbl_business_users . '.user_id');
		$this->db->join( $this->tbl_profiles, $this->tbl_profiles . '.user_id = ' . $this->tbl_users . '.user_id');
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_business . '.business_logo', 'left' );
		$this->db->join( $this->tbl_business_user_purchase, $this->tbl_business_user_purchase . '.business_id = ' . $this->tbl_business . '.business_id', 'LEFT');
		$this->db->join( $this->tbl_business_users . ' bu', 'bu.business_id = ' . $this->tbl_business . '.business_id AND bu.business_admin = 0', 'LEFT');
		$this->db->group_by('business_users.user_id, business.business_id');
		$this->db->where( $this->tbl_users . '.role', 'manager');
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return false;
		}
	}

	/**
	 * update_industry_id
	 *
	 * Update the industry_id field to all the businsses to a new industry_id id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    null
	 */
	public function update_industry_id( $new_id ) {
		$this->db->set( 'industry_id', $new_id );
		$this->db->where( 'industry_id', $this->industry_id );
		$this->db->update( $this->tbl_business );
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
		unset( $this->updated );
		unset( $this->created );
		unset ($this->business_id );

		$this->db->set($this);
		$this->db->insert( $this->tbl_business );

		if ( $this->db->affected_rows() > 0 ) {
			$this->business_id = $this->db->insert_id();

			return true;
		} else {
			return false;
		}
	}

	/**
	 * update
	 *
	 * Update business's profile object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function update() {
		$_business_id = $this->business_id;

		$this->db->where( 'business_id', $this->business_id );

		unset( $this->updated );
		unset( $this->created );
		unset( $this->business_id );

		$this->db->set( $this );
		$this->db->update( $this->tbl_business );

		$this->business_id = $_business_id;

		return $this->db->affected_rows() > 0;
	}

	public function is_business_profile_valid() {
		$this->db->select( 'COUNT(business_id) as count' );
		$this->db->from( $this->tbl_business );
		$this->db->where( 'business_id', $this->business_id );
		$query = $this->db->get();

		return $query->row()->count;
	}

	/**
	 * update_status
	 *
	 * Update business's status parameter
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function update_status() {
		$this->db->set( 'status', $this->status );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->update( $this->tbl_business );
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
		$this->db->select( 'COUNT(business_id) as business_count' );
		$this->db->from( $this->tbl_business );

		$query = $this->db->get();

		return (int) $query->row()->business_count ?: 0;
	}

	/**
	 * get_all_business
	 *
	 * Get the minimal profiles of all the businesses in the platform
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
	public function get_all_business( $offset = 0, $start = null, $end = null ) {
		$response = [
			'total'      => 0,
			'businesses' => []
		];

		$minimal_profile_fields = $this->get_minimal_model();
		$file_fields            = $this->model_files->get_model();

		$fields = $this->merge_fields( $minimal_profile_fields, $file_fields );

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_business );
		$this->db->join( $this->tbl_business_unique_applicants_expire, $this->tbl_business . '.business_id = ' . $this->tbl_business_unique_applicants_expire . '.business_id', 'left' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_business . '.business_logo', 'left' );

		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_business . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_business . '.created <=', $end );
		}
		$this->db->order_by($this->tbl_business . '.created','desc');
		$this->db->limit( 50, $offset );

		$query = $this->db->get();

		// Count to total number of records
		$this->db->reset_query();

		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_business . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_business . '.created <=', $end );
		}
		
		
		$response['total'] = $this->db->count_all_results( $this->tbl_business );

		if ( $query->num_rows() > 0 ) {
			$response['businesses'] = $query->result( 'model_minimal_business_profiles' );
		}
		return $response;
	}
		/**
	 * get_all_business for admin user
	 *
	 * Get the minimal profiles of all the businesses in the platform
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
	public function get_all_business_admin( $offset = 0, $start = null, $end = null ) {
		$response = [
			'total'      => 0,
			'businesses' => []
		];

		$minimal_profile_fields = $this->get_minimal_model();
		$file_fields            = $this->model_files->get_model();

		$fields = $this->merge_fields( $minimal_profile_fields, $file_fields );

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_business );
		
		$this->db->join( $this->tbl_business_users, $this->tbl_business_users . '.business_id = ' . $this->tbl_business . '.business_id AND ' . $this->tbl_business_users . '.business_admin = 1');
		$this->db->join( $this->tbl_users, $this->tbl_users . '.user_id = ' . $this->tbl_business_users . '.user_id');
		$this->db->join( $this->tbl_business_unique_applicants_expire, $this->tbl_business . '.business_id = ' . $this->tbl_business_unique_applicants_expire . '.business_id', 'left' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_business . '.business_logo', 'left' );
		$this->db->where( $this->tbl_users . '.role', 'manager');
		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_business . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_business . '.created <=', $end );
		}

		$this->db->order_by($this->tbl_business . '.created','desc');
		$this->db->limit( 50, $offset );
		$query = $this->db->get();

		// Count to total number of records
		$this->db->reset_query();

		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_business . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_business . '.created <=', $end );
		}
		
		
		$response['total'] = $this->get_business_total_count($offset = 0, $start = null, $end = null);

		if ( $query->num_rows() > 0 ) {
			$response['businesses'] = $query->result( 'model_minimal_business_profiles' );
		}
		return $response;
	}
	// grt total numer of business
	private function get_business_total_count($offset = 0, $start = null, $end = null){
		$minimal_profile_fields = $this->get_minimal_model();
		$file_fields            = $this->model_files->get_model();

		$fields = $this->merge_fields( $minimal_profile_fields, $file_fields );

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_business );
		
		$this->db->join( $this->tbl_business_users, $this->tbl_business_users . '.business_id = ' . $this->tbl_business . '.business_id AND ' . $this->tbl_business_users . '.business_admin = 1');
		$this->db->join( $this->tbl_users, $this->tbl_users . '.user_id = ' . $this->tbl_business_users . '.user_id');
		$this->db->join( $this->tbl_business_unique_applicants_expire, $this->tbl_business . '.business_id = ' . $this->tbl_business_unique_applicants_expire . '.business_id', 'left' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_business . '.business_logo', 'left' );
		$this->db->where( $this->tbl_users . '.role', 'manager');
		if ( ! is_null( $start ) ) {
			$this->db->where( $this->tbl_business . '.created >=', $start );
		}

		if ( ! is_null( $end ) ) {
			$this->db->where( $this->tbl_business . '.created <=', $end );
		}

		$this->db->order_by($this->tbl_business . '.created','desc');
		$query = $this->db->get();
		return $query->num_rows();
	}
	/**
	 * get_all_business
	 *
	 * get business and user detail by business id
	 *
	 * @param integer $business_id
	 *
	 *
	 * @access    public
	 *
	 * @role    manager
	 *
	 * @return    array
	 */
	public function get_business_user_detail_by_business($business_id) {
		$this->db->select('business.business_name as business_name,users.email as email,users.user_id as user_id,profiles.profile_firstname as first_name,profiles.profile_lastname as last_name,profiles.profile_phone_number as phone');
		$this->db->from('business');
		$this->db->join('business_users','business.business_id=business_users.business_id', 'inner');
		$this->db->join('users','users.user_id=business_users.user_id','inner');
		$this->db->join('profiles','profiles.user_id=users.user_id','inner');
		$this->db->where('business.business_id',$business_id);
		$query=$this->db->get();
		$response = $query->row();
		return $response;
	}
		/**
	 * get_all_business
	 *
	 * get business and user detail by user_id id
	 *
	 * @param integer $user_id
	 *
	 *
	 * @access    public
	 *
	 * @role    manager
	 *
	 * @return    array
	 */
	public function get_business_user_detail_by_user($user_id) {
		$this->db->select('business.business_name as business_name,users.email as email,users.user_id as user_id,profiles.profile_firstname as first_name,profiles.profile_lastname as last_name,profiles.profile_phone_number as phone');
		$this->db->from('users');
		$this->db->join('profiles','profiles.user_id=users.user_id','inner');
		$this->db->join('business_users','users.user_id=business_users.user_id', 'inner');
		$this->db->join('business','business.business_id=business_users.business_id','inner');
		$this->db->where('users.user_id',$user_id);
		$query=$this->db->get();
		$response = $query->row();
		return $response;
	}
}
