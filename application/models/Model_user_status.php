<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_user_status extends Base_Model {
	public $user_status_id = null;
	public $user_id = null;
	public $user_status_employment_status = null;
	public $user_status_current = null;
	public $user_status_employment_type = null;
	public $user_status_desired_salary_period = null;
	public $user_status_desired_salary = null;
	public $user_status_benefits = null;
	public $user_status_only_current_location = null;
	public $user_status_relocation = null;
	public $user_status_legal_usa = null;
	public $user_status_available_from = null;
	public $user_status_start_time = null;
	public $user_block_companies = 0;
	public $deleted = 0;

	protected $tbl = 'user_status';

	/**
	 * set_model
	 *
	 * Get the setter schema for the current model
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function set_model() {
		return array(
			'employment_status'     => 'user_status_employment_status',
			'current_status'        => 'user_status_current',
			'employment_type'       => 'user_status_employment_type',
			'desired_salary_period' => 'user_status_desired_salary_period',
			'desired_salary'        => 'user_status_desired_salary',
			'benefits'              => 'user_status_benefits',
			'only_current_location' => 'user_status_only_current_location',
			'relocation'            => 'user_status_relocation',
			'legal_usa'             => 'user_status_legal_usa',
			'available_from'        => 'user_status_available_from',
			'start_time'            => 'user_status_start_time'
		);
	}

	/**
	 * get_model
	 *
	 * Get the model as defined in the Swagger specs
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_model() {
		return array(
			$this->tbl_user_status . '.user_status_id as status_id',
			$this->tbl_user_status . '.user_status_employment_status as employment_status',
			$this->tbl_user_status . '.user_status_current as current_status',
			$this->tbl_user_status . '.user_status_employment_type as employment_type',
			$this->tbl_user_status . '.user_status_desired_salary_period as desired_salary_period',
			$this->tbl_user_status . '.user_status_desired_salary as desired_salary',
			$this->tbl_user_status . '.user_status_benefits as benefits',
			$this->tbl_user_status . '.user_status_available_from as available_from',
			$this->tbl_user_status . '.user_status_start_time as start_time',
			$this->tbl_user_status . '.user_status_legal_usa as legal_usa',
			$this->tbl_user_status . '.user_status_only_current_location as only_current_location',
			$this->tbl_user_status . '.user_status_relocation as relocation'
		);
	}

	/**
	 * get_search_model
	 *
	 * Get the model as defined in the Swagger specs for the applicant search profile
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_search_model() {
		return array(
			'us.user_status_id as status_id',
			'us.user_status_employment_status as employment_status',
			'us.user_status_current as current_status',
			'us.user_status_employment_type as employment_type',
			'us.user_status_desired_salary_period as desired_salary_period',
			'us.user_status_desired_salary as desired_salary',
			'us.user_status_benefits as benefits',
			'us.user_status_available_from as available_from',
			'us.user_status_start_time as start_time',
			'us.user_status_legal_usa as legal_usa',
			'us.user_status_only_current_location as only_current_location',
			'us.user_status_relocation as relocation'
		);
	}

	/**
	 * get
	 *
	 * Get the user status and preferences of the current user_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get() {
		$fields = $this->get_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_user_status );
		$this->db->where( $this->tbl_user_status . '.user_id', $this->user_id );
		$this->db->limit( 1 );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->row();
		}

		return false;
	}

	/**
	 * create
	 *
	 * Create a new status and preferences record
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    bull
	 */
	public function create() {
		$this->db->insert( $this->tbl_user_status, $this );
		$this->user_status_id = $this->db->insert_id();
	}
	public function create_status() {
		$this->db->insert( $this->tbl_user_status, $this );
		return $this->db->insert_id();
	}
	/**
	 * load
	 *
	 * Load and merge the model for the current user_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function load() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_user_status );
		$this->db->where( $this->tbl_user_status . '.user_id', $this->user_id );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		} else {
			return false;
		}
	}

	/**
	 * update_blocked_companies
	 *
	 * Update the blocked companies settings (1 = block all companies)
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @return    boolean
	 */
	public function update_blocked_companies() {
		$this->db->set( 'user_block_companies', $this->user_block_companies );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'deleted', $this->deleted );
		$this->db->update( $this->tbl_user_status );

		return $this->db->affected_rows() > 0;
	}
}
