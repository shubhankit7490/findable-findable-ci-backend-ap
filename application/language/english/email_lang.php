<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

// Account activation email
$lang['applicant_signup']['subject'] = 'Welcome to Findable';
$lang['applicant_signup']['title'] = 'Welcome to Findable';
$lang['applicant_signup']['message'] = 'Congratulation on creating your free account. <br> Findable is the best way to create and share your resume.';
$lang['applicant_signup']['action'] = 'To get started, click confirm on the link below';
$lang['applicant_signup']['button_url'] = 'https://www.findable.co/verify';
$lang['applicant_signup']['button_label'] = 'Confirm and continue';
$lang['applicant_signup']['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved. <br> You received this email because you signed up for a free Findable account';

// Instructions email for a password reset request
$lang['password_reset']['subject'] = 'Findable password reset';
$lang['password_reset']['title'] = 'Password reset';
$lang['password_reset']['message'] = "We see you need a little help with your account's password.";
$lang['password_reset']['action'] = 'To reset the password click on the following link';
$lang['password_reset']['button_url'] = 'https://www.findable.co/reset';
$lang['password_reset']['button_label'] = 'Let me in';
$lang['password_reset']['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved. <br> You received this email because you signed up for a free Findable account';

// Sharing an applicant profile email
$lang['profile_share']['subject'] = 'My Findable Profile';
$lang['profile_share']['title'] = 'Findable profile';
$lang['profile_share']['message'] = "I would like you to see my Findable profile";
$lang['profile_share']['action'] = 'To check my profile click on the following link';
$lang['profile_share']['button_url'] = 'https://www.findable.co/user';
$lang['profile_share']['button_label'] = 'Check my profile';
$lang['profile_share']['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved.';

// Notification email for a new recruiter (for a user which already has a findable account)
$lang['add_recruiter']['subject'] = 'Findable invitation';
$lang['add_recruiter']['title'] = 'Findable invitation';
$lang['add_recruiter']['message'] = "I would like to add you to my account";
$lang['add_recruiter']['action'] = 'To start recruiting applicants click on the following link';
$lang['add_recruiter']['button_url'] = 'https://www.findable.co';
$lang['add_recruiter']['button_label'] = 'Start recruiting';
$lang['add_recruiter']['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved. <br> You received this email because you signed up for a free Findable account';

// Inivitation email for a new recruiter (for a user which does not exists in the platform)
$lang['invite_recruiter']['subject'] = 'Findable invitation';
$lang['invite_recruiter']['title'] = 'Findable invitation';
$lang['invite_recruiter']['message'] = "I would like to add you to my account";
$lang['invite_recruiter']['action'] = 'To start recruiting applicants click on the following link';
$lang['invite_recruiter']['button_url'] = 'https://www.findable.co/user/signup';
$lang['invite_recruiter']['button_label'] = 'Start recruiting';
$lang['invite_recruiter']['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved.';

// Notification email about a help request message from a platform user
$lang['help_message']['subject'] = 'Help needed';
$lang['help_message']['title'] = 'Help needed';
$lang['help_message']['message'] = "A findable user needs help: <br>";
$lang['help_message']['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved. <br> You received this email because you signed up for a free Findable account';

// Applicant contact request message
$lang['contact_request']['subject'] = 'Hi, you have a new contact request on Findable.';
$lang['contact_request']['title'] = 'Findable Contact Request';
$lang['contact_request']['message'] = "You have a new contact request on Findable";
$lang['contact_request']['action'] = 'To update your public resume go to your dashboard';
$lang['contact_request']['button_url'] = 'https://www.findable.co/dashboard';
$lang['contact_request']['button_label'] = 'Open My Dashboard';
$lang['contact_request']['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved.';

$lang['account_acctive']['subject'] = 'Findable Account Activated';
$lang['account_acctive']['message'] = "Congratulation your accout is approved<br>Please click on this link to start using the platform. We wish you good luck and success !! ";
$lang['account_acctive']['footer'] = '<span style="color:#00f3cf">© Findable</span>. All rights reserved';