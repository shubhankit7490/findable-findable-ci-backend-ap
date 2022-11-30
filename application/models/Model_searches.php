<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_searches extends Base_Model {
	public $search_id = null;
	public $search_token = null;
	public $search_json = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * get_search_by_token
	 *
	 * Get the saved searches of the given business for a certain id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    object|boolean
	 */
	public function get_search_by_token() {
		$fields = array(
			$this->tbl_searches . '.search_json'
		);

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_searches );
		$this->db->where( $this->tbl_searches . '.search_token', $this->search_token );
		$this->db->where( $this->tbl_searches . '.deleted', $this->deleted );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return json_decode( $query->row()->search_json, TRUE );
		}

		return false;
	}

	/**
	 * insert_update_search
	 *
	 * Insert new note or update if exists
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function insert_update_search() {
		$sql = 'INSERT INTO ' . $this->tbl_searches . ' (search_token, search_json) VALUES ';
		$sql .= '("' . md5($this->search_json) . '","' . $this->db->escape_str( $this->search_json ) . '")';
		$sql .= ' ON DUPLICATE KEY UPDATE search_id=LAST_INSERT_ID(search_id), deleted=0';

		$this->db->query( $sql );
		$this->search_id = $this->db->insert_id();
	}

	/**
	 * get_search_token
	 *
	 * Create MD5 from the JSON string of the SearchProfile model
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string (MD5)
	 */
	public function get_search_token() {
		return md5( $this->search_json );
	}
}
