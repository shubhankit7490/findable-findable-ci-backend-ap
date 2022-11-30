<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_admin_users_profiles extends Base_Model {
	public $id = null;
	public $firstname = null;
	public $lastname = null;
	public $role = 'applicant';
	public $status = 'pending';
	public $email = '';
	public $phone_number = '';
	public $view_count = 0;
	public $businesses_applied = null;
	public $location = null;
	public $last_login = '';
	public $created = null;

	public $image = array(
		'id'  => null,
		'url' => null
	);

	private $maps = array(
		'id'        => 'id',
		'firstname' => 'firstname',
		'lastname'  => 'lastname',
		'email' => 'email',
		'phone_number' => 'phone_number',
		'location' => 'location',
		'view_count' => 'view_count',
		'businesses_applied' => 'businesses_applied',
		'role'      => 'role',
		'status'    => 'status',
		'created'   => 'created',
		'last_login' => 'last_login',
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

		if ( isset($this->maps[ $name ]) && is_array( $this->maps[ $name ] ) ) {
			$reference = $this;
			foreach ( $this->maps[ $name ] as $propertyToSet ) {
				if ( end( $this->maps[ $name ] ) == $propertyToSet ) {
					if ( $name == 'image_url' && is_string( $value ) ) {
						try {
							$reference->{$propertyToSet} = $this->upload->serve( $value );
						} catch (Exception $e) {
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
			if ( isset($this->maps[ $name ]) && isset( $this->{$this->maps[ $name ]} ) ) {
				$this->{$this->maps[ $name ]} = $value;
			}
		}
	}
}