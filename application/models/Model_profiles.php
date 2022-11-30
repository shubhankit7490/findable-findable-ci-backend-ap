<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_profiles extends Base_Model {
	public $profile_id = null;
	public $user_id = null;
	public $profile_firstname = null;
	public $profile_lastname = null;
	public $profile_phone_number = null;
	public $profile_birthday = null;
	public $profile_skype = null;
	public $profile_website = null;
	public $city_id = null;
	public $profile_image = null;
	public $profile_resume=null;
	public $profile_gender = null;
	public $profile_about = null;

	protected $tbl = 'profiles';

	private $public_profile = array(
		'profile_id',
		'updated'
	);

	public function get_model() {
		return array(
			$this->tbl_profiles . '.profile_firstname as firstname',
			$this->tbl_profiles . '.profile_lastname as lastname',
			$this->tbl_profiles . '.profile_phone_number as phone',
			$this->tbl_users . '.email as email',
			$this->tbl_users . '.market_place as market_place',
			$this->tbl_users . '.is_google_auth as is_google_auth',
			$this->tbl_profiles . '.profile_skype as skype',
			$this->tbl_profiles . '.profile_website as website',
			$this->tbl_profiles . '.profile_about as about',
			$this->tbl_profiles . '.updated as updated',
			$this->tbl_profiles . '.profile_gender',
			$this->tbl_profiles . '.profile_birthday'
		);
	}

	public function get_personal_details_model() {
		return array(
			$this->tbl_profiles . '.profile_firstname as firstname',
			$this->tbl_profiles . '.profile_lastname as lastname',
			$this->tbl_profiles . '.profile_phone_number as phone',
			$this->tbl_users . '.email as email',
			$this->tbl_profiles . '.profile_skype as skype',
			$this->tbl_profiles . '.profile_website as website',
			$this->tbl_profiles . '.profile_about as about',
			$this->tbl_profiles . '.profile_gender as gender',
			$this->tbl_profiles . '.profile_birthday as birthday'
		);
	}

	public function set_model() {
		return array(
			'firstname' => 'profile_firstname',
			'lastname'  => 'profile_lastname',
			'phone'     => 'profile_phone_number',
			'skype'     => 'profile_skype',
			'website'   => 'profile_website',
			'image'     => 'profile_image',
			'user_resume'=> 'profile_resume',
			'about'     => 'profile_about',
			'gender'    => 'profile_gender',
			'birthday'  => 'profile_birthday'
		);
	}

	public function get_personal_details() {
		$profile_fields  = $this->get_personal_details_model();
		$location_fields = $this->model_location->get_model();
		$file_fields     = $this->model_files->get_model();

		$fields = $this->merge_fields( $profile_fields, $location_fields, $file_fields );

		$select = implode( ',', $fields );
		$this->db->select( $select );
		$this->db->from( $this->tbl_profiles );
		$this->db->join( $this->tbl_users, $this->tbl_users . '.user_id = ' . $this->tbl_profiles . '.user_id' );
		$this->db->join( $this->tbl_cities, $this->tbl_cities . '.city_id = ' . $this->tbl_profiles . '.city_id', 'left' );
		$this->db->join( $this->tbl_states, $this->tbl_states . '.state_id = ' . $this->tbl_cities . '.state_id', 'left' );
		$this->db->join( $this->tbl_countries, $this->tbl_countries . '.country_id = ' . $this->tbl_cities . '.country_id', 'left' );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_countries . '.continent_id', 'left' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_profiles . '.profile_image', 'left' );
		$this->db->where( $this->tbl_profiles . '.user_id', $this->user_id );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->row( 0, 'model_personal_details' );
		}

		return false;
	}

	public function get_private_profile() {
		$profile_fields  = $this->get_model();
		$location_fields = $this->model_location->get_model();
		$file_fields     = $this->model_files->get_model();
		$resume=array('fli.file_url as resume_url','fli.file_id as resume_id');
		$fields = $this->merge_fields( $profile_fields, $location_fields, $file_fields,$resume );

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_profiles );
		$this->db->join( $this->tbl_users, $this->tbl_users . '.user_id = ' . $this->tbl_profiles . '.user_id' );
		$this->db->join( $this->tbl_cities, $this->tbl_cities . '.city_id = ' . $this->tbl_profiles . '.city_id', 'left' );
		$this->db->join( $this->tbl_states, $this->tbl_states . '.state_id = ' . $this->tbl_cities . '.state_id', 'left' );
		$this->db->join( $this->tbl_countries, $this->tbl_countries . '.country_id = ' . $this->tbl_cities . '.country_id', 'left' );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_countries . '.continent_id', 'left' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_profiles . '.profile_image', 'left' );
		$this->db->join( "$this->tbl_files fli", 'fli' . '.file_id = ' . $this->tbl_profiles . '.profile_resume', 'left' );
		$this->db->where( $this->tbl_profiles . '.user_id', $this->user_id );
		$this->db->limit( 1 );

		$query = $this->db->get();
		if ( $query->num_rows() > 0 ) {
			return $query->row(0, 'model_profile' );
		}

		return false;
	}

	public function get_public_profile() {
		$location_fields = $this->model_location->get_model();

		$fields = $this->merge_fields( $this->public_profile, $location_fields );
		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_profiles );
		$this->db->join( $this->tbl_cities, $this->tbl_cities . '.city_id = ' . $this->tbl_profiles . '.city_id', 'left' );
		$this->db->join( $this->tbl_states, $this->tbl_states . '.state_id = ' . $this->tbl_cities . '.state_id', 'left' );
		$this->db->join( $this->tbl_countries, $this->tbl_countries . '.country_id = ' . $this->tbl_cities . '.country_id', 'left' );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_countries . '.continent_id', 'left' );
		$this->db->where( $this->tbl_profiles . '.user_id', $this->user_id );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->row( 0, 'model_profile' );
		}

		return false;
	}

    public function update_about($about)
    {
        $this->db->set( 'profile_about', $about );
		$this->db->where( 'user_id', $this->user_id );
        
        return $this->db->update( $this->tbl_profiles );
    }
    public function update($data,$id){
    	$this->db->where( 'profile_id',$id);
    	return $this->db->update( $this->tbl_profiles,$data );
    }
	public function create() {
		$this->db->insert( $this->tbl_profiles, $this );
		$this->profile_id = $this->db->insert_id();
	}
	public function create_profile() {
		$this->db->insert( $this->tbl_profiles, $this );
		return $this->db->insert_id();
	}
	public function load() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_profiles );
		$this->db->where( $this->tbl_profiles . '.user_id', $this->user_id );
		$this->db->limit( 1 );

		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		} else {
			return false;
		}
	}

	public function update_last_updated() {
		$this->db->set( 'updated', 'now()', false );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->update( $this->tbl_profiles );
	}

	public function is_manager_profile_updated() {
		$this->db->select('COUNT(user_id) as count');
		$this->db->from($this->tbl_profiles);
		$this->db->where('user_id', $this->user_id);
		$this->db->where('created = updated');
		$query = $this->db->get();

		return $query->row()->count;
	}
}
