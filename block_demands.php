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

defined('MOODLE_INTERNAL') || die;

class block_demands extends block_base {

    public function init() {

        $this->title = get_string('enroldemands', 'block_demands');
    }

    public function applicable_formats() {

        return array('my' => true);
    }

    public function get_content() {

        global $CFG, $DB, $USER;

        if ($this->content !== null) {

            return $this->content;
        }

        $this->content = new stdClass;

        if (empty($this->instance)) {

            return $this->content;
        }

        $this->content->text = '';
        $this->content->footer = '';

        $listenrolsids = $this->get_list_enrols($USER->id);

        if (isset($listenrolsids)) {

            $teacheddemands = 0;
            foreach ($listenrolsids as $enrolid) {

                $coursenbdemands = $DB->count_records('enrol_demands',
                        array('enrolid' => $enrolid, 'answer' => null));

                $teacheddemands += $coursenbdemands;
            }

            if ($teacheddemands > 0) {

                if ($teacheddemands > 1) {

                    $receiveddemands = get_string('receivedplural', 'block_demands');
                } else {

                    $receiveddemands = get_string('received', 'block_demands');
                }

                $this->content->text .= "<p><a href='$CFG->wwwroot/enrol/demands/requests.php'"
                        . " style='color:#731472;font-weight:bold'>";
                $this->content->text .= "<img src='$CFG->wwwroot/pix/i/enrolusers.png'>";
                $this->content->text .= " <span style='color:red;font-weight:bold'>"
                        . "$teacheddemands</span> $receiveddemands";
                $this->content->text .= "</a></p>";
                $this->content->text .= "<hr></hr><h4 style='color:#731472;'>".
                        get_string('mydemands', 'block_demands')."</h4><br>";
            }
        }

        $nbwantedcourses = $DB->count_records('enrol_demands',
                array('studentid' => $USER->id, 'answer' => null));

        $nbwantedcoursestraitement = $DB->count_records('enrol_demands',
                array('studentid' => $USER->id, 'answer' => 'Oui')) +
                $DB->count_records('enrol_demands',
                array('studentid' => $USER->id, 'answer' => 'Non'));

        if ($nbwantedcourses > 1) {

            $waitingdemands = get_string('waitingplural', 'block_demands');
        } else {

            $waitingdemands = get_string('waiting', 'block_demands');
        }

        $this->content->text .= "<p><a href='$CFG->wwwroot/enrol/demands/requests.php'"
                . " style='color:#731472;font-weight:bold'>";
        $this->content->text .= "<img src='$CFG->wwwroot/blocks/demands/pix/hourglass.png'"
                . " height='20' width='20'>";
        $this->content->text .= " <span style='color:red;font-weight:bold'>$nbwantedcourses"
                . "</span> $waitingdemands";

        if ($nbwantedcoursestraitement > 1) {

            $answereddemands = get_string('answeredplural', 'block_demands');
        } else {

            $answereddemands = get_string('answered', 'block_demands');
        }

        $this->content->text .= "<p><a href='$CFG->wwwroot/enrol/demands/requests.php'"
                . " style='color:#731472;font-weight:bold'>";
        $this->content->text .= "<img src='$CFG->wwwroot/blocks/demands/pix/file.png'"
                . " height='20' width='20'>";
        $this->content->text .= " <span style='color:red;font-weight:bold'>"
                . "$nbwantedcoursestraitement</span> $answereddemands";

        $this->content->footer .= ""
                . "<a href= '$CFG->wwwroot/course/index.php'>".
                get_string('adddemand', 'block_demands')."</a>";

        return $this->content;
    }

    public function get_list_enrols($teacherid) {

        global $DB;

        $listallassignments = $DB->get_records('role_assignments', array('userid' => $teacherid));

        $enrolids = array();

        $listenrols = null;

        foreach ($listallassignments as $assignment) {

            $context = context::instance_by_id($assignment->contextid);

            if ($context->contextlevel == CONTEXT_COURSE) {

                if (has_capability('enrol/demands:managecourseenrolment', $context) &&
                        is_enrolled($context)) {

                    $listenrols = $DB->get_records('enrol',
                            array('enrol' => 'demands', 'courseid' => $context->instanceid));

                    foreach ($listenrols as $enrol) {

                        if (!in_array ($enrol->id , $enrolids)) {

                            $enrolids[] = $enrol->id;
                        }
                    }
                }
            }
        }

        return $enrolids;
    }
}



