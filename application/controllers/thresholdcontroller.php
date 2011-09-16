<?php
    require_once('../library/geopod.php');
    
    class ThresholdController extends Controller {
        
        function index() {
            $geopod = null;
            $points = array();
            if( isset($_GET["data"]) ) {
                
                $data = json_decode(self::base64UrlDecode($_GET["data"]), true);
                $signature = isset($_GET["sig"]) ? self::base64UrlDecode($_GET["sig"]) : "";
                $subdomain = isset($data["subdomain"]) ? $data["subdomain"] : "";
                
                $signature_check = hash_hmac("sha256", $_GET["data"], GEOPOD_CONSUMER_SECRET, true);
                
                if( $signature != $signature_check ) {
                    header("HTTP/1.1 403 Forbidden");
                    $this->render = 0;
                    return;
                }
                
                if( $subdomain ) {
                    $query = new Geopod();
                    $query->where("subdomain", $subdomain);
                    $geopod = $query->search();
                    if( empty($geopod) ) {
                        header("HTTP/1.1 404 Not Found");
                        $this->render = 0;
                        return;
                    }
                    $geopod = $geopod[0];
                    // Get the points here
                    $gc = new GeopodClient($subdomain, $geopod["Geopod"]["access_token"], $geopod["Geopod"]["access_token_secret"], GEOPOD_CONSUMER_KEY, GEOPOD_CONSUMER_SECRET, GEOPOD_API_HOST);
                    $points = $gc->request("/point/", "GET", array("markers[]" => "his"));
                }
            }
            $points = array(array("id" => "149eecb3-4cb9224d"));
            $start = isset($_GET["start"]) ? $_GET["start"] : date("Y-m-d");
            $end = isset($_GET["end"]) ? $_GET["end"] : date("Y-m-d");
            
            $this->set('geopod', $geopod);
            $this->set('points', $points);
            $this->set('start', $start);
            $this->set('end', $end);
        }
        
        function auth() {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                
                if( isset($_POST["subdomain"]) ) {
                    $subdomain = $_POST["subdomain"];
                    $geopod = new Geopod();
                    $geopod->where("subdomain", $subdomain);
                    $result = $geopod->search();
                    
                    if( empty($result) ) {
                        $geopod->id = null;
                        $geopod->name = isset($_POST["name"]) ? $_POST["name"] : "";
                        $geopod->subdomain = $_POST["subdomain"];
                        $geopod->access_token = isset($_POST["access_token"]) ? $_POST["access_token"] : "";
                        $geopod->access_token_secret = isset($_POST["access_token_secret"]) ? $_POST["access_token_secret"] : "";
                        $geopod->save();
                    }
                    else {
                        $geopod->id = $result[0]['Geopod']['id'];
                        $geopod->name = isset($_POST["name"]) ? $_POST["name"] : "";
                        $geopod->access_token = isset($_POST["access_token"]) ? $_POST["access_token"] : "";
                        $geopod->access_token_secret = isset($_POST["access_token_secret"]) ? $_POST["access_token_secret"] : "";
                        $geopod->save();
                    }
                    $this->render = 0;
                }
                else {
                    header("HTTP/1.1 400 Bad Request");
                    $this->render = 0;
                }
            }
            else {
                header("HTTP/1.1 405 Method Not Allowed");
                $this->render = 0;
            }
        }
        
        function data() {
            $point_ids = isset($_GET['points']) ? $_GET['points'] : array();
            $start = isset($_GET['start']) ? $_GET['start'] : date("Y-m-d");
            $end = isset($_GET['end']) ? $_GET['end'] : date("Y-m-d");
            $subdomain = isset($_GET['subdomain']) ? $_GET['subdomain'] : "";
            
            $data_series = array();
            
            if( $subdomain ) {
                $query = new Geopod();
                $query->where("subdomain", $subdomain);
                $geopod = $query->search();
                if( empty($geopod) ) {
                    header("HTTP/1.1 404 Not Found");
                    $this->render = 0;
                    return;
                }
                $geopod = $geopod[0];
                // Get the points here
                $gc = new GeopodClient($subdomain, $geopod["Geopod"]["access_token"], $geopod["Geopod"]["access_token_secret"], GEOPOD_CONSUMER_KEY, GEOPOD_CONSUMER_SECRET, GEOPOD_API_HOST);
                
                foreach( $point_ids as $point_id ) {
                    $point = $gc->request("/history/" . $point_id . "/" . $start . "/" . $end . "/");
                    if( !isset($point["error"]) ) {
                        array_push($data_series, array(
                            "name" => isset($point["name"]) ? $point["name"] : "",
                            "unit" => isset($point["unit"]) ? $point["unit"] : "",
                            "data" => isset($point["data"]) ? $point["data"] : array(),
                            "point_id" => $point_id
                        ));
                    }
                }
                
                $graph_data = array(
                    "start_date" => $start,
                    "end_date" => $end,
                    "utc_offset" => -intval(date("Z")) * 1000,
                    "series" => $data_series
                );
                echo(json_encode($graph_data));
                $this->render = 0;
            }
            else {
                header("HTTP/1.1 400 Bad Request");
                $this->render = 0;
            }
        }
        
        protected static function base64UrlDecode($input) {
            return base64_decode(strtr($input, '-_', '+/'));
        }
    }

?>