<?php
// à renommer en params.php pour faire fonctionner l'appli

define("MODE_DEBUG", FALSE);
 
error_reporting(E_ALL & ~E_DEPRECATED);
//error_reporting(E_ALL);
ini_set('display_errors', '0');

// $glo_email_admin = "michel@ladecadanse.ch"; // prod
// $glo_email_info = "info@ladecadanse.ch"; // prod
// $glo_email_support = "info@ladecadanse.ch"; // prod
$glo_email_admin = "";
$glo_email_info = "";
$glo_email_support = "";

// auth SMTP
// $glo_email_host = "mail.darksite.ch"; // prod
// $glo_email_username = "info@ladecadanse.ch"; // prod
$glo_email_host = "";
$glo_email_username = "";
$glo_email_password = "";

// path
//$rep_absolu = "/home/www/darksite.ch/ladecadanse/"; // prod
$rep_absolu = "";

// URL
// $url_domaine = "http://ladecadanse.darksite.ch"; // prod
$url_domaine = "";
$url_site = $url_domaine."";


// pour récupérer le nom du script courant (sans path ni extension)
// define("PREG_PATTERN_NOMPAGE", "/^\/(.+)\.php$/"); // prod : ladecadanse.darksite.ch
define("PREG_PATTERN_NOMPAGE", "/^\/.*\/(.+)\.php$/"); // host/ladecadanse/

// base de données
$param['dbhost'] = '';
$param['dbusername'] = '';
$param['dbpassword'] = '';
$param['dbname'] = '';
