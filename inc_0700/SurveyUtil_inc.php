<?php
//SurveyUtil_inc.php
class SurveyUtil
{
	public static function responseList($myID) 
	{
		$myReturn = '';
		$sql = sprintf("select ResponseID, DateAdded from " . PREFIX . "responses where SurveyID =%d",$myID);
		
		$prev = '<img src="' . VIRTUAL_PATH . 'images/arrow_prev.gif" border="0" />';
		$next = '<img src="' . VIRTUAL_PATH . 'images/arrow_next.gif" border="0" />';

		# Create instance of new 'pager' class
		$myPager = new Pager(10,'',$prev,$next,'');
		$sql = $myPager->loadSQL($sql);  #load SQL, add offset

		# connection comes first in mysqli (improved) function
		$result = mysqli_query(IDB::conn(),$sql) or die(trigger_error(mysqli_error(IDB::conn()), E_USER_ERROR));

		if(mysqli_num_rows($result) > 0)
		{#records exist - process
			$myReturn .= '<p>And here is a list of responses to the survey:</p>';
			if($myPager->showTotal()==1){$itemz = "response";}else{$itemz = "responses";}  //deal with plural
			$myReturn .= '<div align="center">We have ' . $myPager->showTotal() . ' ' . $itemz . '!</div>
			<table border="true" align="center">
			<tr><th>ID/Link</th>
			<th>Date Taken</th></tr>
			';
			while($row = mysqli_fetch_assoc($result))
			{# process each row
				 $myReturn .= '<tr><td><a href="' . VIRTUAL_PATH . 'surveys/response_view.php?id=' . (int)$row['ResponseID'] . '">' . dbOut($row['ResponseID']) . '</a></td>
				 <td>' . dbOut($row['DateAdded']) . '</td></tr>';
			}
			$myReturn .= '</table>';
			$myReturn .= $myPager->showNAV(); # show paging nav, only if enough records	 
		}else{#no records
			$myReturn .= "<div align=center>No responses yet!</div>";
		}
		return $myReturn;
	}#end responseList()
}