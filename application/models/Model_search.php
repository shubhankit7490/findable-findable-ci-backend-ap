<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_search extends Base_Model {
	public $search = null;
	public $user_id = null;
	public $business_id = null;

	// Search flags: BusinessApplicantsOnly - Search only applicants for the current business
	private static $BusinessApplicantsOnly = false;

	// Search flags: BusinessApplicantsOnly - Search only applicants which did not applied to the current business nor been purchased by the business
	private static $NotBusinessApplicants = false;

	/**
	 * set_flags
	 *
	 * Set the global search flags
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function set_flags( $flag, $value ) {
		self::$$flag = $value;
	}

	/**
	 * get_filtered_applicants
	 *
	 * Perform a search for applicants
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_filtered_applicants( $offset = 1, $orderby = 'id', $order = 'ASC', $limit = 50,$user_ids=NULL ) {
		$sql                 = '';
		$final_query         = '';
		$position_where      = [];
		$status_where        = [];
		$area_of_focus_where = [];
		$languages_where     = [];
		$where_languages		 = [];
		$traits_where        = [];
		$tech_skills_where   = [];
		$education_where     = [];
		$location_where      = [];
		$__location_where    = [];
		$profile_where       = [];

		$response = [
			'total'      => 0,
			'applicants' => []
		];

		// Position based filters - first order selection
		$select = array(
			'DISTINCT (u.user_id) as id',
			'u.status as user_status',
			'u.created_by as created_by',
			'u.creator_id as creator_id',
			'u.market_place as market_place',
			'u.role as user_role',
			'u.search_visible as user_visible',
			'pr.profile_firstname as firstname',
			'pr.profile_lastname as lastname'
		);

		// Positions related conditions
		if ( $this->key_exists( 'jobtitles' ) && count( $this->search['jobtitles'] ) > 0 ) {
			$jobtitle_ids = array_column( array_filter( $this->search['jobtitles'], function ( $var ) {
				if ( $var['id'] !== null ) {
					return true;
				}
			} ), 'id' );

			$jobtitle_name = array_column( array_filter( $this->search['jobtitles'], function ( $var ) {
				if ( $var['id'] === null && strlen($var['name']) > 0 ) {
					return true;
				}
			} ), 'name' );

			if ( count( $jobtitle_ids ) ) {
				if ( count( $jobtitle_name ) ) {
					$position_where[] = '(job_title_id IN (' . implode( ',', $jobtitle_ids ) . ') OR ' . "job_title_id IN (SELECT job_title_id FROM job_title WHERE job_title_name LIKE '" . $jobtitle_name[0] . "%'))";
				} else {
					$position_where[] = 'job_title_id IN (' . implode( ',', $jobtitle_ids ) . ')';
				}
			} else if ( count( $jobtitle_name ) ) {
				$position_where[] = "job_title_id IN (SELECT job_title_id FROM job_title WHERE job_title_name LIKE '" . $jobtitle_name[0] . "%' AND deleted = 0)";
			}
		}
		if ( $this->key_exists( 'experience_from' ) && ! is_null( $this->search['experience_from'] ) ) {
			// Temporary fix - Using a HAVING statement
			// $position_where[] = 'IF(ISNULL(position_to), FLOOR((UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(position_from)) / 31556926), FLOOR((UNIX_TIMESTAMP(position_to) - UNIX_TIMESTAMP(position_from)) / 31556926)) >= ' . $this->search['experience_from'];
		}
		if ( $this->key_exists( 'experience_to' ) && ! is_null( $this->search['experience_to'] ) ) {
			// Temporary fix - Using a HAVING statement
			//$position_where[] = 'IF(ISNULL(position_to), FLOOR((UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(position_from)) / 31556926), FLOOR((UNIX_TIMESTAMP(position_to) - UNIX_TIMESTAMP(position_from)) / 31556926)) <= ' . $this->search['experience_to'];
		}
		if ( $this->key_exists( 'company_id' ) && ! is_null( $this->search['company_id'] ) ) {
			if ( is_null($this->search['company_id']['id'])) {
				$position_where[] = "company_id IN (SELECT company_id FROM companies WHERE company_name LIKE '" . $this->search['company_id']['name'] . "%' AND deleted = 0)";
			} else {
				$position_where[] = 'company_id = ' . $this->search['company_id']['id'];
			}
		}
		if ( $this->key_exists( 'seniority' ) && ! is_null( $this->search['seniority'] ) ) {
			$position_where[] = 'seniority_id = ' . $this->search['seniority'];
		}
		if ( $this->key_exists( 'industry' ) && ! is_null( $this->search['industry'] ) ) {
			if ( is_null($this->search['industry']['id'])) {
				$position_where[] = "industry_id IN (SELECT industry_id FROM industries WHERE industry_name LIKE '" . $this->search['industry']['name'] . "%' AND deleted = 0)";
			} else {
				$position_where[] = 'industry_id = ' . $this->search['industry']['id'];
			}
		}

		// Areas of focus / Responsibilities
		if ( $this->key_exists( 'areas_of_focus' ) ) {
			if ( count( $this->search['areas_of_focus'] ) > 0 ) {
				$responsibility_ids = array_column( array_filter( $this->search['areas_of_focus'], function ( $var ) {
					if ( $var['id'] !== null ) {
						return true;
					}
				} ), 'id' );

				$responsibility_name = array_column( array_filter( $this->search['areas_of_focus'], function ( $var ) {
					if ( $var['id'] === null && strlen($var['name']) > 0 ) {
						return true;
					}
				} ), 'name' );

				if ( count( $responsibility_ids ) ) {
					if ( count( $responsibility_name ) ) {
						$area_of_focus_where[] = '(area_of_focus_id IN (' . implode( ',', $responsibility_ids ) . ') OR ' . "area_of_focus_id IN (SELECT area_of_focus_id FROM areas_of_focus WHERE area_of_focus_name LIKE '" . $responsibility_name[0] . "%'))";
					} else {
						$area_of_focus_where[] = 'area_of_focus_id IN (' . implode( ',', $responsibility_ids ) . ')';
					}
				} else if ( count( $responsibility_name ) ) {
					$area_of_focus_where[] = "area_of_focus_id IN (SELECT area_of_focus_id FROM areas_of_focus WHERE area_of_focus_name LIKE '" . $responsibility_name[0] . "%' AND deleted = 0)";
				}
			}
		}

		// Status and preferences conditions
		if ( $this->key_exists( 'position_type' ) && ! empty( $this->search['position_type'] ) ) {
			$status_where[] = 'user_status_employment_type = ' . $this->db->escape( $this->search['position_type'] );

			// Adjust the position type (salary_period)
			if ($this->search['position_type'] == 'part time')
			{
				$status_where[] = '(user_status_desired_salary_period IN ("H") OR user_status_desired_salary_period IS NULL OR user_status_desired_salary_period = "")';
			}
			else
			{
				$status_where[] = '(user_status_desired_salary_period IN ("Y", "M") OR user_status_desired_salary_period IS NULL OR user_status_desired_salary_period = "")';
			}
		}
		if ( $this->key_exists( 'employment_status' ) && ! empty( $this->search['employment_status'] ) ) {
			$status_where[] = 'user_status_employment_status = ' . $this->db->escape( $this->search['employment_status'] );
		}
		if ( $this->key_exists( 'relocation' ) ) {
			if ( $this->search['relocation'] == 'true' ) {
				$status_where[] = 'user_status_relocation = 1';
			} else if ( $this->search['relocation'] == 'false' ) {
				$status_where[] = 'user_status_relocation = 0';
			}
		}	

		// User salary preferences
		$salary_transform_query = '
			(CASE 
				WHEN user_status_desired_salary_period = "M" THEN user_status_desired_salary / 4
				WHEN user_status_desired_salary_period = "Y" THEN user_status_desired_salary / 48
				WHEN user_status_desired_salary_period = "W" THEN user_status_desired_salary
				ELSE 0
			END)';

		if ( $this->key_exists( 'salary_from' ) ) {
			if ( isset( $this->search['salary_from']['salary_period'] ) && ! empty( $this->search['salary_from']['salary_period'] ) ) {
				if ( ! is_null( $this->search['salary_from']['salary'] ) ) {
					$requested_weekly_salary = false;
					switch ( $this->search['salary_from']['salary_period'] ) {
						case 'H':
							$status_where[] = 'user_status_desired_salary >= ' . $this->search['salary_from']['salary'];
							$status_where[] = 'user_status_desired_salary_period = "' . $this->search['salary_from']['salary_period'] . '"';
							break;
						case 'M':
							$requested_weekly_salary = floor( $this->search['salary_from']['salary'] / 4 );
							$status_where[]          = $salary_transform_query . ' >= ' . $requested_weekly_salary;
							break;
						case 'Y':
							$requested_weekly_salary = floor( $this->search['salary_from']['salary'] / 48 );
							$status_where[]          = $salary_transform_query . ' >= ' . $requested_weekly_salary;
							break;
						case 'W':
							$requested_weekly_salary = floor( $this->search['salary_from']['salary'] );
							$status_where[]          = $salary_transform_query . ' >= ' . $requested_weekly_salary;
							break;
					}
				} else {
					$status_where[] = 'user_status_desired_salary_period = "' . $this->search['salary_from']['salary_period'] . '"';
				}
			}
		}
		if ( $this->key_exists( 'salary_to' ) ) {
			if ( isset( $this->search['salary_to']['salary_period'] ) && ! empty( $this->search['salary_to']['salary_period'] ) ) {
				if ( ! is_null( $this->search['salary_to']['salary'] ) ) {
					$requested_weekly_salary = false;
					switch ( $this->search['salary_to']['salary_period'] ) {
						case 'H':
							$status_where[] = 'user_status_desired_salary <= ' . $this->search['salary_to']['salary'];
							$status_where[] = 'user_status_desired_salary_period = "' . $this->search['salary_to']['salary_period'] . '"';
							break;
						case 'M':
							$requested_weekly_salary = floor( $this->search['salary_to']['salary'] / 4 );
							$status_where[]          = $salary_transform_query . ' <= ' . $requested_weekly_salary;
							break;
						case 'Y':
							$requested_weekly_salary = floor( $this->search['salary_to']['salary'] / 48 );
							$status_where[]          = $salary_transform_query . ' <= ' . $requested_weekly_salary;
							break;
						case 'W':
							$requested_weekly_salary = floor( $this->search['salary_to']['salary'] );
							$status_where[]          = $salary_transform_query . ' <= ' . $requested_weekly_salary;
							break;
					}
				} else {
					$status_where[] = 'user_status_desired_salary_period = "' . $this->search['salary_to']['salary_period'] . '"';
				}
			}
		}
		if ( $this->key_exists( 'benefits' ) && ! empty( $this->search['benefits'] ) ) {
			switch ( $this->search['benefits'] ) {
				case 'all':
					$status_where[] = '(user_status_benefits = 1 OR user_status_benefits = 0)';
					break;
				case 'not':
					$status_where[] = 'user_status_benefits = 0';
					break;
				case 'only':
					$status_where[] = 'user_status_benefits = 1';
					break;
			}
		}
		if ( $this->key_exists( 'legal_usa' ) ) {
			if ( $this->search['legal_usa'] == 'true' ) {
				$status_where[] = 'user_status_legal_usa = 1';
			} else if ( $this->search['legal_usa'] == 'false' ) {
				$status_where[] = 'user_status_legal_usa = 0';
			}
		}

		// Languages
		if ( $this->key_exists( 'languages' ) ) {
			if ( count( $this->search['languages'] ) > 0 ) {
				$language_ids = array_column( array_filter( $this->search['languages'], function ( $var ) {
					if ( $var['id'] !== null ) {
						return true;
					}
				} ), 'id' );

				$language_name = array_column( array_filter( $this->search['languages'], function ( $var ) {
					if ( $var['id'] === null && strlen($var['name']) > 0 ) {
						return true;
					}
				} ), 'name' );

				if ( count( $language_ids ) ) {
					if ( count( $language_name ) ) {
						$languages_where[] = '(language_id IN (' . implode( ',', $language_ids ) . ') OR ' . "language_id IN (SELECT language_id FROM languages WHERE language_name LIKE '" . $language_name[0] . "%'))";
						} else {
							for ($g = 0; $g < count( $language_ids ); $g++) {
								$languages_where[] = "INNER JOIN languages_of_users lu$g ON lu$g.user_id = lu.user_id AND lu$g.language_id = $language_ids[$g]";
								$where_languages[] = "lu$g.deleted = 0";
							}
							unset($g);
						}
				} else if ( count( $language_name ) ) {
					$languages_where[] = "language_id IN (SELECT language_id FROM languages WHERE language_name LIKE '" . $language_name[0] . "%' AND deleted = 0)";
				}
			}
		}

		// Traits
		if ( $this->key_exists( 'traits' ) ) {
			if ( count( $this->search['traits'] ) > 0 ) {

				$trait_ids = array_column( array_filter( $this->search['traits'], function ( $var ) {
					if ( $var['id'] !== null ) {
						return true;
					}
				} ), 'id' );

				$trait_name = array_column( array_filter( $this->search['traits'], function ( $var ) {
					if ( $var['id'] === null && strlen($var['name']) > 0 ) {
						return true;
					}
				} ), 'name' );

				if ( count( $trait_ids ) ) {
					if ( count( $trait_name ) ) {
						$traits_where[] = '(trait_id IN (' . implode( ',', $trait_ids ) . ') OR ' . "trait_id IN (SELECT trait_id FROM traits WHERE trait_name LIKE '" . $trait_name[0] . "%'))";
					} else {
						$traits_where[] = 'trait_id IN (' . implode( ',', $trait_ids ) . ')';
					}
				} else if ( count( $trait_name ) ) {
					$traits_where[] = "trait_id IN (SELECT trait_id FROM traits WHERE trait_name LIKE '" . $trait_name[0] . "%' AND deleted = 0)";
				}
			}
		}

		// Technical skills
		if ( $this->key_exists( 'technical_abillities' ) ) {

			$skills_ids = array_column( array_filter( $this->search['technical_abillities'], function ( $var ) {
				if ( $var['id'] !== null ) {
					return true;
				}
			} ), 'id' );

			$skill_name = array_column( array_filter( $this->search['technical_abillities'], function ( $var ) {
				if ( $var['id'] === null && strlen($var['name']) > 0 ) {
					return true;
				}
			} ), 'name' );

			if ( count( $skills_ids ) ) {
				if ( count( $skill_name ) ) {
					$tech_skills_where[] = '(technical_ability_id IN (' . implode( ',', $skills_ids ) . ') OR ' . "technical_ability_id IN (SELECT technical_ability_id FROM technical_abilities WHERE technical_ability_name LIKE '" . $skill_name[0] . "%'))";
				} else {
					$tech_skills_where[] = 'technical_ability_id IN (' . implode( ',', $skills_ids ) . ')';
				}
			} else if ( count( $skill_name ) ) {
				$tech_skills_where[] = "technical_ability_id IN (SELECT technical_ability_id FROM technical_abilities WHERE technical_ability_name LIKE '" . $skill_name[0] . "%' AND deleted = 0)";
			}
		}

		// User education
		if ( $this->key_exists( 'school_name' ) && ! is_null( $this->search['school_name'] ) ) {
			if ( is_null($this->search['school_name']['id'])) {
				$education_where[] = "school_id IN (SELECT school_id FROM schools WHERE school_name LIKE '" . $this->search['school_name']['name'] . "%' AND deleted = 0)";
			} else {
				$education_where[] = 'school_id = ' . $this->search['school_name']['id'];
			}


		}

		// if ( $this->key_exists( 'education_level' ) && ! is_null( $this->search['education_level'] ) ) {
		// 	$education_where[] = 'school_education_level = ' . $this->search['education_level'];
		// }
		

		// Education Levels
		if ( $this->key_exists( 'education_level' ) ) {
			if ( count( $this->search['education_level'] ) > 0 ) {
				$education_level_ids = array_column( array_filter( $this->search['education_level'], function ( $var ) {
					if ( $var['id'] !== null ) {
						return true;
					}
				} ), 'id' );

				$education_level_name = array_column( array_filter( $this->search['education_level'], function ( $var ) {
					if ( $var['id'] === null && strlen($var['name']) > 0 ) {
						return true;
					}
				} ), 'name' );

				if ( count( $education_level_ids ) ) {
					if ( count( $education_level_name ) ) {
						$education_where[] = '(school_education_level IN (' . implode( ',', $education_level_ids ) . ') OR ' . "school_education_level IN (SELECT education_level_id FROM education_levels WHERE education_level_name LIKE '" . $education_level_name[0] . "%'))";
					} else {
						$education_where[] = 'school_education_level IN (' . implode( ',', $education_level_ids ) . ')';
					}
				} else if ( count( $education_level_ids ) ) {
					$education_where[] = "education_level_id IN (SELECT education_level_id FROM education_levels WHERE education_level_name LIKE '" . $education_level_name[0] . "%' AND deleted = 0)";
				}
			}
		}

		// Location
		if ( $this->key_exists( 'location' ) ) {
			$location_join = '';
			if ( isset( $this->search['location']['city_id'] ) && ! is_null( $this->search['location']['city_id'] ) ) {
				$location_where[] = 'city_id = ' . $this->search['location']['city_id'];
			} else if ( isset( $this->search['location']['state_id'] ) && ! is_null( $this->search['location']['state_id'] ) ) {
				$location_where[] = 'states.state_id = ' . $this->search['location']['state_id'];
				$location_join    = '
					INNER JOIN cities ON cities.city_id = lius.city_id
					INNER JOIN states ON states.state_id = cities.state_id
				';
			} else if ( isset( $this->search['location']['country_id'] ) && ! is_null( $this->search['location']['country_id'] ) ) {
				$location_where[] = 'countries.country_id = ' . $this->search['location']['country_id'];
				$location_join    = '
					INNER JOIN cities ON cities.city_id = lius.city_id
                    INNER JOIN countries ON cities.country_id = countries.country_id
				';
			} else if ( isset( $this->search['location']['continent_id'] ) && ! is_null( $this->search['location']['continent_id'] ) ) {
				$location_where[] = 'continents.continent_id = ' . $this->search['location']['continent_id'];
				$location_join    = '
					INNER JOIN cities ON cities.city_id = lius.city_id
                    INNER JOIN countries ON cities.country_id = countries.country_id
                    INNER JOIN continents ON countries.continent_id = continents.continent_id
				';
			}
		}

		// User profile
		if ( $this->key_exists( 'updated' ) && ! is_null( $this->search['updated'] ) ) {
			$profile_where[] = 'DATEDIFF(NOW(), updated) < ' . $this->search['updated'];
		}

		// Start by selecting the users
		$sql .= 'INNER JOIN ( ';
		$sql .= 'SELECT user_id, profile_firstname, profile_lastname, city_id,profile_resume, updated FROM profiles pr';
		if ( count( $profile_where ) > 0 ) {
			$sql .= ' WHERE ' . implode( ' AND ', $profile_where );
		}
		$sql .= ') pr ON pr.user_id = u.user_id ';

		if ( $this->is_join_positions() ) {
			$select = array_merge( $select, array(
				'(SELECT
						TRUNCATE(SUM(
							(UNIX_TIMESTAMP(IFNULL(pou.position_to, NOW())) - UNIX_TIMESTAMP(pou.position_from)) / 31556926
						), 1)
					FROM positions_of_users pou WHERE u.user_id = pou.user_id AND pou.deleted = 0) as total_experience',
				'jt.job_title_id as job_title_id',
				'jt.job_title_name as job_title_name',
				'sn.seniority_name as seniority_name',
				'sn.seniority_id as seniority_id',
				'pu.positions_of_users_id as position_id'
			) );

			// INNER JOIN the last position which correspond the search criteria
			$sql .= 'INNER JOIN positions_of_users pu
        		ON u.user_id = pu.user_id AND deleted = 0
    			INNER JOIN
    			(
			        SELECT user_id, MAX(position_from) position_from
			        FROM positions_of_users';

			$position_where[] = 'deleted = 0';

			// Position related
			if ( count( $position_where ) > 0 ) {
				$sql .= ' WHERE ' . implode( ' AND ', $position_where );
			}

			$sql .= ' GROUP BY user_id) b ON pu.user_id = b.user_id AND pu.position_from = b.position_from ';

			// Job title - Mandatory but may be un approved
			$sql .= 'LEFT JOIN (
					SELECT job_title_id, job_title_name
					FROM job_title WHERE deleted = 0';
			$sql .= ') jt ON jt.job_title_id = pu.job_title_id ';

			// Seniority
			$sql .= 'LEFT JOIN (
					SELECT seniority_name, seniority_id
					FROM seniorities WHERE deleted = 0';
			$sql .= ') sn ON sn.seniority_id = pu.seniority_id ';

			// Areas of focus
			if ( count( $area_of_focus_where ) > 0 ) {
				$area_of_focus_where[] = 'deleted = 0';
				$sql                   .= 'INNER JOIN (
					SELECT position_of_users_id, area_of_focus_id
					FROM areas_of_focus_of_positions_of_users
					WHERE ' . implode( ' AND ', $area_of_focus_where );
				$sql                   .= ') aof ON aof.position_of_users_id = pu.positions_of_users_id ';
			}
		} else {
			// Connection to the positions table is not required and a position representation may be missing
			$select = array_merge( $select, array(
				'(SELECT
						TRUNCATE(SUM(
							(UNIX_TIMESTAMP(IFNULL(pou.position_to, NOW())) - UNIX_TIMESTAMP(pou.position_from)) / 31556926
						), 1)
					FROM positions_of_users pou WHERE u.user_id = pou.user_id AND pou.deleted = 0) as total_experience',
				'jt.job_title_id as job_title_id',
				'jt.job_title_name as job_title_name',
				'sn.seniority_name as seniority_name',
				'sn.seniority_id as seniority_id',
			) );

			// LEFT JOIN the last position which correspond the search criteria
			$sql .= 'LEFT JOIN positions_of_users pu
        		ON u.user_id = pu.user_id AND deleted = 0
    			INNER JOIN
    			(
			        SELECT user_id, MAX(position_from) position_from
			        FROM positions_of_users';

			$position_where[] = 'deleted = 0';

			// Position related
			if ( count( $position_where ) > 0 ) {
				$sql .= ' WHERE ' . implode( ' AND ', $position_where );
			}
			$sql .= ' GROUP BY user_id) b ON pu.user_id = b.user_id AND pu.position_from = b.position_from ';

			// Job title (Mandatory)
			$sql .= 'LEFT JOIN (
					SELECT job_title_id, job_title_name
					FROM job_title WHERE deleted = 0';
			$sql .= ') jt ON jt.job_title_id = pu.job_title_id ';

			// Seniority
			$sql .= 'LEFT JOIN (
					SELECT seniority_name, seniority_id
					FROM seniorities WHERE deleted = 0';
			$sql .= ') sn ON sn.seniority_id = pu.seniority_id ';

			// Areas of focus
			if ( count( $area_of_focus_where ) > 0 ) {
				$area_of_focus_where = [ 'deleted = 0' ];
				$sql                 .= 'INNER JOIN (
					SELECT position_of_users_id, area_of_focus_id
					FROM areas_of_focus_of_positions_of_users
					WHERE ' . implode( ' AND ', $area_of_focus_where );
				$sql                 .= ') aof ON aof.position_of_users_id = pu.positions_of_users_id ';
			}
		}

		// Status and preferences / location of interest based selection
		if ( count( $status_where ) > 0 ) {
			$select = array_merge( $select, array(
				'st.user_status_desired_salary as user_status_desired_salary',
				'st.user_status_desired_salary_period as user_status_desired_salary_period',
				'st.user_block_companies as user_block_companies',
				'st.user_status_current as user_status_current',
				'(CASE 
					WHEN st.user_status_desired_salary_period = "M" THEN st.user_status_desired_salary / 4
					WHEN st.user_status_desired_salary_period = "Y" THEN st.user_status_desired_salary / 48
					WHEN st.user_status_desired_salary_period = "W" THEN st.user_status_desired_salary
					WHEN st.user_status_desired_salary_period = "H" THEN st.user_status_desired_salary * 168
					ELSE NULL
				END) as user_status_desired_salary_in_weeks',
				'business_applicant_status.status as applicant_status'
			) );

			$sql .= 'INNER JOIN (
					SELECT user_status_id, user_id, user_status_employment_type, user_status_desired_salary, user_status_desired_salary_period, user_status_employment_status, user_status_current, user_status_relocation, user_status_benefits, user_status_legal_usa, user_block_companies
					FROM user_status ';

			if ( count( $status_where ) > 0 ) {
				$sql .= ' WHERE ' . implode( ' AND ', $status_where );
			}

			$sql .= ') st ON st.user_id = u.user_id ';
		} else {
			$select = array_merge( $select, array(
				'st.user_status_desired_salary as user_status_desired_salary',
				'st.user_status_desired_salary_period as user_status_desired_salary_period',
				'st.user_block_companies as user_block_companies',
				'st.user_status_current as user_status_current',
				'(CASE 
					WHEN st.user_status_desired_salary_period = "M" THEN st.user_status_desired_salary / 4
					WHEN st.user_status_desired_salary_period = "Y" THEN st.user_status_desired_salary / 48
					WHEN st.user_status_desired_salary_period = "W" THEN st.user_status_desired_salary
					WHEN st.user_status_desired_salary_period = "H" THEN st.user_status_desired_salary * 168
					ELSE NULL
				END) as user_status_desired_salary_in_weeks',
				'business_applicant_status.status as applicant_status'
			) );

			$sql .= 'LEFT JOIN (
					SELECT user_status_id, user_id, user_status_employment_type, user_status_desired_salary, user_status_desired_salary_period, user_status_employment_status, user_status_current, user_status_relocation, user_status_benefits, user_status_legal_usa, user_block_companies
					FROM user_status ';

			if ( count( $status_where ) > 0 ) {
				$sql .= ' WHERE ' . implode( ' AND ', $status_where );
			}

			$sql .= ') st ON st.user_id = u.user_id ';
		}

		$sql .= 'LEFT JOIN business_applicant_status ON business_applicant_status.user_id = u.user_id AND business_applicant_status.business_id = ' . $this->business_id . ' ';

		// Location
		if ( count( $location_where ) > 0 ) {
			$location_where[] = 'lius.deleted = 0';
			// Extending the selection to include a location model
			$select = array_merge( $select, array(
				'location_cities.city_id as location_city_id',
				'location_cities.city_name as location_city_name',
				'location_states.state_id as location_state_id',
				'location_states.state_name as location_state_name',
				'location_states.state_short_name as location_state_short_name',
				'location_countries.country_id as location_country_id',
				'location_countries.country_name as location_country_name',
				'location_countries.country_short_name_alpha_2 as location_country_short_name_alpha_2',
				'location_countries.country_short_name_alpha_3 as location_country_short_name_alpha_3',
				'location_continents.continent_id as location_continent_id',
				'location_continents.continent_name as location_continent_name',
			) );

			$location_select = array(
				'lius.city_id',
				'lius.user_status_id'
			);
			$location_select = implode( ', ', $location_select );
			$_location_where = implode( ' AND ', $location_where ) . ' LIMIT 1';

			$sql .= 'LEFT JOIN (
					SELECT ' . $location_select . '
					FROM locations_of_interest_of_user_status as lius
					' . $location_join;

			$sql .= ' WHERE ' . $_location_where;
			$sql .= ') lius ON lius.user_status_id = st.user_status_id ';
			$sql .= 'LEFT JOIN cities as location_cities ON location_cities.city_id = lius.city_id ';
			$sql .= 'LEFT JOIN states as location_states ON location_cities.state_id = location_states.state_id ';
			$sql .= 'LEFT JOIN countries as location_countries ON location_cities.country_id = location_countries.country_id ';
			$sql .= 'LEFT JOIN continents as location_continents ON location_countries.continent_id = location_continents.continent_id ';
		}

		$select = array_merge( $select, array(
			'profile_location_cities.city_id as profile_location_city_id',
			'profile_location_cities.city_name as profile_location_city_name',
			'profile_location_states.state_id as profile_location_state_id',
			'profile_location_states.state_name as profile_location_state_name',
			'profile_location_states.state_short_name as profile_location_state_short_name',
			'profile_location_countries.country_id as profile_location_country_id',
			'profile_location_countries.country_name as profile_location_country_name',
			'profile_location_countries.country_short_name_alpha_2 as profile_location_country_short_name_alpha_2',
			'profile_location_countries.country_short_name_alpha_3 as profile_location_country_short_name_alpha_3',
			'profile_location_continents.continent_id as profile_location_continent_id',
			'profile_location_continents.continent_name as profile_location_continent_name',
		) );

		$sql .= 'LEFT JOIN cities as profile_location_cities ON profile_location_cities.city_id = pr.city_id ';
		$sql .= 'LEFT JOIN states as profile_location_states ON profile_location_cities.state_id = profile_location_states.state_id ';
		$sql .= 'LEFT JOIN countries as profile_location_countries ON profile_location_cities.country_id = profile_location_countries.country_id ';
		$sql .= 'LEFT JOIN continents as profile_location_continents ON profile_location_countries.continent_id = profile_location_continents.continent_id ';

		// Languages
		if ( count( $languages_where ) > 0 ) {
			$sql               .= 'INNER JOIN (
				SELECT lu.user_id, lu.language_id, lu.language_level
				FROM languages_of_users lu '. implode( ' ', $languages_where ) .
				" WHERE lu.deleted = 0 AND " . implode( ' AND ', $where_languages ) .
				" GROUP BY lu.user_id";
			$sql               .= ') lng ON lng.user_id = u.user_id ';
		}

		// Traits
		if ( count( $traits_where ) > 0 ) {
			$traits_where[] = 'deleted = 0';
			$sql            .= 'INNER JOIN (
					SELECT user_id, trait_id, trait_prominance
					FROM traits_of_users
					WHERE ' . implode( ' AND ', $traits_where );
			$sql            .= ') tr ON tr.user_id = u.user_id ';
		}

		// Technical abilities
		if ( count( $tech_skills_where ) > 0 ) {
			$tech_skills_where[] = 'deleted = 0';
			$sql                 .= 'INNER JOIN (
					SELECT user_id, technical_ability_id, technical_ability_level
					FROM technical_abilities_of_users
					WHERE ' . implode( ' AND ', $tech_skills_where );
			$sql                 .= ') ts ON ts.user_id = u.user_id ';
		}

		// Education
		if ( count( $education_where ) > 0 ) {
			$education_where[] = 'deleted = 0';
			$sql               .= 'INNER JOIN (
					SELECT user_id, school_id, school_education_level
					FROM schools_of_users
					WHERE ' . implode( ' AND ', $education_where );
			$sql               .= ') edu ON edu.user_id = u.user_id ';
		}

		// Purchased & Applied
		$select = array_merge( $select, array(
			'apr.purchased',
			'app.applied',
			'appunq.applied_unique',
			'bl.blocked_count',
			'al.allowed_count'
		) );

		// Blocked
		$sql .= 'LEFT JOIN (
				SELECT user_id, business_id, COUNT(user_id) as blocked_count
				FROM blocked_businesses
				WHERE deleted = 0 AND business_id = ' . $this->business_id;
		$sql .= ') bl ON bl.user_id = u.user_id AND st.user_block_companies = 0 ';

		// Allowed
		$sql .= 'LEFT JOIN (
				SELECT user_id, business_id, COUNT(user_id) as allowed_count
				FROM allowed_businesses
				WHERE deleted = 0 AND business_id = ' . $this->business_id;
		$sql .= ') al ON al.user_id = u.user_id AND st.user_block_companies = 1 ';

		// Purchased
		$sql .= 'LEFT JOIN ( ';
		$sql .= 'SELECT business_user_purchase.business_id, business_user_purchase.user_id, COUNT(business_user_purchase.user_id) AS purchased
                FROM business_user_purchase
                WHERE business_user_purchase.business_id = ' . $this->business_id . ' AND deleted = 0
				GROUP BY business_user_purchase.business_id, business_user_purchase.user_id';
		$sql .= ') apr ON apr.user_id = u.user_id AND apr.business_id = ' . $this->business_id . ' ';

		// Applied
		$sql .= 'LEFT JOIN ( ';
		$sql .= 'SELECT applicants_of_business.business_id, applicants_of_business.user_id, COUNT(applicants_of_business.user_id) AS applied
                FROM applicants_of_business
                WHERE applicants_of_business.business_id = ' . $this->business_id . ' AND deleted = 0
                GROUP BY applicants_of_business.business_id, applicants_of_business.user_id';
		$sql .= ') app ON app.user_id = u.user_id AND app.business_id = ' . $this->business_id . ' ';

		// Applied Uniquely For Other
		$sql .= 'LEFT JOIN ( ';
		$sql .= 'SELECT applicants_of_business.business_id, applicants_of_business.user_id, COUNT(applicants_of_business.user_id) AS applied_unique
                FROM applicants_of_business
                WHERE applicants_of_business.business_id != ' . $this->business_id . ' AND deleted = 0 AND applicants_of_business.extended_expire = 1 AND applicants_of_business.expire >= now()
                GROUP BY applicants_of_business.business_id, applicants_of_business.user_id';
		$sql .= ') appunq ON appunq.user_id = u.user_id';

		
		// 
		// get resume 
			$select = array_merge( $select, array('fli.file_url as resume_url','fli.file_id as resume_id') );
			$sql .= ' LEFT JOIN files as fli ON fli.file_id = pr.profile_resume ';

		// end get resume 
		// Disable the sql_mode only_full_group_by
		$this->db->simple_query( "SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'" );
		$final_query = 'SELECT SQL_CALC_FOUND_ROWS ' . implode( ", ", $select ) . ' FROM users u ' . $sql;

		// General query conditions - Location
		// Select location from the locations of interest or from the user's profile
		if ( $this->key_exists( 'location' ) ) {
			if ( isset( $this->search['location']['city_id'] ) && ! is_null( $this->search['location']['city_id'] ) ) {
				$__location_where[] = 'location_cities.city_id = ' . $this->search['location']['city_id'] . ' OR ' . 'profile_location_cities.city_id = ' . $this->search['location']['city_id'];
			} else if ( isset( $this->search['location']['state_id'] ) && ! is_null( $this->search['location']['state_id'] ) ) {
				$__location_where[] = 'location_states.state_id = ' . $this->search['location']['state_id'] . ' OR ' . 'profile_location_states.state_id = ' . $this->search['location']['state_id'];
			} else if ( isset( $this->search['location']['country_id'] ) && ! is_null( $this->search['location']['country_id'] ) ) {
				$__location_where[] = 'location_countries.country_id = ' . $this->search['location']['country_id'] . ' OR ' . 'profile_location_countries.country_id = ' . $this->search['location']['country_id'];
			} else if ( isset( $this->search['location']['continent_id'] ) && ! is_null( $this->search['location']['continent_id'] ) ) {
				$__location_where[] = 'location_continents.continent_id = ' . $this->search['location']['continent_id'] . ' OR ' . 'profile_location_continents.continent_id = ' . $this->search['location']['continent_id'];
			}
		}

		$where = ' WHERE';
		

		
		if ( count( $location_where ) > 0 ) {
			$final_query .= $where . ' (' . implode( ' AND ', $__location_where ) . ')';
			$where       = ' AND';
		}

		if ( $this->key_exists( 'account_id' ) && ! is_null( $this->search['account_id'] ) ) {
			$final_query .= $where . ' u.user_id = ' . $this->search['account_id'];
			$where       = ' AND';
		}

		if ( $this->key_exists( 'status' ) && ! is_null( $this->search['status'] ) ) {
			if ( $this->search['status'] == 'my candidates' ) {
				$this->set_flags( 'BusinessApplicantsOnly', true );
			} else {
				$final_query .= $where . ' business_applicant_status.status = "' . $this->search['status'] . '"';
				$where       = ' AND';

			}
		}

		// Additional general conditions:
		// Don't show in the results the user which requested them
		$final_query .= $where . ' u.user_id != ' . $this->user_id;
		$where       = ' AND';
		
		if ( $this->key_exists( 'user_type' ) && ! is_null( $this->search['user_type'] ) && $this->search['user_type'] == 'uploaded candidates') {
			if($user_ids){
				$final_query .= $where . ' u.creator_id IN (' . $user_ids.')';
			}else{
				$final_query .= $where . ' u.creator_id IN (' . $this->user_id.')';
			}
			
			
			$where       = ' AND';
			
			if ( $this->key_exists( 'uploaded_date' ) && ! is_null( $this->search['uploaded_date'] )) {
				$final_query .= $where . ' u.upload_id = ' .$this->search['uploaded_date'];
				$where       = ' AND';
			}
			$final_query .= $where .' u.user_id IN (SELECT MAX(user_id) FROM users where status = "active"  GROUP BY email)';
		}else{
			$final_query .= $where .' u.user_id IN (SELECT MAX(user_id) FROM users where status = "active" and market_place=1 GROUP BY email)';
			$final_query .= $where .' u.market_place=1';
			//$final_query .= $where . ' u.creator_id != ' . $this->user_id;
		}
		if ( $this->key_exists( 'user_name' ) && ! is_null( $this->search['user_name'] )) {
				$final_query .= $where . '((pr.profile_firstname like  "'.trim($this->search["user_name"]).'%" ) or (pr.profile_lastname like "'.trim($this->search["user_name"]).'%" ) )';
				$where       = ' AND';
		}
		// Show only active users (which verified their email)
		$final_query .= $where . ' (u.status = "active" OR (u.status = "pending" AND app.applied > 0))';

		// Show only users which are visible to search results
		$final_query .= $where . ' u.search_visible = 1';

		// Show only users which are not uniquely applied to other
		$final_query .= $where . ' appunq.applied_unique IS NULL';

		// Search flags
		$parsed_flags = $this->parse_flags();
		if ( ! empty( $parsed_flags ) ) {
			$final_query .= $where . $parsed_flags;
		}

		// Show only allowed / Hide blocked
		$final_query .= $where . ' ((st.user_block_companies = 0 AND (bl.blocked_count = 0 OR bl.blocked_count IS NULL)) OR (st.user_block_companies = 1 AND al.allowed_count = 1))';

		// Show only users who are actively looking or interested in offers
		$final_query .= $where . ' st.user_status_current != "not looking"';

		// Fix for the position where statement (using HAVING instead) for the experience range
		if ( ! is_null( $orderby ) ) {
			$final_query .= ' GROUP BY id ';
		}
		$having = ' HAVING';
		if ( $this->key_exists( 'experience_from' ) && ! is_null( $this->search['experience_from'] ) ) {
			$final_query .= $having . ' total_experience >= ' . $this->search['experience_from'];
			$having      = ' AND';
		}
		if ( $this->key_exists( 'experience_to' ) && ! is_null( $this->search['experience_to'] ) ) {
			$final_query .= $having . ' total_experience <= ' . $this->search['experience_to'];
		}


		// orderby & order support
		if ( ! is_null( $orderby ) ) {
			$final_query .= ' ORDER BY ' . $orderby . ' ' . $order.', id desc';
		}

		$final_query .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

		//exit($final_query);

		$query = $this->db->query( $final_query );

		$response['applicants'] = $query->result();
		//echo '<pre>'; print_r($response['applicants']); die;
		if ( $query->num_rows() > 0 ) {
			$count_result      = $this->db->query( "SELECT FOUND_ROWS() as total" );
			$response['total'] = $count_result->row()->total;
		} else {
			$response['total'] = 0;
		}

		return $response;
	}

	/**
	 * get_single_applicant
	 *
	 * Get the search profile of a single applicant
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_single_applicant() {
		$sql         = '';
		$final_query = '';

		$response = [
			'total'      => 0,
			'applicants' => []
		];

		// Position based filters - first order selection
		$select = array(
			'DISTINCT (u.user_id) as id',
			'u.status as user_status',
			'u.role as user_role',
			'u.market_place as market_place',
			'u.search_visible as user_visible',
			'pr.profile_firstname as firstname',
			'pr.profile_lastname as lastname',
			'(SELECT TRUNCATE(SUM(
							(UNIX_TIMESTAMP(IFNULL(pou.position_to, NOW())) - UNIX_TIMESTAMP(pou.position_from)) / 31556926
						), 1)
					FROM positions_of_users pou WHERE u.user_id = pou.user_id AND pou.deleted = 0) as total_experience',
			'jt.job_title_id as job_title_id',
			'jt.job_title_name as job_title_name',
			'sn.seniority_name as seniority_name',
			'sn.seniority_id as seniority_id',
		);

		// Start by selecting the users
		$sql .= 'INNER JOIN ( ';
		$sql .= 'SELECT user_id, profile_firstname, profile_lastname, city_id,profile_resume, updated FROM profiles pr';
		$sql .= ') pr ON pr.user_id = u.user_id ';

		// LEFT JOIN the last position which correspond the search criteria
		$sql .= 'LEFT JOIN positions_of_users pu
        		ON u.user_id = pu.user_id
    			INNER JOIN
    			(
			        SELECT user_id,  positions_of_users_id
			        FROM positions_of_users WHERE deleted = 0 order by position_from asc';
		$sql .= ') b ON pu.user_id = b.user_id AND pu.positions_of_users_id = b.positions_of_users_id ';

		// Job title (Mandatory)
		$sql .= 'LEFT JOIN (
					SELECT job_title_id, job_title_name
					FROM job_title WHERE deleted = 0';
		$sql .= ') jt ON jt.job_title_id = pu.job_title_id ';

		// Seniority
		$sql .= 'LEFT JOIN (
					SELECT seniority_name, seniority_id
					FROM seniorities WHERE deleted = 0';
		$sql .= ') sn ON sn.seniority_id = pu.seniority_id ';

		// Status
		$select = array_merge( $select, array(
			'st.user_status_desired_salary as user_status_desired_salary',
			'st.user_status_desired_salary_period as user_status_desired_salary_period',
			'st.user_block_companies as user_block_companies',
			'st.user_status_current as user_status_current',
			'(CASE 
					WHEN st.user_status_desired_salary_period = "M" THEN st.user_status_desired_salary / 4
					WHEN st.user_status_desired_salary_period = "Y" THEN st.user_status_desired_salary / 48
					WHEN st.user_status_desired_salary_period = "W" THEN st.user_status_desired_salary
					WHEN st.user_status_desired_salary_period = "H" THEN st.user_status_desired_salary * 168
					ELSE NULL
				END) as user_status_desired_salary_in_weeks',
			'business_applicant_status.status as applicant_status'
		) );

		$sql .= 'LEFT JOIN (
					SELECT user_status_id, user_id, user_status_employment_type, user_status_desired_salary, user_status_desired_salary_period, user_status_employment_status, user_status_current, user_status_relocation, user_status_benefits, user_status_legal_usa, user_block_companies
					FROM user_status ';
		$sql .= ') st ON st.user_id = u.user_id ';
		$sql .= 'LEFT JOIN business_applicant_status ON business_applicant_status.user_id = u.user_id AND business_applicant_status.business_id = ' . $this->business_id . ' ';

		// Location
		$select = array_merge( $select, array(
			'location_cities.city_id as location_city_id',
			'location_cities.city_name as location_city_name',
			'location_states.state_id as location_state_id',
			'location_states.state_name as location_state_name',
			'location_states.state_short_name as location_state_short_name',
			'location_countries.country_id as location_country_id',
			'location_countries.country_name as location_country_name',
			'location_countries.country_short_name_alpha_2 as location_country_short_name_alpha_2',
			'location_countries.country_short_name_alpha_3 as location_country_short_name_alpha_3',
			'location_continents.continent_id as location_continent_id',
			'location_continents.continent_name as location_continent_name',
		) );

		$location_join = '
			INNER JOIN cities ON cities.city_id = lius.city_id
			INNER JOIN states ON states.state_id = cities.city_id
            INNER JOIN countries ON cities.country_id = countries.country_id
            INNER JOIN continents ON countries.continent_id = continents.continent_id
		';

		$location_select = array(
			'lius.city_id',
			'lius.user_status_id'
		);

		$location_select = implode( ', ', $location_select );

		$sql .= 'LEFT JOIN (
					SELECT ' . $location_select . '
					FROM locations_of_interest_of_user_status as lius
					' . $location_join;

		$sql .= ' WHERE 1 LIMIT 1';
		$sql .= ') lius ON lius.user_status_id = st.user_status_id ';
		$sql .= 'LEFT JOIN cities as location_cities ON location_cities.city_id = lius.city_id ';
		$sql .= 'LEFT JOIN states as location_states ON location_cities.state_id = location_states.state_id ';
		$sql .= 'LEFT JOIN countries as location_countries ON location_cities.country_id = location_countries.country_id ';
		$sql .= 'LEFT JOIN continents as location_continents ON location_countries.continent_id = location_continents.continent_id ';

		$select = array_merge( $select, array(
			'profile_location_cities.city_id as profile_location_city_id',
			'profile_location_cities.city_name as profile_location_city_name',
			'profile_location_states.state_id as profile_location_state_id',
			'profile_location_states.state_name as profile_location_state_name',
			'profile_location_states.state_short_name as profile_location_state_short_name',
			'profile_location_countries.country_id as profile_location_country_id',
			'profile_location_countries.country_name as profile_location_country_name',
			'profile_location_countries.country_short_name_alpha_2 as profile_location_country_short_name_alpha_2',
			'profile_location_countries.country_short_name_alpha_3 as profile_location_country_short_name_alpha_3',
			'profile_location_continents.continent_id as profile_location_continent_id',
			'profile_location_continents.continent_name as profile_location_continent_name',
		) );

		$sql .= 'LEFT JOIN cities as profile_location_cities ON profile_location_cities.city_id = pr.city_id ';
		$sql .= 'LEFT JOIN states as profile_location_states ON profile_location_cities.state_id = profile_location_states.state_id ';
		$sql .= 'LEFT JOIN countries as profile_location_countries ON profile_location_cities.country_id = profile_location_countries.country_id ';
		$sql .= 'LEFT JOIN continents as profile_location_continents ON profile_location_countries.continent_id = profile_location_continents.continent_id ';

		// Purchased & Applied
		$select = array_merge( $select, array(
			'apr.purchased',
			'app.applied',
			'appunq.applied_unique',
			'bl.blocked_count',
			'al.allowed_count'
		) );

		// Blocked
		$sql .= 'LEFT JOIN (
				SELECT user_id, business_id, COUNT(user_id) as blocked_count
				FROM blocked_businesses
				WHERE deleted = 0 AND business_id = ' . $this->business_id;
		$sql .= ') bl ON bl.user_id = u.user_id AND st.user_block_companies = 0 ';

		// Allowed
		$sql .= 'LEFT JOIN (
				SELECT user_id, business_id, COUNT(user_id) as allowed_count
				FROM allowed_businesses
				WHERE deleted = 0 AND business_id = ' . $this->business_id;
		$sql .= ') al ON al.user_id = u.user_id AND st.user_block_companies = 1 ';

		// Purchased
		$sql .= 'LEFT JOIN ( ';
		$sql .= 'SELECT business_user_purchase.business_id, business_user_purchase.user_id, COUNT(business_user_purchase.user_id) AS purchased
                FROM business_user_purchase
                WHERE business_user_purchase.business_id = ' . $this->business_id . ' AND deleted = 0
				GROUP BY business_user_purchase.business_id, business_user_purchase.user_id';
		$sql .= ') apr ON apr.user_id = u.user_id AND apr.business_id = ' . $this->business_id . ' ';

		// Applied
		$sql .= 'LEFT JOIN ( ';
		$sql .= 'SELECT applicants_of_business.business_id, applicants_of_business.user_id, COUNT(applicants_of_business.user_id) AS applied
                FROM applicants_of_business
                WHERE applicants_of_business.business_id = ' . $this->business_id . ' AND deleted = 0
                GROUP BY applicants_of_business.business_id, applicants_of_business.user_id';
		$sql .= ') app ON app.user_id = u.user_id AND app.business_id = ' . $this->business_id . ' ';

		// Applied Uniquely For Other
		$sql .= 'LEFT JOIN ( ';
		$sql .= 'SELECT applicants_of_business.business_id, applicants_of_business.user_id, COUNT(applicants_of_business.user_id) AS applied_unique
                FROM applicants_of_business
                WHERE applicants_of_business.business_id != ' . $this->business_id . ' AND deleted = 0 AND applicants_of_business.extended_expire = 1 AND applicants_of_business.expire >= now()
                GROUP BY applicants_of_business.business_id, applicants_of_business.user_id';
		$sql .= ') appunq ON appunq.user_id = u.user_id';
		$select = array_merge( $select, array('fli.file_url as resume_url','fli.file_id as resume_id') );
		$sql .= ' LEFT JOIN files as fli ON fli.file_id = pr.profile_resume ';
		// Disable the sql_mode only_full_group_by
		$this->db->simple_query( "SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'" );
		$final_query = 'SELECT SQL_CALC_FOUND_ROWS ' . implode( ", ", $select ) . ' FROM users u ' . $sql;

		$where = ' WHERE';

		if ( $this->key_exists( 'account_id' ) && ! is_null( $this->search['account_id'] ) ) {
			$final_query .= $where . ' u.user_id = ' . $this->search['account_id'];
			$where       = ' AND';
		}

		// Additional general conditions:
		// Don't show in the results the user which requested them
		$final_query .= $where . ' u.user_id != ' . $this->user_id;
		$where       = ' AND';
		// Show only active users (which verified their email)
		if ( $this->key_exists( 'user_type' ) && ! is_null( $this->search['user_type'] ) && $this->search['user_type'] == 'uploaded candidates') {
			if($user_ids){
				$final_query .= $where . ' u.creator_id IN (' . $user_ids.')';
			}else{
				$final_query .= $where . ' u.creator_id IN (' . $this->user_id.')';
			}
			if ( $this->key_exists( 'uploaded_date' ) && ! is_null( $this->search['uploaded_date'] )) {
				$final_query .= $where . ' u.upload_id = ' . $this->search['uploaded_date'];
				$where       = ' AND';
			}
		}else{
			$final_query .= $where .' u.market_place=1';
			//$final_query .= $where . ' u.creator_id != ' . $this->user_id;
		}

		$final_query .= $where . ' u.status = "active"';
		// Show only users which are visible to search results
		$final_query .= $where . ' u.search_visible = 1';
		// Show only users which are not uniquely applied to other
		$final_query .= $where . ' appunq.applied_unique IS NULL';

		// Show only allowed / Hide blocked
		$final_query .= $where . ' ((st.user_block_companies = 0 AND (bl.blocked_count = 0 OR bl.blocked_count IS NULL)) OR (st.user_block_companies = 1 AND al.allowed_count = 1))';

		// Show only users who are actively looking or interested in offers
		$final_query .= $where . ' st.user_status_current != "not looking"';

		$final_query .= ' LIMIT 1';
		//echo $final_query; die;
		$query                  = $this->db->query( $final_query );
		$response['applicants'] = $query->result();

		$response['total'] = $query->num_rows();

		return $response;
	}

	/**
	 * determine_orderby
	 *
	 * Determine the search order by parameter from the requested orderby parameter
	 *
	 * @access    public
	 *
	 * @param string $orderby
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function determine_orderby( $orderby = null, $order = null ) {
		switch ( $orderby ) {
			case 'location':
				return 'city_name';
			case 'jobtitle':
				return 'job_title_name';
			case 'experience':
				return 'total_experience';
			case 'seniority':
				return 'seniority_name';
			case 'salary':
				if($order == 'asc') {
					return 'case WHEN -user_status_desired_salary_in_weeks IS NULL THEN 3 WHEN -user_status_desired_salary_in_weeks = 0 THEN 2 else 1 end, user_status_desired_salary_in_weeks';
				} else {
					return 'user_status_desired_salary_in_weeks';
				}
			default:
				if($order == 'asc') {
					return '-user_status_desired_salary_in_weeks';
				} else {
					return 'user_status_desired_salary_in_weeks';
				}
		}
	}

	/**
	 * determine_order
	 *
	 * Determine the search order by parameter from the requested order parameter
	 *
	 * @access    public
	 *
	 * @param string $order
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function determine_order( $orderby = null, $order = null ) {
		switch ( $orderby ) {
			case '-user_status_desired_salary_in_weeks':
				if ($order === 'asc') {
					return 'desc';
				} else {
					return 'asc';
				}
			default:
				return $order;
		}
	}

	/**
	 * parse_search_models
	 *
	 * Convert search model arrays of record ids into collection of objects with names and ids
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function parse_search_models() {
		// Job titles array
		if ( $this->key_exists( 'jobtitles' ) && count( $this->search['jobtitles'] ) > 0 && ! is_array( $this->search['jobtitles'][0] ) ) {
			$this->load->model( 'model_job_title' );

			$select = $this->model_job_title->get_dictionary_model();
			$select = implode( ',', $select );

			$this->db->select( $select );
			$this->db->from( $this->tbl_job_title );
			$this->db->where_in( $this->tbl_job_title . '.job_title_id', $this->search['jobtitles'] );
			$this->db->where( $this->tbl_job_title . '.deleted', 0 );
			$query = $this->db->get();

			$this->search['jobtitles'] = $query->result();
		}

		// Company ID
		if ( $this->key_exists( 'company_id' ) && ! is_null( $this->search['company_id'] ) ) {
			$this->load->model( 'model_companies' );

			$select = $this->model_companies->get_dictionary_model();
			$select = implode( ',', $select );

			$this->db->select( $select );
			$this->db->from( $this->tbl_companies );
			$this->db->where( $this->tbl_companies . '.company_id', $this->search['company_id'] );
			$this->db->where( $this->tbl_companies . '.deleted', 0 );
			$this->db->limit( 1 );

			$query = $this->db->get();

			$this->search['company_id'] = $query->row();
		}

		// Seniority
		if ( $this->key_exists( 'seniority' ) && ! is_null( $this->search['seniority'] ) ) {
			$this->load->model( 'model_seniorities' );

			$select = $this->model_seniorities->get_dictionary_model();
			$select = implode( ',', $select );

			$this->db->select( $select );
			$this->db->from( $this->tbl_seniorities );
			$this->db->where( $this->tbl_seniorities . '.seniority_id', $this->search['seniority'] );
//			$this->db->where( $this->tbl_seniorities . '.seniority_admin_approved', 1 );
			$this->db->where( $this->tbl_seniorities . '.deleted', 0 );
			$this->db->limit( 1 );

			$query = $this->db->get();

			$this->search['seniority'] = $query->row();
		}

		// Industry
		if ( $this->key_exists( 'industry' ) && ! is_null( $this->search['industry'] ) ) {
			$this->load->model( 'model_industries' );

			$select = $this->model_industries->get_dictionary_model();
			$select = implode( ',', $select );

			$this->db->select( $select );
			$this->db->from( $this->tbl_industries );
			$this->db->where( $this->tbl_industries . '.industry_id', $this->search['industry'] );
//			$this->db->where( $this->tbl_industries . '.industry_admin_approved', 1 );
			$this->db->where( $this->tbl_industries . '.deleted', 0 );
			$this->db->limit( 1 );

			$query = $this->db->get();

			$this->search['industry'] = $query->row();
		}

		// Areas of focus
		if ( $this->key_exists( 'areas_of_focus' ) && count( $this->search['areas_of_focus'] ) > 0 && ! is_array( $this->search['areas_of_focus'][0] ) ) {
			$this->load->model( 'model_areas_of_focus' );

			$select = $this->model_areas_of_focus->get_dictionary_model();
			$select = implode( ',', $select );

			$this->db->select( $select );
			$this->db->from( $this->tbl_areas_of_focus );
			$this->db->where_in( $this->tbl_areas_of_focus . '.area_of_focus_id', $this->search['areas_of_focus'] );
//			$this->db->where( $this->tbl_areas_of_focus . '.area_of_focus_admin_approved', 1 );
			$this->db->where( $this->tbl_areas_of_focus . '.deleted', 0 );
			$query = $this->db->get();

			$this->search['areas_of_focus'] = $query->result();
		}

		// Education level
		if ( $this->key_exists( 'education_level' ) && count( $this->search['education_level'] ) > 0 && ! is_array( $this->search['education_level'][0] ) ) {
			$this->load->model( 'model_education_levels' );

			$select = $this->model_education_levels->get_dictionary_model();
			$select = implode( ',', $select );

			$this->db->select( $select );
			$this->db->from( $this->tbl_education_levels );
			$this->db->where_in( $this->tbl_education_levels . '.education_level_id', $this->search['education_level'] );
			$this->db->where( $this->tbl_education_levels . '.deleted', 0 );
			// $this->db->limit( 1 );

			$query = $this->db->get();

			// $this->search['education_level'] = $query->row();
			$this->search['education_level'] = $query->result();
		}

		// School
		if ( $this->key_exists( 'school_name' ) && ! is_null( $this->search['school_name'] ) ) {
			$this->load->model( 'model_schools' );

			$select = $this->model_schools->get_dictionary_model();
			$select = implode( ',', $select );

			$this->db->select( $select );
			$this->db->from( $this->tbl_schools );
			$this->db->where( $this->tbl_schools . '.school_id', $this->search['school_name'] );
			$this->db->where( $this->tbl_schools . '.deleted', 0 );
			$this->db->limit( 1 );

			$query = $this->db->get();

			$this->search['school_name'] = $query->row();
		}

		// Languages
		if ( $this->key_exists( 'languages' ) && count( $this->search['languages'] ) > 0 && ! is_array( $this->search['languages'][0] ) ) {
			$this->load->model( 'model_languages' );

			$select = $this->model_languages->get_dictionary_model();
			$select = implode( ',', $select );

			$this->db->select( $select );
			$this->db->from( $this->tbl_languages );
			for ($i = 0; $i > count($this->search['languages']); $i++) {
				$this->db->where( $this->tbl_languages . '.language_id', $this->search['languages'][$i] );
			}
			$this->db->where( $this->tbl_languages . '.deleted', 0 );
			$query = $this->db->get();

			$this->search['languages'] = $query->result();
		}

		// Traits
		if ( $this->key_exists( 'traits' ) && count( $this->search['traits'] ) > 0 && ! is_array( $this->search['traits'][0] ) ) {
			$this->load->model( 'model_traits' );

			$select = $this->model_traits->get_dictionary_model();
			$select = implode( ',', $select );

			$this->db->select( $select );
			$this->db->from( $this->tbl_traits );
			$this->db->where_in( $this->tbl_traits . '.trait_id', $this->search['traits'] );
			$this->db->where( $this->tbl_traits . '.deleted', 0 );
			$query = $this->db->get();

			$this->search['traits'] = $query->result();
		}

		// Technical abilities (Tech skills)
		if ( $this->key_exists( 'technical_abillities' ) && count( $this->search['technical_abillities'] ) > 0 && ! is_array( $this->search['technical_abillities'][0] ) ) {
			$this->load->model( 'model_technical_abilities' );

			$select = $this->model_technical_abilities->get_dictionary_model();
			$select = implode( ',', $select );

			$this->db->select( $select );
			$this->db->from( $this->tbl_technical_abilities );
			$this->db->where_in( $this->tbl_technical_abilities . '.technical_ability_id', $this->search['technical_abillities'] );
//			$this->db->where( $this->tbl_technical_abilities . '.technical_ability_admin_approved', 1 );
			$this->db->where( $this->tbl_technical_abilities . '.deleted', 0 );
			$query = $this->db->get();

			$this->search['technical_abillities'] = $query->result();
		}
	}

	/**
	 * is_join_positions
	 *
	 * Determine if a connection to the positions table is mandatory
	 *
	 * @access    private
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	private function is_join_positions() {
		return
			( $this->key_exists( 'jobtitles' ) && count( $this->search['jobtitles'] ) > 0 ) ||
			( $this->key_exists( 'experience_from' ) && ! is_null( $this->search['experience_from'] ) ) ||
			( $this->key_exists( 'experience_to' ) && ! is_null( $this->search['experience_to'] ) ) ||
			( $this->key_exists( 'company_id' ) && ! is_null( $this->search['company_id'] ) ) ||
			( $this->key_exists( 'seniority' ) && ! is_null( $this->search['seniority'] ) ) ||
			( $this->key_exists( 'industry' ) && ! is_null( $this->search['industry'] ) ) ||
			( $this->key_exists( 'areas_of_focus' ) && count( $this->search['areas_of_focus'] ) > 0 );
	}

	/**
	 * key_exists
	 *
	 * Search for a presence of a given key in the search array
	 *
	 * @access    private
	 *
	 * @param mixed $key
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	private function key_exists( $key ) {
		return isset( $this->search[ $key ] ) ?: array_key_exists( $key, $this->search );
	}

	/**
	 * key_exists
	 *
	 * Search for a presence of a given key in the search array
	 *
	 * @access    private
	 *
	 * @params mixed $key
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	private function parse_flags() {
		$statement = '';

		if ( self::$BusinessApplicantsOnly ) {
			$statement = ' (applied = 1 OR purchased = 1)';
		} else if ( self::$NotBusinessApplicants ) {
			$statement = ' ((applied = 0 AND purchased = 0) OR (applied IS NULL AND purchased IS NULL))';
		}

		return $statement;
	}
}