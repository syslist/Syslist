<?
  Include("Includes/global.inc.php");
?>

<HTML>
<HEAD>
<TITLE>Syslist</TITLE>

<SCRIPT LANGUAGE="javascript">
<!--
function warn_on_submit(msg)
{
        if (!confirm(msg)) {
                alert("<?=$progText687;?>");
                return false;
        }
}

function popupWin(myLink, windowName, myWidth, myHeight) {
     var defaultWidth=300;
     var defaultHeight=175;
     var href;
     if (! window.focus) return true;
     if (typeof(myLink) == 'string') href=myLink;
     else href=myLink.href;
     if (myWidth=='') myWidth=defaultWidth;
     if (myHeight=='') myHeight=defaultHeight;
     myLeft=screen.width-(myWidth+20);
     myTop=screen.height-(myHeight+65);
     window.open(href,windowName,'width='+myWidth+',height='+myHeight+',left='+myLeft+',top='+myTop+',scrollbars=yes,dependent=yes,resizable=no');
     return false;
}
//-->
</script>
<LINK REL=StyleSheet HREF="styles.css" TYPE="text/css">
</HEAD>

<BODY bgcolor="#FFFFFF" vlink='blue' alink='blue'>

<img src='Images/logo.gif' border='0' alt="http://www.syslist.com"><p>

<?
  $idCode = validateChoice($progText1023, getOrPost('idCode'));

  If (!$strError) {
      $strSQL = "SELECT c.*, s.email FROM comments as c LEFT JOIN tblSecurity as s ON c.assignedUserID=s.id
        WHERE c.identityCode='$idCode'";
      $result = dbquery($strSQL);
      
      If (mysql_num_rows($result) < 1) {
          $strError = $progText1023;
      }
      While ($row = mysql_fetch_array($result)) {
          $tempAccountID         = $row["accountID"];
          $commentID             = $row["commentID"];
          $subjectID             = $row["subjectID"];
          $commentType           = $row["subjectType"];
          $oldComment            = $row["commentText"];
          $oldStatus             = $row["commentStatus"];
          $oldAssignedUserID     = $row["assignedUserID"];
          $oldAssignedUserEmail  = $row["email"];
      }

      // Get subject's name (which we need for updating comment text.)
      $strSQL2 = "";
      If ($commentType == 'h') {
          $strSQL2 = "SELECT s.firstName, s.middleInit, s.lastName FROM tblSecurity as s, hardware as h
            WHERE h.hardwareID=$subjectID AND h.userID=s.id AND s.accountID=$tempAccountID";
      } ElseIf ($commentType == 'u') {
          $strSQL2 = "SELECT s.firstName, s.middleInit, s.lastName FROM tblSecurity as s WHERE
            s.id=$subjectID AND s.accountID=$tempAccountID";
      }
      $result2      = dbquery($strSQL2);
      $row2         = mysql_fetch_row($result2);
      $subjectName  = buildName($row2[0], $row2[1], $row2[2], 1);

  // idCode is missing
  } Else {
      $hideForm = TRUE;
  }
  
  If (getOrPost('btnSubmit') AND !$strError) {

      // Get email address of primary helpdesk contact
      $strSQLAlert = "SELECT a.ccTicketCreate, a.ccTicketUpdate, t.email
        FROM account_settings as a, tblSecurity as t
        WHERE a.primaryHelpdeskUserID=t.id AND a.accountID=" . $_SESSION['accountID'] . "";
      $resultSQLAlert  = dbquery($strSQLAlert);
      $rowSQLAlert     = mysql_fetch_row($resultSQLAlert);
      $helpdeskEmail   = $rowSQLAlert[2];
      $ccTicketUpdate  = $rowSQLAlert[1];

      $strComment = validateText($progText8, getOrPost('txtComment'), 1, 65535, TRUE, FALSE);

      If (!$strError) {
          $strAddendum = " <br>".$progText9." ".date("m/d/Y, H:i,")." ".$progText10." <i>".$subjectName."</i>: &nbsp;".stripslashes($strComment);
          If ($oldStatus == 'Resolved') {
              $strAddendum .= "<br> &nbsp; - ".$progText11.": Resolved -> Open";
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

          If ($oldAssignedUserEmail) {
              // Send email to old assigned user letting them know about the update.
              mail($oldAssignedUserEmail, ($progText24.": ".date("m-d-Y")), $strMessage,
                   "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
          }

          If ($ccTicketUpdate) {
              // If he hasn't already been emailed above, email the helpdesk-contact-person, too.
              If ($oldAssignedUserEmail != $helpdeskEmail) {
                  mail($helpdeskEmail, ($progText24.": ".date("m-d-Y")), $strMessage,
                    "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
              }
          }

          $strComment = addslashes($oldComment.$strAddendum);
      
          If ($oldStatus == 'Resolved') {
              $extraSQL = ", commentStatus='Open'";
          }
          $strSQL3 = "UPDATE comments SET commentText='$strComment'".$extraSQL." WHERE identityCode='$idCode'";
          $result3 = dbquery($strSQL3);

          $strError = $progText71; # successful update
          $hideForm = TRUE;
      }
  }

  declareError(TRUE);
  
  // Hide all the change notation portions of the comment (ie, the stuff showing
  // that priority was altered, etc.
  $oldComment = preg_replace("/<br> &nbsp;.*?\./", "", $oldComment);
  
  If (!$hideForm) {
?>

<FORM METHOD="post" ACTION="viewComments.php">
<TABLE border='0' cellpadding='4' cellspacing='0' width='700'>

  <TR>
      <TD colspan='2'>&nbsp;</TD>
  </TR>
  <TR><TD valign='top' width='110'><?=$progText18;?>: &nbsp;</TD>
      <TD width='590'><font color='#666666'><?=$oldComment;?></font></TD>
  </TR>
   <TR>
      <TD valign='top' width='110'><?=$progText19;?>:&nbsp;</TD>
      <TD width='590'><textarea name='txtComment' rows='7' cols='65' wrap='virtual'><?=antiSlash($strComment);?></textarea></TD>
   </TR>
   <TR>
      <TD colspan='2'>&nbsp;</TD>
   </TR>
   <TR>
      <TD colspan='2'><input type='submit' name='btnSubmit' value='<?=$progText21;?>'></TD>
   </TR>
  </TABLE>

  <input type='hidden' name='idCode' value='<?=antiSlash($idCode);?>'>
</FORM>

<?
  }
?>

</BODY>
</HTML>
