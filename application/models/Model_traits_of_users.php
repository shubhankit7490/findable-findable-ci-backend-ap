<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_traits_of_users extends Base_Model {
	public $trait_of_user_id = null;
	public $user_id = null;
	public $trait_id = null;
	public $trait_prominance = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * get
	 *
	 * Get the traits collection of the given user_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get() {
		$trait_fields = $this->model_traits->get_model();

		$select = implode( ',', $trait_fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_traits_of_users );
		$this->db->join( $this->tbl_traits, $this->tbl_traits . '.trait_id = ' . $this->tbl_traits_of_users . '.trait_id' );
		$this->db->where( $this->tbl_traits_of_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_traits_of_users . '.deleted', $this->deleted );
		$this->db->order_by( $this->tbl_traits_of_users . '.trait_prominance', 'ASC' );
		$this->db->limit( 4 );
		$query = $this->db->get();

		return $query->result();
	}

	/**
	 * delete_traits
	 *
	 * Soft delete user's traits
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function delete_traits() {
		$this->db->set( 'deleted', 1 );
		$this->db->where( $this->tbl_traits_of_users . '.user_id', $this->user_id );
		$this->db->update( $this->tbl_traits_of_users );
	}

	/**
	 * insert_undelete_traits
	 *
	 * Insert new traits or un delete them if they are marked as deleted
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function insert_undelete_traits( $traits = array() ) {
		if ( count( $traits ) > 0 ) {
			$sql = 'INSERT INTO ' . $this->tbl_traits_of_users . ' (user_id, trait_id, trait_prominance) VALUES ';
			foreach ( $traits as $trait ) {
				$sql .= '(' . $this->user_id . ', ' . $this->db->escape_str( $trait['id'] ) . ', ' . $this->db->escape_str( $trait['prominance'] ) . '),';
			}
			$sql = rtrim( $sql, "," );
			$sql .= ' ON DUPLICATE KEY UPDATE deleted=0, trait_prominance=VALUES(trait_prominance)';

			$this->db->query( $sql );
		}
	}
}
