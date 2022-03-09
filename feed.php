<?php
require_once('config.php');
/*
Name: Wikitude-Webservice for WordPress
Description: Provides a webservice for the augmented-reality-browser Wikitude (http://www.wikitude.org)
Requirements: self hosted WordPress and plugin Geolocation-Plus (http://wordpress.org/extend/plugins/geolocation-plus/)
Webservice documentation URI: http://hyparlocal.talkaboutlocal.org.uk/2012/06/19/import-your-wordpress-content-in-to-wikitude/
Author: Robert Harm updated by Mike Rawlins (Jan 2013)
Author URI: http://www.harm.co.at http://michaelrawlins.co.uk
Version: 1.1
Last Update: 09.01.2013
License: This work is licensed under a Creative Commons Attribution 3.0 Unported License. http://creativecommons.org/licenses/by/3.0/
*/

//Wikitude parameters - referencing documents:
//ARML Specification for Wikitude 4 (http://www.openarml.org/wikitude4.html)
//Wikitude Webservice Documentation (http://wikitude.me/assets/WikitudeWebservice.pdf)

$arprovider = "rbge"; //REQUIRED - no spaces/special characters , identifies the content provider or content channel. Must be unique across all providers
$arname = "Royal Botanic Garden Edinburgh"; //REQUIRED - name of the content provider. This name will be used to display the content provider in the settings and bookmarks menu of the browser

$ardescription = ""; //optional - description of a content provider that provides additional information about the content displayed
$wtproviderurl = ""; //optional - link to the content provider
$wttags = ""; //optional - comma separated list of keywords that characterize the content provider
$wtlogo = ""; //optional - logo displayed on the left bottom corner on Wikitude when an icon is selected - 96x96 pixel, transparent PNG
$wticon = ""; //optional - the icons are displayed in the cam view of Wikitude to indicate a point of interest (POI) - 32x32 pixel, transparent PNG
$wtthumbnail = "http://stories.rbge.org.uk/wp-content/uploads/2013/11/Sibbaldia_favicon.png"; // optional - leave empty if not used; Specific POI image that is displayed in the bubble. This could be for instance a hotel picture for a hotel booking content provider - 64x64 pixel, PNG

$wtemail = "r.hyam@rbge.org.uk"; //optional - displayed on each POI; used for sending an email directly from Wikitude
$wtphone = ""; //optional - example: +4312345 - when a phone number is given, Wikitude displays a "call me" button in the bubble; same phone number for each POI!
$wtattachment = ""; //optional - displayed on each POI; can be a link to a resource (image, PDF file...). You could use this to issue coupons or vouchers for potential clients that found you via Wikitude.

$radiusSet = "500000"; //REQUIRED - retrieve POIs (Points of Interests) from database within this search radius in meters from the current location of the Wikitude user
$maxNumerofPoisSet = "500"; //REQUIRED - used if Wikitude doesnt pass the variable maxNumberofPois - 50 is the maximum recommended

$latStandard = "55.953252"; //optional - for testing/debug: standard-latitude for calling webservice without parameters
$lonStandard = "-3.188267"; //optional - for testing/debug: standard-longitude for calling webservice without parameters


/*********************************/
/*No need to edit below this line*/
/*********************************/

// variables for tables with custom prefix for SQL-Queries
$tabpostmeta = $DB_PREFIX . 'postmeta';
$tabposts = $DB_PREFIX . 'posts';
$tabterms = $DB_PREFIX . 'terms';
$tabtermrelationships = $DB_PREFIX . 'term_relationships';

    /* Work out the radius if it is passed in */
    $radiusSet = isset($_GET['radius']) ? $_GET['radius'] : $radiusSet;

    /* soak in the passed variable or use our own */
    $maxNumberOfPois = isset($_GET['maxNumberOfPois']) ? intval($_GET['maxNumberOfPois']) : $maxNumerofPoisSet;
    $latUser = isset($_GET['latitude']) ? floatval($_GET['latitude']) : $latStandard;
    $lonUser = isset($_GET['longitude']) ? floatval($_GET['longitude']) : $lonStandard;

    $radius = $radiusSet;
    $distanceLLA = 0.01 * $radius / 1112;
    $boundingBoxLatitude1 = $latUser - $distanceLLA;
    $boundingBoxLatitude2 = $latUser + $distanceLLA;
    $boundingBoxLongitude1 = $lonUser - $distanceLLA;
    $boundingBoxLongitude2 = $lonUser + $distanceLLA;

    /* connect to the db */
//    $link = mysql_connect($DB_HOST,$DB_USER,$DB_PASSWORD) or die('Cannot connect to the Database');
//    mysql_select_db($DB_NAME,$link) or die('Cannot select the Database');

    isset($_GET['searchterm']) ? $searchterm = $_GET['searchterm'] : $searchterm = NULL;

    /* grab the POIs from the db  */
    $query = "SELECT
                    p.ID AS id,
                    p.post_title AS name,
                    p.post_excerpt AS description,
                    concat(pm1.meta_value,',',pm2.meta_value) AS adress,
                    p.guid AS url,
                    concat(pm1.meta_value,',',pm2.meta_value) AS coordinates
                FROM $tabposts p
                JOIN $tabpostmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'geo_longitude'
                JOIN $tabpostmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'geo_latitude'
                JOIN wp_term_relationships as tr on p.ID = tr.object_id
                JOIN wp_term_taxonomy as tt on tr.term_taxonomy_id = tt.term_taxonomy_id
                JOIN wp_terms as t on tt.term_id = t.term_id
                WHERE pm2.meta_value >= least($boundingBoxLatitude1,$boundingBoxLatitude2)
                AND pm2.meta_value <= greatest($boundingBoxLatitude1,$boundingBoxLatitude2)
                AND pm1.meta_value >= least($boundingBoxLongitude1, $boundingBoxLongitude2) 
                AND pm1.meta_value <= greatest($boundingBoxLongitude1, $boundingBoxLongitude2)
                AND p.post_status= 'publish'";

    if(isset($_GET['slug'])){
        $query .=  " AND t.slug = '" .  $_GET['slug']  . "'";
    }

    $query .= " GROUP BY p.ID";

    $query .= " limit $maxNumberOfPois";

    $result =  $mysqli->query($query) or die('Errant query:  '.$query);   //mysql_query($query,$link) or die('Errant query:  '.$query);

    /* start output */
        header('Content-type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:ar="http://www.openarml.org/arml/1.0" xmlns:wikitude="http://www.openarml.org/wikitude/1.0" xmlns:wikitudeInternal="http://www.openarml.org/wikitudeInternal/1.0">';
        echo '<Document>';
        echo '<visibility>1</visibility>';
	echo '<open>1</open>';
        echo '<ar:provider id="'.$arprovider.'">';
        echo '<ar:name><![CDATA['.$arname.']]></ar:name>';
        echo '<ar:description><![CDATA['.$ardescription.']]></ar:description>';
        echo '<wikitude:providerUrl><![CDATA['.$wtproviderurl.']]></wikitude:providerUrl>';
        echo '<wikitude:tags><![CDATA['.$wttags.']]></wikitude:tags>';
        echo '<wikitude:logo><![CDATA['.$wtlogo.']]></wikitude:logo>';
        echo '<wikitude:icon><![CDATA['.$wticon.']]></wikitude:icon>';
        echo '</ar:provider>';
            while($value = $result->fetch_assoc()) {
                if(is_array($value)) {
                            echo '<Placemark id=\''.$value['id'].'\'>';
                            echo '<ar:provider><![CDATA['.utf8_encode($arprovider).']]></ar:provider>';
                            echo '<name><![CDATA['.utf8_encode($value['name']).']]></name>';
                            echo '<description><![CDATA[';
                            echo '<p>';
                            echo utf8_encode(strip_tags($value['description']));
                            echo ' <a href="http://stories.rbge.org.uk/archives/' . $value['id'] . '">Read more...</a>';
                            echo '</p>';
                            if ( has_post_thumbnail($value['id'])) {
                              echo '<a  href="' . get_permalink( $value['id'] ) . '" title="' . esc_attr( $value['name'] ) . '">';
                              echo get_the_post_thumbnail($value['id'], 'thumbnail');
                              echo '</a>';
                            }
                            echo ']]></description>';
                            //echo '<description><![CDATA[Test Banana]]></description>';
                            echo '<wikitude:info>';
                                echo '<wikitude:thumbnail><![CDATA['.utf8_encode($wtthumbnail).']]></wikitude:thumbnail>';
                                echo '<wikitude:phone><![CDATA['.utf8_encode($wtphone).']]></wikitude:phone>';
                                echo '<wikitude:url><![CDATA['.utf8_encode($value['url']).']]></wikitude:url>';
                                echo '<wikitude:email><![CDATA['.utf8_encode($wtemail).']]></wikitude:email>';
                                echo '<wikitude:address><![CDATA['.utf8_encode($value['adress']).']]></wikitude:address>';
                                echo '<wikitude:attachment><![CDATA['.utf8_encode($wtattachment).']]></wikitude:attachment>';
                            echo '</wikitude:info>';
                            echo '<Point>';
                                echo '<coordinates><![CDATA['.$value['coordinates'].']]></coordinates>';
                            echo '</Point>';
                            echo '</Placemark>';
                 }
             }
        echo '</Document>';
        echo '</kml>';

?>

