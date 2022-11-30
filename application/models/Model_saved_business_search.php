<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_saved_business_search extends Base_Model {
	public $id = null;
	public $name = null;
	public $created = null;

	public $creator = array(
		'id'   => null,
		'name' => null
	);

	private $maps = array(
		'search_of_business_id' => 'id',
		'name'                  => 'name',
		'status'                => 'status',
		'created'               => 'created',
		'user_id'               => array(
			'creator',
			'id'
		),
		'username'              => array(
			'creator',
			'name'
		)
	);

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
			$this->tbl_searches_of_businesses . '.search_of_business_id as search_of_business_id',
			$this->tbl_searches_of_businesses . '.user_id as user_id',
			$this->tbl_searches_of_businesses . '.search_name as name',
			'CONCAT_WS(" ", ' . $this->tbl_profiles . '.profile_firstname, ' . $this->tbl_profiles . '.profile_lastname) AS username',
			$this->tbl_searches_of_businesses . '.created as created'
		);
	}

	public function get_business_model() {
		return array(
			$this->tbl_searches_of_businesses . '.search_of_business_id as search_of_business_id',
			$this->tbl_searches_of_businesses . '.user_id as user_id',
			$this->tbl_searches_of_businesses . '.search_name as name',
			'CONCAT_WS(" ", ' . $this->tbl_profiles . '.profile_firstname, ' . $this->tbl_profiles . '.profile_lastname) AS username',
			$this->tbl_searches_of_businesses . '.status as status',
			$this->tbl_searches_of_businesses . '.created as created'
		);
	}

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
		$this->creator = $this->array_to_object( $this->creator );

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
}
