<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_business_unique_applicants_expire extends Base_Model {
	public $business_unique_applicants_expire_id = null;
	public $business_id = null;
	public $business_unique_expire = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * insert_update
	 *
	 * Insert new business expire or update if exists
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function insert_update() {
		$sql = 'INSERT INTO ' . $this->tbl_business_unique_applicants_expire . ' (business_id, business_unique_expire) VALUES ';
		$sql .= '(' . $this->business_id . ',' . $this->business_unique_expire . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE business_unique_applicants_expire_id=LAST_INSERT_ID(business_unique_applicants_expire_id), deleted=0, business_unique_expire=VALUES(business_unique_expire)';

		$this->db->query( $sql );
		$this->business_unique_applicants_expire_id = $this->db->insert_id();

		return $this->db->affected_rows() > 0;
	}
}