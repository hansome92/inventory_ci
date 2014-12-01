<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


$active_group = 'default';
$active_record = TRUE;



if (SITE_WEB == 'dev') {
	$db['default']['username'] = 'fabianacosta';
	$db['default']['password'] = 'v3HJv4pL';
	$db['default']['database'] = 'fabianacosta';
} elseif(SITE_WEB == 'books'){
	$db['default']['username'] = 'root';
	$db['default']['password'] = '';
	$db['default']['database'] = 'books';
} else {
	die('failed DB');
}


$db['default']['hostname'] = 'localhost';
$db['default']['dbdriver'] = 'mysql';
$db['default']['dbprefix'] = 'bf_';
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = ''; 
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = TRUE;


/* End of file database.php */
/* Location: ./application/config/database.php */