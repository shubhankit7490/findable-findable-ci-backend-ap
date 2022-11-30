<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_fields_of_study_of_schools_of_users extends Base_Model {
	public $fields_of_study_of_schools_of_user_id = null;
	public $schools_of_user_id = null;
	public $fields_of_study_id = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * soft_delete
	 *
	 * Soft delete a user's fields of study in a certain school (deleted = 1)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function soft_delete() {
		$this->db->set( 'deleted', 1 );
		$this->db->where( 'schools_of_user_id', $this->schools_of_user_id );
		$this->db->update( $this->tbl_fields_of_study_of_schools_of_users );

		return $this->db->affected_rows() > 0;
	}


	/**
	 * insert_fields_of_study
	 *
	 * Create fields of study records in a given school
	 *
	 * @access    public
	 *
	 * @return    null
	 */
	public function insert_fields_of_study( $fields = array() ) {
		if ( count( $fields ) > 0 ) {
			$sql = 'INSERT INTO ' . $this->tbl_fields_of_study_of_schools_of_users . ' (schools_of_user_id, fields_of_study_id) VALUES ';
			foreach ( $fields as $field ) {
				$sql .= '(' . $this->schools_of_user_id . ', ' . $this->db->escape_str( $field["id"] ) . '),';
			}
			$sql = rtrim( $sql, "," );
			$sql .= ' ON DUPLICATE KEY UPDATE deleted=0';

			$this->db->query( $sql );
		}
	}

	/**
	 * update_field_of_study_id
	 *
	 * Update the field_of_study id to all the users to a new field_of_study id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    null
	 */
	public function update_field_of_study_id( $new_id ) {
		$this->db->set( 'fields_of_study_id', $new_id );
		$this->db->where( 'fields_of_study_id', $this->fields_of_study_id );

		// Update ignore (avoid duplicate records)
		$sql = str_replace( 'UPDATE', 'UPDATE IGNORE', $this->db->get_compiled_update( $this->tbl_fields_of_study_of_schools_of_users ) );
		$this->db->query( $sql );

		// Soft delete the old records
		$this->db->set( 'deleted', 1 );
		$this->db->where( 'fields_of_study_id', $this->fields_of_study_id );
		$this->db->update( $this->tbl_fields_of_study_of_schools_of_users );
	}
}