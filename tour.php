<?php

require_once('config.php');
require_once('mp3file.php');

// variables for tables with custom prefix for SQL-Queries
$tabpostmeta = $DB_PREFIX . 'postmeta';
$tabposts = $DB_PREFIX . 'posts';
$tabterms = $DB_PREFIX . 'terms';
$tabtermrelationships = $DB_PREFIX . 'term_relationships';
$escaped_slug = mysql_real_escape_string($_GET['slug']);

    /* grab the POIs from the db  */
    $query = "SELECT
                    p.ID AS id,
                    p.post_title AS name,
                    p.post_excerpt AS description,
                    concat(pm1.meta_value,',',pm2.meta_value) AS address,
                    p.guid AS url,
                    concat(pm1.meta_value,',',pm2.meta_value) AS coordinates,
                    pm1.meta_value as longitude,
                    pm2.meta_value as latitude
                FROM $tabposts p
                JOIN $tabpostmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'geo_longitude'
                JOIN $tabpostmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'geo_latitude'
                JOIN wp_term_relationships as tr on p.ID = tr.object_id
                JOIN wp_term_taxonomy as tt on tr.term_taxonomy_id = tt.term_taxonomy_id
                JOIN wp_terms as t on tt.term_id = t.term_id
                WHERE p.post_status= 'publish'
                AND t.slug = '$escaped_slug'
                GROUP BY p.ID
                ORDER BY p.post_date
                LIMIT 50";

    //echo $query; exit;

    // can the result so we have it for later and can do other queries without
    // messing anything up.
    $result = $mysqli->query($query) or die('Errant query:  '.$query);
    $posts = array();
    while($row = $result->fetch_assoc()){
        $posts[] = $row;
    }

    /* start output */
        header('Content-type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        
        echo '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" >';
        echo '<Document>';
        echo '<visibility>1</visibility>';
        echo '<open>1</open>';

            //write the tour out
            echo '<gx:Tour>';
            echo '<name>Take the tour</name>';
            echo '<gx:Playlist>';
            
            
            foreach($posts as $post) {
                
                //build_flyto($duration, $longitude, $latitude, $range, $heading, $tilt)
                //build_animate_balloon($target_id, $value, $duration = 0.0)
                
                build_flyto(5, $post['longitude'], $post['latitude'], 500, 0, 35);
                
                build_animate_balloon($post['id'], 1, 0.0);
                
                // has it got a sound file?
                $sql = "SELECT guid FROM wp_posts AS p WHERE p.post_mime_type = 'audio/mpeg' AND p.post_parent = " . $post['id'];
                $result = $mysqli->query($sql);
                
                // we just get the first one if there is one
                if($result->num_rows > 0){
                    $row = $result->fetch_assoc();
                    $uri = $row['guid'];
                    $realPath = ABSPATH . substr(parse_url($uri, PHP_URL_PATH), 1);
                    $mp3 = new mp3file($realPath);
                    $mp3meta = $mp3->get_metadata();
                    build_audio($uri);
                    build_wait($mp3meta['Length']);
                }else{
                    build_wait(4.0);
                }
                
                build_animate_balloon($post['id'], 0, 0.0);
 
           
           }
            
            echo '</gx:Playlist>';
            echo '</gx:Tour>';

            echo '<Folder>';
            echo '<name>Points of Interest in Tour</name>';
            echo '<visibility>0</visibility>';
            echo '<open>0</open>';
            
            // write the point in the tour out
            foreach($posts as $post) {
                            echo '<Placemark id=\''.$post['id'].'\'>';
                            echo '<name><![CDATA['.utf8_encode($post['name']).']]></name>';
                            echo '<description><![CDATA[';
                            echo '<p>';
                            echo utf8_encode($post['description']);
                            echo ' <a href="http://stories.rbge.org.uk/archives/' . $post['id'] . '">Read more...</a>';
                            echo '</p>';
                            if ( has_post_thumbnail($post['id'])) {
                                echo '<a  href="' . get_permalink( $post['id'] ) . '" title="' . esc_attr( $post['name'] ) . '">';
                                echo get_the_post_thumbnail($post['id'], 'thumbnail');
                                echo '</a>';
                            }
                            echo ']]></description>';
                            echo '<Point>';
                                echo '<coordinates><![CDATA['.$post['coordinates'].']]></coordinates>';
                            echo '</Point>';
                            echo '</Placemark>';
             }
             
             echo '</Folder>';             
            
        echo '</Document>';
        echo '</kml>';

    function build_flyto($duration, $longitude, $latitude, $range, $heading, $tilt){
        echo "
            <gx:FlyTo>
               <gx:duration>$duration</gx:duration>
               <gx:flyToMode>smooth</gx:flyToMode>
               <LookAt>
                 <longitude>$longitude</longitude>
                 <latitude>$latitude</latitude>
                 <altitude>0</altitude>
                 <range>200</range>
                 <heading>0</heading>
                 <tilt>33.5</tilt>
               </LookAt>
             </gx:FlyTo>
        ";
    }

    function build_animate_balloon($target_id, $value, $duration){
  
        echo "
            <gx:AnimatedUpdate>
              <gx:duration>$duration</gx:duration>
              <Update>
                <targetHref/>
                <Change>
                    <Placemark targetId=\"$target_id\">
                      <gx:balloonVisibility>$value</gx:balloonVisibility>
                    </Placemark>
                </Change>
              </Update>
            </gx:AnimatedUpdate>";
  
    }
    
 
    function build_wait($seconds){
        echo "
            <gx:Wait>
               <gx:duration>$seconds</gx:duration>
             </gx:Wait>";
    }
        
    function build_audio($uri){
    
        echo "
            <gx:SoundCue>
                  <href>$uri</href>
            </gx:SoundCue>
        ";
    }
    
?>

