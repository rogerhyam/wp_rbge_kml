<?php
 header("Content-type: text/xml; charset=utf-8"); 
 echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
      
      <LookAt>
        <longitude>-4</longitude>
        <latitude>55.5</latitude>
        <altitude>0</altitude>
        <range>220000</range>
        <tilt>45</tilt>
        <heading>N</heading>
      </LookAt>
      <flyToView>1</flyToView>
        
      <!-- Edinburgh -->
      <Folder>
        <name>Edinburgh Botanic Garden</name>
        <visibility>1</visibility>
        <open>0</open>
        <description>The gardens in Edinburgh</description>
        <LookAt>
           <longitude>-3.208630</longitude>
           <latitude>55.965543</latitude>
           <altitude>0</altitude>
           <range>500</range>
           <tilt>30</tilt>
           <heading>N</heading>
         </LookAt>
        <?php 
            build_networklink('Native Tree Trail', 'This trail showcases examples of eighteen of Scotland’s most significant woodland trees.', 'Edinburgh', 'native-tree-trail');
        ?>
      </Folder>
     
      <!-- Logan --> 
      <Folder>
          <name>Logan Botanic Gardens</name>
          <visibility>1</visibility>
          <open>0</open>
          <description>dfsd sdf as</description>
          <flyToView>1</flyToView>
          <LookAt>
            <longitude>-4.960728</longitude>
            <latitude>54.743542</latitude>
            <altitude>0</altitude>
            <range>1000</range>
            <tilt>30</tilt>
            <heading>N</heading>
          </LookAt>
          <?php 
            build_networklink('Points of Interest', 'Some attractions of a visit to Logan', 'Logan', 'poi'); 
          ?>
     </Folder>

     <!-- Dawyck -->
     <Folder>
       <name>Dawyck Botanic Garden</name>
       <visibility>1</visibility>
       <open>0</open>
       <description>FIXME: Gardens</description>
       <LookAt>
          <longitude>-3.320621</longitude>
          <latitude>55.601556</latitude>
          <altitude>0</altitude>
          <range>1500</range>
          <tilt>30</tilt>
          <heading>N</heading>
        </LookAt>
       <?php 
           build_networklink('Points of Interest', 'Some attractions of a visit to Dawyck', 'Dawyck', 'poi');
       ?>
     </Folder>

     <!-- Benmore -->
     <Folder>
       <name>Benmore Botanic Garden</name>
       <visibility>1</visibility>
       <open>0</open>
       <description>FIXME: Gardens</description>
       <LookAt>
          <longitude>-4.988376</longitude>
          <latitude>56.027186</latitude>
          <altitude>0</altitude>
          <range>2000</range>
          <tilt>30</tilt>
          <heading>N</heading>
        </LookAt>
       <?php
           build_networklink('Conifer Trail', 'A Google Earth Animated, narrated tour of some conifers at Benmore', 'Benmore', 'conifer-trail', true);
           build_networklink('Points of Interest', 'Some attractions of a visit to Benmore', 'Benmore', 'poi');
       ?>
     </Folder>

  </Document>
</kml>

<?php

function build_networklink($name, $description, $garden, $slug, $tour = false){
    
    // define the boundaries of the different gardens
    $gardens = array(
      
        'Edinburgh' => array(
            'latitude' =>  55.965543,
            'longitude' => -3.208630,
            'radius' => 500
        ),
        
        'Benmore' => array(
            'latitude' =>  56.027186,
            'longitude' =>  -4.988376,
            'radius' => 2000
        ),
        
        'Logan' => array(
            'latitude' => 54.743542,
            'longitude' => -4.960728,
            'radius' => 1500
        ),
        
        'Dawyck' => array(
            'latitude' =>  55.601556,
            'longitude' => -3.320621,
            'radius' => 1500
        ),
        
    );

    $location = $gardens[$garden];

?>

<NetworkLink>
  <name><?php echo $name ?></name>
  <visibility>0</visibility>
  <open>0</open>
  <description><?php echo $description ?></description>
  <refreshVisibility>0</refreshVisibility>
  <flyToView>1</flyToView>
  <LookAt>
    <longitude><?php echo $location['longitude'] ?></longitude>
    <latitude><?php echo $location['latitude'] ?></latitude>
    <altitude>0</altitude>
    <range><?php echo $location['radius'] ?></range>
    <tilt>30</tilt>
    <heading>N</heading>
  </LookAt>
  <Link>
<?php if ($tour){?>      
    <href>http://<?php echo $_SERVER['HTTP_HOST'] ?>/wp-content/plugins/rbge_kml/tour.php?slug=<?php echo $slug ?></href>
<?php }else{?>
    <href>http://<?php echo $_SERVER['HTTP_HOST'] ?>/wp-content/plugins/rbge_kml/feed.php?latitude=<?php echo $location['latitude'] ?>&amp;longitude=<?php echo $location['longitude'] ?>&amp;radius=<?php echo $location['radius'] ?>&amp;slug=<?php echo $slug ?></href>
<?php } ?>
  </Link>
</NetworkLink>

<?php

} // end of build_networklink

?>
