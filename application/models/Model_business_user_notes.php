<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_business_user_notes extends Base_Model {
	public $business_user_note_id = null;
	public $business_id = null;
	public $user_id = null;
	public $note = null;
	public $deleted = 0;
	public $updated = null;
	public $created = null;

	/**
	 * get
	 *
	 * Get the notes of the user related to a given business
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get() {
		$this->db->select( 'note,type,business_user_note_id' );
		$this->db->from( $this->tbl_business_user_notes );
		$this->db->where( $this->tbl_business_user_notes . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_business_user_notes . '.deleted', $this->deleted );
		$this->db->limit( 1 );

		$query = $this->db->get();

		return $query->row();
	}

	/**
	 * insert_update_note
	 *
	 * Insert new note or update if exists
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function insert_update_note() {
		$sql = 'INSERT INTO ' . $this->tbl_business_user_notes . ' (user_id, business_id, note,type) VALUES ';
		$sql .= '(' . $this->user_id . ',' . $this->business_id . ', ' . $this->db->escape( $this->note ) . ', ' . $this->db->escape( $this->type ) . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE note=VALUES(note)';
		$this->db->query( $sql );
		return $this->db->affected_rows() > 0;
	}
	/**
	 * create
	 *
	 * Add the model to the database
	 *
	 * @access    public
	 *
	 * @role    recruiter, manager
	 *
	 * @return    null
	 */
	public function create() {
		$this->db->set($this);
		$this->db->insert($this->tbl_business_user_notes,$this->model_business_user_notes);

		if ( $this->db->affected_rows() > 0 ) {
			$this->id = $this->db->insert_id();
			return true;
		} else {
			return false;
		}
	}
		/**
	 * Update 
	 *
	 * update after all upload done
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function update($id,$business_id,$type,$note) {
		$this->db->set('note',$note);
		$this->db->set('type',$type);
		$this->db->set('update_business_id',$business_id);
		$this->db->where( 'business_user_note_id',$id );
		$this->db->update($this->tbl_business_user_notes);
		if ( $this->db->affected_rows() > 0 ) {
			return true;
		} else {
			return false;
		}
	}
}