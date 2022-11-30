<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_technical_abilities_of_users extends Base_Model {
	public $technical_ability_of_user_id = null;
	public $user_id = null;
	public $technical_ability_id = null;
	public $technical_ability_level = null;
	public $deleted = 0;
	public $updated = null;
	public $created = null;

	/**
	 * Get the technical abilities of a user_id
	 *
	 * @return  array
	 */
	public function get() {
		$tech_fields = $this->model_technical_abilities->get_model();

		$select = implode( ',', $tech_fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_technical_abilities_of_users );
		$this->db->join( $this->tbl_technical_abilities, $this->tbl_technical_abilities . '.technical_ability_id = ' . $this->tbl_technical_abilities_of_users . '.technical_ability_id' );
		$this->db->where( $this->tbl_technical_abilities_of_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_technical_abilities_of_users . '.deleted', $this->deleted );

		$this->db->order_by( $this->tbl_technical_abilities_of_users . '.technical_ability_level', 'DESC' );

		$query = $this->db->get();

		return $query->result();
	}

	/**
	 * soft_delete
	 *
	 * Soft delete a user's technical ability (deleted = 1)
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
		$this->db->where( 'technical_ability_id', $this->technical_ability_id );
		$this->db->update( $this->tbl_technical_abilities_of_users );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * insert_undelete_skill
	 *
	 * Insert new technical ability or un delete it if marked as deleted
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function insert_undelete_skill() {
		$sql = 'INSERT INTO ' . $this->tbl_technical_abilities_of_users . ' (user_id, technical_ability_id, technical_ability_level) VALUES ';
		$sql .= '(' . $this->user_id . ', ' . $this->db->escape_str( $this->technical_ability_id ) . ', "' . $this->db->escape_str( $this->technical_ability_level ) . '")';
		$sql .= ' ON DUPLICATE KEY UPDATE technical_ability_of_user_id=LAST_INSERT_ID(technical_ability_of_user_id), deleted=0, technical_ability_level=VALUES(technical_ability_level)';

		$this->db->query( $sql );
		$this->technical_ability_of_user_id = $this->db->insert_id();
	}

	/**
	 * update_level
	 *
	 * Update the level of the current technical_ability_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function update_level() {
		$this->db->set( 'technical_ability_level', $this->technical_ability_level );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'technical_ability_id', $this->technical_ability_id );
		$this->db->update( $this->tbl_technical_abilities_of_users );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * update_technical_ability_id
	 *
	 * Update the technical ability id to all the users to a new technical ability id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    null
	 */
	public function update_technical_ability_id( $new_id ) {
		$this->db->set( 'technical_ability_id', $new_id );
		$this->db->where( 'technical_ability_id', $this->technical_ability_id );
		$this->db->update( $this->tbl_technical_abilities_of_users );
	}
}
