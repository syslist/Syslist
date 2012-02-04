<?
  Include("Includes/global.inc.php");
  Include("Includes/reportFunctions.inc.php");
  Include("Includes/ticketFunctions.inc.php");
  checkPermissions(2, 1800);

  $numAttributes       = getOrPost('numAttributes');
  $aryPassedShow       = getOrPost('aryPassedShow');
  $aryPassedAttribute  = getOrPost('aryPassedAttribute');
  $btnSort             = getOrPost('btnSort');
  $btnUp               = getOrPost('btnUp');
  $btnDown             = getOrPost('btnDown');
  $btnShow             = getOrPost('btnShow');
  
  $ticketList = $_COOKIE['ticketList'];
  
  $exportMethod = getOrPost('exportMethod');
  
  
  // Used throughout the script--If stuck at a location, you can only see users who are there or who have no location
  if ($_SESSION['stuckAtLocation']) {
      $stuckSQL = " AND (userLocationID IS NULL OR userLocationID=" . $_SESSION['locationStatus'] . ")";
  } else {
      $stuckSQL = "";
  }

  // If no submit button is pressed or first visit then
  // predefine arrays and set defaults
  if (getOrPost('bolSubmit') != "TRUE") {

      $numAttributes = 8;  # number of possible output details
      $btnSort = 1;        # default sort position

      // Default values upon user's entry to the page -- set as appropriate
      // Check whether the output details are stored in cookie
      if (!$ticketList[1]) {
          $aryAttribute[1] = $progText6; $aryShow[1] = 1;
          $aryAttribute[2] = $progText7; $aryShow[2] = 1;
          $aryAttribute[3] = $progText138; $aryShow[3] = 1;
          $aryAttribute[4] = $progText960; $aryShow[4] = 1;
          $aryAttribute[5] = $progText1264; $aryShow[5] = 1;
          $aryAttribute[6] = $progText34; $aryShow[6] = 1;
          $aryAttribute[7] = $progText800; $aryShow[7] = 1;
          $aryAttribute[8] = $progText371; $aryShow[8] = 1;
	  
      } else {
          # explode returns an array starting from subscript 0. So a blank value is inserted
          # at the beginning so that $aryAttribute[0] and $aryShow[0] are blank.
          $ticketList[1] = ",".$ticketList[1];
          $ticketList[2] = ",".$ticketList[2];
          $aryAttribute = explode(",",$ticketList[1]);
          $aryShow = explode(",",$ticketList[2]);
          $btnSort = $ticketList[3];
      }
  } else {  # decode incoming arrays and check submit buttons

      $aryShow = unserialize(urldecode($aryPassedShow));
      $aryAttribute = unserialize(urldecode($aryPassedAttribute));
      $btnSortPosition = $btnSort;

      // Process incoming checkbox values for show/sort and adjust array

      updateCheckboxArray($numAttributes, $btnShow, $aryShow);

      // Check for an up/down button submitted from form
      upDownSubmitHandler($numAttributes, $btnUp, $btnDown, $btnSort, $aryShow, $aryAttribute); // Validate purchase date and purchase price 
      
      $cboStatus = getOrPost('cboStatus');
      $cboPriority = getOrPost('cboPriority');
      $cboAssignedUserID = getOrPost('cboAssignedUserID');
      $cboCategory = getOrPost('cboCategory');
      if ($_SESSION['stuckAtLocation']) {
          // This is subtle. You can't just look at the ticket's location to determine whether the user is allowed to see the ticket. 
          $cboLocationID = array();
      } else {	  
          $cboLocationID = getOrPost('cboLocationID');
      }
      $strDateCreated = validateDate($progText421, getOrPost('txtDateCreated'), 1900, (date("Y")+1), FALSE); 
      $cboDateCreated = cleanComparatorInput(getOrPost('cboDateCreated')); 
      $strTicketText = validateText($progText371, getOrPost('txtTicketText'), 1, 100, FALSE, FALSE); 
  }

  // If an export button pressed -- process the report ELSE
  // regenerate the form with the updated values

  if ($exportMethod != "") {

      // Validate the sort selection

      $strError = validateSort($numAttributes, $btnSort, $aryShow);

      // Compress incoming arrays based on what attributes are shown

      if (!$strError) {
          $strError = compressAttributeArray($numAttributes,
                                             $numToShow,
                                             $btnSort,
                                             $aryShow,
                                             $aryAttribute,
                                             $aryCompAttribute);
      }

      // Preload DB items with recursive lookups into arrays

      $locationFieldIndex = 0;
      if (!$strError) {

          for ($i=1; $i<=$numToShow; $i++) {

              switch ($aryCompAttribute[$i]) {

                case "$progText6":  # Ticket Status
                    loadLookupTable($i, $progText6, TRUE, 'commentStatus', $aryLookupTable);
                    $aryLookupTable[$i]['values']['Open'] = $progText14;
                    $aryLookupTable[$i]['values']['In Progress'] = $progText15;
		            $aryLookupTable[$i]['values']['Resolved'] = $progText16;
                break;

                case "$progText7":  # Ticket Priority
                    loadLookupTable($i, $progText7, TRUE, 'commentPriority', $aryLookupTable);
                    for ($j = 1; $j <= 5; $j++) {
                        $aryLookupTable[$i]['values']["" . $j] = "" . $j;
                    }	    
                break;
		
                case "$progText138":  # Assigned To
                    loadLookupTable($i, $progText138, TRUE, 'assignedUserID', $aryLookupTable);
                    $strSQL = "SELECT id, firstName, middleInit, lastName, userID FROM tblSecurity WHERE
                        accountID=" . $_SESSION['accountID'] . " AND hidden='0' AND securityLevel < 2 ORDER BY lastName";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $aryLookupTable[$i]['values'][$row['id']] = $row['userID'];
                    }
                break;
                
                case "$progText34":  # Location
                    loadLookupTable($i, $progText34, TRUE, 'commentID', $aryLookupTable);
                    $locationFieldIndex = $i;
                break;
                
                case "$progText960": # Category
                    loadLookupTable($i, $progText960, TRUE, 'categoryID', $aryLookupTable);
                    $strSQL = "SELECT * FROM commentCategories WHERE accountID=" . $_SESSION['accountID'] . "";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $aryLookupTable[$i]['values'][$row['categoryID']] = $row['categoryName'];
                    }
                break;

                case "$progText1264": # Date Created
                    loadLookupTable($i, $progText1264, FALSE, 'commentDate', $aryLookupTable);
                    $aryLookupTable[$i]['date'] = TRUE;
                break;
                
                case "$progText800": # Ticket Subject
                    loadLookupTable($i, $progText800, TRUE, 'subjectID', $aryLookupTable);
                    $strSQL = "SELECT commentID, subjectID, subjectType FROM comments WHERE accountID=" . $_SESSION['accountID'] . "";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        // Jacked striahgt from commentLog.. kinda funny, eh?
                        if ($row['subjectType'] == 'h') {
                            $strSQL2 = "SELECT t.visDescription, t.visManufacturer FROM hardware AS h, hardware_types AS t 
                                WHERE h.hardwareTypeID=t.hardwareTypeID AND h.hardwareID=".$row['subjectID']." AND h.accountID=".$_SESSION['accountID'];
                            $result2 = dbquery($strSQL2);
                            $row2 = mysql_fetch_array($result2);
                            $aryLookupTable[$i]['values'][$row['subjectType']][$row['subjectID']] = "<a href='showfull.php?hardwareID=".$row["subjectID"]."'>".
                                writePrettySystemName($row2["visDescription"], $row2["visManufacturer"])."</a>";
                        } elseif ($row['subjectType'] == 'u') {
                            $strSQL2 = "SELECT s.firstName, s.middleInit, s.lastName FROM tblSecurity as s WHERE
                                s.id=".$row["subjectID"]." AND s.accountID=" . $_SESSION['accountID'] . "";
                            $result2 = dbquery($strSQL2);
                            $row2 = mysql_fetch_array($result2);
                            $aryLookupTable[$i]['values'][$row['subjectType']][$row['subjectID']] = "<a href='viewUser.php?viewID=".$row["subjectID"]."'>".
                                buildName($row2["firstName"], $row2["middleInit"], $row2["lastName"], 1)."</a>";
                        } else {
                            $aryLookupTable[$i]['values'][$row['subjectType']][$row['subjectID']] = ""; // will default to N/A
                        }                        
                    }
                    $aryLookupTable[$i]['subject'] = TRUE;
                    
                break;    
                
                case "$progText371": # Ticket Text
                    loadLookupTable($i, $progText371, FALSE, 'commentText', $aryLookupTable);
                break;
                
              }
          }
        
        // This makes sure we have everything we need to filter by location
        
        // Maps locationID -> locationName
        if (!isset($aryLocationLookup)) {
            $aryLocationLookup = preloadLocationLookup();
        }
        // Maps hardwareID -> locationID
        if (!isset($aryHardwareLocation)) {
            $aryHardwareLocation = preloadHardwareLocation();
        }                    
        // Maps userID -> locationID
        $strSQL = "SELECT id, userLocationID FROM tblSecurity WHERE accountID=" . $_SESSION['accountID'];
        $result = dbquery($strSQL);
        while ($row = mysql_fetch_assoc($result)) {
            $aryUserLocation[$row['id']] = $row['userLocationID'];
        }
        // Maps softwareID -> locationID
        $strSQL = "SELECT softwareID, locationID, hardwareID, sparePart FROM software WHERE accountID=" . $_SESSION['accountID'];
        $result = dbquery($strSQL);
        while ($row = mysql_fetch_assoc($result)) {
            if ($row['sparePart'] == "1") {
                $arySoftwareLocation[$row['softwareID']] = $row['locationID'];
            } else {
                $arySoftwareLocation[$row['softwareID']] = $aryHardwareLocation[$row['hardwareID']];
            }
        }
        // Maps peripheralID -> locationID
        $strSQL = "SELECT peripheralID, locationID, hardwareID, sparePart FROM peripherals WHERE accountID=" . $_SESSION['accountID'];
        $result = dbquery($strSQL);
        while ($row = mysql_fetch_assoc($result)) {
            if ($row['sparePart'] == "1") {
                $aryPeripheralLocation[$row['peripheralID']] = $row['locationID'];
            } else {
                $aryPeripheralLocation[$row['peripheralID']] = $aryHardwareLocation[$row['hardwareID']];
            }
        }
        
        $strSQL = "SELECT commentID, subjectID, subjectType FROM comments where accountID=" . $_SESSION['accountID'] . "";
        $result = dbquery($strSQL);
        
        // If it's a user ticket, then the query is on the user's location,
        // Else if it's a system ticket, then the query is on the hardware's location,
        // Else if it's a peripheral ticket, then the query is on the peripheral's 
        //      system's location (unless it's spare, in which case it's on the peripheral's location),
        // Else it's a software ticket, and the query is on the software's system's 
        //      location (unless it's spare, in which case it's on the software's location)

        // aryLookupTable holds values for the displayed results...
        // ... so we do filtering using aryCidToLid as we're processing the resultset.
        // aryCidToLid maps a commentID to its actual location
        
        while ($row = mysql_fetch_array($result)) {
            switch($row['subjectType']) {
                case 'h': // Hardware
                    $aryLookupTable[$locationFieldIndex]['values'][$row['commentID']] = $aryLocationLookup[$aryHardwareLocation[$row['subjectID']]];                    
                    $aryCidToLid[$row['commentID']] = $aryHardwareLocation[$row['subjectID']];
                break;
                case 'p': // Peripheral
                    $aryLookupTable[$locationFieldIndex]['values'][$row['commentID']] = $aryLocationLookup[$aryPeripheralLocation[$row['subjectID']]];
                    $aryCidToLid[$row['commentID']] = $aryPeripheralLocation[$row['subjectID']];
                break;
                case 's': // Software
                    $aryLookupTable[$locationFieldIndex]['values'][$row['commentID']] = $aryLocationLookup[$arySoftwareLocation[$row['subjectID']]];
                    $aryCidToLid[$row['commentID']] = $arySoftwareLocation[$row['subjectID']];
                break;
                case 'u': // User
                    $aryLookupTable[$locationFieldIndex]['values'][$row['commentID']] = $aryLocationLookup[$aryUserLocation[$row['subjectID']]];
                    $aryCidToLid[$row['commentID']] = $aryUserLocation[$row['subjectID']];
                break;
            }
        }
        
        
        // ******
        // This is pretty nuts. We're basically jacking the code from commentLog and creating a list of commentIDs that the user is allowed to see.
        // Then when we create the final result set, we check each ID to see if it's allowed.
        // ******
        if ($_SESSION['stuckAtLocation']) {
            $allowedCommentIDs = array();
        
            $extraSQLSystem = "";
            $extraSQLUser = "";
            $extraSQLSubjectless = "";
            getExtraCommentSQLForStuckUsers($extraSQLSystem, $extraSQLUser, $extraSQLSubjectless);

            // System tickets
            $strSQL = getCommentSQLForSystems($extraSQLSystem);
            $result = dbquery($strSQL);
            while ($row = mysql_fetch_array($result)) {
                $allowedCommentIDs[$row['commentID']] = "present";
            }
            // User tickets
            $strSQL = getCommentSQLForUsers($extraSQLUser);
            $result = dbquery($strSQL);
            while ($row = mysql_fetch_array($result)) {
                $allowedCommentIDs[$row['commentID']] = "present";
            }
            // Subjectless tickets
            $strSQL = getCommentSQLForSubjectless($extraSQLSubjectless);
            $result = dbquery($strSQL);
            while ($row = mysql_fetch_array($result)) {
                $allowedCommentIDs[$row['commentID']] = "present";
            }
        }
        
        

          // Form an SQL query based on lookup array
          if ($locationFieldIndex == 0) {
              $strCommentIDField = "c.commentID, ";
              $fieldOffset = -1;
          } else {
              $strCommentIDField = "";
              $fieldOffset = 0;
          }
          $strSQL = "SELECT c.subjectType, " . $strCommentIDField;
          for ($i=1; $i<=$numToShow; $i++) {
              if (substr($aryLookupTable[$i]['sqlColumn'], 0, 4) == "DATE") {
                  $strSQL .= $aryLookupTable[$i]['sqlColumn'];
              } else {
                  $strSQL .= "c." . $aryLookupTable[$i]['sqlColumn'];
              }
              if ($i != $numToShow) {
                  $strSQL .= ", ";
              }
          }
          $strLeftJoin = "";
          
          $strSQL .= " FROM comments AS c " . $strLeftJoin . "WHERE ";

          // Process the WHERE clause from the drop downs

          if ($cboStatus != "") {
	          $strWhereClause .= listToOrString($cboStatus, "c.commentStatus", TRUE);
	      }
	      if ($cboPriority != "") {
	          $strWhereClause .= listToOrString($cboPriority, "c.commentPriority", TRUE);
          }
          if ($cboAssignedUserID != "") {
              $strWhereClause .= listToOrString($cboAssignedUserID, "c.assignedUserID");
          }
          if ($cboCategory != "") {
              $strWhereClause .= listToOrString($cboCategory, "c.categoryID");
          }
          if ($strTicketText != "") {
              $strWhereClause .= "c.commentText LIKE '%" . $strTicketText . "%' AND ";
          }
          if ($strDateCreated AND $cboDateCreated) {
              $strWhereClause .= " c.commentDate IS NOT NULL AND c.commentDate " . convertSign($cboDateCreated) . " " . dbDate($strDateCreated) . " AND " ;
          }
          $strWhereClause .= "c.accountID=" . $_SESSION['accountID'];
          $strSQL .= $strWhereClause;

          // Prepare arrays for handling the location matching

          if ($cboLocationID != "") {
              if (!isset($aryHardwareLocation)) {
                  $aryHardwareLocation = preloadHardwareLocation();
              }
          }

          // Load up the array containing the results from the SQL and sort
          $result = dbquery($strSQL);
          $i=1;
          $numResults = mysql_num_rows($result);
          if ($numResults == 0) {
              $strError = $progText756; # Handle the empty set
          } else {
              $numResults = 0;
              while ($row = mysql_fetch_array($result)) {
                  $bolAddToArray = TRUE;
                  if (count($cboLocationID != 0) && $cboLocationID[0]) {      
                      $bolAddToArray = FALSE;
                      for ($l = 0; $l < count($cboLocationID); $l++) {
                          if ($aryCidToLid[$row['commentID']] == $cboLocationID[$l]) {
                              $bolAddToArray = TRUE;
                          }
                      }
                  }
                  if ($bolAddToArray == TRUE) {
                      if ($_SESSION['stuckAtLocation']) {
                          $bolAddToArray = FALSE;
                          if ($allowedCommentIDs[$row['commentID']] == "present") {
                              $bolAddToArray = TRUE;
                          }
                      }
                  }
                  
                  if ($bolAddToArray == TRUE) {
                      $numResults++;
                      for ($j=1; $j<=$numToShow; $j++) {
                          if ($aryLookupTable[$j]['fromArray'] == FALSE) {                              
                              $aryResult[$i][$j] = $row[($j-$fieldOffset)];  # Get value from SQL
                          } else {  # Get value from lookup array
                              if ($aryLookupTable[$j]['subject'] == TRUE) {
                                  $aryResult[$i][$j] = $aryLookupTable[$j]['values'][$row['subjectType']][$row['subjectID']];
                              } else {
                                  $aryResult[$i][$j] = $aryLookupTable[$j]['values'][$row[($j-$fieldOffset)]];
                              }
                          }
                      }
                      $i++;
                  }
              }
          }
          if ($numResults == 0) {
              $strError = $progText756; # Handle the empty set after location strip;
          }
      }

      // Sort the multi dimensional result array by secondary index

      if (!$strError) {
          $arySortedResult = sortBySecondIndex($aryResult, $btnSort);
      }
  }
  // Date massage
  for ($i = 0; $i < count($arySortedResult); $i++) {
      for ($j = 1; $j <= $numToShow; $j++) {
          if ($aryLookupTable[$j]['date'] == TRUE) {
              $arySortedResult[$i][$j] = displayDate($arySortedResult[$i][$j]);
          }
      }
  }
              
  if (($exportMethod != "") AND (!$strError)) {
      $attributeList = "";
      $showList = "";
      if (1 <= $numAttributes) {
          $attributeList = $aryAttribute[1];
          $showList = $aryShow[1];
      }
      for ($i=2; $i <= $numAttributes; $i++) {
          $attributeList .= ",".$aryAttribute[$i];
          $showList .= ",".$aryShow[$i];
      }

      // ticketList[1] contains the list in aryAttribute seperated by comma.
      // ticketList[2] contains the list in aryShow seperated by comma.
      // ticketList[3] contains the sort position.

      setcookie("ticketList[1]", $attributeList, (time()+18000000)); # 5000 hour expiration
      setcookie("ticketList[2]", $showList, (time()+18000000)); # 5000 hour expiration
      setcookie("ticketList[3]", $btnSortPosition, (time()+18000000)); # 5000 hour expiration

      switch ($exportMethod) {
         case "$progText750": # Preview in HTML
?>
<HTML>
<HEAD>
<TITLE><? echo $progText1300; ?></TITLE>
<LINK REL=StyleSheet HREF="styles.css" TYPE="text/css">
</HEAD><BODY bgcolor="#FFFFFF" vlink='blue' alink='blue'>
<table border='0' cellpadding='0' cellspacing='0' width='<?=$windowWidth;?>'>
<tr><td><a href='reportTickets.php' class='larger'><b><?=$progText757;?></b></a><p>
<?
            generateHTMLTable($arySortedResult, $aryLookupTable, $numToShow, $numResults);
            die("</td></tr></table></BODY></HTML>");

         case "$progText751": # Export to Excel
            generateExcelFile($arySortedResult, $aryLookupTable, $numToShow);
            break;

         case "$progText752": # Export to Text File
            $delimiter = ":";
            generateTxtFile($arySortedResult, $aryLookupTable, $numToShow, $delimiter);
            break;
      }

  } else {

  writeHeader($progText1300);
  declareError(TRUE);

?>
  <form name="ReportForm" method="post" action="<? echo $_SERVER['PHP_SELF']?>">
  <table border='0' cellpadding='4' cellspacing='0'>
   <tr>
    <td width='150'><b><u><?=$progText721;?></b></u>:</td>
   </tr>
   <tr>
    <td width='150' valign='top'><?=$progText6;?>:</td>
    <td valign='top'>
        <select name='cboStatus[]' size='3' multiple>
          <option value='Open' <?=writeSelected("Open", $cboStatus);?>><?=$progText14;?></option>
          <option value='In Progress' <?=writeSelected("In Progress", $cboStatus);?>><?=$progText15;?></option>
          <option value='Resolved' <?=writeSelected("Resolved", $cboStatus);?>><?=$progText16;?></option>
        </select>
    </td>
   </tr>
   <tr>
    <td width='150' valign='top'><?=$progText7;?>:</td>
    <td valign='top'>
        <select name='cboPriority[]' size='3' multiple>
          <option value='1' <?=writeSelected($cboPriority, 1);?>>1</option>
          <option value='2' <?=writeSelected($cboPriority, 2);?>>2</option>
          <option value='3' <?=writeSelected($cboPriority, 3);?>>3</option>
          <option value='4' <?=writeSelected($cboPriority, 4);?>>4</option>
        </select>
    </td>
    </tr>
    </td>
   </tr>
    <tr>
    <td width='150' valign='top'><?=$progText138;?>:</td>
    <td valign='top'>
        <?
             $strSQL = "SELECT id, firstName, middleInit, lastName, userID FROM tblSecurity WHERE
               accountID=" . $_SESSION['accountID'] . " AND hidden='0' AND securityLevel < 2" . $stuckSQL . " ORDER BY lastName";
             $result = dbquery($strSQL);
             echo "<select name='cboAssignedUserID[]' size='3' multiple>\n";
             while ($row = mysql_fetch_array($result)) {
                 echo "   <OPTION VALUE=\"".$row['id']."\" ";
                 echo writeSelected($cboAssignedUserID, $row['id']);
                 # echo ">".buildName($row["firstName"], $row["middleInit"], $row["lastName"], 0)."</OPTION>\n";
                 echo ">".$row["userID"]."</OPTION>\n";
             }
             echo "</select>\n";

        # $aryExtraOptions = array("* ".$progText811." *");
        # echo buildUserSelect($assignedUserID, false, '', false, false, "AND securityLevel < 2", "cboAssignedUserID", $aryExtraOptions);
       ?>
    </td>
   </tr>   
   <tr>
    <td width='150' valign='top'><?=$progText960;?>:</td>
    <td valign='top'>
          <?
             $strSQL = "SELECT * FROM commentCategories WHERE accountID=" . $_SESSION['accountID'] . "";
             $result = dbquery($strSQL);
             If (mysql_num_rows($result) < 1) {
                 echo $progText437; # N/A
             } Else {
                 echo "<select name='cboCategory[]' size='3' multiple>\n";
                 while ($row = mysql_fetch_array($result)) {
                     echo "   <OPTION VALUE=\"".$row['categoryID']."\" ";
                     echo writeSelected($cboCategory, $row['categoryID']);
                     echo ">".$row['categoryName']."</OPTION>\n";
                 }
                 echo "</select>\n";
             }
	?>
    </td>
   <? if (!$_SESSION['stuckAtLocation']) { ?>
   <tr>
    <td width='150' valign='top'><?=$progText34;?>:</td>
    <td valign='top'>
      <?  buildLocationSelect($cboLocationID, FALSE, FALSE, FALSE, FALSE, 3);  ?>
    </td>
   </tr>
   <?php } ?>
   <tr>
    <td width='150' valign='top'><?= $progText1264; ?>:</td>
    <td valign='top'>
        <select name='cboDateCreated'>
            <option value="lt" <?=writeSelected($cboDateCreated, "lt");?>>&lt;</OPTION>
            <option value="gt" <?=writeSelected($cboDateCreated, "gt");?>>&gt;</OPTION>
            <option value="eq" <?=writeSelected($cboDateCreated, "eq");?>>=</OPTION>
        </select>
        <? buildDate('txtDateCreated', $txtDateCreated) ?>
    </td>
   </tr>
   <tr>
      <td width='150' valign='top'><?= $progText371; ?>:</td>
      <td valign='top'>
       <INPUT SIZE="40" MAXLENGTH="100" TYPE="Text" name="txtTicketText" VALUE="<? echo antiSlash($strTicketText); ?>">
     </td>
   </tr>
   <tr>
  </table><br>
  <table border='0' cellpadding='4' cellspacing='0'>
   <tr>
    <td colspan='4'><b><u><?=$progText728;?></u></b>:</td>
   </tr>
   <tr class='title'>
    <td width='50'><b><?=$progText734;?></b></td>
    <td><b><?=$progText735;?></b></td>
    <td><b><?=$progText736;?></b></td>
    <td><b><?=$progText737;?></b></td>
   </tr>
<?
  for ($i=1; $i<=$numAttributes; $i++) {

    // Figure and display appropriate on/off button

    echo "<tr><td><input type=\"checkbox\" name=\"btnShow[".$i."]\" value=\"1\" ".writeChecked($aryShow[$i],1)."></td>\n";

    // Display the attribute for the individual row

    echo "<td>".$aryAttribute[$i]." &nbsp;</td>\n";

    // Display the up/down buttons for the individual row

    echo "<td>";
    displayUpDownButton($i, $numAttributes);
    echo " &nbsp;</td>\n";

    // Display the sort buttons

    echo "<td><input type=\"radio\" name=\"btnSort\" value=\"".$i."\" ".writeChecked($i, $btnSort)."></td>\n";

?></tr><?
  }
?>
  </table>
<?
    createExportButtons();
?>
  <input type="hidden" name="numAttributes" value="<? echo $numAttributes; ?>">
  <input type="hidden" name="aryPassedShow" value="<? echo urlencode(serialize($aryShow));?>">
  <input type="hidden" name="aryPassedAttribute" value="<? echo urlencode(serialize($aryAttribute));?>">
  <input type="hidden" name="bolSubmit" value="TRUE">
  </form>
<?
  writeFooter();
  }
?>
