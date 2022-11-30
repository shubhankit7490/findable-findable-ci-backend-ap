<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_business_applicant_status extends Base_Model {
	public $business_applicant_status_id = null;
	public $business_id = null;
	public $user_id = null;
	public $status = null;
	public $deleted = 0;
	public $updated = null;
	public $created = null;

	protected $statuses = array(
		'short'        => 0,
		'interviewing' => 0,
		'initial'      => 0,
		'hired'        => 0
	);

	/**
	 * get_model
	 *
	 * Get the model as defined in the Swagger specs
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_model() {
		return array(
			$this->tbl_business_applicant_status . '.business_applicant_status_id as status_id',
			$this->tbl_business_applicant_status . '.status as status',
		);
	}

	/**
	 * get_search_model
	 *
	 * Get the model as defined in the Swagger specs for the applicant search profile
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_search_model() {
		return array(
			'bas.business_applicant_status_id as status_id',
			'bas.status as status',
		);
	}

	/**
	 * get_statuses_count
	 *
	 * Get the statuses count of the user
	 *
	 * @access    public
	 *
	 * @param $since
	 *
	 * @return    integer
	 */
	public function get_statuses_count( $since = null ) {
		$this->db->select( 'COUNT(*) as count, status' );
		$this->db->from( $this->tbl_business_applicant_status );
		$this->db->group_by( 'status' );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'deleted', $this->deleted );

		if ( is_int( $since ) ) {
			$this->db->where( 'created >', 'DATE_SUB(now(), INTERVAL ' . $since . ' DAY)' );
		}

		$query = $this->db->get();
		foreach ( $query->result() as $row ) {
			$this->statuses[ $row->status ] = $row->count;
		}

		return $this->statuses;
	}

	/**
	 * get_business_statuses
	 *
	 * Get the statuses count of the business
	 *
	 * @access    public
	 *
	 * @param $since
	 *
	 * @return    integer
	 */
	public function get_business_statuses( $since = null ) {
		$this->db->select( 'COUNT(*) as count, status' );
		$this->db->from( $this->tbl_business_applicant_status );
		$this->db->group_by( 'status' );
		$this->db->where( 'business_id', $this->business_id );

		if ( is_int( $since ) ) {
			$this->db->where( 'created >', 'DATE_SUB(now(), INTERVAL ' . $since . ' DAY)' );
		}

		$query = $this->db->get();
		foreach ( $query->result() as $row ) {
			$this->statuses[ $row->status ] = $row->count;
		}

		return $this->statuses;
	}

	/**
	 * insert_undelete_status
	 *
	 * Insert new status or un delete it if marked as deleted
	 *
	 * @access    public
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function insert_undelete_status() {
		$sql = 'INSERT INTO ' . $this->tbl_business_applicant_status . ' (business_id, user_id, status) VALUES ';
		$sql .= '(' . $this->business_id . ', ' . $this->user_id . ', "'. $this->status .'")';
		$sql .= ' ON DUPLICATE KEY UPDATE status=VALUES(status)';

		$this->db->query( $sql );

		return $this->db->affected_rows() > 0;
	}
}