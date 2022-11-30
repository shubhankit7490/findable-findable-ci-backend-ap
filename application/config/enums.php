<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

$config['enums'] = (object) array(
	'available_from'          => array(
		'immediately',
		'one week',
		'two weeks',
		'one month',
		'from'
	),
	'employment_status'       => array(
		'employed full time',
		'employed part time',
		'unemployed'
	),
	'current_status'          => array(
		'actively looking',
		'interested in offers',
		'interested',
		'not looking'
	),
	'employment_type'         => array(
		'full time',
		'part time'
	),
	'token_type'              => array(
		'email',
		'activation',
		'reset'
	),
	'applicant_status'        => array(
		'short',
		'interviewing',
		'initial',
		'hired'
	),
	'roles'                   => array(
		'applicant',
		'recruiter',
		'manager'
	)
);