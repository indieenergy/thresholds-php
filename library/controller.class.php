<?php

    class Controller {
    
        protected $_controller;
        protected $_action;
        protected $_template;

        public $doNotRenderHeader;
        public $render;

        function __construct($controller, $action) {
            
            $this->_controller = ucfirst($controller);
            $this->_action = $action;
            
            $this->doNotRenderHeader = 0;
            $this->render = 1;
            $this->_template = new Template($controller,$action);
        }

        function set($name,$value) {
            $this->_template->set($name,$value);
        }
        
        function beforeAction() {
            
        }
        
        function afterAction() {
            
        }
        
        function __destruct() {
            if ($this->render) {
                $this->_template->render($this->doNotRenderHeader);
            }
        }
    }

?>