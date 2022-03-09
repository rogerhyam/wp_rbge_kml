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
                    p.post_content AS content,
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
          echo '<audio_tour xmlns="http://data.rbge.org.uk/ns/audio_tour"  >';

              $count = 0;
              foreach($posts as $post) {

                  echo '<point ';
                  echo ' id="'. $post['id'] .'"';
                  echo ' weight="'. $count++ .'"';
                  echo ' longitude="'.  $post['longitude'] .'"';
                  echo ' latitude="'.  $post['latitude'] .'"';
                  
                  // put in an image
                  if ( has_post_thumbnail($post['id'])) {
                    $thumbnail_id = get_post_thumbnail_id($post['id']);
                    $thumbnail_object = get_post($thumbnail_id);
                    echo ' image="'. $thumbnail_object->guid  .'"';
                  }
                  
                  // put in the mp3
                  $sql = "SELECT guid FROM wp_posts AS p WHERE p.post_mime_type = 'audio/mpeg' AND p.post_parent = " . $post['id'];
                  $result = $mysqli->query($sql);
                  if($result->num_rows > 0){
                      $row = $result->fetch_assoc();
                      echo ' audio="'.  $row['guid'] .'"';
                  }

                  echo ' source="'.  get_permalink( $post['id'] ) .'"';
                  
                  echo ' >';
                  
                  echo '<title><![CDATA['.utf8_encode($post['name']).']]></title>';
                  echo '<summary><![CDATA['.utf8_encode($post['description']).']]></summary>';                  
                  //echo '<full-text><![CDATA['. utf8_encode($post['content']) . ']]></full-text>';
                  
                  echo '</point>';

             }

          echo '</audio_tour>';