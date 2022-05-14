<?php
/*
 *********************************************************************************************************
 * daloRADIUS - RADIUS Web Platform
 * Copyright (C) 2007 - Liran Tal <liran@enginx.com> All Rights Reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *********************************************************************************************************
 *
 * Authors:	Liran Tal <liran@enginx.com>
 *
 *********************************************************************************************************
 */

    include ("library/checklogin.php");
    $operator = $_SESSION['operator_user'];

	include('library/check_operator_perm.php');

	isset($_GET['username']) ? $username = $_GET['username'] : $username = "";
	isset($_GET['csvdata']) ? $username = explode("\n",($_GET['csvdata'])) : $csvdata = ""; //***********edit***********
	isset($_GET['attribute']) ? $attribute = $_GET['attribute'] : $attribute = "";
	isset($_GET['tablename']) ? $tablename = $_GET['tablename'] : $tablename = "";
	isset($_GET['delradacct']) ? $delradacct = $_GET['delradacct'] : $delradacct = "";
	isset($_GET['clearSessionsUsers']) ? $clearSessionsUsers = $_GET['clearSessionsUsers'] : $clearSessionsUsers = "";

	$logAction = "";
	$logDebugSQL = "";
	
	$showRemoveDiv = "block";

	if ( (isset($_GET['username'])) && (!(isset($_GET['attribute']))) && (!(isset($_GET['tablename']))) || (isset($_GET['csvdata'])) ) {
		$allUsernames = "";
		$isSuccessful = 0;

		/* since the foreach loop will report an error/notice of undefined variable $value because
		   it is possible that the $username is not an array, but rather a simple GET request
		   with just some value, in this case we check if it's not an array and convert it to one with
		   a NULL 2nd element
		*/
		include 'library/opendb.php';

		if (!is_array($username))
			$username = array($username, NULL);
		
		//edit array if array has mail
		$i=0;
		$arr= array();
		foreach ($username as $variable=>$value) {
			$username = trim($value);
			$username = str_replace("\\r", "", $username);
			$username = str_replace("\\n", "", $username);
			if($username!=NULL){
				$allUsernames .= $username . ", ";
			}
			//get mac address from mail
			if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
				$sql = "SELECT USERNAME FROM ".$configValues['CONFIG_DB_TBL_DALOUSERINFO'].' WHERE firstname="'.$dbSocket->escapeSimple($username).'"';
				$res = $dbSocket->query($sql);
				
				while($row = $res->fetchRow()) {
						$arr[$i]=$row[0];
						$i++;
				};
			}else{
				$arr[$i]=$username;
				$i++;
			}		
		}
		
		//Array NULL
		if(!$arr){
                        $arr[$i]=NULL;
                }
		
		$username=$arr;
		
		//delete user
		foreach ($username as $variable=>$value) {

			if (trim($variable) != "") {

				$username = $value;
				//$allUsernames .= $username . ", "; 
				//echo $username."</br>";
				

				// setting table-related parameters first
				switch($configValues['FREERADIUS_VERSION']) {
					case '1' :
						$tableSetting['postauth']['user'] = 'user';
						$tableSetting['postauth']['date'] = 'date';
						break;
					case '2' :
						// down
					case '3' :
						// down
					default  :
						$tableSetting['postauth']['user'] = 'username';
						$tableSetting['postauth']['date'] = 'authdate';
						break;
				}
				
				// delete all attributes associated with a username
				//get id
				$sql = "SELECT ID,USERNAME FROM ".$configValues['CONFIG_DB_TBL_DALOUSERINFO'];
				$res = $dbSocket->query($sql);
				while($row = $res->fetchRow()) {
						if($row[1]==$username){
							$userid=$row[0]+2;
						}
				};
				//delete groupcheck
				$sql = "DELETE FROM ".$configValues['CONFIG_DB_TBL_RADGROUPCHECK']." WHERE id='".$dbSocket->escapeSimple($userid)."'";
				$res = $dbSocket->query($sql);
				$logDebugSQL .= $sql . "\n";

				//delete radcheck
				$sql = "DELETE FROM ".$configValues['CONFIG_DB_TBL_RADCHECK']." WHERE Username='".$dbSocket->escapeSimple($username)."'";
				$res = $dbSocket->query($sql);
				$logDebugSQL .= $sql . "\n";

				//delete radrepy
				$sql = "DELETE FROM ".$configValues['CONFIG_DB_TBL_RADREPLY']." WHERE Username='".$dbSocket->escapeSimple($username)."'";
				$res = $dbSocket->query($sql);
				$logDebugSQL .= $sql . "\n";

				//delete userinfo
				$sql = "DELETE FROM ".$configValues['CONFIG_DB_TBL_DALOUSERINFO']." WHERE Username='".$dbSocket->escapeSimple($username)."'";
				$res = $dbSocket->query($sql);
				$logDebugSQL .= $sql . "\n";

				//delete userbillinfo
				$sql = "DELETE FROM ".$configValues['CONFIG_DB_TBL_DALOUSERBILLINFO']." WHERE Username='".$dbSocket->escapeSimple($username)."'";
				$res = $dbSocket->query($sql);
				$logDebugSQL .= $sql . "\n";

				//delete usergroup
				$sql = "DELETE FROM ".$configValues['CONFIG_DB_TBL_RADUSERGROUP']." WHERE Username='".$dbSocket->escapeSimple($username)."'";
				$res = $dbSocket->query($sql);
				$logDebugSQL .= $sql . "\n";

				//delete posauth
				$sql = "DELETE FROM ".$configValues['CONFIG_DB_TBL_RADPOSTAUTH']." WHERE ".
					$tableSetting['postauth']['user']."='".$dbSocket->escapeSimple($username)."'";
				$res = $dbSocket->query($sql);
				$logDebugSQL .= $sql . "\n";

				if (strtolower($delradacct) == "yes") {
					$sql = "DELETE FROM ".$configValues['CONFIG_DB_TBL_RADACCT']." WHERE Username='".$dbSocket->escapeSimple($username)."'";
					$res = $dbSocket->query($sql);
					$logDebugSQL .= $sql . "\n";
				}
				
				$successMsg = "Deleted user(s): <b> $allUsernames </b>";
				$logAction .= "Successfully deleted user(s) [$allUsernames] on page: ";

				

			}  else {
				$failureMsg = "no user was entered, please specify a username to remove from database";
				$logAction .= "Failed deleting user(s) [$allUsernames] on page: ";
			}


		$showRemoveDiv = "none";

		} //foreach
		include 'library/closedb.php';

	} else 	if ( (isset($_GET['username'])) && (isset($_GET['attribute'])) && (isset($_GET['tablename'])) ) {

		/* this section of the deletion process only deletes the username record with the specified attribute
		 * variable from $tablename, this is in order to support just removing a single attribute for the user
		 */

		include 'library/opendb.php';

		if (isset($attribute)) {
			if (preg_match('/__/', $attribute))
				list($columnId, $attribute) = explode("__", $attribute);
			else
				$attribute = $attribute;
		}

		$sql = "DELETE FROM ".$dbSocket->escapeSimple($tablename)." WHERE Username='".$dbSocket->escapeSimple($username)."' ".
				" AND Attribute='".$dbSocket->escapeSimple($attribute)."' AND id=".$dbSocket->escapeSimple($columnId);
		$res = $dbSocket->query($sql);
		$logDebugSQL .= $sql . "\n";

		$successMsg = "Deleted attribute: <b> $attribute </b> for user(s): <b> $username </b> from database";
		$logAction .= "Successfully deleted attribute [$attribute] for user [$username] on page: ";

		include 'library/closedb.php';

		$showRemoveDiv = "none";

	} else if ( (isset($clearSessionsUsers)) && ($clearSessionsUsers != "") ) {

		/* this is used to remove stale user sessions from the accounting table
		*/
		$allUsernames = "";

		if (!is_array($clearSessionsUsers))
			$clearSessionsUsers = array($clearSessionsUsers, NULL);

			foreach ($clearSessionsUsers as $variable=>$value) {

				if (trim($value) != "") {

					list($userSessions,$acctStartTime) = preg_split('/\\|\\|/', $value);

					$allUsernames .= $userSessions . ", ";

					include 'library/opendb.php';

					$sql = "DELETE FROM ".$configValues['CONFIG_DB_TBL_RADACCT'].
						" WHERE Username='$userSessions' AND AcctStartTime='$acctStartTime' ".
						" AND (AcctStopTime='0000-00-00 00:00:00' OR AcctStopTime IS NULL)";
					$res = $dbSocket->query($sql);
					$logDebugSQL .= $sql . "\n";

					$successMsg = "Deleted stale accounting sessions for user: <b> $allUsernames </b> from database";
					$logAction .= "Successfully deleted stale accounting sessions for user [$allUsernames] on page: ";

					include 'library/closedb.php';
			} // if trim

		} // foreach

		$showRemoveDiv = "none";

	}



	include_once('library/config_read.php');
    $log = "visited page: ";

?>



<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>daloRADIUS</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="css/1.css" type="text/css" media="screen,projection" />

</head>

<?php

	include ("menu-mng-users.php");

?>


<div id="contentnorightbar">

	<h2 id="Intro"><a href="#" onclick="javascript:toggleShowDiv('helpPage')"><?php echo t('Intro','mngdel.php') ?>
	:: <?php if (isset($username)) { echo $username; } ?><h144>&#x2754;</h144></a></h2>

	<div id="helpPage" style="display:none;visibility:visible" >
		<?php echo t('helpPage','mngdel') ?>
		<br/>
	</div>
	<?php
		include_once('include/management/actionMessages.php');
	?>

	<div id="removeDiv" style="display:<?php echo $showRemoveDiv ?>;visibility:visible" >
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">

	<fieldset>

		<h302> <?php echo t('title','AccountRemoval') ?> </h302>
		<br/>

		<label for='username' class='form'><?php echo t('all','Username')?></label>
		<!--***********edit***********-->
		<textarea class="form_fileimport" name='csvdata' type='text' id='username' value='<?php echo $username ?>' tabindex=100 style="width:462px;height:297px;"></textarea>
		<br />

		<label for='delradacct' class='form'><?php echo t('all','RemoveRadacctRecords')?></label>
		<select class='form' tabindex=102 name='delradacct' tabindex=101>
			<option value='no'>no</option>
			<option value='yes'>yes</option>
		</select>
		<br />

		<br/><br/>
		<hr><br/>
		<input type="submit" name="submit" value="<?php echo t('buttons','apply') ?>" tabindex=1000
			class='button' />

	</fieldset>

	</form>
	</div>


<?php
        include_once("include/management/autocomplete.php");

        if ($autoComplete) {
                echo "<script type=\"text/javascript\">
                      autoComEdit = new DHTMLSuite.autoComplete();
                      autoComEdit.add('username','include/management/dynamicAutocomplete.php','_small','getAjaxAutocompleteUsernames');
                      </script>";
        }
?>


<?php
	include('include/config/logging.php');
?>

		</div>

		<div id="footer">

<?php
	include 'page-footer.php';
?>


		</div>

</div>
</div>


</body>
</html>
