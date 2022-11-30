<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_token extends Base_Model
{
	public $token_id = NULL;
	public $user_id = NULL;
	public $token = NULL;
	public $type = NULL;
	public $verified = 0;
	public $deleted = 0;
	public $updated = NULL;
	public $created = NULL;

	/**
     * Get verification data
     *
     * @param   mixed $by
     * @return  bool
     */
	public function get_verification($where = array('verification_id', 'deleted'))
	{
		$where = $this->create_where_assoc($where);

		$this->db->where($where);
		$this->db->limit(1);

		$query = $this->db->get($this->tbl_verifications);

		if($query->num_rows() < 1)
			return FALSE;

		$this->merge($query->row());

		return TRUE;
	}

	/**
	 * Get the verification data by token
	 *
	 * @return	bool
	 */
	public function get_by_token()
	{
		return $this->get_verification(['verification_token', 'deleted']);
	}

	/**
	 * Create new verification
	 *
	 * @return	bool
	 */
	public function create()
	{
		$this->db->insert($this->tbl_verifications, $this);

		if($this->db->affected_rows() < 1)
			return FALSE;

		return TRUE;
	}
}