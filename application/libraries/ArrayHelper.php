<?php

class ArrayHelper {

	function __construct() {

	}

	/**
	 * __get
	 *
	 * Enables the use of CI super-global without having to define an extra variable.
	 *
	 * @access    public
	 *
	 * @param    $var
	 *
	 * @return    mixed
	 */
	public function __get( $var ) {
		if ( isset( $this->$var ) ) {
			return $this->$var;
		} else {
			return get_instance()->$var;
		}
	}

	/**
	 * arrange
	 *
	 * Arrange the results and parse the collection of applicants
	 *
	 * @access    public
	 *
	 * @param    array $results
	 *
	 * @return    array
	 */
	public function arrange( $results = array() ) {
		foreach ( $results['applicants'] as &$applicant ) {
			$temporary_applicant = (object) array(
				'id'         => null,
				'location'   => (object) array(),
				'jobtitle'   => (object) array(),
				'experience' => null,
				'seniority'  => (object) array(),
				'salary'     => (object) array(),
				'status'     => null
			);

			if ( isset( $applicant->id ) ) {
				$temporary_applicant->id = $applicant->id;
			}

			if ( isset( $applicant->location_city_id ) ) {
				$temporary_applicant->location->continent_id               = $applicant->location_continent_id;
				$temporary_applicant->location->continent_name             = $applicant->location_continent_name;
				$temporary_applicant->location->city_id                    = $applicant->location_city_id;
				$temporary_applicant->location->city_name                  = $applicant->location_city_name;
				$temporary_applicant->location->state_id                   = $applicant->location_state_id;
				$temporary_applicant->location->state_name                 = $applicant->location_state_name;
				$temporary_applicant->location->state_short_name           = $applicant->location_state_short_name;
				$temporary_applicant->location->country_id                 = $applicant->location_country_id;
				$temporary_applicant->location->country_name               = $applicant->location_country_name;
				$temporary_applicant->location->country_short_name_alpha_3 = $applicant->location_country_short_name_alpha_3;
				$temporary_applicant->location->country_short_name_alpha_2 = $applicant->location_country_short_name_alpha_2;
			} else if ( isset( $applicant->profile_location_city_id ) ) {
				$temporary_applicant->location->continent_id               = $applicant->profile_location_continent_id;
				$temporary_applicant->location->continent_name             = $applicant->profile_location_continent_name;
				$temporary_applicant->location->city_id                    = $applicant->profile_location_city_id;
				$temporary_applicant->location->city_name                  = $applicant->profile_location_city_name;
				$temporary_applicant->location->state_id                   = $applicant->profile_location_state_id;
				$temporary_applicant->location->state_name                 = $applicant->profile_location_state_name;
				$temporary_applicant->location->state_short_name           = $applicant->profile_location_state_short_name;
				$temporary_applicant->location->country_id                 = $applicant->profile_location_country_id;
				$temporary_applicant->location->country_name               = $applicant->profile_location_country_name;
				$temporary_applicant->location->country_short_name_alpha_3 = $applicant->profile_location_country_short_name_alpha_3;
				$temporary_applicant->location->country_short_name_alpha_2 = $applicant->profile_location_country_short_name_alpha_2;
			} else {
				$temporary_applicant->location->continent_id               = null;
				$temporary_applicant->location->continent_name             = null;
				$temporary_applicant->location->city_id                    = null;
				$temporary_applicant->location->city_name                  = null;
				$temporary_applicant->location->state_id                   = null;
				$temporary_applicant->location->state_name                 = null;
				$temporary_applicant->location->state_short_name           = null;
				$temporary_applicant->location->country_id                 = null;
				$temporary_applicant->location->country_name               = null;
				$temporary_applicant->location->country_short_name_alpha_3 = null;
				$temporary_applicant->location->country_short_name_alpha_2 = null;
			}

			if ( isset( $applicant->job_title_id ) ) {
				$temporary_applicant->jobtitle->id   = $applicant->job_title_id;
				$temporary_applicant->jobtitle->name = $applicant->job_title_name;
			} else {
				$temporary_applicant->jobtitle->id   = null;
				$temporary_applicant->jobtitle->name = null;
			}

			if ( isset( $applicant->total_experience ) ) {
				$temporary_applicant->experience = $applicant->total_experience;
			} else {
				$temporary_applicant->experience = null;
			}

			if ( isset( $applicant->seniority_id ) ) {
				$temporary_applicant->seniority->id   = $applicant->seniority_id;
				$temporary_applicant->seniority->name = ucwords($applicant->seniority_name);
			} else {
				$temporary_applicant->seniority->id   = null;
				$temporary_applicant->seniority->name = null;
			}

			if ( isset( $applicant->user_status_desired_salary ) ) {
				$temporary_applicant->salary->salary        = $applicant->user_status_desired_salary;
				$temporary_applicant->salary->salary_period = $applicant->user_status_desired_salary_period;
			} else {
				$temporary_applicant->salary->salary        = null;
				$temporary_applicant->salary->salary_period = null;
			}

			if ( isset( $applicant->applicant_status ) ) {
				$temporary_applicant->status = $applicant->applicant_status;
			}

			$temporary_applicant->firstname = null;
			$temporary_applicant->lastname  = null;

			if ( (isset( $applicant->purchased ) && $applicant->purchased == 1) || (isset( $applicant->applied ) && $applicant->applied == 1 ) ) {
					$temporary_applicant->firstname = $applicant->firstname;
					$temporary_applicant->lastname  = $applicant->lastname;
			}

			$applicant = $temporary_applicant;

		}

		return $results;
	}
	public function arrange_search( $results = array(),$user_id,$user_ids=NULL ) {
		$this->config->load( 'upload' );
		$config = $this->config->item( 'upload' );
		$this->load->library( 'upload', $config );
		foreach ( $results['applicants'] as &$applicant ) {
			$temporary_applicant = (object) array(
				'id'         => null,
				'location'   => (object) array(),
				'jobtitle'   => (object) array(),
				'experience' => null,
				'seniority'  => (object) array(),
				'salary'     => (object) array(),
				'status'     => null
			);

			if ( isset( $applicant->id ) ) {
				$temporary_applicant->id = $applicant->id;
			}

			if ( isset( $applicant->location_city_id ) ) {
				$temporary_applicant->location->continent_id               = $applicant->location_continent_id;
				$temporary_applicant->location->continent_name             = $applicant->location_continent_name;
				$temporary_applicant->location->city_id                    = $applicant->location_city_id;
				$temporary_applicant->location->city_name                  = $applicant->location_city_name;
				$temporary_applicant->location->state_id                   = $applicant->location_state_id;
				$temporary_applicant->location->state_name                 = $applicant->location_state_name;
				$temporary_applicant->location->state_short_name           = $applicant->location_state_short_name;
				$temporary_applicant->location->country_id                 = $applicant->location_country_id;
				$temporary_applicant->location->country_name               = $applicant->location_country_name;
				$temporary_applicant->location->country_short_name_alpha_3 = $applicant->location_country_short_name_alpha_3;
				$temporary_applicant->location->country_short_name_alpha_2 = $applicant->location_country_short_name_alpha_2;
			} else if ( isset( $applicant->profile_location_city_id ) ) {
				$temporary_applicant->location->continent_id               = $applicant->profile_location_continent_id;
				$temporary_applicant->location->continent_name             = $applicant->profile_location_continent_name;
				$temporary_applicant->location->city_id                    = $applicant->profile_location_city_id;
				$temporary_applicant->location->city_name                  = $applicant->profile_location_city_name;
				$temporary_applicant->location->state_id                   = $applicant->profile_location_state_id;
				$temporary_applicant->location->state_name                 = $applicant->profile_location_state_name;
				$temporary_applicant->location->state_short_name           = $applicant->profile_location_state_short_name;
				$temporary_applicant->location->country_id                 = $applicant->profile_location_country_id;
				$temporary_applicant->location->country_name               = $applicant->profile_location_country_name;
				$temporary_applicant->location->country_short_name_alpha_3 = $applicant->profile_location_country_short_name_alpha_3;
				$temporary_applicant->location->country_short_name_alpha_2 = $applicant->profile_location_country_short_name_alpha_2;
			} else {
				$temporary_applicant->location->continent_id               = null;
				$temporary_applicant->location->continent_name             = null;
				$temporary_applicant->location->city_id                    = null;
				$temporary_applicant->location->city_name                  = null;
				$temporary_applicant->location->state_id                   = null;
				$temporary_applicant->location->state_name                 = null;
				$temporary_applicant->location->state_short_name           = null;
				$temporary_applicant->location->country_id                 = null;
				$temporary_applicant->location->country_name               = null;
				$temporary_applicant->location->country_short_name_alpha_3 = null;
				$temporary_applicant->location->country_short_name_alpha_2 = null;
			}

			if ( isset( $applicant->job_title_id ) ) {
				$temporary_applicant->jobtitle->id   = $applicant->job_title_id;
				$temporary_applicant->jobtitle->name = $applicant->job_title_name;
			} else {
				$temporary_applicant->jobtitle->id   = null;
				$temporary_applicant->jobtitle->name = null;
			}

			if ( isset( $applicant->total_experience ) ) {
				$temporary_applicant->experience = $applicant->total_experience;
			} else {
				$temporary_applicant->experience = null;
			}

			if ( isset( $applicant->seniority_id ) ) {
				$temporary_applicant->seniority->id   = $applicant->seniority_id;
				$temporary_applicant->seniority->name = ucwords($applicant->seniority_name);
			} else {
				$temporary_applicant->seniority->id   = null;
				$temporary_applicant->seniority->name = null;
			}

			if ( isset( $applicant->user_status_desired_salary ) ) {
				$temporary_applicant->salary->salary        = $applicant->user_status_desired_salary;
				$temporary_applicant->salary->salary_period = $applicant->user_status_desired_salary_period;
			} else {
				$temporary_applicant->salary->salary        = null;
				$temporary_applicant->salary->salary_period = null;
			}

			if ( isset( $applicant->applicant_status ) ) {
				$temporary_applicant->status = $applicant->applicant_status;
			}

			$temporary_applicant->firstname = null;
			$temporary_applicant->lastname  = null;

			if ($user_id==$applicant->creator_id || (in_array($applicant->creator_id, $user_ids))) {
					$temporary_applicant->firstname = $applicant->firstname;
					$temporary_applicant->lastname  = $applicant->lastname;
			}
			$temporary_applicant->creator_id = $applicant->creator_id;
			$temporary_applicant->display_edit=false;
			if(in_array($applicant->creator_id, $user_ids)){
				$temporary_applicant->display_edit=true;
			}
			if(in_array($applicant->creator_id, $user_ids)){
				$temporary_applicant->resume_id=$applicant->resume_id;
				$url = str_replace( 'http://', 'https://', $this->upload->get_public_url( str_replace("/var/www/html","http://localhost",$applicant->resume_url)));

				$temporary_applicant->resume_url=str_replace("https://staging.findable-api.appspot.com.storage.googleapis.com","https://storage.googleapis.com/staging.findable-api.appspot.com",$url);
			}
			$temporary_applicant->market_place = $applicant->market_place;
			$applicant = $temporary_applicant;

		}

		return $results;
	}
	/**
	 * iterate_stats
	 *
	 * Collect statistics about the given collection of applicants
	 *
	 * @access    public
	 *
	 * @param    array $results
	 *
	 * @return    array
	 */
	public function iterate_stats( $results = array() ) {
		$statistics = (object) array(
			'applied'         => 0,
			'purchased'       => 0,
			'initial_contact' => 0,
			'interviewing'    => 0,
			'short'           => 0,
			'rejected'        => 0,
			'hired'           => 0
		);

		foreach ( $results['applicants'] as &$applicant ) {
			$applicant->applicant_status = ( is_null( $applicant->applicant_status ) ) ? $applicant->applicant_status : trim( $applicant->applicant_status );

			// applied
			if ( $applicant->applied == 1 ) {
				$statistics->applied++;
			}

			// purchased
			if ( $applicant->purchased == 1 ) {
				$statistics->purchased++;
			}

			// initial_contact
			if ( $applicant->applicant_status === 'initial' ) {
				$statistics->initial_contact++;
			}

			// interviewing
			if ( $applicant->applicant_status === 'interviewing' ) {
				$statistics->interviewing++;
			}

			// short
			if ( $applicant->applicant_status === 'short' ) {
				$statistics->short++;
			}

			// rejected
			if ( $applicant->applicant_status === 'rejected' ) {
				$statistics->rejected++;
			}

			// rejected
			if ( $applicant->applicant_status === 'hired' ) {
				$statistics->hired++;
			}
		}

		return $statistics;
	}
}