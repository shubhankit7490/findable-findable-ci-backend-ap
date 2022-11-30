<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_seniorities extends Base_Model {
	public $seniority_id = null;
	public $seniority_name = null;
	public $seniority_admin_approved = 0;
	public $deleted = 0;
	public $updated = null;
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
			$this->tbl_seniorities . '.seniority_id as seniority_id',
			$this->tbl_seniorities . '.seniority_name as seniority_name'
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
			'sn.seniority_id as seniority_id',
			'sn.seniority_name as seniority_name'
		);
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
			$this->tbl_seniorities . '.seniority_name as name',
			$this->tbl_seniorities . '.seniority_id as id'
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
	public function get_table() {
		return $this->tbl_seniorities;
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
		return 'seniority_';
	}

	/**
	 * item_exists
	 *
	 * Check if the item exists in the current dictionary
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function item_exists() {
		$this->db->select( 'seniority_id' );
		$this->db->from( $this->tbl_seniorities );
		$this->db->where( 'LOWER(seniority_name)', strtolower( $this->seniority_name ) );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->seniority_id = $query->row()->seniority_id;

			return true;
		} else {
			return false;
		}
	}

	/**
	 * admin_approve
	 *
	 * Set the dictionary item's status to approved
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function admin_approve() {
		$this->db->set( 'seniority_admin_approved', $this->seniority_admin_approved );
		$this->db->where( 'seniority_id', $this->seniority_id );
		$this->db->update( $this->tbl_seniorities );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * item_add
	 *
	 * Add the current item to the dictionary
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function item_add() {
		$sql = 'INSERT INTO ' . $this->tbl_seniorities . ' (seniority_name) VALUES ';
		$sql .= '(' . $this->db->escape( $this->seniority_name ) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE seniority_id=LAST_INSERT_ID(seniority_id)';

		$this->db->query( $sql );
		$this->seniority_id = $this->db->insert_id();
	}

	/**
	 * soft_delete
	 *
	 * Soft delete a technical ability dictionary item (deleted = 1)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function soft_delete() {
		$this->db->set( 'deleted', $this->deleted );
		$this->db->where( 'seniority_id', $this->seniority_id );
		$this->db->update( $this->tbl_seniorities );
	}
}