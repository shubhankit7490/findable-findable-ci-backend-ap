<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_purchase extends Base_Model {
	public $id = null;
	public $invoice_id = null;
	public $created = null;

	public $package = array(
		'id'                  => null,
		'name'                => null,
		'applicant_screening' => null,
		'initial_credits'     => null,
		'cashback_percent'    => null,
		'credits'             => null,
		'price'               => null
	);

	private $maps = array(
		'id'                  => 'id',
		'package_id'          => array(
			'package',
			'id'
		),
		'name'                => array(
			'package',
			'name'
		),
		'applicant_screening' => array(
			'package',
			'applicant_screening'
		),
		'initial_credits'     => array(
			'package',
			'initial_credits'
		),
		'cashback_percent'    => array(
			'package',
			'cashback_percent'
		),
		'credits'             => array(
			'package',
			'credits'
		),
		'price'               => array(
			'package',
			'price'
		),
		'invoice_id'          => 'invoice_id',
		'created'             => 'created'
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
		$this->package = $this->array_to_object( $this->package );

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
			if ( isset( $this->{$this->maps[ $name ]} ) ) {
				$this->{$this->maps[ $name ]} = $value;
			}

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