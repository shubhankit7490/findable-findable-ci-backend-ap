<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_position extends Base_Model {
	public $id = null;
	public $type = null;
	public $salary = null;
	public $salary_period = null;
	public $from = null;
	public $to = null;
	public $current = null;

	public $company = array(
		'id'   => null,
		'name' => null
	);

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

	public $job_title = array(
		'id'   => null,
		'name' => null
	);

	public $seniority = array(
		'id'   => null,
		'name' => null
	);

	public $industry = array(
		'id'   => null,
		'name' => null
	);

	public $areas_of_focus = array();

	private $maps = array(
		'positions_of_users_id'      => 'id',
		'type'                       => 'type',
		'salary'                     => 'salary',
		'salary_period'              => 'salary_period',
		'from'                       => 'from',
		'to'                         => 'to',
		'current'                    => 'current',
		'seniority_id'               => array(
			'seniority',
			'id'
		),
		'seniority_name'             => array(
			'seniority',
			'name'
		),
		'company_id'                 => array(
			'company',
			'id'
		),
		'company_name'               => array(
			'company',
			'name'
		),
		'industry_id'                => array(
			'industry',
			'id'
		),
		'industry_name'              => array(
			'industry',
			'name'
		),
		'job_title_id'               => array(
			'job_title',
			'id'
		),
		'job_title_name'             => array(
			'job_title',
			'name'
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
		$this->seniority = $this->array_to_object( $this->seniority );
		$this->company   = $this->array_to_object( $this->company );
		$this->location  = $this->array_to_object( $this->location );
		$this->industry  = $this->array_to_object( $this->industry );
		$this->job_title = $this->array_to_object( $this->job_title );


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
	 * Get the area of focus for a given position object
	 *
	 * @return  object
	 */
	public function get_area_of_focus_of_position() {
		$areas_of_focus = $this->model_areas_of_focus->get_model();

		$select = implode( ',', $areas_of_focus );

		$this->db->select( $select );
		$this->db->from( $this->tbl_areas_of_focus_of_positions_of_users );
		$this->db->join( $this->tbl_areas_of_focus, $this->tbl_areas_of_focus . '.area_of_focus_id = ' . $this->tbl_areas_of_focus_of_positions_of_users . '.area_of_focus_id' );
		$this->db->where( $this->tbl_areas_of_focus_of_positions_of_users . '.position_of_users_id', $this->id );
		$this->db->where( $this->tbl_areas_of_focus_of_positions_of_users . '.deleted', 0 );
		$query = $this->db->get();

		foreach ( $query->result() as $row ) {
			// to chcck if user coming from search then we will display full responsibitily data other wise we display 150 character to make user dashboard view consistent.
			if (strpos($_SERVER['HTTP_REFERER'],'search') !== false) {
		    	$area_of_focus_name=$row->area_of_focus_name;
			}else{
				$area_of_focus_name=substr($row->area_of_focus_name,0,100);
			}
			$this->areas_of_focus[] = (object) array(
				'id'   => $row->area_of_focus_id,
				'name' => $area_of_focus_name
			);
		}

		return $this;
	}
}
