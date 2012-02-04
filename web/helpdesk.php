<?
  // 1) need to figure out how assignedUserID is going to be selected!

  $genericNav = TRUE;
  Include("Includes/global.inc.php");

  If (getOrPost('btnSubmit')) {
      $strEmail = validateEmail($progText256, getOrPost('txtEmail'), TRUE);

      If (!$strError) {
           $strSQL = "SELECT t.id, t.userID, t.accountID, t.firstName, t.middleInit, t.lastName
             FROM tblSecurity as t WHERE t.email='$strEmail' AND t.hidden='0'";
           $result     = dbquery($strSQL);
           $userCount  = mysql_num_rows($result);

           If (!$userCount) {
                $strError = $progText345;
           } Else {
                $row = mysql_fetch_row($result);
                $authorID = $row[0];
                $userAccountID = $row[2];
                $strNewAuthor = buildName($row[3], $row[4], $row[5], 1);

                $strSQL2   = "SELECT hardwareID FROM hardware WHERE userID=$authorID AND accountID=$userAccountID";
                $result2   = dbquery($strSQL2);
                $sysCount  = mysql_num_rows($result2);

                If ($sysCount > 0) {
                    $subjectSelected = 0;
                } Else {
                    $commentType      = "u";
                    $subjectSelected  = $authorID;
                }
           }

           // If a user has systems, they must choose one or 'other'
           If (!$subjectSelected AND getOrPost('btnSubmit2')) {
                $cboSystem  = cleanFormInput(getOrPost('cboSystem'));
                $chkOther   = cleanFormInput(getOrPost('chkOther'));
                If ((!$cboSystem AND !$chkOther) OR ($cboSystem AND $chkOther)) {
                    $strError = $progText1200;
                } ElseIf ($cboSystem) {
                    $commentType      = "h";
                    $subjectSelected  = $cboSystem;
                } Else {
                    $commentType      = "u";
                    $subjectSelected  = $authorID;
                }
           }

           // User has been authenticated, subject of help request has been selected (automatically or manually),
           //  and help request has been submitted.
           If (!$strError AND getOrPost('btnSubmit2') AND getOrPost('btnSubmit3') AND $subjectSelected) {

              $cboPriority  = validateChoice($progText7, getOrPost('cboPriority'));
              $strComment   = validateText($progText8, getOrPost('txtComment'), 1, 65535, TRUE, FALSE);

              If (!$strError) {
                  $strComment = $progText8." ".$progText10." <i>".$strNewAuthor."</i>: &nbsp;".$strComment;

                  // Send email to assigned user (unless they are the person making the update.)
                  $strMessage = stripslashes($strComment);
                  $strMessage = str_replace("<br>", "\n", $strMessage);
                  $strMessage = str_replace("&nbsp;", " ", $strMessage);
                  $strMessage = strip_tags($strMessage);

                  // If a system was chosen as subject, verify that the user isn't hacking in an
                  //  ID for a system outside their account.
                  If ($commentType == 'h') {
                      $strSQL7        = "SELECT * FROM hardware WHERE hardwareID=$subjectSelected AND accountID=$userAccountID";
                      $result7        = dbquery($strSQL7);
                      $securityCheck  = mysql_num_rows($result7);
                      If ($securityCheck) {
                          $strMessage     .= "\n\n".$urlPrefix."://".$homeURL."/showfull.php?hardwareID=".$subjectSelected;
                      } Else {
                          die($progText253);
                      }
                  } elseif ($commentType == 'u') {
                      $strMessage .= "\n\n".$urlPrefix."://".$homeURL."/viewUser.php?viewID=".$subjectSelected;
                  }

                  $strSQL6           = "SELECT t.email, t.id FROM tblSecurity as t, account_settings as a
                                        WHERE a.primaryHelpdeskUserID=t.id AND t.accountID=$userAccountID";
                  $result6           = dbquery($strSQL6);
                  $row6              = mysql_fetch_row($result6);
                  $strAssigneeEmail  = $row6[0];
                  $primaryUserID     = $row6[1];

                  mail($strAssigneeEmail, ($progText31.": ".date("m-d-Y")), $strMessage,
                    "From: $adminEmail\r\nReply-To: $adminEmail\r\n");

                  $strSQL5 = "INSERT INTO comments (subjectID, authorID, subjectType, commentDate,
                    commentText, commentPriority, commentStatus, assignedUserID, identityCode, categoryID,
                    accountID) VALUES ($subjectSelected, $authorID, '$commentType', '".date("YmdHis")."',
                    '$strComment', '$cboPriority', 'Open', $primaryUserID, NULL, NULL, $userAccountID)";
                  $result5 = dbquery($strSQL5);
                  $strError = $progText1201;
                  $requestSubmitted = 1;
               }
           }
      }
  }

  writeHeader($progText1202);
  declareError(TRUE);

  // Authenticate user
  If (!getOrPost('btnSubmit') OR (getOrPost('btnSubmit') AND $strError AND !getOrPost('btnSubmit2'))) {
?>

<form name="form1" method="POST" action="helpdesk.php">
  <p><table border='0' cellpadding='4' cellspacing='0'>

    <tr>
      <td><?=$progText235;?>: &nbsp;</td>
      <td><input type="text" name="txtEmail" value="<?echo antiSlash($strEmail);?>" size="30" maxlength="50"></td>
    </tr>

  </table><p>

  <input type='hidden' name='btnSubmit' value='1'>
  <input type="submit" name="btnSubmit" value="<?=$progText21?>">
</form>


<?
  // Select either a system, or 'other' (which translates to the user themselves)
  } ElseIf (!$subjectSelected) {
      echo $progText1203;
?>

<form name="form1" method="POST" action="helpdesk.php">
<p><table border='0' cellpadding='4' cellspacing='0'>
    <tr>
      <td><?=$progText217;?>: &nbsp;</td>
      <td><? echo buildSystemSelect($authorID, $subjectSelected, ""); ?></td>
    </tr>
    <td colspan='2'><b> &nbsp; -OR- </b></td>
    <tr>
      <td colspan='2'><?=$progText69;?>: &nbsp;<input type='checkbox' name='chkOther' value='1'></td>
    </tr>
  </table><p>

  <input type='hidden' name='txtEmail' value='<?=$strEmail;?>'>
  <input type='hidden' name='txtAcctCode' value='<?=$strAcctCode;?>'>
  <input type='hidden' name='btnSubmit' value='1'>
  <input type="submit" name="btnSubmit2" value="<?=$progText21?>">
</form>


<?
  // Input help request
  } ElseIf (!$requestSubmitted) {
?>

<FORM METHOD="post" ACTION="helpdesk.php">
<TABLE border='0' cellpadding='4' cellspacing='0'>
      <TD width='123'><font color='ff0000'>*</font> <?=$progText7;?>: &nbsp;</TD>
      <TD>
        <select name='cboPriority' size='1'>
          <option value=''>&nbsp;</option>
          <option value='1' <?=writeSelected("1", $cboPriority);?>>1</option>
          <option value='2' <?=writeSelected("2", $cboPriority);?>>2</option>
          <option value='3' <?=writeSelected("3", $cboPriority);?>>3</option>
          <option value='4' <?=writeSelected("4", $cboPriority);?>>4</option>
          <option value='5' <?=writeSelected("5", $cboPriority);?>>5</option>
        </select>
        &nbsp;<font class='instructions'>(<?=$progText17;?>)</font>
      </TD>
   </TR>
   <TR>
      <TD valign='top' width='123'><font color='ff0000'>*</font> <?=$progText20;?>: &nbsp;</TD>
      <TD><textarea name='txtComment' rows='7' cols='46' wrap='virtual'><?=antiSlash($strComment);?></textarea></TD>
   </TR>
   <TR>
      <TD colspan='2'>&nbsp;</TD>
   </TR>
   <TR>
      <TD colspan='2'><input type='submit' name='btnSubmit3' value='<?=$progText21;?>'></TD>
   </TR>
  </TABLE>

  <input type='hidden' name='txtEmail' value='<?=$strEmail;?>'>
  <input type='hidden' name='txtAcctCode' value='<?=$strAcctCode;?>'>
  <input type='hidden' name='chkOther' value='<?=$chkOther;?>'>
  <input type='hidden' name='cboSystem' value='<?=$cboSystem;?>'>
  <input type='hidden' name='btnSubmit' value='1'>
  <input type='hidden' name='btnSubmit2' value='1'>
</FORM>

<?
  }
  writeFooter();
?>
