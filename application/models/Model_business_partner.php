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
	public function get_partner_searches($offset, $from = null, $to = null,$seatch=[] ) {
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
		if ( isset( $seatch['city_id'] ) && ! is_null( $seatch['city_id'] ) ) {
			$this->db->where( $this->tbl_business_partner . '.city_id', $seatch['city_id'] );
		}
		if ( isset( $seatch['company'] ) && ! is_null( $seatch['company'] ) ) {
			$this->db->where_in( $this->tbl_business_partner . '.company', $seatch['company'] );
		}
		if ( isset( $seatch['job_title'] ) && ! is_null( $seatch['job_title'] ) ) {
			$this->db->where_in( $this->tbl_business_partner . '.job_title', explode(',',$seatch['job_title'] ));
		}
		if ( ! is_null( $from ) ) {
			$this->db->where( $this->tbl_business_partner . '.created >=', $from );
		}

		if ( ! is_null( $to ) ) {
			$this->db->where( $this->tbl_business_partner . '.created <=', $to );
		}

		$this->db->order_by($this->tbl_business_partner . '.created', 'DESC');
		$this->db->limit( 50, $offset );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->result();
		}
		return [];
	}

	public function get_filter(){
		$filter=[];
		$this->db->select('company');
		$this->db->from( $this->tbl_business_partner );
		$this->db->where( $this->tbl_business_partner . '.created_by', $this->created_by );
		$this->db->group_by($this->tbl_business_partner.'.company');
		$this->db->order_by($this->tbl_business_partner . '.company', 'ASC');
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$filter['company']=$query->result();
		}
		$this->db->select('job_title');
		$this->db->from( $this->tbl_business_partner );
		$this->db->where( $this->tbl_business_partner . '.created_by', $this->created_by );
		$this->db->group_by($this->tbl_business_partner.'.job_title');
		$this->db->order_by($this->tbl_business_partner . '.job_title', 'ASC');
		$query = $this->db->get();
		if ( $query->num_rows() > 0 ) {
			$filter['job_title']=$query->result();
		}
		return $filter;
	}
	public function get_total($from = null, $to = null,$seatch=[]){
		$this->db->select('count(*) as total');
		$this->db->from( $this->tbl_business_partner );
		$this->db->where( $this->tbl_business_partner . '.created_by', $this->created_by );
		if ( isset( $seatch['city_id'] ) && ! is_null( $seatch['city_id'] ) ) {
			$this->db->where( $this->tbl_business_partner . '.city_id', $seatch['city_id'] );
		}
		if ( isset( $seatch['company'] ) && ! is_null( $seatch['company'] ) ) {
			$this->db->where_in( $this->tbl_business_partner . '.company', $seatch['company'] );
		}
		if ( isset( $seatch['job_title'] ) && ! is_null( $seatch['job_title'] ) ) {
			$this->db->where_in( $this->tbl_business_partner . '.job_title', explode(',',$seatch['job_title'] ));
		}
		if ( ! is_null( $from ) ) {
			$this->db->where( $this->tbl_business_partner . '.created >=', $from );
		}

		if ( ! is_null( $to ) ) {
			$this->db->where( $this->tbl_business_partner . '.created <=', $to );
		}
		$query = $this->db->get();
		return $query->row()->total;
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
	public function update($id) {
	

		$this->db->where( 'id', $id );

		unset( $this->created_by );

		$this->db->set( $this );
		$this->db->update( $this->tbl_business_partner );

		return $this->db->affected_rows() > 0;
	}

}
