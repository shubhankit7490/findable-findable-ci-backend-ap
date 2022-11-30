<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_credits extends Base_Model {
	public $credit_id = null;
	public $business_id = null;
	public $credit_amount = null;
	public $credits_from_cashback = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * get
	 *
	 * Get the credits object of the current business_id
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @return    boolean
	 */
	public function get() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_credits );
		$this->db->where( 'business_id', $this->business_id );
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
		unset( $this->credit_id );
		unset( $this->created );

		$this->db->insert( $this->tbl_credits, $this );
		$this->credit_id = $this->db->insert_id();
	}

	/**
	 * update
	 *
	 * Update business's credit balance
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @return    boolean
	 */
	public function update() {
		$this->db->where( 'business_id', $this->business_id );

		unset( $this->created );
		unset( $this->credit_id );

		$this->db->set( $this );
		$this->db->update( $this->tbl_credits );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * add_credits
	 *
	 * Update business's credit balance
	 *
	 * @access    public
	 *
	 * @param integer $amonut
	 *
	 * @role    manager, admin
	 *
	 * @return    boolean
	 */
	public function add_credits( $amount = 0 ) {
		$this->db->where( 'business_id', $this->business_id );
		$this->db->set( 'credit_amount', 'credit_amount+' . $amount, false );
		$this->db->update( $this->tbl_credits );

		return $this->db->affected_rows() > 0;
	}
}