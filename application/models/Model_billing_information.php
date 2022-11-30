<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_billing_information extends Base_Model
{
    public $billing_information_id = NULL;
    public $business_id = NULL;
    public $billing_name = NULL;
    public $billing_street_1 = NULL;
    public $billing_street_2 = NULL;
    public $billing_zipcode = NULL;
    public $city_id = NULL;
    public $deleted = 0;
    public $created = NULL;
}