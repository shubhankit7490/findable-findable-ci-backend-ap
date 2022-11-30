<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_education extends Base_Model {
	public $id = null;
	public $school_id = null;
	public $name = null;
	public $from = null;
	public $to = null;
	public $current = null;
	public $fields = array();

	public $level = array(
		'id'   => null,
		'name' => null
	);

	private $maps = array(
		'schools_of_user_id'   => 'id',
		'school_id'            => 'school_id',
		'school_name'          => 'name',
		'school_from'          => 'from',
		'school_to'            => 'to',
		'school_current'       => 'current',
		'education_level_id'   => array(
			'level',
			'id'
		),
		'education_level_name' => array(
			'level',
			'name'
		),
	);

	public function __set( $name, $value ) {
		$this->level = $this->array_to_object( $this->level );

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

	public function __get( $name ) {
		if ( isset( $this->$name ) ) {
			return $this->$name;
		} else {
			return get_instance()->$name;
		}
	}

	/**
	 * Get the fields of study for a given school of a user
	 *
	 * @return  object
	 */
	public function get_fields_of_study() {
		$fields_of_study = $this->model_fields_of_study->get_model();

		$select = implode( ',', $fields_of_study );
		$this->db->select( $select );
		$this->db->from( $this->tbl_fields_of_study );
		$this->db->join( $this->tbl_fields_of_study_of_schools_of_users, $this->tbl_fields_of_study_of_schools_of_users . '.fields_of_study_id = ' . $this->tbl_fields_of_study . '.fields_of_study_id' );
		$this->db->where( $this->tbl_fields_of_study_of_schools_of_users . '.schools_of_user_id', $this->id );
		$this->db->where( $this->tbl_fields_of_study_of_schools_of_users . '.deleted', $this->model_schools_of_users->deleted );
		$query = $this->db->get();

		foreach ( $query->result() as $row ) {
			$this->fields[] = (object) array(
				'id'   => $row->field_of_study_id,
				'name' => $row->field_of_study_name
			);
		}

		return $this;
	}
}