<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_business_reports extends Base_Model {
	public $business_report_id = null;
	public $business_id = null;
	public $business_user_id = null;
	public $user_id = null;
	public $reason = '';
	public $deleted = 0;
	public $updated = null;
	public $created = null;

	public function get_model() {
		return array(
			'br.user_id as user_id',
			'br2.count',
			'reported_profile.profile_firstname as reported_firstname',
			'reported_profile.profile_lastname as reported_lastname',
			'reported_files.file_id as reported_image_id',
			'reported_files.file_url as reported_image_url'
		);
	}

	/**
	 * get_reports
	 *
	 * Get the fault reports about the platform's applicants
	 *
	 * @param integer $offset
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function get_reports( $offset = null ) {
		$result = [];
		$fields = $this->get_model();

		$select = implode( ',', $fields );
		$sql = 'SELECT ' . $select . ' from ' . $this->tbl_business_reports . ' br
				INNER JOIN ( SELECT user_id, COUNT(*) as count from ' . $this->tbl_business_reports . ' WHERE deleted = 0 GROUP BY user_id ) br2
				INNER JOIN ' . $this->tbl_profiles . ' reported_profile ON reported_profile.user_id = br.user_id
				LEFT JOIN ' . $this->tbl_files . ' reported_files ON reported_files.file_id = reported_profile.profile_image
				WHERE br.user_id = br2.user_id AND br.deleted = 0 GROUP BY user_id LIMIT 50 OFFSET ' . $offset;

		$query = $this->db->query( $sql );

		if ( $query->num_rows() > 0 ) {
			foreach ( $query->result( 'model_fault_report' ) as $report ) {
				$report->get_report_data();
				$result[] = $report;
			}
		}

		return $result;
	}

	/**
	 * num_reports_today
	 *
	 * Count the number of times a business reported a user profile's fault
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function num_reports_today() {
		$this->db->select( 'COUNT(DISTINCT business_report_id) as report_count' );
		$this->db->from( $this->tbl_business_reports );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'deleted', $this->deleted );
		$this->db->where( 'created > DATE_SUB(now(), INTERVAL 1 DAY)', null, true );

		$query = $this->db->get();

		return $query->row()->report_count;
	}

	/**
	 * create
	 *
	 * Insert new fault report
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function create() {
		unset( $this->business_report_id );
		unset( $this->updated );
		unset( $this->created );

		$this->db->insert( $this->tbl_business_reports, $this );
		$this->business_report_id = $this->db->insert_id();
	}

	/**
	 * delete
	 *
	 * Soft delete user's fault report
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Boolean
	 */
	public function delete() {
		$this->db->set( 'deleted', 1 );
		$this->db->where( $this->tbl_business_reports . '.business_report_id', $this->business_report_id );
		$this->db->update( $this->tbl_business_reports );

		return $this->db->affected_rows() > 0;
	}
}