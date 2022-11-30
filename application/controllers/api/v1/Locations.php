<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Locations extends Base_Controller {

	function __construct() {
		parent::__construct();
	}

	/**
	 * index_get
	 *
	 * Get collection of locations according to a specified search criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function index_get() {
		$this->config->load( 'security' );
		$location = $this->get( 'location' );
		$country = $this->get( 'country' );
		$state = $this->get( 'state' );
		$expand = $this->get( 'expand' );

		$this->load->model( 'model_location' );
		$this->model_location->location = $location;

		if ($expand == 'true') {
			$locations = $this->model_location->partial_location_search( $this->config->item( 'max_locations_result' ) );
		} else {
			$locations = $this->model_location->location_search_cities( $this->config->item( 'max_locations_result' ), $country, $state );
		}

		$this->response( $locations, Base_Controller::HTTP_OK );
	}

	/**
	 * countries_get
	 *
	 * Get the states of a country
	 *
	 * @access    public
	 *
	 * @param bool|int $country_id
	 *
	 * @return array
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 */
	public function countries_get() {
		$this->load->model( 'model_countries' );
		$countries = $this->model_countries->get();

		$this->response( $countries, Base_Controller::HTTP_OK );
	}

	/**
	 * country_get
	 *
	 * Get the states of a country
	 *
	 * @access    public
	 *
	 * @param bool|int $country_id
	 *
	 * @return array
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 */
	public function country_get( $country_id = false ) {
		$this->load->model( 'model_states' );
		$this->model_states->country_id = $country_id;
		$states                         = $this->model_states->get();

		$this->response( $states, Base_Controller::HTTP_OK );
	}
}
