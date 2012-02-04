<?
  Function fetchLocationName($strSQL) {
      global $progText520;

      If (is_numeric($_SESSION['locationStatus'])) {
          $locationResult = dbquery($strSQL);
          $thisthing = mysql_fetch_array($locationResult);
	      $strReturnString = $thisthing['locationName'];
      } else {
          $strReturnString = $progText520;
      }
      return $strReturnString;
  }

  // $locationID - locationID that the drop down should default to
  // $showLink - if true, show 'add new location' link next to drop down
  // $formName is name of form this is going into; necessary for javascript onChange form submit.
      // Set $formName = "formXYZ" if you DON'T want the onChange submit, but do need a formName
      // If you don't need javascript or a form name, just set to false.
  // $showUnassigned - if true, add 'unassigned' to list of options in drop down.
  Function buildLocationSelect($locationID, $showLink=TRUE, $formName=FALSE, $showUnassigned=FALSE, $useNamesForValues=FALSE, $numLines=1) {
      global $progText521, $progText866;
      // Short-circuit if stuck
      if ($_SESSION['stuckAtLocation']) {
          return;
      }
      if ($formName AND ($formName != "formXYZ")) {
          $jsText = "onChange=\"document.$formName.submit();\"";
      }

      // Get all location types for the drop down menu
      echo "<SELECT SIZE=\"$numLines\" " . (($numLines > 1) ? "MULTIPLE " : "") . "NAME=\"cboLocationID" . (($numLines > 1) ? "[]" : "") . "\" $jsText>";
      
      $strSQL = "SELECT * FROM locations WHERE accountID=" . $_SESSION['accountID'] . "
        ORDER BY locationName ASC";
      $result = dbquery($strSQL);
      if ($numLines == 1) {
          echo "   <OPTION VALUE=\"\">&nbsp;</OPTION>\n";
      }
      If ($showUnassigned) {
          echo "   <OPTION VALUE=\"unassigned\" ".writeSelected($locationID, "unassigned").">* ".$progText866." *</OPTION>\n";
      }
      while ($row = mysql_fetch_array($result)) {
          echo "   <OPTION VALUE=\"" . (($useNamesForValues) ? $row['locationName'] : $row['locationID']) . "\" ";
          echo writeSelected($locationID, $row['locationID']);
          echo ">".$row['locationName']."</OPTION>\n";
       }
       echo "</SELECT>";
       if ($showLink) {
           echo " &nbsp;<a href='admin_locations.php'>".$progText521."</a>";
       }
  }

  Function buildLocationDropDown() {
      global $systemStatus, $spare, $pageName, $progText34;
      global $progText522, $progText523;
      
      // Short-circuit if stuck
      if ($_SESSION['stuckAtLocation']) {
          return;
      }
  ?>
     <FORM NAME="LocationSelect" METHOD="post" ACTION="<?=$pageName;?>">
       <b><?=$progText34;?>:</b>&nbsp;
       <SELECT class='smaller' SIZE="1" NAME="cboNameLocation" onChange="document.LocationSelect.submit();">

               <?

                  $strSQL = "SELECT * FROM locations WHERE accountID=" . $_SESSION['accountID'] . "
                    ORDER BY locationName ASC";
                  $result = dbquery($strSQL);

                  // don't show "view all" if there's only one choice.
                  If (mysql_num_rows($result) > 1) {
                      echo "   <OPTION VALUE='all,$spare,$systemStatus' ";
                      echo writeSelected($_SESSION['locationStatus'], "all");
	                  echo ">".$progText522."</OPTION>";
                  }

                  // write all locations for the drop down menu
                   while ($rows = mysql_fetch_array($result)) {
                       echo "   <OPTION VALUE='".$rows['locationID'].",$spare,$systemStatus' ";
                       echo writeSelected($_SESSION['locationStatus'], $rows['locationID']);
                       echo ">".$rows['locationName']."</OPTION>";
                   }
               ?>

	   </SELECT> &nbsp;
       <INPUT TYPE="submit" NAME="btnSubmitLoc" class='smaller' VALUE="<?=$progText523;?>">
     </FORM>
<?
    }
?>
