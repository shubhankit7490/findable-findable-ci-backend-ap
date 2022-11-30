<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_towns extends Base_Model
{
	public $town_id = NULL;
	public $town_name = NULL;
	public $country_id = NULL;
	public $state_id = NULL;
	public $deleted = 0;
	public $created = NULL;
}
