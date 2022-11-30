<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_user_data extends Base_Model {

	public $user_id = null;

	public $business_id = null;

	/**
	 * is_applicant_of_business
	 *
	 * Check if the given user_id is an applicant that was purchased by the given business_id or applied to the given business_id
	 *
	 * @access    public
	 *
	 * @return    mixed
	 */
	public function is_applicant_of_business() {
		$sql = "SELECT COUNT(DISTINCT t.business_user_purchase_id) AS purchaseCount,
    			COUNT(DISTINCT p.applicants_of_business_id) AS appliedCount
				FROM users a
    			LEFT JOIN business_user_purchase t ON a.user_id = t.user_id AND t.deleted = 0
                LEFT JOIN applicants_of_business p ON a.user_id = p.user_id AND p.deleted = 0
				WHERE a.user_id = ? AND (t.business_id = ? OR p.business_id = ?)";

		$query  = $this->db->query( $sql, array( $this->user_id, $this->business_id, $this->business_id ) );
		$result = $query->row();

		if ( $result->purchaseCount > 0 || $result->appliedCount > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * get_completion_status
	 *
	 * Get the completion status of the profile sections
	 *
	 * @access    public
	 *
	 * @return    mixed
	 */
	public function get_completion_status() {
		$sql = "SELECT
				COUNT(DISTINCT t.user_status_id) AS statusCount,
    			COUNT(DISTINCT pr.profile_id) AS profileCount,
				CASE WHEN COUNT(DISTINCT po.positions_of_users_id) > 0 THEN 1 ELSE 0 END AS positionsCount,
				CASE WHEN COUNT(DISTINCT la.language_of_user_id) > 0 THEN 1 ELSE 0 END AS languagesCount,
    			CASE WHEN COUNT(DISTINCT tr.trait_of_user_id) > 0 THEN 1 ELSE 0 END AS traitsCount,
    			CASE WHEN COUNT(DISTINCT sc.schools_of_user_id) > 0 THEN 1 ELSE 0 END AS schoolsCount,
				CASE WHEN COUNT(DISTINCT ta.technical_ability_of_user_id) > 0 THEN 1 ELSE 0 END AS techSkillCount
				FROM users a
    			LEFT JOIN " . $this->tbl_user_status . " t ON a.user_id = t.user_id
    			LEFT JOIN " . $this->tbl_profiles . " pr ON a.user_id = pr.user_id
    			LEFT JOIN " . $this->tbl_positions_of_users . " po ON a.user_id = po.user_id AND po.deleted = 0
    			LEFT JOIN " . $this->tbl_languages_of_users . " la ON a.user_id = la.user_id AND la.deleted = 0
				LEFT JOIN " . $this->tbl_traits_of_users . " tr ON a.user_id = tr.user_id AND tr.deleted = 0
				LEFT JOIN " . $this->tbl_schools_of_users . " sc ON a.user_id = sc.user_id AND sc.deleted = 0
                LEFT JOIN " . $this->tbl_technical_abilities_of_users . " ta ON a.user_id = ta.user_id AND ta.deleted = 0
				WHERE a.user_id = ?";

		$query   = $this->db->query( $sql, array( $this->user_id ) );
		$result  = $query->row_array();
		$percent = ( 100 / count( $result ) ) * array_sum( $result );

		return number_format( $percent, 0, '.', '' );
	}

	public function clean() {
		$this->db->trans_start();

//		$sql = "UPDATE " . $this->tbl_user_status . " SET `user_status_employment_status`=NULL, `user_status_current`=NULL, `user_status_employment_type`=NULL, `user_status_desired_salary_period`=NULL, `user_status_desired_salary`=NULL, `user_status_benefits`=NULL, `user_status_only_current_location`=NULL, `user_status_relocation`=NULL, `user_status_available_from`=NULL WHERE `user_id`='". $this->user_id ."'";
//		$this->db->simple_query($sql);
//
//		$sql = "UPDATE ". $this->tbl_profiles ." SET `profile_phone_number`=NULL, `profile_birthday`=NULL, `profile_skype`=NULL, `profile_website`=NULL, `city_id`=NULL, `profile_gender`=NULL, `profile_about`=NULL WHERE `user_id`='". $this->user_id ."'";
//		$this->db->simple_query($sql);

		$sql = "DELETE posts FROM ". $this->tbl_positions_of_users ." INNER JOIN ". $this->tbl_areas_of_focus ." ON " . $this->tbl_areas_of_focus . ".position_of_users_id = ". $this->tbl_positions_of_users .".positions_of_users_id WHERE ". $this->tbl_positions_of_users .".user_id = '". $this->user_id ."'";
		$this->db->simple_query($sql);

		$sql = "DELETE posts FROM ". $this->tbl_schools_of_users ." INNER JOIN ". $this->tbl_fields_of_study_of_schools_of_users ." ON " . $this->tbl_fields_of_study_of_schools_of_users . ".schools_of_user_id = ". $this->tbl_schools_of_users .".schools_of_user_id WHERE ". $this->tbl_schools_of_users .".user_id = '". $this->user_id ."'";
		$this->db->simple_query($sql);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_languages_of_users);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_traits_of_users);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_technical_abilities_of_users);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_blocked_businesses);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_allowed_businesses);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_business_users);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_tokens);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_keys);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_profiles);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_user_status);

		$this->db->where('business_id', $this->business_id);
		$query = $this->db->delete($this->tbl_purchase_history);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_purchase_history);

		$this->db->where('user_id', $this->user_id);
		$query = $this->db->delete($this->tbl_users);

		$this->db->where('business_id', $this->business_id);
		$query = $this->db->delete($this->tbl_credits);

		$this->db->where('business_id', $this->business_id);
		$query = $this->db->delete($this->tbl_payments);

		$this->db->where('business_id', $this->business_id);
		$query = $this->db->delete($this->tbl_packages_of_business);

		$this->db->where('business_id', $this->business_id);
		$query = $this->db->delete($this->tbl_business);

//		$this->db->set('verified', 0);
//		$this->db->set('search_visible', 0);
//		$this->db->where('user_id', $this->user_id);
//		$this->db->update($this->tbl_users);
//
//		$this->db->set('verified', 0);
//		$this->db->where('type', 'activation');
//		$this->db->where('user_id', $this->user_id);
//		$this->db->order_by('token_id', 'DESC');
//		$this->db->limit(1);
//		$this->db->update($this->tbl_tokens);

		$this->db->trans_complete();

		if ( $this->db->trans_status() === false ) {
			print_r($this->db->error()['message']);
			exit();
		} else {
			return true;
		}
	}

	public function get_full_user_data( $user_id ) {
		$fields = array(
			$this->tbl_profiles . '.user_id as user_id',
			$this->tbl_profiles . '.profile_firstname as firstname',
			$this->tbl_profiles . '.profile_lastname as lastname',
			$this->tbl_profiles . '.profile_about as about_me',
			$this->tbl_profiles . '.profile_phone_number as phone_number',
			$this->tbl_users . '.email as email',
			$this->tbl_cities . '.city_name as city',
			$this->tbl_states . '.state_name as state',
			$this->tbl_countries . '.country_name as country'
		);

		$select = implode( ', ', $fields );

		$sql = 'SELECT ' . $select;
		$sql .= ' FROM ' . $this->tbl_profiles;
		$sql .= ' INNER JOIN '. $this->tbl_users .' ON ' . $this->tbl_profiles . '.user_id = ' . $this->tbl_users . '.user_id';
		$sql .= ' LEFT JOIN '. $this->tbl_cities . ' ON ' . $this->tbl_profiles . '.city_id = ' . $this->tbl_cities . '.city_id';
		$sql .= ' LEFT JOIN '. $this->tbl_states . ' ON ' . $this->tbl_cities . '.state_id = ' . $this->tbl_states . '.state_id';
		$sql .= ' LEFT JOIN '. $this->tbl_countries . ' ON ' . $this->tbl_states . '.country_id = ' . $this->tbl_countries . '.country_id';
		$sql .= ' WHERE ' . $this->tbl_profiles . '.user_id = ' . $user_id;
		$sql .= ' GROUP BY ' . $this->tbl_profiles . '.user_id';
		$sql .= ' LIMIT 1';
		
		$query = $this->db->query( $sql );

		$printable = $query->row(0, 'model_printable');
		$printable->getSkills()->getLanguages()->getTraits()->getPositions()->getSchools();

		return json_decode(json_encode($printable), true);
		
	}
	public function is_user_profile_visible( $user_id ) {
		$fields = array('created_by','creator_id');
		$select = implode( ', ', $fields );
		$sql = 'SELECT ' . $select;
		$sql .= ' FROM ' . $this->tbl_users;
		$sql .= ' WHERE ' . 'user_id = ' . $user_id;
		$query = $this->db->query( $sql) ;
		$data=$query->row();
		if(!empty($data))
		{
			return $data;
		}
		else
		{
			return false;
		}
	}
}