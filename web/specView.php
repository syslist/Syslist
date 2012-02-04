<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);
  
  $specType = cleanFormInput(getOrPost('specType'));
  $strSQL = "SELECT * FROM hardware_types WHERE hardwareTypeID=$specType AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $row = mysql_fetch_array($result);

  $strDescription   = $row["visDescription"];
  $strManufacturer  = $row["visManufacturer"];
  $strNotes         = $row["notes"];

  $strFullDescription = writePrettySystemName($strDescription, $strManufacturer);

  writePopHeader($strFullDescription);
?>
  <b><? echo $strFullDescription; ?></b>
<?
  If ($_SESSION['sessionSecurity'] < 2) {
      echo " &nbsp;(<a href=\"javascript:opener.location='admin_hw_types.php?hardwareTypeID=$specType';self.close()\">".$progText461."</A>)";
  }
?>
 <p>
 <table border='0' cellpadding='4' cellspacing='0'>
  <tr>
    <td valign='top'><nobr><u><? echo $progText70;?></u>: &nbsp;</nobr></td>
    <td><? echo writeNA($strNotes);?></td>
  </tr>
  </table><p>

<?
      // default peripherals assigned to hardwareType
      echo "<i>".$progText82."</i><p>\n";

      $strSQL = "SELECT * FROM peripheral_traits as p, hardware_type_defaults as h WHERE
        h.accountID=" . $_SESSION['accountID'] . " AND h.hardwareTypeID=$specType AND h.objectID=p.peripheralTraitID
        AND h.objectType='p' ORDER BY p.visDescription ASC";
      $result = dbquery($strSQL);
      If (mysql_num_rows($result)) {
          echo "<ul>";
      }
      while ($row = mysql_fetch_array($result)) {
          $defaultID     = $row['hardwareTypeDefaultID'];
          $description   = $row['visDescription'];
          $model         = $row['visModel'];
          $manufacturer  = $row['visManufacturer'];

          echo "<li>".writePrettyPeripheralName($description, $model, $manufacturer)."\n";
      }
      If (mysql_num_rows($result)) {
          echo "</ul>";
      } Else {
          echo "<ul><li>".$progText437."</li></ul>\n";
      }

      // default software assigned to hardwareType
      echo "<p><i>".$progText83."</i><p>\n";

      $strSQL = "SELECT * FROM software_traits as s, hardware_type_defaults as h WHERE
        h.accountID=" . $_SESSION['accountID'] . " AND h.hardwareTypeID=$specType AND h.objectID=s.softwareTraitID
        AND h.objectType='s' ORDER BY s.visName ASC";
      $result = dbquery($strSQL);
      If (mysql_num_rows($result)) {
          echo "<ul>";
      }

      while ($row = mysql_fetch_array($result)) {
          $defaultID  = $row['hardwareTypeDefaultID'];
          $name       = $row['visName'];
          $maker      = $row['visMaker'];
          $version    = $row['visVersion'];

          echo "<li>".writePrettySoftwareName($name, $version, $maker)."\n";
      }
      If (mysql_num_rows($result)) {
          echo "</ul>";
      } Else {
          echo "<ul><li>".$progText437."</li></ul>\n";
      }

  writePopFooter();
?>
