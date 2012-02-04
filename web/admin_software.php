<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

$softwareTraitID  = getOrPost('softwareTraitID');
$softwareID       = getOrPost('softwareID');
$hardwareTypeID   = getOrPost('hardwareTypeID');
$delete           = getOrPost('delete');
$formSignal       = getOrPost('formSignal');
$moveOnce         = getOrPost('moveOnce');
$oldLocationID    = getOrPost('oldLocationID');

// Has the form been submitted?
if (getOrPost('btnSubmit')) {

    // Make sure the trait id was not lost somehow
    if ($softwareTraitID) {

        // Scrub cboUser value, then validate it *if* user did not select "make spare part"
        $intUser = cleanFormInput(getOrPost('cboUser'));
        If ($intUser != "spare") {
            $intSystem = validateChoice($progText139, getOrPost('cboSystem'));
        }
        $strSerial    = validateText($progText44, getOrPost('txtSerial'), 1, 254, FALSE, FALSE);
        $intVendorID  = cleanFormInput(getOrPost('cboVendorID'));
        
        // Spare parts must have a location; check for this
        if ($intUser == "spare") {
            if ($_SESSION['stuckAtLocation']) {
                $intLocationID = $_SESSION['locationStatus'];
            } else {
                $intLocationID  = validateChoice($progText34, getOrPost('cboLocationID'));
            }
            $strRoomName    = validateText($progText35, getOrPost('txtRoomName'), 1, 40, FALSE, FALSE);
        }

        // Make sure, if a serial number has been specified, that it is not identical to
        // a serial number already in the database
        If ($softwareID) { # if editing, discount this instance's own softwareID
            $serialSQL = "AND softwareID!=$softwareID";
        }
        if (!$strError AND $strSerial) {

            // Don't do uniqueness check for software traits that have a global serial number
            $strSQLerr = "SELECT COUNT(*) FROM software_traits WHERE softwareTraitID=$softwareTraitID
              AND accountID=" . $_SESSION['accountID'] . " AND universalSerial='$strSerial'";
            $resulterr = dbquery($strSQLerr);
            $rowerr = mysql_fetch_row($resulterr);

            If (!$rowerr[0]) {
                $strSQLerr = "SELECT COUNT(*) FROM software WHERE hidden='0' AND softwareTraitID=$softwareTraitID
                  AND accountID=" . $_SESSION['accountID'] . " AND serial='$strSerial' $serialSQL";
                $resulterr = dbquery($strSQLerr);
                $rowerr = mysql_fetch_row($resulterr);
                If ($rowerr[0]) {
                    $strError = $progText39; # serial already exists
                }
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

            // If updating existing software instance:
            if (getOrPost('softwareID')) {
                // Record this transaction if it's been moved, compare old hardwareID to new (parallels admin_peripherals)
                $result = dbquery("SELECT hardwareID FROM software WHERE softwareID=$softwareID AND accountID=" . $_SESSION['accountID'] . "");
                $row = mysql_fetch_array($result);
                
      	        if ($intMovedToID != $row['hardwareID'])
      	        {
                    if ($row['hardwareID']) {
      	                $oldHardwareID = $row['hardwareID'];
      	            } else {
                        $oldHardwareID = "0";
      	            }
      	            if ($captureSoftwareHistory) {
      	                $result = dbquery("INSERT INTO software_actions (softwareTraitID, hardwareID, actionType, actionDate, userID, movedToID, accountID) VALUES ($softwareTraitID, $oldHardwareID, 'userMove', " . date("YmdHis") . ", " . $_SESSION['userID'] . ", $intMovedToID, " . $_SESSION['accountID'] . ")");
      	            }
      	        }              
                $strSQL = "UPDATE software SET sparePart='$intSparePart', hardwareID=$intSystem, serial='$strSerial',
                  locationID=".makeNull($intLocationID).", vendorID=".makeNull($intVendorID).", roomName=".makeNull($strRoomName, TRUE)." WHERE
                  accountID=" . $_SESSION['accountID'] . " AND softwareID=$softwareID";
                $notify = "update";

            // If creating a new software instance
            } else {

                // If creating spare software, set extra variables
                $extraSQL1 = "";
                $extraSQL2 = "";
                if ($intUser == "spare") {
                    $extraSQL1  = ", locationID, roomName";
                    $extraSQL2  = ", $intLocationID, ".makeNull($strRoomName, TRUE);
                }
                $strSQL = "INSERT INTO software (serial, hardwareID, softwareTraitID, sparePart, vendorID, creationDate, accountID".$extraSQL1.")
                  VALUES ('$strSerial', $intSystem, $softwareTraitID, '$intSparePart',".makeNull($intVendorID).", NOW(), " . $_SESSION['accountID'] . "".$extraSQL2.")";
                $notify = "insert";
            }
            $result = dbquery($strSQL);

            if ($intUser != "spare") {
                redirect("showfull.php", "notify=$notify&hardwareID=$intSystem");
            } else {
                redirect("spareSoftware.php", "notify=$notify");
            }
        }

    // missing necessary variables; throw error.
    } else {
        $strError = $progText136;
    }

// Form was jscript submitted onChange of user/system/location selection, so get form
// data for re-display
} elseif ($formSignal) {
    if ($_SESSION['stuckAtLocation']) {
        $intLocationID = $_SESSION['locationStatus'];
    } else {
        $intLocationID  = cleanFormInput(getOrPost('cboLocationID'));
    }
    $oldLocationID  = cleanFormInput(getOrPost('oldLocationID'));
    $intUser        = cleanFormInput(getOrPost('cboUser'));
    $intSystem      = cleanFormInput(getOrPost('cboSystem'));
    $strRoomName    = cleanFormInput(getOrPost('txtRoomName'));
    $strSerial      = cleanFormInput(getOrPost('txtSerial'));
    $intVendorID    = cleanFormInput(getOrPost('cboVendorID'));

    // If they changed the location (but had a specific user selected), clear the user/system IDs,
    // since they may no longer be appropriate.
    If (($oldLocationID != $intLocationID) AND is_numeric($intUser)) {
        $intUser    = "";
        $intSystem  = "";
    } ElseIf ($oldLocationID != $intLocationID) {
        $intSystem  = "";
    }

// If you're editing a software, preload its data - but only once ($formSignal)
} elseif ($softwareID AND !$delete AND !$formSignal) {
    // Retrieve values from querystring
    $intUser = cleanFormInput(getOrPost('cboUser'));

    // Load up the software we're editing
    $strSQL = "SELECT * FROM software WHERE softwareID=$softwareID AND accountID=" . $_SESSION['accountID'] . "";
    $result = dbquery($strSQL);

    $row              = mysql_fetch_array($result);
    $strSerial        = $row["serial"];
    $intSystem        = $row["hardwareID"];
    $softwareTraitID  = $row["softwareTraitID"];
    $intLocationID    = $row['locationID'];
    $strRoomName      = $row['roomName'];
    $intSparePart     = $row['sparePart'];
    $intVendorID      = $row['vendorID'];

    // If software is not spare, retrieve the location of the system it is associated with
    If (!$intLocationID) {
        $strSQL2 = "SELECT h.locationID FROM hardware as h, software as s WHERE
          s.softwareID=$softwareID AND h.hardwareID=s.hardwareID AND s.accountID=" . $_SESSION['accountID'] . "";
        $result2 = dbquery($strSQL2);

        $row2           = mysql_fetch_array($result2);
        $intLocationID  = $row2['locationID'];
    }

    // If we're editing spare software, we need to make sure 'immovable' type
    // software can be moved, just this once, to its final home  ;)
    If ($intSparePart) {
        $moveOnce = 1;
    }

} Else {

    // retrieve values from querystring
    $intUser    = cleanFormInput(getOrPost('cboUser'));
    $intSystem  = cleanFormInput(getOrPost('cboSystem'));

    // If we're creating software of the 'immovable' type, this will prevent the page
    // from stifling system choice.
    $moveOnce = 1;
}

// Show license warning, one time (!getOrPost('btnSubmit')), if appropriate. Spare software doesn't
//   count against number of licenses left, so don't show warning ($intUser!="spare")
if (($numLicenses != "") AND ($numLicenses != "N/A") AND !getOrPost('btnSubmit') AND ($intUser != "spare")) {
    if ($numLicenses < 1) {
        fillError($progText152.": &nbsp;".$progText153);
    } elseif ($numLicenses == 1) {
        fillError($progText152.": &nbsp;".$progText154);
    } elseif ($numLicenses == 2) {
        fillError($progText152.": &nbsp;".$progText155);
    }
}

If ($softwareID) {
    $actionWord = $progText75; # edit
} Else {
    $actionWord = $progText76; # add
}

writeHeader($actionWord." ".$progText156);
declareError(TRUE);

// Begin form via which to add or edit a software instance.
if ($softwareTraitID) {

    $strSQL = "SELECT * FROM software_traits WHERE softwareTraitID=$softwareTraitID AND
      accountID=" . $_SESSION['accountID'] . " AND hidden='0'";
    $result = dbquery($strSQL);

    $row = mysql_fetch_array($result);
    $strName     = $row['visName'];
    $strMaker    = $row['visMaker'];
    $strVersion  = $row['visVersion'];
    $bolMovable  = $row['canBeMoved'];

    // If we're adding a software instance, pre-populate with universal type values (if any):
    If (!$softwareID) {
        $strSerial    = $row['universalSerial'];
        $intVendorID  = $row['universalVendorID'];
    }
    
?>
  <font color='ff0000'>*</font> <?=$progText13;?>.<p>

  <FORM METHOD="post" ACTION="admin_software.php" NAME="EditSoft" ID="EditSoft">
  <INPUT TYPE="hidden" NAME="formSignal" VALUE="1">
  <INPUT TYPE="hidden" NAME="softwareID" VALUE="<? echo $softwareID; ?>">
  <INPUT TYPE="hidden" NAME="softwareTraitID" VALUE="<? echo $softwareTraitID; ?>">
  <INPUT TYPE="hidden" NAME="oldLocationID" VALUE="<? echo $intLocationID; ?>">
<?
    // code below permits spare software of type "not movable" to be moved, just this once.
    if ($moveOnce) {
?>
      <INPUT TYPE="hidden" NAME="moveOnce" VALUE="1">
<?
    }
?>
    <table border='0' cellpadding='4' cellspacing='0' width='100%'>
<?
    // If adding new software, default (first time only; ie !$formSignal) to the current
    //   location selected for viewing by the user ($_SESSION['locationStatus']).
    if (!getOrPost('btnSubmit') AND !$softwareID AND !$formSignal) { $intLocationID = $_SESSION['locationStatus']; }

    // Show the required asterick if 'make spare part' is selected; location
    // is just a helpful filter in all other cases - not a requirement.
    if ($intUser == "spare") { $happyAsterick = "<font color='ff0000'>*</font> "; }

    if ((!$intSystem OR $softwareID) && !$_SESSION['stuckAtLocation']) {
?>
    <tr>
      <td width='101'><?=$happyAsterick;?><u><?=$progText34;?></u>: </td>
      <td><? buildLocationSelect($intLocationID, TRUE, "EditSoft"); ?></td>
    </tr>
    <tr>
<?
    }

    if ($bolMovable OR $moveOnce) {
?>
      <td width='101' valign='top'><font color='ff0000'>*</font> <u><?=$progText138;?></u>: </td>
      <td><? buildUserSystemSelect($intUser, $intSystem, $intLocationID, TRUE, "EditSoft"); ?></td>
<?
    } else {
       $strSQL2 = "SELECT * FROM hardware as h, hardware_types as t WHERE
         h.hardwareTypeID=t.hardwareTypeID AND h.hardwareID=$intSystem AND t.accountID=" . $_SESSION['accountID'] . "";
       $result2 = dbquery($strSQL2);

       While ($row2 = mysql_fetch_array($result2)) {
          $strSysName       = $row2["visDescription"];
          $strSysMaker      = $row2["visManufacturer"];
          $strSysSerial     = $row2["serial"];
          $strSysIP         = $row2["ipAddress"];
          $strSysHostname   = $row2["hostname"];
       }
       $strSystemDesc = writePrettySystemName($strSysName, $strSysMaker)."&nbsp; - &nbsp;".$progText37.": ".writeNA($strSysHostname)." &nbsp;-&nbsp; ".$progText157.": ".writeNA($strSysIP);
?>
      <td width='101' valign='top'><u><?=$progText138;?></u>: </td>
      <td><?=$strSystemDesc;?><br><font class='soft_instructions'>(<i><?=$progText158;?></i>)</font></td>
      <INPUT TYPE="hidden" NAME="cboSystem" VALUE="<? echo $intSystem; ?>">
<?
    }
?>
    </tr>
    <tr>
      <td width='101'><u><?=$progText1226;?></u>: </td>
      <td><? buildVendorSelect($intVendorID, TRUE, ""); ?></td>
    </tr>
    <tr>
      <td width='101' valign='top'><u><?=$progText159;?></u>: </td>
      <td><? echo $strName; ?></td>
    </tr>
    <tr>
      <td width='101' valign='top'><u><?=$progText120;?></u>: </td>
      <td><? echo writeNA($strMaker); ?></td>
    </tr>
    <tr>
      <td width='101' valign='top'><u><?=$progText160;?></u>: </td>
      <td><? echo writeNA($strVersion); ?></td>
    </tr>
    <tr>
      <td width='101' valign='top'><u><?=$progText44;?></u>: </td>
      <td><INPUT SIZE="30" MAXLENGTH="254" TYPE="Text" NAME="txtSerial" VALUE="<? echo antiSlash($strSerial); ?>"></td>
    </tr>
<?
    // If the new software is currently indicated to be "spare", we must show roomName field
    if ($intUser == "spare") {
?>
     <TR>
       <TD width='101'><u><?=$progText35;?></u>: </TD>
       <TD><INPUT SIZE="20" MAXLENGTH="40" TYPE="Text" NAME="txtRoomName" VALUE="<? echo antiSlash($strRoomName); ?>"></TD>
     </TR>
<?
    }
?>
    <tr>
      <td colspan='2'>&nbsp;
      </td>
    </tr>
     <tr>
      <td colspan='2'><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>">
      &nbsp;</td>
    </tr>
  </table>
  </FORM>
<?
}

// on first visit to page, make user select a software trait
if (!$softwareTraitID) {

    // retrieve values from querystring
    $intUser    = cleanFormInput(getOrPost('cboUser'));
    $intSystem  = cleanFormInput(getOrPost('cboSystem'));

    If (getOrPost('btnQuickFind')) {
        $strQuickFind = cleanFormInput(getOrPost('txtQuickFind'));
        $sqlCondition = "AND sr.visName LIKE '%$strQuickFind%'";
    }

    $strSQL = "SELECT sl.numLicenses, sl.licenseType, sr.softwareTraitID,
      sr.visName, sr.visMaker, sr.visVersion, v.vendorName
      FROM software_traits as sr
      LEFT JOIN software_traits_licenses as tl ON sr.softwareTraitID=tl.softwareTraitID
      LEFT JOIN software_licenses as sl ON tl.licenseID=sl.licenseID
      LEFT JOIN vendors as v ON v.vendorID=sr.universalVendorID
      WHERE sr.accountID=" . $_SESSION['accountID'] . " AND sr.hidden='0' $sqlCondition ORDER BY sr.visName ASC";
    $strSQL = determinePageNumber($strSQL);
    $result = dbquery($strSQL);
    $records = mysql_num_rows($result);
    If ($records == 0) {
        echo $progText163;
    } Else {
        echo "<p><a href='admin_software_types.php'>".$progText74."</a><p>";

        // If results will span more than one page, give user option to quick find as well
        // If user searched, give them quick find (again) in case the search did not turn
        // up what they were looking for.
        if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
?>
            <FORM METHOD="get" ACTION="admin_software.php">
             <input type='hidden' name='btnQuickFind' value='1'>
             <input type='hidden' name='cboSystem' value='<?=$intSystem;?>'>
             <input type='hidden' name='cboUser' value='<?=$intUser;?>'>
             <input type='hidden' name='hardwareTypeID' value='<?=$hardwareTypeID;?>'>
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
          <TD valign='bottom'><b><?=$progText173;?></b></TD>
          <TD valign='bottom'><b><?=$progText120;?></b></TD>
          <TD valign='bottom'><b><?=$progText1226;?></b></TD>
          <TD valign='bottom'><b><?=$progText160;?></b></TD>
          <TD valign='bottom'><b>&nbsp;<?=$progText161;?>&nbsp;<br>&nbsp;<?=$progText162;?></b></TD>
          <TD valign='bottom'><b><?=$progText79;?></b></TD>
        </TR>
<?
        while ($row = mysql_fetch_array($result)) {
           $softwareTraitID  = $row['softwareTraitID'];
           $strName          = $row['visName'];
           $strMaker         = $row['visMaker'];
           $strVersion       = $row['visVersion'];
           $numLicenses      = $row['numLicenses'];
           $licenseType      = $row['licenseType'];
           $strVendorName    = $row['vendorName'];

           If ($licenseType == "peruser") {
              $strSQL2 = "SELECT t.id FROM software as s, tblSecurity as t, hardware as h,
                 software_traits as st WHERE s.softwareTraitID=st.softwareTraitID AND
                 st.softwareTraitID=".$row["softwareTraitID"]." AND s.hardwareID=h.hardwareID AND
                 h.userID=t.id AND s.sparePart='0' AND h.sparePart='0' AND t.hidden='0' AND t.accountID=" . $_SESSION['accountID'] . "
                 AND (s.hidden='0' OR s.hidden='2') GROUP BY t.id";
              $result2            = dbquery($strSQL2);
              $usedLicenses       = mysql_num_rows($result2);
              $remainingLicenses  = $numLicenses - $usedLicenses;

           } ElseIf ($licenseType == "persystem") {
              $strSQL2 = "SELECT h.hardwareID FROM software as s, hardware as h, software_traits as st
                 WHERE s.softwareTraitID=st.softwareTraitID AND st.softwareTraitID=".$row["softwareTraitID"]." AND
                 s.hardwareID=h.hardwareID AND s.sparePart='0' AND h.accountID=" . $_SESSION['accountID'] . " AND
                 (s.hidden='0' OR s.hidden='2') GROUP BY h.hardwareID";
              $result2            = dbquery($strSQL2);
              $usedLicenses       = mysql_num_rows($result2);
              $remainingLicenses  = $numLicenses - $usedLicenses;
           } Else {
              $remainingLicenses  = "N/A";
           }
?>
           <TR class='<?=alternateRowColor()?>'>
             <TD><? echo $strName ?> &nbsp;</TD>
             <TD><? echo writeNA($strMaker); ?> &nbsp;</TD>
             <TD><? echo writeNA($strVendorName); ?> &nbsp;</TD>
             <TD><? echo writeNA($strVersion); ?> &nbsp;</TD>
             <TD>&nbsp;<? echo $remainingLicenses ?> &nbsp;</TD>
             <TD>
<?
           // If hardwareTypeID exists, we are assigning default software to a system type.
           // otherwise, we are adding a software instance to the database.
           If ($hardwareTypeID) {
               echo "<A HREF='admin_hw_types.php?hardwareTypeID=$hardwareTypeID&softwareTraitID=$softwareTraitID'>".$progText76."</A>\n";
           } Else {
               echo "<A HREF='admin_software.php?cboSystem=$intSystem&softwareTraitID=$softwareTraitID&numLicenses=$remainingLicenses&cboUser=$intUser'>".$progText76."</A>\n";
           }
?>
             </TD>
           </TR>
<?
        }

        echo "</table>\n";
        createPaging();
        if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
            echo "\n</FORM>";
        }
    }
}

writeFooter();
?>
