<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

$config['public_path'] = base_url() . 'assets/files/';
$config['public_cv_path'] = base_url() . 'assets/cv/';

$config['upload']['upload_path']      = './assets/files/';
$config['upload']['allowed_types']    = 'gif|jpg|jpeg|png|docx|doc|pdf';
$config['upload']['max_size']         = 10000;
$config['upload']['file_ext_tolower'] = true;
$config['upload']['encrypt_name']     = false;
$config['upload']['overwrite']        = true;
$config['upload']['bucket_name']      = 'gs://findable-api.appspot.com';