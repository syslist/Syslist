<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 900);

  $notify = getOrPost('notify');
  notifyUser($notify);

  $viewID = cleanFormInput(getOrPost('viewID'));

  $strSQL = "SELECT s.userID, s.firstName, s.middleInit, s.lastName, s.email, s.securityLevel,
    s.userLocationID, s.picURL, l.locationName
    FROM tblSecurity as s
    LEFT JOIN locations as l ON l.locationID=s.userLocationID
    WHERE s.accountID=" . $_SESSION['accountID'] . " AND s.id=".$viewID;
  $result = dbquery($strSQL);
  $row = mysql_fetch_row($result);

  $strUserID      = $row[0];
  $strFirstName   = $row[1];
  $strMiddleInit  = $row[2];
  $strLastName    = $row[3];
  $strEmail       = $row[4];
  $intLevel       = $row[5];
  $intLocationID  = $row[6];
  $picURL         = $row[7];
  $strLocation    = $row[8];

  writeHeader($progText511, "", FALSE, "&nbsp;", $picURL, $viewID, "user");
  declareError(TRUE);
?>

<p><table border='0' cellpadding='2'>
    <tr>
      <td><b><?=$progText255;?>: &nbsp;</b></td>
      <td><?echo writeNA($strUserID);?></td>
    </tr>
    <tr>
      <td><b><?=$progText249;?>: &nbsp;</b></td>
      <td><?echo writeNA($strFirstName);?></td>
    </tr>
    <tr>
      <td><b><?=$progText250;?>: &nbsp;</b></td>
      <td><?echo writeNA($strMiddleInit);?></td>
    </tr>
    <tr>
      <td><b><?=$progText251;?>: &nbsp;</b></td>
      <td><?echo writeNA($strLastName);?></td>
    </tr>
    <tr>
      <td><b><?=$progText256;?>: &nbsp;</b></td>
      <td><?echo writeNA($strEmail);?></td>
    </tr>
    <tr>
      <td><b><?=$progText34;?>: &nbsp;</b></td>
      <td><?echo writeNA($strLocation);?></td>
    </tr>

  </table><p>

<?
      echo "<b>".$progText416.":</b> &nbsp;";
      If ($_SESSION['sessionSecurity'] < 2) {
          echo "(<A class='action' HREF='admin_comments.php?commentType=u&subjectID=$viewID'>".$progText417."?</A>)\n"; # add new
      }

      $strSQL3 = "SELECT c.*, s.*, o.categoryName
        FROM comments as c
        LEFT JOIN commentCategories as o ON o.categoryID=c.categoryID
        LEFT JOIN tblSecurity as s ON c.assignedUserID=s.id
        WHERE c.subjectID=$viewID AND c.subjectType='u' AND c.accountID=" . $_SESSION['accountID'] . "
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

  writeFooter();
?>
