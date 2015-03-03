<?php
/**
 * Survey_inc.php data access classes & other related code for SurveySez project
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
 * Version 3 introduces the Result class, and Tally class, which together allow us to 
 * display all results (totaled responses) for a single Survey.
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
 * @version 2.0 2010/08/16
 * @link http://www.billnsara.com/advdb/  
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License ("OSL") v. 3.0
 * @see survey_view.php
 * @see response_view.php 
 */
 
/**
 * Survey Class retrieves data info for an individual Survey
 * 
 * The constructor an instance of the Survey class creates multiple instances of the 
 * Question class and the Answer class to store questions & answers data from the DB.
 *
 * Properties of the Survey class like Title, Description and TotalQuestions provide 
 * summary information upon demand.
 * 
 * A survey object (an instance of the Survey class) can be created in this manner:
 *
 *<code>
 *$mySurvey = new Survey(1);
 *</code>
 *
 * In which one is the number of a valid Survey in the database. 
 *
 * The showQuestions() method of the Survey object created will access an array of question 
 * objects and internally access a method of the Question class named showAnswers() which will 
 * access an array of Answer objects to produce the visible data.
 *
 * @see Question
 * @see Answer 
 * @todo none
 */
 
class Survey
{
	 public $SurveyID = 0;
	 public $Title = "";
	 public $Description = "";
	 public $isValid = FALSE;
	 public $TotalQuestions = 0; #stores number of questions
	 public $TotalResponses = 0; # v5: stores number of responses	 
	 //protected $aQuestion = Array(); #stores an array of question objects - changed to protected in v2
	 public $aQuestion = Array(); #stores an array of question objects - changed to public in v5

	/**
	 * Constructor for Survey class. 
	 *
	 * @param integer $id The unique ID number of the Survey
	 * @return void 
	 * @todo none
	 */ 
    function __construct($id)
	{#constructor sets stage by adding data to an instance of the object
		$this->SurveyID = (int)$id;
		if($this->SurveyID == 0){return FALSE;}
		$iConn = IDB::conn(); #uses a singleton DB class to create a mysqli improved connection 
		
		#get Survey data from DB - v5 adds TotalResponses
		$sql = sprintf("select Title, Description, TotalResponses from " . PREFIX . "surveys Where SurveyID =%d",$this->SurveyID);
		
		#in mysqli, connection and query are reversed!  connection comes first
		$result = mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		if (mysqli_num_rows($result) > 0)
		{#Must be a valid survey!
			$this->isValid = TRUE;
			while ($row = mysqli_fetch_assoc($result))
			{#dbOut() function is a 'wrapper' designed to strip slashes, etc. of data leaving db
			     $this->Title = dbOut($row['Title']);
			     $this->Description = dbOut($row['Description']);
		         $this->TotalResponses = (int)$row['TotalResponses']; # v5: stores number of responses			     
			}
		}
		@mysqli_free_result($result); #free resources
		if(!$this->isValid){return;}  #exit, as Survey is not valid
		
		#attempt to create question objects - InputType field is v4
		$sql = sprintf("select QuestionID, Question, Description, InputType from " . PREFIX . "questions where SurveyID =%d",$this->SurveyID);
		$result = mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		if (mysqli_num_rows($result) > 0)
		{#show results
		   while ($row = mysqli_fetch_assoc($result))
		   {
				$this->TotalQuestions += 1; #increment total number of questions
				#Current TotalQuestions added to Question object as Number property - added in v2 - InputType v4
				$this->aQuestion[] = new Question(dbOut($row['QuestionID']),dbOut($row['Question']),dbOut($row['Description']),$this->TotalQuestions,dbOut($row['InputType']));
		   }
		}
		$this->TotalQuestions = count($this->aQuestion); #TotalQuestions derived above - consider deleting this line!  v2 
		@mysqli_free_result($result); #free resources
		
		#attempt to load all Answer objects into cooresponding Question objects 
	    $sql = "select a.AnswerID, a.Answer, a.Description, a.QuestionID from  
		" . PREFIX . "surveys s inner join " . PREFIX . "questions q on q.SurveyID=s.SurveyID 
		inner join " . PREFIX . "answers a on a.QuestionID=q.QuestionID   
		where s.SurveyID = %d   
		order by a.AnswerID asc";
		$sql = sprintf($sql,$this->SurveyID); #process SQL
		$result = mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		if (mysqli_num_rows($result) > 0)
		{#at least one answer!
		   while ($row = mysqli_fetch_assoc($result))
		   {#match answers to questions
			    $QuestionID = (int)$row['QuestionID']; #process db var
				foreach($this->aQuestion as $question)
				{#Check db questionID against Question Object ID
					if($question->QuestionID == $QuestionID)
					{
						$question->TotalAnswers += 1;  #increment total number of answers
						#create answer, and push onto stack!
						$question->aAnswer[] = new Answer((int)$row['AnswerID'],dbOut($row['Answer']),dbOut($row['Description']));
						break; 
					}
				}	
		   }
		}
	}# end Survey() constructor
	
	/**
	 * Reveals questions in internal Array of Question Objects 
	 *
	 * @param none
	 * @return string prints data from Question Array 
	 * @todo none
	 */ 
	function showQuestions()
	{
		if($this->TotalQuestions > 0)
		{#be certain there are questions
			foreach($this->aQuestion as $question)
			{#print data for each 
				echo $question->Number . ') '; # We're using new Number property instead of id - v2
				echo $question->Text . ' ';
				if($question->Description != ''){echo '(' . $question->Description . ')';}
				echo '<br />';
				$question->showAnswers() . '<br />'; #display array of answer objects
			}
		}else{
			echo 'There are currently no questions for this survey.';	
		}
	}# end showQuestions() method
	
	/**
	 * Allows read only access to Question Array
	 * 
	 * Added in v3 - Result object
	 *
	 * @param none
	 * @return array an array of Question objects 
	 * @todo none
	 */ 
	function getQuestions()
	{
		return $this->aQuestion;
	}# end getQuestions() method
	
		/**
	 * Creates form for taking survey 
	 *
	 * @param none
	 * @return none, prints form on page 
	 * @todo none
	 */ 
	function Form()
	{
		print '<form name="myform" action="' . THIS_PAGE . '" method="post">';
		foreach($this->aQuestion as $question)
		{//print data for each
			$this->createInput($question);
		}
		print '<input type="hidden" name="SurveyID" value="' . $this->SurveyID . '" />';	
		print '<input type="submit" value="Submit!" />';	
		print '</form>';
	}
	
	/**
	 * Passes in a question to add input form objects 
	 * to allow data insertion 
	 *
	 * @param none
	 * @return none, prints form objects on page 
	 * @todo none
	 */
	private function createInput($question)
	{
		switch($question->InputType)
		{
			case "radio":
			case "checkbox":
				print "<b>" . $question->Number . ") ";
				print $question->Text . "</b> ";
				print '<em>(' . $question->Description . ')</em><br />';
				foreach($question->aAnswer as $answer)
				{//print data for each
					print '<input type="' . $question->InputType . '" name="q_' . $question->QuestionID . '[]" value="' . $answer->AnswerID . '" > ';
					print $answer->Text . " ";
					if($answer->Description != "")
					{//only print description if not empty
						print "<em>(" . $answer->Description . ")</em>";
					}
					print '<br />';	
				}
				break;	
		
			case "select":
				print "<b>" . $question->Number . ") ";
				print $question->Text . "</b> ";
				print '<em>(' . $question->Description . ')</em><br />';
				print '<select name="q_' . $question->QuestionID . '">';
				foreach($question->aAnswer as $answer)
				{//print data for each
					print '<option value="' . $answer->AnswerID . '" >' . $answer->Text;
					if($answer->Description != "")
					{//only print description if not empty
						print " <em>(" . $answer->Description . ")</em>";
					}
					print '</option>';	
				}
				print '</select><br />';
				break;
		}				
	}
	
	/**
	 * responseCount() updates a number of matches in another table
	 * @param int $SurveyID Survey being taken
	 */
	public static function responseCount($SurveyID)
	{
		$SurveyID = (int)$SurveyID; //cast to integer
		if($SurveyID > 0)
		{//now no SQL if number not above zero
			$iConn = IDB::conn(); 
			$rowsql = "select count(*) as numrows from " . PREFIX . "responses where SurveyID=" .  $SurveyID;
			$result  = mysqli_query($iConn,$rowsql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
			$row     = mysqli_fetch_assoc($result) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
			$numrows = $row['numrows'];
			$sql = "update " . PREFIX . "surveys set TotalResponses=" . $numrows . " where SurveyID=" . $SurveyID;
			mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		}
	}
	
	/**
	 * insertSurvey() inserts data as entered by public via form() method
	 */
	public static function insertSurvey()
	{
		if(isset($_POST['SurveyID']) && (is_numeric($_POST['SurveyID'])))
		{//insert response!
			$iConn = IDB::conn();
			// turn off auto-commit
			mysqli_autocommit($iConn, FALSE);
			//insert response
			$sql = sprintf("INSERT into " . PREFIX . "responses(SurveyID,DateAdded) VALUES ('%d',NOW())",$_POST['SurveyID']);
			$result = @mysqli_query($iConn,$sql); //moved or die() below!
			
			if(!$result)
			{// if error, roll back transaction
				mysqli_rollback($iConn);
				die(trigger_error("Error Entering Response: " . mysqli_error($iConn), E_USER_ERROR));
			}  
			
			//retrieve responseid
			$ResponseID = mysqli_insert_id($iConn); //get ID of last record inserted
			
			if(!$result)
			{// if error, roll back transaction
				mysqli_rollback($iConn);
				die(trigger_error("Error Retrieving ResponseID: " . mysqli_error($iConn), E_USER_ERROR));
			} 
	
			//loop through and insert answers
			foreach($_POST as $varName=> $value)
			{//add objects to collection
				 $qTest = substr($varName,0,2);  //check for "obj_" added to numeric type
				 if($qTest=="q_")
				 {//add choice!
				 	$QuestionID = substr($varName,2); //identify question
				 	
				 	if(is_array($_POST[$varName]))
				 	{//checkboxes are arrays, and we need to loop through each checked item to insert
					 	while (list ($key,$value) = @each($_POST[$varName])){
						 	$sql = "insert into " . PREFIX . "responses_answers(ResponseID,QuestionID,AnswerID) values($ResponseID,$QuestionID,$value)";
					  		$result = @mysqli_query($iConn,$sql);
					  		if(!$result)
							{// if error, roll back transaction
								mysqli_rollback($iConn);
								die(trigger_error("Error Inserting Choice (array/checkbox): " . mysqli_error($iConn), E_USER_ERROR));
							} 
						}
			 		}else{//not an array, so likely radio or select
				 		$sql = "insert into " . PREFIX . "responses_answers(ResponseID,QuestionID,AnswerID) values($ResponseID,$QuestionID,$value)";
				  	    $result = @mysqli_query($iConn,$sql);
				  	    if(!$result)
						{// if error, roll back transaction
							mysqli_rollback($iConn);
							die(trigger_error("Error Inserting Choice (single/radio): " . mysqli_error($iConn), E_USER_ERROR));
						} 
			 		}
				 }
			}
			//we got this far, lets COMMIT!
			mysqli_commit($iConn);
			
			// our transaction is over, turn autocommit back on
			mysqli_autocommit($iConn, TRUE);
			
			//count total responses, update TotalResponses
			self::responseCount((int)$_POST['SurveyID']);  //convert to int on way in!
			return TRUE;  #
		}else{
			return FALSE;	
		}

	}# End insertSurvey() method
}# end Survey class

/**
 * Question Class stores data info for an individual Question
 * 
 * In the constructor an instance of the Survey class creates multiple 
 * instances of the Question class and the Answer class to store 
 * questions & answers.
 *
 * @see Survey
 * @see Answer 
 * @todo none
 */
class Question
{
	 public $QuestionID = 0;
	 public $Text = "";
	 public $Description = "";
	 public $aAnswer = Array();#stores an array of answer objects
	 public $TotalAnswers = 0;
	 public $Number = 0; # number of current question in sequence - added in v2
	public $InputType = ""; # radio, checkbox, etc. - added in v4
	/**
	 * Constructor for Question class. 
	 *
	 * @param integer $id ID number of question 
	 * @param string $question The text of the question
	 * @param string $description Additional description info
	 * @return void 
     * @todo none
	 */ 
	function __construct($id,$question,$description,$number,$inputtype)
	{#constructor sets stage by adding data to an instance of the object
		$this->QuestionID = (int)$id;
		$this->Text = $question;
		$this->Description = $description;
		$this->Number = (int)$number; # number of current question in sequence - added in v2
		$this->InputType = $inputtype; #added v4
	}# end Question() constructor
	
	/**
	 * Reveals answers in internal Array of Answer Objects 
	 * for each question 
	 *
	 * @param none
	 * @return string prints data from Answer Array 
	 * @todo none
	 */ 
	function showAnswers()
	{
		if($this->TotalAnswers != 1){$s = 's';}else{$s = '';} #add 's' only if NOT one!!
		echo "<em>[" . $this->TotalAnswers . " answer" . $s . "]</em> "; 
		foreach($this->aAnswer as $answer)
		{#print data for each
			echo "<em>(" . $answer->AnswerID . ")</em> ";
			echo $answer->Text . " ";
			if($answer->Description != "")
			{#only print description if not empty
				echo "<em>(" . $answer->Description . ")</em>";
			}
		}
		print "<br />";
	}#end showAnswers() method
}# end Question class

/**
 * Answer Class stores data info for an individual Answer
 * 
 * In the constructor an instance of the Survey class creates multiple 
 * instances of the Question class and the Answer class to store 
 * questions & answers.
 *
 * @see Question 
 * @todo none
 */
class Answer
{
	public $AnswerID = 0;
	public $Text = "";
	public $Description = "";
	public $Tallies = 0; # v5: stores number of times an answer was selected	

	/**
	 * Constructor for Answer class. 
	 *
	 * @param integer $AnswerID ID number of answer 
	 * @param string $Text The text of the answer
	 * @param string $Description Additional description info
	 * @return void 
	 * @todo none
	 */ 
    function __construct($AnswerID,$answer,$description)
	{#constructor sets stage by adding data to an instance of the object
		$this->AnswerID = (int)$AnswerID;
		$this->Text = $answer;
		$this->Description = $description;
	}#end Answer() constructor
}#end Answer class

?>