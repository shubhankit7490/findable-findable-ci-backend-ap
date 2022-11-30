<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_states extends Base_Model {
	public $state_id = null;
	public $country_id = null;
	public $state_name = null;
	public $state_short_name = null;
	public $state_lng = null;
	public $state_lat = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * get_model
	 *
	 * Get a country object with its states
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_model() {
		return array(
			$this->tbl_states . '.state_id as id',
			$this->tbl_states . '.state_name as name',
			$this->tbl_states . '.state_short_name as short_name'
		);
	}

	/**
	 * get
	 *
	 * Get a collection of states in a given country
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get() {
		$fields = $this->get_model();

		$select = implode( ',', $fields );
		$this->db->select( $select );
		$this->db->from( $this->tbl_states );
		$this->db->where( $this->tbl_states . '.country_id', $this->country_id );
		$this->db->where( $this->tbl_states . '.deleted', $this->deleted );

		$query = $this->db->get();

		return $query->result();
	}
}
