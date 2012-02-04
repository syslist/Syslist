<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

// Stuck users can't admin locations
if ($_SESSION['stuckAtLocation']) {
    die;
    // Alternatively,
    // checkPermissions(50, 1); // guarantees redirect to login
}

$locationID            = cleanFormInput(getOrPost('locationID'));
    
// Has the form been submitted?
if (getOrPost('btnSubmit')) {
    $locationName      = validateText($progText91, getOrPost('txtLocationName'), 2, 60, TRUE, FALSE);
    $locationCode      = validateText($progText92, getOrPost('txtLocationCode'), 1, 10, FALSE, FALSE);
    $locationAddress1  = validateText($progText93, getOrPost('txtLocationAddress1'), 2, 50, FALSE, FALSE);
    $locationAddress2  = validateText($progText94, getOrPost('txtLocationAddress2'), 2, 50, FALSE, FALSE);
    $locationCity      = validateText($progText96, getOrPost('txtLocationCity'), 2, 80, FALSE, FALSE);
    $locationState     = cleanFormInput(getOrPost('cboLocationState'));
    $locationCountry   = cleanFormInput(getOrPost('cboLocationCountry'));

    If ($locationCountry == 840) {
        $locationZip   = validateNumber($progText97, getOrPost('txtLocationZip'), 4, 9, FALSE);
    } Else {
        $locationZip   = validateText($progText97, getOrPost('txtLocationZip'), 4, 9, FALSE, FALSE);
    }
    $aryPhoneNumber    = validatePhone($progText98, "locationPhone", FALSE, phoneLengthFromCountry($locationCountry));

    // Check for duplicate location codes upon creation of a new location
	if (!$locationID AND $locationCode) {
        $strSQL = "SELECT locationID FROM locations WHERE locationCode='$locationCode'";
        $result = dbquery($strSQL);
        $intFound = mysql_num_rows($result);
        if ($intFound != 0) {
            $strError = $progText99;
        }
    }

     if (!$strError) { # All required fields were filled out
        // break phone array into useful parts
        $locationPhone      = $aryPhoneNumber[0];
        $locationPhoneCode  = $aryPhoneNumber[1];
        $locationPhoneExt   = $aryPhoneNumber[2];
        
        // Pick the proper sql statement to query the database
        if ($locationID) {
           $strSQL = "UPDATE locations SET locationCode='$locationCode',locationName='$locationName',
             locationAddress1='$locationAddress1',locationAddress2='$locationAddress2',
             locationCity='$locationCity',locationState='$locationState',locationZip='$locationZip',
             locationCountry='$locationCountry',locationPhone='$locationPhone',
             locationPhoneCode='$locationPhoneCode',locationPhoneExt='$locationPhoneExt'
             WHERE accountID=" . $_SESSION['accountID'] . " AND locationID=$locationID";
           $strError = "Record updated successfully.";
        } else {
           $strSQL = "INSERT INTO locations (locationCode,locationName,locationAddress1,
             locationAddress2,locationCity,locationState,locationZip,locationCountry,locationPhone,
             locationPhoneCode,locationPhoneExt,accountID) VALUES ('$locationCode','$locationName',
             '$locationAddress1','$locationAddress2','$locationCity','$locationState','$locationZip',
             '$locationCountry','$locationPhone','$locationPhoneCode','$locationPhoneExt'," . $_SESSION['accountID'] . ")";
           $strError = "Record created successfully.";
        }
        $result = dbquery($strSQL);
    
        $locationID        = "";
        $locationCode      = "";
        $locationName      = "";
        $locationAddress1  = "";
        $locationAddress2  = "";
        $locationCity      = "";
        $locationState     = "";
        $locationZip       = "";
        $locationCountry   = "";
        unset($aryPhoneNumber);
    }

// If we are deleting, make sure nothing is assigned to this location
} elseif (getOrPost('delete') AND $_SESSION['sessionSecurity'] < 1) {

  $strSQL = "SELECT * FROM hardware WHERE locationID=$locationID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $intFound = mysql_num_rows($result);

  // only spare software has a locationID; that's what we're looking for, here.
  $strSQL = "SELECT * FROM software WHERE locationID=$locationID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $intFound2 = mysql_num_rows($result);

  // only spare peripherals have a locationID; that's what we're looking for, here.
  $strSQL = "SELECT * FROM peripherals WHERE locationID=$locationID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $intFound3 = mysql_num_rows($result);
  
  // if something was found, throw an error
  if (!$intFound AND !$intFound2 AND !$intFound3) {
     $strSQL = "DELETE FROM locations WHERE locationID=$locationID AND accountID=" . $_SESSION['accountID'] . "";
     $result = dbquery($strSQL);
     
     If ($locationID == $_SESSION['locationStatus']) {
         $_SESSION['locationStatus'] = "";
     }
  } else {
     If ($intFound) {
         $errText = $progText100;
     }
     If ($intFound2) {
         If ($errText) { $errText.= " ".$progText110." "; } # "and"
         $errText .= $progText101; # "spare software"
     }
     If ($intFound3) {
         If ($errText) { $errText.= " ".$progText110." "; } # "and"
         $errText .= $progText102; # "spare peripherals"
     }

     $strError = $progText103."; ".$errText." ".$progText104;
  }

}

// Load the variables for 'edit' 
if ($locationID AND !getOrPost('btnSubmit')) {

  $strSQL = "SELECT * FROM locations WHERE locationID=$locationID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $thisthing = mysql_fetch_array($result);

  $locationID         = $thisthing["locationID"];
  $locationCode       = $thisthing["locationCode"];
  $locationName       = $thisthing["locationName"];
  $locationAddress1   = $thisthing["locationAddress1"];
  $locationAddress2   = $thisthing["locationAddress2"];
  $locationCity       = $thisthing["locationCity"];
  $locationState      = $thisthing["locationState"];
  $locationZip        = $thisthing["locationZip"];
  $locationCountry    = $thisthing["locationCountry"];
  $aryPhoneNumber[0]  = $thisthing["locationPhone"];
  $aryPhoneNumber[1]  = $thisthing["locationPhoneCode"];
  $aryPhoneNumber[2]  = $thisthing["locationPhoneExt"];
}

if ($locationID) {
    $titlePrefix = $progText75;
    $addInstead = "&nbsp; (<a href='admin_locations.php' class='action'>".$progText105."</a>)";
} else {
    $titlePrefix = $progText76;
}

writeHeader($titlePrefix." ".$progText106);
declareError(TRUE);
?>
<font color='ff0000'>*</font> <?=$progText13;?>.<p>

<FORM METHOD="post" ACTION="<? echo $_SERVER['PHP_SELF']?>">
 <INPUT TYPE="hidden" NAME="locationID" VALUE="<? echo $locationID; ?>">
 <table border='0' cellpadding='2' cellspacing='0' width='500'>
  <tr>
    <td width='130' valign='top'><font color='ff0000'>*</font> <?=$progText91;?>:</td>
    <td width='370'><INPUT SIZE="40" MAXLENGTH="60" TYPE="Text" NAME="txtLocationName" VALUE="<? echo antiSlash($locationName); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText92;?>:</td>
    <td width='370'><INPUT SIZE="4" MAXLENGTH="10" TYPE="Text" NAME="txtLocationCode" VALUE="<? echo antiSlash($locationCode); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText93;?>:</td>
    <td width='370'><INPUT SIZE="40" MAXLENGTH="50" TYPE="Text" NAME="txtLocationAddress1" VALUE="<? echo antiSlash($locationAddress1); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText94;?>:</td>
    <td width='370'><INPUT SIZE="40" MAXLENGTH="50" TYPE="Text" NAME="txtLocationAddress2" VALUE="<? echo antiSlash($locationAddress2); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText96;?>:</td>
    <td width='370'><INPUT SIZE="30" MAXLENGTH="80" TYPE="Text" NAME="txtLocationCity" VALUE="<? echo antiSlash($locationCity); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText107;?>:</td>
    <td width='370'><? buildStates($locationState, 'LocationState')?></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText97;?>:</td>
    <td width='370'><INPUT SIZE="10" MAXLENGTH="9" TYPE="Text" NAME="txtLocationZip" VALUE="<? echo antiSlash($locationZip); ?>"></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText108;?>:</td>
    <td width='370'><? buildCountries($locationCountry, 'LocationCountry')?></td>
  </tr>
  <tr>
    <td width='130' valign='top'><?=$progText98;?>:</td>
    <td width='370'><?buildPhone("locationPhone", $aryPhoneNumber, TRUE, TRUE);?></td>
  </tr>
  <tr><td colspan='2'>&nbsp;</td></tr>
  <tr><td colspan='2'><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>">&nbsp;</td></tr>
 </table>
</FORM>

<table border='0' cellpadding='0' cellspacing='0'>
<tr><td><img src='Images/1pix.gif' border='0' width='1' height='20'></td></tr></table>

<?

  // display all known location types

  echo "<b>".$progText109."</b> $addInstead<p>";
  $strSQL = "SELECT * FROM locations WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY locationName ASC";
  $result = dbquery($strSQL);

  if (mysql_num_rows($result) > 0) {
?>
  <TABLE border='0' cellpadding='4' cellspacing='0'>
  <TR class='title'>
    <TD><b><?=$progText91;?></b> &nbsp; </TD>
    <TD><b><?=$progText92;?></b> &nbsp; </TD>
    <TD><b><?=$progText96;?></b> &nbsp; </TD>
    <TD><b><?=$progText79;?></b></TD>
<?

    while ($row = mysql_fetch_array($result)) {
      $locationID   = $row['locationID'];
      $locationCode = $row['locationCode'];
      $locationName = $row['locationName'];
      $locationCity = $row['locationCity'];

?>
<TR class='<? echo alternateRowColor(); ?>'>
   <TD><? echo $locationName; ?> &nbsp; </TD>
   <TD><? echo writeNA($locationCode); ?> &nbsp; </TD>
   <TD><? echo writeNA($locationCity); ?> &nbsp; </TD>
   <TD>
      <A class='action' HREF="admin_locations.php?locationID=<? echo $locationID; ?>"><?=$progText75;?></A>
<? If ($_SESSION['sessionSecurity'] < 1) { ?>
      &nbsp;<A class='action' HREF="admin_locations.php?locationID=<? echo $locationID; ?>&delete=yes" onClick="return warn_on_submit('<?=$progTextBlock11;?>');"><?=$progText80;?></A>
<? } ?>
   </TD>
</TR>
<?
  }
?></table><?
}

writeFooter();
?>
