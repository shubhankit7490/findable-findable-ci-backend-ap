<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
    {
    	//echo 'We are on <b>'. ENVIRONMENT . '</b> environment<br>';

//	    $this->config->load('upload');
//	    $upload_confid = $this->config->item('upload');
//
//	    echo 'Uploading to: <br>';
//	    echo $upload_confid['bucket_name'] . '<br><br>';
//
//	    $write = file_put_contents($upload_confid['bucket_name'] . '/user_files/test.txt', 'This is a test');
//
//	    echo 'Upload result: <br>';
//	    var_dump($write);
//
//	    echo '<br><br>';
//
//	    echo 'Upload class: <br>';
//	    var_dump(CloudStorageTools);



    // $this->config->load('stripe');
    // $key = $this->config->item('stripe');

    /*
    \Stripe\Stripe::setApiKey($key['secret_key']);
    $myCard = array('number' => '4242424242424242', 'exp_month' => 8, 'exp_year' => 2018);
    $charge = \Stripe\Charge::create(array('card' => $myCard, 'amount' => 2000, 'currency' => 'usd'));
    echo $charge;
    */

    /*
    $mg = new \Mailgun\Mailgun('key-ba308f8896aa3f52dd52682b8f276bd6', null, 'bin.mailgun.net');
    $mg->setApiVersion('e991878b');
    $mg->setSslEnabled(false);
    $domain = 'example.com';

    # Now, compose and send your message.
    $mg->sendMessage($domain, array(
      'from'    => 'bob@example.com',
      'to'      => 'sally@example.com',
      'subject' => 'The PHP SDK is awesome!',
      'text'    => 'It is so simple to send a message.')
    );
    */
	}
}
