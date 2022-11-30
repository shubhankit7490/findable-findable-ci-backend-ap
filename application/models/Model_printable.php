<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_printable extends Base_Model {

	public $positions = [];

	public $schools = [];

	public $skills = ['basic' => [], 'pretty_good' => [], 'expert' => []];

	public $traits = [];

	public $languages = ['basic' => [], 'good' => [], 'pro' => []];

	public function getSkills() {
		$fields = array(
			$this->tbl_technical_abilities_of_users . '.technical_ability_level as tech_level',
			$this->tbl_technical_abilities . '.technical_ability_name as tech_name'
		);

		$select = implode( ', ', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_technical_abilities_of_users );
		$this->db->join( $this->tbl_technical_abilities, $this->tbl_technical_abilities . '.technical_ability_id = ' . $this->tbl_technical_abilities_of_users . '.technical_ability_id' );
		$this->db->where( $this->tbl_technical_abilities_of_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_technical_abilities_of_users . '.deleted', 0 );
		$query = $this->db->get();

		foreach($query->result_array() as $skill) {
			if($skill['tech_level'] < 33) {
				$this->skills['basic'][] = $skill['tech_name'];
			} else if($skill['tech_level'] > 66) {
				$this->skills['expert'][] = $skill['tech_name'];
			} else {
				$this->skills['pretty_good'][] = $skill['tech_name'];
			}
		}

		return $this;
	}

	public function getLanguages() {
		$fields = array(
			$this->tbl_languages_of_users . '.language_level as lang_level',
			$this->tbl_languages . '.language_name as lang_name'
		);

		$select = implode( ', ', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_languages_of_users );
		$this->db->join( $this->tbl_languages, $this->tbl_languages . '.language_id = ' . $this->tbl_languages_of_users . '.language_id' );
		$this->db->where( $this->tbl_languages_of_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_languages_of_users . '.deleted', 0 );
		$query = $this->db->get();

		foreach($query->result_array() as $language) {
			if($language['lang_level'] < 33) {
				$this->languages['basic'][] = $language['lang_name'];
			} else if($language['lang_level'] > 66) {
				$this->languages['pro'][] = $language['lang_name'];
			} else {
				$this->languages['good'][] = $language['lang_name'];
			}
		}

		return $this;
	}

	public function getTraits() {
		$fields = array(
			$this->tbl_traits_of_users . '.trait_prominance as prominance',
			$this->tbl_traits . '.trait_name as name'
		);

		$select = implode( ', ', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_traits_of_users );
		$this->db->join( $this->tbl_traits, $this->tbl_traits . '.trait_id = ' . $this->tbl_traits_of_users . '.trait_id' );
		$this->db->where( $this->tbl_traits_of_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_traits_of_users . '.deleted', 0 );
		$this->db->order_by($this->tbl_traits_of_users . '.trait_prominance', 'ASC');

		$query = $this->db->get();

		$this->traits	= $query->result_array();
		
		return $this;
	}

	public function getPositions() {
		$position_fields = array(
			$this->tbl_positions_of_users . '.positions_of_users_id as positions_of_users_id',
			$this->tbl_positions_of_users . '.position_from as from',
			$this->tbl_positions_of_users . '.position_to as to',
			$this->tbl_positions_of_users . '.position_current as current',
			$this->tbl_job_title . '.job_title_name as job_title',
			$this->tbl_companies . '.company_name as company_name'
		);

		$location  = $this->model_location->get_model();

		$fields = $this->merge_fields( $location, $position_fields );
		$select = implode( ', ', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_positions_of_users );
		$this->db->join( $this->tbl_job_title, $this->tbl_positions_of_users . '.job_title_id = ' . $this->tbl_job_title . '.job_title_id' );
		$this->db->join( $this->tbl_companies, $this->tbl_positions_of_users . '.company_id = ' . $this->tbl_companies . '.company_id' );
		$this->db->join( $this->tbl_cities, $this->tbl_cities . '.city_id = ' . $this->tbl_positions_of_users . '.city_id' );
		$this->db->join( $this->tbl_states, $this->tbl_states . '.state_id = ' . $this->tbl_cities . '.state_id', 'left' );
		$this->db->join( $this->tbl_countries, $this->tbl_countries . '.country_id = ' . $this->tbl_cities . '.country_id', 'left' );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_countries . '.continent_id', 'left' );
		$this->db->where( $this->tbl_positions_of_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_positions_of_users . '.deleted', 0 );

		$this->db->order_by($this->tbl_positions_of_users . '.position_from', 'DESC');
		$this->db->limit( 50 );

		$query = $this->db->get();
		foreach ( $query->result( 'model_position' ) as $position ) {
			$this->positions[] = $position->get_area_of_focus_of_position();
		};

		return $this;
	}

	public function getSchools() {
		$fields = array(
			$this->tbl_schools_of_users . '.schools_of_user_id as schools_of_user_id',
			$this->tbl_schools_of_users . '.school_from as from',
			$this->tbl_schools_of_users . '.school_to as to',
			$this->tbl_schools_of_users . '.school_current as current',
			$this->tbl_schools . '.school_name as school_name',
			$this->tbl_education_levels . '.education_level_name as education_level_name',
		);

		$select = implode( ', ', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_schools_of_users );
		$this->db->join( $this->tbl_schools, $this->tbl_schools . '.school_id = ' . $this->tbl_schools_of_users . '.school_id' );
		$this->db->join( $this->tbl_education_levels, $this->tbl_education_levels . '.education_level_id = ' . $this->tbl_schools_of_users . '.school_education_level' );
		$this->db->where( $this->tbl_schools_of_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_schools_of_users . '.deleted', 0 );

		$this->db->order_by($this->tbl_schools_of_users . '.school_from', 'DESC');
		$this->db->limit( 50 );
		$query = $this->db->get();
		
		foreach ( $query->result( 'model_education' ) as $school ) {
			$this->schools[] = $school->get_fields_of_study();
		}
		
		return $this;
	}
}
