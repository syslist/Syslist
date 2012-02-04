<?
 Include("Includes/global.inc.php");
 checkPermissions(1, 900);

  $strSQL = "SELECT firstName, middleInit, lastName, email FROM tblSecurity WHERE
    id=" . $_SESSION['userID'] . " AND accountID=" . $_SESSION['accountID'] . "";
  $result = dbquery($strSQL);
  $row = mysql_fetch_row($result);
  $strFromName = buildName($row[0], $row[1], $row[2], 1);
  $strFromEmail = $row[3];
  mysql_free_result($result);

  If (getOrPost('btnSubmit')) {
      $strSubject         = validateText($progText800, getOrPost('txtSubject'), 2, 100, TRUE, FALSE);
	  $strBody            = validateText($progText801, getOrPost('txtBody'), 2, 4000, TRUE, FALSE);
      if ($_SESSION['stuckAtLocation']) {
          $cboLocationID = $_SESSION['locationStatus'];
      } else {
          $cboLocationID      = cleanFormInput(getOrPost('cboLocationID'));
      }
	  $cboSoftwareType    = cleanFormInput(getOrPost('cboSoftwareType'));
	  $cboSystemType      = cleanFormInput(getOrPost('cboSystemType'));
	  $cboPeripheralType  = cleanFormInput(getOrPost('cboPeripheralType'));

      If (!$strError) {

          $strSelect          = " SELECT DISTINCT u.firstName, u.lastName, u.email ";
          $strFrom            = " FROM tblSecurity AS u ";
          $strJoin            = "";
          $strWhere           = " WHERE ";
          $strFixedCondition  = " u.email!='' AND u.email IS NOT NULL AND u.hidden='0' AND u.accountID=" . $_SESSION['accountID'] . " ";

          If (is_numeric(getOrPost('cboUser')) AND getOrPost('radUser')) {
              $strError = $progText802; # select either user or group
        # } ElseIf (is_numeric(getOrPost('cboUser'))) {
        #    $strCondition = "AND u.id=$cboUser";
          } ElseIf (getOrPost('radUser') == "withRights") {
              $strCondition = "AND u.securityLevel<3";
          } ElseIf (getOrPost('radUser') == "location" && is_numeric($cboLocationID)) {
              $strCondition = "AND u.userLocationID=$cboLocationID";
          } ElseIf (getOrPost('radUser') == "softwareType" && is_numeric($cboSoftwareType)) {
			  $strJoin = " INNER JOIN hardware AS h ON u.id=h.userID INNER JOIN software AS s ON h.hardwareID=s.hardwareID ";
			  $strWhere = " WHERE h.accountID=u.accountID AND s.hidden='0' AND ";
              $strCondition = " AND s.softwareTraitID=$cboSoftwareType";
          } ElseIf (getOrPost('radUser') == "systemType" && is_numeric($cboSystemType)) {
			  $strJoin = " INNER JOIN hardware AS h ON u.id=h.userID ";
			  $strWhere = " WHERE h.accountID=u.accountID AND ";
              $strCondition = " AND h.hardwareTypeID=$cboSystemType";
          } ElseIf (getOrPost('radUser') == "peripheralType" && is_numeric($cboPeripheralType)) {
			  $strJoin = " INNER JOIN hardware AS h ON u.id=h.userID INNER JOIN peripherals AS p ON h.hardwareID=p.hardwareID ";
			  $strWhere = " WHERE p.accountID=u.accountID AND p.hidden='0' AND ";
              $strCondition = " AND p.peripheralTraitID=$cboPeripheralType";
          } ElseIf (getOrPost('radUser') != "all") {
              $strError = $progText803; # select a target
          }
      }
      If (!$strError) {

		  $strSQL = $strSelect . $strFrom  . $strJoin . $strWhere . $strFixedCondition . $strCondition;

          $mailCount = 0;
          $result = dbquery($strSQL);
          While ($row = mysql_fetch_array($result)) {
              $strFirstName  = $row["firstName"];
              $strLastName   = $row["lastName"];
              $strEmail      = $row["email"];

              $strSalutation = $progText804." ".$strFirstName." ".$strLastName.",\n\n";

        	  $strMessage = $strSalutation.$strBody;

              If ($strEmail) {
                  mail($strEmail, $strSubject, stripslashes($strMessage), "From: $strFromName <$strFromEmail>\r\nReply-To: $strFromEmail\r\n");
                  $mailCount++;
              }
          }
          If ($mailCount > 1) {
              $sentText = $progText805; # emails sent
          } Else {
              $sentText = $progText806; # email sent
          }
          $strError = $mailCount." ".$sentText.".";
      }


  }

  writeHeader($progText807);
  declareError(TRUE);
?>
<!--<font color='ff0000'>*</font> <?=$progText13;?>.<p>-->

<form name="formXYZ" method="POST" action="emailUsers.php">
<table border='0' cellpadding='4' cellspacing='0'>
    <tr>
      <td valign='top' colspan='2'><font color='ff0000'>*</font> <u><?=$progText808;?></u>:</td>
    </tr>
    <!--<tr>
      <td><?=$progText809;?>: &nbsp;</td>
      <td><? # echo buildUserSelect($cboUser, false, '', false, "formXYZ", "AND email!='' AND email IS NOT NULL");?></td>
    </tr>-->
    <? if (!$_SESSION['stuckAtLocation']) { ?>
    <tr>
      <td width='152'><?=$progText810A;?>:</TD>
      <td><input type='radio' name='radUser' value='location' <?=writeChecked(getOrPost('radUser'), "location");?> onClick="formXYZ.cboSystemType.value=''; formXYZ.cboSoftwareType.value=''; formXYZ.cboPeripheralType.value='';">&nbsp;&nbsp;&nbsp;
          <select SIZE="1" NAME="cboLocationID" onChange="formXYZ.radUser[0].checked=true; formXYZ.cboSystemType.value=''; formXYZ.cboSoftwareType.value=''; formXYZ.cboPeripheralType.value='';">
          <?
          // Get all location types for the drop down menu
          $strSQL = "SELECT * FROM locations WHERE accountID=" . $_SESSION['accountID'] . " ORDER BY locationName ASC";
          $result = dbquery($strSQL);
          echo "   <OPTION VALUE=\"\">&nbsp;</OPTION>\n";
          while ($row = mysql_fetch_array($result)) {
              echo "   <OPTION VALUE=\"".$row['locationID']."\" ";
              echo writeSelected($cboLocationID, $row['locationID']);
              echo ">".$row['locationName']."</OPTION>\n";
           }
          ?>
          </select>
      </td>
    </tr>
    <? } ?>
    <tr>
      <td width='152'><?=$progText810C;?>:</TD>
      <td><input type='radio' name='radUser' value='systemType' <?=writeChecked(getOrPost('radUser'), "systemType");?> onClick="formXYZ.cboLocationID.value=''; formXYZ.cboSoftwareType.value=''; formXYZ.cboPeripheralType.value='';">&nbsp;&nbsp;&nbsp;
        <select size="1" name="cboSystemType" onChange="formXYZ.radUser[1].checked=true; formXYZ.cboLocationID.value=''; formXYZ.cboSoftwareType.value=''; formXYZ.cboPeripheralType.value='';">
          <option value=''>&nbsp;</option>
			<?
			// Get all hardware types for the drop down menu
			$strSQLx = "SELECT hardwareTypeID, visDescription, visManufacturer FROM hardware_types WHERE
			  accountID=" . $_SESSION['accountID'] . " ORDER BY visDescription ASC";
			$resultx = dbquery($strSQLx);
			while ($rowx = mysql_fetch_array($resultx)) {
			  echo "   <option value=\"".$rowx['hardwareTypeID']."\" ";
			  echo writeSelected($cboSystemType, $rowx['hardwareTypeID']);
			  echo ">".writePrettySystemName($rowx['visDescription'], $rowx['visManufacturer'])."</option>\n";
			}
			?>
         </select>
      </td>
    </tr>
    <tr>
      <td><?=$progText810B;?>: &nbsp;</td>
      <td><input type='radio' name='radUser' value='softwareType' <?=writeChecked(getOrPost('radUser'), "softwareType");?> onClick="formXYZ.cboSystemType.value=''; formXYZ.cboLocationID.value=''; formXYZ.cboPeripheralType.value='';">&nbsp;&nbsp;&nbsp;
        <select size="1" name="cboSoftwareType" onChange="formXYZ.radUser[2].checked=true; formXYZ.cboSystemType.value=''; formXYZ.cboLocationID.value=''; formXYZ.cboPeripheralType.value='';">
			<option value=''>&nbsp;</option>
			 <?
			 // Get all software types for the drop down menu
			 $strSQLx = "SELECT * FROM software_traits WHERE accountID=" . $_SESSION['accountID'] . " AND hidden='0'
			   ORDER BY visName ASC";
			 $resultx = dbquery($strSQLx);
			 while ($rowx = mysql_fetch_array($resultx)) {
			   echo "   <option value=\"".$rowx['softwareTraitID']."\" ";
			   echo writeSelected($cboSoftwareType, $rowx['softwareTraitID']).">";
			   echo writePrettySoftwareName($rowx['visName'], $rowx['visVersion'], $rowx['visMaker']);
			   echo "</option>\n";
			 }
			 ?>
     </select>
      </td>
    </tr>
    <tr>
      <td width='152'><?=$progText810D;?>:</TD>
      <td><input type='radio' name='radUser' value='peripheralType' <?=writeChecked(getOrPost('radUser'), "peripheralType");?> onClick="formXYZ.cboSystemType.value=''; formXYZ.cboSoftwareType.value=''; formXYZ.cboLocationID.value='';">&nbsp;&nbsp;&nbsp;
        <select size="1" name="cboPeripheralType" onChange="formXYZ.radUser[3].checked=true; formXYZ.cboSystemType.value=''; formXYZ.cboSoftwareType.value=''; formXYZ.cboLocationID.value='';">
		  <option value=''>&nbsp;</option>
			<?
			// Get all peripheral types for the drop down menu
			$strSQLx = "SELECT * FROM peripheral_traits WHERE accountID=" . $_SESSION['accountID'] . " AND hidden='0'
			  ORDER BY visDescription ASC";
			$resultx = dbquery($strSQLx);
			while ($rowx = mysql_fetch_array($resultx)) {
			  echo "   <option value=\"".$rowx['peripheralTraitID']."\" ";
			  echo writeSelected($cboPeripheralType, $rowx['peripheralTraitID']).">";
			  echo writePrettyPeripheralName($rowx['visDescription'], $rowx['visModel'], $rowx['visManufacturer']);
			  echo "</option>\n";
			}
			?>
        </select>
      </td>
    </tr>
    <tr>
      <td><?=$progText810;?>: &nbsp;</td>
      <td><input type='radio' name='radUser' value='withRights' <?=writeChecked(getOrPost('radUser'), "withRights");?> onClick="formXYZ.cboLocationID.value=''; formXYZ.cboSystemType.value=''; formXYZ.cboSoftwareType.value=''; formXYZ.cboPeripheralType.value='';"></td>
    </tr>
    <tr>
      <td><?=$progText811;?>: &nbsp;</td>
      <td><input type='radio' name='radUser' value='all' <?=writeChecked(getOrPost('radUser'), "all");?> onClick="formXYZ.cboLocationID.value=''; formXYZ.cboSystemType.value=''; formXYZ.cboSoftwareType.value=''; formXYZ.cboPeripheralType.value='';"></td>
    </tr>
  </table>

  <p><table border='0' cellpadding='4' cellspacing='0'>
    <tr>
      <td valign='top'><?=$progText812;?>: &nbsp;</td>
      <td valign='top'><i><?=$strFromName;?></i></td>
    </tr>
    <tr>
      <td valign='top'><?=$progText813;?>: &nbsp;</td>
      <td valign='top'><i><?=$strFromEmail;?></i></td>
    </tr>
    <tr>
      <td valign='top'><font color='ff0000'>*</font> <?=$progText800;?>: &nbsp;</td>
      <td valign='top'><input type="text" name="txtSubject" value="<?echo antiSlash($strSubject);?>" size="40" maxlength="100"></td>
    </tr>
    <tr>
      <td valign='top'><font color='ff0000'>*</font> <?=$progText801;?>: &nbsp;</td>
      <td valign='top'><textarea name="txtBody" rows="9" cols="65" wrap="virtual"><?echo antiSlash($strBody);?></textarea></td>
    </tr>
  </table><p>

  <input type="submit" value="Submit" name="btnSubmit" onClick="return warn_on_submit('<?=$progText814;?>');">
   &nbsp;<input type="reset" name="btnReset" value="<?=$progText815;?>">

</form>

<?
  writeFooter();
?>
