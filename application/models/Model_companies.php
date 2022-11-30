<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_companies extends Base_Model {
	public $company_id = null;
	public $company_name = null;
	public $company_admin_approved = 0;
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
			$this->tbl_companies . '.company_id as company_id',
			$this->tbl_companies . '.company_name as company_name'
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
			$this->tbl_companies . '.company_name as name',
			$this->tbl_companies . '.company_id as id'
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
		return $this->tbl_companies;
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
		return 'company_';
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
		$this->db->select( 'company_id' );
		$this->db->from( $this->tbl_companies );
		$this->db->where( 'LOWER(company_name)', strtolower( $this->company_name ) );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if($query->num_rows() > 0) {
			$this->company_id = $query->row()->company_id;
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
		$this->db->set( 'company_admin_approved', $this->company_admin_approved );
		$this->db->where( 'company_id', $this->company_id );
		$this->db->update( $this->tbl_companies );

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
		$sql = 'INSERT INTO ' . $this->tbl_companies . ' (company_name) VALUES ';
		$sql .= '(' . $this->db->escape($this->company_name) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE company_id=LAST_INSERT_ID(company_id)';

		$this->db->query( $sql );
		$this->company_id = $this->db->insert_id();
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
		$this->db->where( 'company_id', $this->company_id );
		$this->db->update( $this->tbl_companies );
	}
}