<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_packages extends Base_Model {
	public $package_id = null;
	public $package_name = null;
	public $applicant_screening = null;
	public $additional_suggested = null;
	public $package_credits = null;
	public $package_initial_credits = null;
	public $package_price = null;
	public $cashback_percent = null;
	public $package_disabled = 0;
	public $custom_package = 0;
	public $deleted = 0;
	public $created = null;

	public function get_model() {
		return array(
			$this->tbl_packages . '.package_id as id',
			$this->tbl_packages . '.package_name as name',
			$this->tbl_packages . '.applicant_screening as applicant_screening',
			$this->tbl_packages . '.package_credits as credits',
			$this->tbl_packages . '.package_initial_credits as initial_credits',
			$this->tbl_packages . '.cashback_percent as cashback_percent',
			$this->tbl_packages . '.package_price as price',
			$this->tbl_packages . '.users as users',
			$this->tbl_packages . '.manage_Candidates as manage_Candidates',
			$this->tbl_packages . '.package_disabled as disabled',
		);
	}

	public function get() {
		$fields = $this->get_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_packages );
		$this->db->where( $this->tbl_packages . '.custom_package', $this->custom_package );
		$this->db->where( $this->tbl_packages . '.deleted', $this->deleted );

		$query = $this->db->get();

		return $query->result();
	}

	public function get_package() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_packages );
		$this->db->where( $this->tbl_packages . '.package_id', $this->package_id );
		$this->db->where( $this->tbl_packages . '.deleted', $this->deleted );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	/**
	 * update
	 *
	 * Update a package object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function update() {
		$_package_id = $this->package_id;

		$this->db->where( 'package_id', $this->package_id );

		unset( $this->deleted );
		unset( $this->created );
		unset( $this->custom_package );
		unset( $this->package_disabled );
		unset( $this->additional_suggested );
		unset( $this->package_id );

		$this->db->set( $this );
		$this->db->update( $this->tbl_packages );

		$this->package_id = $_package_id;

		return $this->db->affected_rows() > 0;
	}
}