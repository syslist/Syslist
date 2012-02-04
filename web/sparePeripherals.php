<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  $notify = getOrPost('notify');
  notifyUser($notify);

  $strSQLlocation = "SELECT * FROM locations WHERE locationID=" . $_SESSION['locationStatus'] . " AND accountID=" . $_SESSION['accountID'] . "";
  $strLocationName = fetchLocationName($strSQLlocation);

  writeHeader(($progText430." ".$strLocationName), "", TRUE);
  declareError(TRUE);

  If (is_numeric($_SESSION['locationStatus'])) {
      $sqlLocCondition  = "p.locationID='" . $_SESSION['locationStatus'] . "' AND";
  }

  $hardwareID = cleanFormInput(getOrPost('hardwareID'));
  $systemStatus = cleanFormInput(getOrPost('systemStatus'));
?>

<table width='100%' border='0' cellpadding='0' cellspacing='0'>
<tr>
  <td class='smaller'>
     <b>Status:</b>&nbsp;
             <?writeActiveLink("sparePeripherals.php?systemStatus=w", $progText413, $systemStatus, "w");?> &nbsp;|&nbsp;
             <?writeActiveLink("sparePeripherals.php?systemStatus=i", $progText415, $systemStatus, "i");?> &nbsp;|&nbsp;
             <?writeActiveLink("sparePeripherals.php?systemStatus=n", $progText414, $systemStatus, "n");?> &nbsp;|&nbsp;
             <?writeActiveLink("sparePeripherals.php", $progText431, $systemStatus, "");?>
  </td>
</tr>
</table><p>

<?
  echo $progTextBlock25;

  echo "\n\n<p><table border='0' cellpadding='0' cellspacing='0'>\n
          <tr>\n
            <td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><A class='action' href='historyView.php?hardwareID=$hardwareID' target='_blank' onClick='return popupWin(this, \"history\", 500, 600)'>".$progText417A."</A></td>\n
            <td><nobr>&nbsp; &nbsp; &nbsp; </nobr></td>\n";

      If ($_SESSION['sessionSecurity'] < 2) {
            echo "<td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><a class='action' href='admin_peripherals.php?cboUser=spare&cboSystem=0'>".$progText432."</a></td>\n";
      } Else {
            echo "<td>&nbsp;</td>\n";
      }

  echo "  </tr>\n
          <tr><td colspan='3'>&nbsp;</td></tr>\n
        </table>\n\n";

  If ($systemStatus) {
      $sqlCondition = "p.peripheralStatus='$systemStatus' AND";
  }

  $strSQL = "SELECT * FROM (peripherals as p, peripheral_traits as pt)
    LEFT JOIN vendors as v ON p.vendorID = v.vendorID WHERE $sqlCondition $sqlLocCondition p.sparePart='1'
    AND pt.peripheralTraitID=p.peripheralTraitID AND pt.accountID=" . $_SESSION['accountID'] . " ORDER BY visDescription ASC";
  $strSQL = determinePageNumber($strSQL);
  $result = dbquery($strSQL);
?>
  <table border='0' cellspacing='0' cellpadding='4'>
  <TR class='title'>
    <TD valign='top'><b><?=$progText122;?></b> &nbsp;</TD>
    <TD valign='top'><b><?=$progText120;?></b> &nbsp;</TD>
    <TD valign='top'><b><?=$progText1220?></b> &nbsp;</TD>
    <TD valign='top'><b><?=$progText315;?></b> &nbsp;</TD>
    <TD valign='top'><b>Serial Number</b> &nbsp;</TD>
    <TD valign='top'><b><?=$progText35;?></b> &nbsp;</TD>
    <TD valign='top'><b><?=$progText1230;?></b> &nbsp;</TD>
    <!--<TD valign='top'><b><?=$progText421;?></b> &nbsp;</TD>-->
    <!--<TD valign='top'><b><?=$progText424;?></b> &nbsp;</TD>-->
    <TD valign='top'><b><?=$progText222;?></b> &nbsp;</TD>
    <TD valign='top'><b><?=$progText79;?></b></TD>
<?
  $iCount = 0;
  $rowStyle = "";
  While ($row = mysql_fetch_array($result)) {

    $peripheralID       = $row["peripheralID"];
    $serial             = $row["serial"];
    $peripheralTraitID  = $row["peripheralTraitID"];
    $manufacturer       = $row['visManufacturer'];
    $typeClass          = $row['visTypeClass'];
    $description        = $row['visDescription'];
    $periphStatus       = $row['peripheralStatus'];
    $strRoomName        = $row["roomName"];
    $strVendorName      = $row["vendorName"];
    $strMacAddress      = $row["macAddress"];
    $purchaseDate       = $row["purchaseDate"];
    $purchasePrice      = $row["purchasePrice"];

    // Display all the peripherals belonging to this server
?>
    <TR class='<? echo alternateRowColor(); ?>'>
      <TD valign='top' class='smaller'><? echo $description; ?> &nbsp;</TD>
      <TD valign='top' class='smaller'><? echo writeNA($manufacturer); ?> &nbsp;</TD>
      <TD valign='top' class='smaller'><? echo writeNA($strVendorName) ?> &nbsp;</TD>
      <TD valign='top' class='smaller'><? echo writePeripheralClass($typeClass); ?> &nbsp;</TD>
      <TD valign='top' class='smaller'><? echo writeNA($serial); ?> &nbsp;</TD>
      <TD valign='top' class='smaller'><? echo writeNA($strRoomName); ?> &nbsp;</TD>
      <TD valign='top' class='smaller'><? echo writeNA($strMacAddress); ?> &nbsp;</TD>
      <!--<TD valign='top' class='smaller'><? echo writeNA(displayDate($purchaseDate)); ?> &nbsp;</TD>-->
      <!--<TD valign='top' class='smaller'><? echo writeNA($purchasePrice); ?> &nbsp;</TD>-->
      <TD valign='top' class='smaller'><? echo writeStatus($periphStatus);

      If ($_SESSION['sessionSecurity'] > 1) {
          echo "&nbsp;";
      } Else {
    ?>
          <nobr>(<a style='text-decoration: none' href='sparePeripherals.php?rowOffset=<?=$rowOffset?>&systemStatus=<?=$systemStatus?>&peripheralID=<?=$peripheralID?>&setStatus=w'><?=$progText434;?></a>
          <a style='text-decoration: none' href='sparePeripherals.php?rowOffset=<?=$rowOffset?>&systemStatus=<?=$systemStatus?>&peripheralID=<?=$peripheralID?>&setStatus=n'><?=$progText435;?></a>
          <a style='text-decoration: none' href='sparePeripherals.php?rowOffset=<?=$rowOffset?>&systemStatus=<?=$systemStatus?>&peripheralID=<?=$peripheralID?>&setStatus=i'><?=$progText436;?></a>) &nbsp;</nobr>
    <?
      }
    ?>
      </TD>
      <TD valign='top' class='smaller'>
    <?
      If ($_SESSION['sessionSecurity'] > 1) {
          echo $progText437;
      } Else {
          echo "<A class='action' HREF='admin_peripherals.php?peripheralID=$peripheralID&cboUser=spare'>".$progText75."</A>&nbsp; \n";

          If ($_SESSION['sessionSecurity'] < 1) {
              echo "<A class='action' HREF='delete.php?returnTo=spare&peripheralID=$peripheralID' onClick=\"return warn_on_submit('".$progText438."');\">".$progText80."</A>\n";
          }
      }
      echo "</TD>";
  }
  echo "</table>";
  createPaging();

  writeFooter();
?>
