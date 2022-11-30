<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_minimal_business_profiles extends Base_Model {
	public $id = null;
	public $name = null;
	public $status = 'pending';
	public $extended = null;
	public $created = null;

	public $image = array(
		'id'  => null,
		'url' => null
	);

	private $maps = array(
		'id'        => 'id',
		'name'      => 'name',
		'lastname'  => 'lastname',
		'status'    => 'status',
		'extended'  => 'extended',
		'created'   => 'created',
		'image_id'  => array(
			'image',
			'id'
		),
		'image_url' => array(
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
			if ( isset( $this->{$this->maps[ $name ]} ) ) {
				$this->{$this->maps[ $name ]} = $value;
			}
		}
	}
}