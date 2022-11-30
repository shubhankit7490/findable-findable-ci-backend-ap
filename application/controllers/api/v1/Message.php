<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Message extends Base_Controller {

	function __construct() {
		parent::__construct();
	}

	/**
	 * index_get
	 *
	 * Get the messages posted to the platform administrator
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function index_get() {
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
			// Load the upload library for image serving
			$this->load->library( 'Upload' );

			$this->load->model( 'model_files' );

			$this->load->model( 'model_message' );
			$this->load->model( 'model_help_messages_of_users' );

			$offset = $this->is_true_null( $offset ) ? 0 : $offset;

			$messages = $this->model_help_messages_of_users->get_messages( $offset );

			$this->response( $messages, Base_Controller::HTTP_OK );
		}
	}

	/**
	 * index_post
	 *
	 * Send a message to the administrator
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function index_post() {
		$valid          = true;
		$form_validated = true;

		$subject = $this->post( 'subject' );
		$message = $this->post( 'message' );

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'email_subject',
			'email_message_required'
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
			$this->load->model( 'model_profiles' );
			$this->model_profiles->user_id = $this->get_active_user();
			$this->model_profiles->load();
			/*print_r([
				'profile' => $this->model_profiles
			]);

			exit;*/

			$this->load->model( 'model_help_messages_of_users' );
			$this->model_help_messages_of_users->user_id = $this->get_active_user();

			$is_message_overflow = $this->model_help_messages_of_users->is_submit_overflow();

			if ( $is_message_overflow ) {
				$this->response( [
					'status'  => false,
					'message' => $this->lang->line( 'max_attempts' )
				], Base_Controller::HTTP_BAD_REQUEST );
			} else {
				// Save the message in the database
				$this->model_help_messages_of_users->message_subject = $subject;
				$this->model_help_messages_of_users->message_message = $message;
				$created                                             = $this->model_help_messages_of_users->create();

				// Sending the verification email
				$this->config->load( 'security' );

				// Load the template parser class
				$this->load->library( 'Template' );

				// Load the language file with the content of the email
				$this->lang->load( 'email' );
				$email_data = $this->lang->line( 'help_message' );
				$email_data['message'] .= $message;

				// Add technical details to the email
				$email_data['details'] = "";
				$email_data['details'] .= "Account: " . $this->get_active_user() . "<br>";
				$email_data['details'] .= "Name: " . $this->model_profiles->profile_firstname . ' ' . $this->model_profiles->profile_lastname . "<br>";
				$email_data['details'] .= "Email: " . $this->model_users->email . "<br>";
				$email_data['details'] .= "Phone: " . $this->model_profiles->profile_phone_number . "<br>";

				$html = $this->template->view( 'help_message', $email_data, true );

				// Load the Mailgun library wrapper
				$this->load->library( 'Mailgun' );

				// Extract the textual version from the html body version
				$text = $this->mailgun->get_text_version( $html );
				// Load the Mailgun library wrapper
				$this->mailgun->html    = $html;
				$this->mailgun->to      = $this->config->item( 'system_admin_email' );
				$this->mailgun->body    = $text;
				$this->mailgun->subject = $email_data['subject'];
				$sent                   = $this->mailgun->send();

				if ( ! $created ) {
					$this->response( [
						'status'  => false,
						'message' => 'Service unavailable'
					], Base_Controller::HTTP_BAD_REQUEST );
				} else {
					$this->response( "", Base_Controller::HTTP_OK );
				}
			};
		}
	}

	/**
	 * index_delete
	 *
	 * Mark a message as deleted
	 *
	 * @param integer $message_id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function index_delete( $message_id ) {
		$valid = true;

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$this->load->model( 'model_help_messages_of_users' );
			$this->model_help_messages_of_users->help_message_of_user_id = $message_id;
			$deleted = $this->model_help_messages_of_users->delete();

			if ( ! $deleted ) {
				$this->response( [
					'status'  => false,
					'message' => 'Not found'
				], Base_Controller::HTTP_NOT_FOUND );
			} else {
				$this->response( [
					'status'  => true
				], Base_Controller::HTTP_OK );
			}
		}
	}
}
