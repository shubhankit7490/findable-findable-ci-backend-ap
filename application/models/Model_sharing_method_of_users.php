<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_sharing_method_of_users extends Base_Model
{
	public $sharing_method_of_user_id = NULL;
	public $user_id = NULL;
	public $sharing_email = NULL;
	public $sharing_subject = NULL;
	public $sharing_message = NULL;
	public $deleted = 0;
	public $created = NULL;
}
