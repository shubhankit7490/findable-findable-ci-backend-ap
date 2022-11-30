<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_purchase_history extends Base_Model {
	public $purchase_history_id = null;
	public $user_id = null;
	public $business_id = null;
	public $package_id = null;
	public $purchase_price = null;
	public $package_credits = 0;
	public $transaction_number = null;
	public $invoice_id = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * create
	 *
	 * Add the model to the database
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function create() {
		unset( $this->purchase_history_id );
		unset( $this->created );

		$this->db->insert( $this->tbl_purchase_history, $this );
		$this->purchase_history_id = $this->db->insert_id();
	}

	/**
	 * count_credits_purchased
	 *
	 * Count the total number of credits purchased in the platform
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function count_credits_purchased() {
		$select = implode( ',', [
			'SUM('. $this->tbl_purchase_history .'.package_credits) as credit_count'
		] );

		$this->db->select( $select );
		$this->db->from( $this->tbl_purchase_history );

		$query = $this->db->get();

		return (int) $query->row()->credit_count ?: 0;
	}
}
