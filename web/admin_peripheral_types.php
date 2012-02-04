<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

$peripheralTraitID = getOrPost('peripheralTraitID');
$delete            = getOrPost('delete');
$delType           = getOrPost('delType');

makeSessionPaging();

// Has the form been submitted
if (getOrPost('btnSubmit')) {
    $description   = validateText($progText122, getOrPost('txtDescription'), 2, 100, TRUE, FALSE);
    $manufacturer  = validateText($progText120, getOrPost('txtManufacturer'), 2, 100, FALSE, FALSE);
    $model         = validateText($progText121, getOrPost('txtModel'), 2, 100, FALSE, FALSE);
    $strNotes      = validateText($progText70, getOrPost('txtNotes'), 2, 500, FALSE, FALSE);
    $bolPreserve   = validateChoice(($progText128." ".$progText135), getOrPost('radPreserve'));
    $bolNotify     = validateChoice(($progText129." ".$progText135), getOrPost('radNotify'));
    $strTypeClass  = cleanFormInput(getOrPost('cboTypeClass'));
    $intVendorID   = cleanFormInput(getOrPost('cboVendorID'));
    $oldVendorID   = cleanFormInput(getOrPost('oldVendorID'));

    // are required fields were filled out
    if (!$strError) {

        // If we are editing a peripheral trait...
        if ($peripheralTraitID) {

            If ($manufacturer) { $makerSQL = "= '$manufacturer'"; } Else { $makerSQL = "IS NULL"; }
            If ($model) { $modelSQL = "= '$model'"; } Else { $modelSQL = "IS NULL"; }
            If ($strTypeClass) { $typeClassSQL = "= '$strTypeClass'"; } Else { $typeClassSQL = "IS NULL"; }

            $strSQL = "SELECT count(*) FROM peripheral_traits WHERE visDescription='$description' AND
              visManufacturer $makerSQL AND visModel $modelSQL AND visTypeClass $typeClassSQL AND
              peripheralTraitID != $peripheralTraitID AND accountID=" . $_SESSION['accountID'] . " AND hidden = '0'";
            $result = dbquery($strSQL);
            $row = mysql_fetch_row($result);

            // If no duplicates are found, execute update.
            If (!$row[0]) {

                $strSQL = "UPDATE peripheral_traits SET visManufacturer=".makeNull($manufacturer, TRUE).",
                  visModel=".makeNull($model, TRUE).", visDescription='$description',
                  visTypeClass=".makeNull($strTypeClass, TRUE).", preserve='$bolPreserve', notify='$bolNotify',
                  notes='$strNotes', universalVendorID=".makeNull($intVendorID)." WHERE accountID=" . $_SESSION['accountID'] . "
                  AND peripheralTraitID=$peripheralTraitID";
                $result = dbquery($strSQL);

                // User has specified a new global Vendor
                If ($intVendorID != $oldVendorID) {
                    $strSQL1 = "UPDATE peripherals SET vendorID=".makeNull($intVendorID)." WHERE accountID=" . $_SESSION['accountID'] . " AND
                      peripheralTraitID=$peripheralTraitID";
                    $result1 = dbquery($strSQL1);
                }

                $strError = $progText71; # record updated successfully
                $dbSuccess = true;

            // otherwise, show error that another type with these characteristics already exists.
            } Else {
                $strError = $progText126;
            }

        // otherwise, we are creating a peripheral trait..
        } else {

            If ($manufacturer) { $makerSQL = "= '$manufacturer'"; } Else { $makerSQL = "IS NULL"; }
            If ($model) { $modelSQL = "= '$model'"; } Else { $modelSQL = "IS NULL"; }
            If ($strTypeClass) { $typeClassSQL = "= '$strTypeClass'"; } Else { $typeClassSQL = "IS NULL"; }

            $strSQL = "SELECT count(*) FROM peripheral_traits WHERE visDescription='$description' AND
              visManufacturer $makerSQL AND visModel $modelSQL AND visTypeClass $typeClassSQL AND
              accountID = " . $_SESSION['accountID'] . " AND hidden = '0'";
            $result = dbquery($strSQL);
            $row = mysql_fetch_row($result);

            // If no duplicates are found, execute update.
            If (!$row[0]) {

                $strSQL = "INSERT INTO peripheral_traits (visManufacturer, visModel, visDescription,
                  visTypeClass, preserve, notify, universalVendorID, accountID, notes) VALUES (".makeNull($manufacturer, TRUE).",
                  ".makeNull($model, TRUE).", '$description', ".makeNull($strTypeClass, TRUE).",
                  '$bolPreserve', '$bolNotify',".makeNull($intVendorID).", " . $_SESSION['accountID'] . ", '$strNotes')";
                $result = dbquery($strSQL);
                $strError = $progText72; # record created successfully
                $dbSuccess = true;

            // otherwise, show error that another type with these characteristics already exists.
            } Else {
                $strError = $progText126;
            }
        }

        If ($dbSuccess) {
            $peripheralTraitID  = "";
            $manufacturer       = "";
            $model              = "";
            $strNotes           = "";
            $description        = "";
            $strTypeClass       = "";
            $bolPreserve        = "";
            $bolNotify          = "";
            $intVendorID        = "";
            $oldVendorID        = "";
        }
    }

// user wants to delete type
} elseif ($delete AND $_SESSION['sessionSecurity'] < 1) {

    $strSQLlock = "LOCK TABLES hardware_type_defaults WRITE, peripheral_actions WRITE,
      peripherals WRITE, peripheral_types WRITE, peripheral_traits WRITE";
    $resultLock = dbquery($strSQLlock);

    // delete every peripheral_action associated with the given peripheral trait
    $strSQL = "DELETE FROM peripheral_actions WHERE peripheralTraitID=$peripheralTraitID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbquery($strSQL);

    // delete every peripheral associated with the given peripheral trait
    $strSQL = "DELETE FROM peripherals WHERE peripheralTraitID=$peripheralTraitID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbquery($strSQL);

    // leave peripheral_types as is; their records are necessary to prevent the agent
    // from recreating instances of this peripheral in the future.

    // delete default peripherals associated with hardware types.
    $strSQL = "DELETE FROM hardware_type_defaults WHERE objectID=$peripheralTraitID AND
      objectType='p' AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbquery($strSQL);

    // If user selects to permanently delete the Peripheral Type.
	if ($delType==1) {
		// mark the peripheral_trait as 'hidden', so it disappears from Syslist and
		// will prevent the agent from recreating instances in the future.
		$strSQL2 = "UPDATE peripheral_traits SET hidden='1' WHERE accountID=" . $_SESSION['accountID'] . " AND
          peripheralTraitID=$peripheralTraitID";
		$result2 = dbquery($strSQL2);

	} elseif ($delType==2) {
	    // delete the trait/type from the db, so that no trace of it remains, which means that
	    // agentReport.php WILL recreate it if the Agent ever reports it again.
		$strSQL2 = "DELETE FROM peripheral_types WHERE accountID=" . $_SESSION['accountID'] . " AND
          peripheralTraitID=$peripheralTraitID";
		$result2 = dbquery($strSQL2);

		$strSQL2 = "DELETE FROM peripheral_traits WHERE accountID=" . $_SESSION['accountID'] . " AND
          peripheralTraitID=$peripheralTraitID";
		$result2 = dbquery($strSQL2);
	}

    $strSQLunlock = "UNLOCK TABLES";
    $resultUnlock = dbquery($strSQLunlock);

    $peripheralTraitID = ""; # clear the variable, so it doesn't get reused.

    $strError = $progText73; # record deleted successfully

// If you are editing a peripheral trait ('type' to the user) - load the vars
} elseif ($peripheralTraitID) {

  $strSQL = "SELECT * FROM peripheral_traits WHERE peripheralTraitID=$peripheralTraitID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);

  While ($row = mysql_fetch_array($result)) {
      $peripheralTraitID  = $row["peripheralTraitID"];
      $manufacturer       = $row["visManufacturer"];
      $model              = $row["visModel"];
      $strNotes           = $row["notes"];
      $description        = $row["visDescription"];
      $strTypeClass       = $row["visTypeClass"];
      $bolPreserve        = $row["preserve"];
      $bolNotify          = $row["notify"];
      $intVendorID        = $row["universalVendorID"];
      $oldVendorID        = $intVendorID;
  }
}

if ($peripheralTraitID) {
    $titlePrefix = $progText75; # edit
    // show 'add new type' link
    $addInstead = "&nbsp; (<a class='action' href='admin_peripheral_types.php'>".$progText74."</a>)";
} else {
    $titlePrefix = $progText76; # add
}

$notify = getOrPost('notify');
notifyUser($notify);

writeHeader($titlePrefix." ".$progText123);
declareError(TRUE);
Include("Includes/traitdelete.inc");
echo $progTextBlock67;
?>

<font color='ff0000'>*</font> <?=$progText13;?>.<p>

<FORM METHOD="post" ACTION="admin_peripheral_types.php">
<INPUT TYPE="hidden" NAME="oldVendorID" VALUE="<?=$oldVendorID;?>">
 <table border='0' cellpadding='2' cellspacing='0' width='400'>
  <tr>
    <td width='100'><font color='ff0000'>*</font> <?=$progText122;?>: &nbsp;</td>
    <td width='300'><INPUT SIZE="30" MAXLENGTH="100" TYPE="Text" NAME="txtDescription" VALUE="<? echo antiSlash($description); ?>"></td>
  </tr>
  <tr>
    <td width='100'><?=$progText120;?>: &nbsp;</td>
    <td width='300'><INPUT SIZE="30" MAXLENGTH="100" TYPE="Text" NAME="txtManufacturer" VALUE="<? echo antiSlash($manufacturer); ?>"></td>
  </tr>
  <tr>
    <td width='142'><?=$progText1227;?>: &nbsp;</td>
    <td><? buildVendorSelect($intVendorID, TRUE, ""); ?></td>
  </tr>
  <tr>
    <td width='100'><?=$progText121;?>: &nbsp;</td>
    <td width='300'><INPUT SIZE="30" MAXLENGTH="100" TYPE="Text" NAME="txtModel" VALUE="<? echo antiSlash($model); ?>"></td>
  </tr>
  <tr>
    <td width='142'><font color='ff0000'>*</font> <?=$progText128;?>? &nbsp;</td>
    <td>
      <INPUT TYPE="radio" NAME="radPreserve" VALUE="1" <?=writeChecked("1", $bolPreserve);?>> <?=$progText140;?>
      <INPUT TYPE="radio" NAME="radPreserve" VALUE="0" <?=writeChecked("0", $bolPreserve);?>> <?=$progText141;?>
    </td>
  </tr>
  <tr>
    <td width='142'><font color='ff0000'>*</font> <?=$progText129;?>? &nbsp;</td>
    <td>
      <INPUT TYPE="radio" NAME="radNotify" VALUE="1" <?=writeChecked("1", $bolNotify);?>> <?=$progText140;?>
      <INPUT TYPE="radio" NAME="radNotify" VALUE="0" <?=writeChecked("0", $bolNotify);?>> <?=$progText141;?>
    </td>
  </tr>
  <tr>
    <td width='100' valign='top'><?=$progText70;?>: &nbsp;</td>
    <td width='300'><textarea name='txtNotes' cols='25' rows='3' wrap='virtual'><? echo antiSlash($strNotes); ?></textarea></td>
  </tr>
  <tr><td colspan='2'>&nbsp;<br><font class='soft_instructions'><?=$progText317;?></font></td>
  <tr>
    <td width='100'><?=$progText315;?>: &nbsp;</td>
    <td width='300'><select name='cboTypeClass' size='1'>
        <option value=''>&nbsp;</option>
        <option value='processor' <?=writeSelected("processor", $strTypeClass);?>><?=$progText59;?></option>
        <option value='opticalStorage' <?=writeSelected("opticalStorage", $strTypeClass);?>><?=$progText310;?></option>
        <option value='diskStorage' <?=writeSelected("diskStorage", $strTypeClass);?>><?=$progText311;?></option>
        <option value='netAdapter' <?=writeSelected("netAdapter", $strTypeClass);?>><?=$progText68;?></option>
        <option value='keyboard' <?=writeSelected("keyboard", $strTypeClass);?>><?=$progText312;?></option>
        <option value='pointingDevice' <?=writeSelected("pointingDevice", $strTypeClass);?>><?=$progText313;?></option>
        <option value='printer' <?=writeSelected("printer", $strTypeClass);?>><?=$progText314;?></option>
        <option value='displayAdaptor' <?=writeSelected("displayAdaptor", $strTypeClass);?>><?=$progText61;?></option>
        <option value='RAM' <?=writeSelected("RAM", $strTypeClass);?>><?=$progText62;?></option>
        <option value='soundCard' <?=writeSelected("soundCard", $strTypeClass);?>><?=$progText65;?></option>
        <option value='monitor' <?=writeSelected("monitor", $strTypeClass);?>><?=$progText127;?></option>
        </select></td>
  </tr>

  <tr><td colspan='2'>&nbsp;</td></tr>
  <tr><td colspan='2'><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>">&nbsp;</td></tr>
 </table>
 <INPUT TYPE="hidden" NAME="peripheralTraitID" VALUE="<?=$peripheralTraitID;?>">
</FORM>

<table border='0' cellpadding='0' cellspacing='0'>
<tr><td><img src='Images/1pix.gif' border='0' width='1' height='20'></td></tr></table>

<?
  If (getOrPost('btnQuickFind')) {
      $strQuickFind1 = cleanFormInput(getOrPost('txtQuickFind'));
      $strQuickFind2 = cleanFormInput(getOrPost('cboQuickFind'));

      If ($strQuickFind1) {
          $sqlCondition = "AND visDescription LIKE '%$strQuickFind1%' ";
      }
      If ($strQuickFind2) {
          $sqlCondition .= "AND visTypeClass='$strQuickFind2'";
      }
  }

  // display all known peripheral traits ('types' to the user)
  echo "<b>".$progText124."</b> $addInstead<p>";
  $strSQL = "SELECT pt.peripheralTraitID, pt.visManufacturer, pt.visModel, pt.visDescription,
      pt.visTypeClass, pt.hidden, pt.preserve, pt.notify, pt.universalVendorID, pt.notes
    FROM peripheral_traits as pt
    LEFT JOIN vendors as v ON v.vendorID=pt.universalVendorID
    WHERE pt.accountID=" . $_SESSION['accountID'] . " AND hidden='0' $sqlCondition
    ORDER BY visDescription ASC";
  $strSQL = determinePageNumber($strSQL);
  $result = dbquery($strSQL);
  $records = mysql_num_rows($result);

  // If there are peripheral traits in the database (or user performed a search, which
  // implies that traits were found earlier in the session), show table and search form.
  if (($records > 0) OR getOrPost('btnQuickFind')) {

      // If results will span more than one page, give user option to quick find as well
      // If user searched, give them quick find (again) in case the search did not turn
      // up what they were looking for.
      if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
?>
          <FORM METHOD="get" ACTION="admin_peripheral_types.php">
          <input type='hidden' name='btnQuickFind' value='1'>
          <table border='0' cellpadding='4' cellspacing='0'>
            <tr>
              <td colspan='5'><?=$progText81;?> (<?=$progText58;?>):&nbsp;
                <input type='text' size='16' name='txtQuickFind' value='<?=$strQuickFind1;?>'>
                &nbsp;<select name='cboQuickFind' size='1'>
                  <option value=''>* <?=$progText316;?> *</option>
                  <option value='processor' <?=writeSelected("processor", $strQuickFind2);?>><?=$progText59;?></option>
                  <option value='opticalStorage' <?=writeSelected("opticalStorage", $strQuickFind2);?>><?=$progText310;?></option>
                  <option value='diskStorage' <?=writeSelected("diskStorage", $strQuickFind2);?>><?=$progText311;?></option>
                  <option value='netAdapter' <?=writeSelected("netAdapter", $strQuickFind2);?>><?=$progText68;?></option>
                  <option value='keyboard' <?=writeSelected("keyboard", $strQuickFind2);?>><?=$progText312;?></option>
                  <option value='pointingDevice' <?=writeSelected("pointingDevice", $strQuickFind2);?>><?=$progText313;?></option>
                  <option value='printer' <?=writeSelected("printer", $strQuickFind2);?>><?=$progText314;?></option>
                  <option value='displayAdaptor' <?=writeSelected("displayAdaptor", $strQuickFind2);?>><?=$progText61;?></option>
                  <option value='RAM' <?=writeSelected("RAM", $strQuickFind2);?>><?=$progText62;?></option>
                  <option value='soundCard' <?=writeSelected("soundCard", $strQuickFind2);?>><?=$progText65;?></option>
                  <option value='monitor' <?=writeSelected("monitor", $strQuickFind2);?>><?=$progText127;?></option>
                </select>

                &nbsp;<INPUT TYPE="submit" NAME="qf" VALUE="<?=$progText21;?>">
              </td>
            </tr>
<?
      } else {
          echo "<table border='0' cellpadding='4' cellspacing='0'>\n";
      }
?>
      <TR class='title'>
        <TD valign='bottom'><nobr><b><?=$progText122;?></b> &nbsp; </nobr></TD>
        <TD valign='bottom'><nobr><b><?=$progText120;?></b> &nbsp; </nobr></TD>
        <TD valign='bottom'><nobr><b><?=$progText1226;?></b> &nbsp; </nobr></TD>
        <TD valign='bottom'><nobr><b><?=$progText121;?></b> &nbsp; </nobr></TD>
        <TD valign='bottom'><nobr><b><?=$progText315;?></b> &nbsp; </nobr></td>
        <TD valign='bottom'><nobr><b><?=$progText70;?></b></nobr></TD>
        <TD valign='bottom'><nobr><b><?=$progText79;?></b></nobr></TD>
<?

      while ($row = mysql_fetch_array($result)) {
          $peripheralTraitID  = $row['peripheralTraitID'];
          $description        = $row['visDescription'];
          $model              = $row['visModel'];
          $strNotes           = $row['notes'];
          $manufacturer       = $row['visManufacturer'];
          $typeClass          = $row['visTypeClass'];
          $strVendorName      = $row['vendorName'];
          If (strlen($strNotes) > 19) {
             $strNoteDots = "...";
          } Else {
          	$strNoteDots = "";
          }
?>
          <TR class='<? echo alternateRowColor(); ?>'>
             <TD class='smaller'><? echo $description; ?> &nbsp; </TD>
             <TD class='smaller'><? echo writeNA($manufacturer); ?> &nbsp; </TD>
             <TD class='smaller'><? echo writeNA($strVendorName); ?> &nbsp; </TD>
             <TD class='smaller'><? echo writeNA($model); ?> &nbsp; </TD>
             <TD class='smaller'><? echo writePeripheralClass($typeClass); ?> &nbsp; </TD>
             <TD class='smaller'><? echo substr(writeNA($strNotes), 0, 19).$strNoteDots; ?> &nbsp; </TD>
             <TD class='smaller'>
                <A class='action' HREF="admin_peripheral_types.php?peripheralTraitID=<?=$peripheralTraitID;?>"><?=$progText75;?></A>
<?
          If ($_SESSION['sessionSecurity'] < 1) {
?>
                &nbsp;<A class='action' HREF="merge_peripheral_types.php?peripheralTraitID=<?=$peripheralTraitID;?>"><?=$progText125;?></A>
                &nbsp;<A class='action' HREF="javascript:confirm_on_delete('p','<?=$progTextBlock19;?>','<?=$peripheralTraitID;?>','<?=$progText1210;?>','<?=$progText1211;?>','<?=$progText1060;?>');"><?=$progText80;?></A>
<?
          }
?>
             </TD>
          </TR>
<?
      }
      echo "</table>";

      $aryQsVarsToRemove = array("peripheralTraitID", "delete");
      createPaging($aryQsVarsToRemove);
      if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
          echo "\n</FORM>";
      }
  }

writeFooter();
?>
