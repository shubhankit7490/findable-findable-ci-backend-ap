<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_fault_reporter extends Base_Model {
	public $id = null;
	public $user_id = null;
	public $firstname = null;
	public $lastname = null;
	public $reason = null;

	public $image = array(
		'id'  => null,
		'url' => null
	);

	private $maps = array(
		'report_id'          => 'id',
		'reporter_id'        => 'user_id',
		'reporter_firstname' => 'firstname',
		'reporter_lastname'  => 'lastname',
		'reason'             => 'reason',
		'reporter_image_id'  => array(
			'image',
			'id'
		),
		'reporter_image_url' => array(
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
					if ( $name == 'image_url' && is_string( $value ) ) {
						try {
							$reference->{$propertyToSet} = $this->upload->serve( $value );
						} catch ( Exception $e ) {
							$reference->{$propertyToSet} = null;
						}
					} else {
						$reference->{$propertyToSet} = $value;
					}
				} else {
					$prop      = new ReflectionProperty( get_class( $reference ), $propertyToSet );
					$reference = $reference->{$prop->name};
				}
			}
		} else {
			$this->{$this->maps[ $name ]} = $value;
		}
	}
}