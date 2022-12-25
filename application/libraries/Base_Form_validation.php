<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Base_Form_validation extends CI_Form_validation {

	protected static $rules = array(
		'firstname'              => array(
			'field'  => 'firstname',
			'label'  => 'lang:firstname',
			'rules'  => 'trim|required|max_length[150]',
			'errors' => array()
		),
		'lastname'               => array(
			'field'  => 'lastname',
			'label'  => 'lang:lastname',
			'rules'  => 'trim|required|max_length[150]',
			'errors' => array()
		),
		'email'                  => array(
			'field'  => 'email',
			'label'  => 'lang:email',
			'rules'  => 'trim|required|max_length[255]|valid_email',
			'errors' => array()
		),
		'password'               => array(
			'field'  => 'password',
			'label'  => 'lang:password',
			'rules'  => 'trim|required|min_length[6]',
			'errors' => array()
		),
		'phone'                  => array(
			'field'  => 'phone',
			'label'  => 'lang:phone',
			'rules'  => 'trim|max_length[16]',
			'errors' => array()
		),
		'mobile_numbar'                  => array(
			'field'  => 'mobile_numbar',
			'label'  => 'mobile number',
			'rules'  => 'trim|required|max_length[16]',
			'errors' => array()
		),
		'job_title'                  => array(
			'field'  => 'job_title',
			'label'  => 'job title',
			'rules'  => 'trim|required',
			'errors' => array()
		),
		'skype'                  => array(
			'field'  => 'skype',
			'label'  => 'lang:skype',
			'rules'  => 'trim|max_length[255]',
			'errors' => array()
		),
		'gender'                 => array(
			'field'  => 'gender',
			'label'  => 'lang:gender',
			'rules'  => 'trim|min_length[1]|max_length[1]|in_list[M,F]',
			'errors' => array()
		),
		'role'                   => array(
			'field'  => 'role',
			'label'  => 'lang:role',
			'rules'  => 'trim|required|in_list[applicant,manager,recruiter]',
			'errors' => array()
		),
		'image'                  => array(
			'field'  => 'image',
			'label'  => 'lang:image',
			'rules'  => 'trim|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'image_id'               => array(
			'field'  => 'image[id]',
			'label'  => 'lang:image',
			'rules'  => 'trim|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'location[city_id]'      => array(
			'field'  => 'location[city_id]',
			'label'  => 'lang:location',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[11]|city_id_integer',
			'errors' => array()
		),
		'location[city_name]'      => array(
			'field'  => 'location[city_name]',
			'label'  => 'lang:location',
			'rules'  => 'trim|required|min_length[1]|max_length[500]',
			'errors' => array()
		),
		'salary'                 => array(
			'field'  => 'salary',
			'label'  => 'lang:salary',
			'rules'  => 'trim|integer|min_length[1]|max_length[8]',
			'errors' => array()
		),
		'salary_period'          => array(
			'field'  => 'salary_period',
			'label'  => 'lang:salary_period',
			'rules'  => 'trim|min_length[1]|max_length[1]|in_list[H,M,W,Y]',
			'errors' => array()
		),
		'from'                   => array(
			'field'  => 'from',
			'label'  => 'lang:from',
			'rules'  => 'trim|datetime',
			'errors' => array()
		),
		'from_required'          => array(
			'field'  => 'from',
			'label'  => 'lang:from',
			'rules'  => 'trim|required|datetime',
			'errors' => array()
		),
		'type'                   => array(
			'field'  => 'type',
			'label'  => 'lang:type',
			'rules'  => 'trim|min_length[1]|max_length[50]',
			'errors' => array()
		),
		'to'                     => array(
			'field'  => 'to',
			'label'  => 'lang:to',
			'rules'  => 'trim|datetime',
			'errors' => array()
		),
		'start'                  => array(
			'field'  => 'start',
			'label'  => 'lang:start',
			'rules'  => 'trim|datetime_or_null',
			'errors' => array()
		),
		'end'                    => array(
			'field'  => 'end',
			'label'  => 'lang:end',
			'rules'  => 'trim|datetime_or_null',
			'errors' => array()
		),
		'company[id]'            => array(
			'field'  => 'company[id]',
			'label'  => 'lang:company',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[11]|company_id_integer',
			'errors' => array()
		),
		'company'            => array(
			'field'  => 'company',
			'label'  => 'lang:company',
			'rules'  => 'trim|required|min_length[1]|max_length[256]',
			'errors' => array()
		),
		'industry[id]'           => array(
			'field'  => 'industry[id]',
			'label'  => 'lang:industry',
			'rules'  => 'trim|integer|min_length[1]|max_length[11]|industry_id_integer',
			'errors' => array()
		),
		'job_title[id]'          => array(
			'field'  => 'job_title[id]',
			'label'  => 'lang:job_title',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[11]|job_title_id_integer',
			'errors' => array()
		),
		'optional_job_title[id]' => array(
			'field'  => 'jobtitle[id]',
			'label'  => 'lang:job_title',
			'rules'  => 'trim|integer|min_length[1]|max_length[11]|job_title_id_integer',
			'errors' => array()
		),
		'seniority[id]'          => array(
			'field'  => 'seniority[id]',
			'label'  => 'lang:seniority',
			'rules'  => 'trim|integer|min_length[1]|max_length[11]|seniority_id_integer',
			'errors' => array()
		),
		'available_from'         => array(
			'field'  => 'available_from',
			'label'  => 'lang:available_from',
			'rules'  => 'trim|required',
			'errors' => array()
		),
		'desired_salary_period'  => array(
			'field'  => 'desired_salary_period',
			'label'  => 'lang:desired_salary_period',
			'rules'  => 'trim|max_length[1]|in_list[H,M,Y]',
			'errors' => array()
		),
		'benefits'               => array(
			'field'  => 'benefits',
			'label'  => 'lang:benefits',
			'rules'  => 'trim|min_length[1]|max_length[1]|is_boolean',
			'errors' => array()
		),
		'relocation'             => array(
			'field'  => 'relocation',
			'label'  => 'lang:relocation',
			'rules'  => 'trim|min_length[1]|max_length[1]|is_boolean',
			'errors' => array()
		),
		'legal_usa'              => array(
			'field'  => 'legal_usa',
			'label'  => 'lang:legal_usa',
			'rules'  => 'trim|min_length[1]|max_length[1]|is_boolean',
			'errors' => array()
		),
		'only_current_location'  => array(
			'field'  => 'only_current_location',
			'label'  => 'lang:only_current_location',
			'rules'  => 'trim|min_length[1]|max_length[1]|is_boolean',
			'errors' => array()
		),
		'start_time'             => array(
			'field'  => 'start_time',
			'label'  => 'lang:start_time',
			'rules'  => 'trim|datetime',
			'errors' => array()
		),
		'start_time_required'    => array(
			'field'  => 'start_time',
			'label'  => 'lang:start_time',
			'rules'  => 'trim|required|datetime',
			'errors' => array()
		),
		'employment_status'      => array(
			'field'  => 'employment_status',
			'label'  => 'lang:employment_status',
			'rules'  => 'trim|required',
			'errors' => array()
		),
		'current_status'         => array(
			'field'  => 'current_status',
			'label'  => 'lang:current_status',
			'rules'  => 'trim|required',
			'errors' => array()
		),
		'employment_type'        => array(
			'field'  => 'employment_type',
			'label'  => 'lang:employment_type',
			'rules'  => 'trim|min_length[1]|max_length[50]',
			'errors' => array()
		),
		'prominance'             => array(
			'field'  => 'prominance',
			'label'  => 'lang:prominance',
			'rules'  => 'trim|min_length[10]|required',
			'errors' => array()
		),
		'skill_level'            => array(
			'field'  => 'level',
			'label'  => 'lang:level',
			'rules'  => 'trim|required|less_than_equal_to[100]|greater_than_equal_to[1]',
			'errors' => array()
		),
		'current'                => array(
			'field'  => 'current',
			'label'  => 'lang:current',
			'rules'  => 'trim|is_boolean',
			'errors' => array()
		),
		'education_level_name'   => array(
			'field'  => 'level[name]',
			'label'  => 'lang:level_name',
			'rules'  => 'trim|required|min_length[1]|max_length[30]',
			'errors' => array()
		),
		'education_level_id'     => array(
			'field'  => 'level[id]',
			'label'  => 'lang:level_id',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'school_id'              => array(
			'field'  => 'school_id',
			'label'  => 'lang:school_id',
			'rules'  => 'trim|integer|required|min_length[1]|school_id_integer',
			'errors' => array()
		),
		'level'                  => array(
			'field'  => 'level',
			'label'  => 'lang:level',
			'rules'  => 'trim|required|integer|in_list[1,2,3]',
			'errors' => array()
		),
		'dictionary_item'        => array(
			'field'  => 'name',
			'label'  => 'lang:dictionary_item',
			'rules'  => 'trim|required|min_length[1]|max_length[150]',
			'errors' => array()
		),
		'token_type'             => array(
			'field'  => 'type',
			'label'  => 'lang:token_type',
			'rules'  => 'trim|required|in_list[email,activation]',
			'errors' => array()
		),
		'email_to'               => array(
			'field'  => 'to',
			'label'  => 'lang:email_to',
			'rules'  => 'trim|required|max_length[255]|valid_email',
			'errors' => array()
		),
		'email_subject'          => array(
			'field'  => 'subject',
			'label'  => 'lang:email_subject',
			'rules'  => 'trim|required|max_length[50]',
			'errors' => array()
		),
		'email_message'          => array(
			'field'  => 'message',
			'label'  => 'lang:email_message',
			'rules'  => 'trim|max_length[250]',
			'errors' => array()
		),
		'email_message_required' => array(
			'field'  => 'message',
			'label'  => 'lang:email_message',
			'rules'  => 'trim|required|max_length[250]',
			'errors' => array()
		),
		'business_name'          => array(
			'field'  => 'name',
			'label'  => 'lang:business_name',
			'rules'  => 'trim|required|max_length[100]',
			'errors' => array()
		),
		'years_established'      => array(
			'field'  => 'year_established',
			'label'  => 'lang:year_established',
			'rules'  => 'trim|required|integer|exact_length[4]',
			'errors' => array()
		),
		'company_size'           => array(
			'field'  => 'size',
			'label'  => 'lang:company_size',
			'rules'  => 'trim|integer',
			'errors' => array()
		),
		'business_location'      => array(
			'field'  => 'location[city_name]',
			'label'  => 'lang:location',
			'rules'  => 'trim|required|min_length[1]|max_length[150]',
			'errors' => array()
		),
		'duns_number'            => array(
			'field'  => 'duns',
			'label'  => 'lang:duns_number',
			'rules'  => 'trim|integer',
			'errors' => array()
		),
		'company_type'           => array(
			'field'  => 'type',
			'label'  => 'lang:company_type',
			'rules'  => 'trim|integer',
			'errors' => array()
		),
		'web_address'            => array(
			'field'  => 'web_address',
			'label'  => 'lang:web_address',
			'rules'  => 'trim|required|valid_url',
			'errors' => array()
		),
		'logo'                   => array(
			'field'  => 'logo',
			'label'  => 'lang:logo',
			'rules'  => 'trim|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'stripe_token'           => array(
			'field'  => 'stripe_token',
			'label'  => 'lang:stripe_token',
			'rules'  => 'trim|required|min_length[10]|max_length[30]',
			'errors' => array()
		),
		'package_id'             => array(
			'field'  => 'package_id',
			'label'  => 'lang:package_id',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'billing_name'           => array(
			'field'  => 'billing_name',
			'label'  => 'lang:billing_name',
			'rules'  => 'trim|max_length[100]',
			'errors' => array()
		),
		'auto_reload'            => array(
			'field'  => 'auto_reload',
			'label'  => 'lang:auto_reload',
			'rules'  => 'trim|required|is_boolean',
			'errors' => array()
		),
		'valid_months'           => array(
			'field'  => 'months',
			'label'  => 'lang:months',
			'rules'  => 'trim|required|integer|in_list[1,3,6,12]',
			'errors' => array()
		),
		'valid_user_email'       => array(
			'field'  => 'email',
			'label'  => 'lang:email',
			'rules'  => 'trim|required|max_length[255]|valid_email|db_exists[users.email]',
			'errors' => array()
		),
		'token'                  => array(
			'field'  => 'token',
			'label'  => 'lang:token',
			'rules'  => 'trim|required|exact_length[32]',
			'errors' => array()
		),
		'offset'                 => array(
			'field'  => 'offset',
			'label'  => 'lang:offset',
			'rules'  => 'trim|integer|is_natural',
			'errors' => array()
		),
		'search'                 => array(
			'field'  => 'search',
			'label'  => 'lang:search',
			'rules'  => 'trim|required|valid_json',
			'errors' => array()
		),
		'applicant_status'       => array(
			'field'  => 'status',
			'label'  => 'lang:status',
			'rules'  => 'trim|required|in_list[short,interviewing,initial,hired,rejected,irrelevant,rejected-expensive,rejected-experience]',
			'errors' => array()
		),
		'birthday'               => array(
			'field'  => 'birthday',
			'label'  => 'lang:birthday',
			'rules'  => 'trim|datetime',
			'errors' => array()
		),
		'user_note'              => array(
			'field'  => 'note',
			'label'  => 'lang:note',
			'rules'  => 'trim|required|min_length[1]|max_length[10000]',
			'errors' => array()
		),
		'note_type' => array(
			'field'  => 'type',
			'label'  => 'lang:note_type',
			'rules'  => 'trim|required|min_length[1]|max_length[1500]',
			'errors' => array()
		),
		'search_name'            => array(
			'field'  => 'name',
			'label'  => 'lang:required',
			'rules'  => 'trim|required|min_length[1]|max_length[150]',
			'errors' => array()
		),
		'searches_from'          => array(
			'field'  => 'from',
			'label'  => 'lang:from',
			'rules'  => 'trim|datetime',
			'errors' => array()
		),
		'searches_to'            => array(
			'field'  => 'to',
			'label'  => 'lang:to',
			'rules'  => 'trim|datetime',
			'errors' => array()
		),
		'purchase_permission'    => array(
			'field'  => 'purchase_permission',
			'label'  => 'lang:purchase_permission',
			'rules'  => 'trim|is_binary',
			'errors' => array()
		),
		'block_all'              => array(
			'field'  => 'block_all',
			'label'  => 'lang:block_all',
			'rules'  => 'trim|required|is_binary',
			'errors' => array()
		),
		'entityId'               => array(
			'field'  => 'id',
			'label'  => 'lang:id',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'about'                  => array(
			'field'  => 'about',
			'label'  => 'lang:about',
			'rules'  => 'trim|max_length[300]',
			'errors' => array()
		),
		'orderby'                => array(
			'field'  => 'orderby',
			'label'  => 'lang:orderby',
			'rules'  => 'trim|in_list[location,jobtitle,experience,seniority,salary]',
			'errors' => array()
		),
		'order'                  => array(
			'field'  => 'order',
			'label'  => 'lang:order',
			'rules'  => 'trim|in_list[asc,desc]',
			'errors' => array()
		),
		'apply'                  => array(
			'field'  => 'apply',
			'label'  => 'lang:apply',
			'rules'  => 'trim|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'active_business_id'     => array(
			'field'  => 'active_business_id',
			'label'  => 'lang:active_business_id',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'reason'                 => array(
			'field'  => 'reason',
			'label'  => 'lang:reason',
			'rules'  => 'trim|required|min_length[1]|max_length[100]',
			'errors' => array()
		),
		'search_status'          => array(
			'field'  => 'status',
			'label'  => 'lang:search_status',
			'rules'  => 'trim|required|in_list[in progress,closed]',
			'errors' => array()
		),
		'user_type'              => array(
			'field'  => 'type',
			'label'  => 'lang:user_type',
			'rules'  => 'trim|required|in_list[user,business]',
			'errors' => array()
		),
		'user_status'            => array(
			'field'  => 'status',
			'label'  => 'lang:user_status',
			'rules'  => 'trim|required|in_list[active,pending,banned,deleted]',
			'errors' => array()
		),
		'business_status'        => array(
			'field'  => 'status',
			'label'  => 'lang:user_status',
			'rules'  => 'trim|in_list[active,pending,banned,deleted,setup]',
			'errors' => array()
		),
		'business_days'          => array(
			'field'  => 'days',
			'label'  => 'lang:business_days',
			'rules'  => 'trim|is_natural',
			'errors' => array()
		),
		'package_update_id'      => array(
			'field'  => 'id',
			'label'  => 'lang:package_id',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'package_initial_credits' => array(
			'field'  => 'initial_credits',
			'label'  => 'lang:initial_credits',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'package_credits' => array(
			'field'  => 'credits',
			'label'  => 'lang:credits',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[11]',
			'errors' => array()
		),
		'package_name' => array(
			'field'  => 'name',
			'label'  => 'lang:package_name',
			'rules'  => 'trim|required|min_length[1]|max_length[150]',
			'errors' => array()
		),
		'name' => array(
			'field'  => 'name',
			'label'  => 'name',
			'rules'  => 'trim|required|min_length[1]|max_length[150]',
			'errors' => array()
		),
		'package_price' => array(
			'field'  => 'price',
			'label'  => 'lang:package_price',
			'rules'  => 'trim|required|numeric',
			'errors' => array()
		),
		'package_cash_back_percent' => array(
			'field'  => 'cashback_percent',
			'label'  => 'lang:cashback_percent',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[3]',
			'errors' => array()
		),
		'package_users' => array(
			'field'  => 'users',
			'label'  => 'lang:users',
			'rules'  => 'trim|required|integer|min_length[1]|max_length[3]',
			'errors' => array()
		),
		'dictionary_name'        => array(
			'field'  => 'dictionary',
			'label'  => 'lang:dictionary_name',
			'rules'  => 'trim|required|in_list[tech,schools,studyfields,focusareas,company,industry,jobtitle,seniority]',
			'errors' => array()
		),
		'dictionary_item_state'        => array(
			'field'  => 'approved',
			'label'  => 'lang:approved',
			'rules'  => 'trim|required|is_binary',
			'errors' => array()
		),
		'log_category' => array(
			'field'  => 'category',
			'label'  => 'lang:category',
			'rules'  => 'trim|required|in_list[profile_share]',
			'errors' => array()
		),
		'log_action' => array(
			'field'  => 'action',
			'label'  => 'lang:action',
			'rules'  => 'trim|required|in_list[facebook,linkedin,twitter,email,googleplus,whatsapp]',
			'errors' => array()
		),
		'company_name' => array(
			'field'  => 'company',
			'label'  => 'lang:company',
			'rules'  => 'trim|required|min_length[1]|max_length[150]',
			'errors' => array()
		),
		'fullname' => array(
			'field'  => 'fullname',
			'label'  => 'lang:fullname',
			'rules'  => 'trim|required|min_length[1]|max_length[150]',
			'errors' => array()
		),
		'contact_request_message' => array(
			'field'  => 'message',
			'label'  => 'lang:contact_message',
			'rules'  => 'trim|max_length[150]',
			'errors' => array()
		)
	);

	protected static $available_rules = array(
		'max_length',
		'min_length',
		'integer',
		'required',
		'in_list',
		'is_boolean',
		'exact_length',
		'is_natural',
		'numeric',
		'school_id_integer',
		'company_id_integer',
		'industry_id_integer',
		'job_title_id_integer',
		'seniority_id_integer',
		'datetime',
		'datetime_or_null',
		'valid_email',
		'trim',
		'alpha_and_numeric',
		'valid_url',
		'is_unique',
		'db_exists',
		'valid_json',
		'is_binary',
		'greater_than_equal_to',
		'less_than_equal_to'
	);

	/**
	 * __get
	 *
	 * Enables the use of CI super-global without having to define an extra variable.
	 *
	 * @access    public
	 *
	 * @param    $name
	 *
	 * @return    mixed
	 */
	public function __get( $name ) {
		if ( isset( $this->$name ) ) {
			return $this->$name;
		} else {
			return get_instance()->$name;
		}
	}

	/**
	 * compile
	 *
	 * Compile an error message
	 *
	 * @access    public
	 *
	 * @param    $field
	 *
	 * @param    $rule
	 *
	 * @return    mixed
	 */
	public function compile( $field, $rule ) {
		return str_replace( '{field}', $field, $rule );
	}

	/**
	 * rule
	 *
	 * Load a rule from a predefined array of rules
	 *
	 * @param   string $field
	 *
	 * @return null
	 */
	public function rule( $field ) {
		// Check if the rule for this field exists
		if ( in_array( $field, array_keys( self::$rules ) ) ) {
			$rule  = self::$rules[ $field ];
			$rules = $this->parse( $rule );

			foreach ( $rules as $_rule ) {
				$rule['errors'][ $_rule ] = $this->lang->line( $_rule );
			}

			$this->set_rules( $rule['field'], $rule['label'], $rule['rules'], $rule['errors'] );
		}
	}

	/**
	 * rules
	 *
	 * Load array of rules from a predefined switch-case of rules
	 *
	 * @return  null
	 */
	public function rules() {
		if ( gettype( func_get_arg( 0 ) ) == 'array' ) {
			foreach ( func_get_arg( 0 ) as $index => $field ) {
				if ( ! is_array( $field ) ) {
					$this->rule( $field );
				} else {
					$this->rule(
						$index,
						array_key_exists( 'rules', $field ) ? $field['rules'] : false,
						array_key_exists( 'errors', $field ) ? $field['errors'] : array()
					);
				}
			}
		} else {
			foreach ( func_get_args() as $index => $field ) {
				$this->rule( $field );
			}
		}
	}

	/**
	 * parse
	 *
	 * Get the needed validation rules from te given rule object (parse using preg_match from the static available_rules)
	 *
	 * @param   array $rule
	 *
	 * @return  array
	 */
	private function parse( $rule ) {
		$found = array();
		foreach ( self::$available_rules as $availiable_rule ) {
			if ( preg_match( "/\b$availiable_rule\b/", $rule['rules'] ) ) {
				$found[] = $availiable_rule;
			}
		}

		return $found;
	}

	/**
	 * Required
	 *
	 * @param    string
	 *
	 * @return    bool
	 */
	public function required( $str ) {
		if ( is_bool( $str ) ) {
			return true;
		} else if ( is_array( $str ) ) {
			return empty( $str );
		} else {
			return trim( $str ) !== '';
		}
	}

	/**
	 * alpha_and_numeric
	 *
	 * Verify that the string provided contain alphabetical and numeric characters
	 *
	 * @param   string $str
	 *
	 * @return  bool
	 */
	public function alpha_and_numeric( $str ) {
		if ( preg_match( '#[0-9]#', $str ) && preg_match( '#[a-zA-Z]#', $str ) ) {
			return true;
		}
		$this->set_message( 'alpha_and_numeric', '{field} must contain alphabetical characters and numbers.' );

		return false;
	}

	/**
	 * city_id_integer
	 *
	 * Verify that the provided argument is a valid integer with minimum length of 1 digit and maximum length of 11 digits.
	 *
	 * @param   string $str
	 *
	 * @return  bool
	 */
	public function city_id_integer( $str ) {
		if ( $this->check_id( $str ) ) {
			return true;
		}
		$this->set_message( __METHOD__, '{field} does not contain a valid city.' );

		return false;
	}


	/**
	 * school_id_integer
	 *
	 * Verify that the provided argument is a valid integer with minimum length of 1 digit and maximum length of 11 digits.
	 *
	 * @param   string $str
	 *
	 * @return  bool
	 */
	public function school_id_integer( $str ) {
		if ( preg_match( '/^\d{1,11}$/', $str ) ) {
			return true;
		}
		$this->set_message( 'school_id_integer', '{field} does not contain a valid school.' );

		return false;
	}

	/**
	 * is_boolean
	 *
	 * Verify that the string provided contain alphabetical and numeric characters
	 *
	 * @param   string $str
	 *
	 * @return  bool
	 */
	public function is_boolean( $str ) {
		if ( in_array( $str, array( "true", "false", "1", "0", true, false ), true ) ) {
			return true;
		}
		$this->set_message( 'is_boolean', '{field} must contain a valid value.' );

		return false;
	}

	/**
	 * is_binary
	 *
	 * Verify that the string provided contain alphabetical and numeric characters
	 *
	 * @param   string $str
	 *
	 * @return  bool
	 */
	public function is_binary( $str ) {
		if ( in_array( $str, array( 1, 0, "1", "0" ), true ) ) {
			return true;
		}
		$this->set_message( 'is_binary', '{field} must contain a valid value.' );

		return false;
	}

	/**
	 * company_id_integer
	 *
	 * Verify that the provided argument is a valid integer with minimum length of 1 digit and maximum length of 11 digits.
	 *
	 * @param   string $str
	 *
	 * @return  bool
	 */
	public function company_id_integer( $str ) {
		if ( $this->check_id( $str ) ) {
			return true;
		}
		$this->set_message( __METHOD__, '{field} does not contain invalid value.' );

		return false;
	}

	/**
	 * industry_id_integer
	 *
	 * Verify that the provided argument is a valid integer with minimum length of 1 digit and maximum length of 11 digits.
	 *
	 * @param   string $str
	 *
	 * @return  bool
	 */
	public function industry_id_integer( $str ) {
		if ( $this->check_id( $str ) ) {
			return true;
		}
		$this->set_message( __METHOD__, '{field} does not contain invalid value.' );

		return false;
	}

	/**
	 * job_title_id_integer
	 *
	 * Verify that the provided argument is a valid integer with minimum length of 1 digit and maximum length of 11 digits.
	 *
	 * @param   string $str
	 *
	 * @return  bool
	 */
	public function job_title_id_integer( $str ) {
		if ( $this->check_id( $str ) ) {
			return true;
		}
		$this->set_message( __METHOD__, '{field} does not contain invalid value.' );

		return false;
	}

	public function seniority_id_integer( $str ) {
		if ( $this->check_id( $str ) ) {
			return true;
		}
		$this->set_message( __METHOD__, '{field} does not contain invalid value.' );

		return false;
	}

	/**
	 * check_id
	 *
	 * Check that the given str has a numeric value (1-11 integer length)
	 *
	 * @access    private
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	private function check_id( $str ) {
		return preg_match( '/^\d{1,11}$/', $str );
	}

	/**
	 * date_time
	 *
	 * Verify that the string provided is a valid date time
	 *
	 * @param   string $str
	 *
	 * @return  bool
	 */
	public function datetime( $str ) {
		$exp = '/^([0-9]{4})([\-])([0-9]{2})([\-])([0-9]{2})[\ ]'
		       . '([0-9]{2})[\:]([0-9]{2})[\:]([0-9]{2})$/';

		$match = array();
		if ( ! preg_match( $exp, $str, $match ) ) {
			$this->set_message( 'datetime', '{field} must be a valid datetime.' );

			return false;
		}

		return true;
	}

	/**
	 * datetime_or_null
	 *
	 * Verify that the string provided is a valid date time or a null value
	 *
	 * @param   string $str
	 *
	 * @return  bool
	 */
	public function datetime_or_null( $str ) {
		return $str === 'null' || $this->datetime( $str );
	}

	public function db_exists( $str, $field ) {
		sscanf( $field, '%[^.].%[^.]', $table, $field );

		return isset( $this->CI->db )
			? ( $this->CI->db->limit( 1 )->get_where( $table, array( $field => $str ) )->num_rows() > 0 )
			: false;
	}

	public function valid_json( $str ) {
		$result = json_decode( $str );

		return json_last_error() === JSON_ERROR_NONE;
	}
}
