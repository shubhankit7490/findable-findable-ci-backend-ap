<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_areas_of_focus_of_positions_of_users extends Base_Model {
	public $areas_of_focus_of_positions_of_users_id = null;
	public $position_of_users_id = null;
	public $area_of_focus_id = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * soft_delete
	 *
	 * Soft delete a user's position (deleted = 1)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function soft_delete() {
		$this->db->set( 'deleted', 1 );
		$this->db->where( 'position_of_users_id', $this->position_of_users_id );
		$this->db->update( $this->tbl_areas_of_focus_of_positions_of_users );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * batch_create
	 *
	 * Insert the given area or mark as un-deleted if already exists
	 *
	 * @access    public
	 *
	 * @params array areas_of_focus_ids
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function batch_create( $areas_of_focus_ids = array() ) {
		$sql = 'INSERT INTO ' . $this->tbl_areas_of_focus_of_positions_of_users . ' (position_of_users_id, area_of_focus_id) VALUES ';
		foreach ( $areas_of_focus_ids as $areas_of_focus_id ) {
			$sql .= '(' . $this->db->escape_str( $this->position_of_users_id ) . ', ' . $this->db->escape_str( $areas_of_focus_id ) . '),';
		}
		$sql = rtrim( $sql, "," );
		$sql .= ' ON DUPLICATE KEY UPDATE deleted=0';

		$this->db->query( $sql );
	}

	/**
	 * update_area_of_focus_id
	 *
	 * Update the fields_of_study id to all the users to a new fields_of_study id
	 *
	 * @access    public
	 *
	 * @params integer new_id
	 *
	 * @role    admin
	 *
	 * @return    null
	 */
	public function update_area_of_focus_id( $new_id ) {
		$this->db->set( 'area_of_focus_id', $new_id );
		$this->db->where( 'area_of_focus_id', $this->area_of_focus_id );

		// Update ignore (avoid duplicate records)
		$sql = str_replace( 'UPDATE', 'UPDATE IGNORE', $this->db->get_compiled_update( $this->tbl_areas_of_focus_of_positions_of_users ) );
		$this->db->query( $sql );

		// Soft delete the old records
		$this->db->set( 'deleted', 1 );
		$this->db->where( 'area_of_focus_id', $this->area_of_focus_id );
		$this->db->update( $this->tbl_areas_of_focus_of_positions_of_users );
	}
}