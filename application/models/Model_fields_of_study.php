<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_fields_of_study extends Base_Model {
	public $fields_of_study_id = null;
	public $fields_of_study_name = null;
	public $fields_of_study_admin_approved = 0;
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
			$this->tbl_fields_of_study . '.fields_of_study_id as field_of_study_id',
			$this->tbl_fields_of_study . '.fields_of_study_name as field_of_study_name'
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
			$this->tbl_fields_of_study . '.fields_of_study_name as name',
			$this->tbl_fields_of_study . '.fields_of_study_id as id'
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
		return $this->tbl_fields_of_study;
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
		return 'fields_of_study_';
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
		$this->db->select( 'fields_of_study_id' );
		$this->db->from( $this->tbl_fields_of_study );
		$this->db->where( 'LOWER(fields_of_study_name)', strtolower( $this->fields_of_study_name ) );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if($query->num_rows() > 0) {
			$this->fields_of_study_id = $query->row()->fields_of_study_id;
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
		$this->db->set( 'fields_of_study_admin_approved', $this->fields_of_study_admin_approved );
		$this->db->where( 'fields_of_study_id', $this->fields_of_study_id );
		$this->db->update( $this->tbl_fields_of_study );

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
		$sql = 'INSERT INTO ' . $this->tbl_fields_of_study . ' (fields_of_study_name) VALUES ';
		$sql .= '(' . $this->db->escape($this->fields_of_study_name) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE fields_of_study_id=LAST_INSERT_ID(fields_of_study_id)';

		$this->db->query( $sql );
		$this->fields_of_study_id = $this->db->insert_id();
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
		$this->db->where( 'fields_of_study_id', $this->fields_of_study_id );
		$this->db->update( $this->tbl_fields_of_study );
	}
}