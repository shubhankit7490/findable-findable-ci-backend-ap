<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_applicants_of_business extends Base_Model {
	public $applicants_of_business_id = null;
	public $user_id = null;
	public $business_id = null;
	public $expire = null;
	public $extended_expire = null;
	public $verified = 0;
	public $deleted = 0;
	public $created = null;

	/**
	 * create
	 *
	 * Insert new application or un delete if exists
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function create() {
		$sql = 'INSERT INTO ' . $this->tbl_applicants_of_business . ' (user_id, business_id, expire, extended_expire, verified) VALUES ';
		$sql .= '(' . $this->user_id . ',' . $this->business_id . ', "' . $this->expire . '", "' . $this->extended_expire . '", "' . $this->verified . '")';
		$sql .= ' ON DUPLICATE KEY UPDATE applicants_of_business_id=LAST_INSERT_ID(applicants_of_business_id), deleted=0';

		$this->db->query( $sql );

		$this->applicants_of_business_id = $this->db->insert_id();
	}

	/**
	 * verify_applications
	 *
	 * Verify all the pending application once the user become's verified
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function verify_applications() {
		$this->db->set('verified', $this->verified);
		$this->db->where('user_id', $this->user_id);
		$this->db->where('verified', 0);
		$this->db->update($this->tbl_applicants_of_business);
	}

	/**
	 * get_applicants_count
	 *
	 * Count the number of applicants with verified application the business has
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    integer
	 */
	public function get_applicants_count() {
		$this->db->select( 'COUNT(DISTINCT applicants_of_business_id) as applicants_count' );
		$this->db->from( $this->tbl_applicants_of_business );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'verified', $this->verified );
		$this->db->where( 'deleted', $this->deleted );

		$query = $this->db->get();

		return $query->row()->applicants_count;
	}

	/**
	 * get_applied
	 *
	 * Get the applicant id's which applied to the business from the given array of applicant id's
	 *
	 * @access    public
	 *
	 * @return    array
	 */
	public function get_applied($applicants = []) {
		$result = [];

		$this->db->select( $this->tbl_applicants_of_business . '.user_id as id' );
		$this->db->from( $this->tbl_applicants_of_business );
		$this->db->where( $this->tbl_applicants_of_business . '.business_id', $this->business_id );
		$this->db->where_in( $this->tbl_applicants_of_business . '.user_id', $applicants );
		$this->db->where( $this->tbl_applicants_of_business . '.deleted', $this->deleted );

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			foreach ($query->result() as $applicant) {
				$result[] = $applicant->id;
			}
		}

		return $result;
	}
}