<?php
/**
 * Survey_inc.php data access classes & other related code for SurveySez project
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
	 #v2: Array of questions changed from private to protected to accommodate Response() object
	 protected $aQuestion = Array();#stores an array of question objects
	
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
		
		#get Survey data from DB
		$sql = sprintf("select Title, Description from " . PREFIX . "surveys Where SurveyID =%d",$this->SurveyID);
		
		#in mysqli, connection and query are reversed!  connection comes first
		$result = mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		if (mysqli_num_rows($result) > 0)
		{#Must be a valid survey!
			$this->isValid = TRUE;
			while ($row = mysqli_fetch_assoc($result))
			{#dbOut() function is a 'wrapper' designed to strip slashes, etc. of data leaving db
			     $this->Title = dbOut($row['Title']);
			     $this->Description = dbOut($row['Description']);
			}
		}
		@mysqli_free_result($result); #free resources
		if(!$this->isValid){return;}  #exit, as Survey is not valid
		
		#attempt to create question objects
		$sql = sprintf("select QuestionID, Question, Description from " . PREFIX . "questions where SurveyID =%d",$this->SurveyID);
		$result = mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		if (mysqli_num_rows($result) > 0)
		{#show results
		   while ($row = mysqli_fetch_assoc($result))
		   {
				$this->TotalQuestions += 1; #increment total number of questions
				#Current TotalQuestions added to Question object as Number property - added in v2
				$this->aQuestion[] = new Question(dbOut($row['QuestionID']),dbOut($row['Question']),dbOut($row['Description']),$this->TotalQuestions);
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
}# end Survey class

class Question
{
	 public $QuestionID = 0;
	 public $Text = "";
	 public $Description = "";
	 public $aAnswer = Array();#stores an array of answer objects
	 public $TotalAnswers = 0;
	 public $Number = 0; # number of current question in sequence - added in v2
	/**
	 * Constructor for Question class. 
	 *
	 * @param integer $id ID number of question 
	 * @param string $question The text of the question
	 * @param string $description Additional description info
	 * @return void 
     * @todo none
	 */ 
	function __construct($id,$question,$description,$number)
	{#constructor sets stage by adding data to an instance of the object
		$this->QuestionID = (int)$id;
		$this->Text = $question;
		$this->Description = $description;
		$this->Number = $number; # number of current question in sequence - added in v2
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

class Answer
{
	 public $AnswerID = 0;
	 public $Text = "";
	 public $Description = "";
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