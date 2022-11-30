<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_location extends Base_Model {
	public $location = false;

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
			$this->tbl_continents . '.continent_id as continent_id',
			$this->tbl_continents . '.continent_name as continent_name',
			$this->tbl_countries . '.country_id as country_id',
			$this->tbl_countries . '.country_name as country_name',
			$this->tbl_countries . '.country_short_name_alpha_2 as country_short_name_alpha_2',
			$this->tbl_countries . '.country_short_name_alpha_3 as country_short_name_alpha_3',
			$this->tbl_states . '.state_id as state_id',
			$this->tbl_states . '.state_name as state_name',
			$this->tbl_states . '.state_short_name as state_short_name',
			$this->tbl_cities . '.city_name as city_name',
			$this->tbl_cities . '.city_id as city_id'
		);
	}

	/**
	 * get_search_model
	 *
	 * Get the model as defined in the Swagger specs for the applicant search profile
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_search_model() {
		return array(
			'continents.continent_id as continent_id',
			'continents.continent_name as continent_name',
			'countries.country_id as country_id',
			'countries.country_name as country_name',
			'countries.country_short_name_alpha_2 as country_short_name_alpha_2',
			'countries.country_short_name_alpha_3 as country_short_name_alpha_3',
			'states.state_id as state_id',
			'states.state_name as state_name',
			'states.state_short_name as state_short_name',
			'cities.city_name as city_name',
			'cities.city_id as city_id'
		);
	}

	/**
	 * full_location_search
	 *
	 * Get the collection of locations based a on a search criteria
	 * If found country the resulting collection will contain all the cities and the states within that country
	 * If found a state the resulting collection will contain all the cities within that state
	 *
	 * @access    public
	 *
	 * @return    array
	 */
	public function full_location_search($limit = false) {
		$sql = "SELECT `continents`.`continent_id` as `continent_id`,
			`continents`.`continent_name` as `continent_name`, 
			`countries`.`country_id` as `country_id`, 
			`countries`.`country_name` as `country_name`, 
			`countries`.`country_short_name_alpha_2` as `country_short_name_alpha_2`, 
			`countries`.`country_short_name_alpha_3` as `country_short_name_alpha_3`, 
			`states`.`state_id` as `state_id`, 
			`states`.`state_name` as `state_name`, 
			`states`.`state_short_name` as `state_short_name`, 
			`cities`.`city_name` as `city_name`, 
			`cities`.`city_id` as `city_id`
			FROM cities
			LEFT JOIN `states` ON `states`.`state_id` = `cities`.`state_id` AND `cities`.`state_id`
			LEFT JOIN `countries` ON `countries`.`country_id` = `cities`.`country_id`
			LEFT JOIN `continents` ON `continents`.`continent_id` = `countries`.`continent_id`
			WHERE MATCH(cities.city_name) AGAINST ('" . $this->db->escape_str( $this->prepare( $this->location ) ) . "*' IN BOOLEAN MODE) > 0
			UNION
			SELECT `continents`.`continent_id` as `continent_id`,
			`continents`.`continent_name` as `continent_name`, 
			`countries`.`country_id` as `country_id`, 
			`countries`.`country_name` as `country_name`, 
			`countries`.`country_short_name_alpha_2` as `country_short_name_alpha_2`, 
			`countries`.`country_short_name_alpha_3` as `country_short_name_alpha_3`, 
			`states`.`state_id` as `state_id`, 
			`states`.`state_name` as `state_name`, 
			`states`.`state_short_name` as `state_short_name`, 
			`cities`.`city_name` as `city_name`, 
			`cities`.`city_id` as `city_id`
			FROM cities
			LEFT JOIN `states` ON `states`.`state_id` = `cities`.`state_id` AND `cities`.`state_id`
			LEFT JOIN `countries` ON `countries`.`country_id` = `cities`.`country_id`
			LEFT JOIN `continents` ON `continents`.`continent_id` = `countries`.`continent_id`
			WHERE MATCH(states.state_name) AGAINST ('" . $this->db->escape_str( $this->prepare( $this->location ) ) . "*' IN BOOLEAN MODE) > 0
			UNION
			SELECT `continents`.`continent_id` as `continent_id`,
			`continents`.`continent_name` as `continent_name`, 
			`countries`.`country_id` as `country_id`, 
			`countries`.`country_name` as `country_name`, 
			`countries`.`country_short_name_alpha_2` as `country_short_name_alpha_2`, 
			`countries`.`country_short_name_alpha_3` as `country_short_name_alpha_3`, 
			`states`.`state_id` as `state_id`, 
			`states`.`state_name` as `state_name`, 
			`states`.`state_short_name` as `state_short_name`, 
			`cities`.`city_name` as `city_name`, 
			`cities`.`city_id` as `city_id`
			FROM cities
			LEFT JOIN `states` ON `states`.`state_id` = `cities`.`state_id` AND `cities`.`state_id`
			LEFT JOIN `countries` ON `countries`.`country_id` = `cities`.`country_id`
			LEFT JOIN `continents` ON `continents`.`continent_id` = `countries`.`continent_id`
			WHERE MATCH(countries.country_name) AGAINST ('" . $this->db->escape_str( $this->prepare( $this->location ) ) . "*' IN BOOLEAN MODE) > 0";

		if($limit !== false) {
			$sql .= " Limit " . $limit;
		}

		$query = $this->db->query( $sql );

		return $query->result();
	}

	/**
	 * partial_location_search
	 *
	 * Get the collection of locations based a on a search criteria
	 * If found countries the resulting collection will contain only those countries
	 * If found states the resulting collection will contain only those states
	 *
	 * @access    public
	 *
	 * @return    array
	 */
	public function partial_location_search( $limit = false) {

		$sql = "SELECT `continents`.`continent_id` as `continent_id`,
			`continents`.`continent_name` as `continent_name`, 
			`countries`.`country_id` as `country_id`, 
			`countries`.`country_name` as `country_name`, 
			`countries`.`country_short_name_alpha_2` as `country_short_name_alpha_2`, 
			`countries`.`country_short_name_alpha_3` as `country_short_name_alpha_3`, 
			null as `state_id`, 
			null as `state_name`, 
			null as `state_short_name`, 
			null as `city_name`, 
			null as `city_id`
			FROM countries
			LEFT JOIN `continents` ON `continents`.`continent_id` = `countries`.`continent_id`
			WHERE MATCH(countries.country_name) AGAINST ('" . $this->db->escape_str( $this->prepare( $this->location ) ) . "*' IN BOOLEAN MODE) > 0
			UNION
			SELECT `continents`.`continent_id` as `continent_id`,
			`continents`.`continent_name` as `continent_name`, 
			`countries`.`country_id` as `country_id`, 
			`countries`.`country_name` as `country_name`, 
			`countries`.`country_short_name_alpha_2` as `country_short_name_alpha_2`, 
			`countries`.`country_short_name_alpha_3` as `country_short_name_alpha_3`, 
			`states`.`state_id` as `state_id`, 
			`states`.`state_name` as `state_name`, 
			`states`.`state_short_name` as `state_short_name`, 
			null as `city_name`, 
			null as `city_id`
			FROM states
			LEFT JOIN `countries` ON `countries`.`country_id` = `states`.`country_id`
			LEFT JOIN `continents` ON `continents`.`continent_id` = `countries`.`continent_id`
			WHERE MATCH(states.state_name) AGAINST ('" . $this->db->escape_str( $this->prepare( $this->location ) ) . "*' IN BOOLEAN MODE) > 0
			UNION
			SELECT `continents`.`continent_id` as `continent_id`,
			`continents`.`continent_name` as `continent_name`, 
			`countries`.`country_id` as `country_id`, 
			`countries`.`country_name` as `country_name`, 
			`countries`.`country_short_name_alpha_2` as `country_short_name_alpha_2`, 
			`countries`.`country_short_name_alpha_3` as `country_short_name_alpha_3`, 
			`states`.`state_id` as `state_id`, 
			`states`.`state_name` as `state_name`, 
			`states`.`state_short_name` as `state_short_name`, 
			`cities`.`city_name` as `city_name`, 
			`cities`.`city_id` as `city_id`
			FROM cities
			LEFT JOIN `states` ON `states`.`state_id` = `cities`.`state_id` AND `cities`.`state_id`
			LEFT JOIN `countries` ON `countries`.`country_id` = `cities`.`country_id`
			LEFT JOIN `continents` ON `continents`.`continent_id` = `countries`.`continent_id`
			WHERE MATCH(cities.city_name) AGAINST ('" . $this->db->escape_str( $this->prepare( $this->location ) ) . "*' IN BOOLEAN MODE) > 0";

		if($limit !== false) {
			$sql .= " Limit " . $limit;
		}

		$query = $this->db->query( $sql );

		return $query->result();
	}

	/**
	 * location_search_cities
	 *
	 * Get collection of locations according to a specified search criteria (searching only for cities)
	 *
	 * @access    public
	 *
	 * @params bool|int $limit
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function location_search_cities($limit = false, $country = null, $state = null) {
		$sql = "SELECT `continents`.`continent_id` as `continent_id`,
			`continents`.`continent_name` as `continent_name`, 
			`countries`.`country_id` as `country_id`, 
			`countries`.`country_name` as `country_name`, 
			`countries`.`country_short_name_alpha_2` as `country_short_name_alpha_2`, 
			`countries`.`country_short_name_alpha_3` as `country_short_name_alpha_3`, 
			`states`.`state_id` as `state_id`, 
			`states`.`state_name` as `state_name`, 
			`states`.`state_short_name` as `state_short_name`, 
			`cities`.`city_name` as `city_name`, 
			`cities`.`city_id` as `city_id`
			FROM cities
			LEFT JOIN `states` ON `states`.`state_id` = `cities`.`state_id` AND `cities`.`state_id`
			LEFT JOIN `countries` ON `countries`.`country_id` = `cities`.`country_id`
			LEFT JOIN `continents` ON `continents`.`continent_id` = `countries`.`continent_id`";

		//$sql .= "WHERE MATCH(cities.city_name) AGAINST ('" . $this->db->escape_str( $this->prepare( $this->location ) ) . "*' IN BOOLEAN MODE) > 0";

		$sql .= "WHERE cities.city_name LIKE '" . $this->db->escape_str( $this->prepare( $this->location ) ) . "%'";

		if(is_numeric($country)) {
			$sql .= " AND cities.country_id = " . $country;
		}

		if(is_numeric($state)) {
			$sql .= " AND cities.state_id = " . $state;
		}

		if($limit !== false) {
			$sql .= " Limit " . $limit;
		}

		$query = $this->db->query( $sql );
		return $query->result();
	}

	/**
	 * get_city_id
	 *
	 * Transaction for upserting (indexing) a google's place api address (Place object)
	 *
	 * @access    public
	 *
	 * @param array $location (city_name, state_name, country_name)
	 *
	 * @param boolean $use_transaction
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean|integer
	 */
	public function get_city_id($location = [], $use_transaction = true) {
		if($use_transaction) {
			// Start a database transaction
			$this->db->trans_begin();
		}

		if (isset($location['country_name']) && !empty($location['country_name'])) {
			// Get the country by country_name
			$this->db->select('country_id');
			$this->db->from('countries');

			if (strlen($location['country_name']) ===  3) {
				$this->db->where('country_short_name_alpha_3', $location['country_name']);	
			} else
			if (strlen($location['country_name']) ===  2) {
				$this->db->where('country_short_name_alpha_2', $location['country_name']);	
			} else {
				$this->db->where('country_name', ucfirst($location['country_name']));
			}

			$query = $this->db->get();
			
			if ( $query->num_rows() > 0 ) {
				$country = $query->row();
				$country_id = $country->country_id;
			} else {
				return false;
			}
		}

		if (isset($location['state_name']) && !empty($location['state_name'])) {
			$this->db->select('state_id');
			$this->db->from('states');
			$this->db->where('state_name', $location['state_name']);
			$this->db->where('country_id', $country_id);
			$this->db->limit(1);

			$query = $this->db->get();

			if ( $query->num_rows() > 0 ) {
				$state = $query->row();
				$state_id = $state->state_id;
			} else {
				$this->db->insert('states', [
					'country_id' => $country_id,
					'state_name' => $location['state_name'],
					'state_short_name' => $location['state_short_name']
				]);
				$state_id = $this->db->insert_id();
			}
		} else {
			$state_id = NULL;
		}

		if (isset($location['city_name']) && !empty($location['city_name'])) {
			// Attempt to find the city_id by the name
			$this->db->select('city_id');
			$this->db->from('cities');
			$this->db->where('city_name', $location['city_name']);
			$this->db->where('country_id', $country_id);

			if ( ! is_null($state_id) ) {
				$this->db->where('state_id', $state_id);
			}

			$query = $this->db->get();

			if ( $query->num_rows() > 0 ) {
				$city = $query->row();
				return $city->city_id;
			}
		}

		$this->db->insert('cities', [
			'country_id' => $country_id,
			'city_name' => $location['city_name'],
			'state_id' => $state_id
		]);

		$city_id = $this->db->insert_id();

		if($use_transaction) {
			if ($this->db->trans_status() === FALSE)
			{
				$this->db->trans_rollback();
				return false;
			}
			else
			{
				$this->db->trans_commit();
				return $city_id;
			}
		} else {
			return $city_id;
		}
	}

	/**
	 * prepare
	 *
	 * Escape special chars for a full-text-search
	 *
	 * @access    private
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	private function prepare() {
		return preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $this->location);
	}
}