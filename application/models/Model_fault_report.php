<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_fault_report extends Base_Model {
	public $user_id = null;
	public $count = 0;
	public $firstname = null;
	public $lastname = null;

	public $image = array(
		'id' => null,
		'url' => null
	);

	public $reports = [];

	public function get_model() {
		return array(
			$this->tbl_business_reports . '.business_report_id as report_id',
			$this->tbl_business_reports . '.business_user_id as reporter_id',
			$this->tbl_business_reports . '.reason as reason',
			'reporter_profile.profile_firstname as reporter_firstname',
			'reporter_profile.profile_lastname as reporter_lastname',
			'reporter_files.file_id as reporter_image_id',
			'reporter_files.file_url as reporter_image_url'
		);
	}

	private $maps = array(
		'user_id' => 'user_id',
		'count'   => 'count',
		'reported_firstname' => 'firstname',
		'reported_lastname' => 'lastname',
		'reported_image_id' => array(
			'image',
			'id'
		),
		'reported_image_url' => array(
			'image',
			'url'
		)
	);

	/**
	 * __set
	 *
	 * Magic method to set the values of the model in a defined structure (maps property)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    array
	 */
	public function __set( $name, $value ) {
		$this->image = $this->array_to_object( $this->image );

		if ( is_array( $this->maps[ $name ] ) ) {
			$reference = $this;
			foreach ( $this->maps[ $name ] as $propertyToSet ) {
				if ( end( $this->maps[ $name ] ) == $propertyToSet ) {
					$reference->{$propertyToSet} = $value;
				} else {
					$prop      = new ReflectionProperty( get_class( $reference ), $propertyToSet );
					$reference = $reference->{$prop->name};
				}
			}
		} else {
			$this->{$this->maps[ $name ]} = $value;
		}
	}

	public function get_report_data() {
		$fields = $this->get_model();
		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_business_reports );
		$this->db->join( $this->tbl_profiles . ' as reporter_profile', 'reporter_profile.user_id = ' . $this->tbl_business_reports . '.business_user_id' );
		$this->db->join( $this->tbl_files . ' as reporter_files', 'reporter_profile.profile_image = reporter_files.file_id', 'LEFT' );
		$this->db->where( $this->tbl_business_reports . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_business_reports . '.deleted', 0 );

		$query = $this->db->get();

		$this->reports = $query->result('model_fault_reporter');
	}
}