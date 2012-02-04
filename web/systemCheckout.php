<?
	Include("Includes/global.inc.php");
	checkPermissions(2, 1800);

    Function validateDueDate ($fieldName, $dateVal, $strUser) {
	    global $strError, $progText540, $progText1214, $progText32;
        If (($dateVal == "mm/dd/yyyy") || ($dateVal == "dd/mm/yyyy")) {
            $dateVal = "";
        }

	    if ($dateVal) {
		    if (!is_numeric($strUser)) {
			    fillError($progText1214);
		    }
  	    }
  	    Return $dateVal;
    }

	global $strOverallError, $recCount, $dbArray;
	$dbArray = array();

	$sqlLocation = "";
	If (is_numeric($_SESSION['locationStatus'])) {
		$sqlLocation = " AND h.locationID=" . $_SESSION['locationStatus'] . " ";
	}

	$strSQLNormalCase = "SELECT h.hardwareID, ht.visDescription, ht.visManufacturer, h.hostname, h.dueDate,
      h.userID, h.locationID, l.locationName
      FROM (hardware as h, hardware_types as ht)
      LEFT JOIN locations as l ON h.locationID=l.locationID
      WHERE h.hardwareTypeID=ht.hardwareTypeID AND h.dueDate IS NOT NULL $sqlLocation
        AND h.accountID=" . $_SESSION['accountID'] . " ORDER BY h.dueDate ASC";

	# strLocationName contains the Location name to be shown in the page title
	$strSQLlocation = "SELECT * FROM locations WHERE locationID=" . $_SESSION['locationStatus'] . " AND accountID=" . $_SESSION['accountID'] . "";
	$strLocationName = fetchLocationName($strSQLlocation);

	# class representing a checked out system
	class CheckedoutSystem
	{
		var $hardwareID;
		var $userID;
		var $dueDate;
		var $description;
		var $manufacturer;
		var $hostname;
		var $locationID;
		var $locationName;
		var $validationErrorUserID;
		var $validationErrorDueDate;

		# Constructor
		Function CheckedoutSystem($p_row)
		{
			$this->hardwareID = $p_row[0];
			$this->description = $p_row[1];
			$this->manufacturer = $p_row[2];
			$this->hostname = writeNA($p_row[3]);
			$this->dueDate = $p_row[4];
			$this->userID = $p_row[5];
			$this->locationID = $p_row[6];
			$this->locationName = $p_row[7];
		}

		Function printRowFromDB()
		{
			$this->printRow($this->userID, $this->dueDate);
		}

		Function printRow($p_userID, $p_dueDate)
		{
			?>
			<TR class='<? echo alternateRowColor(); ?>'>
			<TD valign='top'><a href='showfull.php?hardwareID=<?=$this->hardwareID;?>'><?=$this->hostname;?></a>&nbsp;</TD>
			<TD valign='top'>
			<?
			$currDate=date("m/d/Y");
			if (dateDiff('d', $currDate, displayDate($this->dueDate), false) < 0)
			{
				echo "<font color='red'><b>";
			}
            echo writePrettySystemName(($this->description), ($this->manufacturer))."&nbsp;";
            ?>
			</TD>
			<TD valign='top'><?=$this->locationName;?>&nbsp;</TD>
			<TD valign='top'><nobr>
			<?
				if ($this->validationErrorUserID == 1)
					echo "<font color='red' size='+1'><b>**</b></font> ";
				echo buildUserSelect($p_userID, TRUE, $this->locationID, "", "", "", "cboUser$this->hardwareID");
			?>&nbsp;
			</nobr></TD>
			<TD valign='top'><nobr>
			<?
				if ($this->validationErrorDueDate == 1)
					echo " <font color='red' size='+1'><b>**</b></font> ";
				buildDate("txtDueDate".$this->hardwareID, $p_dueDate);
			?>&nbsp;
			</nobr></TD>
			<TD valign='top'><nobr><input type=checkbox name="chkCheckedin<?=$this->hardwareID;?>"
			onClick="javascript:setDueDate('<?=$this->hardwareID?>', this, '<?=$p_userID?>', '<?=displayDate($p_dueDate)?>')">
			<input type='hidden' name='txtID[]' value="<?=$this->hardwareID;?>">&nbsp;
			</nobr></TD>
			</TR>
			<?
		} # end function printRow
	} # end class CheckedoutSystem

	# this function prints form begin tag
	function startForm()
	{
		global $progText37, $progText33, $progText743, $progText421A, $progText34, $progText1213;
		?>
		<FORM METHOD="POST" NAME="checkout" ACTION="systemCheckout.php">
			<p><table border='0' cellpadding='4' cellspacing='0' width='100%'>
				<TR class='title'>
					<TD valign='top'><nobr><b><?=$progText37;?></b> &nbsp;</nobr></TD>
					<TD valign='top'><nobr><b><?=$progText33;?></b> &nbsp;</nobr></TD>
					<TD valign='top'><nobr><b><?=$progText34;?></b> &nbsp;</nobr></TD>
					<TD valign='top'><nobr><b><?=$progText743;?></b> &nbsp;</nobr></TD>
					<TD valign='top'><nobr><b><?=$progText421A;?></b> &nbsp;</nobr></TD>
					<TD valign='top'><nobr><b><?=$progText1213;?></b>&nbsp;</nobr></TD>
				</TR>
		<?
	}

	# this function prints form end tag
	function endForm() {
	    global $progText21;
		?>
				<TR><TD colspan='6'>&nbsp;</TD></TR>
				<TR>
					<TD colspan='6'><input type='Submit' name='btnSubmit' value='<?=$progText21;?>'></TD>
				</TR>
			</table>
		</FORM>
		<?
	}

	writeHeader(($progText1212." ".$strLocationName), "", TRUE);
?>
    <SCRIPT LANGUAGE="javascript">
    <!--
    function setDueDate(hardwareID, obj, oldUser, oldDueDate)
    {
        if (obj.checked)
        {
            document.checkout.elements['txtDueDate'+hardwareID].value="";
            document.checkout.elements['cboUser'+hardwareID].value="spare";
            // document.checkout.elements['txtDueDate'+hardwareID].disabled=true;
            // document.checkout.elements['cboUser'+hardwareID].disabled=true;
        } else {
            document.checkout.elements['txtDueDate'+hardwareID].value=oldDueDate;
            document.checkout.elements['cboUser'+hardwareID].value=oldUser;
            // document.checkout.elements['txtDueDate'+hardwareID].disabled=false;
            // document.checkout.elements['cboUser'+hardwareID].disabled=false;
        }
    }
    //-->
    </script>
<?

	if (getOrPost('btnSubmit'))
	{
		# if the form was submitted
		$strID = implode(",", getOrPost('txtID'));
		$strSQL = "SELECT h.hardwareID, ht.visDescription, ht.visManufacturer, h.hostname,
          h.dueDate, h.userID, h.locationID, l.locationName
          FROM (hardware as h, hardware_types as ht)
          LEFT JOIN locations as l ON h.locationID=l.locationID
          WHERE h.hardwareTypeID = ht.hardwareTypeID AND h.hardwareID IN ($strID) $sqlLocation
          AND h.accountID=" . $_SESSION['accountID'] . " ORDER BY h.dueDate ASC";
	}
	else
	{
		# if form is shown for the first time
		$strSQL = $strSQLNormalCase;
	}

	$result = mysql_query($strSQL);
	$recCount = mysql_num_rows($result);

	# populate dbArray
	$i = 0;
	while ($row=mysql_fetch_array($result))
	{
		$dbArray[$i] = new CheckedoutSystem($row);
		$i = $i + 1;
	}

	$strOverallError = 0;
	$strHardwareID = "";
	$strDueDate = "";

	# if form was submitted
    if (getOrPost('btnSubmit'))
    {
        # validate form
        $dbArraySize = sizeof($dbArray);

		$i = 0;
        while ($i < $dbArraySize)
        {
            $strHardwareID = $dbArray[$i]->hardwareID;

			# if "checkedIn" checkbox is not checked
            if ($_REQUEST['chkCheckedin'.$strHardwareID] != "on")
            {
				# validate if user is not blank or spare or individual IF due date is specified
                $cboUser = validateChoice($progText32, $_REQUEST['cboUser'.$strHardwareID] );
                $strDueDate = validateDueDate($progText421A, $_REQUEST['txtDueDate'.$strHardwareID], $cboUser);
                if ($strError != "")
                {
                    $strOverallError = 1;
                    $dbArray[$i]->validationErrorUserID = 1;
                    $strError = "";
                }

				# validare due date
                $strDueDate = validateDate($progText421A, $_REQUEST['txtDueDate'.$strHardwareID] , 1900, (date("Y")+1), FALSE);
                if  ($strError != "")
                {
                    $strOverallError = 1;
                    $dbArray[$i]->validationErrorDueDate = 1;
                    $strError = "";
                }
            }
            $i = $i + 1;
        } # end while

        # if there was no validation error
        if ($strOverallError == 0)
        {
            $dbArraySize = sizeof($dbArray);
            foreach ($dbArray as $row)
            {
                $strHardwareID = $row->hardwareID;
                $cboUser = validateChoice($progText32,$_REQUEST['cboUser'.$strHardwareID] );
                $strDueDate = validateDate($progText421A, $_REQUEST['txtDueDate'.$strHardwareID] , 1900, (date("Y")+1), FALSE);

                if ($_REQUEST['chkCheckedin'.$strHardwareID] == "on")
                {
                    $cboUser = "spare";
                }

                # check if DB needs update
                if (($cboUser == $row->userID) && ($strDueDate == displayDate($row->dueDate)))
                {
                    # no update is required
                }
                else
                {
                    # update DB
                    If ($cboUser == "spare") {
                        $cboUser  = "NULL";
                        $spare    = "1";
                    } ElseIf  ($cboUser == "independent") {
                        $cboUser = "NULL";
                        $spare = "2";
                    } ElseIf  ($cboUser == "adminDefined") {
                        $cboUser = "NULL";
                        $spare = "3";
                    } Else {
                        $spare = "0";
                    }

                    $strSql = "UPDATE hardware SET userID=$cboUser, sparePart='$spare', dueDate=" . dbDate($strDueDate) . " WHERE hardwareID=$strHardwareID AND accountID=" . $_SESSION['accountID'] . "";
                    mysql_query($strSql);
                    $strError = $progText71;
                }
            } # end foreach

            # populate dbArray with new values after update
            unset($dbArray);
            $strSQL = $strSQLNormalCase;
            $result = mysql_query($strSQL);
            $recCount = mysql_num_rows($result);

			# populate dbArray
			$i = 0;
            while ($row = mysql_fetch_array($result))
            {
                $dbArray[$i] = new CheckedoutSystem($row);
                $i = $i + 1;
            }
        } # end if
        # else if there was atleast one validation error
        else
        {
            $strError = $progText1215;
            declareError(TRUE);
            startForm();
            foreach ($dbArray as $row)
            {
                $strHardwareID = $row->hardwareID;
                $row->printRow($_REQUEST['cboUser'.$strHardwareID], $_REQUEST['txtDueDate'.$strHardwareID]);
            }
            endForm();
        } // end else
    } // end if

    # if there was no record found display the message.
    if ($recCount == 0)
        echo $progText1217;

    # if page is displayed first time OR page is successfully updated, show form
    if (($strOverallError == 0) && ($recCount > 0))
    {
        declareError(TRUE);
        echo "<font class='soft_instructions'>".$progText1216."</font>";
        startForm();
        foreach ($dbArray as $row)
        {
            $row->printRowFromDB();
        }
        endForm();
    }

	writeFooter();
?>
