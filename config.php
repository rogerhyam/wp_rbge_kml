<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

/***********************************/
/*Configuration                    */
/***********************************/
$DB_HOST = "localhost"; // REQUIRED - database host, in most cases localhost
$DB_NAME = "botanics_blog"; // REQUIRED - name of your wordpress database
$DB_USER = "botanics_blog"; // REQUIRED - mysql-username for accessing the database
$DB_PASSWORD = "yellow122"; // REQUIRED - password for accessing the database
$DB_PREFIX = "wp_"; // REQUIRED - wordpress-table-prefix - wp_ is standard


// make use of wordpress functions
//define('WP_USE_THEMES', false);
require('../../../wp-load.php');
add_theme_support( 'post-thumbnails' );

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

?>