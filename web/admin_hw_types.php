<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

$hardwareTypeID     = getOrPost('hardwareTypeID');
$peripheralTraitID  = getOrPost('peripheralTraitID');
$softwareTraitID    = getOrPost('softwareTraitID');
$addToInstances     = getOrPost('addToInstances');
$transactionOK      = getOrPost('transactionOK');
$delete             = getOrPost('delete');

makeSessionPaging();

// we are attempting to create or edit a hardware (system) type.
if (getOrPost('btnSubmit')) {

  $strDescription   =  validateText($progText58, getOrPost('txtDescription'), 2, 100, TRUE, FALSE);
  $strManufacturer  =  validateText($progText120, getOrPost('txtManufacturer'), 2, 100, FALSE, FALSE);
  $strNotes         =  validateText($progText70, getOrPost('txtNotes'), 2, 500, FALSE, FALSE);
  $intVendorID      =  cleanFormInput(getOrPost('cboVendorID'));
  $oldVendorID      =  cleanFormInput(getOrPost('oldVendorID'));

  if (!$strError) {
      // Pick the proper sql statement to query the database
      if ($hardwareTypeID) {
         $strSQL = "UPDATE hardware_types SET visDescription='$strDescription',
           visManufacturer=".makeNull($strManufacturer, TRUE).", notes='$strNotes',
           universalVendorID=".makeNull($intVendorID)." WHERE
           accountID=" . $_SESSION['accountID'] . " AND hardwareTypeID=$hardwareTypeID";
         $result = dbquery($strSQL);

         // User has specified a new global Vendor
         If ($intVendorID != $oldVendorID) {
             $strSQL1 = "UPDATE hardware SET vendorID=".makeNull($intVendorID)." WHERE accountID=" . $_SESSION['accountID'] . " AND
               hardwareTypeID=$hardwareTypeID";
             $result1 = dbquery($strSQL1);
         }

         $strError = $progText71;
      } else {
         $strSQL = "INSERT INTO hardware_types (visDescription, visManufacturer, notes, universalVendorID, accountID)
           VALUES ('$strDescription', ".makeNull($strManufacturer, TRUE).", '$strNotes', ".makeNull($intVendorID).", " . $_SESSION['accountID']. ")";
         $result = dbquery($strSQL);
         $strError = $progText72."<br>".$progText87;

         $hardwareTypeID = mysql_insert_id($db);
         $insertOp = 1;
      }

      // If they were creating a new system, don't clear the variables; they may
      // want to add default peripherals and software.
      if (!$insertOp) {
          $hardwareTypeID   =  "";
          $strDescription   =  "";
          $strManufacturer  =  "";
          $strNotes         =  "";
          $intVendorID      =  "";
          $oldVendorID      =  "";
      }
  }

// we are attempting to delete a hardware type (if permissions are correct)
} elseif ($hardwareTypeID AND $delete AND ($_SESSION['sessionSecurity'] < 1)) {

  $strSQL = "DELETE FROM hardware WHERE hardwareTypeID=$hardwareTypeID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);

  $strSQL = "DELETE FROM hardware_types WHERE hardwareTypeID=$hardwareTypeID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);

  $hardwareTypeID = ""; # clear the variable, so it doesn't get reused.

  $strError = $progText73;

// We are adding a peripheral to the list of defaults for this hardware type
} elseif ($hardwareTypeID AND $peripheralTraitID) {

    // Give user the opportunity to add this new default to existing system instances, if they wish
    If (($addToInstances == "") AND !$transactionOK) {
        $strError = $progText88." &nbsp;
          <a href='admin_hw_types.php?hardwareTypeID=$hardwareTypeID&peripheralTraitID=$peripheralTraitID&addToInstances=yes'>".$progText140."</a>
           &nbsp;|&nbsp; \n
          <a href='admin_hw_types.php?hardwareTypeID=$hardwareTypeID&peripheralTraitID=$peripheralTraitID&addToInstances=no'>".$progText141."</a>
           <br>$progText89\n";

    } Else {

        // prevent duplicate peripheral defaults from being created
        $strSQL = "SELECT * FROM hardware_type_defaults WHERE accountID=" . $_SESSION['accountID'] . " AND
          hardwareTypeID=$hardwareTypeID AND objectID=$peripheralTraitID AND objectType='p'";
        $result = dbquery($strSQL);

        // If no duplicates found or user said it is safe to continue, add new peripheral default
        If ((mysql_num_rows($result) == 0) OR $transactionOK) {
            $strSQL2 = "INSERT INTO hardware_type_defaults (hardwareTypeID, objectID, objectType,
              accountID) VALUES ($hardwareTypeID, $peripheralTraitID, 'p', " . $_SESSION['accountID'] . ")";
            $result2 = dbquery($strSQL2);

            // Add chosen peripheral to every instance of this system type
            If ($addToInstances == "yes") {
                $strSQL3 = "SELECT hardwareID FROM hardware WHERE hardwareTypeID=$hardwareTypeID";
                $result3 = dbquery($strSQL3);

                while ($row3 = mysql_fetch_array($result3)) {
                    $strSQL4 = "INSERT INTO peripherals (hardwareID, peripheralTraitID, accountID) VALUES
                      (".$row3[0].", $peripheralTraitID, " . $_SESSION['accountID'] . ")";
                    $result4 = dbquery($strSQL4);
                }
            }

            $strError = $progText72; # success

        // Otherwise, ask user if we should proceed
        } Else {
            $strError = $progText84." <a href='admin_hw_types.php?hardwareTypeID=$hardwareTypeID&peripheralTraitID=$peripheralTraitID&transactionOK=1&addToInstances=$addToInstances'>".$progText85."?</a>\n";
        }

        // If user has permitted a duplicate, refresh the page to clear the querystring,
        // and prevent a third duplicate from being added accidentally.
        If ($transactionOK) {
            redirect("admin_hw_types.php", "hardwareTypeID=$hardwareTypeID&strError=$progText72");
        }
    }

// We are adding software to the list of defaults for this hardware type
} elseif ($hardwareTypeID AND $softwareTraitID) {

    // Give user the opportunity to add this new default to existing system instances, if they wish
    If ($addToInstances == "") {
        $strError = $progText88." &nbsp;
          <a href='admin_hw_types.php?hardwareTypeID=$hardwareTypeID&softwareTraitID=$softwareTraitID&addToInstances=yes'>".$progText140."</a>
           &nbsp;|&nbsp; \n
          <a href='admin_hw_types.php?hardwareTypeID=$hardwareTypeID&softwareTraitID=$softwareTraitID&addToInstances=no'>".$progText141."</a>\n";

    } Else {

        // prevent duplicate software defaults from being created
        $strSQL = "SELECT * FROM hardware_type_defaults WHERE accountID=" . $_SESSION['accountID'] . " AND
          hardwareTypeID=$hardwareTypeID AND objectID=$softwareTraitID AND objectType='s'";
        $result = dbquery($strSQL);

        If (mysql_num_rows($result) == 0) {
            $strSQL2 = "INSERT INTO hardware_type_defaults (hardwareTypeID, objectID, objectType,
              accountID) VALUES ($hardwareTypeID, $softwareTraitID, 's', " . $_SESSION['accountID'] . ")";
            $result2 = dbquery($strSQL2);

            // Add chosen software to every instance of this system type
            If ($addToInstances == "yes") {
                $strSQL3 = "SELECT h.hardwareID, s.softwareID
                  FROM hardware as h
                  LEFT JOIN software as s ON (h.hardwareID=s.hardwareID AND s.softwareTraitID=$softwareTraitID)
                  WHERE h.hardwareTypeID=$hardwareTypeID";
                $result3 = dbquery($strSQL3);

                while ($row3 = mysql_fetch_array($result3)) {
                    // If software isn't already associated with this hardware, add it.
                    If (!$row3[1]) {
                        $strSQL4 = "INSERT INTO software (hardwareID, softwareTraitID, creationDate, accountID) VALUES
                          (".$row3[0].", $softwareTraitID, NOW(), " . $_SESSION['accountID'] . ")";
                        $result4 = dbquery($strSQL4);
                    }
                }
            }

            $strError = $progText72; # success
        }
    }

// we are removing software or peripheral from the list of defaults for this hardware type
} elseif ($defaultID AND $hardwareTypeID) {

    $strSQL = "DELETE FROM hardware_type_defaults WHERE hardwareTypeDefaultID=$defaultID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbquery($strSQL);
}

// If you're editing a hardware type, load the vars
if ($hardwareTypeID) {

  $strSQL = "SELECT * FROM hardware_types WHERE hardwareTypeID=$hardwareTypeID AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $row = mysql_fetch_array($result);

  $hardwareTypeID   = $row["hardwareTypeID"];
  $strDescription   = $row["visDescription"];
  $strManufacturer  = $row["visManufacturer"];
  $strNotes         = $row["notes"];
  $intVendorID      = $row["universalVendorID"];
  $oldVendorID      = $intVendorID;
}

if ($hardwareTypeID) {
    $titlePrefix = $progText75;
    $addInstead = "&nbsp; (<a class='action' href='admin_hw_types.php'>".$progText74."</a>)";
} else {
    $titlePrefix = $progText76;
}

writeHeader($titlePrefix." ".$progText77);
declareError(TRUE);
echo $progTextBlock73; # instructions (in HTML table)
?>
<font color='ff0000'>*</font> <?=$progText13;?>.<p>

<FORM METHOD="post" ACTION="admin_hw_types.php">
<INPUT TYPE="hidden" NAME="hardwareTypeID" VALUE="<? echo $hardwareTypeID; ?>">
<INPUT TYPE="hidden" NAME="oldVendorID" VALUE="<?=$oldVendorID;?>">
 <table border='0' cellpadding='2' cellspacing='0' width='450'>
  <tr>
    <td><font color='ff0000'>*</font> <?=$progText58;?>: &nbsp;</td>
    <td><INPUT SIZE="35" maxlength="100" TYPE="Text" NAME="txtDescription" VALUE="<? echo antiSlash($strDescription); ?>"></td>
  </tr>
  <tr>
    <td><?=$progText120;?>: &nbsp;</td>
    <td><INPUT SIZE="35" maxlength="100" TYPE="Text" NAME="txtManufacturer" VALUE="<? echo antiSlash($strManufacturer); ?>"></td>
  </tr>
  <tr>
    <td width='142'><?=$progText1227;?>: &nbsp;</td>
    <td><? buildVendorSelect($intVendorID, TRUE, ""); ?></td>
  </tr>
  <tr>
    <td valign='top'><?=$progText70;?>: &nbsp;</td>
    <td><textarea name='txtNotes' cols='30' rows='3' wrap='virtual'><? echo antiSlash($strNotes); ?></textarea></td>
  </tr>
<?
  if (!$hardwareTypeID) {
      echo "  <tr><td colspan='2'><br><font class='soft_instructions'>".$progText86."</font></td></tr>\n";
  }
?>
  <tr><td colspan='2'>&nbsp;</td></tr>
  <tr><td colspan='2'><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>">&nbsp;</td></tr>
 </table>
</FORM>

<table border='0' cellpadding='0' cellspacing='0'>
<tr><td><img src='Images/1pix.gif' border='0' width='1' height='20'></td></tr></table>

<?
  if ($hardwareTypeID) {

      // default peripherals assigned to hardwareType (plus link to create new default)
      echo "<i>".$progText82."</i> &nbsp; (<a class='action' href='admin_peripherals.php?hardwareTypeID=$hardwareTypeID'>".$progText76."</a>)<p>\n";

      $strSQL = "SELECT * FROM peripheral_traits as p, hardware_type_defaults as h WHERE
        h.accountID=" . $_SESSION['accountID'] . " AND h.hardwareTypeID=$hardwareTypeID AND h.objectID=p.peripheralTraitID
        AND h.objectType='p' ORDER BY p.visDescription ASC";
      $result = dbquery($strSQL);
      If (mysql_num_rows($result)) {
          echo "<ul>";
      }
      while ($row = mysql_fetch_array($result)) {
          $defaultID     = $row['hardwareTypeDefaultID'];
          $description   = $row['visDescription'];
          $model         = $row['visModel'];
          $manufacturer  = $row['visManufacturer'];

          echo "<li>".writePrettyPeripheralName($description, $model, $manufacturer)."\n";
          echo " &nbsp;-&nbsp; <a class='action' href='admin_hw_types.php?defaultID=$defaultID&hardwareTypeID=$hardwareTypeID' onClick=\"return warn_on_submit('".$progText3."');\">".$progText80."</a>\n";
      }
      If (mysql_num_rows($result)) {
          echo "</ul>";
      }

      // default software assigned to hardwareType (plus link to create new default)
      echo "<p><i>".$progText83."</i> &nbsp; (<a class='action' href='admin_software.php?hardwareTypeID=$hardwareTypeID'>".$progText76."</a>)<p>\n";

      $strSQL = "SELECT * FROM software_traits as s, hardware_type_defaults as h WHERE
        h.accountID=" . $_SESSION['accountID'] . " AND h.hardwareTypeID=$hardwareTypeID AND h.objectID=s.softwareTraitID
        AND h.objectType='s' ORDER BY s.visName ASC";
      $result = dbquery($strSQL);
      If (mysql_num_rows($result)) {
          echo "<ul>";
      }

      while ($row = mysql_fetch_array($result)) {
          $defaultID  = $row['hardwareTypeDefaultID'];
          $name       = $row['visName'];
          $maker      = $row['visMaker'];
          $version    = $row['visVersion'];

          echo "<li>".writePrettySoftwareName($name, $version, $maker)."\n";
          echo " &nbsp;-&nbsp; <a class='action' href='admin_hw_types.php?defaultID=$defaultID&hardwareTypeID=$hardwareTypeID' onClick=\"return warn_on_submit('".$progText3."');\">".$progText80."</a>\n";
      }
      If (mysql_num_rows($result)) {
          echo "</ul>";
      }

      echo "<hr width='470' align='left'>";
  }

  If (getOrPost('btnQuickFind')) {
      $strQuickFind = cleanFormInput(getOrPost('txtQuickFind'));
      $sqlCondition = "AND visDescription LIKE '%$strQuickFind%'";
  }

  # echo "<b>".$progText78."</b> $addInstead <p>\n";
  $strSQL = "SELECT ht.hardwareTypeID, ht.visDescription, ht.visManufacturer, ht.notes, v.vendorName
    FROM hardware_types as ht
    LEFT JOIN vendors as v ON v.vendorID=ht.universalVendorID
    WHERE ht.accountID=" . $_SESSION['accountID'] . " $sqlCondition
    ORDER BY visDescription ASC";
  $strSQL = determinePageNumber($strSQL);
  $result = dbquery($strSQL);
  $records = mysql_num_rows($result);

  // if there are software traits in the database (or user performed a search, which
  // implies that traits were found earlier in the session), show table and search form.
  if (($records > 0) OR getOrPost('btnQuickFind')) {

      // If results will span more than one page, give user option to quick find as well
      // If user searched, give them quick find (again) in case the search did not turn
        // up what they were looking for.
      if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
?>
          <FORM METHOD="get" ACTION="admin_hw_types.php">
          <input type='hidden' name='btnQuickFind' value='1'>
          <table border='0' cellpadding='4' cellspacing='0'>
            <tr><td colspan='5'><b><?=$progText78;?></b> <?=$addInstead;?></td></tr>
            <tr><td colspan='5'><img src='Images/1pix.gif' border='0' width='1' height='8'></td></tr>
            <tr>
              <td colspan='5'><?=$progText81;?> (<?=$progText58;?>):&nbsp;
                <input type='text' name='txtQuickFind' value='<?=$strQuickFind;?>'>
                &nbsp;<INPUT TYPE="submit" NAME="qf" VALUE="<?=$progText21;?>">
              </td>
            </tr>
<?
      } else {
          echo "<table border='0' cellpadding='4' cellspacing='0'>";
          echo "<tr><td colspan='5'><b>".$progText78."</b> ".$addInstead."</td></tr>";
          echo "<tr><td colspan='5'><img src='Images/1pix.gif' border='0' width='1' height='8'></td></tr>";
      }
?>
      <TR class='title'>
         <TD valign='bottom'><nobr><b><?=$progText58;?></b> &nbsp; </nobr></TD>
         <TD valign='bottom'><nobr><b><?=$progText120;?></b> &nbsp; </nobr></TD>
         <TD valign='bottom'><nobr><b><?=$progText1226;?></b> &nbsp; </nobr></TD>
         <TD valign='bottom'><nobr><b><?=$progText70;?></b> &nbsp; </nobr></TD>
         <TD valign='bottom'><nobr><b><?=$progText79;?></b></nobr></TD></TR>
<?
      while ($row2 = mysql_fetch_array($result)) {
          $hardwareTypeID   = $row2['hardwareTypeID'];
          $strDescription   = $row2['visDescription'];
          $strManufacturer  = $row2['visManufacturer'];
          $strNotes         = $row2['notes'];
          $strVendorName    = $row2['vendorName'];

          if (strlen($strNotes) > 25) {
              $strNotes = substr($strNotes, 0, 25)."...";
          }
?>
          <TR class='<? echo alternateRowColor(); ?>'>
             <TD class='smaller'><?=$strDescription;?> &nbsp; &nbsp; </TD>
             <TD class='smaller'><?=writeNA($strManufacturer);?> &nbsp; &nbsp; </TD>
             <TD class='smaller'><?=writeNA($strVendorName);?> &nbsp; &nbsp; </TD>
             <TD class='smaller'><?=writeNA($strNotes);?> &nbsp; &nbsp; </TD>
             <TD class='smaller'>
                 <A class='action' HREF="admin_hw_types.php?hardwareTypeID=<?=$hardwareTypeID;?>"><?=$progText75;?></A>
<?
          if ($_SESSION['sessionSecurity'] < 1) {
?>
                 &nbsp;<A class='action' HREF="admin_hw_types.php?hardwareTypeID=<?=$hardwareTypeID;?>&delete=yes" onClick="return warn_on_submit('<?=$progTextBlock10;?>');"><?=$progText80;?></A>
<?
          }
?>
             &nbsp; </TD>
          </TR>
<?
  }
  echo "</table>";

  $aryQsVarsToRemove = array("hardwareTypeID", "delete");
  createPaging($aryQsVarsToRemove);
  if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
      echo "\n</FORM>";
  }
}

writeFooter();
?>
