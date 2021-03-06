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
 * progcode question definition classes.
 *
 * @package    qtype
 * @subpackage progcode
 * @copyright  Richard Lobb, 2011, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * Useful SQL to see what's happening in the DB during development.
 * Written here simply because I wanted to write it down somewhere and why
 * not here?

select q.id as qid, qa.id as qaid, qas.id as qasid, state, qasd.name, value
from mdl_question as q,
          mdl_question_attempts as qa,
          mdl_question_attempt_steps as qas,
          mdl_question_attempt_step_data as qasd
where qa.questionid = q.id
and    qas.questionattemptid = qa.id
and    qasd.attemptstepid = qas.id
order by q.id, qa.id, qas.id, qasd.id

 */


defined('MOODLE_INTERNAL') || die();

/** Max size of output to be stored in question_attempt_step_data table
 *  (which is of type text so limited to 64k).
 */
define('MAX_OUTPUT_LENGTH', 60000);

define('FUNC_MIN_LENGTH', 10);  /* Minimum no. of bytes for a valid bit of code */

require_once($CFG->dirroot . '/question/behaviour/adaptive/behaviour.php');
require_once($CFG->dirroot . '/question/engine/questionattemptstep.php');
require_once($CFG->dirroot . '/question/behaviour/adaptive_adapted_for_progcode/behaviour.php');

/* Use the onlinejudge assignment module
 * (http://code.google.com/p/sunner-projects/wiki/OnlineJudgeAssignmentType)
 * to provide the judge class and the sandboxing (thought the latter
 * is a separate project -- see http://sourceforge.net/projects/libsandbox/).
 */

/**
 * Represents a ProgramCode 'progcode' question.
 */
abstract class qtype_progcode_question extends question_graded_automatically {

    public $testcases;    // Array of testcases

    /**
     * Override default behaviour so that we can use a specialised behaviour
     * that caches test results returned by the call to grade_response().
     *
     * @param question_attempt $qa the attempt we are creating an behaviour for.
     * @param string $preferredbehaviour the requested type of behaviour.
     * @return question_behaviour the new behaviour object.
     */
    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        // TODO: see if there's some way or issuing a warning message when
        // progcode questions aren't being used in an adaptive mode.

        if ($preferredbehaviour == 'adaptive') {
            return  new qbehaviour_adaptive_adapted_for_progcode($qa, $preferredbehaviour);
        }
        else {
            return parent::make_behaviour($qa, $preferredbehaviour);
        }
    }


    public function get_expected_data() {
        return array('answer' => PARAM_RAW, 'rating' => PARAM_INT);
    }


    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }

    public function is_gradable_response(array $response) {
        return array_key_exists('answer', $response) &&
                !empty($response['answer']) &&
                strlen($response['answer']) > FUNC_MIN_LENGTH;
    }

    public function is_complete_response(array $response) {
        return $this->is_gradable_response($response);
    }


    /**
     * In situations where is_gradable_response() returns false, this method
     * should generate a description of what the problem is.
     * @return string the message.
     */
    public function get_validation_error(array $response) {
        return get_string('answerrequired', 'qtype_pycode');
    }


    public function is_same_response(array $prevresponse, array $newresponse) {
        if (!question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer')
            || !question_utils::arrays_same_at_key_integer(
                $prevresponse, $newresponse, 'rating')) {
            return false;
        }
        return true;
    }



    public function get_correct_response() {
        return $this->get_correct_answer();
    }


    public function get_correct_answer() {
        // Allow for the possibility in the future of providing a sample answer
        return isset($this->answer) ? array('answer' => $this->answer) : array();
    }


    // Grade the given 'response'.
    // This implementation assumes a modified behaviour that will accept a
    // third array element in its response, containing data to be cached and
    // served up again in the response on subsequent calls.
    // It will still work with an unmodified behaviour but will be very
    // inefficient as multiple regradings will occur.

    public function grade_response(array $response) {
        if (empty($response['_testresults'])) {
            // debugging('Running ProgramCode tests');
            $code = $response['answer'];
            $testResults = $this->run_tests($code, $this->testcases);
            $this->clean(&$testResults);  // Truncate if nec to fit in DB
            $testResultsSerial = serialize($testResults);
        }
        else {
            $testResultsSerial = $response['_testresults'];
            $testResults = unserialize($testResultsSerial);
        }

        $dataToCache = array('_testresults' => $testResultsSerial);
        if ($this->count_errors($testResults) != 0) {
            return array(0, question_state::$gradedwrong, $dataToCache);
        }
        else {
            return array(1, question_state::$gradedright, $dataToCache);
        }
    }

    // Check the correctness of a student's code given the
    // response and and a set of testCases.
    // Must be implemented by subclasses.
    // Return value is an array of test-result objects.
    // If an error occurs, all further tests are aborted so the returned array may be shorter
    // than the input array
    abstract protected function run_tests($code, $testcases);


    // Count the number of errors in the given array of test results.
    // TODO -- figure out how to eliminate either this one or the identical
    // version in renderer.php.
    private function count_errors($testResults) {
        $errors = 0;
        foreach ($testResults as $tr) {
            if (!$tr->isCorrect) {
                $errors++;
            }
        }
        return $errors;
    }


    /** clean: support method to process the results returned by the
    *  testrunner so it doesn't bust the size limit on data in the
    *  questionAttemptStep table.
    */
    private function clean(&$testResults) {
        $n = count($testResults);
        for($i = 0; $i < $n; $i++) {
            $this->cleanTestResult(&$testResults[$i]);
        }
    }

    private function cleanTestResult(&$tr) {
        $output = $tr->output;
        $suffix = "\n...[snip]...\n";
        $maxLen = MAX_OUTPUT_LENGTH - strlen($suffix);
        if (strlen($output) > $maxLen) {
            $tr->output = substr($output, 0, $maxLen) . $suffix;
        }
    }

}



