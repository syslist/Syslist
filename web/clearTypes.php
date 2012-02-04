<?
  Include("Includes/global.inc.php");
  checkPermissions(0, 1800);

  If ($_POST['btnSubmit']) {

    $strSQLlock = "LOCK TABLES software WRITE, software_traits WRITE, software_types WRITE,
      software_actions WRITE, software_traits_licenses WRITE, software_licenses WRITE,
      peripheral_actions WRITE, peripherals WRITE, peripheral_types WRITE, peripheral_traits WRITE";
    $resultLock = dbquery($strSQLlock);

    # Do not delete types that have vendor info or were marked "preserve"
    $strSQL2 = "SELECT peripheralTraitID FROM peripheral_traits
      WHERE universalVendorID IS NULL AND preserve='0' AND accountID=" . $_SESSION['accountID'] . "";
    $result2 = dbquery($strSQL2);
    While ($row = mysql_fetch_array($result2)) {
        $strSQL = "DELETE FROM peripheral_actions WHERE peripheralTraitID = " . $row["peripheralTraitID"] . "";
        $result = dbquery($strSQL);
        $strSQL = "DELETE FROM peripheral_traits WHERE peripheralTraitID = " . $row["peripheralTraitID"] . "";
        $result = dbquery($strSQL);
        $strSQL = "DELETE FROM peripheral_types WHERE peripheralTraitID = " . $row["peripheralTraitID"] . "";
        $result = dbquery($strSQL);
        $strSQL = "DELETE FROM peripherals WHERE peripheralTraitID = " . $row["peripheralTraitID"] . "";
        $result = dbquery($strSQL);
    }

    # Do not delete types that have license info, were banned, or have vendor info
    $strSQL2 = "SELECT software_traits.softwareTraitID FROM software_traits
      LEFT JOIN software_traits_licenses ON software_traits.softwareTraitID=software_traits_licenses.softwareTraitID
      LEFT JOIN software_licenses ON software_traits_licenses.licenseID=software_licenses.licenseID
      WHERE software_traits.universalVendorID IS NULL AND software_traits_licenses.licenseID IS NULL AND
        software_traits.isBanned='0' AND software_traits.accountID=" . $_SESSION['accountID'] . "";
    $result2 = dbquery($strSQL2);
    While ($row = mysql_fetch_array($result2)) {
        $strSQL = "DELETE FROM software WHERE softwareTraitID = " . $row["softwareTraitID"] . "";
        $result = dbquery($strSQL);
        $strSQL = "DELETE FROM software_traits WHERE softwareTraitID = " . $row["softwareTraitID"] . "";
        $result = dbquery($strSQL);
        $strSQL = "DELETE FROM software_types WHERE softwareTraitID = " . $row["softwareTraitID"] . "";
        $result = dbquery($strSQL);
        $strSQL = "DELETE FROM software_actions WHERE softwareTraitID = " . $row["softwareTraitID"] . "";
        $result = dbquery($strSQL);
    }

    $strSQLunlock = "UNLOCK TABLES";
    $resultUnlock = dbquery($strSQLunlock);

    $strError = "Update successful!";
  }

  writeHeader("delete software and peripherals");
  declareError(TRUE);
?>

  <p>
  This script will delete every software and peripheral record in the database. Only software records with manually-entered license information, vendor information, or a "ban" flag will be kept. Only peripheral records with vendor information or a "preserve" flag will be kept.<p>

  Do not click 'Go!' more than once; doing so may cause the update to fail.
  <p>
  <FORM METHOD="post" ACTION="clearTypes.php">
      <INPUT TYPE="submit" NAME="btnSubmit" VALUE="Go!">
  </FORM>

<?
  writeFooter();
?>
