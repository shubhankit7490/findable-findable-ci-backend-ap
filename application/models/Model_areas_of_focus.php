<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_areas_of_focus extends Base_Model {
	public $area_of_focus_id = null;
	public $area_of_focus_name = null;
	public $area_of_focus_admin_approved = 0;
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
			$this->tbl_areas_of_focus . '.area_of_focus_name as area_of_focus_name',
			$this->tbl_areas_of_focus . '.area_of_focus_id as area_of_focus_id'
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
			$this->tbl_areas_of_focus . '.area_of_focus_name as name',
			$this->tbl_areas_of_focus . '.area_of_focus_id as id'
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
		return $this->tbl_areas_of_focus;
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
		return 'area_of_focus_';
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
		$this->db->select( 'area_of_focus_id' );
		$this->db->from( $this->tbl_areas_of_focus );
		$this->db->where( 'LOWER(area_of_focus_name)', strtolower( $this->area_of_focus_name ) );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if($query->num_rows() > 0) {
			$this->area_of_focus_id = $query->row()->area_of_focus_id;
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
		$this->db->set( 'area_of_focus_admin_approved', $this->area_of_focus_admin_approved );
		$this->db->where( 'area_of_focus_id', $this->area_of_focus_id );
		$this->db->update( $this->tbl_areas_of_focus );

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
		$sql = 'INSERT INTO ' . $this->tbl_areas_of_focus . ' (area_of_focus_name) VALUES ';
		$sql .= '(' . $this->db->escape($this->area_of_focus_name) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE area_of_focus_id=LAST_INSERT_ID(area_of_focus_id)';

		$this->db->query( $sql );
		$this->area_of_focus_id = $this->db->insert_id();
	}

	/**
	 * item_add
	 *
	 * Add the current item to the dictionary during resume upload
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function item_add_upload() {
		$sql = 'INSERT INTO ' . $this->tbl_areas_of_focus . ' (area_of_focus_name) VALUES ';
		$sql .= '(' . $this->db->escape($this->area_of_focus_name) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE area_of_focus_id=LAST_INSERT_ID(area_of_focus_id)';

		$this->db->query( $sql );
		return $this->db->insert_id();
	}

	/**
	 * soft_delete
	 *
	 * Soft delete an area of focus dictionary item (deleted = 1)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function soft_delete() {
		$this->db->set( 'deleted', $this->deleted );
		$this->db->where( 'area_of_focus_id', $this->area_of_focus_id );
		$this->db->update( $this->tbl_areas_of_focus );
	}
}