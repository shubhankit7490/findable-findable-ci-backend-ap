<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_job_title extends Base_Model
{
	public $job_title_id = NULL;
	public $job_title_name = NULL;
	public $job_title_admin_approved = 0;
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
			$this->tbl_job_title . '.job_title_id as job_title_id',
			$this->tbl_job_title . '.job_title_name as job_title_name'
		);
	}

	public function get_search_model() {
		return array(
			'jt.job_title_id as job_title_id',
			'jt.job_title_name as job_title_name'
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
			$this->tbl_job_title . '.job_title_name as name',
			$this->tbl_job_title . '.job_title_id as id'
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
		return $this->tbl_job_title;
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
		return 'job_title_';
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
		$this->db->select( 'job_title_id' );
		$this->db->from( $this->tbl_job_title );
		$this->db->where( 'LOWER(job_title_name)', strtolower( $this->job_title_name ) );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if($query->num_rows() > 0) {
			$this->job_title_id = $query->row()->job_title_id;
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
		$this->db->set( 'job_title_admin_approved', $this->job_title_admin_approved );
		$this->db->where( 'job_title_id', $this->job_title_id );
		$this->db->update( $this->tbl_job_title );

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
		$sql = 'INSERT INTO ' . $this->tbl_job_title . ' (job_title_name) VALUES ';
		$sql .= '(' . $this->db->escape($this->job_title_name) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE job_title_id=LAST_INSERT_ID(job_title_id)';

		$this->db->query( $sql );
		$this->job_title_id = $this->db->insert_id();
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
		$this->db->where( 'job_title_id', $this->job_title_id );
		$this->db->update( $this->tbl_job_title );
	}
}
