<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_business_user_purchase extends Base_Model {
	public $business_user_purchase_id = null;
	public $business_id = null;
	public $user_id = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * get_total
	 *
	 * Get number of applicants purchased by a business
	 *
	 * @access    public
	 *
	 * @return    integer
	 */
	public function get_total() {
		$this->db->select( 'COUNT(DISTINCT business_user_purchase_id) as total_purchased_count' );
		$this->db->from( $this->tbl_business_user_purchase );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'deleted', $this->deleted );

		$query = $this->db->get();

		return $query->row()->total_purchased_count;
	}

	/**
	 * get_purchased
	 *
	 * Get the applicant id's which been purchased by the business from the given array of applicant id's
	 *
	 * @access    public
	 *
	 * @return    array
	 */
	public function get_purchased($applicants = []) {
		$result = [];

		$this->db->select( $this->tbl_business_user_purchase . '.user_id as id' );
		$this->db->from( $this->tbl_business_user_purchase );
		$this->db->where( $this->tbl_business_user_purchase . '.business_id', $this->business_id );
		$this->db->where_in( $this->tbl_business_user_purchase . '.user_id', $applicants );
		$this->db->where( $this->tbl_business_user_purchase . '.deleted', $this->deleted );

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			foreach ($query->result() as $applicant) {
				$result[] = $applicant->id;
			}
		}

		return $result;
	}

	public function remove() {
		$this->db->where('user_id', $this->user_id);
		$this->db->where('business_id', $this->business_id);
		$query = $this->db->delete($this->tbl_business_user_purchase);

		return $this->db->affected_rows() > 0;
	}

	/**
	 * associate
	 *
	 * Associate applicants to the business's purchased applicants
	 *
	 * @access    public
	 *
	 * @return    array
	 */
	public function associate( $applicants = [] ) {
		if ( count( $applicants ) ) {
			$sql = 'INSERT INTO ' . $this->tbl_business_user_purchase . ' (business_id, user_id) VALUES ';
			foreach ( $applicants as $applicant ) {
				$sql .= '(' . $this->business_id . ', ' . $applicant . '),';
			}
			$sql = rtrim( $sql, "," );
			$sql .= ' ON DUPLICATE KEY UPDATE deleted=0';

			$this->db->query( $sql );
		}
	}
}