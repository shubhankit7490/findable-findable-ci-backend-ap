<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class User extends Base_Controller {

	private $cv_debug = [];

	function __construct() {
		parent::__construct();

		$this->config->load( 'session_vars' );
		$this->load->helper( 'array' );

		$this->methods['login_post']['key']    = false;
		$this->methods['signup_post']['key']   = false;
		$this->methods['reset_post']['key']    = false;
		$this->methods['verify_get']['key']    = false;
		$this->methods['forgot_post']['key']   = false;
		$this->methods['requests_post']['key'] = false;
		// Public profile endpoints
		$this->methods['profile_get']['key']     = false;
		$this->methods['traits_get']['key']      = false;
		$this->methods['education_get']['key']   = false;
		$this->methods['experience_get']['key']  = false;
		$this->methods['languages_get']['key']   = false;
		$this->methods['tech_get']['key']        = false;
		$this->methods['preferences_get']['key'] = false;
		$this->methods['cv_get']['key']			 = false;
		$this->methods['has_cv_get']['key']		 = false;
		// Statistics
		$this->methods['views_post']['key'] = false;
	}

	/**
	 * users
	 *
	 * Get collection of users
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    void
	 */
	public function index_get() {
		$valid          = true;
		$form_validated = true;

		$start  = $this->get( 'start' );
		$end    = $this->get( 'end' );
		$offset = $this->get( 'offset' );
		if(isset($_GET['user_type']) && $_GET['user_type']!='null'){
			$user_type = $this->get( 'user_type' );
		}else{
			$user_type =null;
		}
		// Validating input parameters
		$form_validated = $this->validateRequestParameters( [
			'offset' => $offset,
			'start'  => $start,
			'end'    => $end
		], array(
			'start',
			'end',
			'offset'
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
			// Load the upload library for image serving
			$this->load->library( 'Upload' );

			$this->load->model( 'model_files' );
			$this->load->model( 'model_minimal_users_profiles' );
			$this->load->model( 'model_users' );
			$offset = $this->is_true_null( $offset ) ? 0 : $offset*50;
			$start  = $this->is_true_null( $start ) ? null : $start;
			$end    = $this->is_true_null( $end ) ? null : $end;
			if($user_type){
				$users = $this->model_users->get_all_users_data( $offset, $start, $end,null,$user_type );
			}else{
				$users = $this->model_users->get_all_users( $offset, $start, $end,50,[ 'applicant', 'manager', 'recruiter' ]);
			}
			

			$this->response( $users, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * login_post
	 *
	 * Login endpoint for applicants and recruiters
	 *
	 * @access    public
	 *
	 * @role    guest
	 *
	 * @return    Object
	 */
	public function login_post() {
		$valid          = true;
		$form_validated = true;
		$businesses     = [];

		$email    = $this->post( 'email' );
		$password = $this->post( 'password' );
		$apply    = $this->post( 'apply' );

		// Validate the input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'email',
			'apply'
		) );

		// Loading the user model
		$this->load->model( 'model_users' );
		$this->model_users->email = $email;
		if ( ! $this->model_users->get_by_email_login() && $valid ) {
			// User was not found in the database
			$this->response( [
				'status'  => false,
				'message' => 'User name or Password is incorrect'
			], Base_Controller::HTTP_FORBIDDEN );

			$valid = false;
		}
		if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			if ( $valid ) {

				if($this->model_users->created_by==0){
					// Checking the password
						if ( password_verify( $password, $this->model_users->password ) ) {
							if($this->model_users->verified_by_admin){
								/*if ( 1 ) {*/
									$this->load->library( 'Auth' );
									$this->auth->user_id = $this->model_users->user_id;

								// Getting the API key
									$auth = $this->auth->get();
									if ( ! $auth ) {
										$this->response( [
											'status'  => false,
											'message' => 'Unable to authenticate the user'
										], Base_Controller::HTTP_FORBIDDEN );
									} else {
									// Update the last login time
										$this->model_users->update_last_login();

									// Check if the user requested an application to a business
										if ( ! is_null( $apply ) ) {
											$this->load->model( 'model_applicants_of_business' );
											$this->model_applicants_of_business->user_id     = $this->model_users->user_id;
											$this->model_applicants_of_business->business_id = $apply;
											$this->model_applicants_of_business->verified    = ( $this->model_users->status == 'active' );
											$this->model_applicants_of_business->create();
										}

										if ( $this->is_recruiter( $this->model_users->role ) ) {
											$this->load->model( 'model_business_users' );
											$this->model_business_users->user_id = $this->model_users->user_id;
											$businesses   = $this->model_business_users->get_recruiter_businesses();
										}

									// Build the response object
										$response = [
											'key'                => $auth->key,
											'role'               => $this->model_users->role,
											'active_user_id'     => null,
											'active_business_id' => null,
											'user_id'            => $this->model_users->user_id,
											'business_id'        => $auth->active_business_id,
											'status'             => $this->model_users->status,
											'businesses'         => $businesses
										];

									// Overwrite the active parameters if the user's role is admin
										if ( $this->is_admin( $this->model_users->role ) ) {
											$response['active_user_id']     = $auth->active_user_id;
											$response['active_business_id'] = $auth->active_business_id;
										}

									// Respond to the client
										$this->response( $response, Base_Controller::HTTP_OK );
									}
								}else{
									$this->response( [
										'status'  => false,
										'message' => 'You are not verified user'
									], Base_Controller::HTTP_NOT_FOUND );
								} 
						}else {
							$this->response( [
								'status'  => false,
								'message' => 'User name or Password is incorrect'
							], Base_Controller::HTTP_NOT_FOUND );
						}
					} else {
						$this->response( [
							'status'  => false,
							'message' => 'not a valid user'
						], Base_Controller::HTTP_NOT_FOUND );
					}

				} else {
					$this->response( [
						'status'  => false,
						'message' => 'Not found'
					], Base_Controller::HTTP_NOT_FOUND );
				}
			}
		}

	/**
	 * verify_get
	 *
	 * Verify user's email address
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager
	 *
	 * @return    Object
	 */
	public function verify_get() {
		$valid         = true;
		$error_message = false;
		$businesses    = [];

		// Load the tokens model and
		$this->load->model( 'model_tokens' );
		$this->model_tokens->token    = $this->get( 'token' );
		$this->model_tokens->type     = 'activation';
		$this->model_tokens->verified = 0;

		if ( ! $this->model_tokens->get_by_token() ) {
			$valid = false;
		} else {
			// Start a database transaction
			$this->db->trans_start();

			// Set the token as verified
			$this->model_tokens->verify();

			$error_message = $this->get_transaction_error( $error_message );

			// Load the users model
			$this->load->model( 'model_users' );
			$this->model_users->user_id = $this->model_tokens->user_id;

			if ( $this->model_users->get() ) {
				if ( $this->is_applicant( $this->model_users->role ) ) {
					// If the user's role is applicant we change his search_visible to true
					$this->model_users->search_visible = 1;
				}

				// Verify the user's email
				// Change the status of the user to active
				// change the search visibility (Optional)
				$this->model_users->verify();

				$error_message = $this->get_transaction_error( $error_message );

				// Write the modified user object to the cache driver
				$this->set_user( $this->model_tokens->user_id );
				$this->get_user( $this->model_tokens->user_id );

				// Get the user api key and role
				$this->load->library( 'Auth' );
				$this->auth->user_id = $this->model_users->user_id;
				$auth                = $this->auth->get();

				if ( $this->is_applicant( $this->model_users->role ) ) {
					$this->load->model( 'model_applicants_of_business' );
					$this->model_applicants_of_business->user_id  = $this->model_users->user_id;
					$this->model_applicants_of_business->verified = 1;
					$this->model_applicants_of_business->verify_applications();
				} else if ( $this->is_recruiter( $this->model_users->role ) ) {
					$this->load->model( 'model_business_users' );
					$this->model_business_users->user_id = $this->model_users->user_id;
					$businesses                          = $this->model_business_users->get_recruiter_businesses();
				}
			}

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $error_message
				], Base_Controller::HTTP_BAD_REQUEST );
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Build the response object
			$response = [
				'key'                => $auth->key,
				'role'               => $this->model_users->role,
				'active_user_id'     => null,
				'active_business_id' => null,
				'user_id'            => $this->model_users->user_id,
				'business_id'        => $auth->active_business_id,
				'status'             => $this->model_users->status,
				'businesses'         => $businesses
			];

			// Overwrite the active parameters if the user's role is admin
			if ( $this->is_admin( $this->model_users->role ) ) {
				$response['active_user_id']     = $auth->active_user_id;
				$response['active_business_id'] = $auth->active_business_id;
			}

			$this->response( $response, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * forgot_post
	 *
	 * Request a password reset link (sent to the email)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function forgot_post() {
		$valid          = true;
		$form_validated = true;

		$email = $this->post( 'email' );

		if ( is_null( $email ) ) {
			$valid = false;
		} else {
			// Validate the input parameters
			$form_validated = $this->validateRequestParameters( $this->post(), array(
				'valid_user_email'
			) );
		}

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
			// Get the user object
			$this->load->model( 'model_users' );
			$this->model_users->email = $email;
			$this->model_users->get_by_email();
			if($this->model_users->created_by==0){
				// CREATE / GET a token of type reset
				$this->load->model( 'model_tokens' );
				$this->model_tokens->user_id = $this->model_users->user_id;
				$this->model_tokens->type    = 'reset';

				if ( ! $this->model_tokens->get_by_type() ) {
					$this->model_tokens->create();
				}

				// Load the template parser class
				$this->load->library( 'Template' );

				// Load the language file with the content of the email
				$this->lang->load( 'email' );
				$email_data               = $this->lang->line( 'password_reset' );
				$email_data['button_url'] = base_url() . 'reset?token=' . $this->model_tokens->token;

				$html = $this->template->view( 'password_reset', $email_data, true );

				// Load the Mailgun library wrapper
				$this->load->library( 'Mailgun' );

				// Extract the textual version from the html body version
				$text = $this->mailgun->get_text_version( $html );

				// Replace un wanted text with the link to the profile
				$text = $this->mailgun->str_replace_first( 'click on the following link', 'copy and paste the following link in your web browser:', $text );
				$text = $this->mailgun->str_replace_first( 'Let me in', $email_data['button_url'], $text );

				// Set the sending parameter
				$this->mailgun->subject = $email_data['subject'];
				$this->mailgun->html    = $html;
				$this->mailgun->body    = $text;
				$this->mailgun->to      = $email;
				$sent                   = $this->mailgun->send();

				if ( ! $sent ) {
					$this->response( [
						'status'  => false,
						'message' => 'Email service unavailable'
					], Base_Controller::HTTP_SERVICE_UNAVAILABLE );
				} else {
					$this->response( [
						'status' => true,
						'token'  => $this->model_tokens->token
					], Base_Controller::HTTP_OK );
				}
			}else{
				$this->response( [
						'status'  => false,
						'message' => 'Not a valid user'
					], Base_Controller::HTTP_SERVICE_UNAVAILABLE );
			}
		}
	}

	/**
	 * personal_details_get
	 *
	 * Get a user's personal details object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function personal_details_get() {
		$valid = true;

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
			// Load the upload library for the profile image serving
			$this->load->library( 'Upload' );

			// Get the user's profile (public or private)
			$this->load->model( 'model_personal_details' );
			$this->load->model( 'model_profiles' );
			$this->load->model( 'model_location' );
			$this->load->model( 'model_files' );


			$this->model_profiles->user_id = $this->get_active_user();
			$profile                       = $this->model_profiles->get_personal_details();

			if ( ! $profile ) {
				$this->response( [
					'status'  => false,
					'message' => 'Could not find the user'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( $profile, Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * personal_details_put
	 *
	 * Updates the current user's personal details object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function personal_details_put() {
		$valid     = true;
		$validated = true;
		$access    = true;
		$message   = '';

		$user_id = $this->get_active_user();

		$profile = $this->request->body;

		// Validating for profile variable types
		$profile = ( is_string( $profile ) ) ? json_decode( $profile, true ) : $profile;

		// Restricting to admin and the current user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid   = false;
			$message = 'Not authorzied';
			$code    = Base_Controller::HTTP_UNAUTHORIZED;
		} else if ( is_null( $profile ) ) {
			$valid   = false;
			$message = 'Malformed request body';
			$code    = Base_Controller::HTTP_BAD_REQUEST;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $user_id );

			// Checking that the user has activated his account
			if (!$this->check_status($this->model_users)) {
				$valid   = false;
				$message = 'Activation needed';
				$code    = Base_Controller::HTTP_BAD_REQUEST;
			} else {
				// Load the validation class & language
				$this->load->library( 'form_validation' );
				$this->lang->load( 'validation' );

				$this->form_validation->set_data( $profile );

				// Load the validation rules
				$this->form_validation->rules( array(
					'firstname',
					'lastname',
					'gender',
					'phone',
					'skype',
					'birthday',
					'image_id',
					'location[city_name]',
					'about'
				) );

				if ( $this->form_validation->run() == false ) {
					$validated = false;

					foreach ( $this->form_validation->error_array() as $field => $error ) {
						$errors[] = (object) array(
							'field' => $field,
							'error' => $error
						);
					}
				}
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => $message
			], $code );
		} else if ( ! $validated ) {
			$this->response( [
				'status'  => false,
				'message' => $errors
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Load the upload library for image serving
			$this->load->library( 'Upload' );

			$this->load->model( 'model_personal_details' );
			$this->load->model( 'model_profiles' );
			$this->load->model( 'model_location' );
			$this->load->model( 'model_files' );

			$this->model_profiles->user_id = $user_id;
			$current_profile               = $this->model_profiles->get_personal_details();

			if ( count( array_diff_key( $profile, (array) $current_profile ) ) > 0 ) {
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Loading the profile model
				$this->model_profiles->load();

				// Replacing with the new data
				$this->model_profiles->load_model( $profile );

				// Loading location if present
				if ( array_key_exists( 'location', $profile ) ) {
					$this->load->model('model_location');
					$city_id = $this->model_location->get_city_id($profile['location'], false);
					$this->model_profiles->city_id = $city_id;
				}
				
				// Saving the updated model
				if ( ! $this->model_profiles->save() ) {
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
	}

	public function about_get() {
		$valid = true;

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
			// Get the user's profile (public or private)
			$this->load->model( 'model_personal_details' );
			$this->load->model( 'model_profiles' );
			$this->load->model( 'model_location' );
			$this->load->model( 'model_files' );

			$this->model_profiles->user_id = $this->get_active_user();
			$profile                       = $this->model_profiles->get_personal_details();

			if ( ! $profile ) {
				$this->response( [
					'status'  => false,
					'message' => 'Could not find the user'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( $profile->about, Base_Controller::HTTP_OK );
			}
		}
	}

	public function about_put() {
		$valid     = true;
		$validated = true;
		$access    = true;
		$message   = '';

		$user_id = $this->get_active_user();

		$data = $this->request->body;

		// Validating for profile variable types
		$data = ( is_string( $data ) ) ? json_decode( $data, true ) : $data;

		// Restricting to admin and the current user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid   = false;
			$message = 'Not authorzied';
			$code    = Base_Controller::HTTP_UNAUTHORIZED;
		} else if ( is_null( $data ) ) {
			$valid   = false;
			$message = 'Malformed request body';
			$code    = Base_Controller::HTTP_BAD_REQUEST;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $user_id );

			// Checking that the user has activated his account
			if (!$this->check_status($this->model_users)) {
				$valid   = false;
				$message = 'Activation needed';
				$code    = Base_Controller::HTTP_BAD_REQUEST;
			} else {
				// Load the validation class & language
				$this->load->library( 'form_validation' );
				$this->lang->load( 'validation' );

				$this->form_validation->set_data( $data );

				// Load the validation rules
				$this->form_validation->rules( array(
					'about'
				) );

				if ( $this->form_validation->run() == false ) {
					$validated = false;

					foreach ( $this->form_validation->error_array() as $field => $error ) {
						$errors[] = (object) array(
							'field' => $field,
							'error' => $error
						);
					}
				}
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => $message
			], $code );
		} else if ( ! $validated ) {
			$this->response( [
				'status'  => false,
				'message' => $errors
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {            
			$this->load->model( 'model_profiles' );

			$this->model_profiles->user_id = $user_id;
			$result = $this->model_profiles->update_about($data['about']);

			if ( ! $result ) {
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
	 * profile_get
	 *
	 * Get a user's profile object
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */

	public function profile_get( $user_id,$showdata ) {
		$profile_type = 'public_profile';
		$valid        = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}
		if ( $this->is_recruiter() ) {
			$business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$business_id = $this->get_manager_business();
		} else {
			$business_id = null;
		}
		$this->get_user( $this->model_users->creator_id );
		$this->load->model( 'Model_invitations_of_business' );
		$this->Model_invitations_of_business->business_id=$business_id;
		$this->Model_invitations_of_business->accepted=1;
		$this->Model_invitations_of_business->deleted=0;
		$this->Model_invitations_of_business->email=$this->model_users->email;
		$user_ids=array();
		if($this->Model_invitations_of_business->is_exists()){
			 $showdata=true;
		}
		// If the requested profile belongs to the requesting user we should serve the private profile
		if ( ($this->get_active_user() === $user_id) || $showdata) {
			$profile_type = 'private_profile';
		} else {
			// If the requesting user is a recruiter we check if he is an owner of the requested user's profile
			if ( $this->is_recruiter() ) {
				// Get the recruiter's current business_id on behalf he is operating
				$recruiter_business_id = $this->get_recruiter_business();

				// Check if the business purchased the applicant or the applicant applied to the manager's business
				/*$this->load->model( 'model_user_data' );
				$this->model_user_data->user_id     = $user_id;
				$this->model_user_data->business_id = $recruiter_business_id;

				$is_applicant_of_business = $this->model_user_data->is_applicant_of_business();
				if ( $is_applicant_of_business ) {
					$profile_type = 'private_profile';
				} else {
					$profile_type = 'public_profile';
				}*/
				$this->load->model( 'model_user_data' );
				$data=$this->model_user_data->is_user_profile_visible($user_id);
				if($data){
					if($data->created_by==1 && $data->creator_id==$this->get_active_user()){
						$profile_type = 'private_profile';
					}else{
						$profile_type = 'public_profile';
					}	
				}else{
					$profile_type = 'public_profile';
				}
			}

			if ( $this->is_manager() ) {
				$manager_business_id = $this->get_manager_business();

				// Check if the business purchased the applicant or the applicant applied to the manager's business
				/*$this->load->model( 'model_user_data' );
				$this->model_user_data->user_id     = $user_id;
				$this->model_user_data->business_id = $manager_business_id;

				$is_applicant_of_business = $this->model_user_data->is_applicant_of_business();
				$subscription=$this->subscription_profile_get($user_id);
				if ( $is_applicant_of_business) {
					if(!empty($subscription) && $subscription->subscriptions->status=='active'){
						$profile_type = 'public_profile';
					}else{
						$profile_type = 'private_profile';
					}
				} else {
					$profile_type = 'public_profile';
				}*/
				$this->load->model( 'model_user_data' );
				$data=$this->model_user_data->is_user_profile_visible($user_id);
				if($data){
					if($data->created_by==1 && $data->creator_id==$this->get_active_user()){
						$profile_type = 'private_profile';
					}else{
						$profile_type = 'public_profile';
					}	
				}else{
					$profile_type = 'public_profile';
				}
			}
		}
		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Load the upload library for image serving
			$this->load->library( 'Upload' );

			// Get the user's profile (public or private)
			$this->load->model( 'model_profile' );
			$this->load->model( 'model_profiles' );
			$this->load->model( 'model_location' );

			// Load additional models if the profile type is private
			if ( $profile_type == 'private_profile' ) {
				$this->load->model( 'model_files' );
			}
			$this->model_profiles->user_id = $user_id;
			$profile = $this->model_profiles->{'get_' . $profile_type}();
			if ( ! $profile ) {
				$this->response( [
					'status'  => false,
					'message' => 'Could not find the user'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response($profile, Base_Controller::HTTP_OK );
			}
		}
	}
	public function purches_get( $user_id ) {
		$manager_business_id = $this->get_manager_business();
				// Check if the business purchased the applicant or the applicant applied to the manager's business
		$this->load->model( 'model_user_data' );
		$this->model_user_data->user_id     = $user_id;
		$this->model_user_data->business_id = $manager_business_id;
		$is_applicant_of_business = $this->model_user_data->is_applicant_of_business();
		if ( $is_applicant_of_business) {
			$this->response( [
				'status' => true
			], Base_Controller::HTTP_OK );
		}else{
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		}
	}
	/**
	 * reset_post
	 *
	 * Reset the user's password
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function reset_post() {
		$form_validated = true;
		$businesses     = [];
		$token    = $this->post( 'token' );
		$password = $this->post( 'password' );

		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'token',
			'password'
		) );

		if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Verify that the token exists
			$this->load->model( 'model_tokens' );
			$this->model_tokens->token = $token;
			$this->model_tokens->type  = 'reset';

			if ( ! $this->model_tokens->get_by_token() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Get the user which requested the password reset
				$this->load->model( 'model_users' );
				$this->model_users->user_id = $this->model_tokens->user_id;
				$this->model_users->get();

				// Creating an API access key
				$this->load->library( 'Auth' );
				$this->auth->user_id = $this->model_users->user_id;
				$this->auth->role    = $this->model_users->role;
				$auth                = $this->auth->get();

				// Start a database transaction
				$this->db->trans_start();

				if ( is_null( $auth ) ) {
					$this->auth->create();
				}

				// Update the password of the user (using bcrypt algorithm)
				$this->model_users->password = password_hash( $password, PASSWORD_BCRYPT );
				$this->model_users->update();

				// Mark the reset token as verified
				$this->model_tokens->verify();

				// Update the last login time
				$this->model_users->update_last_login();

				$this->db->trans_complete();
				if ( $this->is_recruiter( $this->model_users->role ) ) {
					$this->load->model( 'model_business_users' );
					$this->model_business_users->user_id = $this->model_users->user_id;
					$businesses   = $this->model_business_users->get_recruiter_businesses();
				}

					// Build the response object
				$response = [
					'key'                => $auth->key,
					'role'               => $this->model_users->role,
					'active_user_id'     => null,
					'active_business_id' => null,
					'user_id'            => $this->model_users->user_id,
					'business_id'        => $auth->active_business_id,
					'status'             => $this->model_users->status,
					'businesses'         => $businesses
				];
				if ( $this->db->trans_status() === false ) {
					// Database transaction failed
					$this->response( [
						'status'  => false,
						'message' => $this->get_transaction_error() ?: 'System error'
					], Base_Controller::HTTP_BAD_REQUEST );
				} else {
					// Send a success response to the client
					$this->response( $response, Base_Controller::HTTP_OK );
				}
			}
		}
	}

	/**
	 * profile_put
	 *
	 * Update a user's profile object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function profile_put( $user_id ) {
		$valid     = true;
		$validated = true;
		$access    = true;
		$updated   = true;
		$message   = '';

		$profile = $this->request->body;

		// Validating for profile variable types
		$profile = ( is_string( $profile ) ) ? json_decode( $profile, true ) : $profile;

		// Restricting to admin and the current user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid   = false;
			$message = 'Not authorzied';
			$code    = Base_Controller::HTTP_UNAUTHORIZED;
		} else if ( is_null( $profile ) ) {
			$valid   = false;
			$message = 'Bad request';
			$code    = Base_Controller::HTTP_BAD_REQUEST;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $user_id );

			// Checking that the user has activated his account
			if (!$this->check_status($this->model_users)) {
				$valid   = false;
				$message = 'Activation needed';
				$code    = Base_Controller::HTTP_BAD_REQUEST;
			} else {
				// Load the validation class & language
				$this->load->library( 'form_validation' );
				$this->lang->load( 'validation' );

				$this->form_validation->set_data( $profile );

				// Load the validation rules
				$this->form_validation->rules( array(
					'firstname',
					'lastname',
					'gender',
					'phone',
					'skype',
					'image_id',
					'location[city_name]',
					'about'
				) );

				if ( $this->form_validation->run() == false ) {
					$validated = false;

					foreach ( $this->form_validation->error_array() as $field => $error ) {
						$errors[] = (object) array(
							'field' => $field,
							'error' => $error
						);
					}
				}
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => $message
			], $code );
		} else if ( ! $validated ) {
			$this->response( [
				'status'  => false,
				'message' => $errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_profile' );
			$this->load->model( 'model_profiles' );
			$this->load->model( 'model_location' );
			$this->load->model( 'model_files' );

			$this->model_profiles->user_id = $user_id;
			//$current_profile               = $this->model_profiles->get_private_profile();

			// Loading the profile model
			$this->model_profiles->load();

			// Replacing with the new data
			$this->model_profiles->load_model( $profile );

			if ($this->model_users->email !== $profile['email']) {
				$this->model_users->email = $profile['email'];
				$this->model_users->verified = 0;
				$updated = $this->model_users->update();
			}

			// Loading location if present
			if ( array_key_exists( 'location', $profile ) ) {
				if (is_null($profile['location']['city_id'])) {
					$city_id = $this->model_location->get_city_id($profile['location'], false);
					$profile['location']['city_id'] = $city_id;	
				}

				$this->model_profiles->city_id = $profile['location']['city_id'];
			}

			// Saving the updated model
			if (!$updated) {
				$this->response( [
					'status'  => false,
					'message' => 'Duplicate email'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else if ( ! $this->model_profiles->save()) {
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
	 * preferences_get
	 *
	 * Get a user's preferences object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function preferences_get( $user_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Get the user's preferences
			$this->load->model( 'model_user_status' );
			$this->model_user_status->user_id = $user_id;
			$preferences                      = $this->model_user_status->get();

			if ( ! $preferences ) {
				$this->response( [
					'status'  => false,
					'message' => 'Could not find the user'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Get the location of interest
				$this->load->model( 'model_locations_of_interest_of_user_status' );
				$this->load->model( 'model_location' );

				$this->model_locations_of_interest_of_user_status->user_status_id = $preferences->status_id;
				$preferences->locations                                           = $this->model_locations_of_interest_of_user_status->get_locations();

				// Output to the client
				$this->response( $preferences, Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * preferences_put
	 *
	 * Update a user's preferences object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function preferences_put( $user_id ) {
		$valid          = true;
		$form_validated = true;
		$message        = '';
		$error_message  = false;

		$preferences = $this->request->body;

		// Validating for profile variable types
		$preferences = ( is_string( $preferences ) ) ? json_decode( $preferences, true ) : $preferences;
		// Restricting to admin and the current user
		/*if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid   = false;
			$message = 'Not authorzied';
			$code    = Base_Controller::HTTP_UNAUTHORIZED;
		} else*/ 
		if ( is_null( $preferences ) ) {
			$valid   = false;
			$message = 'Bad request';
			$code    = Base_Controller::HTTP_BAD_REQUEST;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $user_id );

			// Checking that the user has activated his account
			if (!$this->check_status($this->model_users)) {
				$valid   = false;
				$message = 'Activation needed';
				$code    = Base_Controller::HTTP_BAD_REQUEST;
			} else {
				$preferences_validation_rules = array(
					'desired_salary_period',
					'current_status',
					'benefits',
					'relocation',
					'only_current_location',
					'legal_usa',
					'employment_status'
				);

				if ( isset( $preferences['current_status'] ) && ! empty( $preferences['current_status'] ) ) {
					if ( $preferences['current_status'] !== 'not looking' ) {
						$preferences_validation_rules[] = 'available_from';
					}
				}

				if ( isset( $preferences['available_from'] ) && ! empty( $preferences['available_from'] ) ) {
					if ( $preferences['available_from'] == 'from' ) {
						$preferences_validation_rules[] = 'start_time_required';
					} else {
						$preferences_validation_rules[] = 'start_time';
						$preferences['start_time']      = null;
					}
				}

				$form_validated = $this->validateRequestParameters( $preferences, $preferences_validation_rules );

				$this->config->load( 'enums' );

				if ( ! $this->form_validation->in_list( $preferences['current_status'], implode( $this->config->item( 'enums' )->current_status, ',' ) ) ) {
					$form_validated->result   = false;
					$form_validated->errors[] = array(
						'field' => 'current_status',
						'error' => $this->form_validation->compile( 'current_status', $this->lang->line( 'in_list' ) )
					);
				}

				if ( isset( $preferences['current_status'] ) && ! empty( $preferences['current_status'] ) ) {
					if ( $preferences['current_status'] !== 'not looking' ) {
						if ( ! $this->form_validation->in_list( $preferences['available_from'], implode( $this->config->item( 'enums' )->available_from, ',' ) ) ) {
							$form_validated->result   = false;
							$form_validated->errors[] = array(
								'field' => 'available_from',
								'error' => $this->form_validation->compile( 'available_from', $this->lang->line( 'in_list' ) )
							);
						}
					}
				}

				if ( ! $this->form_validation->in_list( $preferences['employment_status'], implode( $this->config->item( 'enums' )->employment_status, ',' ) ) ) {
					$form_validated->result   = false;
					$form_validated->errors[] = array(
						'field' => 'employment_status',
						'error' => $this->form_validation->compile( 'employment_status', $this->lang->line( 'in_list' ) )
					);
				}
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => $message
			], $code );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_user_status' );
			$this->model_user_status->user_id = $user_id;
			$current_preferences              = $this->model_user_status->get();
			$locations                        = ( isset( $preferences['locations'] ) ) ? $preferences['locations'] : false;

			unset( $preferences['locations'] );

			if ( count( array_diff_key( $preferences, (array) $current_preferences ) ) > 0 ) {
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Loading the profile model
				$this->model_user_status->load();

				// Replacing with the new data
				$this->model_user_status->load_model( $preferences );

				// Start a database transaction
				$this->db->trans_start();

				// Save the user_status model
				$this->model_user_status->save();

				$error_message = $this->get_transaction_error( $error_message );

				// Restore the locations of the preferences
				if ( $locations !== false ) {
					$preferences['locations'] = $locations;
				}

				// Loading locations if present
				if ( array_key_exists( 'locations', $preferences ) ) {
					$this->load->model( 'model_location' );
					$this->load->model( 'model_locations_of_interest_of_user_status' );
					$this->load->model('model_location');

					foreach ($preferences['locations'] as &$location) {
						if (is_null($location['city_id'])) {
							$city_id = $this->model_location->get_city_id($location, false);
							$location['city_id'] = $city_id;
						}
					}

					// Remove locations which are not in the request
					$this->model_locations_of_interest_of_user_status->user_status_id = $this->model_user_status->user_status_id;
					$current_locations                                                = $this->model_locations_of_interest_of_user_status->get_locations();
					$requested_locations_city_ids                                     = array_map( create_function( '$o', 'return $o["city_id"];' ), $preferences['locations'] );

					if ( count( $current_locations ) > 0 ) {
						$current_locations_city_ids = array_map( create_function( '$o', 'return $o->city_id;' ), $current_locations );
						$current_locations_city_ids = array_diff( $current_locations_city_ids, $requested_locations_city_ids );

						if ( count( $current_locations_city_ids ) > 0 ) {
							// Soft delete locations which are not present in the requested locations array
							$this->model_locations_of_interest_of_user_status->delete_unused_locations( $current_locations_city_ids );
						}
					}

					$error_message = $this->get_transaction_error( $error_message );

					// Insert new locations of they don't exists
					// Un delete locations which have been previously 'soft deleted'
					$this->model_locations_of_interest_of_user_status->insert_undelete_locations( $requested_locations_city_ids );

					$error_message = $this->get_transaction_error( $error_message );
				}

				$this->db->trans_complete();

				if ( $this->db->trans_status() === false ) {
					// Database transaction failed
					$this->response( [
						'status'  => false,
						'message' => $error_message
					], Base_Controller::HTTP_BAD_REQUEST );
				} else {
					$this->response( [
						'status' => true
					], Base_Controller::HTTP_OK );
				}
			}
		}
	}

	/**
	 * experience_get
	 *
	 * Get a user's experience object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function experience_get( $user_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activaited his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_location' );
			$this->load->model( 'model_positions_of_users' );
			$this->load->model( 'model_companies' );
			$this->load->model( 'model_industries' );
			$this->load->model( 'model_job_title' );
			$this->load->model( 'model_seniorities' );

			$this->load->model( 'model_areas_of_focus' );
			$this->load->model( 'model_areas_of_focus_of_positions_of_users' );

			$this->load->model( 'model_position' );

			$this->model_positions_of_users->user_id = $user_id;
			$positions  = $this->model_positions_of_users->get();

			// Output to the client
			$this->response( $positions, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * experience_post
	 *
	 * Add a new user's experience object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function experience_post( $user_id ) {
		$valid          = true;
		$form_validated = true;
		$message        = '';
		$error_message  = false;

		$position = $this->request->body;

		// Validating for profile variable types
		$position = ( is_string( $position ) ) ? json_decode( $position, true ) : $position;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid   = false;
			$message = 'Bad request';
			$code    = Base_Controller::HTTP_BAD_REQUEST;
		} else if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid   = false;
			$message = 'Not authorzied';
			$code    = Base_Controller::HTTP_UNAUTHORIZED;
		} else if ( is_null( $position ) ) {
			$valid   = false;
			$message = 'Bad request';
			$code    = Base_Controller::HTTP_BAD_REQUEST;
		} else {
			$form_validated = $this->validateRequestParameters( $position, array(
				'salary',
				'salary_period',
				'to',
				'from_required',
				'type',
				'company[id]',
				'location[city_name]',
				'industry[id]',
				'job_title[id]',
				'seniority[id]'
			) );

			$position['current'] = $this->has_param( $position, 'current', 0 );

			// Verify that a position_to is set or the position_current equal 1
			if ( ! isset( $position['to'] ) && $position['current'] == 0 ) {
				$form_validated->result   = false;
				$form_validated->errors[] = array(
					'field' => 'to',
					'error' => $this->form_validation->compile( 'to', $this->lang->line( 'to_not_isset' ) )
				);
			}

			// Position type validation
			if ( isset( $position['type'] ) && ! is_null( $position['type'] ) ) {
				$this->config->load( 'enums' );
				if ( ! $this->form_validation->in_list( $position['type'], implode( $this->config->item( 'enums' )->employment_type, ',' ) ) ) {
					$form_validated->result   = false;
					$form_validated->errors[] = array(
						'field' => 'type',
						'error' => $this->form_validation->compile( 'type', $this->lang->line( 'in_list' ) )
					);
				}
			}

			// Position from vs position to values
			if ( isset( $position['to'] ) && strtotime( $position['to'] ) < strtotime( $position['from'] ) ) {
				$form_validated->result   = false;
				$form_validated->errors[] = array(
					'field' => 'to',
					'error' => $this->form_validation->compile( 'to', $this->lang->line( 'to_lte_from' ) )
				);
			}

			// Validation for area_of focus length
			$this->config->load( 'form_validation' );
			if ( array_key_exists( 'areas_of_focus', $position ) ) {
				if ( count( $position['areas_of_focus'] ) > (int) $this->config->item( 'aof_max_count' ) ) {
					$form_validated->result   = false;
					$form_validated->errors[] = array(
						'field' => 'to',
						'error' => $this->form_validation->compile( 'to', $this->lang->line( 'aof_max_count' ) )
					);
				}
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => $message
			], $code );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			// Check that the user did not work in the same position in the same company in the range of the provided years
			$this->load->model( 'model_positions_of_users' );
			$this->model_positions_of_users->user_id       = $user_id;
			$this->model_positions_of_users->company_id    = $position['company']['id'];
			$this->model_positions_of_users->job_title_id  = (string) $position['job_title']['id'];
			$this->model_positions_of_users->position_from = ( isset( $position['from'] ) ) ? $position['from'] : null;
			$this->model_positions_of_users->position_to   = ( isset( $position['to'] ) ) ? $position['to'] : null;

			if ( $this->model_positions_of_users->is_position_exists() > 0 ) {
				$this->response( [
					'status'  => false,
					'message' => 'Similar position was found'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Add the position to the database
				$this->model_positions_of_users->position_current = $this->has_param( $position, 'current', 0 );

				// Industry
				if ( array_key_exists( 'industry', $position ) ) {
					$this->model_positions_of_users->industry_id = $this->has_param( $position['industry'], 'id' );
				}

				// Location
				if ( array_key_exists( 'location', $position ) ) {
					$this->load->model('model_location');
					$city_id = $this->model_location->get_city_id($position['location'], false);
					$this->model_positions_of_users->city_id = $city_id;
				}

				// Seniority
				if ( array_key_exists( 'seniority', $position ) ) {
					$this->model_positions_of_users->seniority_id = $this->has_param( $position['seniority'], 'id' );
				}

				// Position type
				$this->model_positions_of_users->position_type = $this->has_param( $position, 'type' );

				// Position salary
				if ($this->has_param( $position, 'salary' ) && strlen($position['salary']) > 0) {
					$this->model_positions_of_users->position_salary = $this->has_param( $position, 'salary' );
				}

				// Position salary period
				$this->model_positions_of_users->position_salary_period = $this->has_param( $position, 'salary_period' );

				// Start a database transaction
				$this->db->trans_start();

				// Creating a new position record
				$this->model_positions_of_users->create();

				$error_message = $this->get_transaction_error( $error_message );

				// Parsing the areas of focus
				if ( array_key_exists( 'areas_of_focus', $position ) && ! is_null( $this->model_positions_of_users->positions_of_users_id ) ) {
					if ( count( $position['areas_of_focus'] ) ) {
						// Batch insert the areas of focus
						$areas_of_focus_ids = array_map( create_function( '$o', 'return $o["id"];' ), $position['areas_of_focus'] );

						$this->load->model( 'model_areas_of_focus_of_positions_of_users' );
						$this->model_areas_of_focus_of_positions_of_users->position_of_users_id = $this->model_positions_of_users->positions_of_users_id;
						$this->model_areas_of_focus_of_positions_of_users->batch_create( $areas_of_focus_ids );
					}
				}

				$error_message = $this->get_transaction_error( $error_message );

				$this->db->trans_complete();

				if ( $this->db->trans_status() === false ) {
					// Database transaction failed
					$this->response( [
						'status'  => false,
						'message' => $error_message
					], Base_Controller::HTTP_BAD_REQUEST );
				} else {
					$this->response( [
						'status'  => true,
						'message' => $this->model_positions_of_users->positions_of_users_id
					], Base_Controller::HTTP_OK );
				}
			}
		}
	}


	/**
	 * experience_put
	 *
	 * Update a user's experience object
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function experience_put( $user_id, $position_id ) {
		$valid          = true;
		$form_validated = true;
		$message        = '';
		$error_message  = false;

		$position = $this->request->body;

		// Validating for profile variable types
		$position = ( is_string( $position ) ) ? json_decode( $position, true ) : $position;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid   = false;
			$message = 'Bad request';
			$code    = Base_Controller::HTTP_BAD_REQUEST;
		} /*else if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid   = false;
			$message = 'Not authorzied';
			$code    = Base_Controller::HTTP_UNAUTHORIZED;
		} */
		else if ( is_null( $position ) ) {
			$valid   = false;
			$message = 'Bad request';
			$code    = Base_Controller::HTTP_BAD_REQUEST;
		} else {
			$form_validated = $this->validateRequestParameters( $position, array(
				'salary',
				'salary_period',
				'to',
				'from_required',
				'type',
				'company[id]',
				'location[city_name]',
				'industry[id]',
				'job_title[id]',
				'seniority[id]'
			) );

			$position['current'] = $this->has_param( $position, 'current', 0 );

			// Verify that a position_to is set or the position_current equal 1
			if ( ! isset( $position['to'] ) && $position['current'] == 0 ) {
				$form_validated->result   = false;
				$form_validated->errors[] = array(
					'field' => 'to',
					'error' => $this->form_validation->compile( 'to', $this->lang->line( 'to_not_isset' ) )
				);
			}

			// Position type validation
			if ( isset( $position['type'] ) && ! is_null( $position['type'] ) ) {
				$this->config->load( 'enums' );
				if ( ! $this->form_validation->in_list( $position['type'], implode( $this->config->item( 'enums' )->employment_type, ',' ) ) ) {
					$form_validated->result   = false;
					$form_validated->errors[] = array(
						'field' => 'type',
						'error' => $this->form_validation->compile( 'type', $this->lang->line( 'in_list' ) )
					);
				}
			}

			// Position from vs position to values
			if ( isset( $position['to'] ) && strtotime( $position['to'] ) < strtotime( $position['from'] ) ) {
				$form_validated->result   = false;
				$form_validated->errors[] = array(
					'field' => 'to',
					'error' => $this->form_validation->compile( 'to', $this->lang->line( 'to_lte_from' ) )
				);
			}

			// Validation for area_of focus length
			$this->config->load( 'form_validation' );
			if ( array_key_exists( 'areas_of_focus', $position ) ) {
				if ( count( $position['areas_of_focus'] ) > (int) $this->config->item( 'aof_max_count' ) ) {
					$form_validated->result   = false;
					$form_validated->errors[] = array(
						'field' => 'areas_of_focus',
						'error' => $this->form_validation->compile( 'areas_of_focus', $this->lang->line( 'aof_max_count' ) )
					);
				}
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => $message
			], $code );
		} else if ( ! $form_validated->result ) {
			$this->response( [
				'status'  => false,
				'message' => $form_validated->errors
			], Base_Controller::HTTP_NOT_ACCEPTABLE );
		} else {
			$this->load->model( 'model_positions_of_users' );
			$this->model_positions_of_users->positions_of_users_id = $position_id;
			$this->model_positions_of_users->user_id               = $user_id;
			$this->model_positions_of_users->company_id            = $position['company']['id'];
			$this->model_positions_of_users->job_title_id          = $position['job_title']['id'];
			$this->model_positions_of_users->position_from         = ( isset( $position['from'] ) ) ? $position['from'] : null;
			$this->model_positions_of_users->position_to           = ( isset( $position['to'] ) ) ? $position['to'] : null;

			// Add the position to the database
			$this->model_positions_of_users->position_current = $this->has_param( $position, 'current', 0 );

			// Industry
			if ( array_key_exists( 'industry', $position ) ) {
				$this->model_positions_of_users->industry_id = $this->has_param( $position['industry'], 'id' );
			}

			// Location
			if ( array_key_exists( 'location', $position ) ) {
				$this->load->model('model_location');
				$city_id = $this->model_location->get_city_id($position['location'], false);
				$this->model_positions_of_users->city_id = $city_id;
			}

			// Seniority
			if ( array_key_exists( 'seniority', $position ) ) {
				$this->model_positions_of_users->seniority_id = $this->has_param( $position['seniority'], 'id' );
			}

			// Position type
			$this->model_positions_of_users->position_type = $this->has_param( $position, 'type' );

			// Position salary
			if ($this->has_param( $position, 'salary' ) && strlen($position['salary']) > 0) {
				$this->model_positions_of_users->position_salary = $this->has_param( $position, 'salary' );
			}

			// Position salary period
			$this->model_positions_of_users->position_salary_period = $this->has_param( $position, 'salary_period' );

			// Start a database transaction
			$this->db->trans_start();

			// Update the position object position record
			$this->model_positions_of_users->update();

			$error_message = $this->get_transaction_error( $error_message );

			// Deleting the area of focus
			$this->load->model( 'model_areas_of_focus_of_positions_of_users' );
			$this->model_areas_of_focus_of_positions_of_users->position_of_users_id = $position_id;
			$this->model_areas_of_focus_of_positions_of_users->soft_delete();

			$error_message = $this->get_transaction_error( $error_message );

			// Parsing the areas of focus
			if ( array_key_exists( 'areas_of_focus', $position ) && ! is_null( $position_id ) ) {
				if ( count( $position['areas_of_focus'] ) ) {
					// Batch insert the areas of focus
					$areas_of_focus_ids = array_map( create_function( '$o', 'return $o["id"];' ), $position['areas_of_focus'] );
					$this->model_areas_of_focus_of_positions_of_users->batch_create( $areas_of_focus_ids );
					$error_message = $this->get_transaction_error( $error_message );
				}
			}

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $error_message
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}


	/**
	 * experience_delete
	 *
	 * Remove a a position from the user's experience
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function experience_delete( $user_id, $position_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Available only to the requesting user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Soft delete the position
			$this->load->model( 'model_positions_of_users' );
			$this->model_positions_of_users->user_id               = $user_id;
			$this->model_positions_of_users->positions_of_users_id = $position_id;
			$deleted                                               = $this->model_positions_of_users->soft_delete();

			if ( ! $deleted ) {
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
	 * traits_get
	 *
	 * Get a user's traits collection
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function traits_get( $user_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_traits' );
			$this->load->model( 'model_traits_of_users' );

			$this->model_traits_of_users->user_id = $user_id;
			$traits                               = $this->model_traits_of_users->get();

			$this->response( $traits, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * traits_post
	 *
	 * Add traits to the user
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function traits_post( $user_id ) {
		$valid         = true;
		$error_message = false;

		$traits = $this->request->body;

		$traits = ( is_string( $traits ) ) ? json_decode( $traits, true ) : $traits;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Only the current user and the admin can create traits
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		/// Set the traits prominence according to the collection item index
		foreach ( $traits as $index => &$trait ) {
			$trait['prominance'] = $index + 1;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_traits_of_users' );
			$this->model_traits_of_users->user_id = $user_id;

			// Start a database transaction
			$this->db->trans_start();

			// Soft delete user's traits
			$this->model_traits_of_users->delete_traits();

			$error_message = $this->get_transaction_error( $error_message );

			// un-delete / insert new traits and set updated prominance
			$this->model_traits_of_users->insert_undelete_traits( $traits );

			$error_message = $this->get_transaction_error( $error_message );

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $error_message
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Success
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}


	/**
	 * traits_put
	 *
	 * Update a user's traits
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function traits_put( $user_id ) {
		$valid         = true;
		$error_message = false;

		$traits = $this->request->body;

		$traits = ( is_string( $traits ) ) ? json_decode( $traits, true ) : $traits;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Only the current user and the admin can create traits
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		// Set the traits prominence according to the collection item index
		foreach ( $traits as $index => &$trait ) {
			$trait['prominance'] = $index + 1;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_traits_of_users' );
			$this->model_traits_of_users->user_id = $user_id;

			// Start a database transaction
			$this->db->trans_start();

			// Soft delete user's traits
			$this->model_traits_of_users->delete_traits();

			$error_message = $this->get_transaction_error( $error_message );

			// un-delete / insert new traits and set updated prominance
			$this->model_traits_of_users->insert_undelete_traits( $traits );

			$error_message = $this->get_transaction_error( $error_message );

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $error_message
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Success
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * languages_get
	 *
	 * Get a user's languages collection
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function languages_get( $user_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_languages' );
			$this->load->model( 'model_languages_of_users' );

			$this->model_languages_of_users->user_id = $user_id;
			$languages = $this->model_languages_of_users->get();

			$this->response( $languages, Base_Controller::HTTP_OK );
		}
	}


	/**
	 * languages_delete
	 *
	 * Update a user's language
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function languages_delete( $user_id, $language_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		if ( $valid === true ) {
			// Checking that the language belongs to the user and the level is different than the current
			$this->load->model( 'model_languages_of_users' );
			$this->model_languages_of_users->user_id     = $user_id;
			$this->model_languages_of_users->language_id = $language_id;
			$language_level                              = $this->model_languages_of_users->get_user_language();

			if ( $language_level === false ) {
				$valid = false;
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Perform a soft delete
			$this->load->model( 'model_languages_of_users' );
			$this->model_languages_of_users->user_id     = $user_id;
			$this->model_languages_of_users->language_id = $language_id;
			$this->model_languages_of_users->soft_delete();

			$this->response( [
				'status' => true
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * languages_post
	 *
	 * Create a user's language
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function languages_post( $user_id ) {
		$valid          = true;
		$form_validated = true;

		$language = $this->request->body;

		$language = ( is_string( $language ) ) ? json_decode( $language, true ) : $language;

		if ( is_null( $language ) ) {
			$valid = false;
		}

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		$form_validated = $this->validateRequestParameters( $language, array(
			'level'
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
			// Insert / un-delete the language to the user's profile
			$this->load->model( 'model_languages_of_users' );
			$this->model_languages_of_users->user_id        = $user_id;
			$this->model_languages_of_users->language_id    = $language['id'];
			$this->model_languages_of_users->language_level = $language['level'];
			$this->model_languages_of_users->insert_undelete_language();

			$this->response( $this->model_languages_of_users->language_of_user_id, Base_Controller::HTTP_OK );
		}
	}


	/**
	 * languages_put
	 *
	 * Update a user's language
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @param integer language_id
	 *
	 * @return    void
	 */
	public function languages_put( $user_id, $language_id ) {
		$valid = true;

		// Get the required level
		$level = $this->request->body;

		$level = ( is_array( $level ) ) ? $level[0] : $level;
		$level = ( is_string( $level ) ) ? trim( $level, '"' ) : level;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		$this->load->library( 'form_validation' );
		if ( ! $this->form_validation->numeric( $level ) ) {
			$valid = false;
		}

		if ( ! $this->form_validation->in_list( $level, '1,2,3' ) ) {
			$valid = false;
		}

		if ( $valid === true ) {
			// Checking that the language belongs to the user and the level is different than the current
			$this->load->model( 'model_languages_of_users' );
			$this->model_languages_of_users->user_id     = $user_id;
			$this->model_languages_of_users->language_id = $language_id;
			$language_level                              = $this->model_languages_of_users->get_user_language();

			if ( $language_level === false || $language_level == $level ) {
				$valid = false;
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->model_languages_of_users->language_level = $level;
			$this->model_languages_of_users->update_level();

			$this->response( [
				'status' => true
			], Base_Controller::HTTP_OK );
		}
	}


	/**
	 * education_get
	 *
	 * Get a user's education collection
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function education_get( $user_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_education' );
			$this->load->model( 'model_fields_of_study' );
			$this->load->model( 'model_schools_of_users' );

			$this->model_schools_of_users->user_id = $user_id;
			$schools                               = $this->model_schools_of_users->get();

			$this->response( $schools, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * education_post
	 *
	 * Add education to the user's
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function education_post( $user_id ) {
		$valid         = true;
		$error_message = false;

		$education = $this->request->body;

		$education = ( is_string( $education ) ) ? json_decode( $education, true ) : $education;

		if ( is_null( $education ) ) {
			$valid = false;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $user_id );

			// Checking that the user has activated his account
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}

			// Checking that the user is visible in search results
			if ( $this->model_users->search_visible == 0 ) {
				$valid = false;
			}

			// Only the current user and the admin can perform this operation
			if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
				$valid = false;
			}

			// Input parameters validation
			$form_validated = $this->validateRequestParameters( $education, array(
				'from',
				'to',
				'education_level_name',
				'education_level_id',
				'current'
			) );

			// Position from vs position to values
			if ( isset( $education['to'] ) && isset( $education['from'] ) && strtotime( $education['to'] ) < strtotime( $education['from'] ) ) {
				$form_validated->result   = false;
				$form_validated->errors[] = array(
					'field' => 'to',
					'error' => $this->lang->line( 'to_lte_from' )
				);
			}
		}

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
			$this->load->model( 'model_schools_of_users' );
			$this->model_schools_of_users->user_id                = $user_id;
			$this->model_schools_of_users->school_id              = $education['school_id'];
			$this->model_schools_of_users->school_from            = $education['from'];
			$this->model_schools_of_users->school_to              = ( isset( $education['to'] ) ) ? $education['to'] : null;
			$this->model_schools_of_users->school_current         = ( isset( $education['current'] ) ) ? $education['current'] : 0;
			$this->model_schools_of_users->school_education_level = $education['level']['id'];

			// Start a database transaction
			$this->db->trans_start();

			// Create the school record
			$this->model_schools_of_users->create();

			$error_message = $this->get_transaction_error( $error_message );

			// Create the fields of study in the new school
			if ( isset( $education['fields'] ) ) {
				$this->load->model( 'model_fields_of_study_of_schools_of_users' );
				$this->model_fields_of_study_of_schools_of_users->schools_of_user_id = $this->model_schools_of_users->schools_of_user_id;
				$this->model_fields_of_study_of_schools_of_users->insert_fields_of_study( $education['fields'] );
			}

			$error_message = $this->get_transaction_error( $error_message );

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $error_message
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( $this->model_fields_of_study_of_schools_of_users->schools_of_user_id, Base_Controller::HTTP_OK );
			}
		}
	}


	/**
	 * education_put
	 *
	 * Update a user's education
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function education_put( $user_id, $education_id ) {
		$valid          = true;
		$form_validated = true;
		$error_message  = false;

		$education = $this->request->body;
		$education = ( is_string( $education ) ) ? json_decode( $education, true ) : $education;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Only the current user and the admin can create traits
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		// Validate the education object
		$form_validated = $this->validateRequestParameters( $education, array(
			'school_id',
			'from_required',
			'to',
			'current',
			'education_level_name',
			'education_level_id'
		) );

		// Position from vs position to values
		if ( isset( $education['to'] ) && isset( $education['from'] ) && strtotime( $education['to'] ) < strtotime( $education['from'] ) ) {
			$form_validated->result   = false;
			$form_validated->errors[] = array(
				'field' => 'to',
				'error' => $this->lang->line( 'to_lte_from' )
			);
		}

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
			// Load the schools of users model
			$this->load->model( 'model_schools_of_users' );
			$this->model_schools_of_users->schools_of_user_id     = $education_id;
			$this->model_schools_of_users->user_id                = $user_id;
			$this->model_schools_of_users->school_id              = $education['school_id'];
			$this->model_schools_of_users->school_from            = $education['from'];
			$this->model_schools_of_users->school_to              = $education['to'];
			$this->model_schools_of_users->school_current         = $education['current'];
			$this->model_schools_of_users->school_education_level = $education['level']['id'];

			// Load the fields of study model
			$this->load->model( 'model_fields_of_study_of_schools_of_users' );
			$this->model_fields_of_study_of_schools_of_users->schools_of_user_id = $education_id;

			// Load the user's profile model
			$this->load->model( 'model_profiles' );
			$this->model_profiles->user_id = $user_id;

			// Start a database transaction
			$this->db->trans_start();

			// Update the education record
			$this->model_schools_of_users->update();

			$error_message = $this->get_transaction_error( $error_message );

			// Soft delete the fields of study
			$this->model_fields_of_study_of_schools_of_users->soft_delete();

			$error_message = $this->get_transaction_error( $error_message );

			// Insert / un-undelete the fields of study for the current school record
			$this->model_fields_of_study_of_schools_of_users->insert_fields_of_study( $education['fields'] );

			$error_message = $this->get_transaction_error( $error_message );

			// Update the profile's last_updated parameter
			$this->model_profiles->update_last_updated();

			$error_message = $this->get_transaction_error( $error_message );

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $error_message
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Success
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}


	/**
	 * education_delete
	 *
	 * Remove an education object from the user's educations
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function education_delete( $user_id, $education_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Available only to the requesting user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Soft deleting the school of the user
			$this->load->model( 'model_schools_of_users' );
			$this->model_schools_of_users->user_id            = $user_id;
			$this->model_schools_of_users->schools_of_user_id = $education_id;
			$deleted                                          = $this->model_schools_of_users->soft_delete();

			if ( $deleted ) {
				// Soft deleting the fields of study
				$this->load->model( 'model_fields_of_study_of_schools_of_users' );
				$this->model_fields_of_study_of_schools_of_users->schools_of_user_id = $education_id;
				$this->model_fields_of_study_of_schools_of_users->soft_delete();
			}

			if ( ! $deleted ) {
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
	 * statistics_get
	 *
	 * Get a user's statistics object
	 *
	 * @access    public
	 *
	 * @role    applicant, admin
	 *
	 * @return    Object
	 */
	public function statistics_get( $user_id ) {
		$valid = true;

		// Get the request parameters
		$since = $this->get( 'since' );

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Return response only to the requesting applicant and the system administrator
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->library( 'Upload' );
			// Count the profile views by recruiters (with optional since parameters)
			$this->load->model( 'model_business_user_views' );
			$this->model_business_user_views->user_id = $user_id;
			$business_views = $this->model_business_user_views->get_views_count_by_business( $since );

			// Calculate the completion status of the profile
			$this->load->model( 'model_user_data' );
			$this->model_user_data->user_id = $user_id;
			$profile_completion = $this->model_user_data->get_completion_status();

			// Count the statuses of the applicant as marked by the businesses
			$this->load->model( 'model_business_applicant_status' );
			$this->model_business_applicant_status->user_id = $user_id;
			$statuses_count                                 = $this->model_business_applicant_status->get_statuses_count( $since );

			// Count the profile views by continents
			$your_views = $this->model_business_user_views->get_views_by_continents( $since );

			// Output to the client
			$this->response( [
				'businessViews'     => $business_views,
				'profileCompletion' => $profile_completion,
				'yourStatus'        => $statuses_count,
				'yourViews'         => $your_views
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * statistics_post
	 *
	 * Add to a user's view count
	 *
	 * @access    public
	 *
	 * @role    applicant, admin
	 *
	 * @return    Object
	 */
	public function statistics_post( $user_id ) {
		$valid = true;

		// Get the request parameters
		$add_user_id = $this->post( 'user_id' );

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Don't allow the current user to add view counts to himself
		if ( $this->get_active_user() == $user_id || $this->is_admin() ) {
			$valid = false;
		}

		// Validate that only a business user can view the profile
		$this->load->model( 'model_business_user_views' );
		$this->model_business_user_views->user_id = $user_id;
		if ( $this->is_recruiter() ) {
			$this->model_business_user_views->business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$this->model_business_user_views->business_id = $this->get_manager_business();
		} else {
			$valid = false;
		}

		// Get the location of the active business
		$this->load->model( 'model_business' );
		$this->load->model( 'model_location' );
		$this->model_business->business_id = $this->model_business_user_views->business_id;
		$business                          = $this->model_business->get();

		// Could not locate to business object
		if ( ! $business ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Set the continent id in the count object
			$this->model_business_user_views->continent_id = $business->continent_id;

			// Count the profile views by recruiters (with optional since parameters)
			$this->model_business_user_views->add();

			$this->response( [
				'status' => true
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * views_post
	 *
	 * Add a view count based on IP2Country resolution
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function views_post( $user_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		$country = ( isset( $_SERVER['HTTP_X_APPENGINE_COUNTRY'] ) ) ? $_SERVER['HTTP_X_APPENGINE_COUNTRY'] : false;

		if ( ! $valid || ! $country ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Get the continent from which the request originated
			$this->load->model( 'model_countries' );
			$this->model_countries->country_short_name_alpha_2 = $country;
			$row                                               = $this->model_countries->get_continent_from_alpha_2();

			// Log the public profile view
			$this->load->model( 'model_users_views' );
			$this->model_users_views->user_id      = $user_id;
			$this->model_users_views->continent_id = $row->continent_id;
			$this->model_users_views->add();

			$this->response( [
				'status' => true
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * requests_post
	 *
	 * Send a request to a specific user
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function requests_post( $user_id ) {
		$valid          = true;
		$form_validated = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Validate the input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'company_name',
			'fullname',
			'email',
			'contact_request_message'
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
			$phone = $this->has_param( $this->post(), 'phone', '' );
			$message = $this->has_param( $this->post(), 'message', '' );

			// Save the request in the database
			$this->load->model( 'model_user_requests' );
			$this->model_user_requests->user_id  = $user_id;
			$this->model_user_requests->fullname = $this->post( 'fullname' );
			$this->model_user_requests->email    = $this->post( 'email' );
			$this->model_user_requests->phone    = $this->post( 'phone' );
			$this->model_user_requests->company  = $this->post( 'company' );
			$this->model_user_requests->message  = $this->post( 'message' );

			$this->model_user_requests->save();

			// Send the request to the requested user
			// Load the template parser class
			$this->load->library( 'Template' );

			// Load the language file with the content of the email
			$this->lang->load( 'email' );
			$email_data = $this->lang->line( 'contact_request' );

			$email_data['subtitle'] = $email_data['message'] . " From " . $this->post( 'fullname' );

			if ( strlen( $message ) > 0 ) {
				$email_data['message'] = $message;
			} else {
				$email_data['message'] = "";
			}

			// Add technical details to the email
			$email_data['details'] = "";
			$email_data['details'] .= "From: " . $this->post( 'fullname' ) . "<br>";
			$email_data['details'] .= "Company: " . $this->post( 'company' ) . "<br>";
			$email_data['details'] .= "Email: " . $this->post( 'email' ) . "<br>";
			$email_data['details'] .= "Phone: " . $phone . "<br>";

			$html = $this->template->view( 'contact_request', $email_data, true );

			// Load the Mailgun library wrapper
			$this->load->library( 'Mailgun' );

			// Extract the textual version from the html body version
			$text = $this->mailgun->get_text_version( $html );

			// Load the Mailgun library wrapper
			$this->mailgun->html    = $html;
			$this->mailgun->to      = $this->model_users->email;
			$this->mailgun->body    = $text;
			$this->mailgun->subject = $email_data['subject'];
			$sent                   = $this->mailgun->send();

			if (!$sent ) {
				$this->response( [
					'status' => false,
					'message' => 'Messaging service is currently unavailable'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * tech_get
	 *
	 * Get a user's technical skills collection
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function tech_get( $user_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_technical_abilities' );
			$this->load->model( 'model_technical_abilities_of_users' );

			$this->model_technical_abilities_of_users->user_id = $user_id;
			$tech_skills                                       = $this->model_technical_abilities_of_users->get();

			$this->response( $tech_skills, Base_Controller::HTTP_OK );
		}
	}


	/**
	 * tech_post
	 *
	 * Add a new technical skills to the user's profile
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function tech_post( $user_id ) {
		$valid          = true;
		$form_validated = true;

		$skill = $this->request->body;

		$skill = ( is_string( $skill ) ) ? json_decode( $skill, true ) : $skill;

		if ( is_null( $skill ) ) {
			$valid = false;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $user_id );

			// Checking that the user has activated his account
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}

			// Checking that the user is visible in search results
			if ( $this->model_users->search_visible == 0 ) {
				$valid = false;
			}

			// Available only to the requesting user
			if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
				$valid = false;
			}

			if ( $valid ) {
				// Validate the input parameters
				$form_validated = $this->validateRequestParameters( $skill, array(
					'entityId',
					'skill_level'
				) );
			}
		}

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
			$this->load->model( 'model_technical_abilities_of_users' );
			$this->model_technical_abilities_of_users->user_id                 = $user_id;
			$this->model_technical_abilities_of_users->technical_ability_id    = $skill['id'];
			$this->model_technical_abilities_of_users->technical_ability_level = $skill['level'];
			$this->model_technical_abilities_of_users->insert_undelete_skill();

			$this->response( $this->model_technical_abilities_of_users->technical_ability_of_user_id, Base_Controller::HTTP_OK );
		}
	}


	/**
	 * tech_put
	 *
	 * Update a user's technical skill level
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function tech_put( $user_id, $technical_skill_id ) {
		$valid          = true;
		$form_validated = true;

		// Get the required level
		$level = $this->request->body;

		$level = ( is_array( $level ) ) ? $level[0] : $level;
		$level = ( is_string( $level ) ) ? trim( $level, '"' ) : $level;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		// Validate the skill level
		if ( $valid ) {
			// Validate the input parameters
			$form_validated = $this->validateRequestParameters( array( 'level' => $level ), array(
				'skill_level'
			) );
		}

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
			$this->load->model( 'model_technical_abilities_of_users' );
			$this->model_technical_abilities_of_users->technical_ability_id    = $technical_skill_id;
			$this->model_technical_abilities_of_users->user_id                 = $user_id;
			$this->model_technical_abilities_of_users->technical_ability_level = $level;
			$updated                                                           = $this->model_technical_abilities_of_users->update_level();

			if ( ! $updated ) {
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
	 * tech_delete
	 *
	 * Remove a technical ability from the user's profile
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function tech_delete( $user_id, $technical_skill_id ) {
		$valid         = true;
		$error_message = false;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Available only to the requesting user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Load the technical abilities of users model
			$this->load->model( 'model_technical_abilities_of_users' );
			$this->model_technical_abilities_of_users->user_id              = $user_id;
			$this->model_technical_abilities_of_users->technical_ability_id = $technical_skill_id;

			// Start a database transaction
			$this->db->trans_start();

			$deleted = $this->model_technical_abilities_of_users->soft_delete();

			$error_message = $this->get_transaction_error( $error_message );

			if ( $deleted ) {
				$this->update_profile_updated( $user_id );
				$error_message = $this->get_transaction_error( $error_message );
			}

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				$this->response( [
					'status'  => false,
					'message' => $error_message
				], Base_Controller::HTTP_BAD_REQUEST );
			} else if ( ! $deleted ) {
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
	 * blocked_get
	 *
	 * Get a user's blocked companies collection
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function blocked_get( $user_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Available only to the requesting user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_user_status' );
			$this->model_user_status->user_id = $user_id;
			$this->model_user_status->load();

			if ( $this->model_user_status->user_block_companies == 0 ) {
				// Get the black list of the user's blocked companies
				$this->load->model( 'model_blocked_businesses' );
				$this->model_blocked_businesses->user_id = $user_id;
				$businesses                              = $this->model_blocked_businesses->get();
			} else {
				// Get the white list of the user's allowed companies
				$this->load->model( 'model_allowed_businesses' );
				$this->model_allowed_businesses->user_id = $user_id;
				$businesses                              = $this->model_allowed_businesses->get();
			}

			$this->response( [
				'block_all' => $this->model_user_status->user_block_companies,
				'companies' => $businesses
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * blocked_put
	 *
	 * Update a user's education
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function blocked_put( $user_id ) {
		$valid          = true;
		$form_validated = true;

		$block_all = $this->request->body;

		$block_all = ( is_array( $block_all ) ) ? $block_all[0] : $block_all;
		$block_all = ( is_string( $block_all ) ) ? json_decode( $block_all, true ) : $block_all;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Only the current user and the admin can create traits
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		// Validate the education object
		$form_validated = $this->validateRequestParameters( array( 'block_all' => $block_all ), array(
			'block_all'
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
			$this->load->model( 'model_user_status' );
			$this->model_user_status->user_id              = $user_id;
			$this->model_user_status->user_block_companies = $block_all;
			$updated                                       = $this->model_user_status->update_blocked_companies();

			if ( ! $updated ) {
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
	 * blocked_delete
	 *
	 * Remove a blocked business from the user's blocked businsses
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function blocked_delete( $user_id, $business_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Available only to the requesting user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_user_status' );
			$this->model_user_status->user_id = $user_id;
			$this->model_user_status->load();

			if ( $this->model_user_status->user_block_companies == 0 ) {
				// Deleting from the blocked companies black list
				$this->load->model( 'model_blocked_businesses' );
				$this->model_blocked_businesses->user_id     = $user_id;
				$this->model_blocked_businesses->business_id = $business_id;
				$deleted                                     = $this->model_blocked_businesses->soft_delete();
			} else {
				// Deleting from the allowed companies white list
				$this->load->model( 'model_allowed_businesses' );
				$this->model_allowed_businesses->user_id     = $user_id;
				$this->model_allowed_businesses->business_id = $business_id;
				$deleted                                     = $this->model_allowed_businesses->soft_delete();
			}

			if ( ! $deleted ) {
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
	 * blocked_post
	 *
	 * Add a business to the user's blocked blocked businesses
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function blocked_post( $user_id, $business_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Checking that the user is visible in search results
		if ( $this->model_users->search_visible == 0 ) {
			$valid = false;
		}

		// Available only to the requesting user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_user_status' );
			$this->model_user_status->user_id = $user_id;
			$this->model_user_status->load();

			if ( $this->model_user_status->user_block_companies == 0 ) {
				// Insert / un-delete the blocked business
				$this->load->model( 'model_blocked_businesses' );
				$this->model_blocked_businesses->user_id     = $user_id;
				$this->model_blocked_businesses->business_id = $business_id;
				$this->model_blocked_businesses->insert_undelete_blocked();
			} else {
				// Insert / un-delete the allowed business
				$this->load->model( 'model_allowed_businesses' );
				$this->model_allowed_businesses->user_id     = $user_id;
				$this->model_allowed_businesses->business_id = $business_id;
				$this->model_allowed_businesses->insert_undelete_blocked();
			}

			$this->response( [
				'status' => true
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * signup_post
	 *
	 * Signup user to the platform
	 *
	 * @access    public
	 *
	 * @role    guest
	 *
	 * @return    void
	 */
	public function signup_post() {
		$valid             = true;
		$form_validated    = true;
		$error_message     = false;
		$need_activation   = true;
		$sent              = true;
		$valid_credentials = false;
		$invite_exists     = false;
		$credentials_exist = false;
		$businesses        = [];

		// Validate the input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'firstname',
			'lastname',
			'email',
			'password',
			'role',
			'apply'
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
			// Get the POST variables
			$firstname = $this->post( 'firstname' );
			$lastname  = $this->post( 'lastname' );
			$email     = $this->post( 'email' );
			$password  = $this->post( 'password' );
			$role      = $this->post( 'role' );
			$invite    = $this->post( 'invite' );
			$apply     = $this->post( 'apply' );

			// Check if the user is registering as a recruiter and an invite token is present in the request parameters
			if ( is_string( $invite ) && $this->is_recruiter( $role ) ) {
				// Create an invitation for the recruiter
				$this->load->model( 'model_invitations_of_business' );
				$this->model_invitations_of_business->token = $invite;
				$invite_exists                              = $this->model_invitations_of_business->get_by_token();

				// If the user is signing up with the same email that he been invited to skip the activation
				if ( $invite_exists && $this->model_invitations_of_business->email == $email ) {
					$need_activation = true;
				}
			}
			// Process for existing user entering his own credentials
			$this->load->model( 'model_users' );
			$this->model_users->email = $email;
			$is_platform_email        = $this->model_users->get_by_email_login();

			if ( $is_platform_email ) {
				$credentials_exist = true;

				$this->response( [
					'status'  => false,
					'message' => 'User exists, please login'
				], Base_Controller::HTTP_BAD_REQUEST );
			}

			if ( ! $credentials_exist ) {
				if ( $valid_credentials ) {
					// Associate the user to the businesses which invited him
					if ( $invite_exists ) {
						// Get invites for the email which the token is sent to
						$invites = $this->model_invitations_of_business->get_invites_by_email();

						// Associate to pending invites to the current recruiter
						$this->load->model( 'model_business_users' );
						$this->model_business_users->user_id = $this->model_users->user_id;
						$this->model_business_users->associate( $invites );

						// Mark the invites as accepted
						$this->model_invitations_of_business->accept_invitations( $invites );

					} else if ( is_null( $invite ) && $this->is_recruiter( $role ) ) {
						// Get the pending invites for the given user
						$this->load->model( 'model_invitations_of_business' );
						$this->model_invitations_of_business->email = $email;
						$invites                                    = $this->model_invitations_of_business->get_invites_by_email();

						// Associate to pending invites to the current recruiter
						$this->load->model( 'model_business_users' );
						$this->model_business_users->user_id = $this->model_users->user_id;
						$this->model_business_users->associate( $invites );

						// Mark the invites as accepted
						$this->model_invitations_of_business->accept_invitations( $invites );
					}

					if ( $this->is_recruiter( $role ) ) {
						$this->load->model( 'model_business_users' );
						$this->model_business_users->user_id = $this->model_users->user_id;
						$businesses                          = $this->model_business_users->get_recruiter_businesses();
					}

					// Check if the user requested an application to a business
					if ( ! is_null( $apply ) && $this->is_applicant( $role ) ) {
						$this->load->model( 'model_applicants_of_business' );
						$this->model_applicants_of_business->user_id     = $this->model_users->user_id;
						$this->model_applicants_of_business->business_id = $apply;
						$this->model_applicants_of_business->verified    = ( $this->model_users->status == 'active' );
						$this->model_applicants_of_business->create();
					}

					// Build the response object
					$response = [
						'key'                => $auth->key,
						'role'               => $this->model_users->role,
						'active_user_id'     => null,
						'active_business_id' => null,
						'user_id'            => $this->model_users->user_id,
						'business_id'        => $auth->active_business_id,
						'status'             => $this->model_users->status,
						'businesses'         => $businesses
					];

					// Overwrite the active parameters if the user's role is admin
					if ( $this->is_admin( $this->model_users->role ) ) {
						$response['active_user_id']     = $auth->active_user_id;
						$response['active_business_id'] = $auth->active_business_id;
					}

					// Respond to the client
					$this->response( $response, Base_Controller::HTTP_OK );
				} else {
					// Start a database transaction
					$this->db->trans_begin();

					// Creating the user entity
					$this->load->model( 'model_users' );
					$this->model_users->email    = $email;
					$this->model_users->password = password_hash( $password, PASSWORD_BCRYPT );
					$this->model_users->role     = $role;

					// If the user does not have to activate his account we can register him as an activated user
					if ( ! $need_activation ) {
						$this->model_users->verified = 1;
						$this->model_users->status   = 'active';
					}

					if($this->is_applicant( $role )) {
						$this->model_users->search_visible = 1;
					}
					if($this->is_manager($role)){
						$this->model_users->verified_by_admin =0;
					}
					if($this->is_recruiter($role)){
						$this->model_users->verified_by_admin =0;
					}
					$this->model_users->create();

					$error_message = $this->get_transaction_error( $error_message );

					// Associate the user to the businesses which invited him
					if ( $invite_exists ) {
						// Get invites for the email which the token is sent to
						$invites = $this->model_invitations_of_business->get_invites_by_email();

						// Associate to pending invites to the current recruiter
						$this->load->model( 'model_business_users' );
						$this->model_business_users->user_id = $this->model_users->user_id;
						$this->model_business_users->associate( $invites );

						// Mark the invites as accepted
						$this->model_invitations_of_business->accept_invitations( $invites );

						// Set the active_business_id of the recruiter is not been assigned yet
					/*	if ( count( $invites ) > 0 ) {
							$this->load->library( 'Auth' );
							$this->auth->user_id = $this->model_users->user_id;
							$auth                = $this->auth->get();

							if ( is_null( $auth->active_business_id ) ) {
								$this->auth->key                = $auth->key;
								$this->auth->active_business_id = $invites[0]->business_id;
								$this->auth->update_active_business();
							}
						}*/
					} else if ( is_null( $invite ) && $this->is_recruiter( $role ) ) {
						// Get the pending invites for the given user
						$this->load->model( 'model_invitations_of_business' );
						$this->model_invitations_of_business->email = $email;
						$invites                                    = $this->model_invitations_of_business->get_invites_by_email();

						// Associate to pending invites to the current recruiter
						$this->load->model( 'model_business_users' );
						$this->model_business_users->user_id = $this->model_users->user_id;
						$this->model_business_users->associate( $invites );

						// Mark the invites as accepted
						$this->model_invitations_of_business->accept_invitations( $invites );

						// Set the active_business_id of the recruiter is not been assigned yet
						if ( count( $invites ) > 0 ) {
							$this->load->library( 'Auth' );
							$this->auth->user_id = $this->model_users->user_id;
							$auth                = $this->auth->get();

							if ( is_null( $auth->active_business_id ) ) {
								$this->auth->key                = $auth->key;
								$this->auth->active_business_id = $invites[0]->business_id;
								$this->auth->update_active_business();
							}
						}
					}

					if ( $this->is_recruiter( $role ) ) {
						$this->load->model( 'model_business_users' );
						$this->model_business_users->user_id = $this->model_users->user_id;
						$businesses                          = $this->model_business_users->get_recruiter_businesses();
					}

					// Creating an API access key
					$this->load->library( 'Auth' );
					$this->auth->user_id = $this->model_users->user_id;
					$this->auth->role    = $role;
					$this->auth->create();

					$error_message = $this->get_transaction_error( $error_message );

					// Creating the profile entity
					$this->load->model( 'model_profiles' );
					$this->model_profiles->user_id           = $this->model_users->user_id;
					$this->model_profiles->profile_firstname = $firstname;
					$this->model_profiles->profile_lastname  = $lastname;
					$this->model_profiles->create();

					$error_message = $this->get_transaction_error( $error_message );

					// Creating user status entity
					$this->load->model( 'model_user_status' );
					$this->model_user_status->user_id = $this->model_users->user_id;
					$this->model_user_status->create();

					$error_message = $this->get_transaction_error( $error_message );

					// Disable any account activation logic for applicants only
					if ( $this->is_applicant( $role ) ) {
						$need_activation = false;
					}
					// send email to admin on business user signup
					/*if ($this->post( 'role' )=='manager' ){
						// Sending the verification email
							// Load the template parser class
							$this->load->library( 'Template' );

							// Load the language file with the content of the email
							$this->lang->load( 'email' );
							$email_data['title']='Business user signup';
							$email_data['message']='<p>Dear Admin,</p><p>New business user signup recently with email id '.$email.'</p>';
							$email_data['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved. <br> ';
							$email_data['subject']='Business user signup';
							$html = $this->template->view( 'admin_email_for_business_user', $email_data, true );

							// Load the Mailgun library wrapper
							$this->load->library( 'Mailgun' );

							// Extract the textual version from the html body version
							$text = $this->mailgun->get_text_version( $html );

							// Replace un wanted text with the link to the profile
							$text = $this->mailgun->str_replace_first( 'click confirm on the link below', 'copy and paste the link below in your web browser:', $text );
							$this->mailgun->html    = $html;
							$this->mailgun->to      = 'aryeh@findable.co';
							$this->mailgun->body    = $text;
							$this->mailgun->subject = $email_data['subject'];
							$sent = $this->mailgun->send();
						}*/
						if ( $this->is_applicant( $role ) ) {
							$need_activation = false;
						}
					// Generate an activation token if the user needs an activation
						if ( $need_activation ) {
							$this->load->model( 'model_tokens' );
							$this->model_tokens->user_id = $this->model_users->user_id;
							$this->model_tokens->type    = 'activation';
							$this->model_tokens->create();

							$error_message = $this->get_transaction_error( $error_message );
						}

						if ( $this->db->trans_status() === false ) {
						// Database transaction failed
							$this->db->trans_rollback();

							$this->response( [
								'status'  => false,
								'message' => $error_message
							], Base_Controller::HTTP_BAD_REQUEST );
						} else {
							if ( $need_activation ) {
							// Sending the verification email
							// Load the template parser class
								$this->load->library( 'Template' );

							// Load the language file with the content of the email
								$this->lang->load( 'email' );
								$email_data               = $this->lang->line( 'applicant_signup' );
								$email_data['button_url'] = base_url() . 'verify?token=' . $this->model_tokens->token;

								$html = $this->template->view( 'applicant_signup', $email_data, true );

							// Load the Mailgun library wrapper
								$this->load->library( 'Mailgun' );

							// Extract the textual version from the html body version
								$text = $this->mailgun->get_text_version( $html );

							// Replace un wanted text with the link to the profile
								$text = $this->mailgun->str_replace_first( 'click confirm on the link below', 'copy and paste the link below in your web browser:', $text );
								$text = $this->mailgun->str_replace_first( 'Confirm and continue', $email_data['button_url'], $text );

								$this->mailgun->html    = $html;
								$this->mailgun->to      = $email;
								$this->mailgun->body    = $text;
								$this->mailgun->subject = $email_data['subject'];
								$sent                   = $this->mailgun->send();
							}

							if ( ! $sent ) {
							// Rolling back the database transaction
								$this->db->trans_rollback();

								$this->response( [
									'status'  => false,
									'message' => 'Email service unavailable'
								], Base_Controller::HTTP_SERVICE_UNAVAILABLE );
							} else {
							// Check if the user requested an application to a business
								if ( ! is_null( $apply ) && $this->is_applicant( $role ) ) {
									$this->load->model( 'model_applicants_of_business' );
									$this->model_applicants_of_business->user_id     = $this->model_users->user_id;
									$this->model_applicants_of_business->business_id = $apply;
									$this->model_applicants_of_business->verified    = ( $this->model_users->status == 'active' );
									$this->model_applicants_of_business->extended_expire = "1";
									$this->model_applicants_of_business->expire = date('Y-m-d H:i:s',  strtotime("+1 year"));
									$this->model_applicants_of_business->create();
								}

							// Committing the database transaction
								$this->db->trans_commit();

							// Build the response object
								$response = [
									'key'                => $this->auth->key,
									'role'               => $role,
									'active_user_id'     => null,
									'active_business_id' => null,
									'user_id'            => $this->model_users->user_id,
									'business_id'        => $this->auth->active_business_id,
									'status'             => $this->model_users->status,
									'businesses'         => $businesses
								];

							// Overwrite the active parameters if the user's role is admin
								if ( $this->is_admin( $role ) ) {
									$response['active_user_id']     = $this->auth->active_user_id;
									$response['active_business_id'] = $this->auth->active_business_id;
								}

								$this->response( $response, Base_Controller::HTTP_OK );
							}
						}
					}
				}
			}
		}

	/**
	 * confirm_post
	 *
	 * Update a user's status for a certain business
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function confirm_post() {
		$valid             = true;
		$sent = true;

		// Get the current user from the cache / from the database
		$this->get_user( $this->rest->user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		if ($this->model_users->status !== 'pending') {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_tokens' );
			$this->model_tokens->user_id = $this->model_users->user_id;
			$this->model_tokens->type    = 'activation';
			$this->model_tokens->create();

			// Sending the verification email
			// Load the template parser class
			$this->load->library( 'Template' );

			// Load the language file with the content of the email
			$this->lang->load( 'email' );
			$email_data               = $this->lang->line( 'applicant_signup' );
			$email_data['button_url'] = base_url() . 'verify?token=' . $this->model_tokens->token;

			$html = $this->template->view( 'applicant_signup', $email_data, true );

			// Load the Mailgun library wrapper
			$this->load->library( 'Mailgun' );

			// Extract the textual version from the html body version
			$text = $this->mailgun->get_text_version( $html );

			// Replace un wanted text with the link to the profile
			$text = $this->mailgun->str_replace_first( 'click confirm on the link below', 'copy and paste the link below in your web browser:', $text );
			$text = $this->mailgun->str_replace_first( 'Confirm and continue', $email_data['button_url'], $text );

			$this->mailgun->html    = $html;
			$this->mailgun->to      = $this->model_users->email;
			$this->mailgun->body    = $text;
			$this->mailgun->subject = $email_data['subject'];
			$sent                   = $this->mailgun->send();

			if (!$sent) {
				$this->response( [
					'status'  => false,
					'message' => 'Could not send the confirmation email'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status'  => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * status_put
	 *
	 * Update a user's status for a certain business
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function status_put( $user_id ) {
		$valid          = true;
		$form_validated = true;
		$business_id    = false;

		// Get the required level
		$status = $this->request->body;
		$status = ( is_string( $status ) && strpos( $status, 'status=' ) !== false ) ? str_replace( 'status=', '', $status ) : $status;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		if ( $this->is_recruiter() ) {
			$business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$business_id = $this->get_manager_business();
		} else if ( $this->is_admin() ) {
			$business_id = $this->get_admin_business();
		} else {
			$valid = false;
		}

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		} else {
			// Validate the input parameters
			$form_validated = $this->validateRequestParameters( array( 'status' => $status ), array(
				'applicant_status'
			) );
		}
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
			// Load the business-applicant status model
			$this->load->model( 'model_business_applicant_status' );
			$this->model_business_applicant_status->business_id = $business_id;
			$this->model_business_applicant_status->user_id     = $user_id;
			$this->model_business_applicant_status->status      = $status;

			$updated = $this->model_business_applicant_status->insert_undelete_status();

			if ( ! $updated ) {
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
	 * notes_get
	 *
	 * Get a user's note
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function notes_get( $user_id ) {
		$valid       = true;
		$business_id = false;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		if ( $this->is_recruiter() ) {
			$business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$business_id = $this->get_manager_business();
		} else if ( $this->is_admin() ) {
			$business_id = $this->get_admin_business();
		} else {
			$valid = false;
		}

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
			$this->load->model( 'model_business_user_notes' );
			$this->model_business_user_notes->user_id     = $user_id;
			$this->model_business_user_notes->business_id = $business_id;

			$note = $this->model_business_user_notes->get();

			if ( is_null( $note ) ) {
				$this->response( "", Base_Controller::HTTP_OK );
			} else {
				$this->response( $note, Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * notes_put
	 *
	 * Update a user's notes for a certain business
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function notes_put( $user_id ) {
		$valid          = true;
		$form_validated = true;
		$business_id    = false;

		// Get the required level
		$note=$this->has_param( $this->put(), 'note', '' );
		$type=$this->has_param( $this->put(), 'type', '' );
		$note = ( is_string( $note ) && strpos( $note, 'note=' ) !== false ) ? str_replace( 'note=', '', $note ) : $note;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		if ( $this->is_recruiter() ) {
			$business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$business_id = $this->get_manager_business();
		} else {
			$valid = false;
		}

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		} else {
			// Validate the input parameters
			$form_validated = $this->validateRequestParameters( array( 'note' => $note,'type' => $type), array(
				'user_note','note_type'
			) );
		}

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
			$this->load->model( 'model_business_user_notes' );
			$this->model_business_user_notes->user_id = $user_id;
			$model=$this->model_business_user_notes->get();
			
			if(!empty($model->note)){
				$update = $this->model_business_user_notes->update($model->business_user_note_id,$business_id,$type,$note);
			}else{
				$this->model_business_user_notes->user_id     = $user_id;
				$this->model_business_user_notes->business_id = $business_id;
				$this->model_business_user_notes->note        = $note;
				$this->model_business_user_notes->type        = $type;
				$update = $this->model_business_user_notes->create();
			}
			

			if ( ! $update ) {
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
	 * searches_post
	 *
	 * Create a new saved search
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function searches_post( $user_id ) {
		$valid          = true;
		$form_validated = true;
		$business_id    = false;

		// Get the search json
		$search_profile = $this->request->body;
		$search_profile = ( is_assoc( $search_profile ) ) ? $search_profile : $search_profile[0];

		// Decoding the requested dictionaries JSON string
		$search_profile = ( is_string( $search_profile ) ) ? json_decode( $search_profile, true ) : $search_profile;

		if ( $this->get_active_user() != $user_id ) {
			$valid = false;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $user_id );

			if ( $this->is_recruiter() ) {
				$business_id = $this->get_recruiter_business();
			} else if ( $this->is_manager() ) {
				$business_id = $this->get_manager_business();
			} else {
				$valid = false;
			}

			// Checking that the business_id is created through the business setup flow
			if ( is_null( $business_id ) ) {
				$valid = false;
			}

			// Checking that the user has activated his account
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}

			// Validate the input parameters
			$form_validated = $this->validateRequestParameters( array(
				'name'   => $search_profile['name'],
				'search' => json_encode( $search_profile['search'] )
			), array(
				'search',
				'search_name'
			) );
		}

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
			// Start a database transaction
			$this->db->trans_start();

			// Create a new search record
			$this->load->model( 'model_searches' );
			$this->model_searches->search_json = json_encode( $search_profile['search'] );
			$this->model_searches->insert_update_search();

			// Associate the saved search to the user and the business
			$this->load->model( 'model_searches_of_businesses' );
			$this->model_searches_of_businesses->business_id = $business_id;
			$this->model_searches_of_businesses->user_id     = $user_id;
			$this->model_searches_of_businesses->search_id   = $this->model_searches->search_id;
			$this->model_searches_of_businesses->search_name = $search_profile['name'];
			$this->model_searches_of_businesses->insert_update_search_of_businesses();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $this->get_transaction_error() ?: 'System error'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( $this->model_searches_of_businesses->search_of_business_id, Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * searches_get
	 *
	 * Get a user's searches related to a certain business
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function searches_get( $user_id ) {
		$valid       = true;
		$business_id = false;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Get the recruiter's / manager's business id
		if ( $this->is_recruiter() ) {
			$business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$business_id = $this->get_manager_business();
		} else if ( $this->is_admin() ) {
			$business_id = $this->get_admin_business();
		} else {
			$valid = false;
		}

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
			$this->load->model( 'model_saved_search' );
			$this->load->model( 'model_searches_of_businesses' );
			$this->model_searches_of_businesses->business_id = $business_id;
			$this->model_searches_of_businesses->user_id     = $user_id;

			$searches = $this->model_searches_of_businesses->get();

			$this->response( $searches, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * searches_profile_get
	 *
	 * Get a saved search profile
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @param integer $search_id
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function searches_profile_get( $user_id, $search_id ) {
		$valid       = true;
		$business_id = false;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Get the recruiter's / manager's business id
		if ( $this->is_recruiter() ) {
			$business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$business_id = $this->get_manager_business();
		} else if ( $this->is_admin() ) {
			$business_id = $this->get_admin_business();
		} else {
			$valid = false;
		}

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
			$this->load->model( 'model_searches_of_businesses' );
			$this->model_searches_of_businesses->search_of_business_id = $search_id;
			$this->model_searches_of_businesses->business_id           = $business_id;
			$this->model_searches_of_businesses->user_id               = $user_id;

			$search = $this->model_searches_of_businesses->get_search();

			if ( ! $search ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->load->model( 'model_search' );
				$this->model_search->search = $search;
				$this->model_search->parse_search_models();

				// Get the parsed search object
				$search = $this->model_search->search;

				$this->response( $search, Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * searches_profile_delete
	 *
	 * Removed a saved search from the users saved searches collection
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @param integer $search_id
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function searches_profile_delete( $user_id, $search_id ) {
		$valid       = true;
		$business_id = false;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Get the recruiter's / manager's business id
		if ( $this->is_recruiter() ) {
			$business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$business_id = $this->get_manager_business();
		} else if ( $this->is_admin() ) {
			$business_id = $this->get_admin_business();
		} else {
			$valid = false;
		}

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
			$this->load->model( 'model_searches_of_businesses' );
			$this->model_searches_of_businesses->search_of_business_id = $search_id;
			$this->model_searches_of_businesses->business_id           = $business_id;
			$this->model_searches_of_businesses->user_id               = $user_id;
			$this->model_searches_of_businesses->deleted               = 1;

			$deleted = $this->model_searches_of_businesses->soft_delete();

			if ( ! $deleted ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->response( "", Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * config_put
	 *
	 * Update the internal user's settings (InternalConfig model)
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $user_id
	 *
	 * @return    void
	 */
	public function config_put( $user_id ) {
		$valid          = true;
		$form_validated = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Available only to the requesting user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = false;
		}

		$settings = $this->request->body;
		$settings = ( is_string( $settings ) ) ? json_decode( $settings, true ) : $settings;

		// Validate the input parameters
		$form_validated = $this->validateRequestParameters( $settings, array(
			'active_business_id'
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
			// Check the the requested business_id actually belongs to the requesting user
			$this->load->model( 'model_business_users' );
			$this->model_business_users->user_id     = $user_id;
			$this->model_business_users->business_id = $settings['active_business_id'];
			$valid                                   = $this->model_business_users->get_business_user();

			if ( ! $valid ) {
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->load->library( 'Auth' );
				$this->auth->active_business_id = $settings['active_business_id'];
				$this->auth->key                = $this->rest->key;
				$this->auth->update_active_business();

				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * faults_post
	 *
	 * Report a fault in a user's profile data
	 *
	 * @access    public
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @param integer $user_id
	 *
	 * @return    void
	 */
	public function faults_post( $user_id ) {
		$valid          = true;
		$form_validated = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		$faults = $this->request->body;
		$faults = ( is_string( $faults ) ) ? json_decode( $faults, true ) : $faults;

		// Validate the input parameters
		$form_validated = $this->validateRequestParameters( $faults, array(
			'reason'
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
			$this->config->load( 'security' );

			$this->load->model( 'model_business_reports' );
			$this->model_business_reports->user_id     = $user_id;
			$this->model_business_reports->business_id = $this->rest->active_business_id;
			$reports_today                             = $this->model_business_reports->num_reports_today();

			if ( $reports_today >= $this->config->item( 'max_fault_report_per_day' ) ) {
				$this->response( [
					'status'  => false,
					'message' => 'Exceeded report daily limit'
				], Base_Controller::HTTP_TOO_MANY_REQUESTS );
			} else {
				$this->model_business_reports->business_user_id = $this->get_active_user();
				$this->model_business_reports->reason           = $faults['reason'];
				$this->model_business_reports->create();

				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	public function clean_post( $user_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		$business_id = $this->post( 'business_id' );

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
			$this->load->model( 'model_user_data' );
			$this->model_user_data->user_id     = $user_id;
			$this->model_user_data->business_id = $business_id;
			$this->model_user_data->clean();

			$this->response( [], Base_Controller::HTTP_OK );
		}
	}

	public function clean_delete( $user_id ) {
		$valid = true;

		// Get the recruiter's / manager's business id
		if ( $this->is_recruiter() ) {
			$business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$business_id = $this->get_manager_business();
		} else {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_business_user_purchase' );
			$this->model_business_user_purchase->user_id     = $user_id;
			$this->model_business_user_purchase->business_id = $business_id;
			$removed                                         = $this->model_business_user_purchase->remove();

			if ( $removed ) {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			} else {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			}
		}
	}
	public function cv_post() {
		$valid = true;
		$error = false;
		$exists = false;
		$user_id_creator = $this->get_active_user();
		ini_set('max_execution_time', 0);
		$this->load->model('Model_candidate_upload');
		$this->Model_candidate_upload->creator_id=$user_id_creator;
		$this->Model_candidate_upload->total_user=count($_FILES['file']['name']);
		$this->Model_candidate_upload->uploaded_date=date('Y-m-d');
		$this->Model_candidate_upload->create();
		// Load the upload configurations
		

		

		// Set the file name as the user's id
		// upload multiple resume
		$total_uploaded=0;
		for($j=0;$j<count($_FILES['file']['name']);$j++){
			$valid = true;
			$this->config->load( 'upload' );
			$config = $this->config->item( 'upload' );
			$config['file_name'] = 'resume_'.time().'_'.$j.'_'.$user_id_creator;

		// Load the file upload library
			$this->load->library( 'upload', $config );
			$this->upload->initialize($config, FALSE);
		// Start uploading the file
			if ( ! $this->upload->do_upload_multiple( 'file',$j) ) {
				$valid = false;
				$error = $this->upload->display_errors( '', '' );
			} else {
				$file_data = $this->upload->data();
				// Save the file in the files model
				$this->load->model( 'model_files' );
				$this->model_files->file_url = $file_data['full_path'];
				$fileid=$this->model_files->createfile();
			}	
			if ( ! $valid ) {
				$this->response( [
					'status'  => false,
					'message' => $error
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				try {
				//$client = new SoapClient("http://cvxdemo.daxtra.com/cvx/CVXtractorService.wsdl",array('exceptions' => true));
				$client = new SoapClient("https://cvx-findableone.daxtra.com/cvx/CVXtractorService.wsdl",array('exceptions' => true));
				$fileContents = file_get_contents($file_data['full_path']);
				
				//$response = $client->ProcessCV(array('document_url' => base64_encode($fileContents), 'account' => "findableCO"));
				$response = $client->ProcessCV(array('document_url' => base64_encode($fileContents), 'account' => "findableone"));
				
				}catch ( SoapFault $e ) {
					$this->debug_cv('failed Upload resume  ' . $_FILES['file']['name'][$j]);
					continue;
				}
				$hrxml_profile = simplexml_load_string($response->hrxml);

				if($hrxml_profile->getName() === 'CSERROR') {
					$this->response( [
						'status'  => false,
						'message' => 'Service is unavailable'
					], Base_Controller::HTTP_SERVICE_UNAVAILABLE );
				} else {
					if (isset($hrxml_profile) && !empty($hrxml_profile)) {

						$resume = $hrxml_profile->StructuredXMLResume;
						
						//echo '<pre>'; print_r($resume); die;
						
						$googlePlaces = new SKAgarwal\GoogleApi\PlacesApi('AIzaSyD2Noyrm95Zv_JFRIHl8-1u8oGDiLal4zk', FALSE);

						// query for googleplaces
						$location_query = "";

						// city id required by db tables
						$city_id = "";

						$gotProfile = false;
						
						// create user 
						$email=isset($resume->ContactInfo->ContactMethod->InternetEmailAddress)?(string)$resume->ContactInfo->ContactMethod->InternetEmailAddress:false;
						$first_name=isset($resume->ContactInfo->PersonName->GivenName)?(string)$resume->ContactInfo->PersonName->GivenName:false;
						$last_name=isset($resume->ContactInfo->PersonName->FamilyName)?(string)$resume->ContactInfo->PersonName->FamilyName:false;
						if ($email && $first_name && $last_name) {
							$this->db->trans_begin();
							$this->load->model( 'model_users' );
							$this->load->model('model_profiles');
							$this->model_users->email    = $email;
							$this->model_users->password = password_hash('flexsin123#@!', PASSWORD_BCRYPT );
							$this->model_users->role     = 'applicant';
							$this->model_users->verified = 1;
							$this->model_users->status   = 'active';
							$this->model_users->search_visible = 1;
							$this->model_users->created_by = 1;
							$this->model_users->creator_id = $user_id_creator;
							$this->model_users->upload_id=$this->Model_candidate_upload->id;
							$user_id=$this->model_users->create_user();
							// Creating an API access key
							$this->load->library( 'Auth' );
							$this->auth->user_id = $user_id;
							$this->auth->role    = 'applicant';
							$this->auth->create();
							// create profile for user
							$this->model_profiles->profile_id =NULL;
							$this->model_profiles->user_id = $user_id;
							$this->model_profiles->profile_firstname = $first_name;
							$this->model_profiles->profile_lastname  = $last_name;
							$this->model_profiles->profile_resume  =$fileid;
							$profile_id=$this->model_profiles->create_profile();
							// Creating user status entity
							$this->load->model( 'model_user_status' );
							$this->model_user_status->user_id = $user_id;
							$this->model_user_status->user_status_employment_status = 'employed full time';
							$this->model_user_status->user_status_current = 'actively looking';
							$this->model_user_status->user_status_employment_type = 'full time';
							/*$this->model_user_status->user_status_legal_usa = false;
							$this->model_user_status->user_status_relocation = false;*/
							$this->model_user_status->create_status();

							$this->db->trans_commit();
							// updated uploaded user
							$total_uploaded++;
								//$user_id=$this->model_users->user_id;

						// handling personal infomation:
							if (isset($resume->ContactInfo)&& !empty($resume->ContactInfo)) {
								$this->load->model('model_personal_details');
								$this->load->model('model_profile');
								$this->load->model('model_profiles');
								$this->load->model('model_location');
								$this->load->model('model_files');	
								$this->model_profiles->user_id = $user_id;
								$current_profile = $this->model_profiles->get_personal_details();

							// Loading the profile model
								$this->model_profiles->load();

								$profile = array(
									"profile_firstname" => $current_profile->firstname,
									"profile_lastname" => $current_profile->lastname,
									"profile_gender" => $current_profile->gender,
									"profile_birthday" => $current_profile->birthday,
									"profile_about" => $current_profile->about,
									"profile_phone_number" => $current_profile->phone,
									"profile_website" => $current_profile->website,
									"profile_skype" => $current_profile->skype,
									"profile_image" => (isset($current_profile->image->id)) ? $current_profile->image->id : NULL,
								);

								$city_id = isset($current_profile->location->city_id) ? $current_profile->location->city_id : "";

							/** For Reference
							 * Findable Profile data structure:
							 * 
							 * $arr = array(
							 *	 "firstname" => "null",
							 *	 "lastname" => "null",
							 *	 "phone" => "null",
							 *	 "email" => "null",
							 *	 "skype" => "null",
							 *	 "website" => "null",
							 *	 "about" => "null",
							 *	 "updated" => "null",
							 *	 "location" => (object) array(
							 *	 	"continent_id" => "null",
							 *	 	"continent_name" => "null",
							 *	 	"city_id" => "null",
							 *	 	"city_name" => "null",
							 *	 	"state_id" => "null",
							 *	 	"state_name" => "null",
							 *	 	"state_short_name" => "null",
							 *	 	"country_id" => "null",
							 *	 	"country_name" => "null",
							 *	 	"country_short_name_alpha_3" => "null",
							 *	 	"country_short_name_alpha_2" => "null"
							 *	 ),
							 *	 "image" => (object)array(
							 *	 	"id" => "null",
							 *	 	"url" => "null"
							 *	 )
							 * );
							 */

							

							// Gender
							if (isset($resume->ContactInfo->PersonName->sex) && !empty($resume->ContactInfo->PersonName->sex)) {
								$genders = array(
									"Male" => "M",
									"Female" => "F"
								);
								$profile["profile_gender"] = $genders[(string) $resume->ContactInfo->PersonName->sex];
							}

							// Phone
							// Telephone is home phone, Mobile is another.
							if (isset($resume->ContactInfo->ContactMethod->Mobile)) {
								$profile["profile_phone_number"] = (string) $resume->ContactInfo->ContactMethod->Mobile->FormattedNumber;
							} else
							if (isset($resume->ContactInfo->ContactMethod->Telephone))  {
								$profile["profile_phone_number"] = (string) $resume->ContactInfo->ContactMethod->Telephone->FormattedNumber;
							}

							// sanitize phone
							if ($profile["profile_phone_number"] == trim($profile["profile_phone_number"]) && strpos($profile["profile_phone_number"], ' ') !== false) {
								$profile["profile_phone_number"] = preg_replace('/\D+/', '', $profile["profile_phone_number"]);
							}
							if ( isset($resume->Date) && !empty($resume->Date) && (string)$resume->Date['type'][0] === 'dob' && isset($resume->Date->AnyDate) ) {
								$profile["profile_birthday"] = date("Y-m-d 00:00:00", strtotime($resume->Date->AnyDate));
							}
							// Website
							if (isset($resume->ContactInfo->ContactMethod->InternetWebAddress) && !empty($resume->ContactInfo->ContactMethod->InternetWebAddress)) {
								$profile["profile_website"] = (string) $resume->ContactInfo->ContactMethod->InternetWebAddress;
								// sanitize website
								if (preg_match("/^(https|http):\/\//", $profile["profile_website"])) {
									$profile["profile_website"] = substr($profile["profile_website"], strpos($profile["profile_website"], '//') + 2);
								}
							}

							// About:
							// check if ExecutiveSummary exists and assign it to profile[about]:
							if (isset($resume->ExecutiveSummary) && !empty($resume->ExecutiveSummary)) {
								$profile["profile_about"] = str_replace(array("\r\n", "\n", "\r", "\t"), ' ', $resume->ExecutiveSummary);
							}

							if (isset($resume->ContactInfo->ContactMethod->PostalAddress) && !empty($resume->ContactInfo->ContactMethod->PostalAddress)) {
								$municipality = isset($resume->ContactInfo->ContactMethod->PostalAddress->Municipality) ? $resume->ContactInfo->ContactMethod->PostalAddress->Municipality : '';
								$country_code = isset($resume->ContactInfo->ContactMethod->PostalAddress->CountryCode) ? $resume->ContactInfo->ContactMethod->PostalAddress->CountryCode : '';

								if ($municipality != '') {
									$location_query .= "$municipality";
									
									if ($country_code != '') {
										$location_query .= ", $country_code";
									}
								}
							}


							if ($location_query != '') {
								$places = $googlePlaces->placeAutocomplete($location_query, ["types" => '(cities)']);				

								$predictionItem1 = $places['predictions'][0]['terms'];
								
								if (count($predictionItem1) === 3) {
									$country_name = $predictionItem1[2]["value"];
								} else if (count($predictionItem1) === 2) {
									$country_name = $predictionItem1[1]["value"];
								}

								$location = array(
									"city_name" => $predictionItem1[0]["value"],
									"country_name" => $country_name,
								);
								if(empty($location["city_name"])){
									$location['city_name']='New York';
								}
								if(empty($location["country_name"])){
									$location['country_name']='United States';
								}
								$this->load->model('model_location');
								$city_id = $this->model_location->get_city_id($location, FALSE);
							}

							if ( array_key_exists('location', $current_profile) ) {
								//$this->model_profiles->city_id = $current_profile->location->city_id;
								if($city_id){
									$profile["city_id"] = $city_id;
								}
								
							} else {
								//$this->model_profiles->city_id = $city_id;
								if($city_id){
									$profile["city_id"] = $city_id;
								}
							}

							
							// Replacing with the new data
							//$this->model_profiles->load_model($profile);
							$this->db->trans_begin();
							$this->model_profiles->update($profile,$profile_id);	
							if ($this->db->trans_status() === false) {
								$this->db->trans_rollback();
							} else {
								$this->db->trans_commit();
								$gotProfile = true;
								$this->debug_cv('Profile Updated', true);
							}
							
							// no city id, can not update profile record in findable
						}

						/*
						 * End of personal profile handling
						 */
						// If gotProfile is true then ExecutiveSummary would be committed in the profile transaction above.
						if ($gotProfile != false && isset($resume->ExecutiveSummary) && !empty($resume->ExecutiveSummary)) {
							$about = str_replace(array("\r\n", "\n", "\r", "\t"), ' ', $resume->ExecutiveSummary);

							$this->load->model( 'model_profiles' );

							$this->model_profiles->user_id = $user_id;
							$this->model_profiles->update_about( $about );
						}

						$this->load->model('model_positions_of_users');

						// used in the Employment & Schools dictionaries
						$this->load->model('model_dictionary');
						$this->load->model('model_companies');
						$this->load->model('model_job_title');
						$this->load->model('model_industries');
						$this->load->model( 'model_areas_of_focus' );
						$this->load->model( 'model_areas_of_focus_of_positions_of_users');
						$this->load->model( 'model_seniorities' );
						
						if (isset($resume->EmploymentHistory)) {
							if (count($resume->EmploymentHistory->EmployerOrg) > 0) { // Position Start
							     //echo '<pre>'; print_r($resume->EmploymentHistory); die;
								// handling EmploymentHistory (Positions|Experiences)
								foreach ($resume->EmploymentHistory->EmployerOrg as $key => $position) {
									$location_query = "";
									// continue to next iteration if EmplyerOrgName & other necessary fields missing:
									if (!isset($position->EmployerOrgName) && empty($position->EmployerOrgName)) { 
										$this->debug_cv('Missed Position ' . $key . ' (Basic data error)', $position); continue; 
										}

										/*if (!isset($position->EmployerContactInfo->LocationSummary->Municipality) || !isset($position->EmployerContactInfo->LocationSummary->CountryCode)) {
											$this->debug_cv('Missed Position ' . $key . ' (Location data error)', $position);
											continue;
										}*/ 
										else {
											// ckeck if city not found then set to new york
											if(isset($position->EmployerContactInfo->LocationSummary->Municipality)){
												$municipality = $position->EmployerContactInfo->LocationSummary->Municipality;
											}else{
												$municipality='new york';
											}
											// ckeck if country not found then set to united state
											if(isset($position->EmployerContactInfo->LocationSummary->CountryCode)){
												$country_code =$position->EmployerContactInfo->LocationSummary->CountryCode;
											}else{
												$country_code ='us';
											}
											

											$location_query .= "$municipality";
											$location_query .= "$country_code";

											$places = $googlePlaces->placeAutocomplete($location_query, ["types" => '(cities)']);				

											$predictionItem1 = $places['predictions'][0]['terms'];

											if (count($predictionItem1) === 3) {
												$country_name = $predictionItem1[2]["value"];
											} else if (count($predictionItem1) === 2) {
												$country_name = $predictionItem1[1]["value"];
											}

											$location = array(
												"city_name" => $predictionItem1[0]["value"],
												"country_name" => $country_name,
											);
											if(empty($location["city_name"])){
												$location['city_name']='New York';
											}
											if(empty($location["country_name"])){
												$location['country_name']='United States';
											}
											$this->load->model('model_location');
											$city_id = $this->model_location->get_city_id($location, FALSE);
											
											if($city_id==0){
												$location['city_name']='New York';
												$location['country_name']='United States';
												$city_id = $this->model_location->get_city_id($location, FALSE);
											}
											
											$this->model_positions_of_users->user_id = $user_id;
											$this->model_positions_of_users->city_id = $city_id;

										// Handling company name
											$this->model_dictionary->model = $this->model_companies;

											$company_name = (string) $position->EmployerOrgName;

											$companies = $this->model_dictionary->get_dictionary( 'name', $company_name );

											if (isset($companies) && !empty($companies) && count($companies) > 0) {
											// got an array, using first value:
												$this->model_positions_of_users->company_id = (string) $companies[0]->id;
											} else {
											// No company dictionary record, add new & use returned id:
												$this->model_companies->company_name = ucfirst( $company_name );
												$this->model_companies->item_add();

												$this->model_positions_of_users->company_id = $this->model_companies->company_id;
											}

										// Handling job title
											$this->model_dictionary->model = $this->model_job_title;

											$job_title_name = (string) $position->PositionHistory->Title;

											$job_titles = $this->model_dictionary->get_dictionary( 'name', $job_title_name );

											if (isset($job_titles) && !empty($job_titles) && count($job_titles) > 0) {
											// got an array, using first value:
												$this->model_positions_of_users->job_title_id  = (string) $job_titles[0]->id;
											} else {
											// No job title dictionary record, add new and use returned id:
												$this->model_job_title->job_title_name = ucfirst( $job_title_name );
												$this->model_job_title->item_add();
												$this->model_positions_of_users->job_title_id  = (string) $this->model_job_title->job_title_id;
											}

										// Handle OrgIndustry object
											if ( isset($position->PositionHistory->JobCategory) && isset($position->PositionHistory->JobCategory->CategoryCode) ) {
												$this->model_dictionary->model = $this->model_industries;
												$industry_name = $position->PositionHistory->JobCategory->CategoryCode;
												$industries = $this->model_dictionary->get_dictionary( 'name', $industry_name );

												if (isset($industries) && !empty($industries) && count($industries) > 0) {
												// got an array, using first value:
													$this->model_positions_of_users->industry_id = (string) $industries[0]->id;
												} else {
												// No industry dictionary record, add new & use returned id:
													$this->model_industries->industry_name = ucfirst( $industry_name );
													$this->model_industries->item_add();

													$this->model_positions_of_users->industry_id = $this->model_industries->industry_id;
												}
											}
											// set seniority lavel
											if ( isset($position->PositionHistory->JobLevelInfo) && isset($position->PositionHistory->JobLevelInfo->JobGrade) ) {
											   //$this->model_seniorities;
												$seniority_name = explode('-',$position->PositionHistory->JobLevelInfo->JobGrade);
												if(isset($seniority_name[1]) && !empty($seniority_name[1])){
													$seniority_name=strtolower($seniority_name[1]);
													$this->model_seniorities->seniority_name=$seniority_name;
													if ($this->model_seniorities->item_exists()) {
													// got an array, using first value:
														$this->model_positions_of_users->seniority_id =$this->model_seniorities->seniority_id;
													} else {
													// No industry dictionary record, add new & use returned id:
														$this->model_seniorities->item_add();

														$this->model_positions_of_users->seniority_id = $this->model_industries->seniority_id;
													}
												}
											}
											$this->model_positions_of_users->position_from = null;

										if (isset($position->PositionHistory->StartDate->Year)) { // Year === YYYY
											$position_from = date("Y-m-d 00:00:00", strtotime($position->PositionHistory->StartDate->Year . "-01-01"));
										} else if (isset($position->PositionHistory->StartDate->YearMonth)) { // YearMonthg === YYYY-MM
											$position_from = date("Y-m-d 00:00:00", strtotime($position->PositionHistory->StartDate->YearMonth . "-01"));
										}

										// Start Date cannot be empty. move to next iteration if null
										if (!isset($position_from)) { continue; } 

										$this->model_positions_of_users->position_from = $position_from;

										// if notApplicable or position_from is bigger than EndDate->Year then positon_to will not be reassigned:
										$this->model_positions_of_users->position_to = null;
										$this->model_positions_of_users->position_current = 1;

										if ( $position->PositionHistory->EndDate != "notApplicable" || $position->PositionHistory->EndDate->Year != "notKnown" ) {
											if (isset($position->PositionHistory->EndDate->Year)) { // Year === YYYY
												$position_to = date("Y-m-d 00:00:00", strtotime($position->PositionHistory->EndDate->Year . "-01-01"));	
											} else if (isset($position->PositionHistory->EndDate->YearMonth)) { // YearMonthg === YYYY-MM
												$position_to = date("Y-m-d 00:00:00", strtotime($position->PositionHistory->EndDate->YearMonth . "-01"));
											} else {
												$position_to = null;
											}

											if ($position_to != null && strtotime($this->model_positions_of_users->position_from) < strtotime($position_to)) {
												$this->model_positions_of_users->position_to = $position_to;
												$this->model_positions_of_users->position_current = 0;
											}
										}

										$this->model_positions_of_users->positions_of_users_id = null;
										$this->model_positions_of_users->deleted = 0;

										// Check if position already exist and begin transaction if false
										if ( ! $this->model_positions_of_users->is_position_exists() ) {
											$this->db->trans_begin();

											$this->model_positions_of_users->create();

											if ($this->db->trans_status() === false) {
												$this->db->trans_rollback();
												$this->debug_cv('Missed Position ' . $key . ' (Database error)', $position);
												continue;
											} else {
												if(!empty($position->PositionHistory->Description)){
													$this->model_areas_of_focus->area_of_focus_name = ucfirst( $position->PositionHistory->Description );
													$focous_id=$this->model_areas_of_focus->item_add_upload();
													$this->model_areas_of_focus_of_positions_of_users->position_of_users_id = $this->model_positions_of_users->positions_of_users_id;
													$this->model_areas_of_focus_of_positions_of_users->batch_create( array($focous_id));
												}
												$this->db->trans_commit();
												$this->debug_cv('Added Position ' . $key, $position);
											}
										}
									}
								}
							}else{
								$this->set_default_experiance($user_id);
							}
						}else{
							$this->set_default_experiance($user_id);
						}
						

						$educations = isset($resume->EducationHistory->SchoolOrInstitution) ? $resume->EducationHistory->SchoolOrInstitution : null;
						if (isset($educations) && !empty($educations)) {
							$this->load->model('model_schools');
							$this->load->model('model_schools_of_users');
							//echo '<pre>'; print_r($resume->EducationHistory);
							/*echo count($resume->EducationHistory->SchoolOrInstitution);
							echo '<pre>'; var_dump($resume->EducationHistory->SchoolOrInstitution); 

							die;*/
							/*if (is_array($educations)) {*/
								$this->debug_cv('Education items found', count($educations));
								// array of education objects
								foreach ($educations as $key => $education) {
									// check if school name exists in schools dictionary, retreive id if created or existed:
									if ( ! $this->handle_education_cv($user_id, $education) ) { 
										$this->debug_cv('Education item ' . $key . ' missed', $education); 
										continue; 
									} else {
										$this->debug_cv('Education item ' . $key . ' added', $education); 
									}
								}
							/*} else {
								$this->debug_cv('Education items found', 1);
								// single education object
								if( ! $this->handle_education_cv($user_id, $educations) ) {
									$this->debug_cv('Education item added', false);
								} else {
									$this->debug_cv('Education item added', true);
								}
							}*/
						} // end of educations
						// Languages
						if (isset($resume->Languages)) {
							$this->load->model('model_languages');
							$this->load->model('model_languages_of_users');
							if (isset($resume->Languages->Language) && !empty($resume->Languages->Language) && count($resume->Languages->Language) > 0) {
								$this->debug_cv('Language items found', count($resume->Languages->Language));
								// lanaguage is an array
								foreach ($resume->Languages->Language as $key => $language) {
									if ( ! $this->handle_lanaguage_cv($user_id, $language) ) { 
										$this->debug_cv('Language item ' . $key . ' missed', $language); 
										continue; 
									} else {
										$this->debug_cv('Language item ' . $key . ' added', $language); 
									}
								}
							} else
							if (isset($resume->Languages->Language) && !empty($resume->Languages->Language)) {
								$this->debug_cv('Language items found', 1);
								// singule lanauge object
								if ( ! $this->handle_lanaguage_cv($user_id, $resume->Languages->Language) ) {
									$this->debug_cv('Language item added', false);
								} else {
									$this->debug_cv('Language item added', true);
								}
							}
						} // end of languages
						if (isset($resume->Competency) && !empty($resume->Competency)) {
							$this->load->model('model_technical_abilities');
							$this->load->model('model_technical_abilities_of_users');

							$this->model_technical_abilities_of_users->user_id = $user_id;
							$tech_skills = $this->model_technical_abilities_of_users->get();
							if (count($resume->Competency) > 0) {
								$this->debug_cv('Skills items found', count($resume->Competency));
								for ($k=0;$k<count($resume->Competency);$k++) {
									if (!$this->handle_competency_cv($user_id, $resume->Competency[$k], $tech_skills)) { 
										$this->debug_cv('Skill item ' . $k . ' missed', $resume->Competency[$k]); 
										continue; 
									} else {
										$this->debug_cv('Skill item ' . $k. ' added', $resume->Competency[$k]); 
									}
								}
							} else {
								$this->debug_cv('Skills items found', 1);
								
								if ( ! $this->handle_competency_cv($user_id, $resume->Competency, $tech_skills) ) {
									$this->debug_cv('Skill item added', false);
								} else {
									$this->debug_cv('Skill item added', true);
								}
							}
						} // end of skills
					}else{
					$this->debug_cv('First name,last name,email not found ', false);
					}	

				} else {
						// hrxml_profile returned empty from daxtra parsing service processCV
					$this->debug_cv('cv parse returned empty, choose only supported ', false);
					/*$this->response([
						'status' => false,
						'message' => "cv parse returned empty, choose only supported files."
					], Base_Controller::HTTP_BAD_REQUEST);*/
				}
			}
		}
	}
	$this->Model_candidate_upload->uploaded_user=$total_uploaded;
	$this->Model_candidate_upload->update();
	if (ENVIRONMENT == 'staging' || ENVIRONMENT == 'development') {
		$this->response( [
			'status'   => true,
			'uploaded_id'   => $this->Model_candidate_upload->id,
			'data' => $this->debug_cv()
		], Base_Controller::HTTP_OK );
	} else {
		$this->response( [
			'status'   => true,
		], Base_Controller::HTTP_OK );
	}
}
// cv_post backup for user module
/*	public function cv_post() {

		$valid = true;
		$error = false;
		$exists = false;

		ini_set('max_execution_time', 300);

		// Load the upload configurations
		$this->config->load( 'upload' );
		$config = $this->config->item( 'upload' );

		$user_id = $this->get_active_user();

		// Set the file name as the user's id
		$config['file_name'] = 'resume_' . $user_id;

		// Load the file upload library
		$this->load->library( 'upload', $config );

		$base_path = (ENVIRONMENT == 'development') ? realpath(FCPATH . 'assets/files') . DIRECTORY_SEPARATOR : $this->upload->get_upload_path() . '/user_files/';

		$file_path = $base_path . $config['file_name'];

		if (file_exists($file_path . ".pdf") != false) {
			$exists = true;
			$existing_filename = $file_path . ".pdf";
		} else
		if (file_exists($file_path . ".doc") != false) {
			$exists = true;
			$existing_filename = $file_path . ".doc";
		} else
		if (file_exists($file_path . ".docx") != false) {
			$exists = true;
			$existing_filename = $file_path . ".docx";
		} else
		if (file_exists($file_path . ".rtf") != false) {
			$exists = true;
			$existing_filename = $file_path . ".rtf";
		}

		if ($exists != false && isset($existing_filename)) {
			$this->debug_cv('Previous resume found', true);
			unlink($existing_filename);
		} else {
			$this->debug_cv('Previous resume found', false);
		}
		
		// Start uploading the file
		if ( ! $this->upload->do_upload( 'file' ) ) {
			$valid = false;
			$error = $this->upload->display_errors( '', '' );
		} else {
			$file_data = $this->upload->data();

			// Save the file in the files model
			$this->load->model( 'model_files' );
			$this->model_files->file_url = $file_data['full_path'];
			$this->model_files->create();
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => $error
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {

			$client = new SoapClient("http://cvxdemo.daxtra.com/cvx/CVXtractorService.wsdl");

			$fileContents = file_get_contents($file_data['full_path']);
			
			$response = $client->ProcessCV(array('document_url' => base64_encode($fileContents), 'account' => "FINDABLE_cvxdemo"));
			
			$hrxml_profile = simplexml_load_string($response->hrxml);

			if($hrxml_profile->getName() === 'CSERROR') {
				$this->response( [
					'status'  => false,
					'message' => 'Service is unavailable'
				], Base_Controller::HTTP_SERVICE_UNAVAILABLE );
			} else {
				if (isset($hrxml_profile) && !empty($hrxml_profile)) {
				
					$resume = $hrxml_profile->StructuredXMLResume;
	
					$googlePlaces = new SKAgarwal\GoogleApi\PlacesApi('AIzaSyD2Noyrm95Zv_JFRIHl8-1u8oGDiLal4zk', FALSE);
			
					// query for googleplaces
					$location_query = "";
		
					// city id required by db tables
					$city_id = "";
	
					$gotProfile = false;
	
					// handling personal infomation:
					if (
						isset($resume->ContactInfo)
						&& !empty($resume->ContactInfo)
						&& isset($resume->ContactInfo->ContactMethod->PostalAddress)
						&& isset($resume->ContactInfo->ContactMethod->PostalAddress->Municipality)
					) {
	
						$this->load->model('model_personal_details');
						$this->load->model('model_profile');
						$this->load->model('model_profiles');
						$this->load->model('model_location');
						$this->load->model('model_files');
	
						$this->model_profiles->user_id = $user_id;
						$current_profile = $this->model_profiles->get_personal_details();
						
						// Loading the profile model
						$this->model_profiles->load();
	
						$profile = array(
							"firstname" => $current_profile->firstname,
							"lastname" => $current_profile->lastname,
							"gender" => $current_profile->gender,
							"birthday" => $current_profile->birthday,
							"about" => $current_profile->about,
							"email" => $current_profile->email,
							"phone" => $current_profile->phone,
							"website" => $current_profile->website,
							"skype" => $current_profile->skype,
							"image" => (isset($current_profile->image->id)) ? $current_profile->image->id : NULL,
						);
	
						$city_id = isset($current_profile->location->city_id) ? $current_profile->location->city_id : "";
	
					
	
						
	
						
						if (isset($resume->ContactInfo->PersonName->sex) && !empty($resume->ContactInfo->PersonName->sex)) {
							$genders = array(
								"Male" => "M",
								"Female" => "F"
							);
							$profile["gender"] = $genders[(string) $resume->ContactInfo->PersonName->sex];
						}
	
						// Phone
						// Telephone is home phone, Mobile is another.
						if (isset($resume->ContactInfo->ContactMethod->Mobile)) {
							$profile["phone"] = (string) $resume->ContactInfo->ContactMethod->Mobile->FormattedNumber;
						} else
						if (isset($resume->ContactInfo->ContactMethod->Telephone))  {
							$profile["phone"] = (string) $resume->ContactInfo->ContactMethod->Telephone->FormattedNumber;
						}
	
						// sanitize phone
						if ($profile["phone"] == trim($profile["phone"]) && strpos($profile["phone"], ' ') !== false) {
							$profile["phone"] = preg_replace('/\D+/', '', $profile["phone"]);
						}
	
						// Date of Birth:: dob
						if ( isset($resume->Date) && !empty($resume->Date) && $resume->Date['type'] === 'dob' && isset($resume->Date->Year) ) {
							$profile["birthday"] = date("Y-m-d 00:00:00", strtotime($resume->Date->Year));
						}
	
						// Website
						if (isset($resume->ContactInfo->ContactMethod->InternetWebAddress) && !empty($resume->ContactInfo->ContactMethod->InternetWebAddress)) {
							$profile["website"] = (string) $resume->ContactInfo->ContactMethod->InternetWebAddress;
							// sanitize website
							if (preg_match("/^(https|http):\/\//", $profile["website"])) {
								$profile["website"] = substr($profile["website"], strpos($profile["website"], '//') + 2);
							}
						}
	
						// About:
						// check if ExecutiveSummary exists and assign it to profile[about]:
						if (isset($resume->ExecutiveSummary) && !empty($resume->ExecutiveSummary)) {
							$profile["about"] = str_replace(array("\r\n", "\n", "\r", "\t"), ' ', $resume->ExecutiveSummary);
						}
	
						if (isset($resume->ContactInfo->ContactMethod->PostalAddress) && !empty($resume->ContactInfo->ContactMethod->PostalAddress)) {
							$municipality = isset($resume->ContactInfo->ContactMethod->PostalAddress->Municipality) ? $resume->ContactInfo->ContactMethod->PostalAddress->Municipality : '';
							$country_code = isset($resume->ContactInfo->ContactMethod->PostalAddress->CountryCode) ? $resume->ContactInfo->ContactMethod->PostalAddress->CountryCode : '';
	
							if ($municipality != '') {
								$location_query .= "$municipality";
								
								if ($country_code != '') {
									$location_query .= ", $country_code";
								}
							}
						}
	
	
						if ($location_query != '') {
							$places = $googlePlaces->placeAutocomplete($location_query, ["types" => '(cities)']);				
	
							$predictionItem1 = $places['predictions'][0]['terms'];
							
							if (count($predictionItem1) === 3) {
								$country_name = $predictionItem1[2]["value"];
							} else if (count($predictionItem1) === 2) {
								$country_name = $predictionItem1[1]["value"];
							}
	
							$location = array(
								"city_name" => $predictionItem1[0]["value"],
								"country_name" => $country_name,
							);
	
							$this->load->model('model_location');
							$city_id = $this->model_location->get_city_id($location, FALSE);
						}
	
						if ( array_key_exists('location', $current_profile) ) {
							$this->model_profiles->city_id = $current_profile->location->city_id;
							$profile["city_id"] = $city_id;
						} else {
							$this->model_profiles->city_id = $city_id;
							$profile["city_id"] = $city_id;
						}
	
						
						// Replacing with the new data
						$this->model_profiles->load_model($profile);
						
						if ( isset($city_id) && !empty($city_id) ) {
							$this->db->trans_begin();
							
							// save profile
							$this->model_profiles->save();
							
							if ($this->db->trans_status() === false) {
								$this->db->trans_rollback();
							} else {
								$this->db->trans_commit();
								$gotProfile = true;
								$this->debug_cv('Profile Updated', true);
							}
						} else {
							$this->debug_cv('Profile Updated', 'Error: Missing location');
						}
						// no city id, can not update profile record in findable
					}
	
					
	
					// If gotProfile is true then ExecutiveSummary would be committed in the profile transaction above.
					if ($gotProfile != false && isset($resume->ExecutiveSummary) && !empty($resume->ExecutiveSummary)) {
						$about = str_replace(array("\r\n", "\n", "\r", "\t"), ' ', $resume->ExecutiveSummary);
	
						$this->load->model( 'model_profiles' );
	
						$this->model_profiles->user_id = $user_id;
						$this->model_profiles->update_about( $about );
					}
	
					$this->load->model('model_positions_of_users');
		
					// used in the Employment & Schools dictionaries
					$this->load->model('model_dictionary');
					$this->load->model('model_companies');
					$this->load->model('model_job_title');
					$this->load->model('model_industries');
	
					if (isset($resume->EmploymentHistory)) {
						if (count($resume->EmploymentHistory->EmployerOrg) > 0) { // Position Start
							// handling EmploymentHistory (Positions|Experiences)
							foreach ($resume->EmploymentHistory->EmployerOrg as $key => $position) {
								$location_query = "";
								// continue to next iteration if EmplyerOrgName & other necessary fields missing:
								if ( 
									!isset( $position->EmployerOrgName ) || 
									!isset( $position->PositionHistory->Title ) ||
									!isset( $position->EmployerContactInfo->LocationSummary ) ) { $this->debug_cv('Missed Position ' . $key . ' (Basic data error)', $position); continue; }
								
								if (!isset($position->EmployerContactInfo->LocationSummary->Municipality) || !isset($position->EmployerContactInfo->LocationSummary->CountryCode)) {
									$this->debug_cv('Missed Position ' . $key . ' (Location data error)', $position);
									continue;
								} else {
									$municipality = $position->EmployerContactInfo->LocationSummary->Municipality;
									$country_code = $position->EmployerContactInfo->LocationSummary->CountryCode;
		
									$location_query .= "$municipality";
									$location_query .= ", $country_code";
		
									$places = $googlePlaces->placeAutocomplete($location_query, ["types" => '(cities)']);				
		
									$predictionItem1 = $places['predictions'][0]['terms'];
									
									if (count($predictionItem1) === 3) {
										$country_name = $predictionItem1[2]["value"];
									} else if (count($predictionItem1) === 2) {
										$country_name = $predictionItem1[1]["value"];
									}
		
									$location = array(
										"city_name" => $predictionItem1[0]["value"],
										"country_name" => $country_name,
									);
		
									$this->load->model('model_location');
									$city_id = $this->model_location->get_city_id($location, FALSE);
		
									$this->model_positions_of_users->user_id = $user_id;
									$this->model_positions_of_users->city_id = $city_id;
									
									// Handling company name
									$this->model_dictionary->model = $this->model_companies;
				
									$company_name = (string) $position->EmployerOrgName;
				
									$companies = $this->model_dictionary->get_dictionary( 'name', $company_name );
									
									if (isset($companies) && !empty($companies) && count($companies) > 0) {
										// got an array, using first value:
										$this->model_positions_of_users->company_id = (string) $companies[0]->id;
									} else {
										// No company dictionary record, add new & use returned id:
										$this->model_companies->company_name = ucfirst( $company_name );
										$this->model_companies->item_add();
										
										$this->model_positions_of_users->company_id = $this->model_companies->company_id;
									}
			
									// Handling job title
									$this->model_dictionary->model = $this->model_job_title;
				
									$job_title_name = (string) $position->PositionHistory->Title;
									
									$job_titles = $this->model_dictionary->get_dictionary( 'name', $job_title_name );
					
									if (isset($job_titles) && !empty($job_titles) && count($job_titles) > 0) {
										// got an array, using first value:
										$this->model_positions_of_users->job_title_id  = (string) $job_titles[0]->id;
									} else {
										// No job title dictionary record, add new and use returned id:
										$this->model_job_title->job_title_name = ucfirst( $job_title_name );
										$this->model_job_title->item_add();
										$this->model_positions_of_users->job_title_id  = (string) $this->model_job_title->job_title_id;
									}
			
									// Handle OrgIndustry object
									if ( isset($position->PositionHistory->OrgIndustry) && isset($position->PositionHistory->OrgIndustry->IndustryDescription) ) {
										$this->model_dictionary->model = $this->model_industries;
										$industry_name = $position->PositionHistory->OrgIndustry->IndustryDescription;
										$industries = $this->model_dictionary->get_dictionary( 'name', $industry_name );
				
										if (isset($industries) && !empty($industries) && count($industries) > 0) {
											// got an array, using first value:
											$this->model_positions_of_users->industry_id = (string) $industries[0]->id;
										} else {
											// No industry dictionary record, add new & use returned id:
											$this->model_industries->industry_name = ucfirst( $industry_name );
											$this->model_industries->item_add();
											
											$this->model_positions_of_users->industry_id = $this->model_industries->industry_id;
										}
									}
			
									$this->model_positions_of_users->position_from = null;
				
									if (isset($position->PositionHistory->StartDate->Year)) { // Year === YYYY
										$position_from = date("Y-m-d 00:00:00", strtotime($position->PositionHistory->StartDate->Year . "-01-01"));
									} else if (isset($position->PositionHistory->StartDate->YearMonth)) { // YearMonthg === YYYY-MM
										$position_from = date("Y-m-d 00:00:00", strtotime($position->PositionHistory->StartDate->YearMonth . "-01"));
									}
								
									// Start Date cannot be empty. move to next iteration if null
									if (!isset($position_from)) { continue; } 
					
									$this->model_positions_of_users->position_from = $position_from;
					
									// if notApplicable or position_from is bigger than EndDate->Year then positon_to will not be reassigned:
									$this->model_positions_of_users->position_to = null;
									$this->model_positions_of_users->position_current = 1;
		
									if ( $position->PositionHistory->EndDate != "notApplicable" || $position->PositionHistory->EndDate->Year != "notKnown" ) {
										if (isset($position->PositionHistory->EndDate->Year)) { // Year === YYYY
											$position_to = date("Y-m-d 00:00:00", strtotime($position->PositionHistory->EndDate->Year . "-01-01"));	
										} else if (isset($position->PositionHistory->EndDate->YearMonth)) { // YearMonthg === YYYY-MM
											$position_to = date("Y-m-d 00:00:00", strtotime($position->PositionHistory->EndDate->YearMonth . "-01"));
										} else {
											$position_to = null;
										}
									
										if ($position_to != null && strtotime($this->model_positions_of_users->position_from) < strtotime($position_to)) {
											$this->model_positions_of_users->position_to = $position_to;
											$this->model_positions_of_users->position_current = 0;
										}
									}
		
									$this->model_positions_of_users->positions_of_users_id = null;
									$this->model_positions_of_users->deleted = 0;
		
									// Check if position already exist and begin transaction if false
									if ( ! $this->model_positions_of_users->is_position_exists() ) {
										$this->db->trans_begin();
					
										$this->model_positions_of_users->create();
					
										if ($this->db->trans_status() === false) {
											$this->db->trans_rollback();
											$this->debug_cv('Missed Position ' . $key . ' (Database error)', $position);
											continue;
										} else {
											$this->db->trans_commit();
											$this->debug_cv('Added Position ' . $key, $position);
										}
									}
								}
							}
						}
					}
					
		
					$educations = isset($resume->EducationHistory->SchoolOrInstitution) ? $resume->EducationHistory->SchoolOrInstitution : null;
	
					if (isset($educations) && !empty($educations)) {
						$this->load->model('model_schools');
						$this->load->model('model_schools_of_users');
						if (is_array($educations)) {
							$this->debug_cv('Education items found', count($educations));
							// array of education objects
							foreach ($educations as $key => $education) {
								// check if school name exists in schools dictionary, retreive id if created or existed:
								if ( ! $this->handle_education_cv($user_id, $education) ) { 
									$this->debug_cv('Education item ' . $key . ' missed', $education); 
									continue; 
								} else {
									$this->debug_cv('Education item ' . $key . ' added', $education); 
								}
							}
						} else {
							$this->debug_cv('Education items found', 1);
							// single education object
							if( ! $this->handle_education_cv($user_id, $educations) ) {
								$this->debug_cv('Education item added', false);
							} else {
								$this->debug_cv('Education item added', true);
							}
						}
					} // end of educations
	
					// Languages
					if (isset($resume->Languages)) {
						$this->load->model('model_languages');
						$this->load->model('model_languages_of_users');
						if (isset($resume->Languages->Language) && !empty($resume->Languages->Language) && count($resume->Languages->Language) > 0) {
							$this->debug_cv('Language items found', count($resume->Languages->Language));
							// lanaguage is an array
							foreach ($resume->Languages->Language as $key => $language) {
								if ( ! $this->handle_lanaguage_cv($user_id, $language) ) { 
									$this->debug_cv('Language item ' . $key . ' missed', $language); 
									continue; 
								} else {
									$this->debug_cv('Language item ' . $key . ' added', $language); 
								}
							}
						} else
						if (isset($resume->Languages->Language) && !empty($resume->Languages->Language)) {
							$this->debug_cv('Language items found', 1);
							// singule lanauge object
							if ( ! $this->handle_lanaguage_cv($user_id, $resume->Languages->Language) ) {
								$this->debug_cv('Language item added', false);
							} else {
								$this->debug_cv('Language item added', true);
							}
						}
					} // end of languages
	
					if (isset($resume->Competency) && !empty($resume->Competency)) {
						$this->load->model('model_technical_abilities');
						$this->load->model('model_technical_abilities_of_users');
	
						$this->model_technical_abilities_of_users->user_id = $user_id;
						$tech_skills = $this->model_technical_abilities_of_users->get();
	
						if (count($resume->Competency) > 0) {
							$this->debug_cv('Skills items found', count($resume->Competency));
							foreach ($resume->Competency as $key => $skills) {
								if (!$this->handle_competency_cv($user_id, $skills, $tech_skills)) { 
									$this->debug_cv('Skill item ' . $key . ' missed', $skills); 
									continue; 
								} else {
									$this->debug_cv('Skill item ' . $key . ' added', $skills); 
								}
							}
						} else {
							$this->debug_cv('Skills items found', 1);
							
							if ( ! $this->handle_competency_cv($user_id, $resume->Competency, $tech_skills) ) {
								$this->debug_cv('Skill item added', false);
							} else {
								$this->debug_cv('Skill item added', true);
							}
						}
					} // end of skills
	
					if (ENVIRONMENT == 'staging' || ENVIRONMENT == 'development') {
						$this->response( [
							'status'   => true,
							'data' => $this->debug_cv()
						], Base_Controller::HTTP_OK );
					} else {
						$this->response( [
							'status'   => true,
						], Base_Controller::HTTP_OK );
					}
				} else {
					// hrxml_profile returned empty from daxtra parsing service processCV
					$this->response([
						'status' => false,
						'message' => "cv parse returned empty, choose only supported files."
					], Base_Controller::HTTP_BAD_REQUEST);
				}
			}
		}
	}*/

	public function set_default_experiance($user_id){
		$this->load->model('model_positions_of_users');
		$this->model_positions_of_users->user_id = $user_id;
		$this->model_positions_of_users->city_id = 15;
		$this->model_positions_of_users->company_id = 0;
		$this->model_positions_of_users->job_title_id =1200;
		$this->model_positions_of_users->industry_id =NULL;
		$this->model_positions_of_users->position_from =date('Y-m-d H:i:s');
		$this->model_positions_of_users->position_to =date('Y-m-d H:i:s');
		$this->model_positions_of_users->positions_of_users_id = null;
		$this->model_positions_of_users->deleted = 0;
		$this->model_positions_of_users->create();
	}
	private function handle_lanaguage_cv($user_id, $language) {
		if (!isset($language->LanguageCode)) {
			return false;
		}
		$language_code = $this->model_languages->get_language_by_code(strtoupper($language->LanguageCode));
		$this->model_languages_of_users->user_id = $user_id;
		$result = $this->model_languages_of_users->get_user_language();

		if (isset($language_code) && !empty($language_code)) {
			$this->model_languages_of_users->language_id = $language_code->id;
		}else{
			return false;
		}

		$language_level = array(
			"NATIVE" => "3",
			"EXCELLENT" => "3",
			"FLUENT" => "3",
			"ADVANCED" => "3",
			"INTERMEDIATE" => "2",
			"BASIC" => "1"
		);

		// some situations language->Comments is empty while LanguageCode exists. defaulting to BASIC in case empty.
		if (isset($language->Comments) && !empty($language->Comments)) {
			$this->model_languages_of_users->language_level = $language_level[(string) $language->Comments];
		} else { // default to basic level
			$this->model_languages_of_users->language_level = $language_level["BASIC"];
		}

		$this->db->trans_begin();
		// add language to active user 
		$this->model_languages_of_users->insert_undelete_language();

		if ($this->db->trans_status() === false) {
			$error_message = $this->get_transaction_error();
			$this->db->trans_rollback();
			echo 'Languages error: ' . $error_message;
		} else {
			$this->db->trans_commit();
		}
	} 

	/**
	 * Handle Daxtra cv Education endpoint
	 *
	 * @param [number] $user_id
	 * @param [Object] $education
	 * @return void
	 */
	private function handle_education_cv($user_id, $education) {
		$anydate = isset($education->DatesOfAttendance->StartDate["AnyDate"]) ? $education->DatesOfAttendance->StartDate["AnyDate"] : "";
		if ( // check necessary fields exist and are useful:
			!isset($education->SchoolName)
			|| !isset($education->Degree["degreeType"])
		) { return false; } // Do nothing, continue to the next iteration
		
		$this->model_schools_of_users->user_id = $user_id;
		
		$this->model_dictionary->model = $this->model_schools;
		$school_name = $education->SchoolName;

		$schools = $this->model_dictionary->get_dictionary('name', $school_name);

		if (isset($schools) && !empty($schools) && count($schools) > 0) {
			// Got a list, choosing the first
			$this->model_schools_of_users->school_id = $schools[0]->id;
		} else {
			// Creating a new and adding
			$this->model_schools->school_name = ucfirst((string)$school_name);
			$id_school=$this->model_schools->item_add_school();

			$this->model_schools_of_users->school_id = $id_school;
		}

		if (!isset($this->model_schools_of_users->school_id)) { return false; }

		if (isset($education->DatesOfAttendance->StartDate->Year)) { // Year === YYYY
			$school_from = date("Y-m-d 00:00:00", strtotime($education->DatesOfAttendance->StartDate->Year . "-01-01"));
		} else if (isset($education->DatesOfAttendance->StartDate->YearMonth)) { // YearMonthg === YYYY-MM
			$school_from = date("Y-m-d 00:00:00", strtotime($education->DatesOfAttendance->StartDate->YearMonth . "-01"));
		}

		// Start Date cannot be empty. move to next iteration if null
		if (!isset($school_from)) { $school_from=date("Y-m-d 00:00:00",mktime(0,0,0,1,1,1970)); }

		$this->model_schools_of_users->school_from = $school_from;

		$this->model_schools_of_users->school_to = null;
		
		if ( // check EndDate is valid
			isset($education->DatesOfAttendance->EndDate)
			|| $education->DatesOfAttendance->EndDate->Year != "notKnown"
			|| $education->DatesOfAttendance->EndDate->Year != "notApplicable"
		) {
			if (isset($education->DatesOfAttendance->EndDate->Year)) { // Year === YYYY
				$school_to = date("Y-m-d 00:00:00", strtotime($education->DatesOfAttendance->EndDate->Year . "-01-01"));
			} else if (isset($education->DatesOfAttendance->EndDate->YearMonth)) { // YearMonthg === YYYY-MM
				$school_to = date("Y-m-d 00:00:00", strtotime($education->DatesOfAttendance->EndDate->YearMonth . "-01"));
			} else {
					$school_to = null;	
			}
			if($school_from=='1970-01-01 00:00:00'){
					$school_to=$school_from;
			}
			
			if ($school_to != null && strtotime($this->model_schools_of_users->school_from) <= strtotime($school_to)) {
				$this->model_schools_of_users->school_to = $school_to;
			}
		}
		// current is based on valid/existent EndDate value
		$this->model_schools_of_users->school_current = ($this->model_schools_of_users->school_to === null) ? 1 : 0;

		// degreeType is withing a xml @attribute, retreive and string it
		$degreeType = (string) $education->Degree["degreeType"];

		if (isset($degreeType)) {

			/** For reference.
			 * 	Findable db education levels list: 
			 * 
			 * "1" => "None",
			 * "3" => "Some High School",
			 * "4" => "High School degree",
			 * "5" => "Some college",
			 * "6" => "Associate degree",
			 * "7" => "Bachelor's degree",
			 * "8" => "Master's degree",
			 * "9" => "Professional certificate",
			 * "10"=> "Doctorate",
			 * "11"=> "PHD"
			 */

			// Daxtra degreeList for degreeType mapped to Findable education levels list by id:
			$levels = array(
				"doctorate" => "10",
				"some post-graduate" => "9",
				"masters" => "8",
				"postprofessional" => "9",
				"professional" => "9",
				"bachelors" => "7",
				"associates" => "6",
				"intermediategraduate" => "5",
				"certification" => "9",
				"vocational" => "9",
				"HND/HNC or equivalent" => "9",
				"some college" => "5",
				"high school or equivalent" => "4",
				"some high school or equivalent" => "3",
				"secondary" => "3",
				"ged" => "3"
			);

			$this->db->trans_begin();

			// map received degreeType to findable levels
			$this->model_schools_of_users->school_education_level = $levels[$degreeType];

			// Create the school record
			$this->model_schools_of_users->create_school();

			if ($this->db->trans_status() === false) {
				$this->db->trans_rollback();
				return false;
			} else {
				$this->db->trans_commit();
				return true;
			}
		} // degreeType is mandetory, if none exist continue to the next iteration
		else {
			return false;
		}
	}

/*	private function handle_education_cv($user_id, $education) {
		$anydate = isset($education->DatesOfAttendance->StartDate["AnyDate"]) ? $education->DatesOfAttendance->StartDate["AnyDate"] : "";
		if ( // check necessary fields exist and are useful:
			!isset($education->SchoolName)
			|| !isset($education->Degree["degreeType"])
			|| !isset($education->DatesOfAttendance->StartDate)
			|| !isset($education->DatesOfAttendance->StartDate->Year)
			|| $anydate  === "notKnown"
		) { return false; } // Do nothing, continue to the next iteration
		
		$this->model_schools_of_users->user_id = $user_id;
		
		$this->model_dictionary->model = $this->model_schools;
		$school_name = $education->SchoolName;

		$schools = $this->model_dictionary->get_dictionary('name', $school_name);

		if (isset($schools) && !empty($schools) && count($schools) > 0) {
			// Got a list, choosing the first
			$this->model_schools_of_users->school_id = $schools[0]->id;
		} else {
			// Creating a new and adding
			$this->model_schools->school_name = ucfirst((string)$school_name);
			$id_school=$this->model_schools->item_add_school();

			$this->model_schools_of_users->school_id = $id_school;
		}

		if (!isset($this->model_schools_of_users->school_id)) { return false; }

		if (isset($education->DatesOfAttendance->StartDate->Year)) { // Year === YYYY
			$school_from = date("Y-m-d 00:00:00", strtotime($education->DatesOfAttendance->StartDate->Year . "-01-01"));
		} else if (isset($education->DatesOfAttendance->StartDate->YearMonth)) { // YearMonthg === YYYY-MM
			$school_from = date("Y-m-d 00:00:00", strtotime($education->DatesOfAttendance->StartDate->YearMonth . "-01"));
		}

		// Start Date cannot be empty. move to next iteration if null
		if (!isset($school_from)) { return false; }

		$this->model_schools_of_users->school_from = $school_from;

		$this->model_schools_of_users->school_to = null;
		
		if ( // check EndDate is valid
			isset($education->DatesOfAttendance->EndDate)
			|| $education->DatesOfAttendance->EndDate->Year != "notKnown"
			|| $education->DatesOfAttendance->EndDate->Year != "notApplicable"
		) {
			if (isset($education->DatesOfAttendance->EndDate->Year)) { // Year === YYYY
				$school_to = date("Y-m-d 00:00:00", strtotime($education->DatesOfAttendance->EndDate->Year . "-01-01"));
			} else if (isset($education->DatesOfAttendance->EndDate->YearMonth)) { // YearMonthg === YYYY-MM
				$school_to = date("Y-m-d 00:00:00", strtotime($education->DatesOfAttendance->EndDate->YearMonth . "-01"));
			} else {
				$school_to = null;
			}

			if ($school_to != null && strtotime($this->model_schools_of_users->school_from) < strtotime($school_to)) {
				$this->model_schools_of_users->school_to = $school_to;
			}
		}
		// current is based on valid/existent EndDate value
		$this->model_schools_of_users->school_current = ($this->model_schools_of_users->school_to === null) ? 1 : 0;

		// degreeType is withing a xml @attribute, retreive and string it
		$degreeType = (string) $education->Degree["degreeType"];

		if (isset($degreeType)) {

			//* For reference.
			 //* 	Findable db education levels list: 
			 //* 
			 //* "1" => "None",
			// * "3" => "Some High School",
			// * "4" => "High School degree",
			// * "5" => "Some college",
			// * "6" => "Associate degree",
			// * "7" => "Bachelor's degree",
			// * "8" => "Master's degree",
			// * "9" => "Professional certificate",
			// * "10"=> "Doctorate",
			// * "11"=> "PHD"
			 

			// Daxtra degreeList for degreeType mapped to Findable education levels list by id:
			$levels = array(
				"doctorate" => "10",
				"some post-graduate" => "9",
				"masters" => "8",
				"postprofessional" => "9",
				"professional" => "9",
				"bachelors" => "7",
				"associates" => "6",
				"intermediategraduate" => "5",
				"certification" => "9",
				"vocational" => "9",
				"HND/HNC or equivalent" => "9",
				"some college" => "5",
				"high school or equivalent" => "4",
				"some high school or equivalent" => "3",
				"secondary" => "3",
				"ged" => "3"
			);

			$this->db->trans_begin();

			// map received degreeType to findable levels
			$this->model_schools_of_users->school_education_level = $levels[$degreeType];

			// Create the school record
			$this->model_schools_of_users->create_school();

			if ($this->db->trans_status() === false) {
				$this->db->trans_rollback();
				return false;
			} else {
				$this->db->trans_commit();
				return true;
			}
		} // degreeType is mandetory, if none exist continue to the next iteration
		else {
			return false;
		}
	}*/

	private function handle_competency_cv($user_id, $skill, $user_skills) {
		
		if (!isset($skill->CompetencyEvidence) || empty($skill->CompetencyEvidence) || !isset($skill->CompetencyWeight)) { 
			return false; 
		}
		$skillLevel = 23;
		$skillName = "";

		if ( strpos($this->xml_attribute($skill, 'description'), 'Skill') !== FALSE) {
			$skillName = $this->xml_attribute($skill, 'name');

			if ( $this->xml_attribute($skill->CompetencyWeight, 'type') == 'skillLevel' ) {
				$skillLevelParsed = (int) $skill->CompetencyWeight->NumericValue;
				if ( $skillLevelParsed > $skillLevel ) {
					$skillLevel = $skillLevelParsed;
				}
			}

			$this->model_technical_abilities_of_users->user_id = $user_id;
			$this->model_technical_abilities_of_users->technical_ability_level = $skillLevel;

			$this->db->trans_begin();

			$this->model_technical_abilities->technical_ability_name = ucfirst( $skillName );
			$this->model_technical_abilities->item_add();

			if (isset($this->model_technical_abilities->technical_ability_id)) {
				$this->model_technical_abilities_of_users->technical_ability_id = $this->model_technical_abilities->technical_ability_id;
			} else {
				return false; // record does not exist and adding failed.
			}

			$this->model_technical_abilities_of_users->technical_ability_level = $skillLevel;
			$this->model_technical_abilities_of_users->insert_undelete_skill();

			if ($this->db->trans_status() === false) {
				$this->db->trans_rollback();
				return false;
			} else {
				$this->db->trans_commit();
				return true;
			}
		}
	}

	public function has_cv_get($user_id) {
		$valid = true;
		$exists = false;

		if (isset($user_id) && $user_id === $this->get_active_user()) {

			$this->config->load('upload');
			$config = $this->config->item('upload');

			// Load the file upload library
			$this->load->library( 'upload', $config );

			$base_path = (ENVIRONMENT == 'development') ? realpath(FCPATH . 'assets/files') . DIRECTORY_SEPARATOR : $this->upload->get_upload_path() . '/user_files/';

			$wanted_file = 'resume_' . $user_id;
			$file_path = $base_path . $wanted_file;

			if (file_exists($file_path . '.pdf') != false) {
				$file_type = 'pdf';
				$exists = true;	
			} else
			if (file_exists($file_path . '.doc') != false) {
				$file_type = 'doc';
				$exists = true;
			} else
			if (file_exists($file_path . '.docx') != false) {
				$file_type = 'docx';
				$exists = true;
			}

			if ($exists === false && !isset($file_type)) {
				$this->response([
					'status' => false,
					'message' => 'File does not exist'
				], Base_Controller::HTTP_BAD_REQUEST);
			} else {
				$this->response([
					'status' => true,
					'fileType' => $file_type,
				], Base_Controller::HTTP_OK);
			}
		} else {
			$this->response([
				'status' => false,
				'message' => 'User Id does not match or does not exist'
			], Base_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function cv_get($user_id, $file_extension) {

		$valid = true;

		if (
			isset( $user_id )
			&& $user_id === $this->get_active_user()
			&& isset( $file_extension )
			&& preg_match( "/(pdf|doc|docx)/", $file_extension )
		) {
			$this->config->load('upload');
			$config = $this->config->item('upload');

			// Set the file name as the user's id
			$config['file_name'] = "resume_" . $user_id .".". $file_extension;

			// Load the file upload library
			$this->load->library( 'upload', $config );

			$path = (ENVIRONMENT == 'development') ? realpath(FCPATH . 'assets/files') . DIRECTORY_SEPARATOR : $this->upload->get_upload_path() . '/user_files/';

			$file_data['full_path'] = $path . $config['file_name'];

			$handle = fopen( $file_data['full_path'], "r" );
			$fileContents = fread( $handle, filesize( $file_data['full_path'] ) );
			fclose( $handle );

			if ( empty( $fileContents ) && count( $fileContents ) === 0 ) {
				$valid = false;
			}

			if ( !$valid ) {
				$this->response([
					'status' => false,
					'message' => 'File Not Found'
				], Base_Controller::HTTP_BAD_REQUEST);
			} else {

				$file_path = $path . $config['file_name'];

				if ( file_exists($file_path) != false ) {
					$config['file_name_full'] = $file_path;
					$file = $this->upload->get_file($config['file_name_full']);
				} 

				$content_file_type = 'application/pdf';
				
				if ( preg_match( "/doc/", $file_extension ) ) {
					$content_file_type = 'application/msword';
				} else
				if ( preg_match( "/docx/", $file_extension ) ) {
					$content_file_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
				}

				if ( !isset( $file ) ) {
					$this->response([
						'status' => false,
						'message' => 'File Not Found'
					], Base_Controller::HTTP_BAD_REQUEST);
				} else {
					header('Content-Description: File Transfer');
					header('Content-Transfer-Encoding: binary');
					header('Cache-Control: public, must-revalidate, max-age=0');
					header('Pragma: public');
					header('Expires: Sat, 26 Jul 1998 05:00:00 GMT');
					header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
					header('Content-Type: application/force-download');
					header('Content-Type: application/octet-stream', false);
					header('Content-Type: application/download', false);
					header('Content-Type: ' . $content_file_type, false);
					if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) or empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
						// don't use length if server using compression
						header('Content-Length: ' . strlen($file));
					}
					header('Content-disposition: attachment; filename="' . $config['file_name'] . '"');

					
					echo $this->upload->serve_file( $file . '' );
					
				}
			}
		} else {
			$this->response([
				'status' => false,
				'message' => 'User Id does not match or does not exist'
			], Base_Controller::HTTP_BAD_REQUEST);
		}
	}

	private function xml_attribute($object, $attribute)
	{
		if(isset($object[$attribute]))
			return (string) $object[$attribute];
	}

	private function debug_cv($key = false, $message = null) {
		if (!$key && is_null($message)) {
			return $this->cv_debug;
		}

		if (is_string($key) && is_null($message)) {
			return $this->cv_debug[$key] ?: false;
		}

		if (is_string($key) && !is_null($message)) {
			$this->cv_debug[$key] = $message;
		}
		
		if ($key === false && !is_null($message)) {
			$key = 'Debug Info ('.microtime().')';
			$this->cv_debug[$key] = $message;
		}
	}


	/*
		User subscription API
	*/

	/**
	 * subscription_post
	 *
	 * Purchase subscription for a user
	*/
	public function subscription_post( $user_id ) {
		$valid                    = true;
		$form_validated           = true;
		$is_valid_package         = true;
		$is_valid_customer        = true;
		$has_credits              = true;
		$customer_stripe_creation = true;
		$customer_stripe_update   = true;
		$customer_db_creation     = true;
		$payment_completed        = true;

		$stripe_token = $this->post( 'stripe_token' );
		$package_id   = 'plan_E6ZHTQOnvN6F5k'; // TODO: get real plan id from the config
		$billing_name = $this->post( 'billing_name' );
		
		if ( is_null( $package_id ) ) {
			$valid = false;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $this->get_active_user() );

			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}

			// Retrieve the subscription object
			$this->load->model( 'model_subscription' );
			$this->model_subscription->user_id = $user_id;
			$is_valid_customer = $this->model_subscription->get();


			// Determine the validation rules required for this request
			if ( ! $is_valid_customer || ( $this->model_subscription->payment_stripe_token != $stripe_token && ! is_null( $stripe_token ) ) ) {
				$validation_rules[] = 'stripe_token';
			}

			// Validate the input parameters
			$form_validated = $this->validateRequestParameters( $this->post(), $validation_rules );
			
		}

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
			// Load the Stripe wrapper library
			$this->load->library( 'Stripe' );

			// Start a manual database transaction
			$this->db->trans_begin();

			// Create customer_id if not exist in database
			if ( ! $is_valid_customer ) {
				$customer_stripe_creation = $this->stripe->create_customer( array(
					"description" => $billing_name,
					"email"       => $this->model_users->email,
					"source"      => $stripe_token
				) );

				if ( $customer_stripe_creation ) {
					$this->model_subscription->payment_customer_id  = $this->stripe->customer->id;
					$this->model_subscription->payment_stripe_token = $stripe_token;
					$customer_db_creation = $this->model_subscription->create();
				}
			} else if ( ( $this->model_subscription->payment_stripe_token != $stripe_token && ! is_null( $stripe_token ) ) ) {
				// Load the customer object
				$this->stripe->get_customer( $this->model_subscription->payment_customer_id );
				// Update the customer's credit card token
				$customer_stripe_creation = $this->stripe->update_card( $stripe_token );
				// Update the customer's billing name if sent
				if ( ! is_null( $billing_name ) ) {
					$customer_stripe_update = $this->stripe->update_customer( $this->model_subscription->payment_customer_id, [
						'description' => $billing_name
					] );
				}

				if ( $customer_stripe_creation ) {
					$this->model_subscription->payment_customer_id  = $this->stripe->customer->id;
					$this->model_subscription->payment_stripe_token = $stripe_token;
					$customer_db_creation = $this->model_subscription->update();
				}
			}

			if ( ! $customer_stripe_creation || ! $customer_stripe_update ) {
				// Failed to create / update a customer in Stripe, don't continue with the charge process.
				$this->response( [
					'status'  => false,
					'message' => $this->stripe->get_last_error()
				], Base_Controller::HTTP_BAD_REQUEST );
			} else if ( ! $customer_db_creation ) {
				$this->response( [
					'status'  => false,
					'message' => 'Failed to create a customer'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {

				if ( $this->model_subscription->subscriptions_stripe_id ) {
					$this->response( [
						'status'  => false,
						'message' => 'Bad request'
					], Base_Controller::HTTP_BAD_REQUEST );
				} else {
					$this->stripe->set_customer( $this->model_subscription->payment_customer_id );

					// Create the Subscription
					$subscribe_completed = $this->stripe->create_subscription( $package_id );

					if ( ! $subscribe_completed ) {
						$this->db->trans_rollback();

						$this->response( [
							'status'  => false,
							'message' => $this->stripe->get_last_error()
						], Base_Controller::HTTP_BAD_REQUEST );
					} else {
						$this->model_subscription->subscriptions_stripe_id = $subscribe_completed->subscriptions->id;
						$this->model_subscription->update();

						$this->db->trans_commit();

						$this->response( [
							'status' => true,
							'subscription' => $subscribe_completed
						], Base_Controller::HTTP_OK );
					}
				}
			}
		}
	}

	/**
	 * subscription_put
	 *
	 * update subscription for a user
	*/
	public function subscription_put( $user_id ) {
		$valid                    = true;
		$form_validated           = true;
		$is_valid_package         = true;
		$is_valid_customer        = true;
		$has_credits              = true;
		$customer_stripe_creation = true;
		$customer_stripe_update   = true;
		$customer_db_creation     = true;
		$payment_completed        = true;

		$stripe_token = $this->post( 'stripe_token' );
		$package_id   = 'plan_E6ZHTQOnvN6F5k'; // TODO: get real plan id from the config
		$billing_name = $this->post( 'billing_name' );
		
		if ( is_null( $package_id ) ) {
			$valid = false;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $this->get_active_user() );

			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}

			// Retrieve the subscription object
			$this->load->model( 'model_subscription' );
			$this->model_subscription->user_id = $user_id;
			$is_valid_customer = $this->model_subscription->get();

		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else  {
			// Load the Stripe wrapper library
			$this->load->library( 'Stripe' );

			// Check valid customer
			if ( ! $is_valid_customer ) {
				$this->response( [
					'status'  => false,
					'message' => 'Customer dont exists'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {

				if ( ! $this->model_subscription->subscriptions_stripe_id ) {
					$this->response( [
						'status'  => false,
						'message' => 'Bad request'
					], Base_Controller::HTTP_BAD_REQUEST );
				} else {
					$this->load->library( 'Stripe' );

					$updateSubscription = $this->stripe->update_subscription($this->model_subscription->subscriptions_stripe_id, false);

					if ($updateSubscription) {
						
						$this->response( [
							'status' => true,
							'message' => 'Subscription updated.'
						], Base_Controller::HTTP_OK );
					} else {
						$this->response( [
							'status'  => false,
							'message' => $this->stripe->get_last_error()
						], Base_Controller::HTTP_BAD_REQUEST );
					}
				}
			}
		}
	}

	/**
	 * subscription_get
	 *
	 * Get subscription for a user
	*/
	public function subscription_get( $user_id ) {
		$this->load->model( 'model_subscription' );
		$this->model_subscription->user_id = $user_id;
		$have_subscription = $this->model_subscription->get();
		
		if($have_subscription){
			$this->load->library( 'Stripe' );

			$stripeSubscription = $this->stripe->get_subscription($this->model_subscription->subscriptions_stripe_id);

			$this->response( [
				'status' => true,
				'data' => array(
					'subscription' => $stripeSubscription->subscriptions,
					'user_id' => $this->model_subscription->user_id,
					'payment_stripe_token' => $this->model_subscription->payment_stripe_token,
					'payment_customer_id' => $this->model_subscription->payment_customer_id,
					'subscriptions_stripe_id' => $this->model_subscription->subscriptions_stripe_id,
					'created' => $this->model_subscription->created
				)
			], Base_Controller::HTTP_OK );
		} else {
			$this->response( [
				'status'  => false,
				'message' => 'Cant get user subscription or User dont have one.'
			], Base_Controller::HTTP_OK );
		}
	}
	/**
	 * subscription_get
	 *
	 * Get subscription for a profile
	*/
	public function subscription_profile_get( $user_id ) {
		$this->load->model( 'model_subscription' );
		$this->model_subscription->user_id = $user_id;
		$have_subscription = $this->model_subscription->get();
		
		if($have_subscription){
			$this->load->library( 'Stripe' );
			return $stripeSubscription = $this->stripe->get_subscription($this->model_subscription->subscriptions_stripe_id);
		} else {
			return false;
		}
	}
	/**
	 * subscription_delete
	 *
	 * Delete subscription for a user
	*/
	public function subscription_delete( $user_id ) {
		$this->load->model( 'model_subscription' );
		$this->model_subscription->user_id = $user_id;
		$have_subscription = $this->model_subscription->get();

		if($have_subscription){
			$this->load->library( 'Stripe' );

			// TODO: update $cancelSubscription
			$cancelSubscription = $this->stripe->update_subscription($this->model_subscription->subscriptions_stripe_id, true);
			// $cancelSubscription = true;
			if ($cancelSubscription) {
				
				$this->response( [
					'status' => true,
					'message' => 'Subscription cancelled.'
				], Base_Controller::HTTP_OK );
			} else {
				$this->response( [
					'status'  => false,
					'message' => $this->stripe->get_last_error()
				], Base_Controller::HTTP_BAD_REQUEST );
			}
		} else { 
			$this->response( [
				'status'  => false,
				'message' => 'Cant cancel user subscription.'
			], Base_Controller::HTTP_BAD_REQUEST );
		}
	}
	/**
	 * function to convert ure in tinny url(currently this method not used as per reqirment)
	 *
	 * return tiny url
	*/
	public function converturl_get($userid) {
		if(isset($userid)){
			$url=$_SERVER['HTTP_ORIGIN'].'/user/'.base64_encode($userid);
			$ch = curl_init(); 
			$timeout = 5; 
			curl_setopt($ch, CURLOPT_URL, 'http://tinyurl.com/api-create.php?url='.$url); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 
			$data = curl_exec($ch); 
			curl_close($ch); 
			$this->response( ['status'   => true,
				'url' => $data], Base_Controller::HTTP_OK );
		}else{
			$this->response([
				'status' => false,
				'message' => 'User Id does not match or does not exist'
			], Base_Controller::HTTP_BAD_REQUEST);
		}

	}
	public function uploaded_candidate_get(){
		$user_id_creator = $this->get_active_user();
		if ( $this->is_recruiter() ) {
			$business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$business_id = $this->get_manager_business();
		} else {
			$business_id = null;
		}
		$user=$this->get_user( $this->get_active_user() );
		$this->load->model( 'Model_invitations_of_business' );
		$this->Model_invitations_of_business->business_id=$business_id;
		$this->Model_invitations_of_business->accepted=1;
		$this->Model_invitations_of_business->deleted=0;
		$this->Model_invitations_of_business->email=$this->model_users->email;
		$user_ids=array();
		if(!$this->Model_invitations_of_business->is_exists()){
			$this->load->model( 'model_business_users' );
			$this->model_business_users->business_id=$business_id;
		    $associates=$this->model_business_users->get_my_associate_user();
			
			foreach($associates as $user){
				array_push($user_ids, $user->user_id);
			}
		}
		else{
			array_push($user_ids, $user->user_id);
		}
		$this->load->model('Model_candidate_upload');
		$this->Model_candidate_upload->creator_id=$user_ids;

		$values=$this->Model_candidate_upload->get();
		$this->response( $values, Base_Controller::HTTP_OK );
	}

	public function invitation_email_get($invitation_id){
		$this->load->model( 'Model_invitations_of_business' );
		$this->Model_invitations_of_business->token=$invitation_id;
		$this->Model_invitations_of_business->accepted=0;
		$this->Model_invitations_of_business->deleted=0;
		$data=$this->Model_invitations_of_business->get_by_token_email();
		$this->response( $data, Base_Controller::HTTP_OK );
	}
}
