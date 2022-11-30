<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_languages extends Base_Model
{
	public $language_id = NULL;
	public $language_name = NULL;
	public $language_code = NULL;
	public $deleted = 0;
	public $created = NULL;

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
			$this->tbl_languages . '.language_id as id',
			$this->tbl_languages . '.language_name as name',
			$this->tbl_languages_of_users . '.language_level as level'
		);
	}

	/**
	 * get_dictionary_model
	 *
	 * Get the model as defined in the Swagger specs (dictionary part)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function get_dictionary_model() {
		return array(
			$this->tbl_languages . '.language_name as name',
			$this->tbl_languages . '.language_id as id'
		);
	}

	/**
	 * get_table
	 *
	 * Get the table which is handled by the model
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function get_table() {
		return $this->tbl_languages;
	}

	/**
	 * get_column_prefix
	 *
	 * Get the columns prefix of the current model
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function get_column_prefix() {
		return 'language_';
	}

	public function get_language_by_code( $code ) {
		$fields = array(
			$this->tbl_languages . '.language_id as id',
			$this->tbl_languages . '.language_code as languageCode',
		);

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->get_table() );
		$this->db->like( $this->tbl_languages . ".language_code", $code, 'after' ); // i.e: HE%, EN%
		$this->db->limit( 1 );
		
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			return $query->row();
		}

		return false;
	}
}