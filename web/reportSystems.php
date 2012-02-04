<?
  Include("Includes/global.inc.php");
  Include("Includes/reportFunctions.inc.php");
  checkPermissions(2, 1800);

  $nicmac1 = $progText423." 1";
  $nicmac2 = $progText423." 2";

  $numAttributes       = getOrPost('numAttributes');
  $aryPassedShow       = getOrPost('aryPassedShow');
  $aryPassedAttribute  = getOrPost('aryPassedAttribute');
  $btnSort             = getOrPost('btnSort');
  $btnUp               = getOrPost('btnUp');
  $btnDown             = getOrPost('btnDown');
  $btnShow             = getOrPost('btnShow');

  $systemList = $_COOKIE['systemList'];
  
  $exportMethod = getOrPost('exportMethod');
  
  // If no submit button is pressed or first visit then
  // predefine arrays and set defaults
  if (getOrPost('bolSubmit') != "TRUE") {

      if ($extraSystemField == "")
        $numAttributes = 21;  # number of possible output details
      else
        $numAttributes = 22;
      $btnSort = 1;        # default sort position

      // Default values upon user's entry to the page -- set as appropriate
      // Check whether the output details are stored in cookie
      if (!$systemList[1]) {
          $aryAttribute[1] = $progText743; $aryShow[1] = 1;
          $aryAttribute[2] = $progText722; $aryShow[2] = 0;
          $aryAttribute[3] = $progText33;  $aryShow[3] = 1;
          $aryAttribute[4] = $progText44;  $aryShow[4] = 1;
          $aryAttribute[5] = $progText45;  $aryShow[5] = 0;
          $aryAttribute[6] = $progText740; $aryShow[6] = 1;
          $aryAttribute[7] = $progText222; $aryShow[7] = 0;
          $aryAttribute[8] = $progText420; $aryShow[8] = 1;
          $aryAttribute[9] = $progText34;  $aryShow[9] = 1;
          $aryAttribute[10] = $progText35;  $aryShow[10] = 1;
          $aryAttribute[11] = $progText421; $aryShow[11] = 0;
          $aryAttribute[12] = $progText422; $aryShow[12] = 0;
          $aryAttribute[13] = $progText424; $aryShow[13] = 0;
          $aryAttribute[14] = $progText50; $aryShow[14] = 0;
          $aryAttribute[15] = $nicmac1; $aryShow[15] = 0;
          $aryAttribute[16] = $nicmac2; $aryShow[16] = 0;
          $aryAttribute[17] = $progText1226; $aryShow[17] = 0;
          $aryAttribute[18] = $progText761; $aryShow[18] = 0;
          $aryAttribute[19] = $progText762; $aryShow[19] = 0;
          $aryAttribute[20] = $progText763; $aryShow[20] = 0;
          $aryAttribute[21] = $progText46; $aryShow[21] = 0;
          if ($extraSystemField != "")
          {
             $aryAttribute[22] = $extraSystemField;
             $aryShow[22] = 0;
          }
      } else {
          # explode returns an array starting from subscript 0. So a blank value is inserted
          # at the beginning so that $aryAttribute[0] and $aryShow[0] are blank.
          $systemList[1] = ",".$systemList[1];
          $systemList[2] = ",".$systemList[2];
          $aryAttribute = explode(",",$systemList[1]);
          $aryShow = explode(",",$systemList[2]);
          $btnSort = $systemList[3];
      }

  } else {  # decode incoming arrays and check submit buttons

      $aryShow = unserialize(urldecode($aryPassedShow));
      $aryAttribute = unserialize(urldecode($aryPassedAttribute));
      $btnSortPosition = $btnSort;

      // Process incoming checkbox values for show/sort and adjust array

      updateCheckboxArray($numAttributes, $btnShow, $aryShow);

      // Check for an up/down button submitted from form

      upDownSubmitHandler($numAttributes, $btnUp, $btnDown, $btnSort, $aryShow, $aryAttribute);

      $cboVendorID         = getOrPost('cboVendorID');
      $cboSystemOwnership  = getOrPost('cboSystemOwnership');
      $cboSystemType       = getOrPost('cboSystemType');
      if ($_SESSION['stuckAtLocation']) {
          $cboLocationID = array($_SESSION['locationStatus']);
      } else {	  
          $cboLocationID       = getOrPost('cboLocationID');
      }
      $strPurchaseDate     = validateDate($progText421, getOrPost('txtPurchaseDate'), 1900, (date("Y")+1), FALSE);
	  $cboPurchaseDate     = cleanComparatorInput(getOrPost('cboPurchaseDate'));
	  $strWarrantyDate     = validateDate($progText422, getOrPost('txtWarrantyDate'), 1900, (date("Y")+90), FALSE);
      $cboWarrantyDate     = cleanComparatorInput(getOrPost('cboWarrantyDate'));
	  $strAgentDate        = validateDate($progText50, getOrPost('txtAgentDate'), 1900, (date("Y")+90), FALSE);
      $cboAgentDate        = cleanComparatorInput(getOrPost('cboAgentDate'));
	  $strPurchasePrice    = validateExactNumber($progText424, getOrPost('txtPurchasePrice'), 0, 99999999, FALSE, 2);
	  $cboPurchasePrice    = cleanComparatorInput(getOrPost('cboPurchasePrice'));
      $strSoftwareType     = validateText($progText380, getOrPost('txtSoftwareType'), 2, 70, FALSE, FALSE);
      $strPeripheralType   = validateText($progText382, getOrPost('txtPeripheralType'), 2, 70, FALSE, FALSE);
  }
  $btnShow = $aryShow;

  // If an export button pressed -- process the report ELSE
  // regenerate the form with the updated values

  if (($exportMethod != "") AND (!$strError)) {
       if (getOrPost('includeGraph')== "1")
       { 
			//force display 
			$aryShow[2] = 1;
			$aryShow[7] = 1; 
		}

      // Validate the sort selection

      $strError = validateSort($numAttributes, $btnSort, $aryShow);

      // Compress incoming arrays based on what attributes are shown

      if (!$strError) {
          $strError = compressAttributeArray($numAttributes, $numToShow, $btnSort, $aryShow, $aryAttribute, $aryCompAttribute);
      }

      // Preload DB items with recursive (having to reference more than one SQL
      // table) lookups into arrays and construct a "lookup" array for retrieval of IDs

      if (!$strError) {

          for ($i=1; $i<=$numToShow; $i++) {

              switch ($aryCompAttribute[$i]) {

                case "$progText743":  # User Name
                    loadLookupTable($i, $progText743, TRUE, 'h.userID', $aryLookupTable);
                    $strSQL = "SELECT id, firstName, middleInit, lastName FROM tblSecurity WHERE hidden='0' AND accountID=" . $_SESSION['accountID'];
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $strUserName = buildName($row['firstName'], $row['middleInit'], $row['lastName'], 0);
                        $aryLookupTable[$i]['values'][$row['id']] = $strUserName;
                    }
                break;

                case "$progText722":  # System Ownership
                    loadLookupTable($i, $progText722, TRUE, 'h.sparePart', $aryLookupTable);
                    $aryLookupTable[$i]['values']['0'] = $progText725;
                    $aryLookupTable[$i]['values']['1'] = $progText377;
                    $aryLookupTable[$i]['values']['2'] = $progText472;
                    $aryLookupTable[$i]['values']['3'] = ucfirst($adminDefinedCategory);
                break;

                case "$progText33":  # System Type
                    loadLookupTable($i, $progText33, TRUE, 'h.hardwareTypeID', $aryLookupTable);
                    $strSQL = "SELECT hardwareTypeID, visDescription, visManufacturer FROM hardware_types where accountID=" . $_SESSION['accountID'];
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $aryLookupTable[$i]['values'][$row['hardwareTypeID']] = writePrettySystemName($row['visDescription'], $row['visManufacturer']);
                    }

                break;

                case "$progText44":  # Serial
                    loadLookupTable($i, $progText44, FALSE, 'h.serial', $aryLookupTable);
                break;

                case "$progText45":  # IP Address
                    loadLookupTable($i, $progText45, FALSE, 'h.ipAddress', $aryLookupTable);
                break;

                case "$progText740":  # Hostname
                    loadLookupTable($i, $progText740, FALSE, 'h.hostname', $aryLookupTable);
                break;

                case "$progText222":  # Status
                    loadLookupTable($i, $progText222, TRUE, 'h.hardwareStatus', $aryLookupTable);
                    $aryLookupTable[$i]['values']['n'] = $progText414;
                    $aryLookupTable[$i]['values']['i'] = $progText415;
                    $aryLookupTable[$i]['values']['w'] = $progText413;
                break;

                case "$progText420":  # Asset Tag 
                    loadLookupTable($i, $progText420, FALSE, 'h.assetTag', $aryLookupTable);
                break;

                case "$progText34":  # Location
                    loadLookupTable($i, $progText34, TRUE, 'h.locationID', $aryLookupTable);
                    $strSQL = "SELECT locationID, locationName FROM locations where accountID=" . $_SESSION['accountID'] . "";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $aryLookupTable[$i]['values'][$row['locationID']] = $row['locationName'];
                    }
                break;

                case "$progText35":  # Room Name
                    loadLookupTable($i, $progText35, FALSE, 'h.roomName', $aryLookupTable);
                break;
                
                case "$progText421": # Purchase Date
                    loadLookupTable($i, $progText421, FALSE, 'h.purchaseDate', $aryLookupTable);
                    $aryLookupTable[$i]['date'] = TRUE;
                break;
                
                case "$progText422": # Warrantied Until
                    loadLookupTable($i, $progText422, FALSE, 'h.warrantyEndDate', $aryLookupTable);
                    $aryLookupTable[$i]['date'] = TRUE;
                break;
                
                case "$progText424": # Purchase Price
                    loadLookupTable($i, $progText424, FALSE, 'h.purchasePrice', $aryLookupTable);
                break;
                
                case "$progText50": # Last Agent Update
                    loadLookupTable($i, $progText50, FALSE, 'h.lastAgentUpdate', $aryLookupTable);
                    $aryLookupTable[$i]['date'] = TRUE;
                break;
                
                case "$nicmac1": # Nic Mac 1
                    loadLookupTable($i, $nicmac1, FALSE, 'h.nicMac1', $aryLookupTable);
                break;
                
                case "$nicmac2": # Nic Mac 2
                    loadLookupTable($i, $nicmac2, FALSE, 'h.nicMac2', $aryLookupTable);
                break;

                case "$progText1226":  # Vendor
                    loadLookupTable($i, $progText1226, TRUE, 'h.vendorID', $aryLookupTable);
                    $strSQL = "SELECT vendorID, vendorName FROM vendors where accountID=" . $_SESSION['accountID'] . "";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $aryLookupTable[$i]['values'][$row['vendorID']] = $row['vendorName'];
                    }
                break;
                
                case "$progText761": # Total RAM
                    loadLookupTable($i, $progText761, TRUE, 'h.hardwareID', $aryLookupTable);
                    $strSQL = "SELECT visDescription, hardwareID FROM peripherals LEFT JOIN peripheral_traits ON peripherals.peripheralTraitID=peripheral_traits.peripheralTraitID WHERE peripheral_traits.visTypeClass='RAM'";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $ramAry = split(" ", $row['visDescription']);
                        // Factor out, perhaps?
                        if ($ramAry[1] != "MB" || !is_numeric($ramAry[0])) {
                            //die("Unexpected RAM description");
                            continue;
                        }                        
                        if ($aryLookupTable[$i]['values'][$row['hardwareID']] != "") {
                            $existing = split(" ", $aryLookupTable[$i]['values'][$row['hardwareID']]);
                            $currentAmount = $existing[0];
                        } else {
                            $currentAmount = 0;
                        }
                        $aryLookupTable[$i]['values'][$row['hardwareID']] = ($currentAmount + $ramAry[0]) . " MB";
                    }
                break;

                case "$progText762": # Total Disk Space
                    loadLookupTable($i, $progText762, TRUE, 'h.hardwareID', $aryLookupTable);
                    $strSQL = "SELECT size, hardwareID FROM logicaldisks";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $sizeAry = split(" ", $row['size']);
                        // Factor out, perhaps?
                        if ($sizeAry[1] != "MB" || !is_numeric($sizeAry[0])) {
                            //die("Unexpected size description");
                            continue;
                        }
                        if ($aryLookupTable[$i]['values'][$row['hardwareID']] != "") {
                            $existing = split(" ", $aryLookupTable[$i]['values'][$row['hardwareID']]);
                            $currentAmount = $existing[0];
                        } else {
                            $currentAmount = 0;
                        }
                        $aryLookupTable[$i]['values'][$row['hardwareID']] = ($currentAmount + $sizeAry[0]) . " MB";
                    }
                break;
                
                case "$progText763": # Total Disk Free Space
                    loadLookupTable($i, $progText763, TRUE, 'h.hardwareID', $aryLookupTable);
                    $strSQL = "SELECT freeSpace, hardwareID FROM logicaldisks";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $freeAry = split(" ", $row['freeSpace']);
                        // Factor out, perhaps?
                        if ($freeAry[1] != "MB" || !is_numeric($freeAry[0])) {
                            //die("Unexpected size description");
                            continue;
                        }
                        if ($aryLookupTable[$i]['values'][$row['hardwareID']] != "") {
                            $existing = split(" ", $aryLookupTable[$i]['values'][$row['hardwareID']]);
                            $currentAmount = $existing[0];
                        } else {
                            $currentAmount = 0;
                        }
                        $aryLookupTable[$i]['values'][$row['hardwareID']] = ($currentAmount + $freeAry[0]) . " MB";
                    }
                break;                

                case "$progText46": # Primary OS
                    loadLookupTable($i, $progText46, TRUE, 'h.hardwareID', $aryLookupTable);
                    $strSQL = "SELECT visName, visMaker, visVersion, hardwareID FROM software LEFT JOIN software_traits ON software.softwareTraitID=software_traits.softwareTraitID WHERE software_traits.operatingSystem='1'";
                    $result = dbquery($strSQL);
                    while ($row = mysql_fetch_array($result)) {
                        $aryLookupTable[$i]['values'][$row['hardwareID']] = writePrettySoftwareName($row['visName'], $row['visVersion'], $row['visMaker']);
                    }
                break;
                
                case "$extraSystemField": # user-defined field ("other" by default)
                    loadLookupTable($i, $extraSystemField, FALSE, 'h.other1', $aryLookupTable);
                break;
              }
          }

          // Form an SQL query based on lookup array
          $strSQL = "SELECT DISTINCT ";
          for ($i=1; $i<=$numToShow; $i++) {
              $strSQL .= $aryLookupTable[$i]['sqlColumn'];
              if ($i != $numToShow) {
                  $strSQL .= ", ";
              }
          }
          if ($strSoftwareType) {
              $strFrom1 = ", software AS s, software_traits AS st";
          }
          if ($strPeripheralType) {
               $strFrom2 = ", peripherals AS p, peripheral_traits AS pt";
          }

          $strSQL .= " FROM hardware AS h $strFrom1 $strFrom2 ";

          // Process the WHERE clause from the drop downs
          $strWhereClause = " WHERE ";
          
          if ($cboSystemOwnership != "") {
              $strWhereClause .= "h.sparePart='$cboSystemOwnership' AND ";
          }
          if ($cboSystemType != "") {
              $strWhereClause .= listToOrString($cboSystemType, "h.hardwareTypeID");
          }
          if ($cboLocationID != "") {
              $strWhereClause .= listToOrString($cboLocationID, "h.locationID");
          }
          if ($strPurchaseDate AND $cboPurchaseDate) {
              $strWhereClause .= " h.purchaseDate IS NOT NULL AND h.purchaseDate " . convertSign($cboPurchaseDate) . " " . dbDate($strPurchaseDate) . " AND " ;
          }
          if ($strPurchasePrice AND $cboPurchasePrice) {
              $strWhereClause .= " h.purchasePrice IS NOT NULL AND h.purchasePrice " . convertSign($cboPurchasePrice) . " " . $strPurchasePrice . " AND " ;
          }
          if ($strWarrantyDate AND $cboWarrantyDate) {
              $strWhereClause .= " h.warrantyEndDate IS NOT NULL AND h.warrantyEndDate  " . convertSign($cboWarrantyDate) . " " . dbDate($strWarrantyDate) . " AND " ;
          }
          if ($strAgentDate AND $cboAgentDate) {
              $strWhereClause .= " h.lastAgentUpdate  IS NOT NULL AND h.lastAgentUpdate   " . convertSign($cboAgentDate) . " " . dbDate($strAgentDate) . " AND " ;
          }
          if ($strSoftwareType) {
               $strWhereClause .= " s.hardwareID = h.hardwareID AND st.softwareTraitID=s.softwareTraitID AND s.hidden = '0' AND (st.visName LIKE '%$strSoftwareType%' OR st.visMaker LIKE '%$strSoftwareType%' OR st.visVersion LIKE '%$strSoftwareType%') AND ";
          }
          if ($strPeripheralType) {
               $strWhereClause .= " p.hardwareID = h.hardwareID AND pt.peripheralTraitID=p.peripheralTraitID AND p.hidden = '0' AND (pt.visManufacturer LIKE '%$strPeripheralType%' OR pt.visModel LIKE '%$strPeripheralType%' OR pt.visDescription LIKE '%$strPeripheralType%') AND ";
          }
          if ($cboVendorID != "") {
              $strWhereClause .= listToOrString($cboVendorID, "h.vendorID");
          }
          $strWhereClause .= "h.accountID=" . $_SESSION['accountID'] . "";
          $strSQL .= $strWhereClause;

          // Load up the array containing the results from the SQL and sort

          $result = dbquery($strSQL);
          $i=1;
          $numResults = mysql_num_rows($result);
          if ($numResults == 0) {
              $strError = $progText756; # Handle the empty set
          } else {
              while ($row = mysql_fetch_array($result)) {
                  for ($j=1; $j<=$numToShow; $j++) {
                      if ($aryLookupTable[$j]['fromArray'] == FALSE) {
                          $aryResult[$i][$j] = $row[($j-1)];  # Get value from SQL
                      } else {  # Get value from lookup array
                          $aryResult[$i][$j] = $aryLookupTable[$j]['values'][$row[($j-1)]];
                      }
                  }
                  $i++;
              }
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

      // systemList[1] contains the list in aryAttribute seperated by comma.
      // systemList[2] contains the list in aryShow seperated by comma.
      // systemList[3] contains the sort position.

      setcookie("systemList[1]", $attributeList, (time()+18000000)); # 5000 hour expiration
      setcookie("systemList[2]", $showList, (time()+18000000)); # 5000 hour expiration
      setcookie("systemList[3]", $btnSortPosition, (time()+18000000)); # 5000 hour expiration

      switch ($exportMethod) {
         case "$progText750": # Preview in HTML
?>
<HTML>
<HEAD>
<TITLE><? echo $progText720; ?></TITLE>
<LINK REL=StyleSheet HREF="styles.css" TYPE="text/css">
</HEAD><BODY bgcolor="#FFFFFF" vlink='blue' alink='blue'>
<table border='0' cellpadding='0' cellspacing='0' width='<?=$windowWidth;?>'>
<tr><td><a href='reportSystems.php' class='larger'><b><?=$progText757;?></b></a><p>
<?
             declareError(TRUE);
             generateHTMLTable($arySortedResult, $aryLookupTable, $numToShow, $numResults);
             
             if (getOrPost('includeGraph') == "1") {
				if ($cboLocationID == ""){
					 $xLabel = $progText33;
					 $zLabel = $progText34;
					?>
					<table border=0 width=300>
						<tr>
							<td>
					<?
					createGraph($numToShow,$aryLookupTable,$arySortedResult,$xLabel, $zLabel, $numToShow, $numResults);
					?>
							</td>
						</tr>
					</table>
					<br>
					<?
				}
             	if ($cboSystemType == ""){
					 $xLabel = $progText33;
					 $zLabel = $progText222;
					?>
					<table border=0 width=300>
						<tr>
							<td>
					<?
					createGraph($numToShow,$aryLookupTable,$arySortedResult,$xLabel, $zLabel, $numToShow, $numResults);
					?>
							</td>
						</tr>
					</table>
					<br>
					<?
				}
				if ($cboSystemOwnership == ""){
					 $xLabel = $progText33;
					 $zLabel = $progText722;
					?>
					<table border=0 width=300>
						<tr>
							<td>
					<?
					createGraph($numToShow,$aryLookupTable,$arySortedResult,$xLabel, $zLabel, $numToShow, $numResults);
					?>
							</td>
						</tr>
					</table>
					<br>
					<?
				}
             
             }
             die("</td></tr></table></BODY></HTML>");
             break;

         case "$progText751": # Export to Excel
             generateExcelFile($arySortedResult, $aryLookupTable, $numToShow);
             break;

         case "$progText752": # Export to Text File
             $delimiter = ":";
             generateTxtFile($arySortedResult, $aryLookupTable, $numToShow, $delimiter);
             break;
      }

  } else {

  writeHeader($progText720);
  declareError(TRUE);

?>
  <form name="ReportForm" method="post" action="<? echo $_SERVER['PHP_SELF']?>">
  <table border = '0' width='100%' cellpadding='4' cellspacing='0'>
   <tr>
    <td width='150'><b><?=$progText721;?></b>:</td>
   </tr>
   <tr>
    <td width='150'><?=$progText722;?>:</td>
    <td>
     <select size="1" name="cboSystemOwnership">
       <option value=''>&nbsp;</option>
       <option value='0' <? echo writeSelected($cboSystemOwnership, "0"); ?>><?=$progText725;?></option>
       <option value='1' <? echo writeSelected($cboSystemOwnership, "1"); ?>><?=$progText377;?></option>
       <option value='2' <? echo writeSelected($cboSystemOwnership, "2"); ?>><?=$progText472;?></option>
       <? if ($adminDefinedCategory) { ?>
         <option value='3'<? echo writeSelected($cboSystemOwnership, "3"); ?>><?=ucfirst($adminDefinedCategory);?></option>
       <? } ?>
     </select>
    </td>
   </tr>
   <tr>
    <td width='150' valign='top'><?=$progText33;?>:</td>
    <td valign="top">
     <font class="soft_instructions"><?= $progText777;?></font>
     <br>
     <select multiple size="3" name="cboSystemType[]">
       <?
       // Get all hardware types for the drop down menu
       $strSQLx = "SELECT hardwareTypeID, visDescription, visManufacturer FROM hardware_types WHERE
         accountID=" . $_SESSION['accountID'] ." ORDER BY visDescription ASC";
       $resultx = dbquery($strSQLx);
       while ($rowx = mysql_fetch_array($resultx)) {
         echo "   <option value=\"".$rowx['hardwareTypeID']."\"";
         echo writeSelected($cboSystemType, $rowx['hardwareTypeID']);
         echo ">".writePrettySystemName($rowx['visDescription'], $rowx['visManufacturer'])."</option>\n";
       }
       ?>
     </select>
    </td>
   </tr>
   <? if (!$_SESSION['stuckAtLocation']) { ?>
   <tr>
    <td width='150' valign='top'><?=$progText34;?>:</td>
    <td>
      <?  buildLocationSelect($cboLocationID, FALSE, FALSE, FALSE, FALSE, 3);  ?>
    </td>
   </tr>
   <? } ?>
   <tr>
    <td width='150' valign='top'><?=$progText1226;?>:</td>
    <td>
      <?  buildVendorSelect($cboVendorID, FALSE, FALSE, FALSE, FALSE, 3);  ?>
    </td>
   </tr>
   <tr>
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
      <td width='150'><?=$progText422;?>:</td>
      <td>
        <select name='cboWarrantyDate'>
            <option value="lt" <?=writeSelected($cboWarrantyDate, "lt");?>>&lt;</option>
            <option value="gt" <?=writeSelected($cboWarrantyDate, "gt");?>>&gt;</option>
            <option value="eq" <?=writeSelected($cboWarrantyDate, "eq");?>>=</option>
        </select>
        <? buildDate('txtWarrantyDate', $strWarrantyDate) ?>
      </td>
   </tr>
   <tr>
      <td width='150'><?=$progText50;?>:</td>
      <td>
        <select name='cboAgentDate'>
            <option value="lt" <?=writeSelected($cboAgentDate, "lt");?>>&lt;</option>
            <option value="gt" <?=writeSelected($cboAgentDate, "gt");?>>&gt;</option>
            <option value="eq" <?=writeSelected($cboAgentDate, "eq");?>>=</option>
        </select>
        <? buildDate('txtAgentDate', $strAgentDate) ?>
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
      <TD width='150'><?=$progText380;?>:</TD>
      <TD><INPUT SIZE="40" MAXLENGTH="70" TYPE="Text" NAME="txtSoftwareType" VALUE="<? echo antiSlash($strSoftwareType); ?>"></TD>
   </tr>
   <tr>
      <TD width='150'><?=$progText382;?>:</TD>
      <TD><INPUT SIZE="40" MAXLENGTH="70" TYPE="Text" NAME="txtPeripheralType" VALUE="<? echo antiSlash($strPeripheralType); ?>"></TD>
   </tr>
  </table><br>
  <table border='0' cellpadding='4' cellspacing='0'>
   <tr>
    <td colspan='4'><b><?=$progText728;?></b>:</td>
   </tr>
   <tr class='title'>
    <td width='50'><b><?=$progText734;?></b> &nbsp;</td>
    <td width='190'><b><?=$progText735;?></b> &nbsp;</td>
    <td><b><?=$progText736;?></b> &nbsp;</td>
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
          echo "</tr>\n";
      }
      echo "</table>\n";

    createExportButtons(TRUE);
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
