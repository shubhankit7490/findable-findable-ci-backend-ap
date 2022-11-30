<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_industries extends Base_Model
{
	public $industry_id = NULL;
	public $industry_name = NULL;
	public $industry_admin_approved = 0;
	public $deleted = 0;
	public $updated = NULL;
	public $created = NULL;

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
			$this->tbl_industries . '.industry_id as industry_id',
			$this->tbl_industries . '.industry_name as industry_name'
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
			$this->tbl_industries . '.industry_name as name',
			$this->tbl_industries . '.industry_id as id'
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
		return $this->tbl_industries;
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
		return 'industry_';
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
		$this->db->select( 'industry_id' );
		$this->db->from( $this->tbl_industries );
		$this->db->where( 'LOWER(industry_name)', strtolower( $this->industry_name ) );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if($query->num_rows() > 0) {
			$this->industry_id = $query->row()->industry_id;
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
		$this->db->set( 'industry_admin_approved', $this->industry_admin_approved );
		$this->db->where( 'industry_id', $this->industry_id );
		$this->db->update( $this->tbl_industries );

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
		$sql = 'INSERT INTO ' . $this->tbl_industries . ' (industry_name) VALUES ';
		$sql .= '(' . $this->db->escape($this->industry_name) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE industry_id=LAST_INSERT_ID(industry_id)';

		$this->db->query( $sql );
		$this->industry_id = $this->db->insert_id();
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
		$this->db->where( 'industry_id', $this->industry_id );
		$this->db->update( $this->tbl_industries );
	}
}