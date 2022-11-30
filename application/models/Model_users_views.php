<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_users_views extends Base_Model {
	public $users_views_id = null;
	public $user_id = null;
	public $continent_id = null;
	public $views = 0;
	public $updated = null;

	public function get_model() {
		return array(
			$this->tbl_users_views . '.continent_id as continent_id',
			$this->tbl_users_views . '.views as views',
			$this->tbl_continents . '.continent_name as continent_name'
		);
	}
	/**
	 * add
	 *
	 * Add a public profile view count
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function add() {
		$sql = 'INSERT INTO ' . $this->tbl_users_views . ' (user_id,continent_id) VALUES ';
		$sql .= '(' . $this->user_id . ', '. $this->continent_id .')';
		$sql .= ' ON DUPLICATE KEY UPDATE users_views_id=LAST_INSERT_ID(users_views_id), views=views+1';

		$this->db->query( $sql );
		$this->users_views_id = $this->db->insert_id();
	}

	/**
	 * get
	 *
	 * Get the public profile view count
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get() {
		$fields = $this->get_model();

		$select = implode( ',', $fields );
		$this->db->select( $select );
		$this->db->from( $this->tbl_users_views );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_users_views . '.continent_id');
		$this->db->group_by( $this->tbl_users_views . '.continent_id' );
		$this->db->where( $this->tbl_users_views . '.user_id', $this->user_id );

		$query = $this->db->get();

		if ($query->num_rows()) {
			return $query->result();
		}

		return false;
	}
}