<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_searches_of_businesses extends Base_Model {
	public $search_of_business_id = null;
	public $business_id = null;
	public $user_id = null;
	public $search_id = null;
	public $search_name = null;
	public $status = 'in progress';
	public $deleted = 0;
	public $created = null;

	/**
	 * get
	 *
	 * Get the saved searches of a user in the given business
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array|boolean
	 */
	public function get() {
		$fields = $this->model_saved_search->get_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_searches_of_businesses );

		$this->db->join( $this->tbl_profiles, $this->tbl_profiles . '.user_id = ' . $this->user_id );

		$this->db->where( $this->tbl_searches_of_businesses . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_searches_of_businesses . '.business_id', $this->business_id );
		$this->db->where( $this->tbl_searches_of_businesses . '.deleted', $this->deleted );
		$this->db->order_by( $this->tbl_searches_of_businesses . '.created', 'DESC' );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->result( 'model_saved_search' );
		}

		return [];
	}

	/**
	 * get_search
	 *
	 * Get the saved searches of a user in the given business
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    object|boolean
	 */
	public function get_search() {
		$fields = array(
			$this->tbl_searches . '.search_json'
		);

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_searches_of_businesses );
		$this->db->join( $this->tbl_searches, $this->tbl_searches . '.search_id = ' . $this->tbl_searches_of_businesses . '.search_id' );
		$this->db->where( $this->tbl_searches_of_businesses . '.search_of_business_id', $this->search_of_business_id );
		$this->db->where( $this->tbl_searches_of_businesses . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_searches_of_businesses . '.business_id', $this->business_id );
		$this->db->where( $this->tbl_searches_of_businesses . '.deleted', $this->deleted );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return json_decode( $query->row()->search_json, TRUE );
		}

		return false;
	}

	/**
	 * get_search_by_id
	 *
	 * Get the saved searches of the given business for a certain id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    object|boolean
	 */
	public function get_search_by_id() {
		$fields = array(
			$this->tbl_searches . '.search_json'
		);

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_searches_of_businesses );
		$this->db->join( $this->tbl_searches, $this->tbl_searches . '.search_id = ' . $this->tbl_searches_of_businesses . '.search_id' );
		$this->db->where( $this->tbl_searches_of_businesses . '.search_of_business_id', $this->search_of_business_id );
		$this->db->where( $this->tbl_searches_of_businesses . '.business_id', $this->business_id );
		$this->db->where( $this->tbl_searches_of_businesses . '.deleted', $this->deleted );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return json_decode( $query->row()->search_json, TRUE );
		}

		return false;
	}

	/**
	 * get_business_searches
	 *
	 * Get the saved searches of a business
	 *
	 * @access    public
	 *
	 * @param DateTime|null $from
	 *
	 * @param DateTime|null $to
	 *
	 * @role    manager, admin
	 *
	 * @return    array
	 */
	public function get_business_searches( $from = null, $to = null ) {
		$fields = $this->model_saved_business_search->get_business_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_searches_of_businesses );
		$this->db->join( $this->tbl_profiles, $this->tbl_profiles . '.user_id = ' . $this->tbl_searches_of_businesses . '.user_id' );
		$this->db->where( $this->tbl_searches_of_businesses . '.business_id', $this->business_id );
		$this->db->where( $this->tbl_searches_of_businesses . '.deleted', $this->deleted );

		if ( ! is_null( $from ) ) {
			$this->db->where( $this->tbl_searches_of_businesses . '.created >=', $from );
		}

		if ( ! is_null( $to ) ) {
			$this->db->where( $this->tbl_searches_of_businesses . '.created <=', $to );
		}

		$this->db->order_by($this->tbl_searches_of_businesses . '.created', 'DESC');

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->result( 'model_saved_business_search' );
		}

		return [];
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
	 * @return    boolean
	 */
	public function soft_delete() {
		$this->db->set( 'deleted', $this->deleted );
		$this->db->where( 'search_of_business_id', $this->search_of_business_id );
		$this->db->update( $this->tbl_searches_of_businesses );

		return $this->db->affected_rows() > 0;
	}


	/**
	 * insert_update_search_of_business
	 *
	 * Insert new note or update if exists
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function insert_update_search_of_businesses() {
		$sql = 'INSERT INTO ' . $this->tbl_searches_of_businesses . ' (business_id, user_id, search_id, search_name) VALUES ';
		$sql .= '(' . $this->business_id . ',' . $this->user_id . ',' . $this->search_id . ',"' . $this->db->escape_str( $this->search_name ) . '")';
		$sql .= ' ON DUPLICATE KEY UPDATE search_of_business_id=LAST_INSERT_ID(search_of_business_id), deleted=0';

		$this->db->query( $sql );
		$this->search_of_business_id = $this->db->insert_id();
	}

	/**
	 * insert_update_search_of_business
	 *
	 * Insert new note or update if exists
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function set_status() {
		$this->db->set('status', $this->status);
		$this->db->where('search_of_business_id', $this->search_of_business_id);
		$this->db->where( 'business_id', $this->business_id );

		$this->db->update( $this->tbl_searches_of_businesses );

		return $this->db->affected_rows() > 0;
	}
}
