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
 * Test helpers for the pycode question type.
 *
 * @package    qtype
 * @subpackage pycode
 * @copyright  2011 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the pycode question type.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pycode_test_helper extends question_test_helper {
    public function get_test_questions() {
        return array('sqr', 'helloFunc', 'copyStdin', 'timeout', 'exceptions');
    }

    /**
     * Makes a pycode question asking for a sqr() function
     * @return qtype_pycode_question
     */
    public function make_pycode_question_sqr() {
        question_bank::load_question_definition_classes('pycode');
        $pycode = new qtype_pycode_question();
        test_question_maker::initialise_a_question($pycode);
        $pycode->name = 'Function to square a number n';
        $pycode->questiontext = 'Write a function sqr(n) that returns n squared';
        $pycode->generalfeedback = 'No feedback available for pycode questions.';
        $pycode->testcases = array(
            (object) array('shellinput' => 'sqr(0)',
                          'output'     => '0',
                          'hidden'     => 0),
            (object) array('shellinput' => 'sqr(1)',
                          'output'     => '1',
                          'hidden'     => 0),
            (object) array('shellinput' => 'sqr(11)',
                          'output'     => '121',
                          'hidden'     => 0),
            (object) array('shellinput' => 'sqr(-7)',
                          'output'     => '49',
                          'hidden'     => 0),
            (object) array('shellinput' => 'sqr(-6)',  // The last testcase must be hidden
                          'output'     => '36',
                          'hidden'     => 1)
        );
        $pycode->qtype = question_bank::get_qtype('pycode');
        $pycode->unitgradingtype = 0;
        $pycode->unitpenalty = 0.2;
        return $pycode;
    }
    
    /**
     * Makes a pycode question to write a function that just print 'Hello <name>'
     * This test also tests multiline expressions.
     * @return qtype_pycode_question
     */
    public function make_pycode_question_helloFunc() {
        question_bank::load_question_definition_classes('pycode');
        $pycode = new qtype_pycode_question();
        test_question_maker::initialise_a_question($pycode);
        $pycode->name = 'Function to print hello to someone';
        $pycode->questiontext = 'Write a function sayHello(name) that prints "Hello <name>"';
        $pycode->generalfeedback = 'No feedback available for pycode questions.';
        $pycode->testcases = array(
            (object) array('shellinput' => 'sayHello("")',
                          'output'      => 'Hello ',
                          'hidden'      => 0),
            (object) array('shellinput' => 'sayHello("Angus")',
                          'output'      => 'Hello Angus',
                          'hidden'      => 0),
            (object) array('shellinput' => "name = 'Angus'\nsayHello(name)",
                          'output'      => 'Hello Angus',
                          'hidden'      => 0),
            (object) array('shellinput' => "name = 'Angus'\nname\nprint name\nsayHello(name)",
                          'output'      => "'Angus'\nAngus\nHello Angus",
                          'hidden'      => 0)
        );
        $pycode->qtype = question_bank::get_qtype('pycode');
        $pycode->unitgradingtype = 0;
        $pycode->unitpenalty = 0.2;
        return $pycode;
    }
    
    /**
     * Makes a pycode question to write a function that reads n lines of stdin
     * and writes them to stdout.
     * @return qtype_pycode_question
     */
    public function make_pycode_question_copyStdin() {
        question_bank::load_question_definition_classes('pycode');
        $pycode = new qtype_pycode_question();
        test_question_maker::initialise_a_question($pycode);
        $pycode->name = 'Function to copy n lines of stdin to stdout';
        $pycode->questiontext = 'Write a function copyLines(n) that reads n lines from stdin and writes them to stdout';
        $pycode->generalfeedback = 'No feedback available for pycode questions.';
        $pycode->testcases = array(
            (object) array('shellinput' => 'copyStdin(0)',
                          'stdin'       => '',
                          'output'      => '',
                          'hidden'      => 0),
            (object) array('shellinput' => 'copyStdin(1)',
                          'stdin'       => "Line1\nLine2\n",
                          'output'      => "Line1\n",
                          'hidden'      => 0),
            (object) array('shellinput' => 'copyStdin(2)',
                          'stdin'       => "Line1\nLine2\n",
                          'output'      => "Line1\nLine2\n",
                          'hidden'      => 0),
            (object) array('shellinput' => 'copyStdin(3)',
                          'stdin'       => "Line1\nLine2\n",
                          'output'      => "Line1\nLine2\n", # Irrelevant - runtime error
                          'hidden'      => 0)
        );
        $pycode->qtype = question_bank::get_qtype('pycode');
        $pycode->unitgradingtype = 0;
        $pycode->unitpenalty = 0.2;
        return $pycode;
    }
    
    /**
     * Makes a pycode question that loops forever, to test sandbox timeout.
     * @return qtype_pycode_question
     */
    public function make_pycode_question_timeout() {
        question_bank::load_question_definition_classes('pycode');
        $pycode = new qtype_pycode_question();
        test_question_maker::initialise_a_question($pycode);
        $pycode->name = 'Function to generate a timeout';
        $pycode->questiontext = 'Write a function that loops forever';
        $pycode->generalfeedback = 'No feedback available for pycode questions.';
        $pycode->testcases = array(
            (object) array('shellinput' => 'timeout()',
                          'stdin'       => '',
                          'output'      => '',
                          'hidden'      => 0)
        );
        $pycode->qtype = question_bank::get_qtype('pycode');
        $pycode->unitgradingtype = 0;
        $pycode->unitpenalty = 0.2;
        return $pycode;
    }
    
    
    /**
     * Makes a pycode question that requires students to write a function
     * that conditionally throws exceptions
     * @return qtype_pycode_question
     */
    public function make_pycode_question_exceptions() {
        question_bank::load_question_definition_classes('pycode');
        $pycode = new qtype_pycode_question();
        test_question_maker::initialise_a_question($pycode);
        $pycode->name = 'Function to conditionally throw an exception';
        $pycode->questiontext = 'Write a function isOdd(n) that throws and ValueError exception iff n is odd';
        $pycode->generalfeedback = 'No feedback available for pycode questions.';
        $pycode->testcases = array(
            (object) array('shellinput' => 'try:
  checkOdd(91)
  print "No exception"
except ValueError:
  print "Exception"',
                          'stdin'       => '',
                          'output'      => 'Exception',
                          'hidden'      => 0),
            (object) array('shellinput' => 'for n in [1, 11, 84, 990, 7, 8]:
  try:
     checkOdd(n)
     print "No"
  except ValueError:
     print "Yes"',
                          'stdin'       => '',
                          'output'      => "Yes\nYes\nNo\nNo\nYes\nNo\n",
                          'hidden'      => 0)                              
        );
        $pycode->qtype = question_bank::get_qtype('pycode');
        $pycode->unitgradingtype = 0;
        $pycode->unitpenalty = 0.2;
        return $pycode;
    }

   
}
