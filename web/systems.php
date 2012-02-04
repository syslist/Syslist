<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  $notify = getOrPost('notify');
  notifyUser($notify);

  If (is_numeric($_SESSION['locationStatus'])) {
      $sqlLocation1 = "l.locationCode, ";
      $sqlLocation2 = "locations as l, ";
      $sqlLocation3 = "l.locationID=h.locationID AND h.locationID=" . $_SESSION['locationStatus'] . " AND";
  }

  $systemStatus = cleanFormInput(getOrPost('systemStatus'));
  If ($systemStatus) {
      $sqlStatus = "h.hardwareStatus='$systemStatus' AND";
  }

  $spare = cleanFormInput(getOrPost('spare'));
  If ($spare != "") {
      $sqlSpare = " AND h.sparePart='$spare'";
  }

  // If user used 'quick find', process extra sql and prepare an array of querystring
  // paramaters that will be used to preserve the user's search, in the event that
  // they click sort links after searching.
  If (getOrPost('btnQuickFind')) {
      $strQuickFind   = cleanFormInput(getOrPost('txtQuickFind'));
      $strQuickFind2  = cleanFormInput(getOrPost('cboQuickFind'));

      $sqlQuickFind = "AND $strQuickFind2 LIKE '%$strQuickFind%'";
  }

  $strSQL = "SELECT $sqlLocation1 t.visDescription, t.visManufacturer, h.hostname, h.hardwareID, h.locationID,
    h.roomName, s.firstName, s.middleInit, s.lastName, s.id, h.hardwareStatus, h.ipAddress, h.sparePart
    FROM ($sqlLocation2 hardware as h, hardware_types as t)
    LEFT JOIN tblSecurity as s ON s.id=h.userID
    WHERE $sqlStatus $sqlLocation3 h.accountID=" . $_SESSION['accountID'] . " AND h.hardwareTypeID=t.hardwareTypeID
    $sqlSpare $sqlQuickFind ORDER BY h.sparePart ASC, s.lastName ASC, s.firstName ASC, t.visDescription ASC";

  writeHeader("","",TRUE);
  declareError(TRUE);
?>

<table width='100%' border='0' cellpadding='0' cellspacing='0'>
<tr>
  <td class='smaller' align='left'>
     <b><?=$progText478;?>:</b>
         &nbsp;<nobr><?writeActiveLink("systems.php?spare=0&systemStatus=$systemStatus", $progText32, $spare, "0");?> &nbsp;|&nbsp;
         <?writeActiveLink("systems.php?spare=1&systemStatus=$systemStatus", $progText377, $spare, "1");?> &nbsp;|&nbsp;
         <?writeActiveLink("systems.php?spare=2&systemStatus=$systemStatus", $progText472, $spare, "2");?> &nbsp;|&nbsp;
<?
         if ($adminDefinedCategory) {
             writeActiveLink("systems.php?spare=3&systemStatus=$systemStatus", ucfirst($adminDefinedCategory), $spare, "3");
             echo " &nbsp;|&nbsp; \n";
	     }
?>
	     <?writeActiveLink("systems.php?systemStatus=$systemStatus", $progText431, $spare, "");?> &nbsp; </nobr></td>

  <td align='right'><table border='0' cellpadding='0' cellspacing='0'><tr><td align='left' class='smaller' >
     <b><?=$progText222;?>:</b>
         &nbsp;<nobr><?writeActiveLink("systems.php?systemStatus=w&spare=$spare", $progText413, $systemStatus, "w");?> &nbsp;|&nbsp;
         <?writeActiveLink("systems.php?systemStatus=i&spare=$spare", $progText415, $systemStatus, "i");?> &nbsp;|&nbsp;
         <?writeActiveLink("systems.php?systemStatus=n&spare=$spare", $progText414, $systemStatus, "n");?> &nbsp;|&nbsp;
         <?writeActiveLink("systems.php?spare=$spare", $progText431, $systemStatus, "");?></nobr></td></tr></table></td>
  </tr>
</table>

<table width='100%' border='0' cellpadding='1' cellspacing='0'>
<tr><td colspan='2' class='smaller'>&nbsp;</td></tr>
<tr>
  <td align='left' valign='top'><?

echo "<i>";

If ($spare === "0") {
    echo $progText473; # user-assigned systems
} ElseIf ($spare === "1") {
    echo $progText474; # spare systems
} ElseIf ($spare === "2") {
    echo $progText475; # independent systems
} ElseIf ($spare === "3") {
    echo "<i>".ucfirst($adminDefinedCategory)." ".$progText647;
} Else {
    echo $progText480; # all systems
}

$strSQLlocation = "SELECT * FROM locations WHERE locationID=" . $_SESSION['locationStatus'] . " AND accountID=" . $_SESSION['accountID'] . "";
$strLocationName = fetchLocationName($strSQLlocation);
echo "</i> -&gt; <i>".$strLocationName;
echo "</i>";

If ($systemStatus) {
    echo " (". writeStatus ($systemStatus) . ")";
}
?>

  </td>
  <td align='right' valign='top'>
<?
If ($_SESSION['sessionSecurity'] < 2) {
?>
    <img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><a href="admin_hardware.php" class="action"><?=$progText476;?></A> &nbsp;
    <img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><a href="massUpdate.php" class="action"><?=$progText481;?></A> &nbsp;
    <img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><a href="systemCheckout.php" class="action"><?=$progText1218;?></A>

<?
} Else {
    echo "&nbsp;";
}
?>
  </td>
</tr>

<?
If (!$fatalError) {
    $strSQL = determinePageNumber($strSQL);
    $result = dbquery($strSQL);

    If (($pageNumber > 1) OR getOrPost('btnQuickFind')) {
?>
          <FORM METHOD="get" ACTION="systems.php">
          <tr><td colspan='2'>&nbsp;</td></tr>
          <tr><td colspan='2'>
            <input type='hidden' name='btnQuickFind' value='1'>
            <input type='hidden' name='spare' value='<?=$spare;?>'>
            <input type='hidden' name='systemStatus' value='<?=$systemStatus;?>'>

            <?=$progText81;?>:&nbsp;
            <input type='text' name='txtQuickFind' value='<?=$strQuickFind;?>'>

                &nbsp;<select name='cboQuickFind' size='1'>
                  <option value='t.visDescription' <?=writeSelected("t.visDescription", $strQuickFind2);?>><?=$progText58;?></option>
                  <option value='s.lastName' <?=writeSelected("s.lastName", $strQuickFind2);?>><?=$progText251;?></option>
                  <option value='h.hostname' <?=writeSelected("h.hostname", $strQuickFind2);?>><?=$progText37;?></option>
                </select>

            &nbsp; <INPUT TYPE="submit" NAME="qf" VALUE="<?=$progText21;?>">
            </td></tr>
            <tr><td colspan='2'><font size='+1'>&nbsp;</font></td></tr>
            </FORM></table>
<?
    } else {
        echo "<tr><td colspan='2'><font size='+1'>&nbsp;</font></td></tr></table>";
    }

    while ($row = mysql_fetch_array($result)) {
        $intUserID     = $row['id'];
        $strFirstName  = $row['firstName'];
        $strMiddleInit = $row['middleInit'];
        $strLastName   = $row['lastName'];

        $hardwareID       = $row['hardwareID'];
        $strDescription   = $row['visDescription'];
        $strManufacturer  = $row['visManufacturer'];
        $strHostname      = $row['hostname'];
        $strHwStatus      = $row['hardwareStatus'];
        $strIP            = $row['ipAddress'];
        $strLocationCode  = $row['locationCode'];
        $strRoomName      = $row['roomName'];
        $intSparePart     = $row['sparePart'];

        // Set userID (which is NULL, for spare, independent, and adminDefinedCategory systems),
        // to sparePart value so that <UL> writer works correctly. "x" is appended to
        // $intSparePare because otherwise it might actually be confused with users 1, 2, or 3.
        If (!$intUserID) {
            $intUserID = $intSparePart."x";
        }

        If ($intUserID != $tempUserID) {
            If ($countStarted) {
                echo "</UL>\n";
            }
            If ($intSparePart === "0") {
                echo buildName($strFirstName, $strMiddleInit, $strLastName, 0)."\n";
            } ElseIf (($intSparePart === "1") AND !$spare) {
                echo "[ ".$progText377." ]\n";
            } ElseIf (($intSparePart === "2") AND !$spare) {
                echo "[ ".$progText472." ]\n";
            } ElseIf (($intSparePart === "3") AND !$spare) {
                echo "[ ".$adminDefinedCategory." ]\n";
            }
            echo "<UL>\n";
            $tempUserID = $intUserID;
        }
        $countStarted = TRUE;

        $extraInfo = " (".writeStatus($strHwStatus).") &nbsp;";
        If ($strIP) {
            $extraInfo .= " &nbsp;<nobr><u>".$progText157."</u>: ".$strIP."</nobr>";
        }
        If ($strHostname) {
            $extraInfo .= " &nbsp;<nobr><u>".$progText37."</u>: ".$strHostname."</nobr>";
        }
        # If ($strRoomName) {
        #   $extraInfo .= " &nbsp;<nobr><u>".$progText35."</u>: ".$strRoomName."</nobr>";
        #}

        echo "<LI style='line-height: 35%'><A HREF='showfull.php?hardwareID=$hardwareID'>
          ".writePrettySystemName($strDescription, $strManufacturer)."</A>$extraInfo &nbsp;<p>\n";
    }
    If ($countStarted) {
        echo "</UL>\n";
    }

    createPaging("notify");

    If (!$countStarted) {
        echo $progText477;
    }
}

writeFooter();
?>
