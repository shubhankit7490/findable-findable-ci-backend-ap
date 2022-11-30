<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Dictionary extends Base_Controller {
	protected static $dictionaries = [
		'areas_of_focus',
		'companies',
		'education_levels',
		'fields_of_study',
		'industries',
		'job_title',
		'languages',
		'schools',
		'seniorities',
		'technical_abilities',
		'traits',
		'enums',
		'business'
	];

	function __construct() {
		parent::__construct();
	}

	/**
	 * index_post
	 *
	 * Get dictionaries (with an optional filtering)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function index_post() {
		$valid        = true;
		$result       = array();
		$dictionaries = $this->request->body;

		if ( is_null( $dictionaries ) ) {
			$valid = false;
		}

		// Decoding the requested dictionaries JSON string
		$dictionaries = ( is_string( $dictionaries ) ) ? json_decode( $dictionaries) : $dictionaries;

		// Checking that the request contains at least one dictionary
		if ( ! count( $dictionaries ) ) {
			$valid = false;
		} else {
			// Extracting the names of the requested dictionaries
			$dictionary_names = array_map( create_function( '$o', 'return $o->name;' ), $dictionaries );

			// Checking if all the requested names are valid dictionaries
			$diff = array_diff( $dictionary_names, self::$dictionaries );

			if ( count( $diff ) > 0 ) {
				$valid = false;
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );

			foreach ( $dictionaries as &$dictionary ) {
				if ( property_exists( $dictionary, 'name' ) ) {
					$model = 'model_' . $dictionary->name;
					$this->load->model( $model );
					$this->model_dictionary->model = $this->$model;

					if ( ! property_exists( $dictionary, 'filter' ) ) {
						$dictionary->filter = "";
					}

					if ( ! property_exists( $dictionary, 'value' ) ) {
						$dictionary->value = "";
					}

					$result[] = (object) array(
						'name'   => $dictionary->name,
						'values' => $this->model_dictionary->get_dictionary( $dictionary->filter, $dictionary->value )
					);
				}
			}

			$this->response( $result, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * tech_get
	 *
	 * Get a collection of technical abillities matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function tech_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_technical_abilities' );
			$this->model_dictionary->model = $this->model_technical_abilities;
			$values = $this->model_dictionary->get_dictionary( 'name', $name );

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}


	/**
	 * tech_post
	 *
	 * Suggest a new dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function tech_post() {
		$valid          = true;
		$form_validated = true;

		$name = $this->post( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_technical_abilities' );
			$this->model_technical_abilities->technical_ability_name = ucfirst( $name );

			$this->model_technical_abilities->item_add();

			$this->response( [
				'id' => $this->model_technical_abilities->technical_ability_id
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * tech_put
	 *
	 * Approve a dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function tech_put( $id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_technical_abilities' );
			$this->model_technical_abilities->technical_ability_id             = $id;
			$this->model_technical_abilities->technical_ability_admin_approved = 1;
			if ( ! $this->model_technical_abilities->admin_approve() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Already approved'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * tech_delete
	 *
	 * Merge a dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function tech_delete( $id, $new_id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_technical_abilities_of_users' );
			$this->model_technical_abilities_of_users->technical_ability_id = $id;

			$this->load->model( 'model_technical_abilities' );
			$this->model_technical_abilities->technical_ability_id = $id;
			$this->model_technical_abilities->deleted              = 1;

			// Start a database transaction
			$this->db->trans_start();

			// Update the technical ability to all the users
			$this->model_technical_abilities_of_users->update_technical_ability_id( $new_id );

			// Mark the previous technical ability as deleted
			$this->model_technical_abilities->soft_delete();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * schools_get
	 *
	 * Get a collection of schools matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function schools_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_schools' );
			$this->model_dictionary->model = $this->model_schools;
			$values = $this->model_dictionary->get_dictionary( 'name', $name );

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * schools_put
	 *
	 * Approve a dictionary item
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function schools_put( $id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_schools' );
			$this->model_schools->school_id             = $id;
			$this->model_schools->school_admin_approved = 1;
			if ( ! $this->model_schools->admin_approve() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Already approved'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}


	/**
	 * schools_post
	 *
	 * Suggest a new dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function schools_post() {
		$valid          = true;
		$form_validated = true;

		$name = $this->post( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_schools' );
			$this->model_schools->school_name = ucfirst( $name );

			$this->model_schools->item_add();

			$this->response( [
				'id' => $this->model_schools->school_id
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * studyfields_get
	 *
	 * Get a collection of study fields matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function studyfields_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_fields_of_study' );
			$this->model_dictionary->model = $this->model_fields_of_study;
			$values = $this->model_dictionary->get_dictionary( 'name', $name );

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * studyfields_post
	 *
	 * Suggest a new dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function studyfields_post() {
		$valid          = true;
		$form_validated = true;

		$name = $this->post( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_fields_of_study' );
			$this->model_fields_of_study->fields_of_study_name = ucfirst( $name );

			$this->model_fields_of_study->item_add();

			$this->response( [
				'id' => $this->model_fields_of_study->fields_of_study_id
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * studyfields_put
	 *
	 * Approve a dictionary item
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function studyfields_put( $id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_fields_of_study' );
			$this->model_fields_of_study->fields_of_study_id             = $id;
			$this->model_fields_of_study->fields_of_study_admin_approved = 1;
			if ( ! $this->model_fields_of_study->admin_approve() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Already approved'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * studyfields_delete
	 *
	 * Merge a dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function studyfields_delete( $id, $new_id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_fields_of_study_of_schools_of_users' );
			$this->model_fields_of_study_of_schools_of_users->fields_of_study_id = $id;

			$this->load->model( 'model_fields_of_study' );
			$this->model_fields_of_study->fields_of_study_id = $id;
			$this->model_fields_of_study->deleted            = 1;

			// Start a database transaction
			$this->db->trans_start();

			// Update the technical ability to all the users
			$this->model_fields_of_study_of_schools_of_users->update_field_of_study_id( $new_id );

			// Mark the previous technical ability as deleted
			$this->model_fields_of_study->soft_delete();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * schools_delete
	 *
	 * Merge a dictionary item
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function schools_delete( $id, $new_id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_schools_of_users' );
			$this->model_schools_of_users->school_id = $id;

			$this->load->model( 'model_schools' );
			$this->model_schools->school_id = $id;
			$this->model_schools->deleted   = 1;

			// Start a database transaction
			$this->db->trans_start();

			// Update the technical ability to all the users
			$this->model_schools_of_users->update_school_id( $new_id );

			// Mark the previous technical ability as deleted
			$this->model_schools->soft_delete();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * focusareas_get
	 *
	 * Get a collection of areas of focus matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function focusareas_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );
		$extended = $this->get( 'extended' );
		
		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_areas_of_focus' );
			$this->model_dictionary->model = $this->model_areas_of_focus;

			if( !is_null($extended)) {
				$this->model_dictionary->limit = 1000;
			}

			$values = $this->model_dictionary->get_dictionary( 'name', $name );

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * focusareas_post
	 *
	 * Suggest a new dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function focusareas_post() {
		$valid          = true;
		$form_validated = true;

		$name = $this->post( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_areas_of_focus' );
			$this->model_areas_of_focus->area_of_focus_name = ucfirst( $name );

			$this->model_areas_of_focus->item_add();

			$this->response( [
				'id' => $this->model_areas_of_focus->area_of_focus_id
			], Base_Controller::HTTP_OK );
		}
	}


	/**
	 * focusareas_put
	 *
	 * Approve a dictionary item
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function focusareas_put( $id ) {
		$valid = true;

		if( ! $this->is_admin() ) {
			$valid = false;
		}

		if( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model('model_areas_of_focus');
			$this->model_areas_of_focus->area_of_focus_id = $id;
			$this->model_areas_of_focus->area_of_focus_admin_approved = 1;
			if( ! $this->model_areas_of_focus->admin_approve() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Already approved'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status'  => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * focusareas_delete
	 *
	 * Merge a dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function focusareas_delete( $id, $new_id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model('model_areas_of_focus_of_positions_of_users');
			$this->model_areas_of_focus_of_positions_of_users->area_of_focus_id = $id;

			$this->load->model('model_areas_of_focus');
			$this->model_areas_of_focus->area_of_focus_id = $id;
			$this->model_areas_of_focus->deleted = 1;

			// Start a database transaction
			$this->db->trans_start();

			// Update the technical ability to all the users
			$this->model_areas_of_focus_of_positions_of_users->update_area_of_focus_id( $new_id );

			// Mark the previous technical ability as deleted
			$this->model_areas_of_focus->soft_delete();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * seniority_get
	 *
	 * Get a collection of seniorities matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function seniority_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_seniorities' );
			$this->model_dictionary->model = $this->model_seniorities;
			$values = $this->model_dictionary->get_dictionary( 'name', $name );

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * seniority_post
	 *
	 * Suggest a new dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function seniority_post() {
		$valid          = true;
		$form_validated = true;

		$name = $this->post( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_seniorities' );
			$this->model_seniorities->seniority_name = ucfirst( $name );

			$this->model_seniorities->item_add();

			$this->response( [
				'id' => $this->model_seniorities->seniority_id
			], Base_Controller::HTTP_OK );
		}
	}


	/**
	 * seniority_put
	 *
	 * Approve a dictionary item
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function seniority_put( $id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_seniorities' );
			$this->model_seniorities->seniority_id             = $id;
			$this->model_seniorities->seniority_admin_approved = 1;
			if ( ! $this->model_seniorities->admin_approve() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Already approved'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * seniority_delete
	 *
	 * Merge a dictionary item
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function seniority_delete( $id, $new_id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Load the positions of users model
			$this->load->model( 'model_positions_of_users' );
			$this->model_positions_of_users->seniority_id = $id;

			// Load the seniorities model
			$this->load->model( 'model_seniorities' );
			$this->model_seniorities->seniority_id = $id;
			$this->model_seniorities->deleted    = 1;

			// Start a database transaction
			$this->db->trans_start();

			// Update the seniority_id in all the user positions
			$this->model_positions_of_users->update_seniority_id( $new_id );

			// Mark the previous seniority as deleted
			$this->model_seniorities->soft_delete();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * jobtitle_get
	 *
	 * Get a collection of job titles matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function jobtitle_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_job_title' );
			$this->model_dictionary->model = $this->model_job_title;
			$values = $this->model_dictionary->get_dictionary( 'name', $name );

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * jobtitle_post
	 *
	 * Suggest a new dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function jobtitle_post() {
		$valid          = true;
		$form_validated = true;

		$name = $this->post( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_job_title' );
			$this->model_job_title->job_title_name = ucfirst( $name );

			$this->model_job_title->item_add();

			$this->response( [
				'id' => $this->model_job_title->job_title_id
			], Base_Controller::HTTP_OK );
		}
	}


	/**
	 * jobtitle_put
	 *
	 * Approve a dictionary item
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function jobtitle_put( $id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_job_title' );
			$this->model_job_title->job_title_id             = $id;
			$this->model_job_title->job_title_admin_approved = 1;
			if ( ! $this->model_job_title->admin_approve() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Already approved'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * jobtitle_delete
	 *
	 * Merge a dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function jobtitle_delete( $id, $new_id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Load the positions of users model
			$this->load->model( 'model_positions_of_users' );
			$this->model_positions_of_users->job_title_id = $id;

			// Load the industry model
			$this->load->model( 'model_job_title' );
			$this->model_job_title->job_title_id = $id;
			$this->model_job_title->deleted    = 1;

			// Load the business model
			$this->load->model('model_business_users');
			$this->model_business_users->business_user_job_title = $id;

			// Start a database transaction
			$this->db->trans_start();

			// Update the job title in all the user positions
			$this->model_positions_of_users->update_job_title_id( $new_id );

			// Update the job title in all the businesses users
			$this->model_business_users->update_job_title_id( $new_id );

			// Mark the previous job title as deleted
			$this->model_job_title->soft_delete();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * industry_get
	 *
	 * Get a collection of industries matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function industry_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_industries' );
			$this->model_dictionary->model = $this->model_industries;
			$values = $this->model_dictionary->get_dictionary( 'name', $name );

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * industry_post
	 *
	 * Suggest a new dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function industry_post() {
		$valid          = true;
		$form_validated = true;

		$name = $this->post( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_industries' );
			$this->model_industries->industry_name = ucfirst( $name );

			$this->model_industries->item_add();

			$this->response( [
				'id' => $this->model_industries->industry_id
			], Base_Controller::HTTP_OK );
		}
	}


	/**
	 * industry_put
	 *
	 * Approve a dictionary item
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function industry_put( $id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_industries' );
			$this->model_industries->industry_id             = $id;
			$this->model_industries->industry_admin_approved = 1;
			if ( ! $this->model_industries->admin_approve() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Already approved'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * industry_delete
	 *
	 * Merge a dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function industry_delete( $id, $new_id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Load the positions of users model
			$this->load->model( 'model_positions_of_users' );
			$this->model_positions_of_users->industry_id = $id;

			// Load the industry model
			$this->load->model( 'model_industries' );
			$this->model_industries->industry_id = $id;
			$this->model_industries->deleted    = 1;

			// Load the business model
			$this->load->model('model_business');
			$this->model_business->industry_id = $id;

			// Start a database transaction
			$this->db->trans_start();

			// Update the industry_id in all the user positions
			$this->model_positions_of_users->update_industry_id( $new_id );

			// Update the industry_id in all the businesses
			$this->model_business->update_industry_id( $new_id );

			// Mark the previous technical ability as deleted
			$this->model_industries->soft_delete();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}


	/**
	 * company_post
	 *
	 * Suggest a new dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function company_post() {
		$valid          = true;
		$form_validated = true;

		$name = $this->post( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_companies' );
			$this->model_companies->company_name = ucfirst( $name );

			$this->model_companies->item_add();

			$this->response( [
				'id' => $this->model_companies->company_id
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * company_get
	 *
	 * Get a collection of companies matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function company_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_companies' );
			$this->model_dictionary->model = $this->model_companies;
			$values = $this->model_dictionary->get_dictionary( 'name', $name );

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * company_put
	 *
	 * Approve a dictionary item
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function company_put( $id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_companies' );
			$this->model_companies->company_id             = $id;
			$this->model_companies->company_admin_approved = 1;
			if ( ! $this->model_companies->admin_approve() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Already approved'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * company_delete
	 *
	 * Merge a dictionary item
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function company_delete( $id, $new_id ) {
		$valid = true;

		if ( ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_positions_of_users' );
			$this->model_positions_of_users->company_id = $id;

			$this->load->model( 'model_companies' );
			$this->model_companies->company_id = $id;
			$this->model_companies->deleted    = 1;

			// Start a database transaction
			$this->db->trans_start();

			// Update the technical ability to all the users
			$this->model_positions_of_users->update_company_id( $new_id );

			// Mark the previous technical ability as deleted
			$this->model_companies->soft_delete();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * languages_get
	 *
	 * Get a collection of languages matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function languages_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );
		$extended = $this->get( 'extended' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_languages' );
			$this->model_dictionary->model = $this->model_languages;

			if( !is_null($extended)) {
				$this->model_dictionary->limit = 1000;
			}
			$temp_array=array();
			$values = $this->model_dictionary->get_dictionary( 'name', $name );
			foreach($values as $val){
				if(array_search($val->name, array_column($temp_array, 'name')) == False) {
					array_push($temp_array, $val);
				} 
			}
			$this->response( $temp_array, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * traits_get
	 *
	 * Get a collection of traits matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function traits_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );
		$extended = $this->get( 'extended' );

		// Loading the user's object
		$this->load->model( 'model_users' );
		$this->model_users->user_id = $this->get_active_user();
		$this->model_users->get();

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_traits' );
			$this->model_dictionary->model = $this->model_traits;

			if( !is_null($extended)) {
				$this->model_dictionary->limit = 1000;
			}

			$values = $this->model_dictionary->get_dictionary( 'name', $name );

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * enums_get
	 *
	 * Get the system enums
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function enums_get() {
		$this->config->load('enums');

		$this->response( $this->config->item('enums'), Base_Controller::HTTP_OK );
	}

	/**
	 * business_get
	 *
	 * Get a collection of businesses matching the given criteria
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function business_get() {
		$valid          = true;
		$form_validated = true;

		$name = $this->get( 'name' );

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'dictionary_item'
		) );

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_business' );
			$this->model_dictionary->model = $this->model_business;
			$values = $this->model_dictionary->get_dictionary( 'name', $name );

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * education_levels_get
	 *
	 * Get a collection of education levels
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function education_levels_get() {
		$valid          = true;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Getting the dictionaries
			$this->load->model( 'model_dictionary' );
			$this->load->model( 'model_education_levels' );
			$this->model_dictionary->model = $this->model_education_levels;
			$values = $this->model_dictionary->get_dictionary_items();

			$this->response( $values, Base_Controller::HTTP_OK );
		}
	}
}
