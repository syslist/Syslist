<?
  Include("Includes/global.inc.php");
  checkPermissions(0, 1800);
  
  $editID = cleanFormInput(getOrPost('editID'));
  
  If (!$strError) {
      $errorMessage = $progText283.". (Uid: $editID ,";

      // Delete associated picture file before we hide reference
      $strSQL = "SELECT picURL FROM tblSecurity WHERE id=$editID AND accountID=" . $_SESSION['accountID'] . "";
      $dbResult = dbQuery($strSQL);
      $aryRow = mysql_fetch_row($dbResult);
      if (file_exists($aryRow[0])) {
    	  unlink($aryRow[0]);
      }
      $strSQL1 = "UPDATE hardware SET userID=NULL, sparePart='1' WHERE userID=$editID AND accountID=" . $_SESSION['accountID'] . "";
      $result1 = dbquery($strSQL1);

      $strSQL2 = "UPDATE comments SET assignedUserID=" . $_SESSION['userID'] . " WHERE assignedUserID=$editID and accountID=" . $_SESSION['accountID'] . "";
      $result2 = dbqueryWithAlert($strSQL2, $adminEmail, ($errorMessage." c)"));

      $strSQL3 = "UPDATE tblSecurity SET hidden='1', password=NULL, userID=NULL WHERE id=$editID AND accountID=" . $_SESSION['accountID'] . "";
      $result3 = dbqueryWithAlert($strSQL3, $adminEmail, ($errorMessage." u)"));
  }

  fillError($progText296);

  writeHeader($progText296);

  declareError(TRUE);

  writeFooter();
?>
