<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

$vendorID = getOrPost('vendorID');

// Has the form been submitted?
if (getOrPost('btnSubmit')) {
    $vendorName       = validateText($progText1220, getOrPost('txtVendorName'), 2, 60, TRUE, FALSE);
    $vendorAddress1   = validateText($progText93, getOrPost('txtVendorAddress1'), 2, 50, FALSE, FALSE);
    $vendorAddress2   = validateText($progText94, getOrPost('txtVendorAddress2'), 2, 50, FALSE, FALSE);
    $vendorCity       = validateText($progText96, getOrPost('txtVendorCity'), 2, 80, FALSE, FALSE);
    $vendorState      = cleanFormInput(getOrPost('cboVendorState'));
    $vendorCountry    = cleanFormInput(getOrPost('cboVendorCountry'));
    $strContractInfo  = validateText($progText1224, getOrPost('txtContractInfo'), 2, 500, FALSE, FALSE);
    $strNotes         = validateText($progText70, getOrPost('txtNotes'), 2, 500, FALSE, FALSE);
    
    If ($vendorCountry == 840) {
        $vendorZip   = validateNumber($progText97, getOrPost('txtVendorZip'), 4, 9, FALSE);
    } Else {
        $vendorZip   = validateText($progText97, getOrPost('txtVendorZip'), 4, 9, FALSE, FALSE);
    }
    $aryPhoneNumber    = validatePhone($progText98, "vendorPhone", FALSE, phoneLengthFromCountry($vendorCountry));

     if (!$strError) { # All required fields were filled out
        // break phone array into useful parts
        $vendorPhone      = $aryPhoneNumber[0];
        $vendorPhoneCode  = $aryPhoneNumber[1];
        $vendorPhoneExt   = $aryPhoneNumber[2];
        
        // Pick the proper sql statement to query the database
        if ($vendorID) {
           $strSQL = "UPDATE vendors SET vendorName='$vendorName',
             vendorAddress1='$vendorAddress1',vendorAddress2='$vendorAddress2',
             vendorCity='$vendorCity',vendorState='$vendorState',vendorZip='$vendorZip',
             vendorCountry='$vendorCountry',vendorPhone='$vendorPhone',
             vendorPhoneCode='$vendorPhoneCode',vendorPhoneExt='$vendorPhoneExt',
             contractInfo='$strContractInfo', notes='$strNotes'
             WHERE accountID=" . $_SESSION['accountID'] . " AND vendorID=$vendorID";
           $strError = "Record updated successfully.";
        } else {
           $strSQL = "INSERT INTO vendors (vendorName,vendorAddress1,
             vendorAddress2,vendorCity,vendorState,vendorZip,vendorCountry,vendorPhone,
             vendorPhoneCode,vendorPhoneExt,contractInfo,notes,accountID) VALUES ('$vendorName',
             '$vendorAddress1','$vendorAddress2','$vendorCity','$vendorState','$vendorZip',
             '$vendorCountry','$vendorPhone','$vendorPhoneCode','$vendorPhoneExt',
             '$strContractInfo','$strNotes'," . $_SESSION['accountID'] . ")";
           $strError = "Record created successfully.";
        }
        $result = dbquery($strSQL);

        $vendorID        = "";
        $vendorName      = "";
        $vendorAddress1  = "";
        $vendorAddress2  = "";
        $vendorCity      = "";
        $vendorState     = "";
        $vendorZip       = "";
        $vendorCountry   = "";
        $strContractInfo = "";
        $strNotes        = "";
        unset($aryPhoneNumber);
    }

// If we are deleting, make sure nothing is assigned to this Vendor
} elseif (getOrPost('delete') AND $_SESSION['sessionSecurity'] < 1) {

  $strSQL = "SELECT * FROM hardware WHERE vendorID=$vendorID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $intFound = mysql_num_rows($result);

  $strSQL = "SELECT * FROM hardware_types WHERE universalVendorID=$vendorID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $intFound2 = mysql_num_rows($result);

  $strSQL = "SELECT * FROM software WHERE vendorID=$vendorID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $intFound3 = mysql_num_rows($result);

  $strSQL = "SELECT * FROM software_traits WHERE universalVendorID=$vendorID AND hidden='0' AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $intFound4 = mysql_num_rows($result);

  $strSQL = "SELECT * FROM peripherals WHERE vendorID=$vendorID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $intFound5 = mysql_num_rows($result);

  $strSQL = "SELECT * FROM peripheral_traits WHERE universalVendorID=$vendorID AND hidden='0' AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $intFound6 = mysql_num_rows($result);

  // if something was found, throw an error
  if (!$intFound AND !$intFound2 AND !$intFound3 AND !$intFound4 AND !$intFound5 AND !$intFound6) {
     $strSQL = "DELETE FROM vendors WHERE vendorID=$vendorID AND accountID=" . $_SESSION['accountID'] . "";
     $result = dbquery($strSQL);
     
  } else {
     If ($intFound) {
         $errText = $progText647;
     }
     If ($intFound2) {
         If ($errText) { $errText.= ", ".$progText110." "; } # "and"
         $errText .= $progText33; # "hardware type"
     }
     If ($intFound3) {
         If ($errText) { $errText.= ", ".$progText110." "; } # "and"
         $errText .= $progText156; # "software"
     }
     If ($intFound4) {
         If ($errText) { $errText.= ", ".$progText110." "; } # "and"
         $errText .= $progText380; # "software type"
     }
     If ($intFound5) {
         If ($errText) { $errText.= ", ".$progText110." "; } # "and"
         $errText .= $progText648; # "peripheral"
     }
     If ($intFound6) {
         If ($errText) { $errText.= ", ".$progText110." "; } # "and"
         $errText .= $progText382; # "peripheral type"
     }

      $strError = $progText1233." - ".$errText." ".$progText104;
  }

}

// Load the variables for 'edit' 
if ($vendorID AND !getOrPost('btnSubmit')) {

  $strSQL = "SELECT * FROM vendors WHERE vendorID=$vendorID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $thisthing = mysql_fetch_array($result);

  $vendorID           = $thisthing["vendorID"];
  $vendorCode         = $thisthing["vendorCode"];
  $vendorName         = $thisthing["vendorName"];
  $vendorAddress1     = $thisthing["vendorAddress1"];
  $vendorAddress2     = $thisthing["vendorAddress2"];
  $vendorCity         = $thisthing["vendorCity"];
  $vendorState        = $thisthing["vendorState"];
  $vendorZip          = $thisthing["vendorZip"];
  $vendorCountry      = $thisthing["vendorCountry"];
  $aryPhoneNumber[0]  = $thisthing["vendorPhone"];
  $aryPhoneNumber[1]  = $thisthing["vendorPhoneCode"];
  $aryPhoneNumber[2]  = $thisthing["vendorPhoneExt"];
  $strContractInfo    = $thisthing["contractInfo"];
  $strNotes           = $thisthing["notes"];
}

if ($vendorID) {
    $titlePrefix = $progText75;
    $addInstead = "&nbsp; (<a href='admin_vendors.php' class='action'>".$progText1221."</a>)";
} else {
    $titlePrefix = $progText76;
}

writeHeader($titlePrefix." ".$progText1223);
declareError(TRUE);
?>
<font color='ff0000'>*</font> <?=$progText13;?>.<p>

<FORM METHOD="post" ACTION="<? echo $_SERVER['PHP_SELF']?>">
 <INPUT TYPE="hidden" NAME="vendorID" VALUE="<? echo $vendorID; ?>">
 <table border='0' cellpadding='2' cellspacing='0' width='500'>
  <tr>
    <td width='130' valign='top'><font color='ff0000'>*</font> <?=$progText1220;?>:</td>
    <td width='370'><INPUT SIZE="40" MAXLENGTH="60" TYPE="Text" NAME="txtVendorName" VALUE="<? echo antiSlash($vendorName); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText93;?>:</td>
    <td width='370'><INPUT SIZE="40" MAXLENGTH="50" TYPE="Text" NAME="txtVendorAddress1" VALUE="<? echo antiSlash($vendorAddress1); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText94;?>:</td>
    <td width='370'><INPUT SIZE="40" MAXLENGTH="50" TYPE="Text" NAME="txtVendorAddress2" VALUE="<? echo antiSlash($vendorAddress2); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText96;?>:</td>
    <td width='370'><INPUT SIZE="30" MAXLENGTH="80" TYPE="Text" NAME="txtVendorCity" VALUE="<? echo antiSlash($vendorCity); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText107;?>:</td>
    <td width='370'><? buildStates($vendorState, 'VendorState')?></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText97;?>:</td>
    <td width='370'><INPUT SIZE="10" MAXLENGTH="9" TYPE="Text" NAME="txtVendorZip" VALUE="<? echo antiSlash($vendorZip); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText108;?>:</td>
    <td width='370'><? buildCountries($vendorCountry, 'VendorCountry')?></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText98;?>:</td>
    <td width='370'><?buildPhone("vendorPhone", $aryPhoneNumber, TRUE, TRUE);?></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText1224;?>:</td>
    <td width='370'><textarea name='txtContractInfo' cols='30' rows='3' wrap='virtual'><? echo antiSlash($strContractInfo); ?></textarea></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText70;?>:</td>
    <td width='370'><textarea name='txtNotes' cols='30' rows='3' wrap='virtual'><? echo antiSlash($strNotes); ?></textarea></td>
  </tr>
  
  
  <tr><td colspan='2'>&nbsp;</td></tr>
  <tr><td colspan='2'><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>">&nbsp;</td></tr>
 </table>
</FORM>

<table border='0' cellpadding='0' cellspacing='0'>
<tr><td><img src='Images/1pix.gif' border='0' width='1' height='20'></td></tr></table>

<?

  // display all known Vendors

  echo "<b>".$progText1222."</b> $addInstead<p>";
  $strSQL = "SELECT * FROM vendors WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY vendorName ASC";
  $result = dbquery($strSQL);

  if (mysql_num_rows($result) > 0) {
?>
  <TABLE border='0' cellpadding='4' cellspacing='0'>
  <TR class='title'>
    <TD><b><?=$progText1220;?></b> &nbsp; </TD>
    <TD><b><?=$progText96;?></b> &nbsp; </TD>
    <TD><b><?=$progText98;?></b> &nbsp; </TD>
    <TD><b><?=$progText79;?></b></TD>
<?

    while ($row = mysql_fetch_array($result)) {
      $vendorID         = $row['vendorID'];
      $vendorName       = $row['vendorName'];
      $vendorCity       = $row['vendorCity'];
      $vendorPhone      = $row['vendorPhone'];
      $vendorPhoneCode  = $row['vendorPhoneCode'];
      $vendorPhoneExt   = $row['vendorPhoneExt'];
      If ($vendorPhone) {
          If ($vendorPhoneCode) {
              $vendorPhone = $vendorPhoneCode."-".$vendorPhone;
          }
          If ($vendorPhoneExt) {
              $vendorPhone = $vendorPhone." x".$vendorPhoneExt;
          }
      }


?>
<TR class='<? echo alternateRowColor(); ?>'>
   <TD><? echo $vendorName; ?> &nbsp; </TD>
   <TD><? echo writeNA($vendorCity); ?> &nbsp; </TD>
   <TD><? echo writeNA($vendorPhone); ?> &nbsp; </TD>
   <TD>
      <A class='action' HREF="admin_vendors.php?vendorID=<? echo $vendorID; ?>"><?=$progText75;?></A>
<? If ($_SESSION['sessionSecurity'] < 1) { ?>
      &nbsp;<A class='action' HREF="admin_vendors.php?vendorID=<? echo $vendorID; ?>&delete=yes" onClick="return warn_on_submit('<?=$progTextBlock72;?>');"><?=$progText80;?></A>
<? } ?>
   </TD>
</TR>
<?
  }
?></table><?
}
writeFooter();
?>
