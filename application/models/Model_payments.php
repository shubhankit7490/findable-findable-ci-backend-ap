<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_payments extends Base_Model {
	public $payment_id = null;
	public $business_id = null;
	public $payment_stripe_token = null;
	public $payment_customer_id = null;
	public $payment_auto_reload = 0;
	public $payment_reload_package_id = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * get
	 *
	 * Get the payment object of the current business_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function get() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_payments );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'deleted', $this->deleted );
		$this->db->limit( 1 );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	/**
	 * get_payment
	 *
	 * Get the payment object of the current payment_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function get_payment() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_payments );
		$this->db->where( 'payment_id', $this->payment_id );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'deleted', $this->deleted );
		$this->db->limit( 1 );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

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
		unset( $this->payment_id );
		unset( $this->created );

		$this->db->insert( $this->tbl_payments, $this );
		$this->payment_id = $this->db->insert_id();

		return ( $this->db->affected_rows() != 1 ) ? false : true;
	}


	/**
	 * update
	 *
	 * Update business's payment
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function update() {
		$this->db->where( 'business_id', $this->business_id );

		unset( $this->created );
		unset( $this->payment_id );

		$this->db->set( $this );
		$this->db->update( $this->tbl_payments );

		return $this->db->affected_rows() > 0;
	}
}
