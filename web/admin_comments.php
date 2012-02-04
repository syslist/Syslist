<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

  $commentID = cleanFormInput(getOrPost('commentID'));

  // If editing a comment, load the old comment data
  If ($commentID) {
      $pageTitle = $progText2; # "update a comment"

      $strSQL = "SELECT c.*, s.*, g.categoryName
        FROM comments as c
        LEFT JOIN commentCategories as g ON g.categoryID=c.categoryID
        LEFT JOIN tblSecurity as s ON c.assignedUserID=s.id
        WHERE c.commentID=$commentID AND c.accountID=" . $_SESSION['accountID'] . "";
      $result = dbquery($strSQL);
      While ($row = mysql_fetch_array($result)) {
          $subjectID            = $row["subjectID"];
          $commentType          = $row["subjectType"];
          $strIdentityCode      = $row["identityCode"];
          $oldStatus            = $row["commentStatus"];
          $oldPriority          = $row["commentPriority"];
          $oldComment           = $row["commentText"];
          $oldLocationID        = $row["commentLocationID"];
          If (!$oldLocationID && !$_SESSION['stuckAtLocation']) {
              $oldLocationID = "unassigned";
          }
          $oldCategoryID        = $row["categoryID"];
          $oldCategoryName      = $row["categoryName"];
          $oldAssignedUserID    = $row["assignedUserID"];
          If ($oldAssignedUserID) {
              $oldAssignedUserName   = buildName($row["firstName"], $row["middleInit"], $row["lastName"], 1);
              $oldAssignedUserEmail  = $row["email"];
          }
      }

      // Instructions for editing a comment
      $strInstructions = $progTextBlock2." ";

  // If not editing a comment, just prepare the script to add one.
  } Else {
      $subjectID    = cleanFormInput(getOrPost('subjectID'));
      $commentType  = cleanFormInput(getOrPost('commentType'));

      $pageTitle = $progText5;
      $strInstructions = "";
  }

  // Get subject's email address (if applicable), so user can elect to send them a tracker if they wish
  // Also, determines whether location will be a required field (i.e. only when ticket is subjectless, and user isn't location-locked which is determined later)
  $strSQL2 = "";
  $getLocation = false;
  If ($commentType == 'h') {
      $strSQL2 = "SELECT s.email, h.hardwareStatus
        FROM hardware as h
        LEFT JOIN tblSecurity as s ON h.userID=s.id
        WHERE h.hardwareID=$subjectID AND h.accountID=" . $_SESSION['accountID'] . "";
  } ElseIf ($commentType == 'u') {
      $strSQL2 = "SELECT s.email FROM tblSecurity as s WHERE s.id=$subjectID
        AND s.accountID=" . $_SESSION['accountID'] . "";
  } Else {
      $getLocation = true;
  }
  If ($strSQL2) {
      $result2          = dbquery($strSQL2);
      $row2             = mysql_fetch_row($result2);
      $strSubjectEmail  = $row2[0];
      If ($commentType == 'h') {
          $oldSystemStatus  = $row2[1];
      }
  }

  // Add instructions about tracker, if applicable.
  If ($strSubjectEmail OR $strIdentityCode) {
      $strInstructions .= $progTextBlock68." ";

  // If not, add warning about adding subjectless comments, if applicable.
  } ElseIf (!$subjectID AND !$commentID) {
      $strInstructions .= $progTextBlock69." ";
  }

  // Give option to delete existing comment
  If ($commentID) {
      If ($_SESSION['sessionSecurity'] < 1) {
          $strInstructions .= "<p>".$progTextBlock3." ";
          If ($commentType) {
              $extraQS = "commentType=$commentType&";
          }
          $strInstructions .= "<a class='action' href='delete.php?".$extraQS."returnTo=$subjectID&commentID=$commentID' onClick=\"return warn_on_submit('".$progText3."');\">".$progText4."</a> ";
      }
  }
  If ($strInstructions) {
      $strInstructions .= "<p>";
  }

  // Form has been submitted
  If (getOrPost('btnSubmit')) {
      $chkNotifyCustomer  = cleanFormInput(getOrPost('chkNotifyCustomer'));
      $cboStatus          = cleanFormInput(getOrPost('cboStatus'));
      $cboPriority        = cleanFormInput(getOrPost('cboPriority'));
      $cboCategory        = cleanFormInput(getOrPost('cboCategory'));
      $cboAssignedUserID  = cleanFormInput(getOrPost('cboAssignedUserID'));
      if ($_SESSION['stuckAtLocation']) {
          $cboLocationID = "";
      } elseif ($getLocation) { 
          $cboLocationID      = validateChoice($progText31A, getOrPost('cboLocationID')); 
      }
      $cboSystemStatus    = cleanFormInput(getOrPost('cboSystemStatus'));
      If ($cboPriority OR $cboStatus OR $cboAssignedUserID OR !$commentType) {
          $cboStatus          = validateChoice($progText6, getOrPost('cboStatus'));
          $cboPriority        = validateChoice($progText7, getOrPost('cboPriority'));
          $cboAssignedUserID  = validateChoice($progText22, getOrPost('cboAssignedUserID'));          
      }
      $strComment = validateText($progText8, getOrPost('txtComment'), 1, 65535, TRUE, FALSE);

      If (!$strError) {
          $strSQL3 = "SELECT firstName, middleInit, lastName, email FROM tblSecurity
            WHERE id=" . $_SESSION['userID'] . " AND accountID=" . $_SESSION['accountID'] . "";
          $result3 = dbquery($strSQL3);
          $row3 = mysql_fetch_row($result3);
          $strNewAuthor = addslashes(buildName($row3[0], $row3[1], $row3[2], 1));

          // Get name and email of new assignee, if applicable
          If ($oldAssignedUserID != $cboAssignedUserID) {
              $strSQL4 = "SELECT firstName, middleInit, lastName, email FROM tblSecurity
                WHERE id=$cboAssignedUserID AND accountID=" . $_SESSION['accountID'] . "";
              $result4           = dbquery($strSQL4);
              $row4              = mysql_fetch_row($result4);
              $strNewAssignee    = buildName($row4[0], $row4[1], $row4[2], 1);
              $strAssigneeEmail  = $row4[3];
          }

          // Get new category name, if applicable
          If ($oldCategoryID != $cboCategory) {
              $strSQL4      = "SELECT categoryName FROM commentCategories WHERE categoryID=$cboCategory AND accountID=" . $_SESSION['accountID'] . "";
              $result4      = dbquery($strSQL4);
              $row4         = mysql_fetch_row($result4);
              $strCategory  = $row4[0];
          }

          // clear SQL-unfriendly text from location
          If ($cboLocationID == "unassigned") {
              $cboLocationID = "";
          }

          // Get email address of primary helpdesk contact
          $strSQLAlert = "SELECT a.ccTicketCreate, a.ccTicketUpdate, t.email
            FROM account_settings as a, tblSecurity as t
            WHERE a.primaryHelpdeskUserID=t.id AND a.accountID=" . $_SESSION['accountID'] . "";
          $resultSQLAlert  = dbquery($strSQLAlert);
          $rowSQLAlert     = mysql_fetch_row($resultSQLAlert);
          $ccTicketCreate  = $rowSQLAlert[0];
          $ccTicketUpdate  = $rowSQLAlert[1];
          $helpdeskEmail   = $rowSQLAlert[2];

          // Edit a comment
          If ($commentID) {
              // Note: If you change how the change notation stuff works (below), you must make
                 // sure to update related code in viewComments.php (which hides some of this stuff).

              $strAddendum = " <br>".$progText9." ".date("m/d/Y, H:i,")." ".$progText10." <i>".$strNewAuthor."</i>: &nbsp;".stripslashes($strComment);

              If ($oldStatus != $cboStatus) {
                  $strAddendum .= "<br> &nbsp; - ".$progText11.": ".writeNA($oldStatus)." -> $cboStatus.";
              }
              If ($oldPriority != $cboPriority) {
                  $strAddendum .= "<br> &nbsp; - ".$progText12.": ".writeNA($oldPriority)." -> $cboPriority.";
              }

              If ($oldCategoryID != $cboCategory) {
                  $strAddendum .= "<br> &nbsp; - ".$progText27.": ".writeNA($oldCategoryName)." -> $strCategory.";
              }

              If ($oldAssignedUserID != $cboAssignedUserID) {
                  $strAddendum .= "<br> &nbsp; - ".$progText23.": ".writeNA($oldAssignedUserName)." -> $strNewAssignee.";
              }

              // Make the comment email-safe (ie, replace/strip HTML) and add link to associated system.
              $strMessage = $oldComment.$strAddendum;
              $strMessage = str_replace("<br>", "\n", $strMessage);
              $strMessage = str_replace("&nbsp;", " ", $strMessage);
              $strMessage = strip_tags($strMessage);

              If ($commentType == 'h') {
                  $strMessage .= "\n\n".$urlPrefix."://".$homeURL."/showfull.php?hardwareID=".$subjectID;
              } elseif ($commentType == 'u') {
                  $strMessage .= "\n\n".$urlPrefix."://".$homeURL."/viewUser.php?viewID=".$subjectID;
              } else {
                  $strMessage .= "\n\n".$urlPrefix."://".$homeURL."/admin_comments.php?commentID=".$commentID;
              }

              // Send email to old assigned user letting them know about the update.
              // Don't send the email if the user updating the ticket is also the old assigned
                 // user; they know what they've done  :)
              If ($oldAssignedUserEmail AND ($oldAssignedUserID != $_SESSION['userID'])) {
                  mail($oldAssignedUserEmail, ($progText24.": ".date("m-d-Y")), $strMessage,
                    "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
                  # $mailSentToOldAssigned = 1;
              }

              // Send email to new assigned user, if applicable (unless they are
                 // the person making the update.)
              If (($oldAssignedUserID != $cboAssignedUserID) AND ($cboAssignedUserID != $_SESSION['userID'])) {
                  mail($strAssigneeEmail, ($progText24.": ".date("m-d-Y")), $strMessage,
                    "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
                  # $mailSentToNewAssigned = 1;
              }

              // If he hasn't already been emailed above, email the helpdesk-contact-person, too.
              If ($ccTicketUpdate) {
                  If (($oldAssignedUserEmail != $helpdeskEmail) AND ($strAssigneeEmail != $helpdeskEmail)) {
                      mail($helpdeskEmail, ($progText24.": ".date("m-d-Y")), $strMessage,
                        "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
                  }
              }

              $strComment = addslashes($oldComment.$strAddendum);
              $strSQL5 = "UPDATE comments SET commentStatus=".makeNull($cboStatus,1).",
                commentPriority=".makeNull($cboPriority,1).", assignedUserID=".makeNull($cboAssignedUserID).", commentLocationID=".makeNull($cboLocationID).",
                commentText='$strComment', identityCode=".makeNull($strIdentityCode,1).",
                categoryID=".makeNull($cboCategory)." WHERE commentID=$commentID AND accountID=" . $_SESSION['accountID'] . "";
              $notify = "update";
              $result5 = dbquery($strSQL5);

          // Adding a new comment
          } Else {
              $strComment = $progText8." ".$progText10." <i>".$strNewAuthor."</i>: &nbsp;".$strComment;

              $strSQL5 = "INSERT INTO comments (subjectID, authorID, subjectType, commentDate,
                commentText, commentPriority, commentStatus, assignedUserID, commentLocationID, identityCode, categoryID,
                accountID) VALUES (".makeNull($subjectID).", " . $_SESSION['userID']. ", ".makeNull($commentType,1).",
                '".date("YmdHis")."', '$strComment', ".makeNull($cboPriority,1).", ".makeNull($cboStatus,1).",
                ".makeNull($cboAssignedUserID).", ".makeNull($cboLocationID).", ".makeNull($strIdentityCode,1).", ".makeNull($cboCategory).", ".
                $_SESSION['accountID'] .")";
              $notify = "insert";
              $result5 = dbquery($strSQL5);
              $commentID = mysql_insert_id();

              // Send email to assigned user (unless they are the person making the update.)
              $strMessage = stripslashes($strComment);
              $strMessage = str_replace("<br>", "\n", $strMessage);
              $strMessage = str_replace("&nbsp;", " ", $strMessage);
              $strMessage = strip_tags($strMessage);

              If ($commentType == 'h') {
                  $strMessage .= "\n\n".$urlPrefix."://".$homeURL."/showfull.php?hardwareID=".$subjectID;
              } elseif ($commentType == 'u') {
                  $strMessage .= "\n\n".$urlPrefix."://".$homeURL."/viewUser.php?viewID=".$subjectID;
              } else {
                  $strMessage .= "\n\n".$urlPrefix."://".$homeURL."/admin_comments.php?commentID=".$commentID;
              }

              If ($strAssigneeEmail AND ($cboAssignedUserID != $_SESSION['userID'])) {
                  mail($strAssigneeEmail, ($progText24.": ".date("m-d-Y")), $strMessage,
                    "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
              }

              // If he hasn't already been emailed above, email the helpdesk-contact-person, too.
              If ($ccTicketCreate) {
                  If ($strAssigneeEmail != $helpdeskEmail) {
                      mail($helpdeskEmail, ($progText24.": ".date("m-d-Y")), $strMessage,
                        "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
                  }
              }
          }

          If (($commentType == 'h') AND ($oldSystemStatus != $cboSystemStatus)) {
             $strSQLX = "UPDATE hardware SET hardwareStatus='$cboSystemStatus' WHERE hardwareID=$subjectID
               AND accountID=" . $_SESSION['accountID'] . "";
             $resultX = dbquery($strSQLX);
          }

         // Send a tracker to the subject of this email; generate an ID code if one does not already exist.
          If ($chkNotifyCustomer OR $strIdentityCode) {
              If (!$strIdentityCode) {
                  $newCode = TRUE;

                  $strIdentityCode = $commentID.",".$_SESSION['accountID'];
                  $strTempString = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
                  for ($i = 0; $i < 4; $i++) {
                      srand ((double) microtime() * 1000000);
                      $intPos = rand(0, 33);
                      $strTempChar = substr($strTempString, $intPos, 1);
                      $strIdentityCode .= $strTempChar;
                  }
                  $strIdentityCode = md5($strIdentityCode);
              }

              If ($strSubjectEmail) {
                  If ($newCode) {
                      $strSQL6 = "UPDATE comments SET identityCode='$strIdentityCode' WHERE
                        commentID=$commentID AND accountID=" . $_SESSION['accountID'] . "";
                      $result6 = dbquery($strSQL6);
                  }

                  $strMessage = stripslashes($strComment);
                  $strMessage = str_replace("<br>", "\n", $strMessage);
                  $strMessage = str_replace("&nbsp;", " ", $strMessage);
                  $strMessage = strip_tags($strMessage);
                  $strMessage = $progText25."\n\n".$urlPrefix."://".$homeURL."/viewComments.php?idCode=".$strIdentityCode."\n\n".$strMessage;

                  mail($strSubjectEmail, ($progText26.": ".date("m-d-Y")), $strMessage,
                    "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
              }
          }

          If ($commentType == 'h') {
              redirect("showfull.php", "hardwareID=$subjectID&notify=$notify");
          } elseif ($commentType == 'u') {
              redirect("viewUser.php", "viewID=$subjectID&notify=$notify");
          } else {
              redirect("commentLog.php", "notify=$notify");
          }
      }
  } Else {
      $cboStatus          = $oldStatus;
      $cboPriority        = $oldPriority;
      $cboCategory        = $oldCategoryID;
      $cboAssignedUserID  = $oldAssignedUserID;
      $cboSystemStatus    = $oldSystemStatus;
      $cboLocationID      = $oldLocationID;

      # $chkNotifyCustomer = 1; # default tracker selection to true in all cases; if it isn't appropriate
                                # (because no user email exists), that's OK - value will be discarded upon
                                # form submit.
  }

  writeHeader($pageTitle, 735);
  declareError(TRUE);

  echo $strInstructions;
?>

<font color='ff0000'>*</font> <?=$progText13;?>.
<?
  // assigned to, priority, and status are not optional if this is a subjectless ticket
  If ($commentType) {
      echo "<br><font color='006633'>*</font> ".$progTextBlock4."\n\n";
      $strStarColor = "006633";
  } Else {
      $strStarColor = "ff0000";
  }
?><p>

<FORM METHOD="post" ACTION="admin_comments.php">
<TABLE border='0' cellpadding='4' cellspacing='0'>

   <TR>
      <TD width='123'><?=$progText960;?>: &nbsp;</TD>
      <TD>
          <?
             $strSQL7 = "SELECT * FROM commentCategories WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY categoryName ASC";
             $result7 = dbquery($strSQL7);
             If (mysql_num_rows($result7) < 1) {
                 echo $progText437; # N/A
             } Else {
                 echo "<select name='cboCategory' size='1'>\n";
                 echo "<option value=\"\">&nbsp;</OPTION>\n";
                 while ($row7 = mysql_fetch_array($result7)) {
                     echo "   <OPTION VALUE=\"".$row7['categoryID']."\" ";
                     echo writeSelected($cboCategory, $row7['categoryID']);
                     echo ">".$row7['categoryName']."</OPTION>\n";
                 }
                 echo "</select>\n";
             }
          ?>
      </TD>
   </TR>
   <TR>
      <TD width='123'><font color='<?=$strStarColor;?>'>*</font> <?=$progText22;?>: &nbsp;</TD>
      <TD>
        <?=buildUserSelect($cboAssignedUserID, false, '', false, false, "AND securityLevel < 2", "cboAssignedUserID");?>
      </TD>
<? if ($getLocation && !$_SESSION['stuckAtLocation']) { ?>      
   </TR>
      <TD width='123'><font color='<?=$strStarColor;?>'>*</font> <?=$progText34;?>: &nbsp;</TD>
      <TD>
        <?buildLocationSelect($cboLocationID, false, false, true);?>
      </TD>
   </TR>   
<? } ?>
   <TR>
      <TD><font color='<?=$strStarColor;?>'>*</font> <?=$progText6;?>: &nbsp;</TD>
      <TD>
        <select name='cboStatus' size='1'>
          <option value=''>&nbsp;</option>
          <option value='Open' <?=writeSelected("Open", $cboStatus);?>><?=$progText14;?></option>
          <option value='In Progress' <?=writeSelected("In Progress", $cboStatus);?>><?=$progText15;?></option>
          <option value='Resolved' <?=writeSelected("Resolved", $cboStatus);?>><?=$progText16;?></option>
        </select>
      </TD>
   </TR>
   <TR>
      <TD width='123'><font color='<?=$strStarColor;?>'>*</font> <?=$progText7;?>: &nbsp;</TD>
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
<?
  If ($commentID) {
      echo "<TR><TD colspan='2'>&nbsp;</TD></TR>";
      echo "<TR><TD valign='top' width='123'> ".$progText18.": &nbsp;</TD>";
      echo "<TD><font color='#666666'>$oldComment</font></TD></TR>\n";
      $strFieldTitle = $progText19;
  } Else {
      $strFieldTitle = $progText20;
  }
?>
   <TR>
      <TD valign='top' width='123'><font color='ff0000'>*</font> <?=$strFieldTitle;?>: &nbsp;</TD>
      <TD><textarea name='txtComment' rows='7' cols='50' wrap='virtual'><?=antiSlash($strComment);?></textarea></TD>
   </TR>

   <TR>
      <TD colspan='2'>&nbsp;</TD>
   </TR>

<?
  // If this comment is on a system...
  If ($commentType == 'h') {
?>
   <TR>
      <TD colspan='2'><?=$progText30;?>:&nbsp;
        <select name='cboSystemStatus' size='1'>
          <option value='w' <?=writeSelected("w", $cboSystemStatus);?>><?=$progText413;?></option>
          <option value='n' <?=writeSelected("n", $cboSystemStatus);?>><?=$progText414;?></option>
          <option value='i' <?=writeSelected("i", $cboSystemStatus);?>><?=$progText415;?></option>
        </select>
      </TD>
   </TR>
<?
  }

  // Only show chkNotifyUser if it hasn't been selected in the past ($strIdentityCode does
  // not exist) and is possible ($strSubjectEmail exists)
  If ($strSubjectEmail AND !$strIdentityCode) {
?>

   <TR>
      <TD colspan='2'><?=$progText28;?>:&nbsp;
      <input type='checkbox' name='chkNotifyCustomer' value='1' <?=writeChecked($chkNotifyCustomer, "1");?>></TD>
   </TR>
<?
  } ElseIf ($strIdentityCode) {
?>
   <TR>
      <TD colspan='2'>&nbsp; <font color='gray'><i>* <?=$progText29;?>.</i></font></TD>
   </TR>
<?
  }
?>
   <TR>
      <TD colspan='2'>&nbsp;</TD>
   </TR>
   <TR>
      <TD colspan='2'><input type='submit' name='btnSubmit' value='<?=$progText21;?>'></TD>
   </TR>
  </TABLE>

  <input type='hidden' name='subjectID' value='<?=$subjectID;?>'>
  <input type='hidden' name='commentID' value='<?=$commentID;?>'>
  <input type='hidden' name='commentType' value='<?=$commentType;?>'>
</FORM>

<?
  writeFooter();
?>
