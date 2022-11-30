<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['stripe']['key_test_public'] = 'pk_test_7JXJfK3INsGQVTnDZfvNKDnS';
$config['stripe']['key_test_secret'] = 'sk_test_9SCEfJwW9ZkJxsWSyJlrNFdb';
$config['stripe']['key_live_public'] = 'pk_live_lPoUw6pMVBTxCpidmErCalmQ';
$config['stripe']['key_live_secret'] = 'sk_live_ViOwaZUTodIA89roLj7OFQG4';

$config['stripe']['stripe_test_mode'] = (ENVIRONMENT !== 'production');
$config['stripe']['stripe_verify_ssl'] = FALSE;

$config['stripe']['public_key'] = ($config['stripe']['stripe_test_mode'] ? $config['stripe']['key_test_public'] : $config['stripe']['key_live_public']);
$config['stripe']['secret_key'] = ($config['stripe']['stripe_test_mode'] ? $config['stripe']['key_test_secret'] : $config['stripe']['key_live_secret']);