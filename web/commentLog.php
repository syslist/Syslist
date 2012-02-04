<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  $notify = getOrPost('notify');
  notifyUser($notify);
  writeHeader("");
  declareError(TRUE);

  // If no priority selected, default to 5
  $cboPriority = cleanFormInput(getOrPost('cboPriority'));
  If (!$cboPriority) {
      $cboPriority = 5;
  }
  $extraSQL = "c.commentPriority <= '$cboPriority' AND ";

  // If no status selected, page will show all unresolved issues
  $cboStatus = cleanFormInput(getOrPost('cboStatus'));
  If ($cboStatus == 'All') {
      $cboStatus = "";
  } ElseIf (($cboStatus == 'OpenPlusInProgress') OR !$cboStatus) {
      $cboStatus = "OpenPlusInProgress";
      $extraSQL .= "c.commentStatus != 'Resolved' AND ";
  } ElseIf ($cboStatus) {
      $extraSQL .= "c.commentStatus = '$cboStatus' AND ";
  }

  // If the user viewing this page has the right to be assigned and update tickets, then
  // show him his tickets (unless he has explicitely requested to see something else).
  $cboAssignedUserID = cleanFormInput(getOrPost('cboAssignedUserID'));
  If ($cboAssignedUserID == 'All') {
      $cboAssignedUserID = "";
  } ElseIf (($_SESSION['sessionSecurity'] < 2) AND !$cboAssignedUserID) {
      $cboAssignedUserID = $_SESSION['userID'];
  }
  If ($cboAssignedUserID) {
      $extraSQL .= "c.assignedUserID=$cboAssignedUserID AND ";
  }

  // If no $cboCategory page will just show all issues.
  $cboCategory = cleanFormInput(getOrPost('cboCategory'));
  If ($cboCategory) {
      $extraSQL .= "c.categoryID=$cboCategory AND ";
  }

  $btnQuickFind = cleanFormInput(getOrPost('btnQuickFind'));
  If ($btnQuickFind) {
      $strQuickFind = cleanFormInput(getOrPost('txtQuickFind'));
      $extraSQL .= "c.commentText LIKE '%$strQuickFind%' AND ";
  }
  
  /*
    1) A super-user (i.e. someone NOT location-locked) can see any ticket, period.
    2) A location-locked user can only see tickets that:
        A) Are linked to a user or hardware in their location (subjectID), *or*
        B) Were originally created by a user in their location (authorID), *or*
        C) Are assigned to a user in their location (assignedUserID?)
    3) If commentLocationID is set, it has to be the same as the user's stuck location
   */
  if ($_SESSION['stuckAtLocation']) {
      
      // System tickets check hardware subject, author, and assignedUser
      $extraSQLSystem = $extraSQL . "((c.commentLocationID=" . $_SESSION['locationStatus'] . ") OR ";
      $extraSQLSystem .= "(c.commentLocationID IS NULL AND ((h.locationID=" . $_SESSION['locationStatus'] . ") OR ";
      $extraSQLSystem .= "(s.userLocationID=" . $_SESSION['locationStatus'] .") OR ";
      $extraSQLSystem .= "(s2.userLocationID=" . $_SESSION['locationStatus'] . ")))) AND ";
      
      // Users tickets check user subject, author, and assignedUser
      $extraSQLUser = $extraSQL . "((c.commentLocationID=" . $_SESSION['locationStatus'] . ") OR ";
      $extraSQLUser .= "(c.commentLocationID IS NULL AND ((s3.userLocationID=" . $_SESSION['locationStatus'] . ") OR ";
      $extraSQLUser .= "(s.userLocationID=" . $_SESSION['locationStatus'] .") OR ";
      $extraSQLUser .= "(s2.userLocationID=" . $_SESSION['locationStatus'] . ")))) AND ";
      
      // Subjectless tickets check author and assignedUser
      $extraSQLSubjectless = $extraSQL . "((c.commentLocationID=" . $_SESSION['locationStatus'] . ") OR ";
      $extraSQLSubjectless .= "(c.commentLocationID IS NULL AND ";
      $extraSQLSubjectless .= "((s.userLocationID=" . $_SESSION['locationStatus'] .") OR ";
      $extraSQLSubjectless .= "(s2.userLocationID=" . $_SESSION['locationStatus'] . ")))) AND ";     
  } else {
      $extraSQLSystem = $extraSQL;
      $extraSQLUser = $extraSQL;
      $extraSQLSubjectless = $extraSQL;
  }

  echo "<form method='get' action='commentLog.php'>\n\n";

  If ($_SESSION['sessionSecurity'] < 2) {
      echo "<table border='0' cellpadding='0' cellspacing='3' width='620'>
              <tr>
                <td align='left'><table border='0' cellpadding='0' cellspacing='0'>
                  <tr><td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><a class='action' href='commentCategories.php'>".$progText1011."</a></td>
                      <td><nobr>&nbsp; &nbsp; &nbsp; </nobr></td>
                      <td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><A class='action' HREF='admin_comments.php'>".$progText225."</A></td>
                  </tr></table>
                </td>
                <td align='right'>
                  ".$progText226.":&nbsp;
                  <input type='text' name='txtQuickFind' size='12' value='".$strQuickFind."'>
                  <input type='hidden' name='btnQuickFind' value='1'>
                  <INPUT TYPE='submit' NAME='qf' VALUE='".$progText21."'>
                </td>
              </tr>
            </table>\n\n";
  }

?>
  <table border='0' cellspacing='0' cellpadding='4' width='620'>
    <tr class='title'>
      <td><b>&nbsp;<?=$progText6;?> </b></td>
      <td><b>&nbsp;<?=$progText7;?> </b></td>
      <td><b>&nbsp;<?=$progText138;?> </b></td>
      <td><b>&nbsp;<?=$progText960;?> </b></td>
      <td>&nbsp;</td>
    </tr>

    <tr class='title'>
      <td><select name='cboStatus' size='1'>
          <option value='All'>* <?=$progText431;?> *</option>
          <option value='Open' <?=writeSelected("Open", $cboStatus);?>><?=$progText14;?></option>
          <option value='In Progress' <?=writeSelected("In Progress", $cboStatus);?>><?=$progText15;?></option>
          <option value='OpenPlusInProgress' <?=writeSelected("OpenPlusInProgress", $cboStatus);?>><?=$progText14." + ".$progText15;?></option>
          <option value='Resolved' <?=writeSelected("Resolved", $cboStatus);?>><?=$progText16;?></option>
        </select>
      </td>
      <td><select name='cboPriority' size='1'>
          <option value=''>* <?=$progText431;?> *</option>
          <option value='1' <?=writeSelected($cboPriority, 1);?>>1</option>
          <option value='2' <?=writeSelected($cboPriority, 2);?>>2+</option>
          <option value='3' <?=writeSelected($cboPriority, 3);?>>3+</option>
          <option value='4' <?=writeSelected($cboPriority, 4);?>>4+</option>
        </select> &nbsp;
      </td>
      <td><?
         // If stuck at a location, you can only see users in that location, and super-users
	     if ($_SESSION['stuckAtLocation']) {
                 $stuckSQL = " AND (userLocationID=" . $_SESSION['locationStatus'] . " OR userLocationID IS NULL) ";
	     } else {
	         $stuckSQL = "";
	     }
             $strSQL = "SELECT id, firstName, middleInit, lastName, userID FROM tblSecurity WHERE
               accountID=" . $_SESSION['accountID'] . " AND hidden='0' AND securityLevel < 2" . $stuckSQL . " ORDER BY lastName";
             $result = dbquery($strSQL);
             echo "<select name='cboAssignedUserID' size='1'>\n";
             echo "<option value=\"All\">* ".$progText431." *</OPTION>\n";
             while ($row = mysql_fetch_array($result)) {
                 echo "   <OPTION VALUE=\"".$row['id']."\" ";
                 echo writeSelected($cboAssignedUserID, $row['id']);
                 # echo ">".buildName($row["firstName"], $row["middleInit"], $row["lastName"], 0)."</OPTION>\n";
                 echo ">".$row["userID"]."</OPTION>\n";
             }
             echo "</select>\n";

        # $aryExtraOptions = array("* ".$progText811." *");
        # echo buildUserSelect($assignedUserID, false, '', false, false, "AND securityLevel < 2", "cboAssignedUserID", $aryExtraOptions);
      ?></td>
      <td>
          <?
             $strSQL = "SELECT * FROM commentCategories WHERE accountID=" . $_SESSION['accountID'] . "";
             $result = dbquery($strSQL);
             If (mysql_num_rows($result) < 1) {
                 echo $progText437; # N/A
             } Else {
                 echo "<select name='cboCategory' size='1'>\n";
                 echo "<option value=\"\">* ".$progText431." *</OPTION>\n";
                 while ($row = mysql_fetch_array($result)) {
                     echo "   <OPTION VALUE=\"".$row['categoryID']."\" ";
                     echo writeSelected($cboCategory, $row['categoryID']);
                     echo ">".$row['categoryName']."</OPTION>\n";
                 }
                 echo "</select>\n";
             }
          ?>
      </td>
      <td>
        <input type='submit' name='btnSubmit' value='<?=$progText21;?>'>
      </td>
    </tr>
  </table>
  </form>
  <p>
<?
  // Using an array, because there's no good way to build one query
  // that retrieves all the data you need for all ticket types (user,
  // hardware, and subjectless)
  $aryComments = array();
  $aryIndex = 0;

  // Get all tickets associated with systems; put in array
  $strSQL = "SELECT s.firstName, s.middleInit, s.lastName, c.*, h.hardwareStatus, h.ipAddress,
    t.visDescription, t.visManufacturer, o.categoryName
    FROM (comments as c, tblSecurity as s, hardware as h, hardware_types as t, tblSecurity as s2)
    LEFT JOIN commentCategories as o ON o.categoryID=c.categoryID 
    WHERE $extraSQLSystem c.assignedUserID=s.id AND c.subjectType='h' AND c.authorID=s2.id 
    AND c.commentStatus IS NOT NULL AND c.subjectID=h.hardwareID AND h.hardwareTypeID=t.hardwareTypeID
    AND c.accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  While ($row3 = mysql_fetch_array($result)) {
      $aryComments[$aryIndex]["subjectType"]      = "h";
      $aryComments[$aryIndex]["commentID"]        = $row3["commentID"];
      $aryComments[$aryIndex]["subjectID"]        = $row3["subjectID"];
      $aryComments[$aryIndex]["visDescription"]   = $row3["visDescription"];
      $aryComments[$aryIndex]["visManufacturer"]  = $row3["visManufacturer"];
      $aryComments[$aryIndex]["hardwareStatus"]   = $row3["hardwareStatus"];
      $aryComments[$aryIndex]["firstName"]        = $row3["firstName"];
      $aryComments[$aryIndex]["middleInit"]       = $row3["middleInit"];
      $aryComments[$aryIndex]["lastName"]         = $row3["lastName"];
      $aryComments[$aryIndex]["commentPriority"]  = $row3["commentPriority"];
      $aryComments[$aryIndex]["commentStatus"]    = $row3["commentStatus"];
      $aryComments[$aryIndex]["commentDate"]      = $row3["commentDate"];
      $aryComments[$aryIndex]["commentText"]      = $row3["commentText"];
      $aryComments[$aryIndex]["categoryName"]     = $row3["categoryName"];
      $aryIndex++;
  }

  // Get all tickets associated with users; put in array
  $strSQL = "SELECT s.firstName, s.middleInit, s.lastName, c.*, o.categoryName
    FROM (comments as c, tblSecurity as s)
    LEFT JOIN commentCategories as o ON o.categoryID=c.categoryID 
    LEFT JOIN tblSecurity as s2 ON c.authorID=s2.id 
    LEFT JOIN tblSecurity as s3 ON c.subjectID=s3.id 
    WHERE $extraSQLUser c.assignedUserID=s.id AND c.subjectType='u' AND
    c.commentStatus IS NOT NULL AND c.accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  While ($row3 = mysql_fetch_array($result)) {

      $strSQL2 = "SELECT s.firstName, s.middleInit, s.lastName FROM tblSecurity as s WHERE
        s.id=".$row3["subjectID"]." AND s.accountID=" . $_SESSION['accountID'] . "";
      $result2 = dbquery($strSQL2);
      While ($row2 = mysql_fetch_array($result2)) {
          $aryComments[$aryIndex]["subjFirstName"]   = $row2["firstName"];
          $aryComments[$aryIndex]["subjMiddleInit"]  = $row2["middleInit"];
          $aryComments[$aryIndex]["subjLastName"]    = $row2["lastName"];
      }

      $aryComments[$aryIndex]["subjectType"]      = "u";
      $aryComments[$aryIndex]["commentID"]        = $row3["commentID"];
      $aryComments[$aryIndex]["subjectID"]        = $row3["subjectID"];
      $aryComments[$aryIndex]["firstName"]        = $row3["firstName"];
      $aryComments[$aryIndex]["middleInit"]       = $row3["middleInit"];
      $aryComments[$aryIndex]["lastName"]         = $row3["lastName"];
      $aryComments[$aryIndex]["commentPriority"]  = $row3["commentPriority"];
      $aryComments[$aryIndex]["commentStatus"]    = $row3["commentStatus"];
      $aryComments[$aryIndex]["commentDate"]      = $row3["commentDate"];
      $aryComments[$aryIndex]["commentText"]      = $row3["commentText"];
      $aryComments[$aryIndex]["categoryName"]     = $row3["categoryName"];
      $aryIndex++;
  }

  // Get all subjectless tickets; put in array
  $strSQL = "SELECT s.firstName, s.middleInit, s.lastName, c.*, o.categoryName
    FROM (comments as c, tblSecurity as s)
    LEFT JOIN commentCategories as o ON o.categoryID=c.categoryID 
    LEFT JOIN tblSecurity as s2 ON c.authorID=s2.id
    WHERE $extraSQLSubjectless c.assignedUserID=s.id AND c.subjectType IS NULL AND
    c.commentStatus IS NOT NULL AND c.accountID=" . $_SESSION['accountID'] . "";

  $result = dbquery($strSQL);
  While ($row3 = mysql_fetch_array($result)) {
      $aryComments[$aryIndex]["subjectType"]      = "";
      $aryComments[$aryIndex]["commentID"]        = $row3["commentID"];
      $aryComments[$aryIndex]["subjectID"]        = $row3["subjectID"];
      $aryComments[$aryIndex]["firstName"]        = $row3["firstName"];
      $aryComments[$aryIndex]["middleInit"]       = $row3["middleInit"];
      $aryComments[$aryIndex]["lastName"]         = $row3["lastName"];
      $aryComments[$aryIndex]["commentPriority"]  = $row3["commentPriority"];
      $aryComments[$aryIndex]["commentStatus"]    = $row3["commentStatus"];
      $aryComments[$aryIndex]["commentDate"]      = $row3["commentDate"];
      $aryComments[$aryIndex]["commentText"]      = $row3["commentText"];
      $aryComments[$aryIndex]["categoryName"]     = $row3["categoryName"];
      $aryIndex++;
  }

  if (!$aryIndex) { # if no records returned
      echo $progText216."<P>";
  } else {
      $aryComments = arraySuperMultiSort($aryComments, 'commentPriority', SORT_ASC, 'commentDate', SORT_DESC);
  }

  for ($i = 0; $i < $aryIndex; $i++) {
      echo "<table border='0' cellpadding='1' cellspacing='0' width='620' bgcolor='#666666'><tr><td>\n";
      echo "<table border='0' cellpadding='3' cellspacing='0' width='620' bgcolor='#FFFFFF'>\n";

      echo "<tr bgcolor='#FFF7F7' align='left' valign='top'><td width='230'>";
      If ($aryComments[$i]["subjectType"] == 'h') {
          echo "<b>".$progText217.":</b> &nbsp;<a href='showfull.php?hardwareID=".$aryComments[$i]["subjectID"]."'>".writePrettySystemName($aryComments[$i]["visDescription"], $aryComments[$i]["visManufacturer"])."</a>";
      } ElseIf ($aryComments[$i]["subjectType"] == 'u') {
          echo "<b>".$progText32.":</b> &nbsp;<a href='viewUser.php?viewID=".$aryComments[$i]["subjectID"]."'>".buildName($aryComments[$i]["subjFirstName"], $aryComments[$i]["subjMiddleInit"], $aryComments[$i]["subjLastName"], 1)."</a>";
      } Else {
          echo "<b>".$progText437."</b>"; # n/a
          # echo $progText472;
      }
      echo "</td><td width='130'><b>".$progText221.":</b>&nbsp; ".writeNA($aryComments[$i]["commentPriority"])." (of 5) &nbsp;";
      echo "</td><td width='140'><nobr><b>".$progText222.":</b> &nbsp;</nobr><nobr>".writeNA(writeCommentStatus($aryComments[$i]["commentStatus"]))." &nbsp;</nobr>";
      If ($_SESSION['sessionSecurity'] < 2) {
          echo "</td><td width='120'>";
          echo "<a class='action' href='admin_comments.php?commentID=".$aryComments[$i]["commentID"]."'>";
          echo $progText219."?</a>\n";
      } Else {
          echo "</td><td width='120' align='right'>&nbsp;";
      }

      echo "</td></tr>\n<tr bgcolor='#FFF7F7' align='left' valign='top'><td width='230'>";
      $strAssignedTo = buildName($aryComments[$i]["firstName"], $aryComments[$i]["middleInit"], $aryComments[$i]["lastName"], 1);
      echo "<b>".$progText138.":</b>&nbsp; $strAssignedTo &nbsp;";
      echo "</td><td colspan='2' width='270'><b>".$progText960.":</b> &nbsp;".writeNA($aryComments[$i]["categoryName"]);
      echo "</td><td width='120'><nobr><b>".$progText223.":</b> &nbsp;</nobr><br><nobr>".displayDateTime($aryComments[$i]["commentDate"])." &nbsp;</nobr>";
      echo "</td></tr>\n";
      echo "<tr><td class='row1' colspan='4' width='620'>".$aryComments[$i]["commentText"]."</td></tr>\n";
      echo "</table>\n</td></tr></table><p>\n\n";
  }

  writeFooter();
?>
