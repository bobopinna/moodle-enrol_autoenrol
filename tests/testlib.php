<?php

if(!class_exists('enrol_plugin')) {
    /**
     * Class enrol_plugin
     */
    class enrol_plugin
    {

    }
}

if(!class_exists('moodle_database')) {
    /**
     * Class moodle_database
     */
    abstract class moodle_database
    {
        abstract public function get_records();

    }
}