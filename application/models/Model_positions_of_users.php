<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_positions_of_users extends Base_Model {
	public $positions_of_users_id = null;
	public $user_id = null;
	public $company_id = null;
	public $job_title_id = null;
	public $seniority_id = null;
	public $city_id = null;
	public $industry_id = null;
	public $position_from = null;
	public $position_to = null;
	public $position_current = 0;
	public $position_type = null;
	public $position_salary = null;
	public $position_salary_period = null;
	public $deleted = 0;
	public $updated = null;
	public $created = null;

	public function get_model() {
		return array(
			$this->tbl_positions_of_users . '.positions_of_users_id',
			$this->tbl_positions_of_users . '.position_type as type',
			$this->tbl_positions_of_users . '.position_salary as salary',
			$this->tbl_positions_of_users . '.position_salary_period as salary_period',
			$this->tbl_positions_of_users . '.position_from as from',
			$this->tbl_positions_of_users . '.position_to as to',
			$this->tbl_positions_of_users . '.position_current as current'
		);
	}

	public function get_search_model() {
		return array(
			'pu.positions_of_users_id',
			'pu.position_type as type',
			'pu.position_salary as salary',
			'pu.position_salary_period as salary_period',
			'pu.position_from as position_from',
			'pu.position_to as position_to',
			'pu.position_current as current'
		);
	}

	/**
	 * Get the positions of a user_id
	 *
	 * @return  array
	 */
	public function get() {
		$result = array();

		$location  = $this->model_location->get_model();
		$position  = $this->get_model();
		$company   = $this->model_companies->get_model();
		$industry  = $this->model_industries->get_model();
		$job_title = $this->model_job_title->get_model();
		$seniority = $this->model_seniorities->get_model();

		$fields = $this->merge_fields( $location, $position, $company, $industry, $job_title, $seniority );

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_positions_of_users );
		$this->db->join( $this->tbl_cities, $this->tbl_cities . '.city_id = ' . $this->tbl_positions_of_users . '.city_id' );
		$this->db->join( $this->tbl_states, $this->tbl_states . '.state_id = ' . $this->tbl_cities . '.state_id', 'left' );
		$this->db->join( $this->tbl_countries, $this->tbl_countries . '.country_id = ' . $this->tbl_cities . '.country_id', 'left' );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_countries . '.continent_id', 'left' );
		$this->db->join( $this->tbl_companies, $this->tbl_companies . '.company_id = ' . $this->tbl_positions_of_users . '.company_id' );
		$this->db->join( $this->tbl_industries, $this->tbl_industries . '.industry_id = ' . $this->tbl_positions_of_users . '.industry_id', 'left' );
		$this->db->join( $this->tbl_job_title, $this->tbl_job_title . '.job_title_id = ' . $this->tbl_positions_of_users . '.job_title_id' );
		$this->db->join( $this->tbl_seniorities, $this->tbl_seniorities . '.seniority_id = ' . $this->tbl_positions_of_users . '.seniority_id', 'left' );
		$this->db->where( $this->tbl_positions_of_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_positions_of_users . '.deleted', $this->deleted );

		$this->db->order_by($this->tbl_positions_of_users . '.position_from', 'DESC');

		$query = $this->db->get();

		foreach ( $query->result( 'model_position' ) as $position ) {
			$result[] = $position->get_area_of_focus_of_position();
		}

		return $result;
	}

	/**
	 * Check if a user_id worked in the same position in the same company in the the given range of year
	 *
	 * @return  integer
	 */
	public function is_position_exists() {
		$this->db->select( 'COUNT(DISTINCT positions_of_users_id) as view_count' );
		$this->db->from( $this->tbl_positions_of_users );

		if ( ! is_null( $this->position_to ) ) {
			$this->db->where( array(
				'user_id'          => $this->user_id,
				'company_id'       => $this->company_id,
				'job_title_id'     => $this->job_title_id,
				'deleted'          => $this->deleted,
				'position_from <=' => $this->position_from,
				'position_to >='   => $this->position_to
			) );

			$this->db->or_where("(user_id = ". $this->user_id ." AND company_id = ". $this->company_id ." AND job_title_id = ". $this->job_title_id ." AND deleted = ". $this->deleted ." AND position_from >= '". $this->position_from ."' AND position_to <= '". $this->position_to ."')");
		} else {
			$this->db->where( array(
				'user_id'          => $this->user_id,
				'company_id'       => $this->company_id,
				'job_title_id'     => $this->job_title_id,
				'deleted'          => $this->deleted,
				'position_from <=' => $this->position_from,
				'position_current' => $this->position_current
			) );
		}

		$this->db->group_by( 'positions_of_users_id' );
		$this->db->limit( 1 );

		$query = $this->db->get();

		return ( $query->num_rows() > 0 ) ? $query->row()->view_count : 0;
	}

	public function create() {
		unset( $this->updated );
		unset( $this->created );

		$this->db->set( $this );
		// exit($this->db->get_compiled_insert($this->tbl_positions_of_users));
		$this->db->insert( $this->tbl_positions_of_users );
		$this->positions_of_users_id = $this->db->insert_id();
	}

	/**
	 * soft_delete
	 *
	 * Soft delete a user's position (deleted = 1)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function soft_delete() {
		$this->db->set( 'deleted', 1 );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'positions_of_users_id', $this->positions_of_users_id );
		$this->db->update( $this->tbl_positions_of_users );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * update
	 *
	 * Update user's position object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function update() {
		$this->db->where( 'positions_of_users_id', $this->positions_of_users_id );

		unset( $this->updated );
		unset( $this->created );
		unset( $this->positions_of_users_id );

		$this->db->set( $this );
		$this->db->update( $this->tbl_positions_of_users );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * update_seniority_id
	 *
	 * Update the technical ability id to all the users to a new technical ability id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    null
	 */
	public function update_seniority_id( $new_id ) {
		$this->db->set( 'seniority_id', $new_id );
		$this->db->where( 'seniority_id', $this->seniority_id );
		$this->db->update( $this->tbl_positions_of_users );
	}

	/**
	 * update_job_title_id
	 *
	 * Update the job title id to all the users to a new job title id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    null
	 */
	public function update_job_title_id( $new_id ) {
		$this->db->set( 'job_title_id', $new_id );
		$this->db->where( 'job_title_id', $this->job_title_id );
		$this->db->update( $this->tbl_positions_of_users );
	}

	/**
	 * update_industry_id
	 *
	 * Update the industry_id field to all the users to a new industry_id id
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
		$this->db->update( $this->tbl_positions_of_users );
	}

	/**
	 * update_company_id
	 *
	 * Update the company_id field to all the users to a new company_id id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    null
	 */
	public function update_company_id( $new_id ) {
		$this->db->set( 'company_id', $new_id );
		$this->db->where( 'company_id', $this->company_id );
		$this->db->update( $this->tbl_positions_of_users );
	}
}