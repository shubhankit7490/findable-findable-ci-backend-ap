<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Business extends Base_Controller {

	function __construct() {
		parent::__construct();
		$this->methods['oauth2callback_get']['key'] = false;
		$this->methods['payments_post']['key'] = false;
	}

	/**
	 * index_get
	 *
	 * Get information about the current business
	 *
	 * @access    public
	 *
	 * @param   integer $business_id
	 *
	 * @role    manager, admin
	 *
	 * @return    void
	 */
	public function index_get( $business_id = null ) {
		$valid          = true;
		$form_validated = true;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		if (!$this->check_status($this->model_users) && !$this->is_admin()) {
			$valid = false;
		} else {
			if ( ! $this->is_admin() && $this->get_manager_business() != $business_id ) {
				$valid = false;
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Generate a business object for a specific business
			if ( is_numeric( $business_id ) ) {
				$this->load->library( 'Upload' );

				// Get the manager's business details
				$this->load->model( 'model_location' );
				$this->load->model( 'model_company_types' );
				$this->load->model( 'model_industries' );
				$this->load->model( 'model_files' );
				$this->load->model( 'model_business' );
				$this->load->model( 'model_business_record' );
				$this->model_business->business_id = $business_id;
				$business                          = $this->model_business->getExtendedModel();

				if (!$business) {
					$this->response( [
						'status'  => false,
						'message' => 'Bad request'
					], Base_Controller::HTTP_BAD_REQUEST );
				} else {
					// Get the business step if the status is setup
					$business->step = null;
					if ( $business->status == 'setup' ) {
						// Checking if the business details were updated after creation
						$step1 = $this->model_business->is_business_profile_valid();
						if ( $step1 == 0 ) {
							$business->step = 1;
						} else {
							// Checking if the manager's profile was updated after creation
							$this->load->model( 'model_profiles' );
							$this->model_profiles->user_id = $this->get_active_user();
							$step2                         = $this->model_profiles->is_manager_profile_updated();
							if ( $step2 == 1 ) {
								$business->step = 2;
							} else {
								$business->step = null;
							}
						}
					}

					$this->response( $business, Base_Controller::HTTP_OK );
				}
			} else if ( $this->is_admin() && is_null( $business_id ) ) {

				$start  = $this->get( 'start' );
				$end    = $this->get( 'end' );
				$offset = $this->get( 'offset' );

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

				if ( ! $form_validated->result ) {
					$this->response( [
						'status'  => false,
						'message' => $form_validated->errors
					], Base_Controller::HTTP_NOT_ACCEPTABLE );
				} else {
					// Load the upload library for image serving
					$this->load->library( 'Upload' );

					$this->load->model( 'model_files' );
					$this->load->model( 'model_minimal_business_profiles' );
					$this->load->model( 'model_business' );

					$offset = $this->is_true_null( $offset ) ? 0 : $offset*50;
					$start  = $this->is_true_null( $start ) ? null : $start;
					$end    = $this->is_true_null( $end ) ? null : $end;

					$business = $this->model_business->get_all_business_admin( $offset, $start, $end );

					$this->response( $business, Base_Controller::HTTP_OK );
				}
			} else {
				$this->response( [
					'status'  => false,
					'message' => 'Not allowed'
				], Base_Controller::HTTP_METHOD_NOT_ALLOWED );
			}
		}
	}

	/**
	 * index_post
	 *
	 * Register a new business
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @return    void
	 */
	public function index_post() {
		$valid          = true;
		$form_validated = true;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		if ($this->is_admin()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		$business = $this->request->body;
		$business = ( is_string( $business ) ) ? json_decode( $business, true ) : $business;
		$validate_array=array(
				'business_name',
				'years_established',
				'business_location',
				'company_size',
				'location[city_name]',
				'industry[id]',
				'duns_number',
				'company_type',
				'web_address',
				'logo'
			);
		if(empty($business['web_address'])){
			unset($business['web_address']);
			unset($validate_array[8]);
		}
		if(empty($business['name'])){
			unset($business['name']);
			unset($validate_array[0]);
		}
		if ( is_null( $business ) || empty( $business ) || !$this->check_status($this->model_users) ) {
			$valid = false;
		} else {
			// Validating input parameters
			$form_validated = $this->validateRequestParameters( $business, $validate_array );
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
			$this->load->model('model_location');
			$city_id = $this->model_location->get_city_id($business['location'], false);
			
			

			// Load the business model
			$this->load->model( 'model_business' );
			$this->model_business->business_name             = (isset($business['name']))?$business['name']:$business['firstname'].' '.$business['lastname'];
			$this->model_business->business_year_established = $business['year_established'];
			$this->model_business->business_size             = $this->has_param( $business, 'size' );
			$this->model_business->city_id                   = $city_id;
			$this->model_business->industry_id               = $business['industry']['id'];
			$this->model_business->business_type_id          = $this->has_param( $business, 'type' );
			$this->model_business->business_web_address      = (isset($business['web_address']))?$business['web_address']:'';
			$this->model_business->business_duns_number      = $this->has_param( $business, 'duns' );
			$this->model_business->business_logo             = $this->has_param( $business, 'logo' );
			$this->model_business->status                    = 'active'; // The business will become active after the first payment is made

			// Load the business users model
			$this->load->model( 'model_business_users' );
			$this->model_business_users->user_id             = $this->get_active_user();
			$this->model_business_users->business_admin      = 1;
			$this->model_business_users->purchase_permission = 1;

			// Start a database transaction
			$this->db->trans_start();

			// Create the business entity
			$this->model_business->create();
			
			// Add the current user as the manager of the business
			$this->model_business_users->business_id = $this->model_business->business_id;
			$this->model_business_users->create();

			// Set package and generate initial credits
			$this->set_package($this->model_business->business_id, 1, true);

			// Set the generated business_id as the active business of the manager
			$this->load->library( 'Auth' );
			$this->auth->rest               = $this->rest;
			$this->auth->key                = $this->rest->key;
			$this->auth->active_business_id = $this->model_business->business_id;
			$this->auth->update_active_business();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $this->get_transaction_error() ?: 'System error'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Sending the verification email
					// Load the template parser class
					$this->load->library( 'Template' );

					// Load the language file with the content of the email
					$this->lang->load( 'email' );
					$email_data['title']='Business user signup';
					$email_data['message']='<span>Dear Admin,</span></br><span>New business user signup recently detail is given below</span></br>
						<span>Email: '.$business['email'].'</span></br>
						<span>Name: '.$business['firstname'].' '.$business['lastname'].'</span></br>
						<span>Phone: '.$business['phone'].'</span></br>
						<span>Business Name: '.$business['name'].'</span></br>
						<span>Business size: '.$business['size'].'</span></br>
						<span>Industry type: '.$business['industry'] ['name'].'</span></br>
						<span>City: '.$business['location']['city_name'].'</span></br>
						<span>State: '.$business['location']['state_name'].'</span></br>
						<span>Country: '.$business['location']['country_name'].'</span></br>
						<span>Website: '.$business['web_address'].'</span></br>';
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
				$this->response( [
					'status'  => true,
					'message' => $this->model_business_users->business_id
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * index_put
	 *
	 * Update the business profile
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function index_put( $business_id ) {
		$valid          = true;
		$form_validated = true;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		// Check access permission
		if ( $business_id != $this->get_manager_business() && ! $this->is_admin() ) {
			$valid = false;
		} else {
			$business = $this->request->body;

			$business = ( is_string( $business ) ) ? json_decode( $business, true ) : $business;

			if ( is_null( $business ) || empty( $business ) ) {
				$valid = false;
			} else {
				// Validating input parameters
				$form_validated = $this->validateRequestParameters( $business, array(
					'business_name',
					'years_established',
					'business_location',
					'company_size',
					'industry[id]',
					'duns_number',
					'company_type',
					'web_address',
					'logo'
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
			$this->load->model('model_location');
			$city_id = $this->model_location->get_city_id($business['location'], false);

			// Load the business model
			$this->load->model( 'model_business' );
			$this->model_business->business_id               = $business_id;
			$this->model_business->business_name             = $business['name'];
			$this->model_business->business_year_established = $business['year_established'];
			$this->model_business->business_size             = $business['size'];
			$this->model_business->city_id                   = $city_id;
			$this->model_business->industry_id               = $business['industry']['id'];
			$this->model_business->business_type_id          = $this->has_param( $business, 'type' );
			$this->model_business->business_web_address      = $business['web_address'];
			$this->model_business->business_duns_number      = $this->has_param( $business, 'duns' );
			$this->model_business->business_logo             = $this->has_param( $business, 'logo' );

			// Create the business entity
			if ( ! $this->model_business->update() ) {
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
	 * credits_get
	 *
	 * Get the credit balance of the business
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function credits_get( $business_id ) {
		$valid          = true;
		$credits_status = array(
			'left'              => 0,
			'spent'             => 0,
			'earned'            => 0,
			'auto_reload'       => false,
			'reload_package_id' => 0
		);

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		if ($this->is_admin()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		// Check access permission
		if ( $business_id != $this->get_manager_business() && ! $this->is_admin() ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Load the credits model
			$this->load->model( 'model_credits' );
			$this->model_credits->business_id = $business_id;

			// Load the user purchase model
			$this->load->model( 'model_business_user_purchase' );
			$this->model_business_user_purchase->business_id = $business_id;

			// Load the payments model
			$this->load->model( 'model_payments' );
			$this->model_payments->business_id = $business_id;

			// Get the number of credits left & the number of credits earned by the business
			if ( $this->model_credits->get() ) {
				$credits_status['left']   = $this->model_credits->credit_amount;
				$credits_status['earned'] = $this->model_credits->credits_from_cashback;
			}

			// Get the number of credits spent (Business purchases)
			$credits_status['spent'] = $this->model_business_user_purchase->get_total();

			// Get the credits auto reload settings of the business
			if ( $this->model_payments->get() ) {
				$credits_status['auto_reload'] = $this->model_payments->payment_auto_reload != 0;

				if ( is_numeric( $this->model_payments->payment_reload_package_id ) && $credits_status['auto_reload'] ) {
					$credits_status['reload_package_id'] = $this->model_payments->payment_reload_package_id;
				}
			}

			$this->response( $credits_status, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * credits_post
	 *
	 * Purchase credits for a business
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function credits_post( $business_id ) {
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
		$package_id   = $this->post( 'package_id' );
		$billing_name = $this->post( 'billing_name' );

		if ( is_null( $package_id ) ) {
			$valid = false;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $this->get_active_user() );

			if ($this->is_admin() || $this->is_recruiter()) {
				if (!$this->check_status($this->model_users, true)) {
					$valid = false;
				}
			} else {
				if (!$this->check_status($this->model_users)) {
					$valid = false;
				}
			}

			// Check permissions
			if ( $business_id != $this->get_manager_business() && ! $this->is_admin() ) {
				$valid = false;
			} else {
				// Get the manager's business details
				$this->load->model( 'model_location' );
				$this->load->model( 'model_business' );
				$this->model_business->business_id = $business_id;
				$business                          = $this->model_business->get();

				// Get the manager's details
				$this->load->model( 'model_users' );
				$this->model_users->user_id = $this->get_active_user();
				$this->model_users->get();

				// Load the requested package details
				$this->load->model( 'model_packages' );
				$this->model_packages->package_id = $package_id;
				$is_valid_package                 = $this->model_packages->get_package();

				// Retrieve the customer object
				$this->load->model( 'model_payments' );
				$this->model_payments->business_id = $business_id;
				$is_valid_customer                 = $this->model_payments->get();

				// Load the business credits status
				$this->load->model( 'model_credits' );
				$this->model_credits->business_id = $business_id;
				$has_credits                      = $this->model_credits->get();

				// Determine the validation rules required for this request
				$validation_rules = array( 'package_id' );
				if ( ! $is_valid_customer || is_null( $this->model_payments->payment_customer_id ) || ( $this->model_payments->payment_stripe_token != $stripe_token && ! is_null( $stripe_token ) ) ) {
					$validation_rules[] = 'stripe_token';
				}

				// Validate the input parameters
				$form_validated = $this->validateRequestParameters( $this->post(), $validation_rules );

				if ( ! $is_valid_package ) {
					$valid = false;
				}
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
			// Load the Stripe wrapper library
			$this->load->library( 'Stripe' );

			// Create customer_id if not exist in database
			if ( ! $is_valid_customer ) {
				$customer_stripe_creation = $this->stripe->create_customer( array(
					"description" => $business->name,
					"email"       => $this->model_users->email,
					"source"      => $stripe_token
				) );

				if ( $customer_stripe_creation ) {
					$this->model_payments->payment_customer_id  = $this->stripe->customer->id;
					$this->model_payments->payment_stripe_token = $stripe_token;
					$customer_db_creation                       = $this->model_payments->create();
				}
			} else if ( ( $this->model_payments->payment_stripe_token != $stripe_token && ! is_null( $stripe_token ) ) ) {
				// Load the customer object
				$this->stripe->get_customer( $this->model_payments->payment_customer_id );
				// Update the customer's credit card token
				$customer_stripe_creation = $this->stripe->update_card( $stripe_token );
				// Update the customer's billing name if sent
				if ( ! is_null( $billing_name ) ) {
					$customer_stripe_update = $this->stripe->update_customer( $this->model_payments->payment_customer_id, [
						'description' => $billing_name
					] );
				}

				if ( $customer_stripe_creation ) {
					$this->model_payments->payment_customer_id  = $this->stripe->customer->id;
					$this->model_payments->payment_stripe_token = $stripe_token;
					$customer_db_creation                       = $this->model_payments->update();
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
				// Start a manual database transaction
				$this->db->trans_begin();

				// Save the purchased package in database
				$this->load->model( 'model_packages_of_business' );
				$this->model_packages_of_business->business_id      = $business_id;
				$this->model_packages_of_business->package_id       = $package_id;
				$this->model_packages_of_business->cashback_percent = $this->model_packages->cashback_percent;
				$this->model_packages_of_business->create();

				// Add credits to account
				if ( ! $has_credits ) {
					// Create the record if it's the first payment
					$this->model_credits->credit_amount         = $this->model_packages->package_credits + $this->model_packages->package_initial_credits;
					$this->model_credits->credits_from_cashback = 0;
					$this->model_credits->create();
				} else {
					$this->model_credits->add_credits( $this->model_packages->package_credits );
				}

				if ( $this->db->trans_status() === false ) {
					// Failed to update the credits status / package to business association
					$this->db->trans_rollback();
				} else {

					$this->stripe->set_customer( $this->model_payments->payment_customer_id );

					// Create the invoice and pay it
					$payment_completed = $this->stripe->create_and_pay( array(
						"customer"    => $this->model_payments->payment_customer_id,
						"amount"      => $this->model_packages->package_price * 100,
						"currency"    => "usd",
						"description" => $this->model_packages->package_name,
					) );

					if ( ! $payment_completed ) {
						// Cancel the transaction, failed to charge the customer
						$this->db->trans_rollback();

						$this->response( [
							'status'  => false,
							'message' => $this->stripe->get_last_error()
						], Base_Controller::HTTP_BAD_REQUEST );
					} else {
						$this->model_packages_of_business->invoice_id     = $this->stripe->invoice->id;
						$this->model_packages_of_business->receipt_number = $this->stripe->invoice->receipt_number;
						$this->model_packages_of_business->update();

						// Add the purchased package to the purchase_history of the business
						$this->load->model( 'model_purchase_history' );
						$this->model_purchase_history->user_id            = $this->get_active_user();
						$this->model_purchase_history->business_id        = $business_id;
						$this->model_purchase_history->package_id         = $package_id;
						$this->model_purchase_history->purchase_price     = $this->model_packages->package_price;
						$this->model_purchase_history->package_credits    = $this->model_packages->package_credits;
						$this->model_purchase_history->transaction_number = $this->stripe->invoice->receipt_number;
						$this->model_purchase_history->invoice_id         = $this->stripe->invoice->id;
						$this->model_purchase_history->create();

						if ( ! $is_valid_customer ) {
							// This is the first payment, update the business status from setup to active
							$this->model_business->status = 'active';
							$this->model_business->update_status();
						}

						$this->db->trans_commit();

						$this->response( [
							'status' => true
						], Base_Controller::HTTP_OK );
					}
				}
			}
		}
	}


	/**
	 * credits_put
	 *
	 * Update the credits processing settings
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function credits_put( $business_id ) {
		$valid          = true;
		$form_validated = (object) array( 'result' => true, 'errors' => array() );

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		// Check access permission
		if ( $business_id != $this->get_manager_business() && ! $this->is_admin() ) {
			$valid = false;
		} else {
			$settings = $this->request->body;

			$settings = ( is_string( $settings ) ) ? json_decode( $settings, true ) : $settings;

			if ( is_null( $settings ) || empty( $settings ) ) {
				$valid = false;
			} else {
				// Setting the validation rules required to process this request
				if ( array_key_exists( 'auto_reload', $settings ) ) {
					if ( $settings['auto_reload'] === true ) {
						$form_validated = $this->validateRequestParameters( $settings, array( 'package_id' ) );
					} else if ( $settings['auto_reload'] !== true && $settings['auto_reload'] !== false ) {
						$form_validated = $this->validateRequestParameters( $settings, array( 'auto_reload' ) );
					}
				}
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
			$this->load->model( 'model_payments' );
			$this->model_payments->business_id = $business_id;

			if ( ! $this->model_payments->get() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Could not find a payment method'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				if ( $settings['auto_reload'] == true ) {
					$this->model_payments->payment_auto_reload       = 1;
					$this->model_payments->payment_reload_package_id = $settings['package_id'];
				} else {
					$this->model_payments->payment_auto_reload       = 0;
					$this->model_payments->payment_reload_package_id = null;
				}

				if ( ! $this->model_payments->update() ) {
					$this->response( [
						'status'  => false,
						'message' => 'Could not update'
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
	 * payments_get
	 *
	 * Get the credit card details of the business
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function payments_get( $business_id ) {
		$valid          = true;
		$payment_exists = true;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		// Check access permission
		if ( $business_id != $this->get_manager_business() && ! $this->is_admin() ) {
			$valid = false;
		} else {
			$this->load->model( 'model_payments' );
			$this->model_payments->business_id = $business_id;
			if ( ! $this->model_payments->get() ) {
				// Customer was not found in our database
				$payment_exists = false;
			} else {
				$this->load->library( 'Stripe' );
				$customer = $this->stripe->get_customer( $this->model_payments->payment_customer_id );

				if ( ! $customer ) {
					// Customer was not found in stripe
					$payment_exists = false;
				}
			}
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $payment_exists ) {
			$this->response( [
				'status'  => false,
				'message' => 'Invalid customer'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->response( [
				'id'              => $this->model_payments->payment_id,
				'last4'           => $this->stripe->customer->default_source->last4,
				'exp_month'       => $this->stripe->customer->default_source->exp_month,
				'exp_year'        => $this->stripe->customer->default_source->exp_year,
				'name'            => $this->stripe->customer->default_source->name,
				'address_line1'   => $this->stripe->customer->default_source->address_line1,
				'address_line2'   => $this->stripe->customer->default_source->address_line2,
				'address_city'    => $this->stripe->customer->default_source->address_city,
				'address_country' => $this->stripe->customer->default_source->address_country,
				'address_state'   => $this->stripe->customer->default_source->address_state,
				'address_zip'     => $this->stripe->customer->default_source->address_zip,
				'billing_name'    => $this->stripe->customer->description
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * payments_post
	 *
	 * Reserved endpoint for Stripe webhook (going commando)
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @return    void
	 */
	# TODO: Needs to be tested in live mode
	public function payments_post() {
		$this->load->library( 'Stripe' );

		$body       = @file_get_contents( 'php://input' );
		$event_json = json_decode( $body );
		$event_id   = $event_json->id;

		$event = \Stripe\Event::retrieve( $event_id );
		if ( $event->type == 'invoice.payment_succeeded' ) {
			$object = $event->data->object;

			if ( $object->object == 'invoice' ) {
				$receipt_number = $object->receipt_number;

				if ( ! is_null( $receipt_number ) ) {
					$invoice_id = $object->id;
					$this->load->model( 'model_packages_of_business' );
					$this->model_packages_of_business->invoice_id = $invoice_id;
					if ( $this->model_packages_of_business->get_by_invoice_id() ) {
						$this->model_packages_of_business->receipt_number = $receipt_number;
						$this->model_packages_of_business->update();

						$this->response( "", Base_Controller::HTTP_OK );
					}
				}
			}
		}
	}

	/**
	 * payments_put
	 *
	 * Update the credit card details of the business (assign to existing customer)
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @param integer $payment_id
	 *
	 * @return    void
	 */
	public function payments_put( $business_id, $payment_id ) {
		$valid                  = true;
		$form_validated         = true;
		$payment_exists         = true;
		$customer_stripe_update = true;

		$token = $this->request->body;
		$token = ( is_string( $token ) ) ? json_decode( $token, true ) : $token;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		// Check access permission
		if ( $business_id != $this->get_manager_business() && ! $this->is_admin() ) {
			$valid = false;
		} else if ( is_null( $token ) ) {
			$valid = false;
		} else {
			// Validate the input parameters
			$form_validated = $this->validateRequestParameters( $token, array(
				'stripe_token',
				'billing_name'
			) );

			if ( $form_validated->result ) {
				$this->load->model( 'model_payments' );
				$this->model_payments->business_id = $business_id;
				$this->model_payments->payment_id  = $payment_id;
				if ( ! $this->model_payments->get_payment() ) {
					// Customer was not found in our database
					$payment_exists = false;
				} else {
					$this->load->library( 'Stripe' );
					$customer = $this->stripe->get_customer( $this->model_payments->payment_customer_id );

					if ( ! $customer ) {
						// Customer was not found in stripe
						$payment_exists = false;
					}
				}
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
		} else if ( ! $payment_exists ) {
			$this->response( [
				'status'  => false,
				'message' => 'Invalid customer'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Update the card token for the current customer
			$update = $this->stripe->update_card( $token['stripe_token'] );

			if ( ! is_null( $token['billing_name'] ) ) {
				$customer_stripe_update = $this->stripe->update_customer( $this->model_payments->payment_customer_id, [
					'description' => $token['billing_name']
				] );
			}

			if ( ! $update || ! $customer_stripe_update ) {
				$this->response( [
					'status'  => false,
					'message' => $this->stripe->get_last_error()
				] );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * purchases_get
	 *
	 * Get the history of the purchases
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function purchases_get( $business_id ) {
		$valid          = true;
		$form_validated = true;

		$months = $this->get( 'months' );

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		// Check access permission
		if ( $business_id != $this->get_manager_business() && ! $this->is_admin() ) {
			$valid = false;
		} else if ( is_null( $months ) ) {
			$valid = false;
		} else {
			$form_validated = $this->validateRequestParameters( $this->get(), array(
				'valid_months'
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
			$this->load->model( 'model_packages' );
			$this->load->model( 'model_purchase' );
			$this->load->model( 'model_packages_of_business' );
			$this->model_packages_of_business->business_id = $business_id;
			$purchases                                     = $this->model_packages_of_business->get_since( $months );

			$this->response( $purchases, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * purchases_post
	 *
	 * Purchase applciants
	 *
	 * @access    public
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function purchases_post( $business_id ) {
		$valid          = true;
		$permission     = true;
		$form_validated = true;
		$balance        = true;
		$payment        = true;
		$skipped        = [];
		
		$fullname = $this->post( 'fullname' );
		$company   = $this->post( 'company' );
		$message = $this->post( 'message' );
		$applicants = $this->post( 'applicants' );
		$recruitingfor = $this->post( 'recruitingfor' );
		$exclusive_contact = $this->post('exclusive_contact') ? 'Yes' : ' ';
		$applicants = ( is_string( $applicants ) ) ? json_decode( $applicants, true ) : $applicants;
		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );
		// Checking that the user has activated his account
		if ($this->is_admin()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		if ( $this->is_recruiter() ) {
			$logged_business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$logged_business_id = $this->get_manager_business();
		}

		if ( $logged_business_id != $business_id ) {
			$valid = false;
		}

		if ( ! count( $applicants ) ) {
			$valid = false;
		}
		if ( $valid ) {
			$this->load->model( 'model_business_users' );
			$this->model_business_users->user_id = $this->get_active_user();
			$this->model_business_users->business_id = $business_id;
			if ( ! $user = $this->model_business_users->get_any_business_user() ) {
				$valid = false;
			} else {
				if ( $this->model_business_users->purchase_permission != '1' && $this->is_recruiter() ) {
					$permission = false;
				}
			}
		}

		if ( $valid && $permission ) {
			// Look for previously purchased applicants from the given collection
			$this->load->model( 'model_business_user_purchase' );
			$this->model_business_user_purchase->business_id = $business_id;
			$purchased                                       = $this->model_business_user_purchase->get_purchased( $applicants );

			// Added the found applicants to the skipped array
			$skipped = array_merge( $skipped, $purchased );

			// Reduce the applicant ids from the original applicants
			//$applicants = array_diff( $applicants, $purchased );

			if ( ! count( $applicants ) ) {
				//$valid = false;
					// comments above $vaild to remove allready purchased functionality
					$valid = true;
			} else {
				// Look for previously applied applicants from the given collection
				$this->load->model( 'model_applicants_of_business' );
				$this->model_applicants_of_business->business_id = $business_id;
				$applied                                         = $this->model_applicants_of_business->get_applied( $applicants );

				// Added the found applicants to the skipped array
				$skipped = array_merge( $skipped, $applied );

				// Reduce the applicant ids from the original applicants
				// comments below line to remove allready purchased functionality
				//$applicants = array_diff( $applicants, $applied );

				if ( ! count( $applicants ) ) {
					$valid = false;
				} else {
					// Get the credit balance of the business
					$this->load->model( 'model_credits' );
					$this->model_credits->business_id = $business_id;
					$this->model_credits->get();

					// Get the payment settings
					$this->load->model( 'model_payments' );
					$this->model_payments->business_id = $business_id;
					$this->model_payments->get();

					// Check the credits balance
					if ( (int) $this->model_credits->credit_amount < count( $applicants ) ) {
						if ( $this->model_payments->payment_auto_reload == 1 ) {
							// Get the requested package details
							$this->load->model( 'model_packages' );
							$this->model_packages->package_id = $this->model_payments->payment_reload_package_id;
							$this->model_packages->get_package();

							$this->load->library( 'Stripe' );

							$this->stripe->set_customer( $this->model_payments->payment_customer_id );

							// Start a manual database transaction
							$this->db->trans_begin();

							// Add the purchased package to the purchase_history of the business
							$this->load->model( 'model_purchase_history' );
							$this->model_purchase_history->user_id            = $this->get_active_user();
							$this->model_purchase_history->business_id        = $business_id;
							$this->model_purchase_history->package_id         = $this->model_payments->payment_reload_package_id;
							$this->model_purchase_history->purchase_price     = $this->model_packages->package_price;
							$this->model_purchase_history->package_credits    = $this->model_packages->package_credits;
							$this->model_purchase_history->transaction_number = $this->stripe->invoice->receipt_number;
							$this->model_purchase_history->invoice_id         = $this->stripe->invoice->id;
							$this->model_purchase_history->create();

							// Save the purchased package in database
							$this->load->model( 'model_packages_of_business' );
							$this->model_packages_of_business->business_id      = $business_id;
							$this->model_packages_of_business->package_id       = $this->model_payments->payment_reload_package_id;
							$this->model_packages_of_business->cashback_percent = $this->model_packages->cashback_percent;
							$this->model_packages_of_business->invoice_id       = $this->stripe->invoice->id;
							$this->model_packages_of_business->receipt_number   = $this->stripe->invoice->receipt_number;
							$this->model_packages_of_business->create();

							// Update the credit balance
							$this->model_credits->add_credits( $this->model_packages->package_credits );

							// Create the invoice and pay it
							$payment_completed = $this->stripe->create_and_pay( array(
								"customer"    => $this->model_payments->payment_customer_id,
								"amount"      => $this->model_packages->package_price * 100,
								"currency"    => "usd",
								"description" => $this->model_packages->package_name,
							) );

							if ( ! $payment_completed ) {
								// Cancel the transaction, failed to charge the customer
								$this->db->trans_rollback();
								$payment = false;
							} else {
								$this->db->trans_commit();
								$payment = true;
							}
						} else {
							$balance = false;
						}
					} else {
						$balance = true;
						$payment = true;
					}
				}
			}
		}

		if ( ! $valid ) {
			if ( count( $skipped ) > 0 ) {
				$this->response( [
					'status'  => false,
					'message' => 'All the requested applicants already been purchased or applied'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			}
		} else if ( ! $permission ) {
			$this->response( [
				'status'  => false,
				'message' => 'Insufficient permissions to complete the requested action'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $balance ) {
			$this->response( [
				'status'  => false,
				'message' => 'Insufficient number of credits left in your account'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else if ( ! $payment ) {
			$this->response( [
				'status'  => false,
				'message' => 'Error in processing the payment'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Start a database transaction
			$this->db->trans_start();

			// Associate the applicants to the business account
			$this->load->model( 'model_business_user_purchase' );
			$this->model_business_user_purchase->business_id = $business_id;
			$this->model_business_user_purchase->associate( $applicants );

			// Update the credit balance of the business
			$this->load->model( 'model_credits' );
			$this->model_credits->business_id = $business_id;
			$this->model_credits->add_credits( count( $applicants ) * - 1 );

			$this->db->trans_complete();

			if (false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $this->get_transaction_error() ?: 'System error'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$mesg_data='';
				if($exclusive_contact!=' '){
					$mesg_data .='<p><h4>*Recruiter has exclusive contract</h4></p>';
				}else{
					$mesg_data .='<p>*This recruiter does not have exclusive to fill the position</p>';
				}
				if(!empty($message)){
					$mesg_data .='<p>Please answer the following questions.</p><p>'.$message.'<p>';
				}
				if(!empty($recruitingfor)){
					$recruitingfor='<p>Recruiting for the company : '.$recruitingfor.'.</p>';
				}else{
					$recruitingfor='';
				}
				// send email to admin for connected applicant
				foreach ($applicants as $applicant) {
					$this->load->model( 'model_users' );
					$this->load->model( 'Model_business' );
					$this->model_users->user_id=$applicant;
					$this->model_users->get();
					$busibess_data=$this->Model_business->get_business_user_detail_by_business($business_id);
					if($this->model_users->created_by){
						// send email to admin for uploded candidate
						$creator_data=$this->Model_business->get_business_user_detail_by_user($this->model_users->creator_id);
						$this->mail_on_purches_company_user($applicant,$busibess_data,$creator_data,$mesg_data,$recruitingfor);
					}else{
						// send email to admin for registered candidate
						$this->mail_on_purches_user($applicant,$busibess_data,$mesg_data,$recruitingfor);
					}		
				}
				// end send email to admin for connected applicant
				$this->response( [
					'status'  => true,
					'skipped' => 0
				], Base_Controller::HTTP_OK );
			}
		}
	}
	/**
	 * updateapplicantstatus_post
	 *
	 * chnage applicant status for uploaded candidate
	 *
	 * @access    public
	 *
	 * @role    recruiter, manager
	 *
	 * @params integer $business_id
	 *
	 * @return    void
	 */
	public function updateapplicantstatus_post($business_id){
		$valid          = true;
		$permission     = true;
		$form_validated = true;
	
		$status = $this->post( 'status' );
		$applicants = $this->post( 'applicants' );
		$applicants = ( is_string( $applicants ) ) ? json_decode( $applicants, true ) : $applicants;
		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );
		// Checking that the user has activated his account

		if ( $this->is_recruiter() ) {
			$logged_business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$logged_business_id = $this->get_manager_business();
		}

		if ( ! count( $applicants ) ) {
			$valid = false;
		}

		if ( ! $valid ) {
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
		}else if ( ! $permission ) {
			$this->response( [
				'status'  => false,
				'message' => 'Insufficient permissions to complete the requested action'
			], Base_Controller::HTTP_BAD_REQUEST );
		}else{
				foreach ($applicants as $applicant) {
					$this->load->model( 'model_users' );
					$this->model_users->user_id=$applicant;
					$this->model_users->get();
					if($this->model_users->creator_id==$business_id){
						if($status=='addtomarket'){
							$this->model_users->market_place=1;
						}elseif($status=='removefrommarket'){
							$this->model_users->market_place=0;
						}elseif($status=='deleteandremove'){
							$this->model_users->status='deleted';
						}else{

						}
						$this->model_users->update();
						
					}	
				}
			$this->response( [
					'status'  => true,
				], Base_Controller::HTTP_OK );	
		}

	}

	/**
	 * applicants_post
	 *
	 * Search for applicants which owned by the business
	 *
	 * @access    public
	 *
	 * @role    recruiter, manager
	 *
	 * @params integer $business_id
	 *
	 * @return    void
	 */
	public function applicants_post( $business_id ) {
		$valid          = true;
		$form_validated = true;

		$offset  = $this->query( 'offset' );
		$orderby = $this->query( 'orderby' );
		$order   = $this->query( 'order' );
		$search  = $this->request->body;

		$search = ( is_string( $search ) ) ? json_decode( $search, true ) : $search;
		$search = is_array( $search ) && count( $search ) == 1 ? $search[0] : $search;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Checking that the user has activated his account
		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		if ( $this->is_recruiter() ) {
			$logged_business_id = $this->get_recruiter_business();
		} else if ( $this->is_manager() ) {
			$logged_business_id = $this->get_manager_business();
		}

		if ( $logged_business_id != $business_id ) {
			$valid = false;
		}

		// Validating input parameters
		$validation_params = array(
			'offset'  => $offset,
			'search'  => json_encode( $search ),
			'orderby' => $orderby,
			'order'   => $order
		);
		$form_validated    = $this->validateRequestParameters( $validation_params, array(
			'offset',
			'search',
			'orderby',
			'order'
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

			// Load the models
			$this->load->model( 'model_searches' );
			$this->load->model( 'model_search' );

			// Determine if order sorting filters exists
			$orderby = $this->model_search->determine_orderby( $orderby );

			// Set search flags
			$this->model_search->set_flags( 'BusinessApplicantsOnly', true );

			// Register the search
			$this->model_searches->search_json = json_encode( $search );
			$this->model_searches->insert_update_search();
			$search_token = $this->model_searches->get_search_token();

			// Perform the search
			$this->model_search->search      = $search;
			$this->model_search->user_id     = $this->get_active_user();
			$this->model_search->business_id = $business_id;
			$applicants                      = $this->model_search->get_filtered_applicants( $offset ?: 0, $orderby ?: 'id', $order ?: 'ASC' );

			// Order the search results
			$this->load->library( 'ArrayHelper' );
			$parsed_applicants = $this->arrayhelper->arrange( $applicants );

			// Add the search token
			$parsed_applicants['token'] = $search_token;

			// Send a success response to the client
			$this->response( $parsed_applicants, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * application_post
	 *
	 * Apply to a business
	 *
	 * @access    public
	 *
	 * @role    applicant
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function application_post( $business_id ) {
		$valid = true;

		if ( is_null( $this->get_active_user() ) ) {
			$valid = false;
		} else {
			// Get the current user from the cache / from the database
			$this->get_user( $this->get_active_user() );
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->db->db_debug = false;
			$this->load->model( 'model_applicants_of_business' );
			$this->model_applicants_of_business->user_id     = $this->get_active_user();
			$this->model_applicants_of_business->business_id = $business_id;
			$this->model_applicants_of_business->verified    = $this->check_status($this->model_users);
			$this->model_applicants_of_business->create();

			$this->response( [
				'application_id' => base64_encode( 'USER_APPLICATION|' . $this->model_applicants_of_business->applicants_of_business_id )
			], Base_Controller::HTTP_OK );
		}
	}

	/**
	 * searches_get
	 *
	 * Get a business's searches
	 *
	 * @access    public
	 *
	 * @param integer $business_id
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function searches_get( $business_id ) {
		$valid               = true;
		$form_validated      = true;
		$manager_business_id = false;

		$from = $this->get( 'from' );
		$to   = $this->get( 'to' );

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Get the manager's business id
		if ( $this->is_manager() ) {
			$manager_business_id = $this->get_manager_business();
		} else {
			$valid = false;
		}

		// Checking that the user has activated his account
		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		if ( $business_id != $manager_business_id ) {
			$valid = false;
		}

		// Validate the input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'searches_from',
			'searches_to'
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
			$this->load->model( 'model_saved_business_search' );
			$this->load->model( 'model_searches_of_businesses' );
			$this->model_searches_of_businesses->business_id = $business_id;

			$searches = $this->model_searches_of_businesses->get_business_searches( $from, $to );

			$this->response( $searches, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * searches_put
	 *
	 * Update the status of a saved search
	 *
	 * @access    public
	 *
	 * @param integer $business_id
	 *
	 * @param integer $search_id
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function searches_put( $business_id, $search_id ) {
		$valid               = true;
		$form_validated      = true;
		$manager_business_id = false;

		$status = $this->request->body;
		$status = ( is_string( $status ) ) ? json_decode( $status, true ) : $status;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Get the manager's business id
		if ( $this->is_manager() ) {
			$manager_business_id = $this->get_manager_business();
		} else {
			$valid = false;
		}

		// Checking that the user has activated his account
		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		if ( $business_id != $manager_business_id ) {
			$valid = false;
		}

		// Validate the input parameters
		$form_validated = $this->validateRequestParameters( $status, array(
			'search_status'
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
			$this->load->model( 'model_searches_of_businesses' );
			$this->model_searches_of_businesses->search_of_business_id = $search_id;
			$this->model_searches_of_businesses->business_id           = $business_id;
			$this->model_searches_of_businesses->status                = $status['status'];
			$updated                                                   = $this->model_searches_of_businesses->set_status();

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
	 * recruiters_get
	 *
	 * Get a business's recruiters
	 *
	 * @access    public
	 *
	 * @param integer $business_id
	 *
	 * @role    manager, admin
	 *
	 * @return    void
	 */
	public function recruiters_get( $business_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Get the manager's business id
		if ( $this->is_manager() ) {
			$manager_business_id = $this->get_manager_business();
		} else {
			$valid = false;
		}

		// Checking that the user has activated his account
		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		if ( $business_id != $manager_business_id ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_business_users' );
			$this->model_business_users->business_id = $business_id;
			$recruiters                              = $this->model_business_users->get_recruiters();

			$this->response( $recruiters, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * recruiters_put
	 *
	 * Update the purchase permission of a recruiter
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @param integer $user_id
	 *
	 * @return    void
	 */
	public function recruiters_put( $business_id, $user_id ) {
		$valid          = true;
		$form_validated = true;

		$purchase_permission = $this->request->body;
		$purchase_permission = ( is_string( $purchase_permission ) ) ? json_decode( $purchase_permission, true ) : $purchase_permission;
		$purchase_permission = is_array( $purchase_permission ) && count( $purchase_permission ) === 1 ? $purchase_permission[0] : $purchase_permission;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Get the manager's business id
		if ( $this->is_manager() ) {
			$manager_business_id = $this->get_manager_business();
		} else {
			$valid = false;
		}

		// Checking that the user has activated his account
		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		// Check access permission
		if ( $business_id != $manager_business_id ) {
			$valid = false;
		} else if ( is_null( $purchase_permission ) ) {
			$valid = false;
		} else {
			// Validate the input parameters
			$form_validated = $this->validateRequestParameters( array( 'purchase_permission' => $purchase_permission ), array(
				'purchase_permission'
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
			$this->load->model( 'model_business_users' );
			$this->model_business_users->business_id         = $business_id;
			$this->model_business_users->user_id             = $user_id;
			$this->model_business_users->business_admin      = 0;
			$this->model_business_users->purchase_permission = $purchase_permission;

			$updated = $this->model_business_users->update_purchase_permission();

			if ( ! $updated ) {
				$this->response( [
					'status'  => false,
					'message' => 'Bad request'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				$this->response( "", Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * recruiters_post
	 *
	 * Add a recruiter to the business
	 *
	 * @access    public
	 *
	 * @role    applicant
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function recruiters_post( $business_id ) {
		$valid          = true;
		$form_validated = true;

		$recruiter = $this->request->body;
		$recruiter = ( is_string( $recruiter ) ) ? json_decode( $recruiter ) : (object) $recruiter;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Get the manager's business id
		if ( $this->is_manager() ) {
			$manager_business_id = $this->get_manager_business();
		} else {
			$valid = false;
		}

		// Checking that the user has activated his account
		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		// Check access permission
		if ( $business_id != $manager_business_id ) {
			$valid = false;
		} else if ( is_null( $recruiter ) ) {
			$valid = false;
		} else {
			// Validate the input parameters
			$form_validated = $this->validateRequestParameters( (array) $recruiter, array(
				'email',
				'purchase_permission',
				'optional_job_title[id]'
			) );

			if ( $form_validated->result ) {
				// Loading the users model
				$this->load->model( 'model_users' );
				$this->model_users->email = $recruiter->email;

				// Checking that the user exists in the platform
				$user_exists = $this->model_users->get_by_email();

				// If the user is trying to invite himself
				if ( $user_exists && $this->model_users->user_id == $this->get_active_user() ) {
					$valid = false;
				} else if ( $user_exists && $this->model_users->user_id != $this->get_active_user() ) {
					// Checking that the requested user not already a recruiter of the business
					$this->load->model( 'model_business_users' );
					$this->model_business_users->business_id    = $business_id;
					$this->model_business_users->user_id        = $this->model_users->user_id;
					$this->model_business_users->business_admin = 0;

					$is_business_recruiter = $this->model_business_users->get_business_user();
					if ( $is_business_recruiter ) {
						$valid = false;
					}
				}
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
			// Cast the job title array to model
			$recruiter->jobtitle = (object) $recruiter->jobtitle;

			// Start a database transaction
			$this->db->trans_start();

			// Turn off the database debugging
			$this->db->db_debug = false;

			if ( $user_exists ) {
				// Handler for an existing platform user

				// Creating the business-user record
				$this->model_business_users->purchase_permission = ( $recruiter->purchase_permission == true ) ? 1 : 0;
				if ( isset( $recruiter->jobtitle ) ) {
					$this->model_business_users->business_user_job_title = $recruiter->jobtitle->id;
				}

				$this->model_business_users->create();

				// Upgrade the role of the user
				$this->model_users->role = 'recruiter';
				$this->model_users->update();

				// Set the active_business_id of the recruiter is not been assigned yet
				$this->load->library( 'Auth' );
				$this->auth->user_id = $this->model_users->user_id;
				$auth                = $this->auth->get();

				if ( is_null( $auth->active_business_id ) ) {
					$this->auth->key                = $auth->key;
					$this->auth->active_business_id = $business_id;
					$this->auth->update_active_business();
				}

				// Sending a notification email
				// Load the template parser class
				$this->load->library( 'Template' );

				// Load the language file with the content of the email
				$this->lang->load( 'email' );
				$email_data               = $this->lang->line( 'add_recruiter' );
				$email_data['button_url'] = base_url() . 'business/search';

				$html = $this->template->view( 'add_recruiter', $email_data, true );

				// Load the Mailgun library wrapper
				$this->load->library( 'Mailgun' );

				// Extract the textual version from the html body version
				$text = $this->mailgun->get_text_version( $html );

				// Replace un wanted text with the link to the profile
				$text = $this->mailgun->str_replace_last( 'Start recruiting', $email_data['button_url'], $text );

				// Set the sending parameter
				$this->mailgun->subject = $email_data['subject'];
				$this->mailgun->html    = $html;
				$this->mailgun->body    = $text;
				$this->mailgun->to      = $recruiter->email;
			} else {
				// Handler for a new platform user

				// Create an invitation for the recruiter
				$this->load->model( 'model_invitations_of_business' );
				$this->model_invitations_of_business->business_id         = $business_id;
				$this->model_invitations_of_business->email               = $recruiter->email;
				$this->model_invitations_of_business->purchase_permission = ( $recruiter->purchase_permission == true ) ? 1 : 0;
				if ( isset( $recruiter->jobtitle ) ) {
					$this->model_invitations_of_business->job_title_id = $recruiter->jobtitle->id;
				}

				// Check if the user has a previous invitation
				$is_exists = $this->model_invitations_of_business->is_exists();

				if ( ! $is_exists ) {
					// Create the invitation if could not find a previous one
					$this->model_invitations_of_business->create();
				}

				// Sending a notification email
				// Load the template parser class
				
				$this->load->library( 'Template' );

				// Load the language file with the content of the email
				$this->lang->load( 'email' );
				$email_data               = $this->lang->line( 'invite_recruiter' );
				
				$email_data['button_url'] = base_url() . 'signup?invite=' . $this->model_invitations_of_business->token;
				
				$html                     = $this->template->view( 'invite_recruiter', $email_data, true );

				// Load the Mailgun library wrapper
				$this->load->library( 'Mailgun' );

				// Extract the textual version from the html body version
				$text = $this->mailgun->get_text_version( $html );

				// Replace un wanted text with the link to the profile
				$text = $this->mailgun->str_replace_last( 'Start recruiting', $email_data['button_url'], $text );

				// Set the sending parameter
				$this->mailgun->subject = $email_data['subject'];
				$this->mailgun->html    = $html;
				$this->mailgun->body    = $text;
				$this->mailgun->to      = $recruiter->email;
			}

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $this->get_transaction_error() ?: 'System error'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Send the notification / invite email only on successful database transaction
				$sent = $this->mailgun->send();

				$this->response( [
					'id'                  => $this->model_users->user_id,
					'email'               => $recruiter->email,
					'status'              => $this->model_users->status,
					'jobtitle'            => isset( $recruiter->jobtitle ) ? $recruiter->jobtitle->name : null,
					'purchase_permission' => ( $recruiter->purchase_permission == true ) ? 1 : 0
				], Base_Controller::HTTP_OK );
			}
		}
	}

	/**
	 * recruiters_delete
	 *
	 * Remove a a recruiter from the business's recruiter
	 *
	 * @access    public
	 *
	 * @param integer $business_id
	 *
	 * @param integer $user_id
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function recruiters_delete( $business_id, $user_id ) {
		$valid               = true;
		$manager_business_id = false;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Checking that the user has activated his account
		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		// Get the manager's business id
		if ( $this->is_manager() ) {
			$manager_business_id = $this->get_manager_business();
		} else {
			$valid = false;
		}

		// Check access permission
		if ( $business_id !== $manager_business_id ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			// Soft delete the recruiter
			$this->load->model( 'model_business_users' );
			/*$this->model_business_users->business_id = $business_id;*/
			$this->model_business_users->user_id     = $user_id;
			$deleted                                 = $this->model_business_users->soft_delete();

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
	 * Get the general statistics of the business
	 *
	 * @access    public
	 *
	 * @param integer $business_id
	 *
	 * @role    recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function statistics_get( $business_id ) {
		$valid              = true;
		$active_business_id = false;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Get the manager's business id
		if ( $this->is_manager() ) {
			$active_business_id = $this->get_manager_business();
		} else if ( $this->is_recruiter() ) {
			$active_business_id = $this->get_recruiter_business();
		} else {
			$valid = false;
		}

		// Checking that the user has activated his account
		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		// Each one can request stats only for the business he is operating on behalf
		if ( $business_id != $active_business_id ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$business_stats = (object) array(
				'applied'      => 0,
				'purchased'    => 0,
				'short_listed' => 0,
				'hired'        => 0
			);

			// Get the number of applicants which applied to the current business
			$this->load->model( 'model_applicants_of_business' );
			$this->model_applicants_of_business->business_id = $business_id;
			$this->model_applicants_of_business->verified    = 1;
			$business_stats->applied                         = (int) $this->model_applicants_of_business->get_applicants_count();

			// Get the number of applicants which been purchased by the current business
			$this->load->model( 'model_business_user_purchase' );
			$this->model_business_user_purchase->business_id = $business_id;
			$business_stats->purchased                       = (int) $this->model_business_user_purchase->get_total();

			// Get the number of statuses of the businesses applicants
			$this->load->model( 'model_business_applicant_status' );
			$this->model_business_applicant_status->business_id = $business_id;
			$statuses                                           = $this->model_business_applicant_status->get_business_statuses();

			$business_stats->short_listed = (int) $statuses['short'];
			$business_stats->hired        = (int) $statuses['hired'];

			$this->response( $business_stats, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * results_get
	 *
	 * Get the performance stats of a saved search belong to a business
	 *
	 * @access    public
	 *
	 * @param integer $business_id
	 *
	 * @param integer $search_of_business_id
	 *
	 * @role    manager, admin
	 *
	 * @return    void
	 */
	public function results_get( $business_id, $search_of_business_id ) {
		$valid = true;

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Get the manager's business id
		if ( $this->is_manager() ) {
			$manager_business_id = $this->get_manager_business();
		} else {
			$valid = false;
		}

		// Checking that the user has activated his account
		if ($this->is_admin() || $this->is_recruiter()) {
			if (!$this->check_status($this->model_users, true)) {
				$valid = false;
			}
		} else {
			if (!$this->check_status($this->model_users)) {
				$valid = false;
			}
		}

		if ( $business_id != $manager_business_id ) {
			$valid = false;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_searches_of_businesses' );

			$this->model_searches_of_businesses->business_id           = $business_id;
			$this->model_searches_of_businesses->search_of_business_id = $search_of_business_id;
			if ( ! $search = $this->model_searches_of_businesses->get_search_by_id() ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				// Load the models
				$this->load->model( 'model_searches' );
				$this->load->model( 'model_search' );

				// Perform the search
				$this->model_search->search      = $search;
				$this->model_search->user_id     = $this->get_active_user();
				$this->model_search->business_id = $business_id;
				$applicants                      = $this->model_search->get_filtered_applicants( 0, 'id', 'ASC', 999999999 );

				// Collect the statistics data from the search results
				$this->load->library( 'ArrayHelper' );
				$search_statistics = $this->arrayhelper->iterate_stats( $applicants );

				$this->response( $search_statistics, Base_Controller::HTTP_OK );
			}
		}
	}

	private function mail_on_purches_user($applicant,$busibess_data,$message,$recruitingfor){
					
					$this->load->library( 'Template' );

					// Load the language file with the content of the email
					$this->lang->load( 'email' );
					$email_data['title']='Company Contact Request';
					$email_data['message']='
						<tr color: #000;font-weight: 300;>
							<table>
								<tbody>
									<tr>
									<td align="left">Account #</td><td align="left"> '.$busibess_data->user_id.'</td>
									</tr>
									<tr>
									<td align="left">From:</td><td align="left"> '.$busibess_data->first_name.' '.$busibess_data->last_name.'</td>
									</tr>
									<tr>
									<td align="left">Company:</td><td align="left"> '.$busibess_data->business_name.'</td>
									</tr>
									<tr>
										<td align="left">Email:</td>
										<td align="left"> '.$busibess_data->email.'</td>
									</tr>
									<tr>
										<td align="left">Phone: </td>
										<td align="left">'.$busibess_data->phone.'</td>
									</tr>
								</tbody>
							</table>
							<span style="display: block;width: 100%;padding: 10px 0px;text-align: left;">Is trying to connect with candidate account #<span style="text-decoration: underline;color: #2f5678;"><a target="_blank" href="'.$_SERVER['HTTP_ORIGIN'].'/user/'.$applicant.'">'.$applicant.'</a></span> '.$message.'</span>
								'.$recruitingfor.'
						</tr>';
						
					$email_data['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved. <br> ';
					$email_data['subject']='Company contact to applicant';
					$html = $this->template->view( 'admin_email_for_business_user', $email_data, true );
					// Load the Mailgun library wrapper
					$this->load->library( 'Mailgun' );

					// Extract the textual version from the html body version
					$text = $this->mailgun->get_text_version( $html );
					// Replace un wanted text with the link to the profile
					$text = $this->mailgun->str_replace_first( 'click confirm on the link below', 'copy and paste the link below in your web browser:', $text );
					$this->mailgun->html    = $html;
					$this->mailgun->to      = 'aryeh@findable.co';
					//'vivek_agarwal@seologistics.com';
					$this->mailgun->body    = $text;
					$this->mailgun->subject = $email_data['subject'];
					$sent = $this->mailgun->send();
	
	}
	private function mail_on_purches_company_user($applicant,$busibess_data,$creator_data,$message,$recruitingfor){
		// Sending the verification email
					// Load the template parser class
					$this->load->library( 'Template' );

					// Load the language file with the content of the email
					$this->lang->load( 'email' );
					$email_data['title']='Company Contact Request – On-boarded from recruiter';
					$email_data['message']='
						<tr color: #000;font-weight: 300;>
							<table>
								<tbody>
									<tr>
									<td align="left">Account #</td><td align="left"> '.$busibess_data->user_id.'</td>
									</tr>
									<tr>
									<td align="left">From:</td><td align="left"> '.$busibess_data->first_name.' '.$busibess_data->last_name.'</td>
									</tr>
									<tr>
									<td align="left">Company:</td><td align="left"> '.$busibess_data->business_name.'</td>
									</tr>
									<tr>
										<td align="left">Email:</td>
										<td align="left"> '.$busibess_data->email.'</td>
									</tr>
									<tr>
										<td align="left">Phone: </td>
										<td align="left">'.$busibess_data->phone.'</td>
									</tr>
								</tbody>
							</table>
							<span style="display: block;width: 100%;padding: 10px 0px;text-align: left;">Is trying to connect with candidate account #<span style="text-decoration: underline;color: #2f5678;"><a target="_blank" href="'.$_SERVER['HTTP_ORIGIN'].'/user/'.$applicant.'">'.$applicant.'</a></span> '.$message.'</span>
								'.$recruitingfor.'
							</span> uploaded from the company.</span>
							<table>
								<tbody>
									<tr><td align="left">From:</td><td align="left"> '.$creator_data->first_name.' '.$creator_data->last_name.'</td></tr>
									<tr><td align="left">Company:</td><td align="left">  '.$creator_data->business_name.'</td></tr>
									<tr><td align="left">Email:</td><td align="left"> '.$creator_data->email.'</td></tr>
									<tr><td align="left">Phone:</td><td align="left"> '.$creator_data->phone.'</td></tr>
								</tbody>
							</table>
						</tr>';
						
					$email_data['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved. <br> ';
					$email_data['subject']='Company contact to applicant';
					$html = $this->template->view( 'admin_email_for_business_user', $email_data, true );

					// Load the Mailgun library wrapper
					$this->load->library( 'Mailgun' );
					// Extract the textual version from the html body version
					$text = $this->mailgun->get_text_version( $html );
					// Replace un wanted text with the link to the profile
					$text = $this->mailgun->str_replace_first( 'click confirm on the link below', 'copy and paste the link below in your web browser:', $text );
					$this->mailgun->html    = $html;
					$this->mailgun->to      = 'aryeh@findable.co';
					//'vivek_agarwal@seologistics.com';
					$this->mailgun->body    = $text;
					$this->mailgun->subject = $email_data['subject'];
					$sent = $this->mailgun->send();
		
	}
	/**
	 * sendemail_post
	 *
	 * 
	 *
	 * @access    public
	 *
	 * @role    manager,recruiter
	 *
	 * @return    void
	 */
	# TODO: Needs to be tested in live mode
	public function sendemail_post($user_id) {
		// Get the API client and construct the service object
		$client = $this->getClient($user_id);
		if(isset($client->status) && $client->status==1){
			$client=(array) $client;
			$this->response($client, Base_Controller::HTTP_OK );
		}else if(isset($client->status) && $client->status==0){
			$this->response( [
				'status'  => 0,
				'message' => 'There are some error please try again later'
			], Base_Controller::HTTP_BAD_REQUEST );
		}else{
			$this->get_user($user_id);
			$service = new Google_Service_Gmail($client);
			// Print the labels in the user's account.
			$user = 'me';
			$strSubject = 'Findable';
			$strRawMessage = "From: myAddress<info@findable.com>\r\n";
			$strRawMessage .= "To: toAddress <".$this->model_users->email.">\r\n";
			$strRawMessage .= 'Subject: =?utf-8?B?' . base64_encode($strSubject) . "?=\r\n";
			$strRawMessage .= "MIME-Version: 1.0\r\n";
			$strRawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
			$strRawMessage .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n";
			$strRawMessage .= "".$_POST['message']."\r\n";
			// The message needs to be encoded in Base64URL
			$mime = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');
			$msg = new Google_Service_Gmail_Message();
			$msg->setRaw($mime);
			//The special value **me** can be used to indicate the authenticated user.
			$service->users_messages->send("me", $msg);
			$this->response(array('status'=>2,'msg'=>'Email send successfully'), Base_Controller::HTTP_OK );
		}

	}
	public function oauth2callback_get(){
		$client = new Google_Client();
		$tokenPath = FCPATH.'token.json';
		$credentials = FCPATH.'credentials.json';
		$client->setApplicationName('Findable');
		$client->setScopes(Google_Service_Gmail::GMAIL_SEND);
		$client->setAuthConfig($credentials);
		$client->setAccessType('offline');
		$client->setApprovalPrompt('force');
		$client->setPrompt('select_account consent');
		/*$params = base64_encode('{ "user_id" :'.$user_id.'}');
		$client->setState($params);*/
		$client->setRedirectUri(''.$_SERVER['HTTP_ORIGIN'].'/business/search');
		if (isset($_GET['code'])) {
          $client->authenticate($_GET['code']);
          $token_data= $client->getAccessToken();
          	if($token_data['access_token']){
	          	$this->load->model( 'model_users' );
	          	$this->model_users->user_id=$this->get_active_user();
	          	$this->model_users->googleauth(1,$token_data['access_token'],$token_data['refresh_token']);
	          	$this->response(array('status'=>1,'message'=>'you are authenticated successfully','token'=>$token_data['access_token']), Base_Controller::HTTP_OK);
	        }else{
	        	$this->response( [
				'status'  => 0,
				'message' => 'There are some error please try again later'
				], Base_Controller::HTTP_BAD_REQUEST );
	        }
        }else{
        	$this->response( [
				'status'  => 0,
				'message' => 'There are some error please try again later'
				], Base_Controller::HTTP_BAD_REQUEST );
        }    
	}
	public function getClient($user_id)
	{
		$client = new Google_Client();
		$tokenPath = FCPATH.'token.json';
		$credentials = FCPATH.'credentials.json';
		$client->setApplicationName('Findable');
		$client->setScopes(Google_Service_Gmail::GMAIL_SEND);
		$client->setAuthConfig($credentials);
		$client->setAccessType('offline');
		$client->setApprovalPrompt('force');
		$client->setPrompt('select_account consent');
		/*$params = base64_encode('{ "user_id" :'.$user_id.'}');
		$client->setState($params);*/
		//$client->setRedirectUri(''.base_url().'business/oauth2callback');
		$client->setRedirectUri(''.$_SERVER['HTTP_ORIGIN'].'/business/search');
		$this->get_user($this->get_active_user());
		
		// Load previously authorized token from a file, if it exists.
		// The file token.json stores the user's access and refresh tokens, and is
		// created automatically when the authorization flow completes for the first
		// time.

		/*if (file_exists($tokenPath)) {
			$accessToken = json_decode(file_get_contents($tokenPath), true);
			$client->setAccessToken($accessToken);
		}*/
		// If there is no previous token or it's expired.
		// check if auth token is exist
		if($this->model_users->is_google_auth){
			$client->setAccessToken($this->model_users->google_auth_token);
			// If there is no previous token or it's expired.
			if($client->isAccessTokenExpired()) {
	          $client->refreshToken($this->model_users->refresh_token);
	        }
	    }

		if ($client->isAccessTokenExpired()) {
    		// Refresh the token if possible, else fetch a new one.
			if ($client->getRefreshToken()) {
				$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
			}
			else {
        	// Request authorization from the user.
				$authUrl = $client->createAuthUrl();
				if($authUrl){
					return (object)array('status'=>1,'authurl'=>$authUrl);
				}else{
					return (object)array('status'=>0);
				}
				/*printf("Open the following link in your browser:\n%s\n", $authUrl);
				print 'Enter verification code: ';*/
				/*$authCode = trim(fgets(STDIN));
        		// Exchange authorization code for an access token.
				$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
				$client->setAccessToken($accessToken);
        		// Check to see if there was an error.
				if (array_key_exists('error', $accessToken)) {
					throw new Exception(join(', ', $accessToken));
				}*/
			}

		}
		return $client;
	}
	/**
	 * partner_get
	 *
	 * Get the history of the purchases
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function partner_get( $business_id ) {
		$valid               = true;
		$form_validated      = true;
		$manager_business_id = false;

		$from = $this->get( 'from' );
		$to   = $this->get( 'to' );

		// Validate the input parameters
		$form_validated = $this->validateRequestParameters( $this->get(), array(
			'searches_from',
			'searches_to'
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
			$this->load->model( 'Model_business_partner' );
			$this->Model_business_partner->created_by = $business_id;

			$searches = $this->Model_business_partner->get_partner_searches( $from, $to );

			$this->response( $searches, Base_Controller::HTTP_OK );
		}
	}
	/**
	 * partner_get
	 *
	 * Add partner for business account
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @param integer $business_id
	 *
	 * @return    void
	 */
	public function partner_post( $business_id ) {
		$valid          = true;
		$form_validated = true;
		// Get the current user from the cache / from the database
		$this->load->model( 'Model_business_partner' );
		$partner = $this->request->body;
		$partner = ( is_string( $partner ) ) ? json_decode( $partner, true ) : $partner;
		$validate_array=array(
				'name',
				'mobile_numbar',
				'email',
				'company',
				'location[city_name]',
				'job_title'
			);
		if ( is_null( $partner ) || empty( $partner )) {
			$valid = false;
		} else {
			// Validating input parameters
			$form_validated = $this->validateRequestParameters( $partner, $validate_array );
		}
		$is_platform_email        = $this->Model_business_partner->get_by_email_login($this->post( 'email' ));
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
		}else if ( $is_platform_email ) {
			$this->response( [
					'status'  => false,
					'message' => 'User exists, please login'
				], Base_Controller::HTTP_BAD_REQUEST );
		} else {

			$this->load->model('model_location');
			$city_id = $this->model_location->get_city_id($partner['location'], false);
			// Load the business model
			$this->Model_business_partner->name             = (isset($partner['name']))?$partner['name']:'';
			$this->Model_business_partner->created_by = $business_id;
			$this->Model_business_partner->mobile_numbar             = $this->has_param( $partner, 'mobile_numbar' );
			$this->Model_business_partner->city_id                   = $city_id;
			$this->Model_business_partner->email               = $this->has_param( $partner, 'email' );;
			$this->Model_business_partner->company          = $this->has_param( $partner, 'company' );
			$this->Model_business_partner->job_title      = $this->has_param( $partner, 'job_title' );
			$this->Model_business_partner->tags             = $this->has_param( $business, 'tags' );
			$this->Model_business_partner->status                    = 'active'; // The business will become active after the first payment is made
			// Start a database transaction
			$this->db->trans_start();

			// Create the business entity
			$this->Model_business_partner->create();

			$this->db->trans_complete();

			if ( $this->db->trans_status() === false ) {
				// Database transaction failed
				$this->response( [
					'status'  => false,
					'message' => $this->get_transaction_error() ?: 'System error'
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				
				$this->response( [
					'status'  => true,
					'message' => $this->Model_business_partner->id
				], Base_Controller::HTTP_OK );
			}
		}
	}


}