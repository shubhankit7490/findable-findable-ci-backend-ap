<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_invitations_of_business extends Base_Model {
	public $invitations_of_business_id = null;
	public $business_id = null;
	public $email = null;
	public $job_title_id = null;
	public $purchase_permission = null;
	public $token = null;
	public $accepted = 0;
	public $deleted = 0;
	public $updated = null;
	public $created = null;

	/**
	 * get_by_token
	 *
	 * Get the invite object for the current token
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function get_by_token() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_invitations_of_business );
		$this->db->where( 'token', $this->token );
		$this->db->where( 'accepted', $this->accepted );
		$this->db->where( 'deleted', $this->deleted );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}
	/**
	 * get_by_token
	 *
	 * Get the invite object for the current token
	 *
	 * @access    public
	 *
	 * @role    public
	 * @return    boolean
	 */
	public function get_by_token_email() {
		$this->db->select( 'email' );
		$this->db->from( $this->tbl_invitations_of_business );
		$this->db->where( 'token', $this->token );
		$this->db->where( 'accepted', $this->accepted );
		$this->db->where( 'deleted', $this->deleted );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->row();
		}

		return [];
	}

	/**
	 * get_invites_by_email
	 *
	 * Get the invites collection for the current email
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_invites_by_email() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_invitations_of_business );
		$this->db->where( 'email', $this->email );
		$this->db->where( 'accepted', $this->accepted );
		$this->db->where( 'deleted', $this->deleted );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->result();
		}

		return [];
	}

	/**
	 * is_exists
	 *
	 * Add the model to the database
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function is_exists() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_invitations_of_business );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'email', $this->email );
		$this->db->where( 'accepted', $this->accepted );
		$this->db->where( 'deleted', $this->deleted );
		$this->db->limit( 1 );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	/**
	 * create
	 *
	 * Add the model to the database
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function create() {
		unset( $this->created );
		unset( $this->updated );

		$this->token = $this->generate_token();

		$this->db->insert( $this->tbl_invitations_of_business, $this );
		$this->invitations_of_business_id = $this->db->insert_id();
	}

	/**
	 * accept_invitations
	 *
	 * Mark the given invitations as accepted
	 *
	 * @access    public
	 *
	 * @param array $invites
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function accept_invitations( $invites = [] ) {
		if( count($invites) ) {
			// Extract the invite id's from the invites collection
			$invite_ids = array_map(function($var){return $var->invitations_of_business_id ; } ,$invites);

			// Create the database query
			$this->db->set( 'accepted', 1 );
			$this->db->where_in( 'invitations_of_business_id', $invite_ids );
			$this->db->update( $this->tbl_invitations_of_business );
		}
	}

	/**
	 * generate_token
	 *
	 * Generate a random 16 byte length token
	 *
	 * @access    private
	 *
	 * @params integer bytes
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	private function generate_token( $bytes = 16 ) {
		if ( function_exists( 'random_bytes' ) ) {
			$token = random_bytes( $bytes );
		} else if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$token = openssl_random_pseudo_bytes( $bytes );
		} else {
			return false;
		}

		return bin2hex( $token );
	}
}