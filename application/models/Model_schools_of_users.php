<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_schools_of_users extends Base_Model {
	public $schools_of_user_id = null;
	public $user_id = null;
	public $school_id = null;
	public $school_from = null;
	public $school_to = null;
	public $school_education_level = null;
	public $school_current = 0;
	public $deleted = 0;
	public $created = null;

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
			$this->tbl_schools_of_users . '.schools_of_user_id as schools_of_user_id',
			$this->tbl_schools_of_users . '.school_id as school_id',
			$this->tbl_schools . '.school_name as school_name',
			$this->tbl_schools_of_users . '.school_from as school_from',
			$this->tbl_schools_of_users . '.school_to as school_to',
			$this->tbl_schools_of_users . '.school_current as school_current',
			$this->tbl_schools_of_users . '.school_education_level as education_level_id',
			$this->tbl_education_levels . '.education_level_name as education_level_name'
		);
	}

	/**
	 * get
	 *
	 * Get the education collection of a user
	 *
	 * @access    public
	 *
	 * @return    array
	 */
	public function get() {
		$result = array();

		$fields = $this->get_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_schools_of_users );
		$this->db->join( $this->tbl_schools, $this->tbl_schools . '.school_id = ' . $this->tbl_schools_of_users . '.school_id' );
		$this->db->join( $this->tbl_education_levels, $this->tbl_education_levels . '.education_level_id = ' . $this->tbl_schools_of_users . '.school_education_level' );
		$this->db->where( $this->tbl_schools_of_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_schools_of_users . '.deleted', $this->deleted );

		//$this->db->order_by($this->tbl_schools_of_users . '.school_to IS NULL DESC, '. $this->tbl_schools_of_users . '.school_to DESC', '', false);
		$this->db->order_by($this->tbl_schools_of_users . '.school_from', 'DESC');

		$query = $this->db->get();

		foreach ( $query->result( 'model_education' ) as $school ) {
			$result[] = $school->get_fields_of_study();
		}

		return $result;
	}

	/**
	 * Create
	 *
	 * Create a user's school record
	 *
	 * @access    public
	 *
	 * @return    null
	 */
	public function create() {
		unset( $this->updated );
		unset( $this->created );

		$this->db->insert( $this->tbl_schools_of_users, $this );
		$this->schools_of_user_id = $this->db->insert_id();
	}

	public function create_school() {
		unset( $this->updated );
		unset( $this->created );

		$this->db->insert( $this->tbl_schools_of_users, $this );
		return $this->db->insert_id();
	}
	/**
	 * update
	 *
	 * Update a user's school record
	 *
	 * @access    public
	 *
	 * @return    null
	 */
	public function update() {
		$this->db->set( 'school_id', $this->school_id );
		$this->db->set( 'school_from', $this->school_from );
		$this->db->set( 'school_to', $this->school_to );
		$this->db->set( 'school_education_level', $this->school_education_level );
		$this->db->set( 'school_current', $this->school_current );

		$this->db->where( 'schools_of_user_id', $this->schools_of_user_id );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'deleted', 0 );

		$this->db->update( $this->tbl_schools_of_users );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * soft_delete
	 *
	 * Soft delete a user's blocked business (deleted = 1)
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
		$this->db->where( 'schools_of_user_id', $this->schools_of_user_id );
		$this->db->update( $this->tbl_schools_of_users );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * update_school_id
	 *
	 * Update the school id to all the users to a new school id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    null
	 */
	public function update_school_id( $new_id ) {
		$this->db->set( 'school_id', $new_id );
		$this->db->where( 'school_id', $this->school_id );
		$this->db->update( $this->tbl_schools_of_users );
	}

}
