<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Initially developped for :
 * Université de Cergy-Pontoise
 * 33, boulevard du Port
 * 95011 Cergy-Pontoise cedex
 * FRANCE
 *
 * Create the block for the enrolment on demand method.
 *
 * @package   block_demands
 * @copyright 2018 Laurent Guillet <laurent.guillet@u-cergy.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * File : block_demands.php
 * Define the block
 */

class block_demands extends block_base {

    function init() {

        $this->title = get_string('enroldemands', 'block_demands');
    }

    function applicable_formats() {

        return array('my' => true);
    }

    function get_content() {

        global $CFG, $DB, $USER;

        if ($this->content !== NULL) {

            return $this->content;
        }

        $this->content = new stdClass;

        if (empty($this->instance)) {

            return $this->content;
        }

        $this->content->text = '';

        $teachedcoursesids = $this->get_teached_courses($USER->id);

        if (isset($teachedcoursesids)) {

            $teacheddemands = 0;
            foreach ($teachedcoursesids as $teachedcourseid) {

                $coursenbdemands = $DB->count_records('asked_enrolments',
                        array('courseid' => $teachedcourseid, 'answer' => ''));
                $teacheddemands += $coursenbdemands;
            }

            if ($teacheddemands > 1) {

                $receiveddemands = get_string('receivedplural', 'block_demands');
            } else {

                $receiveddemands = get_string('received', 'block_demands');
            }

            $this->content->text .= "<p><a href='$CFG->wwwroot/blocks/enrol_demands/requests.php'"
                    . " style='color:#731472;font-weight:bold'>";
            $this->content->text .= "<img src='$CFG->wwwroot/pix/i/enrolusers.png'>";
            $this->content->text .=  " <span style='color:red;font-weight:bold'>"
                    . "$teacheddemands</span> $receiveddemands";
            $this->content->text .= "</a></p>";
            $this->content->text .= "<hr></hr><h4 style='color:#731472;'>".
                    get_string('mydemands', 'block_demands')."</h4><br>";
        }

        $waitingsql = "SELECT COUNT(ae.id) AS nbr FROM {asked_enrolments} ae, {course} c "
                . "WHERE ae.studentid=$USER->id AND ae.answer = '' AND ae.courseid = c.id";
        $nbwantedcourses = $DB->get_record_sql($waitingsql);

	$resnbwantedcoursestraitement = "SELECT COUNT(ae.id) AS nbr FROM"
                . " {asked_enrolments} ae, {course} c "
                . "WHERE ae.studentid=$USER->id AND ae.answer IN"
                . " ('Oui', 'Non') AND ae.courseid = c.id";
	$nbwantedcoursestraitement = $DB->get_record_sql($resnbwantedcoursestraitement);

        if ($nbwantedcourses->nbr > 1) {

            $waitingdemands = get_string('waitingplural', 'block_demands');
        } else {

            $waitingdemands = get_string('waiting', 'block_demands');
        }

        $s = $this->is_plural($nbwantedcourses->nbr);
        $this->content->text .= "<p><a href='$CFG->wwwroot/blocks/enrol_demands/requests.php'"
                . " style='color:#731472;font-weight:bold'>";
        $this->content->text .= "<img src='$CFG->wwwroot/blocks/enrol_demands/pix/hourglass.png'"
                . " height='20' width='20'>";
        $this->content->text .=  " <span style='color:red;font-weight:bold'>$nbwantedcourses->nbr"
                . "</span> $waitingdemands";

        if ($nbwantedcoursestraitement->nbr > 1) {

            $answereddemands = get_string('answeredplural', 'block_demands');
        } else {

            $answereddemands = get_string('answered', 'block_demands');
        }

        $this->content->text .= "<p><a href='$CFG->wwwroot/blocks/enrol_demands/requests.php'"
                . " style='color:#731472;font-weight:bold'>";
	$this->content->text .= "<img src='$CFG->wwwroot/blocks/enrol_demands/pix/file.png'"
                . " height='20' width='20'>";
	$this->content->text .=  " <span style='color:red;font-weight:bold'>"
                . "$nbwantedcoursestraitement->nbr</span> $answereddemands";

	//Btn
	$this->content->text .= "<br><center><u>"
                . "<a href= '$CFG->wwwroot/course/index.php'>".
                get_string('adddemand', 'block_demands')."</a></u></center>";
	$this->content->text .= "</a></p>";

        return $this->content;
    }

    function get_teached_courses($teacherid) {

        global $DB;

        $listallassignments = $DB->get_records('role_assignments', array('userid' => $teacherid));

        $courseids = array();

        foreach ($listallassignments as $assignment) {

            $context = context::instance_by_id($assignment->contextid);

            if (has_capability('enrol/demands:managecourseenrolment', $context) &&
                    is_enrolled($context)) {

                if (!in_array ($context->instanceid , $courseids)) {

                    $courseids[] = $context->instanceid;
                }
            }
        }
        return $courseids;
    }
}



