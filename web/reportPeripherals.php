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
  
  $peripheralList = $_COOKIE['peripheralList'];
  
  $exportMethod = getOrPost('exportMethod');
  
  $radPeripheralType = getOrPost('radPeripheralType');

  // If no submit button is pressed or first visit then
  // predefine arrays and set defaults
  if (getOrPost('bolSubmit') != "TRUE") {

      $numAttributes = 13;  # number of possible output details
      $btnSort = 1;        # default sort position
      $bolPeripheralType = "combobox";

      // Default values upon user's entry to the page -- set as appropriate
      // Check whether the output details are stored in cookie
      if (!$peripheralList[1]) {
          $aryAttribute[1] = $progText766; $aryShow[1] = 1;
          $aryAttribute[2] = $progText767; $aryShow[2] = 1;
          $aryAttribute[3] = $progText768; $aryShow[3] = 1;
          $aryAttribute[4] = $progText769; $aryShow[4] = 1;
          $aryAttribute[5] = $progText44;  $aryShow[5] = 0;
          $aryAttribute[6] = $progText222; $aryShow[6] = 1;
          $aryAttribute[7] = $progText34;  $aryShow[7] = 1;
          $aryAttribute[8] = $progText35;  $aryShow[8] = 1;
          $aryAttribute[9] = $progText740; $aryShow[9] = 0;
          $aryAttribute[10] = $progText792; $aryShow[10] = 0;
          $aryAttribute[11] = $progText1226; $aryShow[11] = 0;
          $aryAttribute[12] = $progText421; $aryShow[12] = 0;
          $aryAttribute[13] = $progText424; $aryShow[13] = 0;
      } else {
          # explode returns an array starting from subscript 0. So a blank value is inserted
          # at the beginning so that $aryAttribute[0] and $aryShow[0] are blank.
          $peripheralList[1] = ",".$peripheralList[1];
          $peripheralList[2] = ",".$peripheralList[2];
          $aryAttribute = explode(",",$peripheralList[1]);
          $aryShow = explode(",",$peripheralList[2]);
          $btnSort = $peripheralList[3];
      }
  } else {  # decode incoming arrays and check submit buttons

      $aryShow = unserialize(urldecode($aryPassedShow));
      $aryAttribute = unserialize(urldecode($aryPassedAttribute));
      $btnSortPosition = $btnSort;

      // Process incoming checkbox values for show/sort and adjust array

      updateCheckboxArray($numAttributes, $btnShow, $aryShow);

      // Check for an up/down button submitted from form
      upDownSubmitHandler($numAttributes, $btnUp, $btnDown, $btnSort, $aryShow, $aryAttribute); // Validate purchase date and purchase price 
      
      $cboPeripheralClass      = getOrPost('cboPeripheralClass');
      $cboVendorID             = getOrPost('cboVendorID');
      $cboPeripheralOwnership  = getOrPost('cboPeripheralOwnership');
      $cboPeripheralType       = getOrPost('cboPeripheralType');
      if ($_SESSION['stuckAtLocation']) {
          $cboLocationID = array($_SESSION['locationStatus']);
      } else {	  
          $cboLocationID       = getOrPost('cboLocationID');
      }
      $strPurchaseDate         = validateDate($progText421, getOrPost('txtPurchaseDate'), 1900, (date("Y")+1), FALSE); 
      $cboPurchaseDate         = cleanComparatorInput(getOrPost('cboPurchaseDate')); 
      $strPurchasePrice        = validateExactNumber($progText424, getOrPost('txtPurchasePrice'), 0, 99999999, FALSE, 2); 
      $cboPurchasePrice        = cleanComparatorInput(getOrPost('cboPurchasePrice')); 
      $bolPeripheralType       = validateChoice(($progText767." ".$progText135), getOrPost('radPeripheralType')); 
      $strPeripheralType       = validateText($progText382, getOrPost('txtPeripheralType'), 1, 40, FALSE, FALSE); 
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

      if (!$strError) {

          for ($i=1; $i<=$numToShow; $i++) {

              switch ($aryCompAttribute[$i]) {

                case "$progText766":  # Peripheral Ownership
                    loadLookupTable($i, $progText766, TRUE, 'sparePart', $aryLookupTable);
                    $aryLookupTable[$i]['values']['0'] = $progText770;
                    $aryLookupTable[$i]['values']['1'] = $progText377;
                break;

                case "$progText767":  # Peripheral Type
                    loadLookupTable($i, $progText767, TRUE, 'peripheralTraitID', $aryLookupTable);
                    $strSQL = "SELECT * FROM peripheral_traits where accountID=" . $_SESSION['accountID'] . " AND hidden='0'";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $aryLookupTable[$i]['values'][$row['peripheralTraitID']] = writePrettyPeripheralName($row['visDescription'], $row['visModel'], $row['visManufacturer']);
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
                    loadLookupTable($i, $progText769, TRUE, 'peripheralID', $aryLookupTable);
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
                    $strSQLz = "SELECT peripheralID, hardwareID, sparePart, locationID FROM peripherals
                      WHERE accountID=" . $_SESSION['accountID'] . " AND hidden='0'";
                    $resultz = dbquery($strSQLz);
                    while ($rowz = mysql_fetch_array($resultz)) {
                        if ($rowz['sparePart'] != 1) {
                            $aryLookupTable[$i]['values'][$rowz['peripheralID']] = $aryHardwareUser[$rowz['hardwareID']];
                        }
                    }
                break;

                case "$progText44":  # Serial
                    loadLookupTable($i, $progText44, FALSE, 'serial', $aryLookupTable);
                break;

                case "$progText222":  # Status
                    loadLookupTable($i, $progText222, TRUE, 'peripheralStatus', $aryLookupTable);
                    $aryLookupTable[$i]['values']['n'] = $progText414;
                    $aryLookupTable[$i]['values']['i'] = $progText415;
                    $aryLookupTable[$i]['values']['w'] = $progText413;
                    $aryLookupTable[$i]['values']['d'] = $progText413A;
                break;

                case "$progText34":  # Location
                    loadLookupTable($i, $progText34, TRUE, 'peripheralID', $aryLookupTable);
                    if (!isset($aryLocationLookup)) {
                        $aryLocationLookup = preloadLocationLookup();
                    }
                    if (!isset($aryHardwareLocation)) {
                        $aryHardwareLocation = preloadHardwareLocation();
                    }
                    $strSQLz = "SELECT peripheralID, hardwareID, sparePart, locationID FROM peripherals WHERE
                      accountID=" . $_SESSION['accountID'] . " AND hidden='0'";
                    $resultz = dbquery($strSQLz);
                    while ($rowz = mysql_fetch_array($resultz)) {
                        if ($rowz['sparePart'] == 1) {
                            $aryLookupTable[$i]['values'][$rowz['peripheralID']] = $aryLocationLookup[$rowz['locationID']];
                        } else {
                            $aryLookupTable[$i]['values'][$rowz['peripheralID']] = $aryLocationLookup[$aryHardwareLocation[$rowz['hardwareID']]];
                        }
                    }
                break;

                case "$progText35":  # Room Name
                    loadLookupTable($i, $progText35, TRUE, 'peripheralID', $aryLookupTable);
                    $aryHardwareRoom = preloadHardwareRoom();
                    $strSQLz = "SELECT peripheralID, hardwareID, sparePart, roomName FROM peripherals WHERE
                      accountID=" . $_SESSION['accountID'] . " AND hidden='0'";
                    $resultz = dbquery($strSQLz);
                    while ($rowz = mysql_fetch_array($resultz)) {
                        if ($rowz['sparePart'] == 1) {
                            $aryLookupTable[$i]['values'][$rowz['peripheralID']] = $rowz['roomName'];
                        } else {
                            $aryLookupTable[$i]['values'][$rowz['peripheralID']] = $aryHardwareRoom[$rowz['hardwareID']];
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
                
                case "$progText421": # Purchase Date
                    loadLookupTable($i, $progText421, FALSE, 'purchaseDate', $aryLookupTable);
                    $aryLookupTable[$i]['date'] = TRUE;
                break;
                
                case "$progText424": # Purchase Price
                    loadLookupTable($i, $progText424, FALSE, 'purchasePrice', $aryLookupTable);
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

          $strSQL = "SELECT p.sparePart, p.locationID, p.hardwareID, ";
          for ($i=1; $i<=$numToShow; $i++) {
              $strSQL .= "p." . $aryLookupTable[$i]['sqlColumn'];
              if ($i != $numToShow) {
                  $strSQL .= ", ";
              }
          }
          $strLeftJoin = "";
          if ($cboPeripheralClass) {
              $strLeftJoin = "LEFT JOIN peripheral_traits AS pt ON p.peripheralTraitID=pt.peripheralTraitID "; 
              $strWhereClause .= "pt.visTypeClass='$cboPeripheralClass' AND ";
          }
          if ($strPeripheralType != "" && $bolPeripheralType == 'textbox') {
              if ($strLeftJoin == "") {
                  $strLeftJoin = "LEFT JOIN peripheral_traits AS pt ON p.peripheralTraitID=pt.peripheralTraitID "; 
              }
              $strWhereClause .= "(pt.visDescription LIKE '%$strPeripheralType%' OR pt.visModel LIKE '%$strPeripheralType%' OR pt.visManufacturer LIKE '%$strPeripheralType%') AND ";
          }
          
          $strSQL .= " FROM peripherals AS p " . $strLeftJoin . "WHERE ";

          // Process the WHERE clause from the drop downs

          if ($cboPeripheralOwnership != "") {
              $strWhereClause .= "p.sparePart='$cboPeripheralOwnership' AND ";
          }
          if ($cboPeripheralType != "" && $bolPeripheralType == 'combobox') {
              // OR together all the peripheral types we want to match
              $strWhereClause .= listToOrString($cboPeripheralType, "p.peripheralTraitID");
          } 
          if ($cboVendorID != "") {
              $strWhereClause .= listToOrString($cboVendorID, "p.vendorID");
          }
          if ($strPurchaseDate AND $cboPurchaseDate) {
              $strWhereClause .= " p.purchaseDate IS NOT NULL AND p.purchaseDate " . convertSign($cboPurchaseDate) . " " . dbDate($strPurchaseDate) . " AND " ;
          }
          if ($strPurchasePrice AND $cboPurchasePrice) {
              $strWhereClause .= " p.purchasePrice IS NOT NULL AND p.purchasePrice " . convertSign($cboPurchasePrice) . " " . $strPurchasePrice . " AND " ;
          }

          $strWhereClause .= "p.accountID=" . $_SESSION['accountID'] . " AND p.hidden='0'";
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
          $showList .= ",".$aryShow[$i];
      }

      // peripheralList[1] contains the list in aryAttribute seperated by comma.
      // peripheralList[2] contains the list in aryShow seperated by comma.
      // peripheralList[3] contains the sort position.

      setcookie("peripheralList[1]", $attributeList, (time()+18000000)); # 5000 hour expiration
      setcookie("peripheralList[2]", $showList, (time()+18000000)); # 5000 hour expiration
      setcookie("peripheralList[3]", $btnSortPosition, (time()+18000000)); # 5000 hour expiration

      switch ($exportMethod) {
         case "$progText750": # Preview in HTML
?>
<HTML>
<HEAD>
<TITLE><? echo $progText765; ?></TITLE>
<LINK REL=StyleSheet HREF="styles.css" TYPE="text/css">
</HEAD><BODY bgcolor="#FFFFFF" vlink='blue' alink='blue'>
<table border='0' cellpadding='0' cellspacing='0' width='<?=$windowWidth;?>'>
<tr><td><a href='reportPeripherals.php' class='larger'><b><?=$progText757;?></b></a><p>
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

  writeHeader($progText765);
  declareError(TRUE);

?>
  <form name="ReportForm" method="post" action="<? echo $_SERVER['PHP_SELF']?>">
  <table border='0' cellpadding='4' cellspacing='0'>
   <tr>
    <td width='150'><b><u><?=$progText721;?></b></u>:</td>
   </tr>
   <tr>
    <td width='150'><?=$progText766;?>:</td>
    <td>
     <select size="1" name="cboPeripheralOwnership">
       <option value=''>&nbsp;</option>
       <option value='0' <? echo writeSelected($cboPeripheralOwnership, "0"); ?>><?=$progText770;?></option>
       <option value='1' <? echo writeSelected($cboPeripheralOwnership, "1"); ?>><?=$progText377;?></option>
     </select>
    </td>
   </tr>
   <tr>
    <td width='150' valign='top'><?=$progText767;?>:</td>
    <td valign="top">
    <font class="soft_instructions"><?= $progText777;?></font>
    <br/>
    <table cellspacing=0 cellpadding=0><tr><td valign="top">
    <input type='radio' name='radPeripheralType' value='combobox' <?=writeChecked("combobox", $bolPeripheralType);?> onclick="ReportForm.txtPeripheralType.value='';"> 
    </td><td>
     <select multiple size="3" name="cboPeripheralType[]" onchange="ReportForm.radPeripheralType[0].checked=true; ReportForm.txtPeripheralType.value='';">
       <?
       // Get all peripheral types for the drop down menu
       $strSQLx = "SELECT * FROM peripheral_traits WHERE accountID=" . $_SESSION['accountID'] . " AND hidden='0'
         ORDER BY visDescription ASC";
       $resultx = dbquery($strSQLx);
       while ($rowx = mysql_fetch_array($resultx)) {
         echo "   <option value=\"".$rowx['peripheralTraitID']."\"";
         echo writeSelected($cboPeripheralType, $rowx['peripheralTraitID']).">";
         echo writePrettyPeripheralName($rowx['visDescription'], $rowx['visModel'], $rowx['visManufacturer']);
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
      <input type='radio' name='radPeripheralType' value='textbox' <?=writeChecked("textbox", $bolPeripheralType);?> onclick="ReportForm.elements[2].selectedIndex=-1;"> 
      </td><td>
      
      <input type='text' name='txtPeripheralType' value='<?= $strPeripheralType ?>' size='60' maxlength='60' onkeypress="ReportForm.radPeripheralType[1].checked=true; ReportForm.elements[2].selectedIndex=-1;"></td>
      </td></tr></table>
   </tr>
    <tr>
    <td width='150'><?=$progText776;?>:</td>
    <td>
      <?  buildPeripheralClassSelect($txtPeripheralClass);  ?>
    </td>
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
    <td width='150' valign='top'><?=$progText1226;?>:</td>
    <td>
      <?  buildVendorSelect($cboVendorID, FALSE, FALSE, FALSE, FALSE, 3);  ?>
    </td>
   </tr>
   <tr>
    <td width='150'><?=$progText421;?>:</td>
    <td>
        <select name='cboPurchaseDate'>
            <option value="lt" <?=writeSelected($cboPurchaseDate, "lt");?>>&lt;</OPTION>
            <option value="gt" <?=writeSelected($cboPurchaseDate, "gt");?>>&gt;</OPTION>
            <option value="eq" <?=writeSelected($cboPurchaseDate, "eq");?>>=</OPTION>
        </select>
        <? buildDate('txtPurchaseDate', $strPurchaseDate) ?>
    </td>
   </tr>
   <tr>
      <td width='150'><?=$progText424;?>:</td>
      <td>
        <select name='cboPurchasePrice'>
            <option value="lt" <?=writeSelected($cboPurchasePrice, "lt");?>>&lt;</OPTION>
            <option value="gt" <?=writeSelected($cboPurchasePrice, "gt");?>>&gt;</OPTION>
            <option value="eq" <?=writeSelected($cboPurchasePrice, "eq");?>>=</OPTION>
        </select>
       <INPUT SIZE="10" MAXLENGTH="11" TYPE="Text" name="txtPurchasePrice" VALUE="<? echo antiSlash($strPurchasePrice); ?>">
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
