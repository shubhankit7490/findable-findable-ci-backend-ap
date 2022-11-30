<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_sys_log extends Base_Model {
	public $sys_log_id = null;
	public $user_id = null;
	public $category = null;
	public $action = null;
	public $params = null;
	public $created = null;

	/**
	 * count_profile_shares
	 *
	 * Count the total number of profile shares made withing the platform
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    integer
	 */
	public function count_profile_shares() {
		$this->db->select( 'COUNT(user_id) as share_count' );
		$this->db->from( $this->tbl_sys_log );
		$this->db->where( 'category', 'profile_share' );

		$query = $this->db->get();

		return (int) $query->row()->share_count ?: 0;
	}

	/**
	 * add_log
	 *
	 * Add a system log record
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    integer
	 */
	public function add_log() {
		unset( $this->sys_log_id );
		unset( $this->created );

		$this->db->insert( $this->tbl_sys_log, $this );
		$this->sys_log_id = $this->db->insert_id();
	}
}
