<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_files extends Base_Model {
	public $file_id = null;
	public $file_url = null;
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
			$this->tbl_files . '.file_url as image_url',
			$this->tbl_files . '.file_id as image_id'
		);
	}

	/**
	 * create
	 *
	 * Add the current model to the schema
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	public function createfile() {
		$this->db->insert( $this->tbl_files, $this );
		return $this->db->insert_id();
	}
	public function create() {
		$this->db->insert( $this->tbl_files, $this );
		$this->file_id = $this->db->insert_id();
	}
}