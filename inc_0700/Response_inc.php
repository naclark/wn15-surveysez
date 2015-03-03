<?php
/**
 * Response_inc.php provides additional data access classes for the SurveySez project
 * 
 * This file requires Survey_inc.php to access the original Survey, Question & Answer classes
 * 
 * Data access for several of the SurveySez pages are handled via Survey classes 
 * named Survey,Question & Answer, respectively.  These classes model the one to many 
 * relationships between their namesake database tables. 
 *
 * Version 2 introduces two new classes, the Response and Choice classes, and moderate 
 * changes to the existing classes, Survey, Question & Answer.  The Response class will 
 * inherit from the Survey Class (using the PHP extends syntax) and will be an elaboration 
 * on a theme.  
 *
 * An instance of the Response class will attempt to identify a SurveyID from the srv_responses 
 * database table, and if it exists, will attempt to create all associated Survey, Question & Answer 
 * objects, nearly exactly as the Survey object.
 *
 * @package SurveySez
 * @author William Newman
 * @version 2.0 2010/07/31
 * @link http://www.billnsara.com/advdb/  
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License ("OSL") v. 3.0
 * @see Survey_inc.php 
 * @see response_show.php 
 * @todo none
 */

/**
 * Response Class retrieves response info for an individual Survey
 * 
 * The constructor of the Response class inherits all data from an instance of 
 * the Survey class.  As such it has access to all Question class and the Answer class 
 * info. 
 *
 * Properties of the Survey class like Title, Description and TotalQuestions provide 
 * summary information upon demand.
 * 
 * A response object (an instance of the Response class) can be created in this manner:
 *
 *<code>
 *$myResponse = new Response(1);
 *</code>
 *
 * In which one is the number of a valid Response in the database. 
 *
 * The showChoices() method of the Response object will access an array of choice 
 * objects and only show answers to questions that match
 *
 * @see Survey
 * @see Question
 * @see Answer
 * @see Choice  
 * @todo none
 */
class Response extends Survey
{
	public $ResponseID = 0; # unique ID number of current response
	public $DateTaken = ""; # Date Survey was taken
	public $SurveyID = 0;
	public $isValid = FALSE;
	public $aChoice = Array(); # stores an array of choice objects
	
	/**
	 * Constructor for Response class. 
	 *
	 * @param integer $id ID number of Response 
	 * @return void 
	 * @todo none
	 */ 
    function __construct($id)
	{
		$this->ResponseID = (int)$id;
		if($this->ResponseID == 0){return FALSE;} # invalid response id - abort
		$iConn = IDB::conn(); # uses a singleton DB class to create a mysqli improved connection

		$sql = sprintf("select SurveyID, DateAdded from " . PREFIX . "responses where ResponseID =%d",$this->ResponseID);
		$result = mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		if (mysqli_num_rows($result) > 0)
		{# returned a response!
		  while ($row = mysqli_fetch_array($result))
		   {# load singular response object properties
			   $this->SurveyID = (int)$row['SurveyID'];
			   $this->DateTaken = dbOut($row['DateAdded']);
		   }
		}else{
			return FALSE; #no responses - abort	
		}
		mysqli_free_result($result);
		parent::__construct($this->SurveyID); # access parent class to build Question & Answers

		# attempt to load choice array of Answer objects
		if($this->TotalQuestions > 0)
		{# Questions must exist for this survey, if we are to proceed
			$sql = sprintf("select AnswerID, QuestionID, RQID from " . PREFIX . "responses_answers where ResponseID=%d order by QuestionID asc",$this->ResponseID);
			$result = mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
			if (mysqli_num_rows($result) > 0)
			{# must be choices
			   while ($row = mysqli_fetch_array($result))
			   {# load data into array of choices
				   $this->aChoice[] = new Choice((int)$row['AnswerID'],(int)$row['QuestionID'],(int)$row['RQID']); 
			   }
			@mysqli_free_result($result);
			}
		}
	}# End Response Constructor
	
	/**
	 * Reveals choices in the internal Array of Choice Objects
	 *
	 * The choice array identifies chosen ID numbers from answers.  
	 * This function will echo only the chosen answers, not those unchosen. 
	 *
	 * @param none
	 * @return string prints data from Choice Array 
	 * @todo none
	 */ 
	function showChoices()
	{
		foreach($this->aQuestion as $question)
		{# loop through questions to reveal answers
			echo "<b>" . $question->Number . ") ";
			echo $question->Text . "</b> ";
			echo '<em>(' . $question->Description . ')</em> ';
			foreach($question->aAnswer as $answer)
			{# loop through answers to see if chosen
				foreach($this->aChoice as $choice)
				{# loop through all choices to see if matches current answer
					if($answer->AnswerID == $choice->AnswerID)
					{# only show answers that are chosen
						echo '<b>' . $answer->Text . "</b> ";
						break;	
					}
				}
			}
			echo "<br />"; # break after each question/choices
		}
	}#End showChoices() method
}#End Response class

/**
 * Choice Class stores data info for an individual Choice to an Answer
 * 
 * In the constructor an instance of the Response class creates multiple 
 * instances of the Choice class tacked to the Answer class to store 
 * response data.
 *
 * @see Answer
 * @see Response 
 * @todo none
 */
class Choice {
	public $AnswerID = 0; # ID of associated answer
	public $QuestionID = 0; # ID of associated question
	public $ChoiceID = 0; # ID of individual choice
	
	/**
	 * Constructor for Choice class. 
	 *
	 * @param integer $AnswerID ID number of associated answer 
	 * @param integer $QuestionID ID number of associated question
	 * @param integer $RQID ID number of choice from srv_response_question table
	 * @return void 
	 * @todo none
	 */ 
    function __construct($AnswerID,$QuestionID,$RQID)
	{# constructor sets stage by adding data to an instance of the object
		$this->AnswerID = (int)$AnswerID;
		$this->QuestionID = (int)$QuestionID;
		$this->ChoiceID = (int)$RQID;

	}#End Choice constructor
}#End Choice class

?>