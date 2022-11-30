<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_countries extends Base_Model {
	public $country_id = null;
	public $country_name = null;
	public $country_short_name_alpha_2 = null;
	public $country_short_name_alpha_3 = null;
	public $country_lng = null;
	public $country_lat = null;
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
			$this->tbl_countries . '.country_id as id',
			$this->tbl_countries . '.country_name as name',
			$this->tbl_countries . '.country_short_name_alpha_2 as short_name_alpha_2',
			$this->tbl_countries . '.country_short_name_alpha_3 as short_name_alpha_3'
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
		$this->db->from( $this->tbl_countries );
		$this->db->where( $this->tbl_countries . '.deleted', $this->deleted );
		$this->db->order_by( $this->tbl_countries . '.country_name', 'ASC' );

		$query = $this->db->get();

		return $query->result();
	}

	/**
	 * get_continent_from_alpha_2
	 *
	 * Get the continent of a country from it's alpha 2 representation
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_continent_from_alpha_2() {
		$this->db->select( $this->tbl_countries . '.continent_id as continent_id' );
		$this->db->from( $this->tbl_countries );
		$this->db->where( $this->tbl_countries . '.deleted', $this->deleted );
		$this->db->where( $this->tbl_countries . '.country_short_name_alpha_2', $this->country_short_name_alpha_2 );
		$this->db->limit(1);

		$query = $this->db->get();

		if ($query->num_rows()) {
			return $query->row();
		}

		return false;
	}
}