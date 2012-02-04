<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  $notify = getOrPost('notify');
  notifyUser($notify);

  $sqlLocation1 = "";
  $sqlLocation2 = "";
  If (is_numeric($_SESSION['locationStatus'])) {
      $sqlLocation1 = "(s.userlocationID=" . $_SESSION['locationStatus'] . " OR (s.userLocationID IS NULL AND s.securityLevel < 1)) AND";
      $sqlLocation2 = "l.locationName DESC, ";
  }

  // create a SQL snippet for later use, based on user (sort order) input
  $asort = strip_tags(getOrPost('asort'));
  $dsort = strip_tags(getOrPost('dsort'));
  if ($asort) {
      $sqlOrder = "$asort ASC";
  } elseif ($dsort) {
      $sqlOrder = "$dsort DESC";
  } else {
      $sqlOrder = "s.lastName ASC"; # default sort
  }

  If (getOrPost('btnQuickFind')) {
      $strQuickFind = cleanFormInput(getOrPost('txtQuickFind'));
      $sqlCondition = "AND s.lastName LIKE '%$strQuickFind%'";

      $aryQS = array("btnQuickFind=1", "txtQuickFind=$strQuickFind");
  }

  $strSQL = "SELECT s.*, l.locationName
    FROM tblSecurity as s
    LEFT JOIN locations as l ON l.locationID=s.userLocationID
    WHERE $sqlLocation1 s.accountID=" . $_SESSION['accountID'] . " AND s.hidden='0' $sqlCondition
    ORDER BY $sqlLocation2 $sqlOrder";
  $strSQL = determinePageNumber($strSQL);
  $result = dbquery($strSQL);
  $records = mysql_num_rows($result);

  writeHeader($progText507, "", TRUE);
  declareError(TRUE);

  if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
      echo "<FORM METHOD='get' ACTION='viewUsers.php'>\n";
  }

  If ($_SESSION['sessionSecurity'] < 2) {
      echo "<table border='0' cellpadding='0' cellspacing='0'>
              <tr>
                <td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><A class='action' HREF='createUser.php'>".$progText508."</A></td>
                <td><nobr>&nbsp; &nbsp; &nbsp; </nobr></td>
                <td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><A class='action' HREF='emailUsers.php'>".$progText807."</A></td>
                <td><nobr>&nbsp; &nbsp; &nbsp; </nobr></td>
                <td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><A class='action' HREF='importMenu.php'>".$progText1050."</A></td>
              </tr>
              <tr><td colspan='3'>&nbsp;</td></tr>
            </table>\n\n";
  }

  if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
?>
      <input type='hidden' name='btnQuickFind' value='1'>
      <table border='0' cellpadding='4' cellspacing='0'>
        <tr>
          <td colspan='6'><?=$progText81;?> (<?=$progText251;?>):&nbsp;
            <input type='text' name='txtQuickFind' value='<?=$strQuickFind;?>'>
            &nbsp;<INPUT TYPE="submit" NAME="qf" VALUE="<?=$progText21;?>">
          </td>
        </tr>
<?
  } else {
      echo "<table border='0' cellpadding='4' cellspacing='0'>\n";
  }
?>
        <tr class='title'>
           <td><b><nobr><?=$progText159;?> </nobr><? sortColumnLinks("s.lastName", $aryQS);?></b> &nbsp; </td>
           <td><b><nobr><?=$progText255;?> </nobr><? sortColumnLinks("s.id", $aryQS);?></b> &nbsp; </td>
           <td><b><nobr><?=$progText409;?></nobr></b> &nbsp; </td>
           <td><b><nobr><?=$progText509;?> </nobr><? sortColumnLinks("s.lastLogin", $aryQS);?></b> &nbsp; </td>
           <td><b><nobr><?=$progText34;?></nobr></b> &nbsp; </td>
           <td><b><nobr><?=$progText79;?></nobr></b> </td>
        </tr>
<?
  while ($row = mysql_fetch_array($result)) {
       $strUserID      = $row["userID"];
       $strFirstName   = $row["firstName"];
       $strMiddleInit  = $row["middleInit"];
       $strLastName    = $row["lastName"];
       $intUserID      = $row["id"];
       $intLevel       = $row["securityLevel"];
       $strLastLogin   = $row["lastLogin"];
       $strLocation    = $row["locationName"];

       If (!$strLastLogin) {
           $strLastLogin = writeNA($strLastLogin);
       } Else {
           $strLastLogin = displayDateTime($strLastLogin)." &nbsp;";
       }

       echo "<tr class='".alternateRowColor()."'>\n";
       echo "<td>".buildName($strFirstName, $strMiddleInit, $strLastName, 0)." &nbsp;</td>\n";
       echo "<td>".writeNA($strUserID)." &nbsp;</td>\n";
       echo "<td>".writeSecurityLevel($intLevel)." &nbsp;</td>\n";
       echo "<td>".$strLastLogin." &nbsp;</td>\n";
       echo "<td>".writeNA($strLocation)." &nbsp;</td>\n";
       echo "<td>";

       // If admin has full rights, or admin has limited rights (and user to be edited
       // has no access rights), or admin == user to be edited, then show edit link.
       If ((($intLevel == 3) AND ($_SESSION['sessionSecurity'] < 2)) OR ($_SESSION['sessionSecurity'] < 1) OR ($intUserID == $_SESSION['userID'])) {
           echo "<a class='action' href='editUser.php?editID=$intUserID'>".$progText75."</a>&nbsp; \n";
       } Else {
           echo "<a class='action' href='viewUser.php?viewID=$intUserID'>".$progText510."</a>&nbsp; \n";
       }

       If ($_SESSION['sessionSecurity'] < 2) {
           echo "<a class='action' href='admin_comments.php?commentType=u&subjectID=$intUserID'>".$progText31."</a>&nbsp; \n"; # add comment
       }

       // If admin has sessionSecurity = 0 and user to be edited is not admin himself,
       // permit user to be deleted
       If (($_SESSION['userID'] != $intUserID) AND ($_SESSION['sessionSecurity'] < 1)) {
            echo "<a class='action' href='deleteUser.php?editID=$intUserID' onClick=\"return warn_on_submit('".$progTextBlock27."');\">".$progText80."</a></li>\n";
       }

       echo "</td></tr>\n";
  }

  echo "</table>";
  createPaging("notify");

  writeFooter();
?>
