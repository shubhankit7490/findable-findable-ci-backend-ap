<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_locations_of_interest_of_user_status extends Base_Model {
	public $locations_of_interest_of_user_status_id = null;
	public $user_status_id = null;
	public $city_id = null;
	public $deleted = 0;
	public $created = null;

	/**
	 * get_locations
	 *
	 * Get the user's locations of interest collection
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function get_locations() {
		$fields = $this->model_location->get_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_locations_of_interest_of_user_status );
		$this->db->join( $this->tbl_cities, $this->tbl_cities . '.city_id = ' . $this->tbl_locations_of_interest_of_user_status . '.city_id', 'left' );
		$this->db->join( $this->tbl_states, $this->tbl_states . '.state_id = ' . $this->tbl_cities . '.state_id AND ' . $this->tbl_cities . '.state_id', 'left' );
		$this->db->join( $this->tbl_countries, $this->tbl_countries . '.country_id = ' . $this->tbl_cities . '.country_id', 'left' );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_countries . '.continent_id', 'left' );
		$this->db->where( $this->tbl_locations_of_interest_of_user_status . '.user_status_id', $this->user_status_id );
		$this->db->where( $this->tbl_locations_of_interest_of_user_status . '.deleted', $this->deleted );
		$query = $this->db->get();

		return $query->result();
	}

	/**
	 * delete_unused_locations
	 *
	 * Soft delete locations of interest based on the locations parameter
	 *
	 * @access    public
	 *
	 * @param array $locations
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function delete_unused_locations( $locations = array() ) {
		$this->db->set( 'deleted', 1 );
		$this->db->where( $this->tbl_locations_of_interest_of_user_status . '.user_status_id', $this->user_status_id );
		$this->db->where( $this->tbl_locations_of_interest_of_user_status . '.deleted', 0 );
		if(count($locations) > 0) {
			$this->db->where_in( $this->tbl_locations_of_interest_of_user_status . '.city_id', $locations );
		}
		$this->db->update( $this->tbl_locations_of_interest_of_user_status );
	}

	/**
	 * insert_undelete_locations
	 *
	 * Insert new locations of interest of un delete them if they are marked as deleted
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function insert_undelete_locations( $locations = array() ) {
		if(count($locations) > 0) {
			$sql = 'INSERT INTO ' . $this->tbl_locations_of_interest_of_user_status . ' (user_status_id, city_id) VALUES ';
			foreach ( $locations as $location ) {
				$sql .= '(' . $this->db->escape_str( $this->user_status_id ) . ', ' . $this->db->escape_str( $location ) . '),';
			}
			$sql = rtrim( $sql, "," );
			$sql .= ' ON DUPLICATE KEY UPDATE deleted=0';

			// exit($sql);

			$this->db->query( $sql );
		}
	}
}