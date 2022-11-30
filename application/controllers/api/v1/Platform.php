<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Platform extends Base_Controller {

	function __construct() {
		parent::__construct();
	}

	/**
	 * stats_get
	 *
	 * Get the statistics of the platform's activity
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function stats_get() {
		$valid = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Handle the request
			$this->load->model( 'model_users' );
			$this->load->model( 'model_business' );
			$this->load->model( 'model_sys_log' );
			$this->load->model( 'model_purchase_history' );

			$platformUsersCount          = $this->model_users->count();
			$platformBusinessCount       = $this->model_business->count();
			$platformProfileShareCount   = $this->model_sys_log->count_profile_shares();
			$platformCreditPurchaseCount = $this->model_purchase_history->count_credits_purchased();
			$this->response( [
				'users'             => $platformUsersCount->total,
				'applicants'        => $platformUsersCount->applicant,
				'recruiter'         =>  $platformUsersCount->recruiter,
				'businesses'        => $platformUsersCount->manager,
				'profile_shares'    => $platformProfileShareCount,
				'credits_purchased' => $platformCreditPurchaseCount
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * login_post
	 *
	 * Login on behalf of a User / Business
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function login_post( $type, $id ) {
		$valid          = true;
		$form_validated = true;

		// Validate the incoming request parameters
		$form_validated = $this->validateRequestParameters( array( 'type' => $type, 'id' => $id ), array(
			'user_type',
			'entityId'
		) );

		// Check that the user exists in the platform
		$this->load->model('model_users');
		$this->model_users->user_id = $id;
		if( ! $this->model_users->get() ) {
			$valid = false;
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
			$this->load->library( 'Auth' );
			$this->auth->key                         = $this->rest->key;
			$this->auth->{'active_' . $type . '_id'} = $id;
			$result = $this->auth->{'update_active_' . $type}();

			$this->response( [
				'status' => $result
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * logout_post
	 *
	 * Stop making API calls on behalf of a User / Business
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function logout_post() {
		$valid = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->library( 'Auth' );
			$this->auth->key                = $this->rest->key;
			$this->auth->active_business_id = null;
			$this->auth->active_user_id     = null;

			$result = $this->auth->reset_active_data();

			$this->response( [
				'status' => $result
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * users_put
	 *
	 * Update the status of a user
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function users_put( $user_id ) {
		$valid          = true;
		$form_validated = true;

		$status = $this->request->body;
		$status = ( is_string( $status ) ) ? json_decode( $status, true ) : $status;

		$form_validated = $this->validateRequestParameters( $status, array(
			'user_status'
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
			$this->load->model( 'model_users' );
			$this->model_users->user_id = $user_id;

			if ( ! $this->model_users->get() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->model_users->status = $status['status'];
				$updated                   = $this->model_users->update();

				$this->response( [
					'status' => $updated
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * users_delete
	 *
	 * Delete user
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function users_delete( $user_id ) {
		$valid = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model('model_users');
			$this->model_users->user_id = $user_id;
			if ( ! $this->model_users->get() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->model_users->status = 'deleted';
				$updated = $this->model_users->update();

				if ( ! $updated ) {
					$this->response( [
						'status' => true,
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

	/**
	 * users_post
	 *
	 * Block user
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function users_post( $user_id ) {
		$valid = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model('model_users');
			$this->model_users->user_id = $user_id;
			if ( ! $this->model_users->get() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->model_users->status = 'banned';
				$updated = $this->model_users->update();

				if ( ! $updated ) {
					$this->response( [
						'status' => true,
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

	/**
	 * business_delete
	 *
	 * Delete business
	 *
	 * @access    public
	 *
	 * @param integer $business_id
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function business_delete( $business_id ) {
		$valid = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model('model_business');
			$this->model_business->business_id = $business_id;
			if ( ! $this->model_business->get_business() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->model_business->status = 'deleted';
				$updated = $this->model_business->update();

				if ( ! $updated ) {
					$this->response( [
						'status' => true,
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

	/**
	 * business_post
	 *
	 * Block business
	 *
	 * @access    public
	 *
	 * @param integer $business_id
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function business_post( $business_id ) {
		$valid = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model('model_business');
			$this->model_business->business_id = $business_id;
			if ( ! $this->model_business->get_business() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->model_business->status = 'banned';
				$updated = $this->model_business->update();

				if ( ! $updated ) {
					$this->response( [
						'status' => true,
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

	/**
	 * dictionary_get
	 *
	 * Get a collections of dictionary items
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function dictionary_get() {
		$valid = true;
		$form_validated = true;

		$dictionary = $this->get( 'dictionary' );
		$approved = $this->get( 'approved' );
		if(isset($_GET['offset'])){
			$offset=$this->get( 'offset' );
		}else{
			$offset=NULL;
		}
		$form_validated = $this->validateRequestParameters( ['dictionary' => $dictionary, 'approved' => $approved], array(
			'dictionary_name',
			'dictionary_item_state'
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

			// Get the internal name of the requested dictionary
			$dictionary_name = $this->model_dictionary->parse_dictionary_name($dictionary);

			if( ! $dictionary_name ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$model = 'model_' . $dictionary_name;
				$this->load->model( $model );
				$this->model_dictionary->model = $this->$model;

				// Get the dictionary items (without limit & offset)
				$items = $this->model_dictionary->get_segmented_items($approved,$offset);

				$this->response( $items, Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * dictionary_post
	 *
	 * Add new value to approved dictionary items
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function dictionary_post( ) {
		//FOR INDIA//
		$dictionary    = $this->post( 'dictionary' );
		$value = $this->post( 'value' );

		$valid = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->response( [
				'status' => true
			], Base_Controller::HTTP_OK );
		}
		//
	}

	/**
	 * dictionary_put
	 *
	 * Edit dictionary value
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function dictionary_put( ) {
		//FOR INDIA//
		$dictionary    = $this->post( 'dictionary' );
		$id = $this->post( 'id' );
		$value = $this->post( 'value' );

		$valid = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->response( [
				'status' => true
			], Base_Controller::HTTP_OK );
		}
		//
	}

	/**
	 * business_put
	 *
	 * Update the status of a business
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function business_put( $business_id ) {
		$valid          = true;
		$form_validated = true;
		$statusUpdated  = true;
		$expireUpdated  = true;

		$statusInput = $this->request->body;
		$statusInput = ( is_string( $statusInput ) ) ? json_decode( $statusInput, true ) : $statusInput;

		$form_validated = $this->validateRequestParameters( $statusInput, array(
			'business_status',
			'business_days'
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
			$this->load->model( 'model_business' );
			$this->model_business->business_id = $business_id;

			if ( ! $this->model_business->get_business() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$status = $this->has_param( $statusInput, 'status' );
				$days   = $this->has_param( $statusInput, 'days' );

				if ( ! is_null( $status ) ) {
					$this->model_business->status = $status;
					$statusUpdated                = $this->model_business->update();
				}

				if ( ! is_null( $days ) && $statusUpdated ) {
					$this->load->model( 'model_business_unique_applicants_expire' );
					$this->model_business_unique_applicants_expire->business_id            = $business_id;
					$this->model_business_unique_applicants_expire->business_unique_expire = $days * 86400;
					$expireUpdated                                                 = $this->model_business_unique_applicants_expire->insert_update();
				}

				$this->response( [
					'status' => $statusUpdated && $expireUpdated
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * requests_get
	 *
	 * Get a collection of applicant contact requests
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function requests_get() {
		$valid          = true;
		$form_validated = true;

		$offset = $this->get( 'offset' );

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( [ 'offset' => $offset ], array(
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
			$offset = $this->is_true_null( $offset ) ? 0 : $offset;

			$this->load->model( 'model_user_requests' );
			$requests = $this->model_user_requests->get( $offset );

			$this->response( $requests, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * requests_get
	 *
	 * Get a collection of applicant contact requests
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function applicants_get() {
		$valid          = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->library( 'upload' );
			$this->load->library( 'xls' );
			$this->load->model( 'model_admin_users_profiles' );
			$this->load->model( 'model_files' );
			$this->load->model( 'model_users' );
			$this->load->model( 'model_location' );
			$applicants = $this->model_users->get_all_users_for_admin( 0, null, null, $limit = 5000, [ 'applicant', 'manager', 'recruiter' ]);

			$this->xls->excel([
				'id' => 'User ID', 
				'firstname' => 'First name', 
				'lastname' => 'Last name', 
				'email' => 'Email',
				'phone_number' => 'Phone',
				'location' => 'Location',
				'status' => 'Status', 
				'role' => 'role', 
				'view_count' => 'Business clicks',
				'businesses_applied' => 'Businesses applied',
				'created' => 'Signup date', 
				'last_login' => 'Last login date'
			], $applicants['users'])->download();

			$this->response( $applicants, Base_Controller::HTTP_OK );
		}
	}
		/**
	 * requests_get
	 *
	 * Get a collection of recruiter contact requests
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function recruiter_get() {
		$valid          = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->library( 'upload' );
			$this->load->library( 'xls' );
			$this->load->model( 'model_admin_users_profiles' );
			$this->load->model( 'model_files' );
			$this->load->model( 'model_users' );
			$this->load->model( 'model_location' );
			$applicants = $this->model_users->get_all_users_for_admin( 0, null, null, $limit = 5000, 'recruiter');

			$this->xls->excel([
				'id' => 'User ID', 
				'firstname' => 'First name', 
				'lastname' => 'Last name', 
				'email' => 'Email',
				'phone_number' => 'Phone',
				'location' => 'Location',
				'status' => 'Status',  
				'view_count' => 'Business clicks',
				'businesses_applied' => 'Businesses applied',
				'created' => 'Signup date', 
				'last_login' => 'Last login date'
			], $applicants['users'])->download();

			$this->response( $applicants, Base_Controller::HTTP_OK );
		}
	}
	/**
	 * requests_get
	 *
	 * Get a collection of applicant contact requests
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function business_get() {
		$valid          = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->library( 'upload' );
			$this->load->library( 'xls' );
			$this->load->model( 'model_files' );
			$this->load->model( 'model_business' );
			$businesses = $this->model_business->get_all_business_for_admin();

			$this->xls->excel([
				'business_id' => 'Business ID', 
				'name' => 'Business Name', 
				'status' => 'Status', 
				'owner_email' => 'Manager email', 
				'owner_firstname' => 'Manager firstname', 
				'owner_lastname' => 'Manager lastname', 
				'owner_phone_number' => 'Manager phone',
				'web_address' => 'Web address', 
				'purchase_count' => 'Applicant purchases',
				'recruiters_count' => 'Recruiters',
				'business_created' => 'Signup date'
			], $businesses)->download();

			$this->response( $applicants, Base_Controller::HTTP_OK );
		}
	}
		/**
	 * verify_post
	 *
	 * verify business user 
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function approve_post( $user_id ) {
		$valid = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model('model_users');
			$this->model_users->user_id = $user_id;
			if ( ! $this->model_users->get() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->model_users->verified_by_admin =1;
				$updated = $this->model_users->update();

				if ( ! $updated ) {
					$this->response( [
						'status' => true,
						'message' => 'Bad request'
					], Base_Controller::HTTP_BAD_REQUEST );
				} else {
				// send email to client after admin approved
				$this->load->library( 'Template' );
					// Load the language file with the content of the email
				$this->lang->load( 'email' );

				$email_data = $this->lang->line( 'account_acctive' );
				// Add technical details to the email
				$email_data['details'] = "";
				$email_data['details'] .= "<a style='color: #2f5678;' href='".base_url()."/business/login'>Access Your Account</a>";

				$html = $this->template->view( 'admin_approved', $email_data, true );
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

					$this->response( [
						'status' => true
					], Base_Controller::HTTP_OK );
				}
			}
		}
	}
}
