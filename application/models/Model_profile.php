<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_profile extends Base_Model {
	public $firstname = null;
	public $lastname = null;
	public $phone = null;
	public $email = null;
	public $market_place = null;
	public $is_google_auth = null;
	public $skype = null;
	public $website = null;
	public $about = null;
	public $updated = null;
	public $profile_gender = null;
	public $profile_birthday = null;

	public $location = array(
		'continent_id'               => null,
		'continent_name'             => null,
		'city_id'                    => null,
		'city_name'                  => null,
		'state_id'                   => null,
		'state_name'                 => null,
		'state_short_name'           => null,
		'country_id'                 => null,
		'country_name'               => null,
		'country_short_name_alpha_3' => null,
		'country_short_name_alpha_2' => null,
	);

	public $image = array(
		'id'  => null,
		'url' => null
	);

	public $resume = array(
		'id'  => null,
		'url' => null
	);
	private $maps = array(
		'profile_id'                  => 'profile_id',
		'firstname'                  => 'firstname',
		'lastname'                   => 'lastname',
		'phone'                      => 'phone',
		'email'                      => 'email',
		'market_place'               => 'market_place',
		'is_google_auth'             => 'is_google_auth',
		'skype'                      => 'skype',
		'website'                    => 'website',
		'about'                      => 'about',
		'updated'                    => 'updated',
		'image_id'                   => array(
			'image',
			'id'
		),
		'image_url'                  => array(
			'image',
			'url'
		),
		'resume_id'                  => array(
			'resume',
			'id'
		),
		'resume_url'                  => array(
			'resume',
			'url'
		),
		'continent_id'               => array(
			'location',
			'continent_id'
		),
		'continent_name'             => array(
			'location',
			'continent_name'
		),
		'city_id'                    => array(
			'location',
			'city_id'
		),
		'city_name'                  => array(
			'location',
			'city_name'
		),
		'state_id'                   => array(
			'location',
			'state_id'
		),
		'state_name'                 => array(
			'location',
			'state_name'
		),
		'state_short_name'           => array(
			'location',
			'state_short_name'
		),
		'country_id'                 => array(
			'location',
			'country_id'
		),
		'country_name'               => array(
			'location',
			'country_name'
		),
		'country_short_name_alpha_3' => array(
			'location',
			'country_short_name_alpha_3'
		),
		'country_short_name_alpha_2' => array(
			'location',
			'country_short_name_alpha_2'
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
		$this->location = $this->array_to_object( $this->location );
		$this->image    = $this->array_to_object( $this->image );
		$this->resume    = $this->array_to_object( $this->resume );
		if ( is_array( $this->maps[ $name ] ) ) {
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
					if ( $name == 'resume_url' && is_string( $value ) ) {
						try {
							//$reference->{$propertyToSet} = $this->upload->get_public_url( $value );
						} catch (Exception $e) {
							$reference->{$propertyToSet} = null;
						}
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

	public function __get( $name ) {
		if ( isset( $this->$name ) ) {
			return $this->$name;
		} else {
			return get_instance()->$name;
		}
	}
}
