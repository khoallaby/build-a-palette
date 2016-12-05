<?php

abstract class base_plugin {
    private static $instance = array();

    protected function __construct() {

    }

    public static function get_instance() {
        $c = get_called_class();
        if ( !isset( self::$instance[$c] ) ) {
            self::$instance[$c] = new $c();
            self::$instance[$c]->init();
        }
        return self::$instance[$c];
    }

    abstract public function init();
}

