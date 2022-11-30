<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_subscription extends Base_Model {
	public $subscription_id = null;
	public $user_id = null;
	public $payment_stripe_token = null;
	public $payment_customer_id = null;
	public $subscriptions_stripe_id = null;
	public $created = null;

	/**
	 * get
	 *
	 * Get the subscription object of the current user_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function get() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_subscription );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->limit( 1 );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	/**
	 * get_subscription
	 *
	 * Get the subscription object of the current subscription_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function get_subscription() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_subscription );
		$this->db->where( 'subscription_id', $this->subscription_id );
		$this->db->where( 'user_id', $this->user_id );
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
		unset( $this->subscription_id );
		unset( $this->created );

		$this->db->insert( $this->tbl_subscription, $this );
		$this->subscription_id = $this->db->insert_id();

		return ( $this->db->affected_rows() != 1 ) ? false : true;
	}


	/**
	 * update
	 *
	 * Update user's subscription payment
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function update() {
		$this->db->where( 'user_id', $this->user_id );

		unset( $this->created );
		unset( $this->subscription_id );

		$this->db->set( $this );
		$this->db->update( $this->tbl_subscription );

		return $this->db->affected_rows() > 0;
	}
}
