<?php

class Rbac {
	var $route;
	var $directory;
	var $class;
	var $method;

	public $rest = null;

	public $user_has_permission = false;

	public static $crud = array(
		'post'   => 'create',
		'get'    => 'read',
		'put'    => 'update',
		'delete' => 'delete'
	);

	public static $public_access = array(
		'user/login'           => array( 'create' ),
		'user/signup'          => array( 'create' ),
		'user/verify'          => array( 'read' ),
		'user/forgot'          => array( 'create' ),
		'user/reset'           => array( 'create' ),
		'images/index'         => array( 'read' ),
		'user/profile'         => array( 'read' ),
		'user/traits'          => array( 'read' ),
		'user/education'       => array( 'read' ),
		'user/experience'      => array( 'read' ),
		'user/converturl'       => array( 'read' ),
		'user/uploaded_candidate'       => array( 'read' ),
		'user/purches'         => array( 'read' ),
		'user/languages'       => array( 'read' ),
		'user/tech'            => array( 'read' ),
		'user/preferences'     => array( 'read' ),
		'user/views'           => array( 'create' ),
		'user/requests'        => array( 'create' ),
		'user/invitation_email' => array( 'read' ),
	    'printable/download'    => array( 'create' ),
	    'printable/pdf' 		=> array('read'),
	);

	// Define the restricted endpoints and operations for each role
	public static $permissions = array(
		'applicant' => array(
			'user/about'                  => array( 'create', 'read', 'update' ),
			'user/profile'                => array( 'read', 'update' ),
			'user/converturl'              => array( 'read', 'update' ),
			'user/uploaded_candidate'              => array( 'read', 'update' ),
			'user/purches'              => array( 'read', 'update' ),
			'user/confirm'                => array( 'create' ),
			'user/personal_details'       => array( 'read', 'update' ),
			'user/preferences'            => array( 'read', 'update' ),
			'user/experience'             => array( 'read', 'create', 'update', 'delete' ),
			'user/traits'                 => array( 'read', 'create', 'update' ),
			'user/languages'              => array( 'read', 'create', 'update', 'delete' ),
			'user/education'              => array( 'read', 'create', 'update', 'delete' ),
			'user/statistics'             => array( 'read' ),
			'user/tech'                   => array( 'read', 'create', 'update', 'delete' ),
			'user/blocked'                => array( 'read', 'create', 'update', 'delete' ),
			'user/download'               => array( 'create' ),
			'user/clean'                  => array( 'create', 'delete' ),
			'user/cv'					  => array( 'create', 'read' ),
			'user/has_cv' 				  => array( 'read' ),
			'locations/index'             => array( 'read' ),
			'locations/country'           => array( 'read' ),
			'locations/countries'         => array( 'read' ),
			'email/index'                 => array( 'create' ),
			'tokens/index'                => array( 'create' ),
			'dictionary/index'            => array( 'create' ),
			'dictionary/languages'        => array( 'read' ),
			'dictionary/traits'           => array( 'read' ),
			'dictionary/tech'             => array( 'read', 'create' ),
			'dictionary/schools'          => array( 'read', 'create' ),
			'dictionary/studyfields'      => array( 'read', 'create' ),
			'dictionary/focusareas'       => array( 'read', 'create' ),
			'dictionary/seniority'        => array( 'read', 'create' ),
			'dictionary/jobtitle'         => array( 'read', 'create' ),
			'dictionary/industry'         => array( 'read', 'create' ),
			'dictionary/company'          => array( 'read', 'create' ),
			'dictionary/enums'            => array( 'read' ),
			'dictionary/business'         => array( 'read' ),
			'dictionary/education_levels' => array( 'read' ),
			'images/index'                => array( 'read', 'create' ),
			'message/index'               => array( 'create' ),
			'business/application'        => array( 'create' ),
			'log/index'                   => array( 'create' ),
			'printable/download' 		  => array( 'create' ),
			'printable/pdf' 			  			=> array( 'read' ),
			'user/subscription' 					=> array( 'read', 'create', 'update', 'delete' )
		),
		'recruiter' => array(
			'user/about'                  => array( 'create', 'read', 'update' ),
			'user/profile'                => array( 'read', 'update'  ),
			'user/converturl'              => array( 'read'),
			'user/uploaded_candidate'     => array( 'read'),
			'user/purches'              	=> array( 'read', 'update' ),
			'user/confirm'                => array( 'create' ),
			'user/personal_details'       => array( 'read', 'update' ),
			'user/preferences'            => array( 'read', 'update' ),
			'user/experience'             => array( 'read', 'create', 'update', 'delete' ),
			'user/traits'                 => array( 'read', 'create', 'update' ),
			'user/languages'              => array( 'read', 'create', 'update', 'delete' ),
			'user/education'              => array( 'read', 'create', 'update', 'delete' ),
			'user/tech'                   => array( 'read', 'create', 'update', 'delete' ),
			'user/statistics'             => array( 'create' ),
			'user/blocked'                => array( 'read', 'create', 'update', 'delete' ),
			'user/status'                 => array( 'update' ),
			'user/notes'                  => array( 'read', 'update' ),
			'user/searches'               => array( 'read', 'create' ),
			'user/searches_profile'       => array( 'read', 'delete' ),
			'user/download'               => array( 'create' ),
			'user/config'                 => array( 'update' ),
			'user/faults'                 => array( 'create' ),
			'user/clean'                  => array( 'create', 'delete' ),
			'locations/index'             => array( 'read' ),
			'locations/country'           => array( 'read' ),
			'locations/countries'         => array( 'read' ),
			'email/index'                 => array( 'create' ),
			'tokens/index'                => array( 'create' ),
			'dictionary/index'            => array( 'create' ),
			'dictionary/languages'        => array( 'read' ),
			'dictionary/traits'           => array( 'read' ),
			'dictionary/tech'             => array( 'read', 'create' ),
			'dictionary/schools'          => array( 'read', 'create' ),
			'dictionary/studyfields'      => array( 'read', 'create' ),
			'dictionary/focusareas'       => array( 'read', 'create' ),
			'dictionary/seniority'        => array( 'read', 'create' ),
			'dictionary/jobtitle'         => array( 'read', 'create' ),
			'dictionary/industry'         => array( 'read', 'create' ),
			'dictionary/company'          => array( 'read', 'create' ),
			'dictionary/enums'            => array( 'read' ),
			'dictionary/business'         => array( 'read' ),
			'dictionary/education_levels' => array( 'read' ),
			'images/index'                => array( 'read', 'create' ),
			'message/index'               => array( 'create' ),
			'applicants/index'            => array( 'create' ),
			'business/purchases'          => array( 'create' ),
			'business/updateapplicantstatus' => array( 'read', 'create', 'update', 'delete' ),
			'business/partner'            => array( 'read', 'create', 'update', 'delete' ),
			'business/applicants'         => array( 'create' ),
			'business/application'        => array( 'create' ),
			'business/statistics'         => array( 'read' ),
			'search/index'                => array( 'read' ),
			'log/index'                   => array( 'create' ),
			'user/subscription' 					=> array( 'read'),

			'business/index'              => array( 'read', 'create', 'update' ),
			'packages/index'              => array( 'read' ),
			'business/credits'            => array( 'read', 'create', 'update' ),
			'user/cv'					  => array( 'create', 'read' ),
			'business/sendemail'              => array( 'read', 'create', 'update' ),
			'business/oauth2callback'              => array( 'read', 'create', 'update' ),
		),
		'manager'   => array(
			'locations/index'             => array( 'read' ),
			'locations/country'           => array( 'read' ),
			'locations/countries'         => array( 'read' ),
			'tokens/index'                => array( 'create' ),
			'email/index'                 => array( 'create' ),
			'user/status'                 => array( 'update' ),
			'user/confirm'                => array( 'create' ),
			'user/notes'                  => array( 'read', 'update' ),
			'user/searches'               => array( 'read', 'create' ),
			'user/searches_profile'       => array( 'read', 'delete' ),
			'user/about'                  => array( 'create', 'read', 'update' ),
			'user/profile'                => array( 'read', 'update' ),
			'user/converturl'              => array( 'read', 'update' ),
			'user/uploaded_candidate'      => array( 'read', 'update' ),
			'user/purches'              	=> array( 'read', 'update' ),
			'user/personal_details'       => array( 'read', 'update' ),
			'user/preferences'            => array( 'read', 'update' ),
			'user/experience'             => array( 'read', 'create', 'update', 'delete' ),
			'user/traits'                 => array( 'read', 'create', 'update' ),
			'user/languages'              => array( 'read', 'create', 'update', 'delete' ),
			'user/education'              => array( 'read', 'create', 'update', 'delete' ),
			'user/statistics'             => array( 'read', 'create' ),
			'user/tech'                   => array( 'read', 'create', 'update', 'delete' ),
			'user/blocked'                => array( 'read', 'create', 'update', 'delete' ),
			'user/download'               => array( 'create' ),
			'user/cv'					  => array( 'create', 'read' ),
			'user/config'                 => array( 'update' ),
			'user/faults'                 => array( 'create' ),
			'user/clean'                  => array( 'create', 'delete' ),
			'dictionary/index'            => array( 'create' ),
			'dictionary/languages'        => array( 'read' ),
			'dictionary/traits'           => array( 'read' ),
			'dictionary/tech'             => array( 'read', 'create' ),
			'dictionary/schools'          => array( 'read', 'create' ),
			'dictionary/studyfields'      => array( 'read', 'create' ),
			'dictionary/focusareas'       => array( 'read', 'create' ),
			'dictionary/seniority'        => array( 'read', 'create' ),
			'dictionary/jobtitle'         => array( 'read', 'create' ),
			'dictionary/industry'         => array( 'read', 'create' ),
			'dictionary/company'          => array( 'read', 'create' ),
			'dictionary/enums'            => array( 'read' ),
			'dictionary/business'         => array( 'read' ),
			'dictionary/education_levels' => array( 'read' ),
			'business/index'              => array( 'read', 'create', 'update' ),
			'packages/index'              => array( 'read' ),
			'business/credits'            => array( 'read', 'create', 'update' ),
			'business/payments'           => array( 'read', 'update' ),
			'business/purchases'          => array( 'read', 'create' ),
			'business/updateapplicantstatus'  => array( 'read', 'create', 'update', 'delete' ),
			'business/searches'           => array( 'read', 'update' ),
			'business/results'            => array( 'read' ),
			'business/statistics'         => array( 'read' ),
			'applicants/index'            => array( 'create' ),
			'images/index'                => array( 'read', 'create' ),
			'message/index'               => array( 'create' ),
			'business/applicants'         => array( 'create' ),
			'business/application'        => array( 'create' ),
			'business/recruiters'         => array( 'read', 'create', 'update', 'delete' ),
			'search/index'                => array( 'read' ),
			'log/index'                   => array( 'create' ),
			'user/subscription' 					=> array( 'read'),
			'business/sendemail'              => array( 'read', 'create', 'update' ),
			'business/oauth2callback'              => array( 'read', 'create', 'update' ),
			'business/partner'            => array( 'read', 'create', 'update', 'delete' ),
		),
		'admin'     => array(
			'user/index'                  => array( 'read' ),
			'user/personal_details'       => array( 'read', 'update' ),
			'user/about'                  => array( 'create', 'read', 'update' ),
			'user/profile'                => array( 'read', 'update' ),
			'user/converturl'             => array( 'read', 'update' ),
			'user/uploaded_candidate'     => array( 'read', 'update' ),
			'user/purches'              => array( 'read', 'update' ),
			'user/status'                 => array( 'update' ),
			'user/notes'                  => array( 'read', 'update' ),
			'user/searches'               => array( 'read', 'create' ),
			'user/tech'                   => array( 'read', 'create', 'update', 'delete' ),
			'user/searches_profile'       => array( 'read', 'delete' ),
			'user/blocked'                => array( 'read', 'create', 'update', 'delete' ),
			'user/download'               => array( 'create' ),
			'user/config'                 => array( 'update' ),
			'user/faults'                 => array( 'create' ),
			'user/clean'                  => array( 'create', 'delete' ),
			'user/statistics'             => array( 'read' ),
			'user/cv'					  					=> array( 'create', 'read' ),
			'user/has_cv' 				  			=> array( 'read' ),
			'locations/index'             => array( 'read' ),
			'locations/country'           => array( 'read' ),
			'locations/countries'         => array( 'read' ),
			'tokens/index'                => array( 'read', 'create' ),
			'email/index'                 => array( 'create' ),
			'dictionary/index'            => array( 'create' ),
			'dictionary/languages'        => array( 'read' ),
			'dictionary/traits'           => array( 'read' ),
			'dictionary/tech'             => array( 'read', 'create', 'update', 'delete' ),
			'dictionary/schools'          => array( 'read', 'create', 'update', 'delete' ),
			'dictionary/studyfields'      => array( 'read', 'create', 'update', 'delete' ),
			'dictionary/focusareas'       => array( 'read', 'create', 'update', 'delete' ),
			'dictionary/seniority'        => array( 'read', 'create', 'update', 'delete' ),
			'dictionary/jobtitle'         => array( 'read', 'create', 'update', 'delete' ),
			'dictionary/industry'         => array( 'read', 'create', 'update', 'delete' ),
			'dictionary/company'          => array( 'read', 'create', 'update', 'delete' ),
			'dictionary/enums'            => array( 'read' ),
			'dictionary/business'         => array( 'read' ),
			'dictionary/education_levels' => array( 'read' ),
			'business/index'              => array( 'read', 'create', 'update' ),
			'packages/index'              => array( 'read', 'update' ),
			'business/credits'            => array( 'read', 'create', 'update' ),
			'business/payments'           => array( 'read', 'update' ),
			'business/purchases'          => array( 'read', 'create' ),
			'business/updateapplicantstatus'  => array( 'read', 'create', 'update', 'delete' ),
			'business/partner'            => array( 'read', 'create', 'update', 'delete' ),
			'business/searches'           => array( 'read', 'update' ),
			'business/results'            => array( 'read' ),
			'business/statistics'         => array( 'read' ),
			'business/recruiters'         => array( 'read', 'create', 'update', 'delete' ),
			'applicants/index'            => array( 'read', 'create' ),
			'message/index'               => array( 'read', 'create', 'delete' ),
			'faults/index'                => array( 'read', 'delete' ),
			'search/index'                => array( 'read' ),
			'platform/stats'              => array( 'read' ),
			'platform/login'              => array( 'create' ),
			'platform/logout'             => array( 'create' ),
			'platform/users'              => array( 'create', 'update', 'delete' ),
			'platform/approve'            => array( 'create', 'update', 'delete' ),
			'platform/business'           => array( 'read', 'create', 'update', 'delete' ),
			'platform/dictionary'         => array( 'read', 'create', 'update' ),
			'platform/requests'           => array( 'read' ),
			'platform/applicants'         => array( 'read' ),
			'platform/recruiter'         => array( 'read' ),
			'log/index'                   => array( 'create' ),
			'printable/download' 		  		=> array('create'),
			'printable/pdf' 			 				=> array('read'),
			'user/subscription' 					=> array( 'read', 'create', 'update', 'delete' )
		)
	);

	/**
	 * __construct
	 *
	 * @access    public
	 *
	 * @return    RBAC instance
	 */
	function __construct() {
		$this->route     =& load_class( 'Router' );
		$this->directory = substr( $this->route->fetch_directory(), 0, - 1 );
		$this->class     = $this->route->fetch_class();
		$this->method    = $this->route->fetch_method();
		$this->request   = $this->input->method( false );
	}

	/**
	 * __get
	 *
	 * Enables the use of CI super-global without having to define an extra variable.
	 *
	 * @access    public
	 *
	 * @param    $var
	 *
	 * @return    mixed
	 */
	public function __get( $var ) {
		return get_instance()->$var;
	}

	/**
	 * is_allowed
	 *
	 * Check the role's permission to access endpoints and perform CRUD operations
	 *
	 * @access    public
	 *
	 * @return    boolean
	 */
	public function has_permission() {
		$valid = false;

		// Public access endpoints
		if ( in_array( strtolower( $this->class . '/' . $this->method ), array_keys( self::$public_access ) ) &&
		     in_array( self::$crud[ $this->request ], self::$public_access[ strtolower( $this->class . '/' . $this->method ) ] )
		) {
			$valid = true;
		} else if ( isset( $this->rest->role ) && in_array( strtolower( $this->class . '/' . $this->method ), array_keys( self::$permissions[ $this->rest->role ] ) ) &&
		            in_array( self::$crud[ $this->request ], self::$permissions[ $this->rest->role ][ strtolower( $this->class . '/' . $this->method ) ] )
		) {
			// Valid role access permission and CRUD operation for the requested endpoint
			$valid = true;
		}

		$this->user_has_permission = $valid;
	}
}

?>