<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  $notify = getOrPost('notify');
  notifyUser($notify);

  $strSQLlocation = "SELECT * FROM locations WHERE locationID=" . $_SESSION['locationStatus'] . " AND accountID=" . $_SESSION['accountID'] . "";
  $strLocationName = fetchLocationName($strSQLlocation);

  writeHeader(($progText449." ".$strLocationName), "", TRUE);
  declareError(TRUE);

  If (is_numeric($_SESSION['locationStatus'])) {
      $sqlLocCondition  = "s.locationID='" . $_SESSION['locationStatus']. "' AND";
  }

  $strSQL = "SELECT s.softwareID, s.serial, s.roomName, t.visName, t.visMaker, t.visVersion, v.vendorName
    FROM (software as s, software_traits as t)
    LEFT JOIN vendors as v ON s.vendorID = v.vendorID
    WHERE $sqlLocCondition s.sparePart='1' AND t.softwareTraitID=s.softwareTraitID AND
    t.accountID=" . $_SESSION['accountID'] . " ORDER BY t.visName ASC";
  $strSQL = determinePageNumber($strSQL);
  $result = dbquery($strSQL);

  echo $progTextBlock26;
?>

 <p><a class='action' href='admin_software.php?cboUser=spare&cboSystem=0'><?=$progText450;?>?</a>

  <p><table border='0' cellpadding='4' cellspacing='0'>
  <TR class='title'>
     <TD><b><?=$progText173;?></b> &nbsp;</TD>
     <TD><b><?=$progText120;?></b> &nbsp;</TD>
     <TD><b><?=$progText1220;?></b> &nbsp;</TD>
     <TD><b><?=$progText160;?></b> &nbsp;</TD>
     <TD><b><?=$progText44;?></b> &nbsp;</TD>
     <TD><b><?=$progText35;?></b> &nbsp;</TD>
     <TD><b><?=$progText79;?></b></TD></TR> <?

  $iCount = 0;
  $rowStyle = "";
  while ($row = mysql_fetch_array($result)) {

    $softwareID    = $row["softwareID"];
    $strSerial     = $row["serial"];
    $strRoomName   = $row["roomName"];
    $strName       = $row['visName'];
    $strMaker      = $row['visMaker'];
    $strVersion    = $row['visVersion'];
    $strVendorName = $row['vendorName'];

?>
    <TR class='<? echo alternateRowColor(); ?>'>
      <TD class='smaller' valign='top'><? echo $strName; ?></TD>
      <TD class='smaller' valign='top'><? echo writeNA($strMaker); ?> &nbsp;</TD>
      <TD class='smaller' valign='top'><? echo writeNA($strVendorName); ?> &nbsp;</TD>
      <TD class='smaller' valign='top'><? echo writeNA($strVersion); ?> &nbsp;</TD>
      <TD class='smaller' valign='top'><? echo writeNA($strSerial); ?> &nbsp;</TD>
      <TD class='smaller' valign='top'><? echo writeNA($strRoomName); ?> &nbsp;</TD>
      <TD class='smaller' valign='top'>
    <?
      If ($_SESSION['sessionSecurity'] > 1) {
          echo "N/A";
      } Else {
          echo "<A class='action' HREF='admin_software.php?softwareID=$softwareID&cboUser=spare'>".$progText75."</A>&nbsp; \n";

          If ($_SESSION['sessionSecurity'] < 1) {
              echo "<A class='action' HREF='delete.php?returnTo=spare&softwareID=$softwareID' onClick=\"return warn_on_submit('".$progText438."');\">".$progText80."</A>\n";
          }
          echo "</TD>";
      }
  }
  echo "</table>";

  createPaging();
  writeFooter();
?>
