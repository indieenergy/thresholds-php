<?php
    
    class Client
    {
        const API_HOST = "indiegeopod.com";
        const API_PORT = 80;
        const API_VERSION = "v1";
        
        protected $token_key;
        protected $token_secret;
        protected $consumer_key;
        protected $consumer_secret;
        protected $host;
        protected $port;
        
        function __construct($token_key, $token_secret, $consumer_key, $consumer_secret, $host=self::API_HOST, $port=self::API_PORT)
        {
            $this->token_key = $token_key;
            $this->token_secret = $token_secret;
            $this->consumer_key = $consumer_key;
            $this->consumer_secret = $consumer_secret;
            
            $this->host = $host;
            $this->port = $port;
        }
        
        public function request($target, $method="GET", $params=array())
        {
            $base = "";
            
            try {
                $oauth = new OAuth($this->consumer_key, $this->consumer_secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
                $oauth->setToken($this->token_key, $this->token_secret);
                
                if( $method == "GET" ) {
                    $base = $this->build_full_url($target, $params);
                    $oauth->fetch($base);
                }
                else {
                    $base = $this->build_full_url($target);
                    $oauth->fetch($base, $params, OAUTH_HTTP_METHOD_POST);
                }
                
                $response_info = $oauth->getLastResponseInfo();
                
                if( $response_info["http_code"] == 200 ) {
                    $response = json_decode($oauth->getLastResponse(), true);
                }
                else {
                    $response = Array('error'=>$oauth->getLastResponse());
                }
            }
            catch(OAuthException $E) {
                $response = Array('error'=>$E->lastResponse);
            }
            catch(Exception $E) {
                $response = Array('error'=>$E->getMessage());
            }
            return $response;
        }
        
        protected function build_full_url($target, $params=array())
        {
            $port = $this->port == 80 ? "" : ":" . $this->port;
            $base_full_url = "http://" . $this->host . $port;
            return $base_full_url . $this->build_url($target, $params);
        }
        
        protected function build_url($url, $params=array())
        {
            $target_path = str_replace('%2F', '/', urlencode($url));
            
            if( empty($params) ) {
                return "/api/" . self::API_VERSION . $target_path;
            }
            else {
                return "/api/" . self::API_VERSION . $target_path . '?' . http_build_query($params, null, '&');
            }
        }
        
    }

    class UserClient extends Client
    {
        public function geopods()
        {
            return $this->request("/geopods/");
        }
    }
    
    class GeopodClient extends Client
    {
        protected $geopod;
        
        function __construct($geopod, $token_key, $token_secret, $consumer_key, $consumer_secret, $host=Client::API_HOST, $port=Client::API_PORT)
        {
            parent::__construct($token_key, $token_secret, $consumer_key, $consumer_secret, $host, $port);
            $this->geopod = $geopod;
        }
        
        protected function build_full_url($target, $params=array())
        {
            $port = $this->port == 80 ? "" : ":" . $this->port;
            $base_full_url = "http://" . $this->geopod . "." . $this->host . $port;
            return $base_full_url . $this->build_url($target, $params);
        }
        
        public function info()
        {
            return $this->request("/");
        }
    }
    
?>