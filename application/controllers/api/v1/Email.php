<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Email extends Base_Controller {

	function __construct() {
		parent::__construct();
	}

	/**
	 * index_post
	 *
	 * Send an email through the system
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

		$to      = $this->post( 'to' );
		$subject = $this->post( 'subject' );
		$message = $this->has_param( $this->post(), 'message', '' );

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Validating input parameters
		$form_validated = $this->validateRequestParameters( $this->post(), array(
			'email_to',
			'email_subject',
			'email_message'
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
			// Load the template parser class
			$this->load->library( 'Template' );

			// Load the language file with the content of the email
			$this->lang->load( 'email' );
			$email_data = $this->lang->line( 'profile_share' );
			if ( strlen( $message ) ) {
				$email_data['message'] .= ' <br>' . $message;
			}

			$email_data['button_url'] = base_url() . 'user/' . $this->get_active_user();

			$html = $this->template->view( 'profile_share', $email_data, true );

			// Load the Mailgun library wrapper
			$this->load->library( 'Mailgun' );

			// Extract the textual version from the html body version
			$text = $this->mailgun->get_text_version( $html );

			// Replace un wanted text with the link to the profile
			$text = $this->mailgun->str_replace_last('Check my profile', $email_data['button_url'], $text);

			// Parse the subject. Send the user's subject if exists
			if( ! is_null($subject) ) {
				$email_data['subject'] = $subject;
			}

			// Set the sending parameter
			$this->mailgun->subject = $email_data['subject'];
			$this->mailgun->html    = $html;
			$this->mailgun->body    = $text;
			$this->mailgun->to      = $to;
			$sent                   = $this->mailgun->send();

			if ( ! $sent ) {
				$this->response( [
					'status'  => false,
					'message' => 'Email service unavailable'
				], Base_Controller::HTTP_SERVICE_UNAVAILABLE );
			} else {
				$this->response( [
					'status' => true
				], Base_Controller::HTTP_OK );
			}
		}
	}
}
