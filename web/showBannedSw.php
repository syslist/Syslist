<?
  $genericNav = TRUE;
  Include("Includes/global.inc.php");
  # self-hosted version does not have checkPermissions built-in; only hosted.
  # checkPermissions(2, 1800);
  writeHeader($progText1270);

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

  If (getOrPost('btnQuickFind')) {
      $strQuickFind = cleanFormInput(getOrPost('txtQuickFind'));
      $sqlCondition = "AND t.visName LIKE '%$strQuickFind%'";

      $aryQS = array("btnQuickFind=1", "txtQuickFind=$strQuickFind");
  }

  $rs = dbquery( $strSQL = "SELECT l.numLicenses, l.licenseType, l.pricePerLicense,
   t.bannedReason, t.softwareTraitID, t.visName, t.visMaker, t.visVersion
   FROM software_traits as t
   LEFT JOIN software_traits_licenses as tl ON t.softwareTraitID=tl.softwareTraitID
   LEFT JOIN software_licenses as l ON tl.licenseID=l.licenseID
   WHERE t.accountID=" . $_SESSION['accountID'] . " AND t.hidden='0' AND isBanned='1'
     $sqlCondition $sqlOrder");
  $i = 0;
  while ($row = mysql_fetch_assoc($rs)) { $results[$i++] = $row; }
  $totalresults = $i;
  
  // note: purposely leaving paging functionality out. most pages with quickfind
  //  and many potential records should include paging. See detailed_p.php for
  //  an example
  if (($totalresults >= $rowLimit) OR getOrPost('btnQuickFind')) {
?>
      <FORM METHOD='get' ACTION='showBannedSw.php'>
      <input type='hidden' name='btnQuickFind' value='1'>
      <table border='0' cellpadding='4' cellspacing='0'>
      <tr>
        <td colspan='4'><?=$progText81;?> (<?=$progText173;?>):&nbsp;
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
    <td valign='bottom' width='330'><b><nobr><?=$progText188;?></nobr></b>&nbsp;</nobr></td>
  </tr>
<?
  for ($i = 0; $i < $totalresults; $i++) {
      $row = $results[$i];
      echo "<tr class='".alternateRowColor()."'>";
      echo "<td valign='top'><a href='search.php?btnSubmitAll=1&cboSoftwareType=".$row["softwareTraitID"]."'>".$row["visName"]."</a> &nbsp;</td>";
      echo "<td valign='top'>&nbsp;".writeNA($row["visVersion"])." &nbsp;</td>";
      echo "<td valign='top'>".writeNA($row["visMaker"])." &nbsp;</td>";
      echo "<td valign='top' width='330'>".str_replace("\n", "<br>", $row["bannedReason"])." &nbsp;</td>";
      echo "</tr>\n";
  }
  echo "</table>";

  if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
      echo "</FORM>\n";
  }

  writeFooter();
?>
