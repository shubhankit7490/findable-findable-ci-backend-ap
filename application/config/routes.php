<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/*
| -------------------------------------------------------------------------
| REST API Routes
| -------------------------------------------------------------------------
*/

// Auth endpoints
$route['users/login'] = 'api/v1/user/login';
$route['users/logout'] = 'api/v1/user/logout';
$route['users/forgot'] = 'api/v1/user/forgot';
$route['users/reset'] = 'api/v1/user/reset';
$route['users/confirm'] = 'api/v1/user/confirm';

// Applicant endpoints
$route['users/me/profile'] = 'api/v1/user/personal_details';
$route['users/(:num)/about'] = 'api/v1/user/about/$1';
$route['users/(:num)/profile'] = 'api/v1/user/profile/$1';
$route['users/(:num)/profile/(:num)'] = 'api/v1/user/profile/$1/$2';
$route['users/(:num)/purches'] = 'api/v1/user/purches/$1';
$route['users/(:num)/preferences'] = 'api/v1/user/preferences/$1';
$route['users/(:num)/experience'] = 'api/v1/user/experience/$1';
$route['users/(:num)/experience/(:num)'] = 'api/v1/user/experience/$1/$2';
$route['users/(:num)/traits'] = 'api/v1/user/traits/$1';
$route['users/(:num)/languages'] = 'api/v1/user/languages/$1';
$route['users/(:num)/languages/(:num)'] = 'api/v1/user/languages/$1/$2';
$route['users/(:num)/education'] = 'api/v1/user/education/$1';
$route['users/(:num)/education/(:num)'] = 'api/v1/user/education/$1/$2';
$route['users/(:num)/statistics'] = 'api/v1/user/statistics/$1';
$route['users/(:num)/tech'] = 'api/v1/user/tech/$1';
$route['users/(:num)/tech/(:num)'] = 'api/v1/user/tech/$1/$2';
$route['users/(:num)/blocked'] = 'api/v1/user/blocked/$1';
$route['users/(:num)/blocked/(:num)'] = 'api/v1/user/blocked/$1/$2';
$route['users/(:num)/status'] = 'api/v1/user/status/$1';
$route['users/(:num)/notes'] = 'api/v1/user/notes/$1';
$route['users/(:any)/invitationEmail'] = 'api/v1/user/invitation_email/$1';
$route['users/(:num)/searches'] = 'api/v1/user/searches/$1';
$route['users/(:num)/searches/(:num)'] = 'api/v1/user/searches_profile/$1/$2';
// $route['users/(:num)/download'] = 'api/v1/user/download/$1';
$route['users/(:num)/download'] = 'api/v1/printable/download/$1';
$route['printable/(:any)/pdf'] = 'api/v1/printable/pdf/$1';
$route['users/(:num)/config'] = 'api/v1/user/config/$1';
$route['users/(:num)/faults'] = 'api/v1/user/faults/$1';
$route['users/(:num)/clean'] = 'api/v1/user/clean/$1';
$route['users/(:num)/views'] = 'api/v1/user/views/$1';
$route['users/(:num)/requests'] = 'api/v1/user/requests/$1';
$route['users/(:num)/cv/(:any)'] = 'api/v1/user/cv/$1/$2';
$route['users/(:num)/has_cv'] = 'api/v1/user/has_cv/$1';
$route['users/cv'] = 'api/v1/user/cv';
$route['users/(:num)/converturl'] = 'api/v1/user/converturl/$1';
$route['users/(:num)/subscription'] = 'api/v1/user/subscription/$1';
$route['users/uploaded_candidate'] = 'api/v1/user/uploaded_candidate';


// General purpose endpoints
$route['users'] = 'api/v1/user';
$route['locations'] = 'api/v1/locations';
$route['locations/country'] = 'api/v1/locations/countries';
$route['locations/country/(:num)'] = 'api/v1/locations/country/$1';
$route['dictionary'] = 'api/v1/dictionary';
$route['tokens'] = 'api/v1/tokens';
$route['email'] = 'api/v1/email';
$route['applicants'] = 'api/v1/applicants';
$route['users/images'] = 'api/v1/images/index/user';
$route['business/images'] = 'api/v1/images/index/business';
$route['users/signup'] = 'api/v1/user/signup';
$route['users/verify'] = 'api/v1/user/verify';

// Dictionary control routes
$route['dictionary/tech'] = 'api/v1/dictionary/tech';
$route['dictionary/tech/(:num)'] = 'api/v1/dictionary/tech/$1';
$route['dictionary/tech/(:num)/(:num)'] = 'api/v1/dictionary/tech/$1/$2';
$route['dictionary/schools'] = 'api/v1/dictionary/schools';
$route['dictionary/schools/(:num)'] = 'api/v1/dictionary/schools/$1';
$route['dictionary/schools/(:num)/(:num)'] = 'api/v1/dictionary/schools/$1/$2';
$route['dictionary/studyfields'] = 'api/v1/dictionary/studyfields';
$route['dictionary/studyfields/(:num)'] = 'api/v1/dictionary/studyfields/$1';
$route['dictionary/studyfields/(:num)/(:num)'] = 'api/v1/dictionary/studyfields/$1/$2';
$route['dictionary/focusareas'] = 'api/v1/dictionary/focusareas';
$route['dictionary/focusareas/(:num)'] = 'api/v1/dictionary/focusareas/$1';
$route['dictionary/focusareas/(:num)/(:num)'] = 'api/v1/dictionary/focusareas/$1/$2';
$route['dictionary/seniority'] = 'api/v1/dictionary/seniority';
$route['dictionary/seniority/(:num)'] = 'api/v1/dictionary/seniority/$1';
$route['dictionary/seniority/(:num)/(:num)'] = 'api/v1/dictionary/seniority/$1/$2';
$route['dictionary/jobtitle'] = 'api/v1/dictionary/jobtitle';
$route['dictionary/jobtitle/(:num)'] = 'api/v1/dictionary/jobtitle/$1';
$route['dictionary/jobtitle/(:num)/(:num)'] = 'api/v1/dictionary/jobtitle/$1/$2';
$route['dictionary/industry'] = 'api/v1/dictionary/industry';
$route['dictionary/industry/(:num)'] = 'api/v1/dictionary/industry/$1';
$route['dictionary/industry/(:num)/(:num)'] = 'api/v1/dictionary/industry/$1/$2';
$route['dictionary/company'] = 'api/v1/dictionary/company';
$route['dictionary/company/(:num)'] = 'api/v1/dictionary/company/$1';
$route['dictionary/company/(:num)/(:num)'] = 'api/v1/dictionary/company/$1/$2';
$route['dictionary/languages'] = 'api/v1/dictionary/languages';
$route['dictionary/traits'] = 'api/v1/dictionary/traits';
$route['dictionary/enums'] = 'api/v1/dictionary/enums';
$route['dictionary/business'] = 'api/v1/dictionary/business';
$route['dictionary/education_levels'] = 'api/v1/dictionary/education_levels';

// Business routes
$route['business'] = 'api/v1/business/index';
$route['business/(:num)'] = 'api/v1/business/index/$1';
$route['business/(:num)/credits'] = 'api/v1/business/credits/$1';
$route['business/(:num)/payments'] = 'api/v1/business/payments/$1';
$route['business/(:num)/payments/(:num)'] = 'api/v1/business/payments/$1/$2';
$route['business/(:num)/purchases'] = 'api/v1/business/purchases/$1';
$route['business/(:num)/updateapplicantstatus'] = 'api/v1/business/updateapplicantstatus/$1';
$route['business/(:num)/applicants'] = 'api/v1/business/applicants/$1';
$route['business/(:num)/application'] = 'api/v1/business/application/$1';
$route['business/(:num)/searches'] = 'api/v1/business/searches/$1';
$route['business/(:num)/searches/(:num)'] = 'api/v1/business/searches/$1/$2';
$route['business/(:num)/recruiters'] = 'api/v1/business/recruiters/$1';
$route['business/(:num)/recruiters/(:num)'] = 'api/v1/business/recruiters/$1/$2';
$route['business/(:num)/statistics'] = 'api/v1/business/statistics/$1';
$route['business/(:num)/results/(:num)'] = 'api/v1/business/results/$1/$2';
$route['business/(:num)/sendemail'] = 'api/v1/business/sendemail/$1';

$route['business/oauth2callback'] = 'api/v1/business/oauth2callback';
$route['business/(:num)/partner'] = 'api/v1/business/partner/$1';

// Packages
$route['package'] = 'api/v1/packages/index';
$route['package/(:num)'] = 'api/v1/packages/index/$1';

// Faults
$route['faults'] = 'api/v1/faults/index';
$route['faults/(:num)'] = 'api/v1/faults/index/$1';

// Search
$route['search/(:any)'] = 'api/v1/search/index/$1';

// Messages
$route['message'] = 'api/v1/message/index';
$route['message/(:num)'] = 'api/v1/message/index/$1';

// Logs
$route['log'] = 'api/v1/log/index';

// Platform
$route['platform/stats'] = 'api/v1/platform/stats';
$route['platform/login/(:any)/(:num)'] = 'api/v1/platform/login/$1/$2';
$route['platform/logout'] = 'api/v1/platform/logout';
$route['platform/users/(:num)'] = 'api/v1/platform/users/$1';
$route['platform/approve/(:num)'] = 'api/v1/platform/approve/$1';
$route['platform/business'] = 'api/v1/platform/business';
$route['platform/business/(:num)'] = 'api/v1/platform/business/$1';
$route['platform/dictionary'] = 'api/v1/platform/dictionary';
$route['platform/requests'] = 'api/v1/platform/requests';
$route['platform/applicants'] = 'api/v1/platform/applicants';
$route['platform/recruiter'] = 'api/v1/platform/recruiter';