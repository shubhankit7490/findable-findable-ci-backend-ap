<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Mailgun {
	protected $mg;

	public $body = '';

	public $to = 'israel@interjet.co.il';

	public $subject = 'Test mail';

	public $from = 'service@findable.co';

	public $html = '';

	private $_client = null;

	private $_mailgun = null;

	public function __construct() {
		$this->config->load( 'mailgun' );

		$this->_client  = new \Http\Adapter\Guzzle6\Client();
		$this->_mailgun = new \Mailgun\Mailgun( $this->config->item( 'mailgun_secret_key' ), $this->_client );
	}

	/**
	 * __get
	 *
	 * Enables the use of CI super-global without having to define an extra variable.
	 *
	 * @access    public
	 *
	 * @params    $var
	 *
	 * @return    mixed
	 */
	public function __get( $name ) {
		if ( isset( $this->$name ) ) {
			return $this->$name;
		} else {
			return get_instance()->$name;
		}
	}

	/**
	 * send
	 *
	 * Send an email letter via the mailgun API
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function send() {
		if ( ENVIRONMENT == 'development' ) {
			//return true;
				$response = $this->_mailgun->sendMessage( $this->config->item( 'mailgun_domain' ), array(
				'from'    => $this->config->item( 'mailgun_message_from' ),
				'to'      => $this->to,
				'subject' => $this->subject,
				'html'    => $this->html,
				'text'    => $this->body
			) );

			if ( $response->http_response_code != 200 ) {
				return false;
			} else if ( $response->http_response_body->message != 'Queued. Thank you.' ) {
				return false;
			} else {
				return true;
			}
		} else {
			$response = $this->_mailgun->sendMessage( $this->config->item( 'mailgun_domain' ), array(
				'from'    => $this->config->item( 'mailgun_message_from' ),
				'to'      => $this->to,
				'subject' => $this->subject,
				'html'    => $this->html,
				'text'    => $this->body
			) );

			if ( $response->http_response_code != 200 ) {
				return false;
			} else if ( $response->http_response_body->message != 'Queued. Thank you.' ) {
				return false;
			} else {
				return true;
			}
		}
	}

	/**
	 * get_text_version
	 *
	 * Strip html tags and prepare the string to be sent as a textual version of the html email
	 *
	 * @access    public
	 *
	 * @param string $html
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function get_text_version( $html = '' ) {
		$body = preg_match( '/\<body.*?\>(.*)\<\/body\>/si', $html, $match ) ? $match[1] : $html;
		$body = str_replace( "\t", '', preg_replace( '#<!--(.*)--\>#', '', trim( strip_tags( $body ) ) ) );

		for ( $i = 20; $i >= 3; $i -- ) {
			$body = str_replace( str_repeat( "\n", $i ), "\n\n", $body );
		}

		// Reduce multiple spaces
		$body = preg_replace( '| +|', ' ', $body );

		return $body;
	}

	/**
	 * str_replace_first
	 *
	 * Replace only the first occurrence in a string
	 *
	 * @access    public
	 *
	 * @param string $search the needle to find
	 *
	 * @param string $replace the replaced text
	 *
	 * @param string $subject the text string to search in
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function str_replace_first( $search, $replace, $subject ) {
		$search = '/' . preg_quote( $search, '/' ) . '/';

		return preg_replace( $search, $replace, $subject, 1 );
	}

	/**
	 * str_replace_last
	 *
	 * Replace only the last occurrence in a string
	 *
	 * @access    public
	 *
	 * @param string $search the needle to find
	 *
	 * @param string $replace the replaced text
	 *
	 * @param string $subject the text string to search in
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function str_replace_last( $search, $replace, $subject ) {
		$pos = strrpos( $subject, $search );

		if ( $pos !== false ) {
			$subject = substr_replace( $subject, $replace, $pos, strlen( $search ) );
		}

		return $subject;
	}
}
