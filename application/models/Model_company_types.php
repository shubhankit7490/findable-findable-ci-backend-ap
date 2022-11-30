<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_company_types extends Base_Model {
	public $company_type_id = null;
	public $company_type_name = null;
	public $deleted = 0;
	public $created = null;

	public function get_model() {
		return array(
			$this->tbl_company_types . '.company_type_id as company_type_id',
			$this->tbl_company_types . '.company_type_name as company_type_name'
		);
	}
}