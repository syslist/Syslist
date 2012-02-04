<?
function buildlist($hardwareID, $uid) {
  global $rowStyle, $spare, $windowWidth;
  global $progText577, $progText417, $progText578, $progText579, $progText437;
  global $progText122, $progText121, $progText375, $progText222, $progText79, $progText315;
  global $progText434, $progText435, $progText436, $progText75, $progText80, $progText438;
  global $progText580, $progText173, $progText120, $progText160, $progText79;
  global $progText581, $progText582, $progText1226, $progText1230, $progText421, $progText424;
  global $progText575, $progText576, $progText159, $progText583, $progText584, $progText585, $progText586;

$strSQL = "SELECT * FROM logicaldisks WHERE hardwareID=$hardwareID AND accountID=" . $_SESSION['accountID'];
$result = dbquery($strSQL);
If (mysql_num_rows($result) >= 1) {
echo "<font size='+1'>".$progText575.":</font> &nbsp;";

?>
      <p><table border='0' cellspacing='0' cellpadding='4' width='100%'>
      <TR class='title'>
        <TD valign='bottom'><b><?= $progText159 ?></b></TD>
        <TD valign='bottom'><b><?= $progText583 ?></b></TD>
        <TD valign='bottom'><b><nobr><?= $progText584 ?></nobr></b></TD>
        <TD valign='bottom'><b><?= $progText585 ?></b></TD>
        <TD valign='bottom'><b><nobr><?= $progText586 ?></nobr></b></TD>
        </TR>
<? 
    While ($row = mysql_fetch_array($result)) {
      $diskName = $row['name'];
      $diskVolumeName = $row['volumeName'];
      $diskFileSystem = $row['fileSystem'];
      $diskSize = $row['size'];
      $diskFreeSpace = $row['freeSpace'];
?>
       <TR class='<? echo alternateRowColor(); ?>'>
          <TD class='smaller'><? echo writeNA($diskName); ?> &nbsp;</TD>
          <TD class='smaller'><? echo writeNA($diskVolumeName); ?> &nbsp;</TD>
          <TD class='smaller'><? echo writeNA($diskFileSystem); ?> &nbsp;</TD>
          <TD class='smaller'><? echo writeNA($diskSize); ?> &nbsp;</TD>
          <TD class='smaller'><? echo writeNA($diskFreeSpace); ?> &nbsp;</TD>          
       </TR>   
<?
    }
    echo "</table>";
    echo "<table border='0' cellpadding='0' cellspacing='0' height='32'><tr><td>&nbsp;</td></tr></table>";  
}
  $strSQL = "SELECT * FROM (peripherals as p, peripheral_traits as pt)
    LEFT JOIN vendors as v ON p.vendorID = v.vendorID
    WHERE p.hardwareID=$hardwareID AND pt.peripheralTraitID=p.peripheralTraitID AND
      pt.accountID=" . $_SESSION['accountID'] . " AND p.hidden='0'
    ORDER BY pt.visDescription ASC";
  $result = dbquery($strSQL);
  echo "<font size='+1'>".$progText577.":</font> &nbsp;";
  If ($_SESSION['sessionSecurity'] < 2) {
      echo "(<A class='action' HREF='admin_peripherals.php?cboSystem=$hardwareID&cboUser=$uid'>".$progText417."?</A>)\n";
  }

  If (mysql_num_rows($result) < 1) {
      echo "<br> &nbsp; *".$progText578."<br>";
  } Else {
?>

      <p><table border='0' cellspacing='0' cellpadding='4' width='100%'>
      <TR class='title'>
        <TD valign='bottom'><b><?=$progText122;?> </b></TD>
        <TD valign='bottom'><b><?=$progText315;?> </b></TD>
        <TD valign='bottom'><b><nobr><?=$progText375;?> </nobr></b></TD>
        <TD valign='bottom'><b><?=$progText1226;?> </b></TD>
        <!--<TD valign='bottom'><b><?=$progText1230;?> </b></TD>-->
        <TD valign='bottom'><b><?=str_replace(" ", "<br>", $progText421);?> </b></TD>
        <TD valign='bottom'><b><?=str_replace(" ", "<br>", $progText424);?> </b></TD>
        <TD valign='bottom'><b><?=$progText222;?> </b></TD>
        <TD valign='bottom'><b><?=$progText79;?> </b></TD>

<?
      $rowStyle = "";
      While ($row = mysql_fetch_array($result)) {

         $peripheralID         = $row["peripheralID"];
         $periphSerial         = $row["serial"];
         $peripheralTraitID    = $row["peripheralTraitID"];
         $periphManufacturer   = $row['visManufacturer'];
         $periphTypeClass      = $row['visTypeClass'];
         $periphDescription    = $row['visDescription'];
         $periphStatus         = $row['peripheralStatus'];
         $periphVendorName     = $row['vendorName'];
         $periphMacAddress     = $row['macAddress'];
         $periphPurchaseDate   = $row['purchaseDate'];
         $periphPurchasePrice  = $row['purchasePrice'];

         $stringLimit = round(($windowWidth / 25), 0);
         If (strlen($periphSerial) > $stringLimit) {
             $periphSerial = substr($periphSerial, 0, ($stringLimit-1))." ...";
         }
     
         // Display all the peripherals belonging to this server
?>
        <TR class='<? echo alternateRowColor(); ?>'>
          <TD class='smaller'><? echo writePrettyPeripheralName($periphDescription, "", $periphManufacturer); ?> &nbsp;</TD>
          <TD class='smaller'><? echo writePeripheralClass($periphTypeClass); ?> &nbsp;</TD>
          <TD class='smaller'><? echo writeNA($periphSerial); ?> &nbsp;</TD>
          <TD class='smaller'><? echo writeNA($periphVendorName); ?> &nbsp;</TD>
          <!--<TD class='smaller'><? echo writeNA($periphMacAddress); ?> &nbsp;</TD>-->
          <TD class='smaller'><? echo writeNA(displayDate($periphPurchaseDate)); ?> &nbsp;</TD>
          <TD class='smaller'><? echo writeNA($periphPurchasePrice); ?> &nbsp;</TD>
          <TD class='smaller'><? echo writeStatus($periphStatus);

          If ((strpos($_SERVER['SCRIPT_NAME'], "showfull.php") === false) OR ($_SESSION['sessionSecurity'] > 1)) {
              echo "&nbsp;";
          } Else {
    ?>
             <nobr>(<a class='action' href='<?=$_SERVER['SCRIPT_NAME']?>?spare=<?=$spare?>&hardwareID=<?=$hardwareID?>&peripheralID=<?=$peripheralID?>&setStatus=w'><?=$progText434;?></a>
              <a class='action' href='<?=$_SERVER['SCRIPT_NAME']?>?spare=<?=$spare?>&hardwareID=<?=$hardwareID?>&peripheralID=<?=$peripheralID?>&setStatus=n'><?=$progText435;?></a>
              <a class='action' href='<?=$_SERVER['SCRIPT_NAME']?>?spare=<?=$spare?>&hardwareID=<?=$hardwareID?>&peripheralID=<?=$peripheralID?>&setStatus=i'><?=$progText436;?></a>)</nobr>
    <?
          }
    ?>
          </TD>
          <TD valign='top' class='smaller'>
    <?
          If ($_SESSION['sessionSecurity'] > 1) {
              echo $progText437;
          } Else {
    ?>
             <A class='action' HREF="admin_peripherals.php?peripheralID=<?=$peripheralID;?>&cboUser=<?=$uid;?>"><?=$progText75;?></A>
    <?
              If ($_SESSION['sessionSecurity'] < 1) {
    ?>
                 &nbsp;<A class='action' HREF="delete.php?returnTo=<?=$hardwareID;?>&peripheralID=<?=$peripheralID;?>" onClick="return warn_on_submit('<?=$progText581;?>');"><?=$progText80;?></A>
    <?
              }
          }
          echo "</TD>";
      }

      echo "</table>";
  }

  mysql_free_result($result);

  $strSQL = "SELECT s.softwareID, s.serial, s.vendorID, v.vendorName, t.visName, t.visMaker, t.visVersion
    FROM (software as s, software_traits as t)
    LEFT JOIN vendors as v ON v.vendorID=s.vendorID
    WHERE s.hardwareID=$hardwareID AND t.softwareTraitID=s.softwareTraitID AND
      t.accountID=" . $_SESSION['accountID'] . " AND s.hidden='0'
    ORDER BY t.operatingSystem DESC, t.visName ASC, t.visVersion ASC";
  $result = dbquery($strSQL);

  echo "<table border='0' cellpadding='0' cellspacing='0' height='32'><tr><td>&nbsp;</td></tr></table>";
  echo "<font size='+1'>".$progText579.":</font> &nbsp;";
  If ($_SESSION['sessionSecurity'] < 2) {
      echo "(<A class='action' HREF='admin_software.php?cboSystem=$hardwareID&cboUser=$uid'>".$progText417."?</A>)\n";
  }

  If (mysql_num_rows($result) < 1) {
      echo "<br> &nbsp; *".$progText580."<br>";
  } Else {
?>
  <p><table border='0' cellpadding='4' cellspacing='0' width='100%'>
  <TR class='title'>
    <TD valign='bottom'><b><?=$progText173;?> </b></TD>
    <TD valign='bottom'><b><?=$progText160;?> </b></TD>
    <TD valign='bottom'><b><nobr><?=$progText375;?></nobr> </b></TD>
    <TD valign='bottom'><b><?=$progText1226;?> </b></TD>
    <TD valign='bottom'><b><?=$progText79;?> </b></TD>
  </TR>
<?

  $rowStyle = "";
  while ($row = mysql_fetch_array($result)) {

     $softwareID    = $row["softwareID"];
     $strSerial     = $row["serial"];
     $strName       = $row['visName'];
     $strMaker      = $row['visMaker'];
     $strVersion    = $row['visVersion'];
     $strVendorName = $row['vendorName'];
     
     $stringLimit = round(($windowWidth / 20), 0);
     If (strlen($strSerial) > $stringLimit) {
         $strSerial = substr($strSerial, 0, ($stringLimit-1))." ...";
     }
     $stringLimit = round(($windowWidth / 30), 0);
     If (strlen($strVersion) > $stringLimit) {
         $strVersion = substr($strVersion, 0, ($stringLimit-1))." ...";
     }
?>
    <TR class='<? echo alternateRowColor(); ?>'>
      <TD class='smaller'><? echo writePrettySoftwareName($strName, "", $strMaker); ?> &nbsp;</TD>
      <TD class='smaller'><? echo writeNA($strVersion); ?> &nbsp;</TD>
      <TD class='smaller'><? echo writeNA($strSerial); ?> &nbsp;</TD>
      <TD class='smaller'><? echo writeNA($strVendorName); ?> &nbsp;</TD>
      <TD class='smaller'>
    <?
      If ($_SESSION['sessionSecurity'] > 1) {
          echo $progText437;
      } Else {
    ?>
        <A class='action' HREF="admin_software.php?softwareID=<?=$softwareID;?>&cboUser=<?=$uid;?>"><?=$progText75;?></A>
    <?
          If ($_SESSION['sessionSecurity'] < 1) {
    ?>
              &nbsp;<A class='action' HREF="delete.php?returnTo=<?=$hardwareID;?>&softwareID=<?=$softwareID;?>" onClick="return warn_on_submit('<?=$progText582;?>');"><?=$progText80;?></A>
    <?
          }
      }
      echo "</TD>";
  }
  echo "</table>";
  }
}
?>
