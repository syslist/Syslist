<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 900);

  If (getOrPost('btnSubmit')) {
      $strFirstName   = validateText($progText249, getOrPost('txtFirstName'), 2, 40, TRUE, FALSE);
      $strMiddleInit  = validateText($progText250, getOrPost('txtMiddleInit'), 1, 1, FALSE, FALSE);
      $strLastName    = validateText($progText251, getOrPost('txtLastName'), 2, 40, TRUE, FALSE);
      if ($_SESSION['stuckAtLocation']) {
          $intLocationID = $_SESSION['locationStatus'];
          $intStuckAtLocation = 1;
      } else {
          $intLocationID = cleanFormInput(getOrPost('cboLocationID'));
          $intStuckAtLocation = checkboxToBinary(cleanFormInput(getOrPost('chkStuckAtLocation'))); 
      }
      
      
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
      
      $strUserID      = validateText($progText255, getOrPost('txtUserID'), 3, 20, $requireExtras, FALSE);
      $strEmail       = validateEmail($progText256, getOrPost('txtEmail'), $requireExtras);

      If (!$strError AND ($requireExtras OR $strUserID)) {
          $strSQL = "SELECT id FROM tblSecurity WHERE userID='$strUserID' AND hidden='0'";
          $result = dbquery($strSQL);
          $intFound = mysql_num_rows($result);
          If ($intFound != 0) {
               $strError = $progText257; # userid taken
          }
      }
      If (!$strError AND ($requireExtras OR $strEmail)) {
          $strSQL = "SELECT id FROM tblSecurity WHERE email='$strEmail' AND hidden='0'";
          $result = dbquery($strSQL);
          $intFound = mysql_num_rows($result);
          If ($intFound != 0) {
              $strError = $progText258; # email address already exists
          }
      }

      If (!$strError) {

          // If the user will have login rights, make them a password
          If ($intLevel != 3) {
              $strTempString = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
              srand ((double) microtime() * 1000000);
              for ($i = 0; $i < 8; $i++) {
                   $intPos = rand(0, 33);
                   $strTempChar = substr($strTempString, $intPos, 1);
                   $strPassword = $strPassword.$strTempChar;
                   $md5Password = md5($strPassword);
              }
          } Else {
              $md5Password = "";
          }

          $strSQL = "INSERT INTO tblSecurity (userID, password, firstName, middleInit, lastName,
            email, securityLevel, userLocationID, stuckAtLocation, accountID) VALUES (".makeNull($strUserID, TRUE).",
            ".makeNull($md5Password, TRUE).", '$strFirstName', ".makeNull($strMiddleInit, TRUE).",
            '$strLastName', ".makeNull($strEmail, TRUE).", $intLevel, ".makeNull($intLocationID).",
	    '" . $intStuckAtLocation . "', " . $_SESSION['accountID'] . ")";
          $result = dbquery($strSQL);
          $strError = $strFirstName." ".$strLastName." ".$progText259;

          // If the user will have login rights, email them their new password
          If ($intLevel != 3) {
              $strURL   = $urlPrefix."://".$homeURL;
              $msgBody  = $progText260." '$strUserID' ".$progText261." '$strPassword'. ";
              $msgBody .= $progText262." $strURL.";
              mail($strEmail, ($progText263.": ".date("m-d-Y")), $msgBody,
                "From: $adminEmail\r\nReply-To: $adminEmail\r\n");

              $strError .= " ".$progText264; # they have been emailed their password
          }

          // clear form fields, so another user can be created.
          $strFirstName   = "";
          $strMiddleInit  = "";
          $strLastName    = "";
          $intLevel       = "";
          $strUserID      = "";
          $strEmail       = "";
          $intLocationID  = "";
      }
  }

  writeHeader($progText265);
  declareError(TRUE);

  If ($_SESSION['sessionSecurity'] < 1) {
      echo $progTextBlock16;
  }
?>
<font color='ff0000'>*</font> <?=$progText13;?>.<br>
<font color='006633'>*</font> <?=$progText266;?>.
<p>
<form name="form1" method="POST" action="createUser.php">
  <p><table border='0' cellpadding='2'>
    <tr>
      <td><font color='006633'>*</font> <?=$progText255;?>: &nbsp;</td>
      <td><input type="text" name="txtUserID" value="<?echo $strUserID;?>" size="20" maxlength="20"></td>
    </tr>
    <tr>
      <td><font color='ff0000'>*</font> <?=$progText249;?>: &nbsp;</td>
      <td><input type="text" name="txtFirstName" value="<?echo $strFirstName;?>" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td><?=$progText250;?>: &nbsp;</td>
      <td><input type="text" name="txtMiddleInit" value="<?echo $strMiddleInit;?>" size="1" maxlength="1"></td>
    </tr>
    <tr>
      <td><font color='ff0000'>*</font> <?=$progText251;?>: &nbsp;</td>
      <td><input type="text" name="txtLastName" value="<?echo $strLastName;?>" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td><font color='006633'>*</font> <?=$progText256;?>: &nbsp;</td>
      <td><input type="text" name="txtEmail" value="<?echo $strEmail;?>" size="40" maxlength="50"></td>
    </tr>
    <tr>
      <td><font color='ff0000'>*</font> <?=$progText267;?>: &nbsp;</td>
      <td>
          <select name='cboLevel' onChange="document.getElementById('chkStuckAtLocation').disabled = (this.selectedIndex <= 1 || this.selectedIndex == 4); if (this.selectedIndex <= 1 || this.selectedIndex == 4) { document.getElementById('chkStuckAtLocation').checked = false; }">
  <? If ($_SESSION['sessionSecurity'] < 1) { ?>
              <option value=''>&nbsp;</option>
              <option value='0' <? echo writeSelected($intLevel, "0"); ?>><?=$progText269;?></option>
              <option value='1' <? echo writeSelected($intLevel, "1"); ?>><?=$progText270;?></option>
              <option value='2' <? echo writeSelected($intLevel, "2"); ?>><?=$progText271;?></option>
  <? } ?>
              <option value='3' <? echo writeSelected($intLevel, "3"); ?>><?=$progText272;?></option>
          </select>
      </td>
    </tr>
<? if (!$_SESSION['stuckAtLocation']) { ?>
    <tr><td colspan='2'>&nbsp;<br><font class='soft_instructions'><?=$progTextBlock58;?></font></td>
    <tr>
      <td><?=$progText34;?>: &nbsp;</td>
      <td><? buildLocationSelect($intLocationID); ?></td>
    </tr>
<? } ?>
    <? if ($_SESSION['sessionSecurity'] == 0) { ?>
    <tr>
      <td></td>
      <td><input type='checkbox' name='chkStuckAtLocation' id='chkStuckAtLocation' <?= writeChecked($intStuckAtLocation, 1) ?> <?= ($intLevel == 0 || $intLevel == 3) ? "disabled" : "" ?>><?= $progText273 ?></td>
    </tr>
    <? } ?>

  </table><p>

  <input type="submit" name="btnSubmit" value="<?=$progText21;?>">
</form>

<?
  writeFooter();
?>
