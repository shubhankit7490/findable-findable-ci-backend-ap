<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_tokens extends Base_Model {
	public $token_id = null;
	public $user_id = null;
	public $token = null;
	public $type = null;
	public $verified = 0;
	public $deleted = 0;

	/**
	 * get_model
	 *
	 * Get the model as defined in the Swagger specs
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_model() {
		return array(
			$this->tbl_tokens . '.token_id as token_id',
			$this->tbl_tokens . '.user_id as user_id',
			$this->tbl_tokens . '.token as token',
			$this->tbl_tokens . '.type as type',
			$this->tbl_tokens . '.verified as verified'
		);
	}

	/**
	 * create
	 *
	 * Generate a token and save it in the schema
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function create() {
		$this->token = $this->generate_token();
		$this->db->insert( $this->tbl_tokens, $this );
		$this->token_id = $this->db->insert_id();
	}

	/**
	 * get_by_token
	 *
	 * Fetch the token from the schema
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function get_by_token() {
		$fields = $this->get_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_tokens );
		$this->db->where( $this->tbl_tokens . '.token', $this->token );
		$this->db->where( $this->tbl_tokens . '.type', $this->type );
		$this->db->where( $this->tbl_tokens . '.verified', $this->verified );
		$this->db->where( $this->tbl_tokens . '.deleted', $this->deleted );
		$this->db->limit( 1 );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	/**
	 * get_by_type
	 *
	 * Fetch the token of a certain type for the given user_id
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function get_by_type() {
		$fields = $this->get_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_tokens );
		$this->db->where( $this->tbl_tokens . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_tokens . '.type', $this->type );
		$this->db->where( $this->tbl_tokens . '.verified', $this->verified );
		$this->db->limit( 1 );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	/**
	 * verify
	 *
	 * Mark the given token_id as verified
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function verify() {
		$this->db->set( 'verified', 1 );
		$this->db->where( 'token_id', $this->token_id );
		$this->db->update( $this->tbl_tokens );
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