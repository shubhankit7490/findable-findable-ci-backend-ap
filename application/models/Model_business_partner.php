<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_business_partner extends Base_Model {

	public $created_by = null;

	public function get_by_email_login($email) {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_business_partner );
		$this->db->where( 'email', $email);
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
		$this->db->insert( $this->tbl_business_partner, $this );
		$this->id = $this->db->insert_id();
	}
	private function get_fields(){
		return array(
			$this->tbl_business_partner . '.*',
		);
	}

	/**
	 * get_business_partner
	 *
	 * Get the saved searches of a business
	 *
	 * @access    public
	 *
	 * @param DateTime|null $from
	 *
	 * @param DateTime|null $to
	 *
	 * @role    manager, admin
	 *
	 * @return    array
	 */
	public function get_partner_searches( $from = null, $to = null ) {
		$fields = $this->get_fields();
		$this->load->model('model_location');
		$location_fields = $this->model_location->get_model();
		$fields          = $this->merge_fields( $fields, $location_fields );
		$select = implode( ',', $fields );
		$this->db->select( $select );
		$this->db->from( $this->tbl_business_partner );
		$this->db->join( $this->tbl_cities, $this->tbl_cities . '.city_id = ' . $this->tbl_business_partner . '.city_id' );
		$this->db->join( $this->tbl_states, $this->tbl_states . '.state_id = ' . $this->tbl_cities . '.state_id', 'left' );
		$this->db->join( $this->tbl_countries, $this->tbl_countries . '.country_id = ' . $this->tbl_cities . '.country_id', 'left' );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_countries . '.continent_id', 'left' );
		$this->db->where( $this->tbl_business_partner . '.created_by', $this->created_by );
		//$this->db->where( $this->tbl_business_partner . '.deleted', $this->deleted );

		if ( ! is_null( $from ) ) {
			$this->db->where( $this->tbl_business_partner . '.created >=', $from );
		}

		if ( ! is_null( $to ) ) {
			$this->db->where( $this->tbl_business_partner . '.created <=', $to );
		}

		$this->db->order_by($this->tbl_business_partner . '.created', 'DESC');

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->result();
		}

		return [];
	}


	/**
	 * soft_delete
	 *
	 * Soft delete a technical ability dictionary item (deleted = 1)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function soft_delete() {
		$this->db->set( 'deleted', $this->deleted );
		$this->db->where( 'search_of_business_id', $this->search_of_business_id );
		$this->db->update( $this->tbl_searches_of_businesses );

		return $this->db->affected_rows() > 0;
	}


}
