<?php

include('testlib.php');

/**
 * Class autoenrol_base
 */
abstract class autoenrol_base extends PHPUnit_Framework_TestCase {
    /**
     * @param $obj
     * @param $method
     */
    protected function assertMethodExists($obj, $method){
        if(!method_exists($obj, $method)) {
            $classname = get_class($obj);
            $this->fail("method $classname::$method does not exist");
        }
    }

    /**
     * @param int $id
     * @return stdclass
     */
    protected function get_example_user($id=1){
        $user = new stdclass();
        $user->id = 2;
        $user->firstname = "Test";
        $user->lastname = "User ".$id;

        return $user;
    }
}
