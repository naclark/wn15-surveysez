<?php
/**
 * survey_view.php works with survey_list.php (index.php here) to create a list/view app
 * 
 * Based on demo_list_pager.php along with demo_view_pager.php (sample web application)
 *
 * The difference between demo_list.php and demo_list_pager.php is the reference to the 
 * Pager class which processes a mysqli SQL statement and spans records across multiple  
 * pages. 
 *
 * The associated view page, demo_view_pager.php is virtually identical to demo_view.php. 
 * The only difference is the pager version links to the list pager version to create a 
 * separate application from the original list/view. 
 * 
 * @package SurveySez
 * @author Nick Clark <n.alexander.clark@gmail.com>
 * @version 1.0 2015/02/03
 * @link http://www.stoneseas.com/itc250
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License ("OSL") v. 3.0
 * @see survey_list.php
 * @see index.php
 * @see Pager_inc.php
 */

# '../' works for a sub-folder.  use './' for the root  
require '../inc_0700/config_inc.php'; #provides configuration, pathing, error handling, db credentials
 
# check variable of item passed in - if invalid data, forcibly redirect back to demo_list_pager.php page
if(isset($_GET['id']) && (int)$_GET['id'] > 0){#proper data must be on querystring
	 $myID = (int)$_GET['id']; #Convert to integer, will equate to zero if fails
}else{
	myRedirect("./index.php");
}
$mySurvey = new Survey($myID);
if($mySurvey->isValid)
{
	$config->titleTag = "A survey called '" .$mySurvey->Title . "'!";
}else{//no such survey exists
	$config->titleTag = "No such survey exists!";
}
//dumpDie($mySurvey);

get_header(); #defaults to theme header or header_inc.php

echo '
<h3 align="center">' . $config->titleTag . '</h3>
	 ';

if($mySurvey->isValid)
{ #check to see if we have a valid SurveyID
	echo 
	'<p>Here is the Survey\'s description: </p>
	<p>'.$mySurvey->Description . '</p>';
	$mySurvey->showQuestions();
    echo SurveyUtil::responseList($myID);
	$myResult = new Result($myID);
	if($myResult->isValid) {
		echo '<h3 align="center">Behold, the tallied responses!</h3>';
		$myResult->showGraph() . "<br />";	//showTallies method shows all questions, answers and tally totals!
		unset($myResult);
	}
	//No "else" needed. If there's no responseList, then it'll say "No responses yet!" anyways.
}else{
	echo "Sorry, no such survey!";	
}
echo '<p><a href="index.php">Back to survey list!</a></p>';
get_footer(); #defaults to theme footer or footer_inc.php

