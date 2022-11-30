<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_schools extends Base_Model
{
	public $school_id = NULL;
	public $school_name = NULL;
	public $school_admin_approved = 0;
	public $deleted = 0;
	public $created = NULL;

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
			$this->tbl_schools . '.school_name as name',
			$this->tbl_schools . '.school_id as id'
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
		return $this->tbl_schools;
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
		return 'school_';
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
		$this->db->select( 'school_id' );
		$this->db->from( $this->tbl_schools );
		$this->db->where( 'LOWER(school_name)', strtolower( $this->school_name ) );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if($query->num_rows() > 0) {
			$this->school_id = $query->row()->school_id;
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
		$this->db->set( 'school_admin_approved', $this->school_admin_approved );
		$this->db->where( 'school_id', $this->school_id );
		$this->db->update( $this->tbl_schools );

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
		$sql = 'INSERT INTO ' . $this->tbl_schools . ' (school_name) VALUES ';
		$sql .= '(' . $this->db->escape($this->school_name) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE school_id=LAST_INSERT_ID(school_id)';

		$this->db->query( $sql );
		$this->school_id = $this->db->insert_id();
	}
	public function item_add_school() {
		$sql = 'INSERT INTO ' . $this->tbl_schools . ' (school_name) VALUES ';
		$sql .= '(' . $this->db->escape($this->school_name) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE school_id=LAST_INSERT_ID(school_id)';

		$this->db->query( $sql );
		//$this->school_id = $this->db->insert_id();
		return $this->db->insert_id();
	}
	/**
	 * soft_delete
	 *
	 * Soft delete a school dictionary item (deleted = 1)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function soft_delete() {
		$this->db->set( 'deleted', $this->deleted );
		$this->db->where( 'school_id', $this->school_id );
		$this->db->update( $this->tbl_schools );
	}
}
