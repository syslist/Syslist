<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

  $formSignal  = getOrPost('formSignal');
  $spare       = getOrPost('spare');
  
  // If we're adding (not editing) a system...
  $id = cleanFormInput(getOrPost('id'));
  if (!$id) {
      $strSQL_license  = "Select decode('".addslashes(base64_decode($licenseKey))."','Development*Test.Key')";
      $result_license  = dbquery($strSQL_license);
      $row_license     = mysql_fetch_row($result_license);

      $commaCount = substr_count($row_license[0], ",");
      If ($commaCount !== 3) {
          $strError = $progTextZZ1;
          $licenseFailure = 1;
      } Else {
          $aryLicense = explode(",", $row_license[0]);
      }

      If (!$licenseFailure AND !is_numeric($aryLicense[3])) {
          $strError = $progTextZZ1;
          $licenseFailure = 1;
      }

      If (!$licenseFailure) {
          $strSQL_count  = "SELECT count(*) FROM hardware";
          $result_count  = dbquery($strSQL_count);
          $row_count     = mysql_fetch_row($result_count);

          $systemsRemaining = $aryLicense[3] - $row_count[0];
          If ($systemsRemaining < 1) {
              $strError = $progTextBlock9." (".$aryLicense[3].")";
              $limitReached = TRUE;
          }
      }
  }

  if (getOrPost('btnSubmit') AND !$limitReached) { // the form has been submitted
      if ($_SESSION['stuckAtLocation']) {
          $cboLocationID = $_SESSION['locationStatus'];
      } else {
          $cboLocationID = validateChoice($progText34, getOrPost('cboLocationID'));
      }
      $cboUser          = validateChoice($progText32, getOrPost('cboUser'));
      $cboType          = validateChoice($progText33, getOrPost('cboType'));
      $cboVendorID      = cleanFormInput(getOrPost('cboVendorID'));
      $strRoomName      = validateText($progText35, getOrPost('txtRoomName'), 1, 40, FALSE, FALSE);
      $strHW_Serial     = validateText($progText36, getOrPost('txtHW_Serial'), 1, 254, FALSE, FALSE);
      $strHostname      = validateText($progText37, getOrPost('txtHostname'), 1, 40, FALSE, FALSE);
      $strAssetTag      = validateText($progText420, getOrPost('txtAssetTag'), 1, 254, FALSE, FALSE);
      $strPurchasePrice = validateExactNumber($progText424, getOrPost('txtPurchasePrice'), 0, 99999999, FALSE, 2);
	  $strDueDate		= validateDate($progText421A, getOrPost('txtDueDate'), 1900, (date("Y")+20), FALSE);
      $strPurchaseDate  = validateDate($progText421, getOrPost('txtPurchaseDate'), 1900, (date("Y")+1), FALSE);
      $strWarrantyDate  = validateDate($progText422, getOrPost('txtWarrantyDate'), 1900, (date("Y")+90), FALSE);
	  $strNicMac1       = validateText($progText423." 1", getOrPost('txtNicMac1'), 1, 40, FALSE, FALSE);
	  $strNicMac2       = validateText($progText423." 2", getOrPost('txtNicMac2'), 1, 40, FALSE, FALSE);
      $strOther1        = validateText($extraSystemField, getOrPost('txtOther1'), 1, 254, FALSE, FALSE);
      $strIP            = validateIP("1", FALSE);

	  if (!$strError AND $strDueDate) {
          if (!is_numeric($cboUser)) {
              fillError($progText1214);
          }	
      }	
		
      if (!$strError AND !$id AND $strHW_Serial) {
          $strSQLerr = "SELECT COUNT(*) FROM hardware WHERE hardwareTypeID=$cboType AND accountID=" . $_SESSION['accountID'] . " AND
            serial='$strHW_Serial'";
      } elseif (!$strError AND $id AND $strHW_Serial) {
          $strSQLerr = "SELECT COUNT(*) FROM hardware WHERE hardwareTypeID=$cboType AND accountID=" . $_SESSION['accountID'] . " AND
            serial='$strHW_Serial' AND hardwareID!=$id";
      }
      If (!$strError AND $strHW_Serial) {
          $resulterr = dbquery($strSQLerr);
          $rowerr = mysql_fetch_row($resulterr);
          If ($rowerr[0] > 0) {
              $strError = $progText39;
          }
      }

      if (!$strError AND !$id AND $strHostname) {
          $strSQLerr = "SELECT COUNT(*) FROM hardware WHERE accountID=" . $_SESSION['accountID'] . " AND
            hostname='$strHostname'";
      } elseif (!$strError AND $id AND $strHostname) {
          $strSQLerr = "SELECT COUNT(*) FROM hardware WHERE accountID=" . $_SESSION['accountID'] . " AND
            hostname='$strHostname' AND hardwareID!=$id";
      }
      If (!$strError AND $strHostname) {
          $resulterr = dbquery($strSQLerr);
          $rowerr = mysql_fetch_row($resulterr);
          If ($rowerr[0] > 0) {
              $strError = $progText40;
          }
      }

      if (!$strError) {
          If ($cboUser == "spare") {
              $cboUser  = "NULL";
              $spare    = "1";
          } ElseIf  ($cboUser == "independent") {
              $cboUser  = "NULL";
              $spare    = "2";
          } ElseIf  ($cboUser == "adminDefined") {
              $cboUser  = "NULL";
              $spare    = "3";
          } Else {
              $spare = "0";
          }

          if ($id) {
             $strSQL = "UPDATE hardware SET userID=$cboUser, sparePart='$spare', hostname=".makeNull($strHostname, TRUE).",
               hardwareTypeID=$cboType, locationID=$cboLocationID, roomName=".makeNull($strRoomName, TRUE).",
               serial=".makeNull($strHW_Serial, TRUE).", ipAddress=".makeNull($strIP, TRUE).",
               assetTag=".makeNull($strAssetTag, TRUE).", lastManualUpdate='".date("Ymd")."', lastManualUpdateBy=" . $_SESSION['userID'] . ",
               dueDate=".dbDate($strDueDate).", purchaseDate=".dbDate($strPurchaseDate).", warrantyEndDate=".dbDate($strWarrantyDate).",
               other1=".makeNull($strOther1, TRUE).", purchasePrice=".makeNull($strPurchasePrice).", nicMac1=".makeNull($strNicMac1, TRUE).",
               nicMac2=".makeNull($strNicMac2, TRUE).", vendorID=".makeNull($cboVendorID)." WHERE
               hardwareID=$id AND accountID=" . $_SESSION['accountID'] . "";
             $strNotify = "notify=update";
          } else {
             $strSQL = "INSERT INTO hardware (hostname, hardwareTypeID, locationID, roomName, serial,
               userID, ipAddress, sparePart, assetTag, lastManualUpdate, lastManualUpdateBy, dueDate, purchaseDate,
               warrantyEndDate, other1, purchasePrice, vendorID, accountID, nicMac1, nicMac2)
               VALUES (".makeNull($strHostname, TRUE).", $cboType, $cboLocationID, ".makeNull($strRoomName, TRUE).",
               ".makeNull($strHW_Serial, TRUE).", $cboUser, ".makeNull($strIP, TRUE).", '$spare',
               ".makeNull($strAssetTag, TRUE).", '".date("Ymd")."', " . $_SESSION['userID'] . ", ".dbDate($strDueDate).", 
               ".dbDate($strPurchaseDate).", ".dbDate($strWarrantyDate).", ".makeNull($strOther1, TRUE).", 
               ".makeNull($strPurchasePrice).", ".makeNull($cboVendorID).", " . $_SESSION['accountID'] . ", ".makeNull($strNicMac1, TRUE).",
               ".makeNull($strNicMac2, TRUE).")";
             $strNotify = "notify=insert";
          }
          $result = dbquery($strSQL);

          // If inserting new hardware, retrieve the unique id and use it to add all appropriate
          // peripheral and software default instances, as well as to finish building $strNotify
          if (!$id) {
              $id = mysql_insert_id($db);

              // Retrieve all default peripheral and software IDs, to assign them with this
              // new hardware
              $strSQL2 = "SELECT * FROM hardware_type_defaults WHERE accountID=" . $_SESSION['accountID'] . " AND
                hardwareTypeID=$cboType";
              $result2 = dbquery($strSQL2);

              while ($row2 = mysql_fetch_array($result2)) {
                  // If record relates to a default peripheral, add peripheral instance for
                  // this hardware
                  If ($row2["objectType"] == 'p') {
                      $strSQL3 = "INSERT INTO peripherals (hardwareID, peripheralTraitID, sparePart,
                        accountID) VALUES ($id, ".$row2["objectID"].", '0', " . $_SESSION['accountID'] . ")";
                      $result3 = dbquery($strSQL3);

                  // Software...
                  } ElseIf ($row2["objectType"] == 's') {
                      $strSQL3 = "INSERT INTO software (hardwareID, softwareTraitID, sparePart, creationDate,
                        accountID) VALUES ($id, ".$row2["objectID"].", '0', NOW(), " . $_SESSION['accountID'] . ")";
                      $result3 = dbquery($strSQL3);
                  }
              }
          }
          $strNotify .= "&hardwareID=$id";
          redirect("showfull.php", $strNotify);
      }

  // form was jscript submitted onChange of user selection, so get form data for re-display
  } elseif ($formSignal) {
        if ($_SESSION['stuckAtLocation']) {
            $cboLocationID = $_SESSION['locationStatus'];
        } else {
            $cboLocationID = cleanFormInput(getOrPost('cboLocationID'));
        }
        $cboUser          = cleanFormInput(getOrPost('cboUser'));
        $cboType          = cleanFormInput(getOrPost('cboType'));
        $cboVendorID      = cleanFormInput(getOrPost('cboVendorID'));
        $strRoomName      = cleanFormInput(getOrPost('txtRoomName'));
        $strHW_Serial     = cleanFormInput(getOrPost('txtHW_Serial'));
        $strHostname      = cleanFormInput(getOrPost('txtHostname'));
        $strAssetTag      = cleanFormInput(getOrPost('txtAssetTag'));
        $strPurchasePrice = cleanFormInput(getOrPost('txtPurchasePrice'));
        $strDueDate       = cleanFormInput(getOrPost('txtDueDate'));
        $strPurchaseDate  = cleanFormInput(getOrPost('txtPurchaseDate'));
        $strWarrantyDate  = cleanFormInput(getOrPost('txtWarrantyDate'));
        $strOther1        = cleanFormInput(getOrPost('txtOther1'));
		$strNicMac1		  = cleanFormInput(getOrPost('txtNicMac1'));
		$strNicMac2       = cleanFormInput(getOrPost('txtNicMac2'));
        $strIP            = validateIP("1", FALSE);

        $lastAgentUpdate  = cleanFormInput(getOrPost('lastAgentUpdate'));

  // load the data for the system we are editing
  } elseif ($id) {
        If ($spare === "0") {
           $strSQL = "SELECT * FROM hardware as h, hardware_types as t, tblSecurity as s WHERE
             s.id=h.userID AND h.hardwareTypeID=t.hardwareTypeID AND h.hardwareID=$id AND t.accountID=" . $_SESSION['accountID'] . "";
        } Else {
           $strSQL = "SELECT * FROM hardware as h, hardware_types as t WHERE
             h.hardwareTypeID=t.hardwareTypeID AND h.hardwareID=$id AND t.accountID=" . $_SESSION['accountID'] . "";
        }
        $result = dbquery($strSQL);

        While ($row = mysql_fetch_array($result)) {
            $strHostname      = $row["hostname"];
            $cboType          = $row["hardwareTypeID"];
            $cboVendorID      = $row["vendorID"];
            $cboLocationID    = $row["locationID"];
            $strRoomName      = $row["roomName"];
            $strHW_Serial     = $row["serial"];
            $spare            = $row["sparePart"];
            $strHwStatus      = $row["hardwareStatus"];
            $strIP            = $row["ipAddress"];
            $strAssetTag      = $row["assetTag"];
            $strPurchasePrice = $row["purchasePrice"];
            $strDueDate		  = $row["dueDate"];
            $strPurchaseDate  = $row["purchaseDate"];
            $strWarrantyDate  = $row["warrantyEndDate"];
            $strOther1        = $row["other1"];
			$strNicMac1       = $row["nicMac1"];
			$strNicMac2       = $row["nicMac2"];
			$lastAgentUpdate  = $row["lastAgentUpdate"];
            
            If ($spare === "0") {
                $cboUser      = $row["id"];
            }
        }

  } else { # we're creating a new system; set location to whatever default is currently set by user.
      $cboLocationID = $_SESSION['locationStatus'];
  }

  if ($id) {
      $pageTitle = $progText41;
  } else {
      $pageTitle = $progText42;
  }

  $strSQLz = "SELECT * FROM hardware_types WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY visDescription ASC";
  $resultz = dbquery($strSQLz);
  if (mysql_num_rows($resultz) == 0) {
      fillError($progTextBlock6);
      $missingTypes = TRUE;
  }

  $strSQLy = "SELECT locationID FROM locations WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY locationName ASC";
  $resulty = dbquery($strSQLy);
  if (mysql_num_rows($resulty) == 0) {
      fillError($progTextBlock7);
      $missingTypes = TRUE;
  // For convenience' sake, if this account only has one location, select it for the user
  } elseif ((mysql_num_rows($resulty) == 1) AND !getOrPost('btnSubmit') AND !getOrPost('formSignal')) {
      $rowy = mysql_fetch_row($resulty);
      $cboLocationID = $rowy[0];
  }

  writeHeader($pageTitle);
  declareError(TRUE);

  If (!$missingTypes AND !$limitReached) {

?>
<font color='ff0000'>*</font> <?=$progText13;?>.<p>

<FORM METHOD="post" ACTION="admin_hardware.php" name="frmHardware" id="frmHardware">
<INPUT TYPE="hidden" NAME="formSignal" VALUE="1">
<TABLE border='0' width='100%' cellpadding='4' cellspacing='0'>
<? if (!$_SESSION['stuckAtLocation']) { ?>
   <TR>
      <TD width='120'><font color='ff0000'>*</font> <?=$progText34;?>:</TD>
      <TD>
         <? buildLocationSelect($cboLocationID, TRUE, "frmHardware"); ?>
      </TD>
   </TR>
<? } ?>
   <TR>
      <TD width='120'><font color='ff0000'>*</font> User:</TD>
      <TD><?
    if (!getOrPost('btnSubmit')) {
        if ($spare === "1") {
            $cboUser = "spare";
        } elseif ($spare === "2") {
            $cboUser = "independent";
        } elseif ($spare === "3") {
            $cboUser = $adminDefinedCategory;
        }
    }
    echo buildUserSelect($cboUser, TRUE, $cboLocationID);
    ?> &nbsp;<a href='createUser.php'><?=$progText508;?></a></TD>
   </TR>
   <TR>
      <TD width='120'><font color='ff0000'>*</font> <?=$progText33;?>:</TD>
      <TD>
         <SELECT SIZE="1" NAME="cboType" >
            <OPTION VALUE=''>&nbsp;</OPTION>
             <?
             // Get all hardware types for the drop down menu
             while ($rowz = mysql_fetch_array($resultz)) {
                  echo "   <OPTION VALUE=\"".$rowz['hardwareTypeID']."\" ";
                  echo writeSelected($cboType, $rowz['hardwareTypeID']);
                  echo ">".writePrettySystemName($rowz['visDescription'], $rowz['visManufacturer'])."</OPTION>\n";
             }
             ?>
         </SELECT> &nbsp;<a href='admin_hw_types.php'><?=$progText43;?></a>
      </TD>
   </TR>
   <TR>
      <TD width='120'><?=$progText1226;?>:</TD>
      <TD>
         <? buildVendorSelect($cboVendorID, TRUE, ""); ?>
      </TD>
   </TR>
   <TR>
      <TD width='120'><?=$progText35;?>:</TD>
      <TD><INPUT SIZE="20" MAXSIZE="20" TYPE="Text" NAME="txtRoomName" VALUE="<? echo antiSlash($strRoomName); ?>"></TD>
   </TR>
   <TR>
      <TD width='120'><?=$progText44;?>:</TD>
      <TD><INPUT SIZE="30" MAXLENGTH="254" TYPE="Text" NAME="txtHW_Serial" VALUE="<? echo antiSlash($strHW_Serial); ?>"></TD>
   </TR>
   <TR>
      <TD width='120'><?=$progText37;?>:</TD>
      <TD><INPUT SIZE="30" MAXLENGTH="40" TYPE="Text" NAME="txtHostname" VALUE="<? echo antiSlash($strHostname); ?>"></TD>
   </TR>
   <TR>
      <TD width='120'><?=$progText420;?>:</TD>
      <TD><INPUT SIZE="30" MAXLENGTH="254" TYPE="Text" NAME="txtAssetTag" VALUE="<? echo antiSlash($strAssetTag); ?>"></TD>
   </TR>
   <TR>
      <TD width='120'><?=$progText45;?>:</TD>
      <TD><? buildIP($strIP, "1"); ?></TD>
   </TR>
<?
   // allow edit of NIC MAC only if this system was not originally created by the Agent
   if (!$lastAgentUpdate) {
       echo "<TR><TD width='120'>".$progText423." 1</TD><TD><INPUT SIZE='20' MAXSIZE='20' TYPE='Text' NAME='txtNicMac1' VALUE='".antiSlash($strNicMac1)."'/></TD></TR>\n";
       echo "<TR><TD width='120'>".$progText423." 2</TD><TD><INPUT SIZE='20' MAXSIZE='20' TYPE='Text' NAME='txtNicMac2' VALUE='".antiSlash($strNicMac2)."'/></TD></TR>\n";
   } else {
       echo "<input type='hidden' name='txtNicMac1' value='".antiSlash($strNicMac1)."'>\n";
       echo "<input type='hidden' name='txtNicMac2' value='".antiSlash($strNicMac2)."'>\n";
       echo "<input type='hidden' name='lastAgentUpdate' value='".antiSlash($lastAgentUpdate)."'>\n";
   }
?>
   <TR>
      <TD width='120'><?=$progText424;?>:</TD>
      <TD><INPUT SIZE="10" MAXLENGTH="11" TYPE="Text" NAME="txtPurchasePrice" VALUE="<? echo antiSlash($strPurchasePrice); ?>"></TD>
   </TR>

  <TR>
      <TD width='120'><?=$progText421A;?>:</TD>
      <TD><? buildDate('txtDueDate', $strDueDate); ?></TD>
   </TR>

   <TR>
      <TD width='120'><?=$progText421;?>:</TD>
      <TD><? buildDate('txtPurchaseDate', $strPurchaseDate); ?></TD>
   </TR>
   <TR>
      <TD width='120'><?=$progText422;?>:</TD>
      <TD><? buildDate('txtWarrantyDate', $strWarrantyDate); ?></TD>
   </TR>
   
   <? If ($extraSystemField) { ?>
       <TR>
          <TD width='120'><?=$extraSystemField;?>:</TD>
          <TD><INPUT SIZE="30" MAXLENGTH="254" TYPE="Text" NAME="txtOther1" VALUE="<? echo antiSlash($strOther1); ?>"></TD>
       </TR>
   <? } ?>
   
   <TR><TD colspan='2'>&nbsp;</TD></TR>

   <TR>
      <TD colspan='2'><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>"></TD>
   </TR>
  </TABLE>

  <input type='hidden' name='spare' value='<?=$spare;?>'>
  <input type="hidden" name="id" value="<?=$id;?>">
</FORM>

<?
  }
  writeFooter();
?>
