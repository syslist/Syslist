<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 900);

  $notify = getOrPost('notify');
  notifyUser($notify);

  $editID         = cleanFormInput(getOrPost('editID'));
  $oldLocationID  = cleanFormInput(getOrPost('oldLocationID'));
  $oldLevel       = cleanFormInput(getOrPost('oldLevel'));

  If (getOrPost('btnSubmit')) {
      $strFirstName   = validateText($progText249, getOrPost('txtFirstName'), 2, 40, TRUE, FALSE);
      $strMiddleInit  = validateText($progText250, getOrPost('txtMiddleInit'), 1, 1, FALSE, FALSE);
      $strLastName    = validateText($progText251, getOrPost('txtLastName'), 2, 40, TRUE, FALSE);
      if ($_SESSION['stuckAtLocation']) {
          $intLocationID = $_SESSION['locationStatus'];
      } else {
          $intLocationID  = cleanFormInput(getOrPost('cboLocationID'));
      }
      $intStuckAtLocation = checkboxToBinary(cleanFormInput(getOrPost('chkStuckAtLocation')));
      
      If ($_SESSION['userID'] != $editID) {
          $intLevel = cleanFormInput(getOrPost('cboLevel'));

          // If user has the right to assign a securityLevel, force them too
          If (($intLevel == "") AND ($_SESSION['sessionSecurity'] < 1)) {
              fillError($progText252);

          // If the user does NOT have the right, but has tried to hack a value in there,
          // alert the admin.
          } ElseIf (($intLevel < 3) AND ($_SESSION['sessionSecurity'] > 0)) {
              fillError($progText253." ".$progText254);
              $msgBody = $urlPrefix."://".$homeURL." user: " . $_SESSION['userID'];
              mail($adminEmail, ($progText253.": ".date("m-d-Y H:i")), $msgBody,
                "From: $adminEmail\r\nReply-To: $adminEmail\r\n");

          // If a security level associated with login rights (0-2) was selected,
          // require email address and userID to be entered.
          } ElseIf ($intLevel < 3) {
              $requireExtras = TRUE;
          }
      } Else {
          $requireExtras = TRUE;
      }

      $strUserID      = validateText($progText255, getOrPost('txtUserID'), 3, 20, $requireExtras, FALSE);
      $strEmail       = validateEmail($progText256, getOrPost('txtEmail'), $requireExtras);

      If (!$strError AND ($requireExtras OR $strUserID)) {
          $strSQL = "SELECT id FROM tblSecurity WHERE userID='$strUserID' AND hidden='0' AND accountID=".$_SESSION['accountID']." AND id != ".$editID;
          $result = dbquery($strSQL);
          $intFound = mysql_num_rows($result);
          If ($intFound != 0) {
               $strError = $progText257; # userid taken
          }
      }
      If (!$strError AND ($requireExtras OR $strEmail)) {
          $strSQL = "SELECT id FROM tblSecurity WHERE email='$strEmail' AND hidden='0' AND accountID=".$_SESSION['accountID']." AND id != ".$editID;
          $result = dbquery($strSQL);
          $intFound = mysql_num_rows($result);
          If ($intFound != 0) {
              $strError = $progText258; # email address already exists
          }
      }

      // If no errors, but admin has changed user's location, make admin specify what to do
      // with hardware associated with user.
      If (!$strError AND getOrPost('btnSubmit2')) {

          // validation of user input
          $hardwareHandling = cleanFormInput(getOrPost('radHardwareHandling'));
          If (!$hardwareHandling) {
              $strError = $progText334;
              $hardwareUpdateIncomplete = TRUE;
          }
      }

      If (!$strError AND
          (getOrPost('btnSubmit2') OR !$intLocationID OR ($oldLocationID == $intLocationID))) {

          // If user has been upgraded to a security level with login rights, make them a password
          If (($oldLevel == 3) AND ($intLevel < 3) AND ($_SESSION['userID'] != $editID)) {
              $strTempString = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
              for ($i = 0; $i < 8; $i++) {
                   srand ((double) microtime() * 1000000);
                   $intPos = rand(0, 33);
                   $strTempChar = substr($strTempString, $intPos, 1);
                   $strPassword = $strPassword.$strTempChar;
                   $md5Password = md5($strPassword);
              }
              $sqlPassword = ", password='$md5Password'";
          }
          If ($_SESSION['userID'] != $editID) {
              $sqlLevel = ", securityLevel=$intLevel";
          }

          // if admin is editing himself, prepare data for confirmation email (further below)
          If ($_SESSION['userID'] == $editID) {
              $strSQL = "SELECT email FROM tblSecurity WHERE accountID=" . $_SESSION['accountID'] . " AND id=".$_SESSION['userID'];
              $result = dbquery($strSQL);
              $row = mysql_fetch_row($result);
              $strOldEmail = $row[0];

              If ($intLocationID) {
                  $strSQL = "SELECT locationName FROM locations WHERE locationID=".$intLocationID;
                  $result = dbquery($strSQL);
                  $row = mysql_fetch_row($result);
                  $strLocation = $row[0];
              }
          }
	  
          // You can't edit your location if you're stuck there
          if (!($editID == $_SESSION['userID'] && $_SESSION['stuckAtLocation'])) {
              $sqlLocation = ", userLocationID=".makeNull($intLocationID);
          } else {
          	  $sqlLocation = "";
          }
	  
	      // You can't edit whether a user is stuck unless you're full access.
	      // Plus, full access users don't get stuck.
	      if ($_SESSION['sessionSecurity'] == 0) {
	          if ($intLevel == 0) {
                  $intStuckAtLocation = 0;
	          }
              $sqlStuck = ", stuckAtLocation='".$intStuckAtLocation."'";
	      } else {
	          $sqlStuck = "";	
	      }
	  
          // make the update
          $strSQL = "UPDATE tblSecurity SET userID=".makeNull($strUserID, TRUE).",
            firstName='$strFirstName', middleInit=".makeNull($strMiddleInit, TRUE).",
            lastName='$strLastName', email=".makeNull($strEmail, TRUE).
	        $sqlLocation . $sqlStuck . $sqlPassword . $sqlLevel . 
	        " WHERE accountID=" . $_SESSION['accountID'] . " AND id=".$editID;
          $result = dbquery($strSQL);

          // if user's location was changed, make the corresponding hardware update
          If ($hardwareHandling == "move") {
              $strSQL2 = "UPDATE hardware SET locationID=$intLocationID WHERE userID=$editID AND
                accountID=" . $_SESSION['accountID'] . "";
              $result2 = dbquery($strSQL2);

          } ElseIf ($hardwareHandling == "spare") {
              $strSQL2 = "UPDATE hardware SET sparePart='1', userID=NULL WHERE userID=$editID AND
                accountID=" . $_SESSION['accountID'] . "";
              $result2 = dbquery($strSQL2);
          }

          // if admin is editing himself, then send confirmation email to him
          If ($_SESSION['userID'] == $editID) {
              $msgBody = $msgBody.$progText489.": ".$urlPrefix."://".$homeURL."\n\n";
              $msgBody = $msgBody.$progText490.": $strUserID\n";
              $msgBody = $msgBody.$progText491.": ".buildName($strFirstName, $strMiddleInit, $strLastName, 1)."\n";
              $msgBody = $msgBody.$progText492.": $strEmail\n\n";
              $msgBody = $msgBody.$progText34.": ".writeNA($strLocation)."\n\n";
              $msgBody = $msgBody.$progText493;

              $strSubject = $progText494;
              mail($strEmail, $strSubject, $msgBody, "From: $adminEmail\r\nReply-To: $adminEmail\r\n");

              If ($strEmail != $strOldEmail) {
                  mail($strOldEmail, $strSubject, $msgBody, "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
              }
          }

          // If user has been upgraded to a security level with login rights, email password to them
          If (($oldLevel == 3) AND ($intLevel < 3) AND ($_SESSION['userID'] != $editID)) {
              $strURL   = $urlPrefix."://".$homeURL;
              $msgBody  = $progText260." '$strUserID' ".$progText261." '$strPassword'. ";
              $msgBody .= $progText262." $strURL.";
              mail($strEmail, ($progText263.": ".date("m-d-Y")), $msgBody,
                "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
                $strError2 = $progText264; # they have been emailed their password
          }
          $strError = $progText332." ".$strError2; # account updated successfully

          $oldLevel = $intLevel; # in case they change things again; reflect the new "old" level.
          $oldLocationID = $intLocationID;
      }
  } ElseIf (getOrPost('editID')) {
      $strSQL = "SELECT userID, firstName, middleInit, lastName, email, securityLevel,
        userLocationID, stuckAtLocation, picURL FROM tblSecurity WHERE accountID=" . $_SESSION['accountID'] . " AND id=".$editID;
      $result = dbquery($strSQL);
      $row = mysql_fetch_row($result);

      $strUserID           = $row[0];
      $strFirstName        = $row[1];
      $strMiddleInit       = $row[2];
      $strLastName         = $row[3];
      $strEmail            = $row[4];
      $intLevel            = $row[5];
      $oldLevel            = $intLevel;
      $intLocationID       = $row[6];
      $intStuckAtLocation  = $row[7];
      $picURL              = $row[8];
      $oldLocationID       = $intLocationID;

      // If user should not be able to edit this editID, but has hacked the querystring,
      // alert the admin.
      If (($intLevel < 3) AND ($_SESSION['sessionSecurity'] > 0) AND ($_SESSION['userID'] != $editID)) {
          $msgBody = $urlPrefix."://".$homeURL." user: " . $_SESSION['userID'];
          mail($adminEmail, ($progText253.": ".date("m-d-Y H:i")), $msgBody,
            "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
          die($progText253." ".$progText254);
      }
  } Else {
      $strError = $progText136; # be sure you haven't altered the querystring
      $noEditID = TRUE;
  }

  // (SCRATCH) Potential picture URL is in aryRow[0]. Null case handled in writeHeader by default.
  writeHeader($progText333, "", FALSE, "&nbsp;", $picURL, $editID, "user");
  declareError(TRUE);

  // If admin has changed user's location to a specifc place, make admin specify what to do
  // with hardware associated with user. This occurs if there are no errors during the first
  // part of the editing process (!$strError OR $hardwareUpdateIncomplete).
  If (!$noEditID AND
      $intLocationID AND
      ($oldLocationID != $intLocationID) AND
      (!$strError OR $hardwareUpdateIncomplete)) {

      echo "<font class='soft_instructions'>".$progText335."</font>\n";
?>

<form name="form1" method="POST" action="editUser.php">

  <input type="radio" name="radHardwareHandling" value="move"> <?=$progText336;?><br>
  <input type="radio" name="radHardwareHandling" value="spare"> <?=$progText337;?><p>

  <input type="hidden" value="1" name="btnSubmit">
  <input type="hidden" value="<? echo $editID; ?>" name="editID">
  <input type="hidden" value="<? echo $oldLevel; ?>" name="oldLevel">
  <input type="hidden" value="<? echo $intLevel; ?>" name="cboLevel">
  <input type="hidden" value="<? echo $oldLocationID; ?>" name="oldLocationID">
  <input type="hidden" value="<? echo $intLocationID; ?>" name="cboLocationID">
  <input type="hidden" name="txtUserID" value="<?echo antiSlash($strUserID);?>">
  <input type="hidden" name="txtFirstName" value="<?echo antiSlash($strFirstName);?>">
  <input type="hidden" name="txtMiddleInit" value="<?echo antiSlash($strMiddleInit);?>">
  <input type="hidden" name="txtLastName" value="<?echo antiSlash($strLastName);?>">
  <input type="hidden" name="txtEmail" value="<?echo antiSlash($strEmail);?>">

  <input type="submit" name="btnSubmit2" value="<?=$progText21;?>">

<?
  // Edit user profile
  } ElseIf (!$noEditID) {
?>
<p>
<font color='ff0000'>*</font> <?=$progText13;?>.<br>
<font color='006633'>*</font> <?=$progText266;?>.
<p>
<form name="form1" method="POST" action="editUser.php">
  <p><table border='0' cellpadding='2'>
    <tr>
      <td><font color='006633'>*</font> <?=$progText255;?>: &nbsp;</td>
      <td><input type="text" name="txtUserID" value="<?echo antiSlash($strUserID);?>" size="20" maxlength="20"></td>
    </tr>
    <tr>
      <td><font color='ff0000'>*</font> <?=$progText249;?>: &nbsp;</td>
      <td><input type="text" name="txtFirstName" value="<?echo antiSlash($strFirstName);?>" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td><?=$progText250;?>: &nbsp;</td>
      <td><input type="text" name="txtMiddleInit" value="<?echo antiSlash($strMiddleInit);?>" size="1" maxlength="1"></td>
    </tr>
    <tr>
      <td><font color='ff0000'>*</font> <?=$progText251;?>: &nbsp;</td>
      <td><input type="text" name="txtLastName" value="<?echo antiSlash($strLastName);?>" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td><font color='006633'>*</font> <?=$progText256;?>: &nbsp;</td>
      <td><input type="text" name="txtEmail" value="<?echo antiSlash($strEmail);?>" size="40" maxlength="50"></td>
    </tr>
<?
    If ($_SESSION['userID'] != $editID) {
?>
    <tr>
      <td><font color='ff0000'>*</font> <?=$progText267;?>: &nbsp;</td>
      <td>
          <select name='cboLevel' onChange="document.getElementById('chkStuckAtLocation').disabled = (this.selectedIndex <= 1 || this.selectedIndex == 4); if (this.selectedIndex <= 1 || this.selectedIndex == 4) { document.getElementById('chkStuckAtLocation').checked = false; }">
<?
        If ($_SESSION['sessionSecurity'] < 1) {
?>
              <option value=''>&nbsp;</option>
              <option value='0' <? echo writeSelected($intLevel, "0"); ?>><?=$progText269;?></option>
              <option value='1' <? echo writeSelected($intLevel, "1"); ?>><?=$progText270;?></option>
              <option value='2' <? echo writeSelected($intLevel, "2"); ?>><?=$progText271;?></option>
<?
        }
?>
              <option value='3' <? echo writeSelected($intLevel, "3"); ?>><?=$progText272;?></option>
          </select>
      </td>
    </tr>
    <?

    }
    // Upload Picture line
    if ($_SESSION['sessionSecurity'] < 1) {
    	echo "<tr><td></td><td><a HREF='admin_pic.php?target=user&id=$editID'>".$progText415A."</A>\n</td></tr>"; # upload picture
    }
?>
     <? // You can't edit anyone's location if you're stuck somewhere ?>
    <? if (!$_SESSION['stuckAtLocation'] || $_SESSION['sessionSecurity'] == 0) { ?>
    <tr><td colspan='2'>&nbsp;<br><font class='soft_instructions'><?=$progTextBlock58;?></font></td>
    <tr>
      <td><?=$progText34;?>: &nbsp;</td>
      <td><? buildLocationSelect($intLocationID); ?></td>
    </tr>
    <? } ?>
    <? // Only full access users can edit whether the user is stuck ?> 
    <? if ($_SESSION['sessionSecurity'] == 0) { ?>
    <tr>
      <td></td>
      <td><input type='checkbox' name='chkStuckAtLocation' id='chkStuckAtLocation' <?= writeChecked($intStuckAtLocation, 1) ?> <?= ($intLevel == 0 || $intLevel == 3) ? "disabled" : "" ?>><?= $progText273 ?></td>
    </tr>
    <? } ?>

  </table><p>

  <input type="hidden" value="<? echo $oldLevel; ?>" name="oldLevel">
  <input type="hidden" value="<? echo $oldLocationID; ?>" name="oldLocationID">
  <input type="hidden" value="<? echo $editID; ?>" name="editID">
  <input type="submit" name="btnSubmit" value="<?=$progText21;?>">
<?
  If ($_SESSION['userID'] == $editID) {
      echo "&nbsp;<a href='changePW.php'>".$progText198."</a>\n";
  }
?>

</form>

<?

      echo "<table border='0' cellpadding='0' cellspacing='0'>
           <tr><td><img src='Images/1pix.gif' border='0' width='1' height='8'></td></tr></table>
            <b>".$progText416.":</b> &nbsp;";
      If ($_SESSION['sessionSecurity'] < 2) {
          echo "(<A class='action' HREF='admin_comments.php?commentType=u&subjectID=$editID'>".$progText417."?</A>)\n"; # add new
      }

      $strSQL3 = "SELECT c.*, s.*, o.categoryName
        FROM comments as c
        LEFT JOIN commentCategories as o ON o.categoryID=c.categoryID
        LEFT JOIN tblSecurity as s ON c.assignedUserID=s.id
        WHERE c.subjectID=$editID AND c.subjectType='u' AND c.accountID=" . $_SESSION['accountID'] . "
        ORDER BY c.commentDate DESC, c.commentPriority ASC";
      $result3 = dbquery($strSQL3);
      If (mysql_num_rows($result3) < 1) {
          echo "<br> &nbsp; *".$progText418.".<br>"; # no comments made yet
      } Else {
          echo "<p>";
          While ($row3 = mysql_fetch_array($result3)) {
              If ($row3["id"]) {
                  $strAssignedUser = buildName($row3["firstName"], $row3["middleInit"], $row3["lastName"], 1);
                  $strAssignedUser .= " (".$row3["userID"].")";
              } Else {
                  $strAssignedUser = "";
              }
              echo "<table border='0' cellpadding='1' cellspacing='0' width='620' bgcolor='#666666'><tr><td>\n";
              echo "<table border='0' cellpadding='3' cellspacing='0' width='620' bgcolor='#FFFFFF'>\n";
              echo "<tr bgcolor='#FFF7F7' align='left' valign='top'>\n";
              echo "<td with='315'><b>".$progText223.":</b>&nbsp;".displayDateTime($row3["commentDate"])."&nbsp;</td>";
              echo "<td width='90'><b>".$progText221.":</b>&nbsp;".writeNA($row3["commentPriority"])."&nbsp;</td>";
              echo "<td width='130'><b>".$progText222.":</b>&nbsp;".writeNA(writeCommentStatus($row3["commentStatus"]))."&nbsp;</td>";
              echo "<td align='right' width='85'>&nbsp;";
              If ($_SESSION['sessionSecurity'] < 2) {
                  echo "<a class='action' href='admin_comments.php?subjectID=$hardwareID&commentID=".$row3["commentID"]."'>";
                  echo $progText419."?</a>\n";
              }

              echo "</td></tr><tr bgcolor='#FFF7F7' align='left' valign='top'>\n";
              echo "<td with='315'><b>".$progText138.":</b>&nbsp;".writeNA($strAssignedUser)."&nbsp;</td>";
              echo "<td colspan='3'><b>".$progText960.":</b>&nbsp;".writeNA($row3["categoryName"])."&nbsp;</td>";
              echo "</tr>\n";
              echo "<tr><td class='row1' colspan='4'>".$row3["commentText"]."</td></tr>\n";
              echo "<tr><td class='row1' colspan='4'>&nbsp;</td></tr>\n";
              echo "</table>\n</td></tr></table><p>\n\n";
          }
      }
  }

  writeFooter();
?>
