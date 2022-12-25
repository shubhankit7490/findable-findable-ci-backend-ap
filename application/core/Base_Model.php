<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Base_Model extends CI_Model {
	protected $tbl_areas_of_focus = 'areas_of_focus'; // Dictionary of responsibilities
	protected $tbl_areas_of_focus_of_positions_of_users = 'areas_of_focus_of_positions_of_users'; // Responsibilities of user positions
	protected $tbl_applicants_of_business = 'applicants_of_business'; // Applicants which applied to a certain business
	protected $tbl_billing_information = 'billing_information'; // Billing information of a business
	protected $tbl_blocked_businesses = 'blocked_businesses'; // Businesses which were blocked by users (Blacklist)
	protected $tbl_allowed_businesses = 'allowed_businesses'; // Businesses which were allowed by users (Whitelist)
	protected $tbl_business = 'business'; // Businesses in the platform
	protected $tbl_business_unique_applicants_expire = 'business_unique_applicants_expire'; // Unique expire period for each business (Grace period)
	protected $tbl_business_users = 'business_users'; // Users of a business (Manager & Recruiters)
	protected $tbl_business_user_notes = 'business_user_notes'; // Notes given to an applicant by a business
	protected $tbl_business_user_purchase = 'business_user_purchase'; // Applicants which were purchased a certain business
	protected $tbl_business_applicant_status = 'business_applicant_status'; // Status given to an applicant by a business
	protected $tbl_business_reports = 'business_reports'; // Reports about applicants regarding gross information
	protected $tbl_business_user_views = 'business_user_views'; // Profile views by business
	protected $tbl_continents = 'continents'; // Location continents
	protected $tbl_companies = 'companies'; // Dictionary of companies
	protected $tbl_company_types = 'company_types'; // Dictionary of company types
	protected $tbl_countries = 'countries'; // Location countries
	protected $tbl_credits = 'credits'; // Credit balance of each business
	protected $tbl_education_levels = 'education_levels'; // Dictionary of education levels
	protected $tbl_fields_of_study = 'fields_of_study'; // Dictionary of study fields
	protected $tbl_fields_of_study_of_schools_of_users = 'fields_of_study_of_schools_of_users'; // Fields of study of an applicant's education record
	protected $tbl_files = 'files'; // Files uploaded by users
	protected $tbl_seniorities = 'seniorities'; // Dictionary of seniorities
	protected $tbl_help_messages_of_users = 'help_messages_of_users'; // Messages to the administrator
	protected $tbl_industries = 'industries'; // Dictionary of industries
	protected $tbl_invitations = 'invitations'; // Invitations
	protected $tbl_invitations_of_business = 'invitations_of_business'; // Invitations of recruiters to become business users
	protected $tbl_languages = 'languages'; // Dictionary of languages
	protected $tbl_languages_of_users = 'languages_of_users'; // Languages of users
	protected $tbl_locations_of_interest_of_user_status = 'locations_of_interest_of_user_status'; // Location of interest which were defined as part of the status and preferences
	protected $tbl_packages = 'packages'; // Packages of the platform
	protected $tbl_packages_of_business = 'packages_of_business'; // Packages which are unique for a business
	protected $tbl_payments = 'payments'; // Payments processed by Stripe
	protected $tbl_job_title = 'job_title'; // Dictionary of job titles
	protected $tbl_keys = 'keys'; // API keys of users (Used by the rest server)
	protected $tbl_positions_of_users = 'positions_of_users'; // Positions of users
	protected $tbl_profiles = 'profiles'; // Profiles of users
	protected $tbl_purchase_history = 'purchase_history'; // History purchase of each business
	protected $tbl_schools = 'schools'; // Dictionary of schools
	protected $tbl_schools_of_users = 'schools_of_users'; // Education records of users
	protected $tbl_searches = 'searches'; // Searches made by the platform users
	protected $tbl_searches_of_businesses = 'searches_of_businesses'; // Saved searches of a business user (Manager or Recruiter)
	protected $tbl_sharing_method_of_users = 'sharing_method_of_users'; // Sharing tokens required for a full profile share (Canceled feature)
	protected $tbl_states = 'states'; // Location states
	protected $tbl_sys_log = 'sys_log'; // General system log (Based on category, action, label)
	protected $tbl_technical_abilities = 'technical_abilities'; // Dictionary of technical skills
	protected $tbl_technical_abilities_of_users = 'technical_abilities_of_users'; // Technical skills of users
	protected $tbl_cities = 'cities'; // Location cities
	protected $tbl_tokens = 'tokens'; // Tokens of different types belong to users which want to perform a certain action which requires a temporary token (e.g. account activation)
	protected $tbl_traits = 'traits'; // Dictionary of traits
	protected $tbl_traits_of_users = 'traits_of_users'; // Traits of users
	protected $tbl_users = 'users'; // Platform users
	protected $tbl_user_requests = 'user_requests'; // Contact requests made to the user
	protected $tbl_users_views = 'users_views'; // Public profile views
	protected $tbl_user_status = 'user_status'; // Status and preferences of users
	protected $tbl_subscription = 'subscriptions';  // Subscriptions of applicants
	protected $tbl_candidate_upload = 'candidate_upload';  // Uploaded candidate by resume
	protected $tbl_business_partner = 'business_partner';  // Uploaded candidate by resume
	protected $allowed_dictionary_filters = array(
		'deleted'
	);

	protected $searchable_dictionary_filters = array(
		'name'
	);

	public function __construct() {
		parent::__construct();
		$this->load->helper( 'array' );
	}

	/**
	 * save
	 *
	 * Save the current model in the table which is defined in the public parameter tbl of the current model
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function save() {
		$this->db->where( $this->tbl . '.user_id', $this->user_id );

		unset( $this->updated );
		unset( $this->created );

		$this->db->set( $this );

		return $this->db->update( $this->tbl );
	}

	/**
	 * load_model
	 *
	 * Load the given model array or object to the current model according to the model definitions which are defined in the model's set_model method
	 *
	 * @access    public
	 *
	 * @params integer model
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function load_model( $model ) {
		if ( is_array( $model ) ) {
			$this->deep_merge( $this->array_to_object( $model ), $this->set_model() );
		} else if ( is_object( $model ) ) {
			$this->merge( $model );
		} else {
			return false;
		}
	}

	/**
	 * Merge the data record into the current model
	 *
	 * @params   mixed $merge
	 *
	 * @return  void
	 */
	public function merge( $merge ) {
		foreach ( $merge as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * deep_merge
	 *
	 * Merge the given object to the current model and use the values of the given values parameter
	 *
	 * @access    public
	 *
	 * @params integer merge
	 *
	 * @params integer values
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	protected function deep_merge( $merge, $values ) {
		foreach ( $merge as $key => $value ) {
			if ( in_array( $key, array_keys( $values ) ) ) {
				$this->{$values[$key]} = $value;
			}
		}
	}

	/**
	 * merge_fields
	 *
	 * @params   n -length arrays
	 *
	 * @return  array
	 */
	protected function merge_fields() {
		$args = func_get_args();
		$num  = func_num_args();
		for ( $i = 1; $i < $num; $i ++ ) {
			$args[0] = array_merge( (array) $args[0], (array) $args[ $i ] );
		}

		return $args[0];
	}

	/**
	 * array_to_object
	 *
	 * @params   $d
	 *
	 * @return  object
	 */
	protected function array_to_object( $d ) {
		return is_array( $d ) ? (object) array_map( __METHOD__, $d ) : $d;
	}

	/**
	 * object_to_array
	 *
	 * @params   $d
	 *
	 * @return  array
	 */
	protected function object_to_array( $d ) {
		return is_object( $d ) ? (array) array_map( __METHOD__, $d ) : $d;
	}
}