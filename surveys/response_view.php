<?php
/**
 * response_test.php shows an entire response, after it has been created
 * 
 * Will attempt to create a response 'object' to store user entered response data.
 *
 * This is a test page to prove the concept of storage of Response data, with  
 * internal Question, Answer and Choice object data
 * 
 * @package SurveySez
 * @author Bill Newman <williamnewman@gmail.com>
 * @version 2.1 2011/11/03
 * @link http://www.billnsara.com/advdb/  
 * @license http:// opensource.org/licenses/osl-3.0.php Open Software License ("OSL") v. 3.0
 * @see config_inc.php  
 * @todo none
 */

require '../inc_0700/config_inc.php'; #provides configuration, pathing, error handling, db credentials

/*
$config->metaDescription = ''; #Fills <meta> tags.
$config->metaKeywords = '';
$config->metaRobots = '';
$config->loadhead = ''; #load page specific JS
$config->banner = ''; #goes inside header
$config->copyright = ''; #goes inside footer
$config->sidebar1 = ''; #goes inside left side of page
$config->sidebar2 = ''; #goes inside right side of page
$config->nav1["page.php"] = "New Page!"; #add a new page to end of nav1 (viewable this page only)!!
$config->nav1 = array("page.php"=>"New Page!") + $config->nav1; #add a new page to beginning of nav1 (viewable this page only)!!
*/

# check variable of item passed in - if invalid data, forcibly redirect back to demo_list_pager.php page
if(isset($_GET['id']) && (int)$_GET['id'] > 0){#proper data must be on querystring
	 $myID = (int)$_GET['id']; #Convert to integer, will equate to zero if fails
}else{
	myRedirect("./index.php");
}

# currently 'hard wired' to one response - will need to pass in #id of a Response on the qstring  
$myResponse = new Response($myID);
if($myResponse->isValid)
{
	$PageTitle = "Response to the survey '" . $myResponse->Title . "'!";
}else{
	$PageTitle = THIS_PAGE; #use constant 
}
$config->titleTag = $PageTitle;
#END CONFIG AREA ---------------------------------------------------------- 

get_header(); # defaults to header_inc.php
?>
<h2><?php print $PageTitle; ?></h2>

<p>Here you go, have a delicious response to a survey!</p>
<?php

if($myResponse->isValid)
{# check to see if we have a valid SurveyID
	echo "Survey Title: <b><a href='" . VIRTUAL_PATH . "surveys/survey_view.php?id=" . $myResponse->SurveyID . "'>" . $myResponse->Title . "</a></b><br />";  # show data on page
	echo "Date Taken: " . $myResponse->DateTaken . "<br />";
	echo "Survey Description: " . $myResponse->Description . "<br />";
	echo "Number of Questions: " .$myResponse->TotalQuestions . "<br /><br />";
	echo $myResponse->showChoices() . "<br />";	# showChoices method shows all questions, and selected answers (choices) only!
	unset($myResponse);  # destroy object & release resources
}else{
	echo "Sorry, no such response!";	
}

get_footer(); #defaults to footer_inc.php

?>