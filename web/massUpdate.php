<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

  if (getOrPost('btnSubmit')) { // the form has been submitted
      // If stuck, can't update anything unless it's in your location
      if ($_SESSION['stuckAtLocation']) {
          $intLocationFilter = $_SESSION['locationStatus'];
      } else { 
          $intLocationFilter = cleanFormInput(getOrPost('cboLocationFilter'));
      }
      $intTypeFilter      = cleanFormInput(getOrPost('cboTypeFilter'));
      $strHostname        = validateText($progText37, getOrPost('txtHostname'), 1, 40, FALSE, FALSE);
      $strAssetTagFilter  = validateText($progText420, getOrPost('txtAssetTagFilter'), 1, 254, FALSE, FALSE);
      $strIP1             = validateIP("1", FALSE);
      $strIP2             = validateIP("2", FALSE);
      $intVendorFilter    = cleanFormInput(getOrPost('cboVendorFilter'));

      If (($strIP1 OR $strIP2) AND (!$strIP1 OR !$strIP2)) {
          $strError = $progText860;
      }

      // Can't update if you're stuck
      if ($_SESSION['stuckAtLocation']) {
          $intLocationUpdate = "";
      } else { 
          $intLocationUpdate  = cleanFormInput(getOrPost('cboLocationUpdate'));
      }
      $intTypeUpdate      = cleanFormInput(getOrPost('cboTypeUpdate'));
      $strRoomName        = validateText($progText35, getOrPost('txtRoomName'), 1, 40, FALSE, FALSE);
      $strHW_Serial       = validateText($progText36, getOrPost('txtHW_Serial'), 1, 254, FALSE, FALSE);
      $strAssetTagUpdate  = validateText($progText420, getOrPost('txtAssetTagUpdate'), 1, 254, FALSE, FALSE);
      $strPurchasePrice   = validateText($progText424, getOrPost('txtPurchasePrice'), 1, 25, FALSE, FALSE);
      $strPurchaseDate    = validateDate($progText421, getOrPost('txtPurchaseDate'), 1900, (date("Y")+1), FALSE);
      $strWarrantyDate    = validateDate($progText422, getOrPost('txtWarrantyDate'), 1900, (date("Y")+90), FALSE);
      $intVendorUpdate    = cleanFormInput(getOrPost('cboVendorUpdate'));

      if (!$strError AND
          ($intLocationFilter OR $intTypeFilter OR $strHostname OR $strAssetTagFilter OR $strIP1 OR $intVendorFilter) AND
          ($intLocationUpdate OR $intTypeUpdate OR $strRoomName OR $strHW_Serial OR $strAssetTagUpdate OR $strPurchasePrice OR $strPurchaseDate OR $strWarrantyDate OR $intVendorUpdate)) {

          $strCondition1 = "";
          $strCondition2 = "";

          // build WHERE portion of SQL update query
          If ($intLocationFilter) {
              If (is_numeric($intLocationFilter)) {
                  $strCondition1 = "locationID=$intLocationFilter AND ";
              } ElseIf ($intLocationFilter == "unassigned") {
                  $strCondition1 = "locationID IS NULL AND ";
              }
          }
          If ($intVendorFilter) {
              $strCondition1 .= "vendorID=$intVendorFilter AND ";
          }
          If ($intTypeFilter) {
              $strCondition1 .= "hardwareTypeID=$intTypeFilter AND ";
          }
          If ($strHostname) {
              $strCondition1 .= "hostname LIKE '%$strHostname%' AND ";
          }
          If ($strAssetTagFilter) {
              $strCondition1 .= "assetTag LIKE '%$strAssetTagFilter%' AND ";
          }
          If ($strIP1) {
              $strCondition1 .= "INET_ATON(ipAddress) >= INET_ATON('$strIP1') AND
                INET_ATON(ipAddress) <= INET_ATON('$strIP2') AND ";
          }

          // build SET portion of SQL update query
          $comma = "";
          If ($intLocationUpdate) {
              $strCondition2 = "locationID=$intLocationUpdate";
          }
          If ($intVendorUpdate) {
              If ($strCondition2) {
                  $comma = ",";
              }
              $strCondition2 .= "$comma vendorID=$intVendorUpdate";
          }
          If ($intTypeUpdate) {
              If ($strCondition2) {
                  $comma = ",";
              }
              $strCondition2 .= "$comma hardwareTypeID=$intTypeUpdate";
          }
          If ($strRoomName) {
              If ($strCondition2) {
                  $comma = ",";
              }
              $strCondition2 .= "$comma roomName='$strRoomName'";
          }
          If ($strHW_Serial) {
              If ($strCondition2) {
                  $comma = ",";
              }
              $strCondition2 .= "$comma serial='$strHW_Serial'";
          }
          If ($strAssetTagUpdate) {
              If ($strCondition2) {
                  $comma = ",";
              }
              $strCondition2 .= "$comma assetTag='$strAssetTagUpdate'";
          }
          If ($strPurchasePrice) {
              If ($strCondition2) {
                  $comma = ",";
              }
              $strCondition2 .= "$comma purchasePrice=$strPurchasePrice";
          }
          If ($strPurchaseDate) {
              If ($strCondition2) {
                  $comma = ",";
              }
              $strCondition2 .= "$comma purchaseDate=".dbDate($strPurchaseDate);
          }
          If ($strWarrantyDate) {
              If ($strCondition2) {
                  $comma = ",";
              }
              $strCondition2 .= "$comma warrantyEndDate=".dbDate($strWarrantyDate);
          }
          
          If (getOrPost('btnContinue')) {
              $strSQL = "UPDATE hardware SET $strCondition2 WHERE $strCondition1 accountID=" . $_SESSION['accountID'] . "";
              $result = dbquery($strSQL);
              
              $strError = $progText865;
              
          } Else {
              $strSQL = "SELECT count(*) FROM hardware WHERE $strCondition1 accountID=" . $_SESSION['accountID'] . "";
              $result = dbquery($strSQL);
              $rowCount = mysql_fetch_row($result);
              $updateCount = $rowCount[0];
          }

      } ElseIf (!$strError) {
          $strError = $progText861;
      }
  }

  writeHeader($progText481);
  declareError(TRUE);

  if (getOrPost('btnSubmit') AND !$strError AND !getOrPost('btnContinue')) {
?>

  <font size='+1'><?=$progText862;?>: <b><?=$updateCount;?></b><p>
  
  <FORM METHOD="post" ACTION="massUpdate.php">
    <INPUT TYPE="hidden" NAME="btnSubmit" VALUE="1">

    <INPUT TYPE="hidden" NAME="cboLocationFilter" VALUE="<?=$intLocationFilter;?>">
    <INPUT TYPE="hidden" NAME="cboTypeFilter" VALUE="<?=$intTypeFilter;?>">
    <INPUT TYPE="hidden" NAME="txtHostname" VALUE="<?=antiSlash($strHostname);?>">
    <INPUT TYPE="hidden" NAME="txtAssetTagFilter" VALUE="<?=antiSlash($strAssetTagFilter);?>">
    <INPUT TYPE="hidden" NAME="txtIP11" VALUE="<?=getOrPost('txtIP11');?>">
    <INPUT TYPE="hidden" NAME="txtIP21" VALUE="<?=getOrPost('txtIP21');?>">
    <INPUT TYPE="hidden" NAME="txtIP31" VALUE="<?=getOrPost('txtIP31');?>">
    <INPUT TYPE="hidden" NAME="txtIP41" VALUE="<?=getOrPost('txtIP41');?>">
    <INPUT TYPE="hidden" NAME="txtIP12" VALUE="<?=getOrPost('txtIP12');?>">
    <INPUT TYPE="hidden" NAME="txtIP22" VALUE="<?=getOrPost('txtIP22');?>">
    <INPUT TYPE="hidden" NAME="txtIP32" VALUE="<?=getOrPost('txtIP32');?>">
    <INPUT TYPE="hidden" NAME="txtIP42" VALUE="<?=getOrPost('txtIP42');?>">
    <INPUT TYPE="hidden" NAME="cboVendorFilter" VALUE="<?=$intVendorFilter;?>">
    
    <INPUT TYPE="hidden" NAME="cboLocationUpdate" VALUE="<?=$intLocationUpdate;?>">
    <INPUT TYPE="hidden" NAME="cboTypeUpdate" VALUE="<?=$intTypeUpdate;?>">
    <INPUT TYPE="hidden" NAME="txtRoomName" VALUE="<?=antiSlash($strRoomName);?>">
    <INPUT TYPE="hidden" NAME="txtHW_Serial" VALUE="<?=antiSlash($strHW_Serial);?>">
    <INPUT TYPE="hidden" NAME="txtAssetTagUpdate" VALUE="<?=antiSlash($strAssetTagUpdate);?>">
    <INPUT TYPE="hidden" NAME="txtPurchasePrice" VALUE="<?=antiSlash($strPurchasePrice);?>">
    <INPUT TYPE="hidden" NAME="txtPurchaseDate" VALUE="<?=antiSlash($strPurchaseDate);?>">
    <INPUT TYPE="hidden" NAME="txtWarrantyDate" VALUE="<?=antiSlash($strWarrantyDate);?>">
    <INPUT TYPE="hidden" NAME="cboVendorUpdate" VALUE="<?=$intVendorUpdate;?>">
    
    <INPUT TYPE="submit" NAME="btnContinue" VALUE="<?=$progText85;?>?">
  </FORM>
<?      
  } Else {
      echo $progTextBlock59;
?>

  <P><u><?=$progText863;?></u>:<P>

  <FORM METHOD="post" ACTION="massUpdate.php">
  <INPUT TYPE="hidden" NAME="formSignal" VALUE="1">
  <TABLE border='0' width='100%' cellpadding='4' cellspacing='0'>
<? if (!$_SESSION['stuckAtLocation']) { ?>
     <TR>
        <TD width='103'><?=$progText34;?>:</TD>
        <TD><?

  echo "<SELECT SIZE=\"1\" NAME=\"cboLocationFilter\">\n";
  $strSQL = "SELECT * FROM locations WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY locationName ASC";
  $result = dbquery($strSQL);
  echo "   <OPTION VALUE=\"\">&nbsp;</OPTION>\n";
  echo "   <OPTION VALUE=\"unassigned\" ".writeSelected($intLocationFilter, "unassigned").">* ".$progText866." *</OPTION>\n";
  while ($row = mysql_fetch_array($result)) {
      echo "   <OPTION VALUE=\"" . $row['locationID'] . "\" ";
      echo writeSelected($intLocationFilter, $row['locationID']);
      echo ">".$row['locationName']."</OPTION>\n";
  }
  echo "</SELECT>\n";

        ?></TD>
     </TR>
<? } ?>
     <TR>
        <TD width='103'><?=$progText33;?>:</TD>
        <TD>
           <SELECT SIZE="1" NAME="cboTypeFilter" >
              <OPTION VALUE=''>&nbsp;</OPTION>
<?
  // Get all hardware types for the drop down menu
  $strSQLz = "SELECT * FROM hardware_types WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY visDescription ASC";
  $resultz = dbquery($strSQLz);
  while ($rowz = mysql_fetch_array($resultz)) {
     echo "   <OPTION VALUE=\"".$rowz['hardwareTypeID']."\" ";
     echo writeSelected($intTypeFilter, $rowz['hardwareTypeID']);
     echo ">".writePrettySystemName($rowz['visDescription'], $rowz['visManufacturer'])."</OPTION>\n";
  }
?>
           </SELECT>
        </TD>
     </TR>
     <TR>
        <TD width='103'><?=$progText1226;?>:</TD>
        <TD><?

  echo "<SELECT SIZE=\"1\" NAME=\"cboVendorFilter\">\n";
  $strSQLv = "SELECT * FROM vendors WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY vendorName ASC";
  $resultv = dbquery($strSQLv);
  echo "   <OPTION VALUE=\"\">&nbsp;</OPTION>\n";
  while ($rowv = mysql_fetch_array($resultv)) {
      echo "   <OPTION VALUE=\"" . $rowv['vendorID'] . "\" ";
      echo writeSelected($intVendorFilter, $rowv['vendorID']);
      echo ">".$rowv['vendorName']."</OPTION>\n";
  }
  echo "</SELECT>\n";

        ?></TD>
     </TR>
     <TR>
        <TD width='103'><?=$progText37;?>:</TD>
        <TD><INPUT SIZE="30" MAXLENGTH="40" TYPE="Text" NAME="txtHostname" VALUE="<? echo antiSlash($strHostname); ?>"></TD>
     </TR>
     <TR>
        <TD width='103'><?=$progText420;?>:</TD>
        <TD><INPUT SIZE="30" MAXLENGTH="254" TYPE="Text" NAME="txtAssetTagFilter" VALUE="<? echo antiSlash($strAssetTagFilter); ?>"></TD>
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



     <TR><TD colspan='2'>&nbsp;</TD></TR>
     <TR><TD colspan='2'><u><?=$progText864;?></u>:</TD></TR>
     <TR><TD colspan='2'>&nbsp;</TD></TR>
<? if (!$_SESSION['stuckAtLocation']) { ?>
     <TR>
        <TD width='103'><?=$progText34;?>:</TD>
        <TD><?

  echo "<SELECT SIZE=\"1\" NAME=\"cboLocationUpdate\">\n";
  $strSQL = "SELECT * FROM locations WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY locationName ASC";
  $result = dbquery($strSQL);
  echo "   <OPTION VALUE=\"\">&nbsp;</OPTION>\n";
  while ($row = mysql_fetch_array($result)) {
      echo "   <OPTION VALUE=\"" . $row['locationID'] . "\" ";
      echo writeSelected($intLocationUpdate, $row['locationID']);
      echo ">".$row['locationName']."</OPTION>\n";
  }
  echo "</SELECT>\n";

        ?></TD>
     </TR>
<? } ?>
     <TR>
        <TD width='103'><?=$progText33;?>:</TD>
        <TD>
           <SELECT SIZE="1" NAME="cboTypeUpdate" >
              <OPTION VALUE=''>&nbsp;</OPTION>
<?
  // Get all hardware types for the drop down menu
  $strSQLz = "SELECT * FROM hardware_types WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY visDescription ASC";
  $resultz = dbquery($strSQLz);
  while ($rowz = mysql_fetch_array($resultz)) {
     echo "   <OPTION VALUE=\"".$rowz['hardwareTypeID']."\" ";
     echo writeSelected($intTypeUpdate, $rowz['hardwareTypeID']);
     echo ">".writePrettySystemName($rowz['visDescription'], $rowz['visManufacturer'])."</OPTION>\n";
  }
?>
           </SELECT>
        </TD>
     </TR>
     <TR>
        <TD width='103'><?=$progText1226;?>:</TD>
        <TD><?

  echo "<SELECT SIZE=\"1\" NAME=\"cboVendorUpdate\">\n";
  $strSQLv = "SELECT * FROM vendors WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY vendorName ASC";
  $resultv = dbquery($strSQLv);
  echo "   <OPTION VALUE=\"\">&nbsp;</OPTION>\n";
  while ($rowv = mysql_fetch_array($resultv)) {
      echo "   <OPTION VALUE=\"" . $rowv['vendorID'] . "\" ";
      echo writeSelected($intVendorUpdate, $rowv['vendorID']);
      echo ">".$rowv['vendorName']."</OPTION>\n";
  }
  echo "</SELECT>\n";

        ?></TD>
     </TR>

     <TR>
        <TD width='103'><?=$progText35;?>:</TD>
        <TD><INPUT SIZE="20" MAXSIZE="20" TYPE="Text" NAME="txtRoomName" VALUE="<? echo antiSlash($strRoomName); ?>"></TD>
     </TR>
     <TR>
        <TD width='103'><?=$progText420;?>:</TD>
        <TD><INPUT SIZE="30" MAXLENGTH="254" TYPE="Text" NAME="txtAssetTagUpdate" VALUE="<? echo antiSlash($strAssetTagUpdate); ?>"></TD>
     </TR>
     <TR>
        <TD width='103'><?=$progText424;?>:</TD>
        <TD><INPUT SIZE="10" MAXLENGTH="25" TYPE="Text" NAME="txtPurchasePrice" VALUE="<? echo antiSlash($strPurchasePrice); ?>"></TD>
     </TR>
     <TR>
        <TD width='103'><?=$progText421;?>:</TD>
        <TD><? buildDate('txtPurchaseDate', $strPurchaseDate); ?></TD>
     </TR>
     <TR>
        <TD width='103'><?=$progText422;?>:</TD>
        <TD><? buildDate('txtWarrantyDate', $strWarrantyDate); ?></TD>
     </TR>
     <TR><TD colspan='2'>&nbsp;</TD></TR>

     <TR>
        <TD colspan='2'><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>"></TD>
     </TR>
    </TABLE>

  </FORM>

<?
  }
  writeFooter();
?>
