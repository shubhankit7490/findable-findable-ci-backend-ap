<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_business_user_views extends Base_Model {
	public $business_user_view_id = null;
	public $business_id = null;
	public $user_id = null;
	public $continent_id = null;
	public $view_count = 0;
	public $updated = null;
	public $created = null;

	protected $tbl = null;

	protected $continents = array(
		'northamerica' => array(
			'count'      => 0,
			'views'      => 0,
			'businesses' => array()
		),
		'southamerica' => array(
			'count'      => 0,
			'views'      => 0,
			'businesses' => array()
		),
		'asia'         => array(
			'count'      => 0,
			'views'      => 0,
			'businesses' => array()
		),
		'africa'       => array(
			'count'      => 0,
			'views'      => 0,
			'businesses' => array()
		),
		'oceania'      => array(
			'count'      => 0,
			'views'      => 0,
			'businesses' => array()
		),
		'europe'       => array(
			'count'      => 0,
			'views'      => 0,
			'businesses' => array()
		),
		'antarctica'   => array(
			'count'      => 0,
			'views'      => 0,
			'businesses' => array()
		)
	);

	/**
	 * create
	 *
	 * Creates a new view record for the current day
	 *
	 * @access    public
	 *
	 * @return    integer
	 */
	public function create() {
		$this->view_count = 1;
		unset( $this->updated );
		unset( $this->created );
		$this->db->insert( $this->tbl_business_user_views, $this );
		$this->business_user_view_id = $this->db->insert_id();
	}

	/**
	 * check_view_today
	 *
	 * Get number of views of a user profile by a certain business since the start of the current day
	 *
	 * @access    public
	 *
	 * @return    integer
	 */
	public function check_view_today() {
		$this->db->select( 'COUNT(DISTINCT business_user_view_id) as view_count' );
		$this->db->from( $this->tbl_business_user_views );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'created > DATE_SUB(now(), INTERVAL 1 DAY)', null, true );

		$query = $this->db->get();

		return $query->row()->view_count;
	}

	/**
	 * get_views_count
	 *
	 * Get number of views of a user profile
	 *
	 * @access    public
	 *
	 * @param $since
	 *
	 * @return    integer
	 */
	public function get_views_count_by_business( $since = null ) {
		$this->db->select( 'COUNT(DISTINCT business_user_view_id) as view_count' );
		$this->db->from( $this->tbl_business_user_views );
		$this->db->where( 'user_id', $this->user_id );

		if ( is_int( $since ) ) {
			$this->db->where( 'created > DATE_SUB(now(), INTERVAL ' . $since . ' DAY)', null, false );
		}

		$query = $this->db->get();

		return $query->row()->view_count;
	}

	/**
	 * get_views_count
	 *
	 * Get number of views of a user profile
	 *
	 * @access    public
	 *
	 * @param $since
	 *
	 * @return    integer
	 */
	public function get_views_count( $since = null ) {
		$this->db->select( 'COALESCE(SUM(`view_count`),0) as view_count' );
		$this->db->from( $this->tbl_business_user_views );
		$this->db->where( 'user_id', $this->user_id );

		if ( is_int( $since ) ) {
			$this->db->where( 'created > DATE_SUB(now(), INTERVAL ' . $since . ' DAY)', null, false );
		}

		$query = $this->db->get();

		return $query->row()->view_count;
	}

	/**
	 * get_views_by_continents
	 *
	 * Get number of views of a user profile by continents
	 *
	 * @access    public
	 *
	 * @param $since
	 *
	 * @return    integer
	 */
	public function get_views_by_continents( $since = null ) {
		// Cast the continents to object
		$this->continents = (object) $this->continents;

		// Cast the 1st level members of the continent object to objects
		array_walk( $this->continents, function ( &$item, $key ) {
			$item = (object) $item;
		} );

		$fields = array(
			'LOWER(continent_name) as continent',
			$this->tbl_business_user_views . '.view_count as count',
			$this->tbl_business . '.business_name as name',
			$this->tbl_files . '.file_url as logo',
			$this->tbl_business_user_views . '.continent_id as continent_id',
		);

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_business_user_views );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_business_user_views . '.continent_id' );
		$this->db->join( $this->tbl_business, $this->tbl_business . '.business_id = ' . $this->tbl_business_user_views . '.business_id' );
		$this->db->join( $this->tbl_files, $this->tbl_files . '.file_id = ' . $this->tbl_business . '.business_logo', 'left' );
		$this->db->where( 'user_id', $this->user_id );

		if ( is_int( $since ) ) {
			$this->db->where( 'created > DATE_SUB(now(), INTERVAL ' . $since . ' DAY)', null, false );
		}

		$query = $this->db->get();

		// Get the view count
		$view_fields = array(
			'LOWER(continent_name) as continent',
			$this->tbl_users_views . '.continent_id as continent_id',
			$this->tbl_users_views . '.views as views',
			$this->tbl_continents . '.continent_name as continent_name'
		);

		$select = implode( ',', $view_fields );
		$this->db->select( $select );
		$this->db->from( $this->tbl_users_views );
		$this->db->join( $this->tbl_continents, $this->tbl_continents . '.continent_id = ' . $this->tbl_users_views . '.continent_id' );
		$this->db->group_by( $this->tbl_users_views . '.continent_id' );
		$this->db->where( $this->tbl_users_views . '.user_id', $this->user_id );

		if ( is_int( $since ) ) {
			$this->db->where( $this->tbl_users_views . '.created > DATE_SUB(now(), INTERVAL ' . $since . ' DAY)', null, false );
		}

		$view_query = $this->db->get();

		foreach( $view_query->result() as $view_row ) {
			$this->continents->{preg_replace( '/\s+/', '', $view_row->continent )}->views += $view_row->views;
		}

		foreach ( $query->result() as $row ) {
			$this->continents->{preg_replace( '/\s+/', '', $row->continent )}->count += $row->count;

			// Searching for similar businesses
			$neededObjects = array_filter(
				$this->continents->{preg_replace( '/\s+/', '', $row->continent )}->businesses,
				function ( $e ) use ( &$row ) {
					return $e->name == $row->name;
				}
			);

			// Adding only unique businesses
			if ( ! count( $neededObjects ) ) {
				$this->continents->{preg_replace( '/\s+/', '', $row->continent )}->businesses[] = (object) array(
					'name' => $row->name,
					'logo' => is_null( $row->logo ) ? null : $this->upload->serve( $row->logo, [ 'secure_url' => true ] )
				);
			}
		}

		return $this->continents;
	}

	/**
	 * increase
	 *
	 * Increase the latest view_count period by one (of the current business / applicant)
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    string
	 */
	private function increase() {
		$this->db->set( 'view_count', 'view_count+1', false );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'created > DATE_SUB(now(), INTERVAL 1 DAY)', null, false );
		$this->db->order_by( 'business_user_view_id', 'DESC' );
		$this->db->limit( 1 );

		$this->db->update( $this->tbl_business_user_views );
	}

	/**
	 * add
	 *
	 * Add a view count record (creates one if it's the first today or update view_count if present today)
	 *
	 * @access    public
	 *
	 * @return    null
	 */
	public function add() {
		if ( $this->check_view_today() > 0 ) {
			$this->increase();
		} else {
			$this->create();
		}
	}
}