<?php
/**
 * Result_inc.php provides additional data access classes for the SurveySez project
 * 
 * This file requires Response_inc.php to access the Response & Choice classes
 *
 * This file requires Survey_inc.php to access the original Survey, Question & Answer classes
 * 
 * Data access for several of the SurveySez pages are handled via Survey classes 
 * named Survey,Question & Answer, respectively.  These classes model the one to many 
 * relationships between their namesake database tables. 
 *
 * Version 5 refactors the Result object and adds the showGraph() method to the 
 * Result class, to present a bar graph when showing results 
 *
 * Version 4 adds the capability to create a 'form' for taking a survey.  It also 
 * changes the question object to store the 'InputType', for example, 'radio'
 *
 * Version 3 introduces two new classes, the Result and Tally classes, and moderate 
 * changes to the existing classes, Survey, Question & Answer.  The Result class will 
 * inherit from the Survey Class (using the PHP extends syntax) and will be an elaboration 
 * on a theme.
  *
 * Version 2 introduces two new classes, the Response and Choice classes, and moderate 
 * changes to the existing classes, Survey, Question & Answer.  The Response class will 
 * inherit from the Survey Class (using the PHP extends syntax) and will be an elaboration 
 * on a theme.  
 *
 * An instance of the Response class will attempt to identify a SurveyID from the " . PREFIX . "responses 
 * database table, and if it exists, will attempt to create all associated Survey, Question & Answer 
 * objects, nearly exactly as the Survey object.
 *
 * @package SurveySez
 * @author William Newman
 * @version 2.0 2010/08/16
 * @link http://www.billnsara.com/advdb/  
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License ("OSL") v. 3.0
 * @see Survey_inc.php
 * @see Response_inc.php  
 * @see response_show.php 
 * @todo none
 */

/**
 * Result Class tallies response info for an individual Survey
 * 
 * The constructor of the Response class inherits all data from an instance of 
 * the Survey class.  As such it has access to all Question class and the Answer class 
 * info. 
 *
 * Properties of the Survey class like Title, Description and TotalQuestions provide 
 * summary information upon demand.
 * 
 * A result object (an instance of the Result class) can be created in this manner:
 *
 *<code>
 *$myResult = new Result(1);
 *</code>
 *
 * In which one is the number of a valid Survey in the database. 
 *
 * The showTallies() method of the Response object created will access an array of Tally 
 * objects and show the entire result of a Survey
 *
 * @see Survey
 * @see Question
 * @see Answer
 * @see Choice  
 * @todo none
 */

 class Result {
	public $aResponse = ""; # changed array to a single variable to store one response, tally obj removed - v5 
	public $TotalResponses = 0;  # total number of responses tabulated
	public $Title = "";
	public $Description = "";
	public $isValid = FALSE;
	
	/**
	 * Constructor for Response class. 
	 *
	 * @param integer $id ID number of Survey 
	 * @return void 
	 * @todo none
	 */ 
	function __construct($id)
	{
		$id = (int)$id; # explicit integer conversion forces zero if not numeric 
		if($id == 0){return FALSE;}
		$iConn = IDB::conn();

		# v5, now we only want one response!  (limit 0,1)
		$sql = sprintf("select ResponseID from " . PREFIX . "responses where SurveyID =%d",$id);
		$result = mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		if (mysqli_num_rows($result) > 0)
		{# returned a response!
		  $this->isValid = TRUE;
		  while ($row = mysqli_fetch_assoc($result))
		   {# load singular response object properties
			   # $this->TotalResponses += 1;  # REMOVED IN v5!
			   $this->aResponse = new Response((int)$row['ResponseID']); # v5: create one response object, not an array!
		   }
		}else{
		   return FALSE; #no results - abort
		}
		mysqli_free_result($result);
		# v5: Will use group by to load Tallies property of answer object - no longer needing a Tally object
		$sql = sprintf("select AnswerID, Count(*) as Tallies from " . PREFIX . "responses_answers where QuestionID in (select QuestionID from " . PREFIX . "questions where SurveyID = %d) group by AnswerID",$id);
		$result = mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		if (mysqli_num_rows($result) > 0)
		{//returned a response!
		  while ($row = mysqli_fetch_array($result))
		   {//load singular response object properties
			    $intAnswer = (int)$row['AnswerID'];
				foreach($this->aResponse->aQuestion as $question)

				{//loop through questions to reveal answers
					foreach($question->aAnswer as $answer)
					{//loop through to attach TotalChoices
						if($intAnswer == $answer->AnswerID)


						{//calculate tally
							$answer->Tallies = (int)$row['Tallies'];
							break;
						}
					}
				}
		   }
		}
		@mysqli_free_result($result);
		$this->Description = $this->aResponse->Description; #v5 - this info now from the single response, not array element
		$this->Title = $this->aResponse->Title;
		$this->TotalResponses = $this->aResponse->TotalResponses;	
		
	}# end Result Constructor
	
	/**
	 * Reveals Tallies in the the Tallies property of Answer object (v5)
	 *
	 * Uses the first response as the model for the questions & answers, since 
	 * all would match for one survey.
	 *
	 * Once the answers are reached, the AnswerID number of each Tally must be matched to 
	 * reveal the Total number of tallies per question.
	 *
	 * @param none
	 * @return string prints data from Answer object 
	 * @todo none
	 */ 
	function showTallies()
	{# v5: Only one response now - no calculations done here!
		foreach($this->aResponse->aQuestion as $question)
		{#loop through questions to reveal answers
			echo "<b>" . $question->Number . ") ";
			echo $question->Text . "</b> ";
			echo '<em>(' . $question->Description . ')</em> ';
			foreach($question->aAnswer as $answer)
			{#loop through to show totals for chosen answers (tallies)
				echo $answer->Text . ": <b>";
				echo $answer->Tallies . "</b> ";
			}
			echo "<br />";
		}
	}# end showTallies()
	
	/**
	 * Creates a bar graph from the number of tallies per answer
	 *
	 * Uses the response as the model for the questions & answers, since 
	 * all would match for one survey.
	 *
	 * Once the Answers are reached, the Tallies property is used to calculate  
	 * percentages, and then extend images that create a bar graph effect  
	 *
	 * @param none
	 * @return string echos data from Tallies Property 
	 * @todo none
	 */ 
	function showGraph()
	{# v5: creates a bar graph effect by effecting background color of divs of varying lengths
		$TotalPixels =  400;  # maximum width of bar graph
		$MyColors = "red,blue,green,black,purple,orange";
		$aColors = explode(",",$MyColors);
		foreach($this->aResponse->aQuestion as $question)
		{//loop through questions to reveal answers
			echo '<table border="0" style="border-collapse:collapse"><tr><td colspan="2">';
			echo "<b>" . $question->Number . ") ";
			echo $question->Text . "</b> ";
			echo '<em>(' . $question->Description . ')</em> </td></tr>';
			$TotalTallies = 0; //needed for percentile
			foreach($question->aAnswer as $answer)
			{# loop through to calculate total tallies for a given question
				$TotalTallies += $answer->Tallies;
			}
			$x=0; //counter
			foreach($question->aAnswer as $answer)
			{# loop through to show totals for chosen answers (tallies)
				echo '<tr><td><font color="' . $aColors[$x] . '"><b>' . $answer->Text . '</b> ';
				$myPercent = number_format(($answer->Tallies/$TotalTallies)*100,0);
				echo $myPercent . '% <em>(' . $answer->Tallies . ')</em></font></td>';
				echo '<td><div style="width:' . number_format(($answer->Tallies/$TotalTallies)*$TotalPixels,0) . 'px;height:8px;padding:2px;';
				echo 'background:url(' . VIRTUAL_PATH . 'images/2px' . $aColors[$x] . '.gif) left repeat"></div></td>';
				$x += 1;
				if($x >= count($aColors)){$x = 0;} //start at zero again
				echo "</tr>";
			}
			echo '<tr><td colspan="2">&nbsp;</td></tr></table>';	
		}
	}# end showGraph()
}# end Result() class
# tally class was removed here - v5
?>