<?
  Include("Includes/global.inc.php");
  Include("Includes/reportFunctions.inc.php");
  checkPermissions(2, 1800);

  $numAttributes       = getOrPost('numAttributes');
  $aryPassedShow       = getOrPost('aryPassedShow');
  $aryPassedAttribute  = getOrPost('aryPassedAttribute');
  $btnSort             = getOrPost('btnSort');
  $btnUp               = getOrPost('btnUp');
  $btnDown             = getOrPost('btnDown');
  $btnShow             = getOrPost('btnShow');
  
  $softwareList = $_COOKIE['softwareList'];
  
  $exportMethod = getOrPost('exportMethod');

  $radSoftwareType = getOrPost('radSoftwareType');

  // If no submit button is pressed or first visit then
  // predefine arrays and set defaults

  if (getOrPost('bolSubmit') != "TRUE") {

      $numAttributes = 10;        # number of possible output details
      $btnSort = 1;              # default sort position
      $bolIgnoreUnlicensed = 0;  # by default, don't ignore unlicensed software
      $bolSoftwareType = "combobox";

      // Default values upon user's entry to the page -- set as appropriate
      // Check whether the output details are stored in cookie
      if (!$softwareList[1]) {
          $aryAttribute[1]  = $progText786; $aryShow[1]  = 1;
          $aryAttribute[2]  = $progText787; $aryShow[2]  = 1;
          $aryAttribute[3]  = $progText34;  $aryShow[3]  = 1;
          $aryAttribute[4]  = $progText35;  $aryShow[4]  = 1;
          $aryAttribute[5]  = $progText768; $aryShow[5]  = 0;
          $aryAttribute[6]  = $progText792; $aryShow[6]  = 0;
          $aryAttribute[7]  = $progText769; $aryShow[7]  = 0;
          $aryAttribute[8]  = $progText44;  $aryShow[8]  = 0;
          $aryAttribute[9]  = $progText740; $aryShow[9]  = 0;
          $aryAttribute[10] = $progText1226; $aryShow[10] = 0;

      } else {

          # explode returns an array starting from subscript 0. So a blank value is inserted
          # at the beginning so that $aryAttribute[0] and $aryShow[0] are blank.

          $softwareList[1] = ",".$softwareList[1];
          $softwareList[2] = ",".$softwareList[2];
          $aryAttribute = explode(",",$softwareList[1]);
          $aryShow = explode(",",$softwareList[2]);
          $btnSort = $softwareList[3];
      }
  } else {  # decode incoming arrays and check submit buttons

      $aryShow = unserialize(urldecode($aryPassedShow));
      $aryAttribute = unserialize(urldecode($aryPassedAttribute));
      $btnSortPosition = $btnSort;
      
      // Process incoming checkbox values for show/sort and adjust array

      updateCheckboxArray($numAttributes, $btnShow, $aryShow);

      // Check for an up/down button submitted from form
      
      upDownSubmitHandler($numAttributes, $btnUp, $btnDown, $btnSort, $aryShow, $aryAttribute);

      $cboVendorID           = getOrPost('cboVendorID');
      $cboSoftwareOwnership  = getOrPost('cboSoftwareOwnership');
      $cboSoftwareType       = getOrPost('cboSoftwareType');
      if ($_SESSION['stuckAtLocation']) {
          $cboLocationID = array($_SESSION['locationStatus']);
      } else {	  
          $cboLocationID       = getOrPost('cboLocationID');
      }
      $bolSoftwareType       = validateChoice(($progText767." ".$progText135), getOrPost('radSoftwareType')); 
      $strSoftwareType       = validateText($progText787, getOrPost('txtSoftwareType'), 1, 40, FALSE, FALSE);     
      $bolIgnoreUnlicensed   = validateChoice(($progText791." ".$progText135), getOrPost('radIgnoreUnlicensed'));        
  }

  // If an export button pressed -- process the report ELSE
  // regenerate the form with the updated values

  if ($exportMethod != "") {

      // Validate the sort selection

      $strError = validateSort($numAttributes, $btnSort, $aryShow);

      // Compress incoming arrays based on what attributes are shown

      if (!$strError) {
          $strError = compressAttributeArray($numAttributes, $numToShow, $btnSort, $aryShow, $aryAttribute, $aryCompAttribute);
      }

      // Preload DB items with recursive lookups into arrays

      if (!$strError) {

          for ($i=1; $i<=$numToShow; $i++) {

              switch ($aryCompAttribute[$i]) {

                case "$progText786":  # Software Ownership
                    loadLookupTable($i, $progText786, TRUE, 'sparePart', $aryLookupTable);
                    $aryLookupTable[$i]['values']['0'] = $progText789;
                    $aryLookupTable[$i]['values']['1'] = $progText377;
                break;

                case "$progText787":  # Software Type
                    loadLookupTable($i, $progText787, TRUE, 'softwareTraitID', $aryLookupTable);
                    $strSQL = "SELECT * FROM software_traits where accountID=" . $_SESSION['accountID'] . " AND hidden='0'";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $aryLookupTable[$i]['values'][$row['softwareTraitID']] = writePrettySoftwareName($row['visName'], $row['visVersion'], $row['visMaker']);
                    }
                break;

                case "$progText768":  # Assigned System
                    loadLookupTable($i, $progText768, TRUE, 'hardwareID', $aryLookupTable);
                    $strSQLx = "SELECT hardwareTypeID, visDescription, visManufacturer FROM hardware_types WHERE accountID=" . $_SESSION['accountID'] . "";
                    $resultx = dbquery($strSQLx);
                    while ($rowx = mysql_fetch_array($resultx)) {
                        $aryHardwareLookup[$rowx['hardwareTypeID']] = writePrettySystemName($rowx['visDescription'], $rowx['visManufacturer']);
                    }
                    $strSQLy = "SELECT hardwareID, hardwareTypeID FROM hardware WHERE accountID=" . $_SESSION['accountID'] . "";
                    $resulty = dbquery($strSQLy);
                    while ($rowy = mysql_fetch_array($resulty)) {
                        $aryLookupTable[$i]['values'][$rowy['hardwareID']] = $aryHardwareLookup[$rowy['hardwareTypeID']];
                    }
                break;
                             
                case "$progText769":  # Assigned User
                    loadLookupTable($i, $progText769, TRUE, 'softwareID', $aryLookupTable);
                    $strSQLx = "SELECT id, firstName, middleInit, lastName FROM tblSecurity WHERE hidden='0' AND accountID=" . $_SESSION['accountID'] . "";
                    $resultx = dbquery($strSQLx);
                    while ($rowx = mysql_fetch_array($resultx)) {
                        $aryUserLookup[$rowx['id']] = buildName($rowx['firstName'], $rowx['middleInit'], $rowx['lastName'], 0);
                    }
                    $strSQLy = "SELECT hardwareID, userID, sparePart FROM hardware WHERE accountID=" . $_SESSION['accountID'] . "";
                    $resulty = dbquery($strSQLy);
                    while ($rowy = mysql_fetch_array($resulty)) {
                        if ($rowy['sparePart'] == 0) {
                            $aryHardwareUser[$rowy['hardwareID']] = $aryUserLookup[$rowy['userID']];
                        } elseif ($rowy['sparePart'] == 1) {
                            $aryHardwareUser[$rowy['hardwareID']] = $progText377;
                        } elseif ($rowy['sparePart'] == 2) {
                            $aryHardwareUser[$rowy['hardwareID']] = $progText472;
                        } elseif ($rowy['sparePart'] == 3) {
                            $aryHardwareUser[$rowy['hardwareID']] = ucfirst($adminDefinedCategory);
                        }
                    }
                    $strSQLz = "SELECT softwareID, hardwareID, sparePart, locationID FROM software WHERE
                      accountID=" . $_SESSION['accountID'] . " AND hidden='0'";
                    $resultz = dbquery($strSQLz);
                    while ($rowz = mysql_fetch_array($resultz)) {
                        if ($rowz['sparePart'] != 1) {
                            $aryLookupTable[$i]['values'][$rowz['softwareID']] = $aryHardwareUser[$rowz['hardwareID']];
                        }
                    }
                break;

                case "$progText44":  # Serial
                    loadLookupTable($i, $progText44, FALSE, 'serial', $aryLookupTable);
                break;

                case "$progText34":  # Location
                    loadLookupTable($i, $progText34, TRUE, 'softwareID', $aryLookupTable);
                    if (!isset($aryLocationLookup)) {
                        $aryLocationLookup = preloadLocationLookup();
                    }
                    if (!isset($aryHardwareLocation)) {
                        $aryHardwareLocation = preloadHardwareLocation();
                    }
                    $strSQLz = "SELECT softwareID, hardwareID, sparePart, locationID FROM software WHERE
                      accountID=" . $_SESSION['accountID'] . " AND hidden='0'";
                    $resultz = dbquery($strSQLz);
                    while ($rowz = mysql_fetch_array($resultz)) {
                        if ($rowz['sparePart'] == 1) {
                            $aryLookupTable[$i]['values'][$rowz['softwareID']] = $aryLocationLookup[$rowz['locationID']];
                        } else {
                            $aryLookupTable[$i]['values'][$rowz['softwareID']] = $aryLocationLookup[$aryHardwareLocation[$rowz['hardwareID']]];
                        }
                    }
                break;

                case "$progText35":  # Room Name
                    loadLookupTable($i, $progText35, TRUE, 'softwareID', $aryLookupTable);
                    $aryHardwareRoom = preloadHardwareRoom();
                    $strSQLz = "SELECT softwareID, hardwareID, sparePart, roomName FROM software WHERE
                      accountID=" . $_SESSION['accountID'] . " AND hidden='0'";
                    $resultz = dbquery($strSQLz);
                    while ($rowz = mysql_fetch_array($resultz)) {
                        if ($rowz['sparePart'] == 1) {
                            $aryLookupTable[$i]['values'][$rowz['softwareID']] = $rowz['roomName'];
                        } else {
                            $aryLookupTable[$i]['values'][$rowz['softwareID']] = $aryHardwareRoom[$rowz['hardwareID']];
                        }
                    }
                break;
                
                case "$progText740":  # Host Name
                    loadLookupTable($i, $progText740, TRUE, 'hardwareID', $aryLookupTable);
                    $strSQLx = "SELECT hardwareID, hostname FROM hardware WHERE accountID=" . $_SESSION['accountID'] . "";
                    $resultx = dbquery($strSQLx);
                    while ($rowx = mysql_fetch_array($resultx)) {
                        $aryLookupTable[$i]['values'][$rowx['hardwareID']] = $rowx['hostname'];
                    }
                break;
                
                case "$progText1226":  # Vendor
                    loadLookupTable($i, $progText1226, TRUE, 'vendorID', $aryLookupTable);
                    $strSQLv = "SELECT * FROM vendors where accountID=" . $_SESSION['accountID'] . "";
                    $resultv = dbquery($strSQLv);
                    while ($rowv = mysql_fetch_array($resultv)) {
                        $aryLookupTable[$i]['values'][$rowv['vendorID']] = $rowv['vendorName'];
                    }
                break;
                
                case "$progText792": # System Asset Tag
                    loadLookupTable($i, $progText792, TRUE, 'hardwareID', $aryLookupTable);
                    $strSQLx = "SELECT hardwareID, assetTag FROM hardware WHERE accountID=" . $_SESSION['accountID'] . "";
                    $resultx = dbquery($strSQLx);
                    while ($rowx = mysql_fetch_array($resultx)) {
                        $aryLookupTable[$i]['values'][$rowx['hardwareID']] = $rowx['assetTag'];
                    }                    
                break;              
              }
          }

          // Form an SQL query based on lookup array

          $strSQL = "SELECT s.sparePart, s.locationID, s.hardwareID, ";
          for ($i=1; $i<=$numToShow; $i++) {
              $strSQL .= "s." . $aryLookupTable[$i]['sqlColumn'];
              if ($i != $numToShow) {
                  $strSQL .= ", ";
              }
          }

          $strLeftJoin = "";
          $strWhereClause = "";
          if ($bolIgnoreUnlicensed == 1) {
              $strLeftJoin = "LEFT JOIN software_traits_licenses AS stl ON s.softwareTraitID=stl.softwareTraitID ";
              $strWhereClause .= "stl.licenseID IS NOT NULL AND ";
          }
          
          if ($strSoftwareType != "" && $bolSoftwareType == 'textbox') {
              $strLeftJoin .= "LEFT JOIN software_traits AS st ON s.softwareTraitID=st.softwareTraitID "; 
              $strWhereClause .= "(st.visName LIKE '%$strSoftwareType%' OR st.visMaker LIKE '%$strSoftwareType%' OR st.visVersion LIKE '%$strSoftwareType%') AND ";
          }
          
          $strSQL .= " FROM software AS s " . $strLeftJoin . "WHERE ";

          // Process the WHERE clause from the drop downs

          if ($cboSoftwareOwnership != "") {
              $strWhereClause .= "s.sparePart='$cboSoftwareOwnership' AND ";
          }
          if ($cboSoftwareType != "" && $bolSoftwareType == 'combobox') {
              $strWhereClause .= listToOrString($cboSoftwareType, "s.softwareTraitID");
          }
          if ($cboVendorID != "") {
              $strWhereClause .= listToOrString($cboVendorID, "s.vendorID");
          }

          $strWhereClause .= "s.accountID=" . $_SESSION['accountID'] . " AND s.hidden='0'";
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
              while ($row = mysql_fetch_array($result)) {
                  $bolAddToArray = TRUE;
                  if (($cboLocationID != "") AND ($row['sparePart'] == 1)) {
                      if (array_search($row['locationID'], $cboLocationID) === FALSE) {
                          $bolAddToArray = FALSE;
                          $numResults--;
                      }
                  } elseif (($cboLocationID != "") AND ($row['sparePart'] ==0)) {
                      if (array_search($aryHardwareLocation[$row['hardwareID']], $cboLocationID) === FALSE) {
                          $bolAddToArray = FALSE;
                          $numResults--;
                      }
                  }
                  if ($bolAddToArray == TRUE) {
                      for ($j=1; $j<=$numToShow; $j++) {
                          if ($aryLookupTable[$j]['fromArray'] == FALSE) {
                              $aryResult[$i][$j] = $row[($j+2)];  # Get value from SQL
                          } else {  # Get value from lookup array
                              $aryResult[$i][$j] = $aryLookupTable[$j]['values'][$row[($j+2)]];
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
          $showList.=",".$aryShow[$i];
      }

      // softwareList[1] contains the list in aryAttribute seperated by comma.
      // softwareList[2] contains the list in aryShow seperated by comma.
      // softwareList[3] contains the sort position.

      setcookie("softwareList[1]", $attributeList, (time()+18000000)); # 5000 hour expiration
      setcookie("softwareList[2]", $showList, (time()+18000000)); # 5000 hour expiration
      setcookie("softwareList[3]", $btnSortPosition, (time()+18000000)); # 5000 hour expiration

      switch ($exportMethod) {
         case "$progText750": # Preview in HTML
?>
<HTML>
<HEAD>
<TITLE><? echo $progText785; ?></TITLE>
<LINK REL=StyleSheet HREF="styles.css" TYPE="text/css">
</HEAD><BODY bgcolor="#FFFFFF" vlink='blue' alink='blue'>
<table border='0' cellpadding='0' cellspacing='0' width='<?=$windowWidth;?>'>
<tr><td><a href='reportSoftware.php' class='larger'><b><?=$progText757;?></b></a><p>
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

  writeHeader($progText785);
  declareError(TRUE);

?>
  <form name="ReportForm" method="post" action="<? echo $_SERVER['PHP_SELF']?>">
  <table border='0' cellpadding='4' cellspacing='0'>
   <tr>
    <td width='175'><b><u><?=$progText721;?></b></u>:</td>
   </tr>
   <tr>
    <td width='175'><?=$progText786;?>:</td>
    <td>
     <select size="1" name="cboSoftwareOwnership">
       <option value=''>&nbsp;</option>
       <option value='0' <? echo writeSelected($cboSoftwareOwnership, "0"); ?>><?=$progText789;?></option>
       <option value='1' <? echo writeSelected($cboSoftwareOwnership, "1"); ?>><?=$progText377;?></option>
     </select>
    </td>
   </tr>
   <tr>
    <td width='175' valign='top'><?=$progText787;?>:</td>
    <td>
    <font class="soft_instructions"><?= $progText777;?></font>
    <br/>
    <table cellspacing=0 cellpadding=0><tr><td valign="top">
    <input type='radio' name='radSoftwareType' value='combobox' <?=writeChecked("combobox", $bolSoftwareType);?> onclick="ReportForm.txtSoftwareType.value='';"> 
    </td><td>
     <select multiple size="3" name="cboSoftwareType[]" onchange="ReportForm.radSoftwareType[0].checked=true; ReportForm.txtSoftwareType.value='';">
       <?
       // Get all software types for the drop down menu
       $strSQLx = "SELECT * FROM software_traits WHERE accountID=" . $_SESSION['accountID'] . " AND hidden='0'
         ORDER BY visName ASC, visVersion ASC";
       $resultx = dbquery($strSQLx);
       while ($rowx = mysql_fetch_array($resultx)) {
         echo "   <option value=\"".$rowx['softwareTraitID']."\"";
         echo writeSelected($cboSoftwareType, $rowx['softwareTraitID']).">";
         echo writePrettySoftwareName($rowx['visName'], $rowx['visVersion'], $rowx['visMaker']);
         echo "</option>\n";
       }
       ?>
     </select>
    </td></tr></table>
    </td>
   </tr>
   <tr>
     <td width='150'></td>
     <td valign="top">
      <font class="soft_instructions"><?= $progText778; ?></font>
      <br/>
      <table cellspacing=0 cellpadding=0><tr><td valign="top">
      <input type='radio' name='radSoftwareType' value='textbox' <?=writeChecked("textbox", $bolSoftwareType);?> onclick="ReportForm.elements[2].selectedIndex=-1;"> 
      </td><td>
      
      <input type='text' name='txtSoftwareType' value='<?= $strSoftwareType ?>' size='60' maxlength='60' onkeypress="ReportForm.radSoftwareType[1].checked=true; ReportForm.elements[2].selectedIndex=-1;"></td>
      </td></tr></table>
   </tr>
   <? if (!$_SESSION['stuckAtLocation']) { ?>
   <tr>
    <td width='150' valign='top'><?=$progText34;?>:</td>
    <td>
      <?  buildLocationSelect($cboLocationID, FALSE, FALSE, FALSE, FALSE, 3);  ?>
    </td>
   </tr>
   <?php } ?>
   <tr>
    <td width='175' valign='top'><?=$progText1226;?>:</td>
    <td>
      <?  buildVendorSelect($cboVendorID, FALSE, FALSE, FALSE, FALSE, 3);  ?>
    </td>
   </tr>
   <tr>
    <td width='175'><?=$progText791;?>:</td>
    <td>
      <INPUT TYPE="radio" NAME="radIgnoreUnlicensed" VALUE="1" <?=writeChecked("1", $bolIgnoreUnlicensed);?>> <?=$progText140;?>
      <INPUT TYPE="radio" NAME="radIgnoreUnlicensed" VALUE="0" <?=writeChecked("0", $bolIgnoreUnlicensed);?>> <?=$progText141;?>
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
