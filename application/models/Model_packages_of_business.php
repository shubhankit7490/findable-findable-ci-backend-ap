<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_packages_of_business extends Base_Model {
	public $packages_of_business_id = null;
	public $package_id = null;
	public $business_id = null;
	public $invoice_id = null;
	public $receipt_number = null;
	public $cashback_percent = null;
	public $deleted = 0;
	public $updated = null;
	public $created = null;

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
			$this->tbl_packages_of_business . '.packages_of_business_id as id',
			$this->tbl_packages . '.package_id as package_id',
			$this->tbl_packages_of_business . '.invoice_id as invoice_id',
			$this->tbl_packages_of_business . '.created as created'
		);
	}

	public function get_by_invoice_id() {
		$this->db->select('*');
		$this->db->from($this->tbl_packages_of_business);
		$this->db->where($this->tbl_packages_of_business . '.invoice_id', $this->invoice_id);
		$this->db->limit(1);

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
		unset( $this->packages_of_business_id );
		unset( $this->created );
		unset( $this->updated );

		$this->db->insert( $this->tbl_packages_of_business, $this );
		$this->packages_of_business_id = $this->db->insert_id();
	}

	/**
	 * update
	 *
	 * Update business's package
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function update() {
		$this->db->where( 'packages_of_business_id', $this->packages_of_business_id );

		unset( $this->created );
		unset( $this->updated );
		unset( $this->packages_of_business_id );

		$this->db->set( $this );
		$this->db->update( $this->tbl_packages_of_business );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * get_since
	 *
	 * Get the payments made by the client for a given period
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @param integer $months
	 *
	 * @return    null
	 */
	public function get_since( $months = false ) {
		$package_fields = $this->model_packages->get_model();
		$payment_fields = $this->get_model();

		$fields = $this->merge_fields( $package_fields, $payment_fields );

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_packages_of_business );
		$this->db->join( $this->tbl_packages, $this->tbl_packages . '.package_id = ' . $this->tbl_packages_of_business . '.package_id', 'left' );
		$this->db->where( 'business_id', $this->business_id );

		if ( $months !== false ) {
			$this->db->where( $this->tbl_packages_of_business . '.created > DATE_SUB(now(), INTERVAL ' . $months . ' MONTH)', null, false );
		}

		$this->db->order_by( $this->tbl_packages_of_business . '.created', 'DESC' );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->result( 'model_purchase' );
		}

		return array();
	}
}
