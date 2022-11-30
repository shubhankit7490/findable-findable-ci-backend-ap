<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_candidate_upload extends Base_Model {
	public $id = null;
	public $creator_id = null;
	public $total_user = null;
	public $uploaded_user = 0;
	public $uploaded_date = null;

	public function get_model() {
		return array(
			$this->tbl_candidate_upload . '.id',
			$this->tbl_candidate_upload . '.creator_id',
			$this->tbl_candidate_upload . '.total_user',
			$this->tbl_candidate_upload . '.uploaded_user',
			$this->tbl_candidate_upload . '.uploaded_date'
		);
	}
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
	
	public function get() {
		$fields =$this->get_model();

		$select = implode( ',', $fields );
		$select .=',CONCAT(UPPER(SUBSTRING(profiles.profile_firstname,1,1)),UPPER(SUBSTRING(profiles.profile_lastname,1,1))) as name';
		$this->db->select( $select );
		$this->db->from( $this->tbl_candidate_upload);
		$this->db->where_in( $this->tbl_candidate_upload . '.creator_id', $this->creator_id );
		$this->db->join( $this->tbl_profiles, $this->tbl_profiles . '.user_id = ' . $this->tbl_candidate_upload . '.creator_id' );
		$this->db->order_by('id','desc');
		$query = $this->db->get();

		return $query->result();
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
		$this->db->set($this);
		$this->db->insert($this->tbl_candidate_upload );

		if ( $this->db->affected_rows() > 0 ) {
			$this->id = $this->db->insert_id();
			return true;
		} else {
			return false;
		}
	}
		/**
	 * Update 
	 *
	 * update after all upload done
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function update() {
		$this->db->set( 'uploaded_user', $this->uploaded_user);
		$this->db->where( 'id', $this->id );
		$this->db->update( $this->tbl_candidate_upload );
	}

}