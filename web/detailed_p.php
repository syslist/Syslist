<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  $strSQLlocation = "SELECT * FROM locations WHERE locationID=" . $_SESSION['locationStatus'] . " AND accountID=" . $_SESSION['accountID'] . "";
  $strLocationName = fetchLocationName($strSQLlocation);

  // create a SQL snippet for later use, based on user (sort order) input
  $asort = strip_tags(getOrPost('asort'));
  $dsort = strip_tags(getOrPost('dsort'));
  if ($asort) {
      $sqlOrder = "ORDER BY $asort ASC";
  } elseif ($dsort) {
      $sqlOrder = "ORDER BY $dsort DESC";
  } else {
      $sqlOrder = "ORDER BY pt.visDescription ASC";
  }

  // If user used 'quick find', process extra sql and prepare an array of querystring
  // paramaters that will be used to preserve the user's search, in the event that
  // they click sort links after searching.
  If (getOrPost('btnQuickFind')) {
      $strQuickFind1 = cleanFormInput(getOrPost('txtQuickFind'));
      $strQuickFind2 = cleanFormInput(getOrPost('cboQuickFind'));
      
      If ($strQuickFind1) {
          $sqlCondition = "AND pt.visDescription LIKE '%$strQuickFind1%' ";
      }
      If ($strQuickFind2) {
          $sqlCondition .= "AND pt.visTypeClass='$strQuickFind2'";
      }

      $aryQS = array("btnQuickFind=1",
                     "txtQuickFind=$strQuickFind1",
                     "cboQuickFind=$strQuickFind2");
  }

  writeHeader(($progText307." ".$strLocationName), "", TRUE);

  echo $progText308."<p>"; # link to sparePeripherals.php

  // If user has selected a specific location, add up all peripherals in that location
  if (is_numeric($_SESSION['locationStatus'])) {

      $strSQLx = "SELECT peripheralTraitID FROM peripheral_traits WHERE accountID=" . $_SESSION['accountID'] . "
        AND hidden='0'";
      $resultx = dbquery($strSQLx);

      // Initialize array of types where keys are 'peripheralTraitID' and values are instance count
      $aryTypesCount = array();
      while ($row = mysql_fetch_array($resultx)) {
          $aryTypesCount[$row["peripheralTraitID"]] = 0;
      }
      mysql_free_result($resultx);

      // Calculate the number of spares from peripherals table (where $locationID NOT NULL)
      $strSQLy = "SELECT count(p.peripheralID) as pCount, pt.peripheralTraitID FROM peripherals as p,
        peripheral_traits as pt WHERE pt.peripheralTraitID=p.peripheralTraitID AND p.locationID=" . $_SESSION['locationStatus'] . "
        AND pt.accountID=" . $_SESSION['accountID'] . " GROUP by pt.peripheralTraitID";
      $resulty = dbquery($strSQLy);
      while ($row = mysql_fetch_array($resulty)) {
          $aryTypesCount[$row["peripheralTraitID"]] += $row["pCount"];
      }
      mysql_free_result($resulty);

      // Calculate the number of peripherals associated with hardware at $_SESSION['locationStatus']
      $strSQLz = "SELECT p.peripheralTraitID, h.locationID FROM peripherals as p, hardware as h
        WHERE p.locationID IS NULL AND p.accountID=" . $_SESSION['accountID'] . " AND h.hardwareID=p.hardwareID
        AND p.hidden='0'"; # GROUP by p.peripheralID?
      $resultz = dbquery($strSQLz);
      while ($row = mysql_fetch_array($resultz)) {
          if ($row["locationID"] == $_SESSION['locationStatus']) {
              $aryTypesCount[$row["peripheralTraitID"]]++;
          }
      }
      mysql_free_result($resultz);

  // otherwise, just prepare to count up the peripherals instances in the main query (below)
  } else {
      $sqlSelect1 = "count(p.peripheralID) as pCount,";
      $sqlSelect2 = "peripherals as p,";
      $sqlSelect3 = "pt.peripheralTraitID=p.peripheralTraitID AND p.hidden='0' AND";
      $sqlSelect4 = "GROUP BY pt.visDescription, pt.visModel, pt.visManufacturer";
  }

  // Main query: display a list of peripheral types (with instance count, if location inspecific)
  $strSQL = "SELECT $sqlSelect1 pt.peripheralTraitID, pt.visDescription, pt.visModel,
    pt.visManufacturer, pt.visTypeClass FROM $sqlSelect2 peripheral_traits as pt WHERE $sqlSelect3
    pt.hidden='0' AND pt.accountID=" . $_SESSION['accountID'] . " $sqlCondition $sqlSelect4 $sqlOrder";
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

          <FORM METHOD="get" ACTION="detailed_p.php">
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
      <tr class='title'>
        <td valign='bottom'><b><nobr><?=$progText122;?> </nobr><? sortColumnLinks("pt.visDescription", $aryQS); ?></b>&nbsp; </td>
        <td valign='bottom'><b><nobr><?=$progText120;?> </nobr><? sortColumnLinks("pt.visManufacturer", $aryQS); ?></b>&nbsp; </td>
        <td valign='bottom'><b><nobr><?=$progText121;?></nobr></b>&nbsp; </td>
        <td valign='bottom'><b><nobr><?=$progText315;?></nobr></b>&nbsp; </td>
        <td valign='bottom'><b><nobr><?=$progText309;?></nobr></b>&nbsp;</td>
      </tr>
<?
      While ($row = mysql_fetch_array($result)) {

          echo "<tr class='".alternateRowColor()."'>\n";
          echo "<td><a href='search.php?btnSubmitAll=1&txtPeripheralType=".$row["visDescription"]."'>".$row["visDescription"]."</a> &nbsp;</td>\n";
          echo "<td>".writeNA($row["visManufacturer"])." &nbsp;</td>\n";
          echo "<td>".writeNA($row["visModel"])." &nbsp;</td>\n";
          echo "<td>".writePeripheralClass($row["visTypeClass"])." &nbsp;</td>\n";
          if (is_numeric($_SESSION['locationStatus'])) {
              echo "<td>&nbsp;".$aryTypesCount[$row["peripheralTraitID"]]."</td>\n";
          } else {
              echo "<td>&nbsp;".$row["pCount"]."</td>\n";
          }
          echo "</tr>\n\n";
      }

      echo "</table>\n";
      createPaging();
      if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
          echo "\n</FORM>";
      }
  }

  writeFooter();
?>
