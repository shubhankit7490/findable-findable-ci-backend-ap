<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_blocked_businesses extends Base_Model {
	public $blocked_business_id = null;
	public $business_id = null;
	public $user_id = null;
	public $deleted = 0;
	public $created = null;

	public function get() {
		$fields = array(
			$this->tbl_blocked_businesses . '.business_id as id',
			$this->tbl_business . '.business_name as name'
		);

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_blocked_businesses );
		$this->db->join( $this->tbl_business, $this->tbl_business . '.business_id = ' . $this->tbl_blocked_businesses . '.business_id' );
		$this->db->where( $this->tbl_blocked_businesses . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_blocked_businesses . '.deleted', $this->deleted );
		$this->db->where( $this->tbl_business . '.status', 'active' );

		$query = $this->db->get();

		return $query->result();
	}

	/**
	 * insert_undelete_blocked
	 *
	 * Insert new blocked business or un delete it if marked as deleted
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function insert_undelete_blocked() {
		$sql = 'INSERT INTO ' . $this->tbl_blocked_businesses . ' (business_id, user_id) VALUES ';
		$sql .= '(' . $this->business_id . ', ' . $this->user_id . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE blocked_business_id=LAST_INSERT_ID(blocked_business_id), deleted=0';

		$this->db->query( $sql );
		$this->blocked_business_id = $this->db->insert_id();
	}

	/**
	 * soft_delete
	 *
	 * Soft delete a user's blocked business (deleted = 1)
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
		$this->db->where( 'business_id', $this->business_id );
		$this->db->update( $this->tbl_blocked_businesses );

		return $this->db->affected_rows() > 0;
	}
}