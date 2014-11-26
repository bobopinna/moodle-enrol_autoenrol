<?php

include('autoenrol_base.php');

/**
 * Class sync_user_enrolments_test
 */
class sync_user_enrolments_test extends autoenrol_base {

    /**
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        require(dirname(__DIR__).'/lib.php');
        return parent::__construct($name, $data, $dataName);
    }

    /**
     *
     * @test
     */
    public function no_records_test(){
        global $DB;
        $DB = $this->getMockForAbstractClass('moodle_database');
        $DB->expects($this->any())
            ->method('get_records')
            ->will($this->returnValue( array( ) ));

        $enrol = new enrol_autoenrol_plugin();

        $user = $this->get_example_user(2);

        $this->assertMethodExists($enrol, 'sync_user_enrolments');

        $enrol->sync_user_enrolments($user);

    }
}
