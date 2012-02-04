<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  $notify = getOrPost('notify');
  notifyUser($notify);

  // with this in place we can strip out spare qstrings?
  $strSQL2  = "SELECT sparePart FROM hardware WHERE hardwareID=$hardwareID";
  $result2  = dbquery($strSQL2);
  $row2     = mysql_fetch_row($result2);
  $spare    = $row2[0];

   if ($hardwareID AND ($spare === "0")) {
       $strSQL = "SELECT * FROM hardware as h, hardware_types as t, tblSecurity as s WHERE
         s.id=h.userID AND h.hardwareTypeID=t.hardwareTypeID AND h.hardwareID=$hardwareID
         AND t.accountID=" . $_SESSION['accountID'] . "";
   } elseif ($hardwareID AND ($spare > 0)) {
       $strSQL = "SELECT * FROM hardware as h, hardware_types as t WHERE
         h.hardwareTypeID=t.hardwareTypeID AND h.hardwareID=$hardwareID AND
         t.accountID=" . $_SESSION['accountID'] . "";
       $result = dbquery($strSQL);
   }

   $result = dbquery($strSQL);

   While ($row = mysql_fetch_array($result)) {
      $strDescription   = $row["visDescription"];
      $strManufacturer  = $row["visManufacturer"];
      $hardwareTypeID   = $row["hardwareTypeID"];
      $serial           = $row["serial"];
      $spare            = $row["sparePart"];
      $strHwStatus      = $row["hardwareStatus"];
      $strIP            = $row["ipAddress"];
      $assetTag         = $row["assetTag"];
      $strHostname      = $row["hostname"];
      $locationID       = $row["locationID"];
      $roomName         = $row["roomName"];
      $vendorID         = $row["vendorID"];
      $strOther1        = $row["other1"];
      $strPurchasePrice = $row["purchasePrice"];
      If ($strPurchasePrice) {
          $strPurchasePrice = number_format($strPurchasePrice, 2, ".", ",");
      }
      $strPurchaseDate  = $row["purchaseDate"];
      $strWarrantyDate  = $row["warrantyEndDate"];
      $nicMac1          = $row["nicMac1"];
      $nicMac2          = $row["nicMac2"];

      $modifiedDate       = $row["lastManualUpdate"];
      $modifyingUser      = $row["lastManualUpdateBy"];
      $modifiedAgentDate  = $row["lastAgentUpdate"];

      If ($spare === "0") {
          $intUserID        = $row["id"];
          $strFirstName     = $row["firstName"];
          $strMiddleInit    = $row["middleInit"];
          $strLastName      = $row["lastName"];
          $strEmail         = $row["email"];
      }

      If ($locationID) {
          $strSQLlocation = "SELECT * FROM locations as l WHERE l.locationID=$locationID";
          $locationResult = dbquery($strSQLlocation);
          $thisthing = mysql_fetch_array($locationResult);
          $locationName = $thisthing['locationName'];
      }

      If ($vendorID) {
          $strSQLVendor = "Select * FROM vendors as v WHERE v.vendorID=$vendorID";
          $vendorResult = dbquery($strSQLVendor);
          $thisthing = mysql_fetch_array($vendorResult);
          $vendorName = $thisthing['vendorName'];
      }

      // Potential picture URL is in aryRow[0]. Null case handled in writeHeader by default.
      $strSQL = "SELECT picURL FROM hardware WHERE hardwareID=$hardwareID AND accountID=" . $_SESSION['accountID'] . "";
      $dbResult = dbquery($strSQL);
      $aryRow = mysql_fetch_row($dbResult);
      $picURL = $aryRow[0];

      writeHeader($progText408, "", FALSE, "&nbsp;", $picURL, $hardwareID, "hw");
      declareError(TRUE);
    ?>
      <p>
      <table border='0' cellpadding='0' cellspacing='0'>
       <tr><td valign='top' align='left'>
        <table border='0' cellpadding='4' cellspacing='0'>
    <?
      If ($spare === "1") {
           $strUserName = $progText377;
           $intUserID = "toSpare";
      } ElseIf ($spare === "2") {
           $strUserName = $progText472;
           $intUserID = "toIndependent";
      } ElseIf (($spare === "3") AND ($adminDefinedCategory)) {
           $strUserName = ucfirst($adminDefinedCategory);
           $intUserID = "toAdminDefined";
      } Else {
           $strUserName = buildName($strFirstName, $strMiddleInit, $strLastName, 1);
           If ($strEmail) {
               $strUserName = "<a href='mailto:$strEmail'>$strUserName</a>";
           }
      }
    ?>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText32;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo $strUserName; ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText409;?>:</b> &nbsp; </nobr></TD>
      <TD><a href="specView.php?specType=<?=$hardwareTypeID;?>" target="_blank" onClick="return popupWin(this, 'spec', 400, 600)"><? echo writePrettySystemName($strDescription, $strManufacturer); ?></a></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText375;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeNA($serial); ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText37;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeNA($strHostname); ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText157;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeNA($strIP); ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText420;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeNA($assetTag); ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText222;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeStatus($strHwStatus); ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText34;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeNA($locationName); ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText35;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeNA($roomName); ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText1226;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeNA($vendorName); ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText424;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeNA($strPurchasePrice); ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText421;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeNA(displayDate($strPurchaseDate)); ?></TD></TR>
   <TR class='row1'>
      <TD valign='top'><nobr><b><?=$progText422;?>:</b> &nbsp; </nobr></TD>
      <TD><? echo writeNA(displayDate($strWarrantyDate)); ?></TD></TR>

<? If ($extraSystemField) { ?>
       <TR class='row1'>
          <TD valign='top'><nobr><b><?=$extraSystemField;?>:</b> &nbsp; </nobr></TD>
          <TD><? echo writeNA($strOther1); ?></TD></TR>
<? } ?>

   </table></td>
   <td width='34'>&nbsp;</td>
   <td valign='top' align='left'>
    <?
      echo "<ul>\n";
      echo "<li style='line-height: 150%; margin-left: -8;'><A class='action' href='historyView.php?hardwareID=$hardwareID' target='_blank' onClick='return popupWin(this, \"history\", 500, 600)'>$progText417A</A>\n";
      If ($_SESSION['sessionSecurity'] < 2) {

          echo "<li style='line-height: 150%; margin-left: -8;'><a class='action' HREF='admin_hardware.php?id=$hardwareID&spare=$spare'>".$progText410."</A>\n"; # edit system

          If ($_SESSION['sessionSecurity'] < 1) {
              echo "<li style='line-height: 150%; margin-left: -8;'><A class='action' HREF='delete.php?hardwareID=$hardwareID' onClick=\"return warn_on_submit('".$progTextBlock24."');\">".$progText411."</A>\n"; # delete system
          }

          // Upload Picture line
          echo "<li style='line-height: 150%; margin-left: -8;'><a class='action' HREF='admin_pic.php?target=hw&id=$hardwareID'>".$progText415A."</A>\n"; # upload picture
        ?>

        <li style='line-height: 150%; margin-left: -8;'><?=$progText412;?>:
             <br><a style='FONT-SIZE: 11px' class='action' href='showfull.php?hardwareID=<?=$hardwareID?>&setStatus=w'><?=$progText413;?></a>&nbsp; |
             &nbsp;<a style='FONT-SIZE: 11px' class='action' href='showfull.php?hardwareID=<?=$hardwareID?>&setStatus=n'><?=$progText414;?></a>&nbsp; |
             &nbsp;<a style='FONT-SIZE: 11px' class='action' href='showfull.php?hardwareID=<?=$hardwareID?>&setStatus=i'><?=$progText415;?></a>
    <?
      }
      echo "</ul>\n";
    ?>
      <table border='0' cellpadding='4' cellspacing='0'>
        <tr>
          <td>&nbsp;</td>
          <td><b><?=$progText48;?></b>: </td>
          <td><?echo writeNA(displayDate($modifiedDate));?></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td><b><?=$progText49;?></b>: </td>
          <td><?echo fetchUserNameFromID($modifyingUser);?></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td><b><?=$progText50;?></b>: </td>
          <td><?echo writeNA(displayDate($modifiedAgentDate));?></td>
        </tr>
    <?
      If ($nicMac1) {
    ?>
        <tr><td colspan='3'>&nbsp;</td></tr>
        <tr>
          <td>&nbsp;</td>
          <td colspan='2' align='left'><b><?=$progText423;?> 1</b>: &nbsp;<?=$nicMac1;?></td>
        </tr>
    <?
      }
      If ($nicMac2) {
    ?>
        <tr>
          <td>&nbsp;</td>
          <td colspan='2' align='left'><b><?=$progText423;?> 2</b>: &nbsp;<?=$nicMac2;?></td>
        </tr>
    <?
      }
    ?>
      </table>
   </td></tr></table>

    <?
      echo "<table border='0' cellpadding='0' cellspacing='0' height='32'><tr><td>&nbsp;</td></tr></table>";
      buildlist($hardwareID, $intUserID);
      echo "<table border='0' cellpadding='0' cellspacing='0' height='32'><tr><td>&nbsp;</td></tr></table>";

      echo "<font size='+1'>".$progText416.":</font> &nbsp;";
      If ($_SESSION['sessionSecurity'] < 2) {
          echo "(<A class='action' HREF='admin_comments.php?commentType=h&subjectID=$hardwareID'>".$progText417."?</A>)\n"; # add new
      }

      $strSQL3 = "SELECT c.*, s.*, o.categoryName
        FROM comments as c
        LEFT JOIN commentCategories as o ON o.categoryID=c.categoryID
        LEFT JOIN tblSecurity as s ON c.assignedUserID=s.id
        WHERE c.subjectID=$hardwareID AND c.subjectType='h' AND c.accountID=" . $_SESSION['accountID'] . "
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
