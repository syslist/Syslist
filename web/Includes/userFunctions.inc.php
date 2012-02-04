<?
  # functions.inc.php *must* be included in a page before this file.

  // $intUserID - userID that should be pre-selected in drop down. Also can take "spare", "toSpare", and "toIndependant"
  // $showSpare - if you want "make spare" and/or "make independant" to be an option, set this = TRUE
  // $intLocationID - locationID upon which user list should be filtered.
  // $notSystem - set = TRUE if you want your list of users to include only those that have systems.
      // This will also cause "spare systems" and "independant systems" to be listed in dropdown.
  // $formName is name of form this is going into; necessary if you want javascript onChange form submit.
      // Set $formName = "formXYZ" if you DON'T want the onChange submit, but do need a formName
      // If you don't need javascript or a form name, just set to false.
  // Set $strSqlCondition if you need to further refine the list of users returned by the db.
  // Set $fieldName if you want the select drop-down to be called something other than 'cboUser'
  // Set $aryExtraOptions if you need additional custom options in the drop down. Value must be an array.

  Function buildUserSelect($intUserID,
                           $showSpare,
                           $intLocationID,
                           $notSystem = FALSE,
                           $formName = "",
                           $strSqlCondition = "",
                           $fieldName = "cboUser",
                           $aryExtraOptions = FALSE) {

      global $adminDefinedCategory, $progText591, $progText592, $progText593;
      global $progText594, $progText595, $progText596, $progText597;
      
      // If stuck, fix location ID
      if ($_SESSION['stuckAtLocation']) {
        $intLocationID = $_SESSION['locationStatus'];
      }

      if (!$notSystem) {
          // Set sql parameters to filter on locationID, if provided (and not set to "all")
          If (is_numeric($intLocationID)) {
              $sqlLocation = "(userLocationID=$intLocationID OR userLocationID IS NULL) AND";
          }
          $strSQL = "SELECT id, firstName, middleInit, lastName, userID FROM tblSecurity WHERE $sqlLocation
            accountID=" . $_SESSION['accountID'] . " AND hidden='0' $strSqlCondition ORDER BY lastName";
          $spareText = $progText593; # make this a spare system
      } else {

          // Set sql parameters to filter on locationID, if provided (and not set to "all")
          If (is_numeric($intLocationID)) {
              $sqlLocation = "(s.userLocationID=$intLocationID OR s.userLocationID IS NULL) AND";
          }
          $strSQL = "SELECT DISTINCT s.id, s.firstName, s.middleInit, s.lastName, s.userID FROM hardware as h,
            tblSecurity as s WHERE $sqlLocation s.id=h.userID AND s.hidden='0' AND s.accountID=" . $_SESSION['accountID'] . "
            $strSqlCondition ORDER BY s.lastName";
          $spareText = $progText592; # make this a spare part
      }

      if ($formName AND ($formName != "formXYZ")) {
          $jsText = "onChange=\"document.$formName.submit();\"";
      }

      $strReturnString = "<select name='".$fieldName."' size='1' $jsText>\n";
      $strReturnString .= "<option value=''>&nbsp;</option>\n";

      If (is_array($aryExtraOptions)) {
          Foreach ($aryExtraOptions as $extraOption) {
              $strReturnString .= "<option value='$extraOption' ".writeSelected($extraOption, $intUserID).">$extraOption</option>\n";
          }
      }

      if ($showSpare) {
          $strReturnString .= "<option value='spare' ".writeSelected("spare", $intUserID).">** ".$spareText." **</option>\n";
          if (!$notSystem) {
              $strReturnString .= "<option value='independent' ".writeSelected("independent", $intUserID).">** ".$progText594." **</option>\n";
	          if ($adminDefinedCategory) {
                  $strReturnString .= "<option value='adminDefined' ".writeSelected("adminDefined", $intUserID).">** ".$progText595." **</option>\n";
	          }
          }
          $showDivider = TRUE;
      }

      if ($notSystem) {
          $strSQLx = "SELECT count(*) FROM hardware WHERE sparePart='1' AND accountID=" . $_SESSION['accountID'] . "";
          $resultx = dbquery($strSQLx);
          $rowx = mysql_fetch_row($resultx);

          if ($rowx[0] > 0) {
              $strReturnString .= "<option value='toSpare' ".writeSelected("toSpare", $intUserID).">** ".$progText596." **</option>\n";
              $showDivider = TRUE;
          }
          mysql_free_result($resultx);

          $strSQLx = "SELECT count(*) FROM hardware WHERE sparePart='2' AND accountID=" . $_SESSION['accountID'] . "";
          $resultx = dbquery($strSQLx);
          $rowx = mysql_fetch_row($resultx);
          if ($rowx[0] > 0) {
              $strReturnString .= "<option value='toIndependent' ".writeSelected("toIndependent", $intUserID).">** ".$progText597." **</option>\n";
              $showDivider = TRUE;
          }
          mysql_free_result($resultx);

	      if ($adminDefinedCategory) {
                  $strSQLx = "SELECT count(*) FROM hardware WHERE sparePart='3' AND accountID=" . $_SESSION['accountID'] . "";
                  $resultx = dbquery($strSQLx);
                  $rowx = mysql_fetch_row($resultx);
                  if ($rowx[0] > 0) {
                      $strReturnString .= "<option value='toAdminDefined' ".writeSelected("toAdminDefined", $intUserID).">** ".$adminDefinedCategory." ".$progText591." **</option>\n";
                      $showDivider = TRUE;
                  }
                  mysql_free_result($resultx);
    	  }
      }

      if ($showDivider) {
          $strReturnString .= "<option value=''>&nbsp;</option>\n";
      }

      $result = dbquery($strSQL);
      while ($row = mysql_fetch_array($result)) {
          $strReturnString .= "<option value='".$row['id']."' ".writeSelected($row['id'], $intUserID).">";
          $strReturnString .= buildName($row["firstName"], $row["middleInit"], $row["lastName"], 0);
          If ($row["userID"]) {
              $strReturnString .= " (".$row["userID"].")";
          }
          $strReturnString .= "</option>\n";
      }
      $strReturnString .= "</select>\n";
      Return $strReturnString;
  }

  Function buildSystemSelect($intUserID, $intSystemID, $intLocationID, $userAccountID = "") {
      global $adminDefinedCategory;
      global $progText598, $progText157, $progText600;

      // If stuck, fix location ID
      if ($_SESSION['stuckAtLocation']) {
        $intLocationID = $_SESSION['locationStatus'];
      }

      If ($userAccountID) {
          $_SESSION['accountID'] = $userAccountID;
      }

      // If a true userID is provided, build a list of all systems associated with that userID
      If (is_numeric($intUserID)) {

          $strSQL = "SELECT ht.visDescription, ht.visManufacturer, h.ipAddress, h.hardwareID, h.hostname
            FROM hardware_types as ht, hardware as h, tblSecurity as s WHERE s.id=h.userID AND
            h.hardwareTypeID=ht.hardwareTypeID AND h.userID=$intUserID AND ht.accountID=" . $_SESSION['accountID'] . "
            ORDER BY ht.visDescription ASC";

      // Build a list of non-user systems, depending on the category indicated.
      } Else {

          If ($intUserID == "toSpare") { # build list of all spare systems
              $sparePart = "1";
          } ElseIf ($intUserID == "toIndependent") { # build list of all independent systems
              $sparePart = "2";
          } ElseIf ($intUserID == "toAdminDefined") { # build list of all systems placed in the admin defined category
              $sparePart = "3";
          }

          // Set sql parameters to filter on locationID, if provided (and not set to "all")
          If (is_numeric($intLocationID)) {
              $sqlLocation = "h.locationID=$intLocationID AND";
          }

          $strSQL = "SELECT ht.visDescription, ht.visManufacturer, h.ipAddress, h.hardwareID, h.hostname
            FROM hardware_types as ht, hardware as h WHERE $sqlLocation h.sparePart='$sparePart' AND
            h.hardwareTypeID=ht.hardwareTypeID AND ht.accountID=" . $_SESSION['accountID'] . " ORDER BY
            ht.visDescription ASC";
      }

      If (($intUserID != "") AND ($intUserID != "spare")) {
          $result = dbquery($strSQL);

          $strReturnString = "<select name='cboSystem' size='1'>\n";
          $strReturnString .= "<option value=''>- ".$progText598." -</option>\n";
          while ($row = mysql_fetch_array($result)) {
              $strReturnString .= "<option value='".$row['hardwareID']."' ".writeSelected($row['hardwareID'], $intSystemID).">";
              $strReturnString .= writePrettySystemName($row['visDescription'], $row['visManufacturer'])."&nbsp; - &nbsp;".$progText600.": ".writeNA($row['hostname'])." &nbsp;-&nbsp; ".$progText157.": ".writeNA($row['ipAddress']);
              $strReturnString .= "</option>\n";
          }
          $strReturnString .= "</select>\n";
          Return $strReturnString;
      }
  }

  // $intUserID - userID that should be pre-selected in drop down. Also can take "spare", "toSpare", and "toIndependant"
  // $intSystemID - systemID that should be pre-selected in drop down.
  // $intLocationID - locationID upon which user and/or system list should be filtered.
  // $showSpare - if you want "make spare part" to be an option, set this = TRUE
  // $formName - name of form these dropdowns are going into; necessary if you want javascript onChange form submit.
  Function buildUserSystemSelect($intUserID, $intSystemID, $intLocationID, $showSpare, $formName = "") {
      echo buildUserSelect($intUserID, $showSpare, $intLocationID, TRUE, $formName);
      If (($intUserID != "") AND ($intUserID != "spare")) {
          echo "<br>";
      }
      // If stuck, fix location ID
      if ($_SESSION['stuckAtLocation']) {
        $intLocationID = $_SESSION['locationStatus'];
      }
      echo buildSystemSelect($intUserID, $intSystemID, $intLocationID);
  }

  Function fetchUserNameFromID ($intUserID) {
      global $progText599, $progText437;

      if ($intUserID) {
          $strSQL = "SELECT firstName, middleInit, lastName FROM tblSecurity WHERE id=$intUserID AND hidden='0' AND accountID=" . $_SESSION['accountID'] . "";
          $result = dbquery($strSQL);
          $row = mysql_fetch_array($result);
          if ($row =="") {  // user has been deleted from tblSecurity
              $strReturnString = $progText599; # Deleted User
          } else {
              $strReturnString = buildName($row["firstName"], $row["middleInit"], $row["lastName"], 1);
          }
          return $strReturnString;
      } else {
          return $progText437; # N/A
      }
  }
?>
