<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
class ilp_mis_attendance_overview_plugin_course extends ilp_mis_attendance_plugin{

    protected $withperformance;

    public function __construct( $params=array() ) {
        parent::__construct( $params );
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
        global $CFG;
        require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');


        // set up the flexible table for displaying the portfolios
        $flextable = new ilp_ajax_table( 'attendance_plugin_simple' );
        $headers = array(
                'Subject',
                'Attendance',
                'Punctuality',
                'Grade',
                'Performance'
         );
        $columns = $headers;
        $flextable->define_columns($columns);
        $flextable->define_headers($headers);
        $flextable->initialbars(false);
        $flextable->setup();
        foreach( $this->data as $row ){
            $data = array();
            $data[ 'Subject' ] = $row[ 0 ];
            $data[ 'Attendance' ] = $row[ 1 ];
            $data[ 'Punctuality' ] = $row[ 2 ];
            $data[ 'Grade' ] = $row[ 3 ];
            $data[ 'Performance' ] = $row[ 4 ];
            $flextable->add_data_keyed( $data );
        }
		ob_start();
        $flextable->print_html();
		$pluginoutput = ob_get_contents();
        ob_end_clean();
        
        echo $pluginoutput;
/*
        if( is_string( $this->data ) ){
            echo $this->data;
        }
        elseif( is_array( $this->data ) ){
		    echo self::test_entable( $this->data );
        }
*/
    }

    public function set_data( $student_id, $term_id=null, $withperformance=null ){
        $this->data = $this->format_for_display( $this->get_attendance_summary_by_course( $student_id ) , $withperformance );
    }

    protected function set_params( $params ){
        parent::set_params( $params );
        $this->params[ 'coursestudent_table' ] = get_config( 'block_ilp' , 'mis_coursestudent_table' );
        $this->params[ 'coursestudent_table_student_id_field' ] = get_config( 'block_ilp' , 'mis_coursestudent_table_student_id_field' );
        $this->params[ 'coursestudent_table_term_grade_field' ] = get_config( 'block_ilp' , 'mis_coursestudent_table_term_grade_field' );
        $this->params[ 'coursestudent_table_term_performance_field' ] = get_config( 'block_ilp' , 'mis_coursestudent_table_term_performance_field' );
    }

    protected function get_attendance_summary_by_course( $student_id ){
        $table = $this->params[ 'coursestudent_table' ];
        $studentid_field = $this->params[ 'coursestudent_table_student_id_field' ];
        $whereparams = array(
            $studentid_field => array( '=' => $student_id )
        );
        return $this->dbquery( $table, $whereparams );
    }

    private function format_for_display( $data, $withperformance ){
        $tablerowlist = array();
        $headerrow = array(
            'Subject',
            'Attendance',
            'Punctuality'
        );
        if( $withperformance ){
            $headerrow[] = 'Grade';
            $headerrow[] = 'Performance';
            $gradefield = $this->params[ 'coursestudent_table_term_grade_field' ];
            $performancefield = $this->params[ 'coursestudent_table_term_performance_field' ];
        }
        //$tablerowlist[] = $headerrow;
        foreach( $data as $subject => $row ){
            $subject = $row[ 'courseName' ];
            $attendance = $this->calcScore( $row, 'attendance' );
            $punctuality = $this->calcScore( $row, 'punctuality' );
            $outrow = array(
                $subject, $attendance, $punctuality
            );
            if( $withperformance ){
                $outrow[] = $row[ $gradefield ];
                $outrow[] = $row[ $performancefield ];
            }
            $tablerowlist[] = $outrow;
        }
        return $tablerowlist;
    }

    public function plugin_type(){
        return 'overview';
    }
    
    public function config_settings( &$settings ){
    	$settingsheader 	= new admin_setting_heading('block_ilp/mis_attendance_plugin_course', get_string('ilp_mis_attendance_plugin_course_pluginname', 'block_ilp'), '');
    	$settings->add($settingsheader);
        $tablename = new admin_setting_configtext( 'block_ilp/mis_coursestudent_table', get_string( 'ilp_mis_coursestudent_table', 'block_ilp' ), get_string( 'ilp_mis_coursestudent_tabledesc', 'block_ilp'  ) , '' );
		$settings->add( $tablename );
        $sidfield = new admin_setting_configtext( 'block_ilp/mis_coursestudent_table_student_id_field', get_string( 'ilp_mis_coursestudent_table_student_id_field', 'block_ilp' ), get_string( 'ilp_mis_coursestudent_table_student_id_fielddesc', 'block_ilp'  ) , '' );
		$settings->add( $sidfield );
        $gradefield = new admin_setting_configtext( 'block_ilp/mis_coursestudent_table_term_grade_field', get_string( 'ilp_mis_coursestudent_table_term_grade_field', 'block_ilp' ), get_string( 'ilp_mis_coursestudent_table_term_grade_fielddesc', 'block_ilp'  ) , ''  );
		$settings->add( $gradefield );
        $performancefield = new admin_setting_configtext( 'block_ilp/mis_coursestudent_table_term_performance_field', get_string( 'ilp_mis_coursestudent_table_term_performance_field', 'block_ilp' ), get_string( 'ilp_mis_coursestudent_table_term_performance_fielddesc', 'block_ilp'  ) , ''  );
		$settings->add( $performancefield );
    }
	function language_strings(&$string) {
        $string[ 'ilp_mis_attendance_plugin_course_pluginname' ]                =   'Course Overview';
        $string[ 'ilp_mis_coursestudent_table' ]                                =   'course-student table';
        $string[ 'ilp_mis_coursestudent_tabledesc' ]                            =   'Table containing student attendance per course';
        $string[ 'ilp_mis_coursestudent_table_student_id_field' ]               =   'course-student student id field';
        $string[ 'ilp_mis_coursestudent_table_student_id_fielddesc' ]           =   'Field in course-student table identifying a student';
        $string[ 'ilp_mis_coursestudent_table_term_grade_field' ]               =   'course-student grade field';
        $string[ 'ilp_mis_coursestudent_table_term_grade_fielddesc' ]           =   'Field in course-student table giving a student\'s grade';
        $string[ 'ilp_mis_coursestudent_table_term_performance_field' ]         =   'course-student performance field';
        $string[ 'ilp_mis_coursestudent_table_term_performance_fielddesc' ]     =   'Field in course-student table showing a student\'s performance';
    }
}
