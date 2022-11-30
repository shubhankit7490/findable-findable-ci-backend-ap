<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_languages_of_users extends Base_Model {
	public $language_of_user_id = null;
	public $user_id = null;
	public $language_id = null;
	public $language_level = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * get
	 *
	 * Get the languages collection of the current user
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get() {
		$languages_fields = $this->model_languages->get_model();

		$select = implode( ',', $languages_fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_languages_of_users );
		$this->db->join( $this->tbl_languages, $this->tbl_languages . '.language_id = ' . $this->tbl_languages_of_users . '.language_id' );
		$this->db->where( $this->tbl_languages_of_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_languages_of_users . '.deleted', $this->deleted );
		$query = $this->db->get();

		return $query->result();
	}

	/**
	 * update_level
	 *
	 * Set the level of the given language_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function update_level() {
		$this->db->set( 'language_level', $this->language_level );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'language_id', $this->language_id );
		$this->db->update( $this->tbl_languages_of_users );
	}

	/**
	 * get_user_language
	 *
	 * Get the level of a user's language of false of does'nt exist
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    mixed
	 */
	public function get_user_language() {
		$this->db->select( $this->tbl_languages_of_users . '.language_level' );
		$this->db->from( $this->tbl_languages_of_users );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'language_id', $this->language_id );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() == 0 ) {
			return false;
		} else {
			return $query->row()->language_level;
		}
	}

	/**
	 * soft_delete
	 *
	 * Soft delete a user's language (deleted = 1)
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
		$this->db->where( 'language_id', $this->language_id );
		$this->db->update( $this->tbl_languages_of_users );
	}

	/**
	 * insert_undelete_language
	 *
	 * Insert new language or un delete if marked as deleted
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function insert_undelete_language() {
		$sql = 'INSERT INTO ' . $this->tbl_languages_of_users . ' (user_id, language_id, language_level) VALUES ';
		$sql .= '(' . $this->user_id . ',' . $this->db->escape_str( $this->language_id) . ', ' . $this->db->escape_str( $this->language_level ) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE language_of_user_id=LAST_INSERT_ID(language_of_user_id), deleted=0, language_level=VALUES(language_level)';
		$this->db->query( $sql );

		$this->language_of_user_id = $this->db->insert_id();
	}
}