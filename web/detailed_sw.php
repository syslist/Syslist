<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  $strSQLlocation = "SELECT * FROM locations WHERE locationID=" . $_SESSION['locationStatus'] . " AND accountID=" . $_SESSION['accountID'] . "";
  $strLocationName = fetchLocationName($strSQLlocation);

  // create a SQL snippet for later use, based on user (sort order) input
  $asort = strip_tags(getOrPost('asort'));
  $dsort = strip_tags(getOrPost('dsort'));
  if ($asort) {
      $sqlOrder = "ORDER BY $asort ASC";
  } elseif ($dsort) {
      $sqlOrder = "ORDER BY $dsort DESC";
  } else {
      $sqlOrder = "ORDER BY t.visName ASC"; # default sort
  }

  // If user used 'quick find', process extra sql and prepare an array of querystring
  // paramaters that will be used to preserve the user's search, in the event that
  // they click sort links after searching.
  If (getOrPost('btnQuickFind')) {
      $strQuickFind = cleanFormInput(getOrPost('txtQuickFind'));
      $sqlCondition2 = "AND t.visName LIKE '%$strQuickFind%'";

      $aryQS = array("btnQuickFind=1", "txtQuickFind=$strQuickFind");
  }

  writeHeader(($progText322." ".$strLocationName), "", TRUE);
?>
  <table border='0' cellpadding='0' cellspacing='0'>
    <tr>
      <td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><A class='action' HREF='spareSoftware.php'><?=$progText320;?></A></td>
      <!-- showBannedSw in self-hosted product only -->
      <td><nobr>&nbsp; &nbsp; &nbsp; </nobr></td>
      <td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><A class='action' HREF='showBannedSw.php'><?=$progText324;?></A></td>
      <!-- end self-hosted-only code -->
      <td><nobr>&nbsp; &nbsp; &nbsp; </nobr></td>
      <td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><A class='action' HREF='findSwByDate.php'><?=$progText323;?></A></td>
    </tr>
    <tr><td colspan='3'>&nbsp;</td></tr>
  </table>
<?
  // If user has specified a location, prepare sql condition to return software from only that location
  // (this applies only to the 'copy count', not the license count, which is not location specific.)
  if (is_numeric($_SESSION['locationStatus'])) {
      $sqlCondition = "s.locationID=" . $_SESSION['locationStatus'] . " AND ";
  } else {
      $sqlCondition = "s.locationID IS NOT NULL AND ";
  }

  // Initialize array containing all software types associated with this account
  $strSQLx = "SELECT softwareTraitID FROM software_traits WHERE accountID=" . $_SESSION['accountID'] . " AND hidden='0'";
  $resultx = dbquery($strSQLx);
  $aryTotalCopies = array();
  while ($row = mysql_fetch_array($resultx)) {
      $aryTotalCopies[$row["softwareTraitID"]] = 0;
  }
  mysql_free_result($resultx);

  // Count up the spare software instances (spares have a locationID) associated with each software type
  $strSQLy = "SELECT count(s.softwareID) as sCount, t.softwareTraitID FROM software as s, software_traits as t
    WHERE $sqlCondition t.softwareTraitID=s.softwareTraitID AND t.accountID=" . $_SESSION['accountID'] . " AND s.hidden='0'
    GROUP BY t.softwareTraitID";
  $resulty = dbquery($strSQLy);
  while ($row = mysql_fetch_array($resulty)) {
      $aryTotalCopies[$row["softwareTraitID"]] += $row["sCount"];
  }
  mysql_free_result($resulty);

  // Add the non-spare software instances (locationID is null) based on locationID from associated hardware
  $strSQLz = "SELECT h.locationID, s.softwareTraitID FROM software as s, hardware as h WHERE
    s.locationID IS NULL AND s.accountID=" . $_SESSION['accountID'] . " AND h.hardwareID=s.hardwareID AND s.hidden='0'";
  $resultz = dbquery($strSQLz);
  while ($row = mysql_fetch_array($resultz)) {
      if (($row["locationID"] == $_SESSION['locationStatus']) OR (!is_numeric($_SESSION['locationStatus'])))  {
          $aryTotalCopies[$row["softwareTraitID"]]++;
      }
  }
  mysql_free_result($resultz);

  /*
     To prepare for when we permit 2+ licenses to be associated with a software trait, we must
     add functionality to combine licenses of same type and display them on one line, and
     licenses of different type and display them on another line, then display correct
     remaining licenses for both.
  */

  $strSQL = "SELECT l.numLicenses, l.licenseType, l.pricePerLicense,
     t.softwareTraitID, t.visName, t.visMaker, t.visVersion
     FROM software_traits as t
     LEFT JOIN software_traits_licenses as tl ON t.softwareTraitID=tl.softwareTraitID
     LEFT JOIN software_licenses as l ON tl.licenseID=l.licenseID
     WHERE t.accountID=" . $_SESSION['accountID'] . " AND t.hidden='0' $sqlCondition2 $sqlOrder";
  $strSQL = determinePageNumber($strSQL);
  $result = dbquery($strSQL);
  $records = mysql_num_rows($result);

  // If there are software traits in the database (or user performed a search, which
  // implies that traits were found earlier in the session), show table and search form.
  if (($records > 0) OR getOrPost('btnQuickFind')) {

      // If results will span more than one page, give user option to quick find as well
      // If user searched, give them quick find (again) in case the search did not turn
      // up what they were looking for.
      if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
?>
          <FORM METHOD="get" ACTION="detailed_sw.php">
          <input type='hidden' name='btnQuickFind' value='1'>
          <table border='0' cellpadding='4' cellspacing='0'>
          <tr>
            <td colspan='5'><?=$progText81;?> (<?=$progText173;?>):&nbsp;
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
        <td valign='bottom'><b><nobr><?=$progText173;?> </nobr><? sortColumnLinks("t.visName", $aryQS); ?></b>&nbsp; </td>
        <td valign='bottom'><b><nobr><?=$progText160;?> </nobr><? sortColumnLinks("t.visVersion", $aryQS); ?></b>&nbsp; </td>
        <td valign='bottom'><b><nobr><?=$progText120;?> </nobr><? sortColumnLinks("t.visMaker", $aryQS); ?></b>&nbsp; </td>
        <td valign='bottom'><b><nobr><?=$progText321;?></nobr>&nbsp; </td>
        <td valign='bottom'><b><nobr>&nbsp;<?=$progText161;?>&nbsp;</nobr><br><nobr>&nbsp;<?=$progText162;?>&nbsp;</nobr></b></td>
        <td valign='bottom'><b><?=$progText1231;?>&nbsp;</td>
      </tr>
<?

      While ($row = mysql_fetch_array($result)) {
         If ($tempID == $row["softwareTraitID"]) {
             $moreLicenses = TRUE;
         } Else {
             $moreLicenses = FALSE;
             $tempID = $row["softwareTraitID"];
         }
         If ($row["pricePerLicense"]) {
             $strPricePerLicense = number_format($row["pricePerLicense"], 2, ".", ",");
         } else {
             $strPricePerLicense = "";
         }

         If ($moreLicenses) {
             // placeholder: future code goes here.
             # echo "<td colspan='5'>&nbsp;".$remainingLicenses." &nbsp;</td>";

         } Else {
             echo "<tr class='".alternateRowColor()."'>";
             echo "<td><a href='search.php?btnSubmitAll=1&txtSoftwareType=".$row["visName"]."'>".$row["visName"]."</a> &nbsp;</td>";
             echo "<td>&nbsp;".writeNA($row["visVersion"])." &nbsp;</td>";
             echo "<td>".writeNA($row["visMaker"])." &nbsp;</td>";
             echo "<td>&nbsp;".$aryTotalCopies[$row["softwareTraitID"]]." &nbsp;</td>";

             // Calculate remaining licenses based on peruser/persystem distinction
             If ($row["licenseType"] == "peruser") {
                 $strSQL2 = "SELECT t.id FROM software as s, tblSecurity as t, hardware as h WHERE
                   s.softwareTraitID=".$row["softwareTraitID"]." AND s.hardwareID=h.hardwareID AND
                   h.userID=t.id AND s.sparePart='0' AND h.sparePart='0' AND t.accountID=" . $_SESSION['accountID'] . "
                   AND t.hidden='0' AND (s.hidden='0' OR s.hidden='2')
                   GROUP BY t.id";
                 $result2            = dbquery($strSQL2);
                 $usedLicenses       = mysql_num_rows($result2);
                 $remainingLicenses  = $row["numLicenses"] - $usedLicenses;
                 mysql_free_result($result2);

             } ElseIf ($row["licenseType"] == "persystem") {
                 $strSQL2 = "SELECT h.hardwareID FROM software as s, hardware as h WHERE
                   s.softwareTraitID=".$row["softwareTraitID"]." AND s.hardwareID=h.hardwareID AND
                   s.sparePart='0' AND h.accountID=" . $_SESSION['accountID'] . " AND (s.hidden='0' OR s.hidden='2')
                   GROUP BY h.hardwareID";
                 $result2            = dbquery($strSQL2);
                 $usedLicenses       = mysql_num_rows($result2);
                 $remainingLicenses  = $row["numLicenses"] - $usedLicenses;
                 mysql_free_result($result2);
             } Else {
                 $remainingLicenses  = "N/A";
             }
             echo "<td>&nbsp;".$remainingLicenses." &nbsp;</td>";
             echo "<td>&nbsp;".writeNA($strPricePerLicense)."&nbsp;</td>";
             echo "</tr>\n";
         }
      }
      echo "</table>";
      createPaging();
      if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
          echo "\n</FORM>";
      }
  }

  writeFooter();
?>

