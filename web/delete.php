<?
  Include("Includes/global.inc.php");
  checkPermissions(0, 1800);

  $errorMessage = $progText283.". (";
  
  $returnTo      = getOrPost('returnTo');
  $commentID     = getOrPost('commentID');
  $commentType   = getOrPost('commentType');
  $id            = getOrPost('id');
  $target        = getOrPost('target');
  $hardwareID    = getOrPost('hardwareID');
  $peripheralID  = getOrPost('peripheralID');
  $softwareID    = getOrPost('softwareID');

If ($commentID) {
    $strSQL = "DELETE FROM comments WHERE commentID=$commentID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbquery($strSQL);

    If ($commentType == 'h') {
        redirect("showfull.php", "notify=delete&hardwareID=$returnTo");
    } elseif ($commentType == 'u') {
        redirect("viewUser.php", "notify=delete&viewID=$returnTo");
    } else {
        redirect("commentLog.php", "notify=delete");
    }

} ElseIf (($id) && ($target)) {
    // Response to Delete Picture link
    // Populate settings based on target
    if ($target == "user")
    {
    	$targetTable = "tblSecurity";
    	$targetID = "id";
    	$targetRedirect = "editUser.php?notify=delete&editID=$id";
    }
    elseif ($target == "hw")
    {
    	$targetTable = "hardware";
    	$targetID = "hardwareID";
    	$targetRedirect = "showfull.php?notify=delete&hardwareID=$id";
    }

    // Delete associated picture file, then clear reference
    $strSQL = "SELECT picURL FROM $targetTable WHERE $targetID=$id AND accountID=" . $_SESSION['accountID'] . "";
    $dbResult = dbQuery($strSQL);
    $aryRow = mysql_fetch_row($dbResult);
    if (file_exists($aryRow[0]))
    	unlink($aryRow[0]);
    $strSQL = "UPDATE $targetTable SET picURL='' WHERE $targetID=$id AND accountID=" . $_SESSION['accountID'] . "";
    dbQuery($strSQL);

    redirect($targetRedirect);

} ElseIf ($hardwareID) {

    $strSQLlock = "LOCK TABLES software WRITE, peripherals WRITE, comments WRITE, hardware WRITE,
      peripheral_actions WRITE, ip_history WRITE, software_actions WRITE";
    $resultLock = dbquery($strSQLlock);

    // Delete associated picture file before we delete reference in row
    $strSQL = "SELECT picURL FROM hardware WHERE hardwareID=$hardwareID AND accountID=" . $_SESSION['accountID'] . "";
    $dbResult = dbQuery($strSQL);
    $aryRow = mysql_fetch_row($dbResult);
    if (file_exists($aryRow[0]))
    	unlink($aryRow[0]);

    $strSQL = "DELETE FROM software_actions WHERE hardwareID=$hardwareID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbqueryWithAlert($strSQL, $adminEmail, ($errorMessage." Hid: $hardwareID)"));

    $strSQL = "DELETE FROM peripheral_actions WHERE hardwareID=$hardwareID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbqueryWithAlert($strSQL, $adminEmail, ($errorMessage." Hid: $hardwareID)"));

    $strSQL = "DELETE FROM software WHERE hardwareID=$hardwareID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbqueryWithAlert($strSQL, $adminEmail, ($errorMessage." Hid: $hardwareID)"));

    $strSQL = "DELETE FROM peripherals WHERE hardwareID=$hardwareID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbqueryWithAlert($strSQL, $adminEmail, ($errorMessage." Hid: $hardwareID)"));

    $strSQL = "DELETE FROM comments WHERE subjectID=$hardwareID AND subjectType='h' AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbqueryWithAlert($strSQL, $adminEmail, ($errorMessage." Hid: $hardwareID)"));

    $strSQL = "DELETE FROM hardware WHERE hardwareID=$hardwareID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbqueryWithAlert($strSQL, $adminEmail, ($errorMessage." Hid: $hardwareID)"));

    $strSQL = "DELETE FROM ip_history WHERE hardwareID=$hardwareID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbqueryWithAlert($strSQL, $adminEmail, ($errorMessage." Hid: $hardwareID)"));

    $strSQLunlock = "UNLOCK TABLES";
    $resultUnlock = dbquery($strSQLunlock);

    fillError($progText284);

} ElseIf ($peripheralID) {

    // Find the IDs this peripheral is associated with, then archive the transaction
    $result = dbquery("SELECT hardwareID, peripheralTraitID FROM peripherals WHERE peripheralID=$peripheralID AND accountID=" . $_SESSION['accountID'] . "");
    $row = mysql_fetch_array($result);
    if ($row['hardwareID']) {
        $oldHardwareID = $row['hardwareID'];
    } else {
        $oldHardwareID = "0";
    }
    $result = dbquery("INSERT INTO peripheral_actions (peripheralTraitID, hardwareID, actionType, actionDate, userID, movedToID, accountID) VALUES (" . $row['peripheralTraitID'] . ", " . $oldHardwareID . ", 'userDel', " . date("YmdHis") . ", " . $_SESSION['userID'] . ", NULL, " . $_SESSION['accountID'] . ")");

    // we truly delete spare peripherals; but we only hide peripherals associated with systems.
    // this is because the agent might otherwise re-create peripherals associated with a system,
    // even if the user does not want it to.
    If ($returnTo == "spare") {
        $strSQL = "DELETE FROM peripherals WHERE peripheralID=$peripheralID AND accountID=" . $_SESSION['accountID'] . "";
    } Else {
        $strSQL = "UPDATE peripherals SET hidden='1' WHERE peripheralID=$peripheralID AND accountID=" . $_SESSION['accountID'] . "";
    }
    $result = dbquery($strSQL);

    if ($returnTo == "spare") {
        redirect("sparePeripherals.php", "notify=delete");
    } else {
        redirect("showfull.php", "notify=delete&hardwareID=$returnTo");
    }

} ElseIf ($softwareID) {
    // we truly delete spare software; but we only hide software associated with systems.
    // this is because the agent might otherwise re-create software associated with a system,
    // even if the user does not want it to.
    If ($returnTo == "spare") {
        $strSQL1 = "DELETE FROM software WHERE softwareID=$softwareID AND accountID=" . $_SESSION['accountID'] . "";
    } Else {
        // Find the IDs this peripheral is associated with, then archive the transaction
        $result = dbquery("SELECT hardwareID, softwareTraitID FROM software WHERE softwareID=$softwareID AND accountID=" . $_SESSION['accountID'] . "");
        $row = mysql_fetch_array($result);
        if ($row['hardwareID']) {
            $oldHardwareID = $row['hardwareID'];
        } else {
            $oldHardwareID = "0";
        }
        // Optionally capture software history in the software_actions table
        if ($captureSoftwareHistory) {
            $result = dbquery("INSERT INTO software_actions (softwareTraitID, hardwareID, actionType, actionDate, userID, movedToID, accountID) VALUES (" . $row['softwareTraitID'] . ", " . $oldHardwareID . ", 'userDel', " . date("YmdHis") . ", " . $_SESSION['userID'] . ", NULL, " . $_SESSION['accountID'] . ")");
        }
        // check if software trait is marked canBeMoved; if not, hidden should be set to '2', not '1',
        // which will make the instance invisible to the user but will cause it to continue to be
        // counted against total licenses of this software trait.
        $strSQL0 = "SELECT t.canBeMoved FROM software as s, software_traits as t WHERE
          s.softwareID=$softwareID AND s.softwareTraitID=t.softwareTraitID AND s.accountID=" . $_SESSION['accountID'] . "";
        $result0 = dbquery($strSQL0);
        $row0 = mysql_fetch_row($result0);
        If ($row0[0] === "1") {
            $strHidden = "1";
        } Else {
            $strHidden = "2";
        }
        $strSQL1 = "UPDATE software SET hidden='$strHidden' WHERE softwareID=$softwareID AND
          accountID=" . $_SESSION['accountID'] . "";
    }
    $result1 = dbquery($strSQL1);

    if ($returnTo == "spare") {
        redirect("spareSoftware.php", "notify=delete");
    } else {
        redirect("showfull.php", "notify=delete&hardwareID=$returnTo");
    }
}

writeHeader($progText284);

declareError(TRUE);

writeFooter();
?>
