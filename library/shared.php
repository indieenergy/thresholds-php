<?php

    function setReporting() {
        if( DEVELOPMENT_ENVIRONMENT == true ) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        }
        else {
            error_reporting(E_ALL);
            ini_set('display_errors', 'Off');
            ini_set('log_errors', 'On');
            ini_set('error_log', ROOT.DS.'tmp'.DS.'logs'.DS.'error.log');
        }
    }
    
    function stripSlashesDeep($value) {
        $value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value);
        return $value;
    }
    
    function removeMagicQuotes() {
        if( get_magic_quotes_gpc() ) {
            $_GET = stripSlashesDeep($_GET);
            $_POST = stripSlashesDeep($_POST);
            $_COOKIE = stripSlashesDeep($_COOKIE);
        }
    }
    
    function unregisterGlobals() {
        if( ini_get('register_globals') ) {
            $array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
            foreach( $array as $value ) {
                foreach( $GLOBALS[$value] as $key=> $var ) {
                    if( $var == $GLOBALS[$key] ) {
                        unset($GLOBALS[$key]);
                    }
                }
            }
        }
    }
    
    function performAction($controller, $action, $queryString=null, $render=0) {
        $controllerName = ucfirst($controller) . 'Controller';
        $dispatch = new $controllerName($controller, $action);
        $dispatch->render = $render;
        return call_user_func_array(array($dispatch, $action), $queryString);
    }
    
    function routeURL($url) {
        global $routing;
        
        foreach( $routing as $pattern => $result ) {
            if( preg_match($pattern, $url) ) {
                return preg_replace($pattern, $result, $url);
            }
        }
        return($url);
    }
    
    function callHook() {
        global $url;
        global $default;
        
        $queryString = array();
        
        if( !isset($url) ) {
            $controller = $default['controller'];
            $action = $default['action'];
        }
        else {
            $url = routeURL($url);
            $urlArray = array();
            $urlArray = explode("/", $url);
            $controller = $urlArray[0];
            array_shift($urlArray);
            if( isset($urlArray[0]) ) {
                $action = $urlArray[0];
                array_shift($urlArray);
            }
            else {
                $action = 'index'; // Default action
            }
            $queryString = $urlArray;
        }
        
        $controllerName = ucfirst($controller) . 'Controller';
        
        $dispatch = new $controllerName($controller, $action);
        
        if( (int)method_exists($controllerName, $action) ) {
            call_user_func_array(array($dispatch,"beforeAction"),$queryString);
            call_user_func_array(array($dispatch,$action),$queryString);
            call_user_func_array(array($dispatch,"afterAction"),$queryString);
        }
        else {
            /* Error */
        }
    }
    
    function __autoload($className) {
        if( file_exists(ROOT . DS . 'library' . DS . strtolower($className) . '.class.php') ) {
            require_once(ROOT . DS . 'library' . DS . strtolower($className) . '.class.php');
        }
        else if( file_exists(ROOT . DS . 'application' . DS . 'controllers' . DS . strtolower($className) . '.php') ) {
            require_once(ROOT . DS . 'application' . DS . 'controllers' . DS . strtolower($className) . '.php');
        }
        else if( file_exists(ROOT . DS . 'application' . DS . 'models' . DS . strtolower($className) . '.php') ) {
            require_once(ROOT . DS . 'application' . DS . 'models' . DS . strtolower($className) . '.php');
        }
        else {
            /* Error */
        }
    }
    
    setReporting();
    removeMagicQuotes();
    unregisterGlobals();
    callHook();
    
?>