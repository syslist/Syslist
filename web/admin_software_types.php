<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

$softwareTraitID  = getOrPost('softwareTraitID');
$delete           = getOrPost('delete');
$delType          = getOrPost('delType');
$licenseID        = getOrPost('licenseID');

makeSessionPaging();

// Has the form been submitted?
if (getOrPost('btnSubmit')) {
    $strName          = validateText($progText173, getOrPost('txtName'), 2, 100, TRUE, FALSE);
    $strManufacturer  = validateText($progText120, getOrPost('txtManufacturer'), 2, 100, FALSE, FALSE);
    $strVersion       = validateText($progText160, getOrPost('txtVersion'), 1, 100, FALSE, FALSE);
    $strNotes         = validateText($progText70, getOrPost('txtNotes'), 2, 500, FALSE, FALSE);
    $strLicense       = cleanFormInput(getOrPost('radLicense'));
    If ($strLicense) {
        $requireNumLicenses = TRUE;
    }
    $intNumLicense       = validateNumber($progText174, getOrPost('txtNumLicense'), 1, 10, $requireNumLicenses);
    $strPricePerLicense  = validateExactNumber($progText1231, getOrPost('txtPricePerLicense'), 0, 99999999, FALSE, 2);
    If ($intNumLicense AND !$strLicense) {
        fillError($progText175);
    }
    If ($strPricePerLicense AND !$strLicense) {
        fillError($progText1232);
    }
    $bolOS            = validateChoice(($progText176." ".$progText135), getOrPost('radOS'));
    $bolMovable       = validateChoice(($progText177." ".$progText135), getOrPost('radMovable'));
    $bolInsNotify     = validateChoice(($progText185." ".$progText135), getOrPost('radInsNotify'));
    $bolUnInsNotify   = validateChoice(($progText186." ".$progText135), getOrPost('radUnInsNotify'));
    $bolBanned        = validateChoice(($progText187." ".$progText135), getOrPost('radBanned'));
    $strBannedReason  = validateText($progText188, getOrPost('txtBannedReason'), 1, 500, $bolBanned);

    $strSerial        = validateText($progText183, getOrPost('txtSerial'), 1, 254, FALSE, FALSE);
    $oldSerial        = cleanFormInput(getOrPost('oldSerial'));

    $intVendorID      = cleanFormInput(getOrPost('cboVendorID'));
    $oldVendorID      = cleanFormInput(getOrPost('oldVendorID'));

    // Are the required fields filled out?
    if  (!$strError) {

        // If we are editing a software trait
        if (getOrPost('softwareTraitID')) {

            If ($strManufacturer) { $makerSQL = "= '$strManufacturer'"; } Else { $makerSQL = "IS NULL"; }
            If ($strVersion) { $versionSQL = "= '$strVersion'"; } Else { $versionSQL = "IS NULL"; }

            $strSQL = "SELECT count(*) FROM software_traits WHERE visName='$strName' AND
              visMaker $makerSQL AND visVersion $versionSQL AND softwareTraitID != $softwareTraitID
              AND accountID = " . $_SESSION['accountID'] . " AND hidden = '0'";
            $result = dbquery($strSQL);
            $row = mysql_fetch_row($result);

            // If no duplicates are found, execute update.
            If (!$row[0]) {

                // If canBeMoved == '1', then (in case it was '0' before) delete all software
                // instances that have hidden == '2'. Software instances associated with an
                // immovable trait become hidden == '2' when they are manually "deleted" by a user
                If ($bolMovable === '1') {
                    $strSQL0 = "DELETE FROM software WHERE softwareTraitID=$softwareTraitID AND
                      hidden='2' AND accountID=" . $_SESSION['accountID'] . "";
                    $result0 = dbquery($strSQL0);
                }

                $strSQL1 = "UPDATE software_traits SET visName='$strName', visMaker=".makeNull($strManufacturer, TRUE).",
                  visVersion=".makeNull($strVersion, TRUE).", operatingSystem='$bolOS', canBeMoved='$bolMovable',
                  universalSerial=".makeNull($strSerial, TRUE).", installNotify='$bolInsNotify', uninstallNotify='$bolUnInsNotify', isBanned='$bolBanned', bannedReason=".makeNull($strBannedReason, TRUE).",
                  notes='$strNotes', universalVendorID=".makeNull($intVendorID)." WHERE accountID=" . $_SESSION['accountID'] . " AND softwareTraitID=$softwareTraitID";
                $result1 = dbquery($strSQL1);

                // User has specified a new global serial
                If ($strSerial != $oldSerial) {
                    $strSQL2 = "UPDATE software SET serial='$strSerial' WHERE accountID=" . $_SESSION['accountID'] . " AND
                      softwareTraitID=$softwareTraitID";
                    $result2 = dbquery($strSQL2);
                }

                // User has specified a new global Vendor
                If ($intVendorID != $oldVendorID) {
                    $strSQL2 = "UPDATE software SET vendorID=".makeNull($intVendorID)." WHERE accountID=" . $_SESSION['accountID'] . " AND
                      softwareTraitID=$softwareTraitID";
                    $result2 = dbquery($strSQL2);
                }

                // User has provided license info; update existing (old) license records
                If ($strLicense AND $licenseID) {
                    $strSQL2 = "UPDATE software_licenses SET licenseType='$strLicense', numLicenses=$intNumLicense,
                    pricePerLicense=".makeNull($strPricePerLicense)." WHERE licenseID=$licenseID AND accountID=" . $_SESSION['accountID'] . "";
                    $result2 = dbquery($strSQL2);

                // User has provided license info; create new license records
                } ElseIf ($strLicense) {
                    $strSQL2 = "INSERT INTO software_licenses (licenseType, numLicenses, pricePerLicense, accountID) VALUES
                      ('$strLicense', $intNumLicense, ".makeNull($strPricePerLicense).", " . $_SESSION['accountID'] . ")";
                    $result2 = dbquery($strSQL2);
                    $licenseID = mysql_insert_id($db);

                    $strSQL3 = "INSERT INTO software_traits_licenses (licenseID, softwareTraitID, accountID)
                      VALUES ($licenseID, $softwareTraitID, " . $_SESSION['accountID'] . ")";
                    $result3 = dbquery($strSQL3);

                // User has removed license info; delete existing (old) license records
                } ElseIf (!$strLicense AND $licenseID) {
                    $strSQL2 = "DELETE FROM software_traits_licenses WHERE softwareTraitID=$softwareTraitID
                      AND licenseID=$licenseID AND accountID=" . $_SESSION['accountID'] . "";
                    $result2 = dbquery($strSQL2);

                    // Check to make sure no other software trait is using this license type
                    // If not, then go ahead and delete it.
                    $strSQLcheck = "SELECT * FROM software_traits_licenses WHERE licenseID=$licenseID
                      AND accountID=" . $_SESSION['accountID'] . "";
                    $resultCheck = dbquery($strSQLcheck);

                    If (mysql_num_rows($resultCheck) == 0) {
                        $strSQL3 = "DELETE FROM software_licenses WHERE licenseID=$licenseID
                          AND accountID=" . $_SESSION['accountID'] . "";
                        $result3 = dbquery($strSQL3);
                    }
                }
                $strError = $progText71;
                $dbSuccess = true;

            // otherwise, show error that another type with these characteristics already exists.
            } Else {
                $strError = $progText184;
            }

        // otherwise, we are inserting a software trait
        } else {

            If ($strManufacturer) { $makerSQL = "= '$strManufacturer'"; } Else { $makerSQL = "IS NULL"; }
            If ($strVersion) { $versionSQL = "= '$strVersion'"; } Else { $versionSQL = "IS NULL"; }

            $strSQL = "SELECT count(*) FROM software_traits WHERE visName='$strName' AND
              visMaker $makerSQL AND visVersion $versionSQL AND accountID = " . $_SESSION['accountID'] . " AND
              hidden = '0'";
            $result = dbquery($strSQL);
            $row = mysql_fetch_row($result);

            // If no duplicates are found, execute update.
            If (!$row[0]) {

                $strSQL = "INSERT INTO software_traits (visName, visMaker, visVersion, operatingSystem,
                  canBeMoved, universalSerial, installNotify, uninstallNotify, isBanned, bannedReason, universalVendorID, accountID, notes) VALUES ('$strName', ".makeNull($strManufacturer, TRUE).",
                  ".makeNull($strVersion, TRUE).", '$bolOS', '$bolMovable', ".makeNull($strSerial, TRUE).", '$bolInsNotify', '$bolUnInsNotify', '$bolBanned', ".makeNull($strBannedReason, TRUE).", ".makeNull($intVendorID).", " . $_SESSION['accountID'] . ", '$strNotes')";
                $result = dbquery($strSQL);
                $softwareTraitID = mysql_insert_id($db);

                // User has provided license info; create new license records
                If ($strLicense) {
                    $strSQL2 = "INSERT INTO software_licenses (licenseType, numLicenses, pricePerLicense, accountID) VALUES
                      ('$strLicense', $intNumLicense, ".makeNull($strPricePerLicense).", " . $_SESSION['accountID'] . ")";
                    $result2 = dbquery($strSQL2);
                    $licenseID = mysql_insert_id($db);

                    $strSQL3 = "INSERT INTO software_traits_licenses (licenseID, softwareTraitID, accountID)
                      VALUES ($licenseID, $softwareTraitID, " . $_SESSION['accountID'] . ")";
                    $result3 = dbquery($strSQL3);
                }

                $strError = $progText72;
                $dbSuccess = true;

            // otherwise, show error about another type with these characteristics already exists.
            } Else {
                $strError = $progText184;
            }
        }

        If ($dbSuccess) {
            $softwareTraitID     = "";
            $strManufacturer     = "";
            $strName             = "";
            $strVersion          = "";
            $strLicense          = "";
            $intNumLicense       = "";
            $bolOS               = "";
            $bolMovable          = "";
            $bolInsNotify        = "";
            $bolUnInsNotify      = "";
            $bolBanned           = "";
            $strBannedReason     = "";
            $licenseID           = "";
            $strNotes            = "";
            $strSerial           = "";
            $oldSerial           = "";
            $intVendorID         = "";
            $oldVendorID         = "";
            $strPricePerLicense  = "";
        }

    } # end $strError if clause

// user wants to delete type
} elseif ($delete AND $_SESSION['sessionSecurity'] < 1) {

  $strSQLlock = "LOCK TABLES hardware_type_defaults WRITE, software_actions WRITE,
    software WRITE, software_types WRITE, software_traits WRITE,
    software_licenses WRITE, software_traits_licenses WRITE";
  $resultLock = dbquery($strSQLlock);

  // delete every software_action associated with the given software trait
  $strSQL = "DELETE FROM software_actions WHERE softwareTraitID=$softwareTraitID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);

  // delete every software instance associated with the given software trait
  $strSQL = "DELETE FROM software WHERE softwareTraitID=$softwareTraitID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);

  // leave software_types as is; their records are necessary to prevent the agent
  // from recreating instances of this software in the future.

  // delete default software associated with hardware types.
  $strSQL = "DELETE FROM hardware_type_defaults WHERE objectID=$softwareTraitID AND
    objectType='s' AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);

      // delete license data associated with this software trait. note: this needs to
      // delete only licenses that aren't in use by other software traits (in a future
      // version; for now, since licenses can't be shared, this code is OK).

      $strSQL2 = "SELECT DISTINCT licenseID FROM software_traits_licenses WHERE
        softwareTraitID=$softwareTraitID AND accountID=" . $_SESSION['accountID'] . "";
      $result2 = dbquery($strSQL2);

      While ($row2 = mysql_fetch_array($result2)) {
          $strSQL3 = "DELETE FROM software_licenses WHERE licenseID=".$row2["licenseID"]."
            AND accountID=" . $_SESSION['accountID'] . "";
          $result3 = dbquery($strSQL3);
      }

      $strSQL3 = "DELETE FROM software_traits_licenses WHERE softwareTraitID=$softwareTraitID
        AND accountID=" . $_SESSION['accountID'] . "";
      $result3 = dbquery($strSQL3);

	  // If user selects to permanently delete the Software Type.
	  if ($delType==1) {
			// mark the software_trait as 'hidden', so it disappears from Syslist and
			// will prevent the agent from recreating instances in the future.
			$strSQL = "UPDATE software_traits SET hidden='1' WHERE accountID=" . $_SESSION['accountID'] . " AND
              softwareTraitID=$softwareTraitID";
			$result = dbquery($strSQL);

      } elseif ($delType==2) {
          // delete the trait from the db, so that no trace of it remains, which means that
	      // agentReport.php WILL recreate it if the Agent ever reports it again.
		  $strSQL2 = "DELETE FROM software_types WHERE accountID=" . $_SESSION['accountID'] . " AND
            softwareTraitID=$softwareTraitID";
	 	  $result2 = dbquery($strSQL2);

		  $strSQL2 = "DELETE FROM software_traits WHERE accountID=" . $_SESSION['accountID'] . " AND
            softwareTraitID=$softwareTraitID";
	 	  $result2 = dbquery($strSQL2);
	  }

  $strSQLunlock = "UNLOCK TABLES";
  $resultUnlock = dbquery($strSQLunlock);

  $softwareTraitID = ""; # clear the variable, so it doesn't get reused.

  $strError = $progText73;

// If you are editing a software trait ('type', to the user) - load the vars
} elseif ($softwareTraitID) {

  $strSQL = "SELECT t.softwareTraitID, t.visName, t.visMaker, t.visVersion, t.operatingSystem,
    t.canBeMoved, t.installNotify, t.uninstallNotify, t.isBanned, t.bannedReason, t.notes, l.licenseType, l.numLicenses, l.pricePerLicense,
    l.licenseID, t.universalSerial, t.universalVendorID
    FROM software_traits as t
    LEFT JOIN software_traits_licenses as tl ON t.softwareTraitID=tl.softwareTraitID
    LEFT JOIN software_licenses as l ON tl.licenseID=l.licenseID
    WHERE t.softwareTraitID=$softwareTraitID AND t.accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);

  While ($row = mysql_fetch_array($result)) {
      $softwareTraitID     = $row["softwareTraitID"];
      $strName             = $row["visName"];
      $strManufacturer     = $row["visMaker"];
      $strVersion          = $row["visVersion"];
      $bolOS               = $row["operatingSystem"];
      $bolMovable          = $row["canBeMoved"];
      $bolInsNotify        = $row["installNotify"];
      $bolUnInsNotify      = $row["uninstallNotify"];
      $bolBanned           = $row["isBanned"];
      $strBannedReason     = $row["bannedReason"];
      $strLicense          = $row["licenseType"];
      $intNumLicense       = $row["numLicenses"];
      $licenseID           = $row["licenseID"];
      $strNotes            = $row["notes"];
      $strSerial           = $row["universalSerial"];
      $oldSerial           = $strSerial;
      $intVendorID         = $row["universalVendorID"];
      $oldVendorID         = $intVendorID;
      $strPricePerLicense  = $row["pricePerLicense"];
  }
}

if ($softwareTraitID) {
    $titlePrefix = $progText75; # edit
    $addInstead = "&nbsp; (<a class='action' href='admin_software_types.php'>".$progText74."</a>)";
} else {
    $titlePrefix = $progText76; # add
}

$notify = getOrPost('notify');
notifyUser($notify);

writeHeader($titlePrefix." ".$progText178);
declareError(TRUE);
Include("Includes/traitdelete.inc");
echo $progTextBlock12; # instructions (in HTML table)
?>

<script language="javascript">
function clearForm() {
    document.forms[0].radLicense[0].checked=false;
    document.forms[0].radLicense[1].checked=false;
    return false;
}
</script>


<font color='ff0000'>*</font> <?=$progText13;?>.<p>

<FORM METHOD="post" ACTION="admin_software_types.php">
<INPUT TYPE="hidden" NAME="softwareTraitID" VALUE="<?=$softwareTraitID;?>">
<INPUT TYPE="hidden" NAME="licenseID" VALUE="<?=$licenseID;?>">
<INPUT TYPE="hidden" NAME="oldSerial" VALUE="<?=$oldSerial;?>">
<INPUT TYPE="hidden" NAME="oldVendorID" VALUE="<?=$oldVendorID;?>">

 <table border='0' cellpadding='2' cellspacing='0'>
  <tr>
    <td width='142'><font color='ff0000'>*</font> <?=$progText173;?>: &nbsp;</td>
    <td><INPUT SIZE="30" MAXLENGTH="100" TYPE="Text" NAME="txtName" VALUE="<? echo antiSlash($strName); ?>"></td>
  </tr>
  <tr>
    <td width='142'><?=$progText120;?>: &nbsp;</td>
    <td><INPUT SIZE="30" MAXLENGTH="100" TYPE="Text" NAME="txtManufacturer" VALUE="<? echo antiSlash($strManufacturer); ?>"></td>
  </tr>
  <tr>
    <td width='142'><?=$progText1227;?>: &nbsp;</td>
    <td><? buildVendorSelect($intVendorID, TRUE, ""); ?></td>
  </tr>
  <tr>
    <td width='142'><?=$progText160;?>: &nbsp;</td>
    <td><INPUT SIZE="30" MAXLENGTH="100" TYPE="Text" NAME="txtVersion" VALUE="<? echo antiSlash($strVersion); ?>"></td>
  </tr>
  <tr>
    <td width='142'><?=$progText179;?>: &nbsp;</td>
    <td>
      <INPUT TYPE="radio" NAME="radLicense" VALUE="peruser" <?=writeChecked("peruser", $strLicense);?>> <?=$progText181;?>
      <INPUT TYPE="radio" NAME="radLicense" VALUE="persystem" <?=writeChecked("persystem", $strLicense);?>> <?=$progText182;?>
      &nbsp;(<a href='' class='action' onClick="return clearForm();">clear</a>)
    </td>
  </tr>
  <tr>
    <td width='142'><?=$progText174;?>: &nbsp;</td>
    <td><INPUT SIZE="7" MAXLENGTH="10" TYPE="Text" NAME="txtNumLicense" VALUE="<? echo antiSlash($intNumLicense); ?>"></td>
  </tr>
  <tr>
    <td width='142'><?=$progText1231;?>: &nbsp;</td>
    <td><INPUT SIZE="7" MAXLENGTH="10" TYPE="Text" NAME="txtPricePerLicense" VALUE="<? echo antiSlash($strPricePerLicense); ?>"></td>
  </tr>
  <tr>
    <td width='142'><font color='ff0000'>*</font> <?=$progText176;?>? &nbsp;</td>
    <td>
      <INPUT TYPE="radio" NAME="radOS" VALUE="1" <?=writeChecked("1", $bolOS);?>> <?=$progText140;?>
      <INPUT TYPE="radio" NAME="radOS" VALUE="0" <?=writeChecked("0", $bolOS);?>> <?=$progText141;?>
    </td>
  </tr>
  <tr>
    <td width='142'><font color='ff0000'>*</font> <?=$progText177;?>? &nbsp;</td>
    <td>
      <INPUT TYPE="radio" NAME="radMovable" VALUE="1" <?=writeChecked("1", $bolMovable);?>> <?=$progText140;?>
      <INPUT TYPE="radio" NAME="radMovable" VALUE="0" <?=writeChecked("0", $bolMovable);?>> <?=$progText141;?>
    </td>
  </tr>
  <tr>
    <td width='142'><font color='ff0000'>*</font> <?=$progText185;?>? &nbsp;</td>
    <td>
      <INPUT TYPE="radio" NAME="radInsNotify" VALUE="1" <?=writeChecked("1", $bolInsNotify);?>> <?=$progText140;?>
      <INPUT TYPE="radio" NAME="radInsNotify" VALUE="0" <?=writeChecked("0", $bolInsNotify);?>> <?=$progText141;?>
    </td>
  </tr>
    <tr>
    <td width='142'><font color='ff0000'>*</font> <?=$progText186;?>? &nbsp;</td>
    <td>
      <INPUT TYPE="radio" NAME="radUnInsNotify" VALUE="1" <?=writeChecked("1", $bolUnInsNotify);?>> <?=$progText140;?>
      <INPUT TYPE="radio" NAME="radUnInsNotify" VALUE="0" <?=writeChecked("0", $bolUnInsNotify);?>> <?=$progText141;?>
    </td>
  </tr>
  </tr>
    <tr>
    <td width='142'><font color='ff0000'>*</font> <?=$progText187;?>? &nbsp;</td>
    <td>
      <INPUT TYPE="radio" NAME="radBanned" VALUE="1" <?=writeChecked("1", $bolBanned);?>> <?=$progText140;?>
      <INPUT TYPE="radio" NAME="radBanned" VALUE="0" <?=writeChecked("0", $bolBanned);?>> <?=$progText141;?>
    </td>
  </tr>  
  <tr>
    <td valign='top' width='142'><?=$progText188;?>: &nbsp;</td>
    <td><textarea name='txtBannedReason' cols='25' rows='3' wrap='virtual'><? echo antiSlash($strBannedReason); ?></textarea></td>
  </tr>
  <tr>
    <td width='142'><?=$progText183;?>: &nbsp;</td>
    <td><INPUT SIZE="30" MAXLENGTH="254" TYPE="Text" NAME="txtSerial" VALUE="<? echo antiSlash($strSerial); ?>"></td>
  </tr>
  <tr>
    <td valign='top' width='142'><?=$progText70;?>: &nbsp;</td>
    <td><textarea name='txtNotes' cols='25' rows='3' wrap='virtual'><? echo antiSlash($strNotes); ?></textarea></td>
  </tr>


  <tr><td colspan='2'>&nbsp;</td></tr>

  <tr><td colspan='2'><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>">&nbsp;</td></tr>
 </table>
</FORM>

<table border='0' cellpadding='0' cellspacing='0'>
<tr><td><img src='Images/1pix.gif' border='0' width='1' height='20'></td></tr></table>

<?
  If (getOrPost('btnQuickFind')) {
      $strQuickFind = cleanFormInput(getOrPost('txtQuickFind'));
      $sqlCondition = "AND t.visName LIKE '%$strQuickFind%'";
  }

  // display all known software traits ('types' to the user)
  echo "<b>".$progText180."</b> $addInstead<p>";
  $strSQL = "SELECT t.softwareTraitID, t.visName, t.visMaker, t.visVersion, t.notes, l.numLicenses, l.pricePerLicense, v.vendorName
    FROM software_traits as t
    LEFT JOIN software_traits_licenses as tl ON t.softwareTraitID=tl.softwareTraitID
    LEFT JOIN software_licenses as l ON tl.licenseID=l.licenseID
    LEFT JOIN vendors as v ON v.vendorID=t.universalVendorID
    WHERE t.accountID=" . $_SESSION['accountID'] . " AND t.hidden='0' $sqlCondition ORDER BY t.visName ASC";
  $strSQL = determinePageNumber($strSQL);
  $result = dbquery($strSQL);
  $records = mysql_num_rows($result);

  // If there are software traits in the database (or user performed a search, which
  // implies that traits were found earlier in the session), show table and search form.
  if (($records > 0) OR getOrPost('btnQuickFind')) {

      // If results will span more than one page, give user option to quick find as well
      // If user searched, give them quick find (again) in case the search did not turn
      // up what they were looking for.
      if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
?>
          <FORM METHOD="get" ACTION="admin_software_types.php">
          <input type='hidden' name='btnQuickFind' value='1'>
          <table border='0' cellpadding='4' cellspacing='0'>
            <tr>
              <td colspan='5'><?=$progText81;?> (<?=$progText173;?>):&nbsp;
                <input type='text' name='txtQuickFind' value='<?=$strQuickFind;?>'>
                &nbsp;<INPUT TYPE="submit" NAME="qf" VALUE="<?=$progText21;?>">
              </td>
            </tr>
<?
      } else {
          echo "<table border='0' cellpadding='4' cellspacing='0'>\n";
      }
?>

    <TR class='title'>
       <TD valign='bottom'><nobr><b><?=$progText173;?></b> &nbsp; </nobr></TD>
       <TD valign='bottom'><nobr><b><?=$progText160;?></b> &nbsp; </nobr></TD>
       <TD valign='bottom'><nobr><b><?=$progText120;?></b> &nbsp; </nobr></TD>
       <TD valign='bottom'><nobr><b><?=$progText1226;?></b> &nbsp; </nobr></TD>
       <TD valign='bottom'><nobr><b><?=$progText162;?></b> &nbsp; </nobr></TD>
       <TD valign='bottom' width='65'><b><?=$progText1231;?></b><nobr> &nbsp;  </nobr></TD>
       <TD valign='bottom'><nobr><b><?=$progText70;?></b></nobr></TD>
       <TD valign='bottom'><nobr><b><?=$progText79;?></b></nobr></TD>
    </TR>
<?
    while ($row = mysql_fetch_array($result)) {
        $softwareTraitID     = $row['softwareTraitID'];
        $strName             = $row['visName'];
        $strMaker            = $row['visMaker'];
        $strVersion          = $row['visVersion'];
        $strLicenses         = $row['numLicenses'];
        $strNotes            = $row['notes'];
        $strVendorName       = $row['vendorName'];
        $strPricePerLicense  = $row['pricePerLicense'];
        
        If ($strPricePerLicense) {
            $strPricePerLicense = number_format($strPricePerLicense, 2, ".", ",");
        }
        If (strlen($strNotes) > 19) {
            $strNoteDots = "...";	
        } Else {
       	    $strNoteDots = "";	
        }
?>
        <TR class='<? echo alternateRowColor(); ?>'>
           <TD class='smaller'><? echo $strName; ?> &nbsp;</TD>
           <TD class='smaller'><? echo writeNA($strVersion); ?> &nbsp;</TD>
           <TD class='smaller'><? echo writeNA($strMaker); ?> &nbsp;</TD>
           <TD class='smaller'><? echo writeNA($strVendorName); ?> &nbsp;</TD>
           <TD class='smaller'><? echo writeNA($strLicenses); ?> &nbsp;</TD>
           <TD class='smaller'><? echo writeNA($strPricePerLicense); ?> &nbsp;</TD>
           <TD class='smaller'><? echo substr(writeNA($strNotes), 0, 19).$strNoteDots; ?> &nbsp;</TD>
           <TD class='smaller'>
              <A class='action' HREF="admin_software_types.php?softwareTraitID=<?=$softwareTraitID;?>"><?=$progText75;?></A>
<?
        If ($_SESSION['sessionSecurity'] < 1) {
?>
            &nbsp;<A class='action' HREF="merge_software_types.php?softwareTraitID=<?=$softwareTraitID;?>"><?=$progText125;?></A>
            &nbsp;<A class='action' HREF="javascript:confirm_on_delete('s','<?=$progTextBlock13;?>','<?=$softwareTraitID;?>','<?=$progText1210;?>','<?=$progText1211;?>','<?=$progText1060;?>');"><?=$progText80;?></A>
<?
        }
?>
           </TD>
        </TR>
<?
    }
    echo "</table>";
    $aryQsVarsToRemove = array("softwareTraitID", "licenseID", "notify", "delete");
    createPaging($aryQsVarsToRemove);
    if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
        echo "\n</FORM>";
    }
  }

writeFooter();
?>
