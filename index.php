<?php
/**
* Plugin Name: White House Distance
* Plugin URI: http://moshebendavid.com/whitehouse-distance
* Description: Display distance from the visitor to the White House.
* Version: 1.0
* Author: Moshe Bendavid
* Author URI: http://moshebendavid.com
* License: whdis
*/
/*  Copyright 2020  Moshe Bendavid  (email : moshebendavid84@gmail.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/* :: Assets :: */
function whouse_scripts() {
  wp_enqueue_style( 'poppins-font', 'https://fonts.googleapis.com/css?family=Poppins:400,700&display=swap' );
  wp_enqueue_style( 'wh-style', plugin_dir_url( __FILE__ ) . 'assets/css/wh-style.min.css' );
}
add_action( 'wp_enqueue_scripts', 'whouse_scripts' );
/* :: Shortcode :: */
function whitehouse_distance() {
  // SSL is necessary for API to work correctly
  // Validate SSL on the URL
  $stream = stream_context_create (array("ssl" => array("capture_peer_cert" => true)));
  $read = fopen( site_url(), "rb", false, $stream);
  $cont = stream_context_get_params($read);
  $var = ($cont["options"]["ssl"]["peer_certificate"]);
  $result = (!is_null($var)) ? true : false;
  if ($result == true) {
    return "
    <script>
    jQuery(function($) {
      var options = {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
      };
      function success(pos) {
        var crd = pos.coords;
        let requestURL = '".site_url()."/wp-json/wh/v1/'+crd.latitude+'/'+crd.longitude;
        //console.log(requestURL);
        let request = new XMLHttpRequest();
        request.open('GET', requestURL);
        request.responseType = 'json';
        request.send();
        request.onload = function() {
          let response = request.response;
          $('#distanceKey').html(response.whitehouse_distance);
          $({ Counter: 0 }).animate({ Counter: $('#distanceKey').text() }, {
            duration: 1000,
            easing: 'swing',
            step: function () {
              $('#distanceKey').text(this.Counter.toFixed(2));
            }
          });
        }
      }
      function error(err) {
        console.warn('ERROR('+err.code+'):'+err.message+'');
      }
      navigator.geolocation.getCurrentPosition(success, error, options);
    });
    </script>
    <div class='boxhid' >
    <div class='icwh'></div>
    <h1>How far from the Whitehouse am I?</h1>
    <p><span id='distanceKey'>0.00</span> Miles</p>
    <p class='disttext'>Is your current distance from the Whitehouse</p>
    </div>";
  }
}
add_shortcode( 'whitehouse-distance', 'whitehouse_distance' );
/* :: Function for Custom Api Endpoint :: */
function whitehouse_distance_wpapi($request) {
  //Whitehouse geoLocation
  $whLat = '38.8976763';
  $whLon = '-77.0365298';
  //Geolocation Args
  $lat = $request['lat'];
  $long = $request['lon'];
  //Api Url for Calculation
  $urlCalculation ='https://graphhopper.com/api/1/route?point='.$lat.','.$long.'&point='.$whLat.','.$whLon.'&vehicle=car&out_array=distances&fail_fast=true&key=9bc14525-7340-4e32-957b-7b7737e51493&type=json';
  $calcCurl = curl_init();
  curl_setopt($calcCurl , CURLOPT_RETURNTRANSFER, true);
  curl_setopt($calcCurl , CURLOPT_URL,$urlCalculation);
  $resultCalc = curl_exec($calcCurl );
  curl_close($calcCurl);
  $decResultCalc = json_decode($resultCalc, true);
  //Converting to Miles
  $farCalc_RAW = $decResultCalc['paths'][0]['distance']*0.000621371;
  //Show Only 2 Decimals
  $farCalc = number_format((float)$farCalc_RAW, 2, '.', '');
  //Key and Value
  $data['whitehouse_distance'] = $farCalc;
  return $data;
}
/* :: Custom Api Endpoint :: */
add_action( 'rest_api_init', function () {
  register_rest_route( 'wh/v1/', '/(?P<lat>\S+)/(?P<lon>\S+)', array(
    'methods' => WP_REST_Server::READABLE,
    'callback' => 'whitehouse_distance_wpapi',
    'args' => [
      'lat',
      'lon',
      ],
    ) );
  } );
