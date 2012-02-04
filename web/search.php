<?
Include("Includes/global.inc.php");
checkPermissions(2, 1800);

writeHeader($progText374);

if (getOrPost('btnSubmitAll')) {

    $strName           = validateText($progText251, getOrPost('txtName'), 1, 40, FALSE, FALSE);
    $cboSystemType     = cleanFormInput(getOrPost('cboSystemType'));
    $strHW_Serial      = validateText($progText36, getOrPost('txtHW_Serial'), 1, 150, FALSE, FALSE);
    $strHostname       = validateText($progText370, getOrPost('txtHostname'), 1, 70, FALSE, FALSE);
    $strAssetTag       = validateText($progText420, getOrPost('txtAssetTag'), 1, 254, FALSE, FALSE);
    $strMacAddress     = validateText($progText1230, getOrPost('txtMacAddress'), 1, 70, FALSE, FALSE);
    $strIP1            = validateIP("1", FALSE, "GET");
    $strIP2            = validateIP("2", FALSE, "GET");

    $strHW_Comment     = validateText($progText371, getOrPost('txtHW_Comment'), 1, 150, FALSE, FALSE);
    $strPurchaseDate   = validateDate($progText421, getOrPost('txtPurchaseDate'), 1900, (date("Y")+1), FALSE);
    $cboPurchaseDate   = cleanComparatorInput(getOrPost('cboPurchaseDate'));
    $strWarrantyDate   = validateDate($progText422, getOrPost('txtWarrantyDate'), 1900, (date("Y")+90), FALSE);
    $cboWarrantyDate   = cleanComparatorInput(getOrPost('cboWarrantyDate'));
    $strAgentDate      = validateDate($progText422, getOrPost('txtAgentDate'), 1900, (date("Y")+90), FALSE);
    $cboAgentDate      = cleanComparatorInput(getOrPost('cboAgentDate'));
    $strExtraSystemField = validateText($extraSystemField, getOrPost('txtExtraSystemField'), 1, 255, FALSE, FALSE);

    $strSoftwareType   = validateText($progText380, getOrPost('txtSoftwareType'), 1, 70, FALSE, FALSE);
    $strSW_Serial      = validateText($progText372, getOrPost('txtSW_Serial'), 1, 150, FALSE, FALSE);

    $strPeripheralType = validateText($progText382, getOrPost('txtPeripheralType'), 1, 70, FALSE, FALSE);
    $strP_Serial       = validateText($progText373, getOrPost('txtP_Serial'), 1, 150, FALSE, FALSE);
    $cboStatus         = cleanFormInput(getOrPost('cboStatus'));

    if ($_SESSION['stuckAtLocation']) {
       $cboLocationID = $_SESSION['locationStatus'];
    } else {
       $cboLocationID     = cleanFormInput(getOrPost('cboLocationID'));
    }

    If (($strIP1 OR $strIP2) AND (!$strIP1 OR !$strIP2)) {
        $strError = $progText860;
    }

    // Avoid is_numeric tests (arbitrary decision)
    if ($cboSystemType == "") {
        unset($cboSystemType);
    }
    if ($cboPeripheralType == "") {
        unset($cboPeripheralType);
    }
    if ($cboStatus == "") {
        unset($cboStatus);
    }
    if ($cboLocationID == "") {
        unset($cboLocationID);
    }
    // Although everything's handled together, we still decide which groups of fields were used
    // to deal with details of filtering results
    if ($strName || $cboSystemType || $cboLocationID || $strHW_Serial || $strHostname || $strAssetTag || $strMacAddress || ($strIP1 &&  $strIP2) || $strHW_Comment || $strPurchaseDate || $strWarrantyDate || $strAgentDate || $strExtraSystemField) {
        $optSystem = TRUE;
    }
    if ($strSoftwareType || $strSW_Serial) {
        $optSoftware = TRUE;
    }
    if ($strPeripheralType || $strP_Serial || $cboStatus) {
        $optPeripheral = TRUE;
    }

    if ((!$optSystem) && (!$optSoftware) && (!$optPeripheral)) {
        $strError = $progText385;
    }

    if (!$strError) {
        // Hack prevention
        unset($strSelects);
        unset($strFroms);
        unset($strLeftJoins);
        unset($strOns);
        unset($strWheres);
        unset($strOrderBy);
        // Let's be clear on what we're displaying to the user
        $strSelects   = "u.firstName, u.middleInit, u.lastName, u.id";
        function queryForSystem(&$strSelects, &$strFroms, &$strLeftJoins, &$strWheres, &$strOrderBy) {
            $strSelects  .= ", ht.visDescription AS htVisDescription, ht.visManufacturer AS htVisManufacturer, h.hardwareID, h.hostname, h.serial, h.assetTag";
            $strFroms     = "hardware AS h, hardware_types AS ht";
            $strLeftJoins = "tblSecurity AS u ON u.id=h.userID";
            $strWheres    = "h.hardwareTypeID=ht.hardwareTypeID AND h.accountID=" . $_SESSION['accountID'] . "";
            $strOrderBy   = "ht.visDescription, h.hostname, h.userID";
        }
        if ($optSystem) {
            queryForSystem($strSelects, $strFroms, $strLeftJoins, $strWheres, $strOrderBy);
        } elseif ($optSoftware) {
            queryForSystem($strSelects, $strFroms, $strLeftJoins, $strWheres, $strOrderBy);
            if (!$optPeripheral)
                $strWheres .= " AND h.hardwareID=s.hardwareID";
        } elseif ($optPeripheral) {
            // Can't exactly query for system view here. We have to triple left join hardware onto peripherals
            // because there might not be hardware associated with the peripheral if the peripheral is spare,
            // therefore possibly no hardware_type or tblsecurity entry.
            $strSelects   .= ", p.hardwareID, ht.visDescription AS htVisDescription, ht.visManufacturer AS htVisManufacturer, h.hostname, h.serial, h.assetTag";
            $strLeftJoins  = "hardware AS h ON h.hardwareID=p.hardwareID LEFT JOIN tblSecurity AS u ON u.id=h.userID LEFT JOIN hardware_types AS ht ON ht.hardwareTypeID=h.hardwareTypeID";
            $strWheres     = "p.accountID=" . $_SESSION['accountID'] . "";
            $strOrderBy    = "p.sparePart DESC, ht.visDescription, h.hostname, h.userID, pt.visDescription";
        }
        // Now focus on putting all the WHERE's together:
        if ($optSystem) {
            if ($strName) {
                $strWheres .= " AND u.lastName LIKE '%$strName%'";
                $strOrderBy = "u.lastName, u.userID";
            }
            if ($cboSystemType) {
                $strWheres .= " AND h.hardwareTypeID=$cboSystemType";
            }
            if ($cboLocationID == "unassigned") {
                $strWheres .= " AND h.locationID IS NULL";
            } elseif ($cboLocationID) {
                $strWheres .= " AND h.locationID=$cboLocationID";
            }
            if ($strHostname) {
                $strWheres .= " AND h.hostname LIKE '%$strHostname%'";
            }
            if ($strAssetTag) {
                $strWheres .= " AND h.assetTag LIKE '%$strAssetTag%'";
            }
            if ($strMacAddress) {
                $strWheres .= " AND (h.nicMac1 LIKE '%$strMacAddress%' OR h.nicMac2 LIKE '%$strMacAddress%')";
            }
            if ($strHW_Serial) {
                $strWheres .= " AND h.serial LIKE '%$strHW_Serial%'";
            }
            if ($strIP1) {
              $strWheres .= " AND INET_ATON(ipAddress) >= INET_ATON('$strIP1') AND
                INET_ATON(ipAddress) <= INET_ATON('$strIP2')";
            }
            if ($strHW_Comment) {
                $strWheres  .= " AND c.subjectID=h.hardwareID AND c.subjectType='h' AND c.commentText LIKE '%$strHW_Comment%'";
                $strFroms   .= ", comments as c ";
            }
            if ($strPurchaseDate AND $cboPurchaseDate) {
                $strWheres  .= " AND h.purchaseDate IS NOT NULL AND h.purchaseDate " . convertSign($cboPurchaseDate) . " " . dbDate($strPurchaseDate);
            }
            if ($strWarrantyDate AND $cboWarrantyDate) {
                $strWheres  .= " AND h.warrantyEndDate IS NOT NULL AND h.warrantyEndDate " . convertSign($cboWarrantyDate) . " " . dbDate($strWarrantyDate);
            }
            if ($strAgentDate AND $cboAgentDate) {
                $strWheres  .= " AND h.lastAgentUpdate IS NOT NULL AND h.lastAgentUpdate " . convertSign($cboAgentDate) . " " . dbDate($strAgentDate);
            }
            if ($strExtraSystemField) {
                $strWheres  .= " AND h.other1 LIKE '%$strExtraSystemField%'";
            }
        }
        if ($optSoftware) {
            if (isset($strFroms)) {
                $strFroms .= ", ";
            }
            $strWheres .= " AND s.hidden='0' AND st.softwareTraitID=s.softwareTraitID";
             $strFroms .= "software AS s, software_traits AS st";
            if ($strSoftwareType) {
                $strWheres .= " AND (st.visName LIKE '%$strSoftwareType%' OR st.visMaker LIKE '%$strSoftwareType%' OR st.visVersion LIKE '%$strSoftwareType%') ";
            }
            if ($strSW_Serial) {
                $strWheres .= " AND s.serial LIKE '%$strSW_Serial%' ";
            }
        }
        if ($optPeripheral) {
            if (isset($strFroms)) {
                $strFroms .= ", ";
            }
            $strFroms  .= "peripherals AS p, peripheral_traits AS pt";
            $strWheres .= " AND p.hidden='0' AND pt.peripheralTraitID=p.peripheralTraitID";
            if ($strPeripheralType) {
                $strWheres .= " AND (pt.visManufacturer LIKE '%$strPeripheralType%' OR pt.visModel LIKE '%$strPeripheralType%' OR pt.visDescription LIKE '%$strPeripheralType%') ";
            }
            if ($strP_Serial) {
                $strWheres .= " AND p.serial LIKE '%$strP_Serial%'";
            }
            if ($cboStatus) {
                $strWheres .= " AND p.peripheralStatus='$cboStatus' ";
            }
        }
        // Finally, intersect multiple queries at hardwareID
        if ($optPeripheral && $optSoftware) {
            $strWheres .= " AND h.hardwareID=p.hardwareID AND h.hardwareID=s.hardwareID";
        } elseif ($optSystem && $optPeripheral) {
            $strWheres .= " AND h.hardwareID=p.hardwareID";
        } elseif ($optSystem && $optSoftware) {
            $strWheres .= " AND h.hardwareID=s.hardwareID";
        }

        // The bit of string concatenation in there isn't intended to be confusing, but we need to have slightly
        // different WHERE clauses for spare and not spare peripherals.
        $strSQL = "SELECT DISTINCT $strSelects FROM ($strFroms) LEFT JOIN $strLeftJoins WHERE $strWheres" . (($optPeripheral) ? " AND p.sparePart='0'" : "") . " ORDER BY $strOrderBy";
        $strSQL = determinePageNumber($strSQL);
        $result = dbquery($strSQL);
        if (mysql_num_rows($result) > 0) {
            echo "<table border='0' cellpadding='3' cellspacing='0'>";
        }
        while ($row = mysql_fetch_array($result)) {
            if (($optSystem) || ($optSoftware) || ($optPeripheral)) {
                // System view
                echo "<tr><td valign='top'><nobr><a href='showfull.php?hardwareID=".$row['hardwareID']."'>";
                echo writePrettySystemName($row['htVisDescription'], $row['htVisManufacturer'])."</a> &nbsp;</nobr></td>\n";
                if ($strAssetTag) {
                    echo "<td valign='top'><u>".$progText420."</u>: ".writeNA($row['assetTag'])." &nbsp;</td>\n";
                } else {
                    echo "<td valign='top'><u>".$progText37."</u>: ".writeNA($row['hostname'])." &nbsp;</td>\n";
                }
                if ($row['id']) {
                    echo "<td valign='top'><u>".$progText32."</u>: <nobr>".buildName($row['firstName'],$row['middleInit'],$row['lastName'])." &nbsp;</nobr></td>\n";
                }
                echo "<td valign='top'><u>".$progText375."</u>: ".writeNA($row['serial'])." &nbsp;</td>\n";
                echo "</tr>\n";
            }
        }
        if (mysql_num_rows($result) > 0) {
            echo "</table>";
        } else {
            $strError = $progText376;
        }
        createPaging();
        // Show spare peripherals via separate link
        echo "<p>";
        if ($optPeripheral) {
            $result = dbquery("SELECT peripheralID FROM ($strFroms) LEFT JOIN $strLeftJoins WHERE $strWheres AND p.sparePart='1'");
            $numRows = mysql_num_rows($result);
            if ($numRows > 0) {
                echo " &nbsp; &nbsp; *<i>" . $progText102 . ":</i> [ <a href='sparePeripherals.php'>" . $numRows . "</a> ]";
            }
        }
        echo "<p>";
    }
}
    declareError(TRUE);
    echo "<font class='instructions'>".$progText386.":</font>";
?>

<p><FORM METHOD="get" ACTION="search.php">
<TABLE border='0' width='100%' cellpadding='4' cellspacing='0'>
   <TR>
      <TD width='152'><?=$progText378;?>:</TD>
      <TD><INPUT SIZE="25" maxlength="40" TYPE="Text" NAME="txtName" VALUE="<? echo antiSlash($strName); ?>"></TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText33;?>:</TD>
      <TD>
         <SELECT SIZE="1" NAME="cboSystemType" >
            <OPTION VALUE=''>&nbsp;</OPTION>
             <?
             // Get all hardware types for the drop down menu
             $strSQLz = "SELECT hardwareTypeID, visDescription, visManufacturer FROM hardware_types WHERE
               accountID=" . $_SESSION['accountID'] . " ORDER BY visDescription ASC";
             $resultz = dbquery($strSQLz);
             while ($rowz = mysql_fetch_array($resultz)) {
                  echo "   <OPTION VALUE=\"".$rowz['hardwareTypeID']."\"";
                  echo writeSelected($cboSystemType, $rowz['hardwareTypeID']);
                  echo ">".writePrettySystemName($rowz['visDescription'], $rowz['visManufacturer'])."</OPTION>\n";
             }
             ?>
         </SELECT>
      </TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText36;?>:</TD>
      <TD><INPUT SIZE="30" MAXLENGTH="150" TYPE="Text" NAME="txtHW_Serial" VALUE="<? echo antiSlash($strHW_Serial); ?>"></TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText37;?>:</TD>
      <TD><INPUT SIZE="40" MAXLENGTH="70" TYPE="Text" NAME="txtHostname" VALUE="<? echo antiSlash($strHostname); ?>"></TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText420;?>:</TD>
      <TD><INPUT SIZE="40" MAXLENGTH="254" TYPE="Text" NAME="txtAssetTag" VALUE="<? echo antiSlash($strAssetTag); ?>"></TD>
   </TR>
   <TR>
      <TD width='103'><?=$progText45;?>:</TD>
      <TD><TABLE border='0' cellpadding='1' cellspacing='0'>
            <TR><TD><u><?=$progText868;?></u>: &nbsp;</TD>
                <TD><? buildIP($strIP1, "1"); ?> (<?=$progText867;?>)</TD>
            <TR><TD><u><?=$progText869;?></u>: &nbsp;</TD>
                <TD><? buildIP($strIP2, "2"); ?> (<?=$progText867;?>)</TD></TR></TABLE>
      </TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText1230;?>:</TD>
      <TD><INPUT SIZE="40" MAXLENGTH="70" TYPE="Text" NAME="txtMacAddress" VALUE="<? echo antiSlash($strMacAddress); ?>"></TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText371;?>:</TD>
      <TD><INPUT SIZE="40" MAXLENGTH="150" TYPE="Text" NAME="txtHW_Comment" VALUE="<? echo antiSlash($strHW_Comment); ?>"></TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText421;?>:</TD>
      <TD>
        <SELECT NAME='cboPurchaseDate'>
            <OPTION VALUE="lt" <?=writeSelected($cboPurchaseDate, "lt");?>>&lt;</OPTION>
            <OPTION VALUE="gt" <?=writeSelected($cboPurchaseDate, "gt");?>>&gt;</OPTION>
            <OPTION VALUE="eq" <?=writeSelected($cboPurchaseDate, "eq");?>>=</OPTION>
        </SELECT>
        <? buildDate('txtPurchaseDate', $strPurchaseDate) ?>
      </TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText422;?>:</TD>
      <TD>
        <SELECT NAME='cboWarrantyDate'>
            <OPTION VALUE="lt" <?=writeSelected($cboWarrantyDate, "lt");?>>&lt;</OPTION>
            <OPTION VALUE="gt" <?=writeSelected($cboWarrantyDate, "gt");?>>&gt;</OPTION>
            <OPTION VALUE="eq" <?=writeSelected($cboWarrantyDate, "eq");?>>=</OPTION>
        </SELECT>
        <? buildDate('txtWarrantyDate', $strWarrantyDate) ?>
      </TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText50;?>:</TD>
      <TD>
        <SELECT NAME='cboAgentDate'>
            <OPTION VALUE="lt" <?=writeSelected($cboAgentDate, "lt");?>>&lt;</OPTION>
            <OPTION VALUE="gt" <?=writeSelected($cboAgentDate, "gt");?>>&gt;</OPTION>
            <OPTION VALUE="eq" <?=writeSelected($cboAgentDate, "eq");?>>=</OPTION>
        </SELECT>
        <? buildDate('txtAgentDate', $strAgentDate) ?>
      </TD>
   </TR>
   <? if ($extraSystemField != "") { ?>
     <TR>
       <TD width='152'><?=$extraSystemField?>:</TD>
       <TD><INPUT SIZE="40" MAXLENGTH="255" TYPE="Text" NAME="txtExtraSystemField" VALUE="<? echo antiSlash($strExtraSystemField); ?>"></TD>
     </TR>
   <? } ?>
  </TABLE>

  <p><!--<font class='instructions'>Software search:</font><p>-->

  <TABLE border='0' width='100%' cellpadding='4' cellspacing='0'>

   <TR>
      <TD width='152'><?=$progText380;?>:</TD>
      <TD><INPUT SIZE="40" MAXLENGTH="70" TYPE="Text" NAME="txtSoftwareType" VALUE="<? echo antiSlash($strSoftwareType); ?>"></TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText372;?>:</TD>
      <TD><INPUT SIZE="30" MAXLENGTH="150" TYPE="Text" NAME="txtSW_Serial" VALUE="<? echo antiSlash($strSW_Serial); ?>"></TD>
   </TR>

  </TABLE>

  <p><!--<font class='instructions'>Peripheral search:</font><p>-->

  <TABLE border='0' width='100%' cellpadding='4' cellspacing='0'>
   <TR>
      <TD width='152'><?=$progText382;?>:</TD>
      <TD><INPUT SIZE="40" MAXLENGTH="70" TYPE="Text" NAME="txtPeripheralType" VALUE="<? echo antiSlash($strPeripheralType); ?>"></TD>
   </TR>
   <TR>
      <TD width='152'><?=$progText373;?>:</TD>
      <TD><INPUT SIZE="30" MAXLENGTH="150" TYPE="Text" NAME="txtP_Serial" VALUE="<? echo antiSlash($strP_Serial); ?>"></TD>
   </TR>
      <TR>
      <TD width='152'><?=$progText384;?>:</TD>
      <TD><SELECT SIZE="1" NAME="cboStatus"><OPTION VALUE="" <?=writeSelected($cboStatus, "")?>></OPTION><OPTION VALUE="w" <?=writeSelected($cboStatus, "w")?>><?=$progText413?></OPTION><OPTION VALUE="n" <?=writeSelected($cboStatus, "n")?>><?=$progText414?></OPTION><OPTION VALUE="i" <?=writeSelected($cboStatus, "i")?>><?=$progText415?></OPTION><OPTION VALUE="d" <?=writeSelected($cboStatus, "d")?>><?=$progText413A?></OPTION></SELECT></TD>
   </TR>
  </TABLE>
  <? if (!$_SESSION['stuckAtLocation']) { ?>
  <p>
  <TABLE border='0' width='100%' cellpadding='4' cellspacing='0'>
    <TR>
      <TD width='152'><?=$progText34;?>:</TD>
      <TD><?=buildLocationSelect($cboLocationID, FALSE, FALSE, TRUE);?></TD>
    </TR>
  </TABLE>
  <? } ?>
  <p>
  <TABLE border='0' width='100%' cellpadding='4' cellspacing='0'>
   <TR>
      <TD colspan='2'><INPUT TYPE="submit" NAME="btnSubmitAll" VALUE="<?=$progText383;?>"></TD>
   </TR>
  </TABLE>
  </FORM>

<?
  writeFooter();
?>
