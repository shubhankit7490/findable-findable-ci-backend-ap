<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Auth {
	public $user_id;

	public $key;

	public $role;

	public $active_user_id = null;

	public $active_business_id = null;

	public function __construct() {
		$this->ci =& get_instance();
		$this->ci->config->load( 'rest' );
	}

	public function get() {
		return $this->ci->db
			->where( 'user_id', $this->user_id )
			->get( $this->ci->config->item( 'rest_keys_table' ) )
			->row();
	}

	public function create() {
		$this->key = $this->_generate_key();
		return $this->_insert_key( $this->key, array( 'user_id' => $this->user_id ) );
	}

	public function update_active_business() {
		return $this->_update_key( $this->key, array( 'active_business_id' => $this->active_business_id ) );
	}

	public function update_active_user() {
		return $this->_update_key( $this->key, array( 'active_user_id' => $this->active_user_id ) );
	}

	public function reset_active_data() {
		return $this->_update_key( $this->key, array(
			'active_user_id' => $this->active_user_id,
			'active_business_id' => $this->active_business_id
		) );
	}

	private function _generate_key() {
		do {
			// Generate a random salt
			$salt = base_convert( bin2hex( $this->ci->security->get_random_bytes( 64 ) ), 16, 36 );
			// If an error occurred, then fall back to the previous method
			if ( $salt === false ) {
				$salt = hash( 'sha256', time() . mt_rand() );
			}
			$new_key = substr( $salt, 0, $this->ci->config->item( 'rest_key_length' ) );
		} while ( $this->_key_exists( $new_key ) );

		return $new_key;
	}

	/* Private Data Methods */
	private function _get_key( $key ) {
		return $this->ci->db
			->where( $this->ci->config->item( 'rest_key_column' ), $key )
			->get( $this->ci->config->item( 'rest_keys_table' ) )
			->row();
	}

	private function _key_exists( $key ) {
		return $this->ci->db
			       ->where( $this->ci->config->item( 'rest_key_column' ), $key )
			       ->count_all_results( $this->ci->config->item( 'rest_keys_table' ) ) > 0;
	}

	private function _insert_key( $key, $data ) {
		$data[ $this->ci->config->item( 'rest_key_column' ) ] = $key;
		$data[ 'role' ] = $this->role;
		$data['date_created']                                 = function_exists( 'now' ) ? now() : time();

		return $this->ci->db
			->set( $data )
			->insert( $this->ci->config->item( 'rest_keys_table' ) );
	}

	private function _update_key( $key, $data ) {
		return $this->ci->db
			->where( $this->ci->config->item( 'rest_key_column' ), $key )
			->update( $this->ci->config->item( 'rest_keys_table' ), $data );
	}

	private function _delete_key( $key ) {
		return $this->ci->db
			->where( $this->ci->config->item( 'rest_key_column' ), $key )
			->delete( $this->ci->config->item( 'rest_keys_table' ) );
	}
}
