<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_help_messages_of_users extends Base_Model {
	public $help_message_of_user_id = null;
	public $user_id = null;
	public $message_subject = null;
	public $message_message = null;
	public $deleted = 0;
	public $created = null;

	public function get_model() {
		return array(
			$this->tbl_help_messages_of_users . '.help_message_of_user_id as id',
			$this->tbl_profiles . '.profile_firstname as firstname',
			$this->tbl_profiles . '.profile_lastname as lastname',
			$this->tbl_help_messages_of_users . '.message_subject as subject',
			$this->tbl_help_messages_of_users . '.message_message as message',
			$this->tbl_help_messages_of_users . '.created as created'
		);
	}

	/**
	 * is_submit_overflow
	 *
	 * Check if the given user_id sent too many messages in a pre-defined interval
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function is_submit_overflow() {
		$this->config->load('security');
		$max_attempts = $this->config->item('max_help_message_per_hour');

		$this->db->select( 'COUNT(*) as count' );
		$this->db->from( $this->tbl_help_messages_of_users );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'deleted', $this->deleted );
		$this->db->where( 'created >', 'DATE_SUB(now(), INTERVAL 1 HOUR)', FALSE);

		$query = $this->db->get();
		$message_count = $query->row()->count;

		return ($message_count >= $max_attempts);
	}

	/**
	 * create
	 *
	 * Creates a new view record for the current day
	 *
	 * @access    public
	 *
	 * @return    integer
	 */
	public function create() {
		unset( $this->created );
		$this->db->insert( $this->tbl_help_messages_of_users, $this );
		$this->help_message_of_user_id = $this->db->insert_id();

		return $this->db->affected_rows() > 0;
	}

	/**
	 * get_messages
	 *
	 * Get a collection of messages that were sent by the platform users
	 *
	 * @param integer $offset
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    array
	 */
	public function get_messages( $offset = null ) {
		$message_fields = $this->get_model();
		$file_fields            = $this->model_files->get_model();

		$fields = $this->merge_fields( $message_fields, $file_fields );

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_help_messages_of_users );
		$this->db->join( $this->tbl_profiles, $this->tbl_help_messages_of_users . '.user_id = ' . $this->tbl_profiles . '.user_id' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_profiles . '.profile_image', 'left' );
		$this->db->where($this->tbl_help_messages_of_users . '.deleted', 0);
		$this->db->order_by($this->tbl_help_messages_of_users . '.created', ' DESC');
		$this->db->limit( 50, $offset );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->result( 'model_message' );
		}

		return [];
	}

	/**
	 * delete
	 *
	 * Soft delete user's help message
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function delete() {
		$this->db->set( 'deleted', 1 );
		$this->db->where( $this->tbl_help_messages_of_users . '.help_message_of_user_id', $this->help_message_of_user_id );
		$this->db->update( $this->tbl_help_messages_of_users );

		return $this->db->affected_rows() > 0;
	}
}