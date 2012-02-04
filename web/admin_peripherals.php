<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

$peripheralTraitID  = getOrPost('peripheralTraitID');
$peripheralID       = getOrPost('peripheralID');
$hardwareTypeID     = getOrPost('hardwareTypeID');
$formSignal         = getOrPost('formSignal');
$delete             = getOrPost('delete');

// Has the form been submitted?
if (getOrPost('btnSubmit')) {

  // Cannot do anything without a trait id; hope it did not get lost
  if (getOrPost('peripheralTraitID')) {

    // Scrub cboUser value, then validate it *if* user did not select "make spare part"
    $intUser = cleanFormInput(getOrPost('cboUser'));
    If ($intUser != "spare") {
        $intSystem = validateChoice($progText139, getOrPost('cboSystem'));
    }

    $strSerial    =  validateText($progText44, getOrPost('txtSerial'), 1, 254, FALSE, FALSE);

    // Spare parts must have a location; check for this
    if ($intUser == "spare") {
        if ($_SESSION['stuckAtLocation']) {
            $intLocationID = $_SESSION['locationStatus'];
        } else {
            $intLocationID  = validateChoice($progText34, getOrPost('cboLocationID'));
        }
        $strRoomName    = validateText($progText35, getOrPost('txtRoomName'), 1, 40, FALSE, FALSE);
    }

    $intVendorID       = cleanFormInput(getOrPost('cboVendorID'));
    $strMacAddress     = validateText($progText1230, getOrPost('txtMacAddress'), 1, 254, FALSE, FALSE);
    $strPurchaseDate   = validateDate($progText421, getOrPost('txtPurchaseDate'), 1900, (date("Y")+1), FALSE);
    $strPurchasePrice  = validateExactNumber($progText424, getOrPost('txtPurchasePrice'), 0, 99999999, FALSE, 2);

    // Make sure, if a serial number has been specified, that it is not identical to
    // a serial number already in the database
    If ($peripheralID) { # if editing, discount this instance's own peripheralID
        $serialSQL = "AND peripheralID!=$peripheralID";
    }
    if (!$strError AND $strSerial) {
        $strSQLerr = "SELECT COUNT(*) FROM peripherals WHERE hidden='0' AND
          peripheralTraitID=$peripheralTraitID AND accountID=" . $_SESSION['accountID'] . " AND
          serial='$strSerial' $serialSQL";
        $resulterr = dbquery($strSQLerr);
        $rowerr = mysql_fetch_row($resulterr);
        if ($rowerr[0] > 0) {
            $strError = $progText39;
        }
    }

    if (!$strError) {

        // Set the variables needed to perform database update
        if ($intUser == "spare") {
            $intSystem      = "NULL";
            $intSparePart   = "1";
            $intMovedToID   = "0";
        } else {
            $intSparePart   = "0";
            $strRoomName    = "";
            $intLocationID  = "";
            $intMovedToID   = $intSystem;
        }

        // If updating an existing peripheral instance:
        if ($peripheralID) {
            // Record this transaction if it's been moved, compare old hardwareID to new
            $result = dbquery("SELECT hardwareID FROM peripherals WHERE peripheralID=$peripheralID AND accountID=" . $_SESSION['accountID'] . "");
            $row = mysql_fetch_array($result);

  	        if ($intMovedToID != $row['hardwareID'])
  	        {
                if ($row['hardwareID']) {
  	                $oldHardwareID = $row['hardwareID'];
  	            } else {
                    $oldHardwareID = "0";
  	            }
  	            $result = dbquery("INSERT INTO peripheral_actions (peripheralTraitID, hardwareID, actionType, actionDate, userID, movedToID, accountID) VALUES ($peripheralTraitID, $oldHardwareID, 'userMove', " . date("YmdHis") . ", " . $_SESSION['userID'] . ", $intMovedToID, " . $_SESSION['accountID'] . ")");
  	        }

            $strSQL = "UPDATE peripherals SET sparePart='$intSparePart', serial='$strSerial',
                hardwareID='$intSystem', peripheralTraitID='$peripheralTraitID', locationID=".makeNull($intLocationID).",
                roomName=".makeNull($strRoomName, TRUE).", vendorID=".makeNull($intVendorID).", macAddress=".makeNull($strMacAddress, TRUE).",
                purchaseDate=".dbDate($strPurchaseDate).", purchasePrice=".makeNull($strPurchasePrice)."
                WHERE accountID=" . $_SESSION['accountID'] . " AND peripheralID=$peripheralID";
            $notify = "update";

        // If creating a new peripheral instance:
        } else {

           // If creating spare peripheral, set extra variables
           $extraSQL1 = "";
           $extraSQL2 = "";
           if ($intUser == "spare") {
               $extraSQL1 = ", locationID, roomName";
               $extraSQL2 = ", $intLocationID, ".makeNull($strRoomName, TRUE);
           }
           $strSQL = "INSERT INTO peripherals (hardwareID, serial, peripheralTraitID, sparePart,
             peripheralTypeID, vendorID, macAddress, purchaseDate, purchasePrice, accountID".$extraSQL1.") VALUES ($intSystem, '$strSerial',
             $peripheralTraitID, '$intSparePart', NULL, ".makeNull($intVendorID).", ".makeNull($strMacAddress, TRUE).", ".
             dbDate($strPurchaseDate).", ".makeNull($strPurchasePrice).", " . $_SESSION['accountID'] . "".$extraSQL2.")";
           $notify = "insert";
        }
        $result = dbquery($strSQL);

        if ($intUser != "spare") {
            redirect("showfull.php", "notify=$notify&hardwareID=$intSystem");
        } else {
            redirect("sparePeripherals.php", "notify=$notify");
        }
    }

  // missing necessary variables; throw error.
  } else {
    $strError = $progText136;
  }

// Form was jscript submitted onChange of user/system/location selection, so get form
// data for re-display
} elseif (getOrPost('formSignal')) {
    
    if ($_SESSION['stuckAtLocation']) {
        $intLocationID = $_SESSION['locationStatus'];
    } else {
        $intLocationID = cleanFormInput(getOrPost('cboLocationID'));
    }
    $oldLocationID     = cleanFormInput(getOrPost('oldLocationID'));
    $intUser           = cleanFormInput(getOrPost('cboUser'));
    $intSystem         = cleanFormInput(getOrPost('cboSystem'));
    $strRoomName       = cleanFormInput(getOrPost('txtRoomName'));
    $strSerial         = cleanFormInput(getOrPost('txtSerial'));
    $intVendorID       = cleanFormInput(getOrPost('cboVendorID'));
    $strMacAddress     = cleanFormInput(getOrPost('txtMacAddress'));
    $strPurchaseDate   = cleanFormInput(getOrPost('txtPurchaseDate'));
    $strPurchasePrice  = cleanFormInput(getOrPost('txtPurchasePrice'));

    // If they changed the location (but had a specific user selected), clear the user/system IDs,
    // since they may no longer be appropriate.
    If (($oldLocationID != $intLocationID) AND is_numeric($intUser)) {
        $intUser    = "";
        $intSystem  = "";
    } ElseIf ($oldLocationID != $intLocationID) {
        $intSystem  = "";
    }

// If you are editing a peripheral, preload its data - but only once (getOrPost('formSignal'))
} elseif ($peripheralID AND !$delete AND !getOrPost('formSignal')) {

    // Retrieve values from querystring
    $intUser = cleanFormInput(getOrPost('cboUser'));

    // Load up the peripheral we're editing
    $strSQL = "SELECT * FROM peripherals as p, peripheral_traits as pt WHERE
      p.peripheralTraitID=pt.peripheralTraitID AND p.peripheralID=$peripheralID AND
      pt.accountID=" . $_SESSION['accountID'] . "";
    $result = dbquery($strSQL);

    $row = mysql_fetch_array($result);
    $peripheralID       = $row["peripheralID"];
    $intSystem          = $row["hardwareID"];
    $peripheralTraitID  = $row["peripheralTraitID"];
    $strSerial          = $row["serial"];
    $manufacturer       = $row['visManufacturer'];
    $model              = $row['visModel'];
    $description        = $row['visDescription'];
    $intLocationID      = $row['locationID'];
    $strRoomName        = $row['roomName'];
    $intVendorID        = $row['vendorID'];
    $strMacAddress      = $row['macAddress'];
    $strPurchaseDate    = $row['purchaseDate'];
    $strPurchasePrice   = $row['purchasePrice'];

    // If peripheral is not spare, retrieve the location of the system it is associated with
    If (!$intLocationID) {
        $strSQL2 = "SELECT h.locationID FROM hardware as h, peripherals as p WHERE
          p.peripheralID=$peripheralID AND h.hardwareID=p.hardwareID AND p.accountID=" . $_SESSION['accountID'] . "";
        $result2 = dbquery($strSQL2);

        $row2           = mysql_fetch_array($result2);
        $intLocationID  = $row2['locationID'];
    }

} Else {

    // retrieve values from querystring
    $intUser    = cleanFormInput(getOrPost('cboUser'));
    $intSystem  = cleanFormInput(getOrPost('cboSystem'));
}

If ($peripheralID) {
    $actionWord = $progText75; # edit
} Else {
    $actionWord = $progText76; # add
}

writeHeader($actionWord." ".$progText137);
declareError(TRUE);

// Begin form via which to add or edit a software instance.
if ($peripheralTraitID) {
    $strSQL = "SELECT * FROM peripheral_traits WHERE peripheralTraitID=$peripheralTraitID
      AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbquery($strSQL);

    while ($row = mysql_fetch_array($result)) {
        $manufacturer = $row['visManufacturer'];
        $model        = $row['visModel'];
        $description  = $row['visDescription'];

        // If we're adding a peripheral instance, pre-populate with universal type values (if any):
        If (!$peripheralID) {
            $intVendorID = $row['universalVendorID'];
        }
    }
?>
  <font color='ff0000'>*</font> <?=$progText13;?>.<p>

  <FORM METHOD="post" ACTION="admin_peripherals.php" NAME="EditPeriph" ID="EditPeriph">
  <INPUT TYPE="hidden" NAME="formSignal" VALUE="1">
  <INPUT TYPE="hidden" NAME="peripheralID" VALUE="<?=$peripheralID;?>">
  <INPUT TYPE="hidden" NAME="peripheralTraitID" VALUE="<?=$peripheralTraitID;?>">
  <INPUT TYPE="hidden" NAME="oldLocationID" VALUE="<? echo $intLocationID; ?>">
  <table border='0' cellpadding='3' width='100%'>
<?
    // If adding new peripheral, default (first time only; ie !getOrPost('formSignal')) to the current
    // location selected for viewing by the user ($_SESSION['locationStatus']).
    if (!getOrPost('btnSubmit') AND !$peripheralID AND !getOrPost('formSignal')) { $intLocationID = $_SESSION['locationStatus']; }

    // Show the required asterick if 'make spare part' is selected; location
    // is just a helpful filter in all other cases - not a requirement.
    if ($intUser == "spare") { $happyAsterick = "<font color='ff0000'>*</font> "; }

    if ((!$intSystem OR $peripheralID) && !$_SESSION['stuckAtLocation']) {
?>
    <tr>
      <td width='101'><?=$happyAsterick;?><u><?=$progText34;?></u>: </td>
      <td><? buildLocationSelect($intLocationID, TRUE, "EditPeriph"); ?></td>
    </tr>
<?
    }
?>
    <tr>
    <tr>
      <td width='106' valign='top'><font color='ff0000'>*</font> <u><?=$progText138;?></u>: </td>
      <td width='450'><? buildUserSystemSelect($intUser, $intSystem, $intLocationID, TRUE, "EditPeriph"); ?></td>

    <tr>
      <td width='101'><u><?=$progText1226;?></u>: </td>
      <td><? buildVendorSelect($intVendorID, TRUE, ""); ?></td>
    </tr>
    <tr>
      <td width='106'><u><?=$progText122;?></u>: </td>
      <td width='450'><? echo $description; ?></td>
    </tr>
    <tr>
      <td width='106'><u><?=$progText120;?></u>: </td>
      <td width='450'><? echo writeNA($manufacturer); ?></td>
    </tr>
    <tr>
      <td width='106'><u><?=$progText121;?></u>: </td>
      <td width='450'><? echo writeNA($model); ?></td>
    </tr>
    <tr>
      <td width='106'><u><?=$progText44;?></u>: </td>
      <td width='450'><INPUT SIZE="30" MAXLENGTH="254" TYPE="Text" NAME="txtSerial" VALUE="<? echo antiSlash($strSerial); ?>"></td>
    </tr>
<?
    // If the new peripheral is currently indicated to be "spare", we must show roomName field
    if ($intUser == "spare") {
?>
    <TR>
      <TD width='106'><u><?=$progText35;?></u>: </TD>
      <TD><INPUT SIZE="20" MAXLENGTH="40" TYPE="Text" NAME="txtRoomName" VALUE="<? echo antiSlash($strRoomName); ?>"></TD>
   </TR>
<?
    }
?>
    <tr>
      <td width='106'><u><?=$progText1230;?></u>: </td>
      <td width='450'><INPUT SIZE="30" MAXLENGTH="254" TYPE="Text" NAME="txtMacAddress" VALUE="<? echo antiSlash($strMacAddress); ?>"></td>
    </tr>
   <tr>
      <td width='106'><u><?=$progText421;?></u>:</td>
      <td width='450'><? buildDate('txtPurchaseDate', $strPurchaseDate); ?></td>
   </tr>
   <tr>
      <td width='120'><u><?=$progText424;?></u>:</td>
      <td><INPUT SIZE="10" MAXLENGTH="11" TYPE="Text" NAME="txtPurchasePrice" VALUE="<? echo antiSlash($strPurchasePrice); ?>"></td>
   </tr>
   <tr>
      <td colspan='2'>&nbsp;</td>
    </tr>
    <tr>
      <td colspan='2'><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>"> &nbsp;</td>
    </tr>
  </table>
  </FORM>
<?
}

// on first visit to page, make user select a peripheral trait
if (!$peripheralTraitID) {

  // retrieve values from querystring
  $intUser    = cleanFormInput(getOrPost('cboUser'));
  $intSystem  = cleanFormInput(getOrPost('cboSystem'));

  If (getOrPost('btnQuickFind')) {
      $strQuickFind = cleanFormInput(getOrPost('txtQuickFind'));
      $sqlCondition = "AND visDescription LIKE '%$strQuickFind%'";
  }

  $strSQL = "SELECT pt.*, v.vendorName
    FROM peripheral_traits as pt
    LEFT JOIN vendors as v ON v.vendorID=pt.universalVendorID
    WHERE hidden='0' AND pt.accountID=" . $_SESSION['accountID'] . " $sqlCondition
    ORDER BY visDescription ASC";
  $strSQL = determinePageNumber($strSQL);
  $result = dbquery($strSQL);
  $records = mysql_num_rows($result);
  If ($records == 0) {
      echo $progText142;
  } Else {
      echo "<p><a href='admin_peripheral_types.php'>".$progText74."</a><p>";

      // If results will span more than one page, give user option to quick find as well
      // If user searched, give them quick find (again) in case the search did not turn
      // up what they were looking for.
      if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
?>
          <FORM METHOD="get" ACTION="admin_peripherals.php">
           <input type='hidden' name='btnQuickFind' value='1'>
           <input type='hidden' name='cboSystem' value='<?=$intSystem;?>'>
           <input type='hidden' name='cboUser' value='<?=$intUser;?>'>
           <input type='hidden' name='hardwareTypeID' value='<?=$hardwareTypeID;?>'>
          <table border='0' cellpadding='4' cellspacing='0'>
           <tr>
             <td colspan='4'><?=$progText81;?> (<?=$progText58;?>):&nbsp;
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
         <TD><b><?=$progText122;?></b></TD>
         <TD><b><?=$progText121;?></b></TD>
         <TD><b><?=$progText120;?></b></TD>
         <TD><b><?=$progText1226;?></b></TD>
         <TD><b><?=$progText79;?></b></TD>
       </TR>
<?
      while ($row = mysql_fetch_array($result)) {
        $peripheralTraitID = $row['peripheralTraitID'];
        $manufacturer      = $row['visManufacturer'];
        $model             = $row['visModel'];
        $description       = $row['visDescription'];
        $strVendorName     = $row['vendorName'];

?>
       <TR class='<?=alternateRowColor()?>'>
         <TD><? echo $description; ?> &nbsp;</TD>
         <TD><? echo writeNA($model); ?> &nbsp;</TD>
         <TD><? echo writeNA($manufacturer); ?> &nbsp;</TD>
         <TD><? echo writeNA($strVendorName); ?> &nbsp;</TD>
         <TD>
<?
         // If hardwareTypeID exists, we are assigning a default peripheral to a system type.
         // otherwise, we are adding a peripheral instance to the database.
         If ($hardwareTypeID) {
             echo "<A HREF='admin_hw_types.php?hardwareTypeID=$hardwareTypeID&peripheralTraitID=$peripheralTraitID'>".$progText76."</A>\n";
         } Else {
             echo "<A HREF='admin_peripherals.php?cboSystem=$intSystem&peripheralTraitID=$peripheralTraitID&cboUser=$intUser'>".$progText76."</A>\n";
         }
?>
         </TD>
      </TR>
<?
      }
  }
  echo "</table>\n";
  createPaging();
  if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
      echo "\n</FORM>";
  }
}

writeFooter();
?>
