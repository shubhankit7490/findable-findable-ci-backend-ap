<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_technical_abilities extends Base_Model {
	public $technical_ability_id = null;
	public $technical_ability_name = null;
	public $technical_ability_admin_approved = 0;
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
			$this->tbl_technical_abilities_of_users . '.technical_ability_id as id',
			$this->tbl_technical_abilities . '.technical_ability_name as name',
			$this->tbl_technical_abilities_of_users . '.technical_ability_level as level'
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
			$this->tbl_technical_abilities . '.technical_ability_name as name',
			$this->tbl_technical_abilities . '.technical_ability_id as id'
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
		return $this->tbl_technical_abilities;
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
		return 'technical_ability_';
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
		$this->db->select( 'technical_ability_id' );
		$this->db->from( $this->tbl_technical_abilities );
		$this->db->where( 'LOWER(technical_ability_name)', strtolower( $this->technical_ability_name ) );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if($query->num_rows() > 0) {
			$this->technical_ability_id = $query->row()->technical_ability_id;
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
		$this->db->set( 'technical_ability_admin_approved', $this->technical_ability_admin_approved );
		$this->db->where( 'technical_ability_id', $this->technical_ability_id );
		$this->db->update( $this->tbl_technical_abilities );

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
		$sql = 'INSERT INTO ' . $this->tbl_technical_abilities . ' (technical_ability_name) VALUES ';
		$sql .= '(' . $this->db->escape($this->technical_ability_name) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE technical_ability_id=LAST_INSERT_ID(technical_ability_id)';

		$this->db->query( $sql );
		$this->technical_ability_id = $this->db->insert_id();
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
		$this->db->where( 'technical_ability_id', $this->technical_ability_id );
		$this->db->update( $this->tbl_technical_abilities );
	}
}