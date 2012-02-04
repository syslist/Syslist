<?
  Include("Includes/global.inc.php");
  checkPermissions(0, 1800);

  If ($_POST['btnSubmit']) {

    $strSQL = "SELECT software_traits.softwareTraitID
       FROM software_traits LEFT JOIN software ON software_traits.softwareTraitID = software.softwareTraitID
       WHERE software.softwareTraitID IS NULL AND software_traits.hidden = '0'
          AND software_traits.accountID=" . $_SESSION['accountID'] . "";
    $result = dbquery($strSQL);
    while ($row = mysql_fetch_array($result)) {

        $strSQL2 = "SELECT software_licenses.licenseID
           FROM software_licenses, software_traits_licenses
           WHERE software_traits_licenses.softwareTraitID=" . $row["softwareTraitID"] . "
              AND software_licenses.licenseID=software_traits_licenses.licenseID
              AND software_licenses.accountID=" . $_SESSION['accountID'] . "";
        $result2 = dbquery($strSQL2);
        while ($row2 = mysql_fetch_array($result2)) {
            $strSQL3 = "DELETE FROM software_licenses WHERE licenseID=" . $row2["licenseID"] . " AND accountID=" . $_SESSION['accountID'] . "";
            $result3 = dbquery($strSQL3);
        }
        
        $strSQL4 = "DELETE FROM software_traits_licenses WHERE softwareTraitID=" . $row["softwareTraitID"] . " AND accountID=" . $_SESSION['accountID'] . "";
        $result4 = dbquery($strSQL4);
        $strSQL4 = "DELETE FROM software_actions WHERE softwareTraitID=" . $row["softwareTraitID"] . " AND accountID=" . $_SESSION['accountID'] . "";
        $result4 = dbquery($strSQL4);
        $strSQL4 = "DELETE FROM software_types WHERE softwareTraitID=" . $row["softwareTraitID"] . " AND accountID=" . $_SESSION['accountID'] . "";
        $result4 = dbquery($strSQL4);
        $strSQL4 = "DELETE FROM software_traits WHERE softwareTraitID=" . $row["softwareTraitID"] . " AND accountID=" . $_SESSION['accountID'] . "";
        $result4 = dbquery($strSQL4);
    }

    $strSQL = "SELECT peripheral_traits.peripheralTraitID
       FROM peripheral_traits LEFT JOIN peripherals ON peripheral_traits.peripheralTraitID = peripherals.peripheralTraitID
       WHERE peripherals.peripheralTraitID IS NULL AND peripheral_traits.hidden = '0'
          AND peripheral_traits.accountID=" . $_SESSION['accountID'] . "";
    $result = dbquery($strSQL);
    while ($row = mysql_fetch_array($result)) {
        $strSQL2 = "DELETE FROM peripheral_actions WHERE peripheralTraitID=" . $row["peripheralTraitID"] . " AND accountID=" . $_SESSION['accountID'] . "";
        $result2 = dbquery($strSQL2);
        $strSQL2 = "DELETE FROM peripheral_types WHERE peripheralTraitID=" . $row["peripheralTraitID"] . " AND accountID=" . $_SESSION['accountID'] . "";
        $result2 = dbquery($strSQL2);
        $strSQL2 = "DELETE FROM peripheral_traits WHERE peripheralTraitID=" . $row["peripheralTraitID"] . " AND accountID=" . $_SESSION['accountID'] . "";
        $result2 = dbquery($strSQL2);
    }

    $strError = "Update successful!";
  }

  writeHeader("Purge unused software and peripheral types");
  declareError(TRUE);
?>

  <p>
  Do not click 'Go!' more than once; doing so may cause the update to fail.
  <p>
  <FORM METHOD="post" ACTION="purgeUnusedTypes.php">
      <INPUT TYPE="submit" NAME="btnSubmit" VALUE="Go!">
  </FORM>

<?
  writeFooter();
?>
