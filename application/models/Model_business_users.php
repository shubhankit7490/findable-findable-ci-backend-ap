<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_business_users extends Base_Model {
	public $business_user_id = null;
	public $user_id = null;
	public $business_id = null;
	public $business_user_job_title = null;
	public $purchase_permission = 0;
	public $business_admin = 0;
	public $deleted = 0;
	public $created = null;

	public function get_model() {
		return array(
			$this->tbl_business_users . '.user_id as id',
			$this->tbl_users . '.email as email',
			$this->tbl_users . '.status as status',
			$this->tbl_job_title . '.job_title_name as jobtitle',
			$this->tbl_business_users . '.purchase_permission as purchase_permission'
		);
	}

	public function get_multi_business_model() {
		return array(
			$this->tbl_business_users . '.business_id as id',
			$this->tbl_business . '.business_name as name'
		);
	}

	public function get() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_business_users );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'business_admin', $this->business_admin );
		$this->db->where( 'deleted', $this->deleted );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	public function get_recruiter_businesses() {
		$fields = $this->get_multi_business_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_business_users );
		$this->db->join( $this->tbl_business, $this->tbl_business . '.business_id = ' . $this->tbl_business_users . '.business_id' );
		$this->db->where( $this->tbl_business_users . '.user_id', $this->user_id );
		$this->db->where( $this->tbl_business_users . '.business_admin', $this->business_admin );
		$this->db->where( $this->tbl_business_users . '.deleted', $this->deleted );
		$query = $this->db->get();

		return $query->result();
	}

	public function get_business_user() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_business_users );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'business_admin', $this->business_admin );
		$this->db->where( 'deleted', $this->deleted );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	public function get_any_business_user() {
		$this->db->select( '*' );
		$this->db->from( $this->tbl_business_users );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'deleted', $this->deleted );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			$this->merge( $query->row() );

			return true;
		}

		return false;
	}

	/**
	 * update_job_title_id
	 *
	 * Update the job title id to all the users to a new job title id
	 *
	 * @access    public
	 *
	 * @role    admin
	 *
	 * @return    null
	 */
	public function update_job_title_id( $new_id ) {
		$this->db->set( 'business_user_job_title', $new_id );
		$this->db->where( 'business_user_job_title', $this->business_user_job_title );
		$this->db->update( $this->tbl_business_users );
	}

	/**
	 * create
	 *
	 * Insert new business user or un delete him if he is marked as deleted
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    Object
	 */
	public function create() {
		$sql = 'INSERT INTO ' . $this->tbl_business_users . ' (user_id, business_id, business_user_job_title, purchase_permission, business_admin) VALUES ';
		$sql .= '(' . $this->user_id . ',' . $this->business_id . ', ' . $this->db->escape( $this->business_user_job_title ) . ', ' . $this->purchase_permission . ', ' . $this->business_admin . ')';
		$sql .= ' ON DUPLICATE KEY UPDATE deleted=0';

		$this->db->query( $sql );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * associate
	 *
	 * associate the user to the requested invites
	 *
	 * @access    public
	 *
	 * @param array $invites
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function associate( $invites = [] ) {
		if ( count( $invites ) ) {
			$sql = 'INSERT INTO ' . $this->tbl_business_users . ' (user_id, business_id, business_user_job_title, purchase_permission) VALUES ';
			foreach ( $invites as $invite ) {
				$sql .= '(' . $this->user_id . ', ' . $invite->business_id . ', ' . $this->db->escape( $invite->job_title_id ) . ', ' . $invite->purchase_permission . '),';
			}
			$sql = rtrim( $sql, "," );
			$sql .= ' ON DUPLICATE KEY UPDATE deleted=0';

			$this->db->query( $sql );
		}
	}

	/**
	 * get_recruiters
	 *
	 * Get a collection of a business recruiters
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function get_recruiters() {
		$fields = $this->get_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->tbl_business_users );
		$this->db->join( $this->tbl_users, $this->tbl_users . '.user_id = ' . $this->tbl_business_users . '.user_id' );
		$this->db->join( $this->tbl_job_title, $this->tbl_job_title . '.job_title_id = ' . $this->tbl_business_users . '.business_user_job_title', 'left' );
		$this->db->where( $this->tbl_business_users . '.business_id', $this->business_id );
		$this->db->where( $this->tbl_business_users . '.deleted', $this->deleted );
		$this->db->where_in( $this->tbl_users . '.status', array( 'active', 'pending' ) );

		$query = $this->db->get();

		return $query->result();
	}

	/**
	 * update_purchase_permission
	 *
	 * Update the purchase permission of a recruiter
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    boolean
	 */
	public function update_purchase_permission() {
		$this->db->set( 'purchase_permission', $this->purchase_permission );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'user_id', $this->user_id );
		$this->db->where( 'business_admin', $this->business_admin );
		$this->db->where( 'deleted', $this->deleted );

		$this->db->update( $this->tbl_business_users );

		return $this->db->affected_rows() > 0;
	}

	/**
	 * soft_delete
	 *
	 * Soft delete a recruiter (deleted = 1)
	 *
	 * @access    public
	 *
	 * @role    manager, admin
	 *
	 * @return    boolean
	 */
	public function soft_delete() {
		$this->db->set( 'deleted', 1 );
		$this->db->where( 'business_id', $this->business_id );
		/*$this->db->where( 'user_id', $this->user_id );*/
		$this->db->where( 'deleted', $this->deleted );
		$this->db->update( $this->tbl_business_users );

		return $this->db->affected_rows() > 0;
	}

		/**
	 * get_my_associate_user
	 *
	 * Add the model to the database
	 *
	 * @access    public
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    null
	 */
	public function get_my_associate_user() {
		$this->db->select( 'user_id' );
		$this->db->from( $this->tbl_business_users );
		$this->db->where( 'business_id', $this->business_id );
		$this->db->where( 'deleted',0 );
		$query = $this->db->get();

		if ( $query->num_rows() > 0 ) {
			return $query->result();
		}

		return [];
	}
}