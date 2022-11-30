<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_user_requests extends Base_Model {
	public $user_request_id = null;
	public $user_id = null;
	public $fullname = null;
	public $company = null;
	public $email = null;
	public $phone = null;
	public $message = null;
	public $created = null;

	/**
	 * get
	 *
	 * Save the model to the corresponding database table
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get( $offset = null ) {
		$response = [];

		$sql = 'SELECT SQL_CALC_FOUND_ROWS user_id, fullname, company, email, phone, message FROM ' . $this->tbl_user_requests;
		$sql .= ' ORDER BY created DESC LIMIT 50 OFFSET ' . $offset;
		$query                  = $this->db->query( $sql );

		if ( $query->num_rows() > 0 ) {
			$count_result      = $this->db->query( "SELECT FOUND_ROWS() as total" );
			$response['total'] = $count_result->row()->total;
		} else {
			$response['total'] = 0;
		}

		$response['requests'] = $query->result();

		return $response;
	}

	/**
	 * save
	 *
	 * Save the model to the corresponding database table
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function save() {
		unset($this->user_request_id);
		unset($this->created);

		$this->db->insert($this->tbl_user_requests, $this);
		$this->user_request_id = $this->db->insert_id();
	}
}
