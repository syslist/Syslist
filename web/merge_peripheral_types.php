<?
  Include("Includes/global.inc.php");
  checkPermissions(0, 1800);

  $peripheralTraitID   = getOrPost('peripheralTraitID');
  $peripheralTraitID2  = getOrPost('peripheralTraitID2');  
  
  // Has the form been submitted?
  if (getOrPost('btnSubmit')) {
      $radMerge = cleanFormInput(getOrPost('radMerge'));

      If (!$radMerge) {
          $strError = $progText827;
      }

      // Are the required fields filled out?
      if  (!$strError) {

          // Determine which trait is being kept, and which is being deprecated.
          If ($peripheralTraitID == $radMerge) {
              $primaryID     = $peripheralTraitID;
              $deprecatedID  = $peripheralTraitID2;
          } Else {
              $primaryID     = $peripheralTraitID2;
              $deprecatedID  = $peripheralTraitID;
          }

          $strSQLlock = "LOCK TABLES hardware as h WRITE, peripherals as p1 WRITE,
            peripherals as p2 WRITE, hardware_type_defaults WRITE, peripheral_actions WRITE,
            peripherals WRITE, peripheral_types WRITE, peripheral_traits WRITE";
          $resultLock = dbquery($strSQLlock);

          // delete the deprecated peripheral actions (history)
          $strSQL = "DELETE FROM peripheral_actions WHERE accountID=" . $_SESSION['accountID'] . " AND
            peripheralTraitID=$deprecatedID";
          $result = dbquery($strSQL);

          // find and eliminate instances of deprecatedID which otherwise would become
          //   duplicates (because primaryID is also associated with the machine.)
          $strSQL_dupe = "SELECT h.hardwareID FROM hardware as h, peripherals as p1, peripherals as p2
				      WHERE p1.peripheralTraitID=$primaryID AND p2.peripheralTraitID=$deprecatedID AND
				      p1.hardwareID=h.hardwareID AND p2.hardwareID=h.hardwareID AND
							h.accountID=" . $_SESSION['accountID'] . "";
          $result_dupe = dbquery($strSQL_dupe);
          While ($row_dupe = mysql_fetch_array($result_dupe)) {
              $strSQL = "DELETE FROM peripherals WHERE accountID=" . $_SESSION['accountID'] . " AND
                 peripheralTraitID=$deprecatedID AND hardwareID=" . $row_dupe['hardwareID'] . "";
              $result = dbquery($strSQL);
          }

          // update every peripheral instance associated with the deprecated peripheral trait
          $strSQL = "UPDATE peripherals SET peripheralTraitID=$primaryID WHERE
            peripheralTraitID=$deprecatedID AND accountID=" . $_SESSION['accountID'] . "";
          $result = dbquery($strSQL);

          // update every peripheral type associated with the deprecated peripheral trait
          $strSQL = "UPDATE peripheral_types SET peripheralTraitID=$primaryID WHERE
            peripheralTraitID=$deprecatedID AND accountID=" . $_SESSION['accountID'] . "";
          $result = dbquery($strSQL);

          // update default peripheral (= to deprecated trait) in hardware_types_defaults
          $strSQL = "UPDATE hardware_type_defaults SET objectID=$primaryID WHERE
            objectID=$deprecatedID AND objectType='p' AND accountID=" . $_SESSION['accountID'] . "";
          $result = dbquery($strSQL);

          // delete the deprecated peripheral_trait
          $strSQL = "DELETE FROM peripheral_traits WHERE accountID=" . $_SESSION['accountID'] . " AND
            peripheralTraitID=$deprecatedID";
          $result = dbquery($strSQL);
          
          $strSQLunlock = "UNLOCK TABLES";
          $resultUnlock = dbquery($strSQLunlock);

          redirect("admin_peripheral_types.php", "notify=merge");
      }
  }

  // If user has selected the two peripheral traits to be merged, show their info
  // and present the form for joining them.
  if ($peripheralTraitID AND $peripheralTraitID2) {
      $strSQL = "SELECT count(p.peripheralID) as pCount, pt.peripheralTraitID, pt.visDescription,
        pt.visModel, pt.visManufacturer
        FROM peripheral_traits as pt
        LEFT JOIN peripherals as p ON pt.peripheralTraitID=p.peripheralTraitID AND p.hidden='0'
        WHERE pt.hidden='0' AND pt.accountID=" . $_SESSION['accountID'] . " AND pt.peripheralTraitID=$peripheralTraitID
        GROUP BY pt.visDescription, pt.visModel, pt.visManufacturer";
      $result = dbquery($strSQL);

      While ($row = mysql_fetch_array($result)) {
          $peripheralTraitID  = $row["peripheralTraitID"];
          $manufacturer       = $row["visManufacturer"];
          $model              = $row["visModel"];
          $description        = $row["visDescription"];
          $pCount             = $row["pCount"];
      }

      $strSQL = "SELECT count(p.peripheralID) as pCount, pt.peripheralTraitID, pt.visDescription,
        pt.visModel, pt.visManufacturer
        FROM peripheral_traits as pt
        LEFT JOIN peripherals as p ON pt.peripheralTraitID=p.peripheralTraitID AND p.hidden='0'
        WHERE pt.hidden='0' AND pt.accountID=" . $_SESSION['accountID'] . " AND pt.peripheralTraitID=$peripheralTraitID2
        GROUP BY pt.visDescription, pt.visModel, pt.visManufacturer";
      $result = dbquery($strSQL);

      While ($row = mysql_fetch_array($result)) {
          $peripheralTraitID2  = $row["peripheralTraitID"];
          $manufacturer2       = $row["visManufacturer"];
          $model2              = $row["visModel"];
          $description2        = $row["visDescription"];
          $pCount2             = $row["pCount"];
      }

      writeHeader($progText125." ".$progText654);
      declareError(TRUE);

      echo $progTextBlock57; # instructions
?>
      <p><FORM METHOD="post" ACTION="merge_peripheral_types.php">
        <INPUT TYPE="hidden" NAME="peripheralTraitID" VALUE="<?=$peripheralTraitID;?>">
        <INPUT TYPE="hidden" NAME="peripheralTraitID2" VALUE="<?=$peripheralTraitID2;?>">

        <table border='0' cellpadding='4' cellspacing='0'>
          <tr>
            <td><input type='radio' name='radMerge' value='<?=$peripheralTraitID;?>'> </td>
            <td><?=writePrettyPeripheralName($description, $model, $manufacturer);?> &nbsp; &nbsp;</td>
            <td><i><? echo $progText309."</i>: ".$pCount;?></td>
          </tr>
          <tr>
            <td><input type='radio' name='radMerge' value='<?=$peripheralTraitID2;?>'> </td>
            <td><?=writePrettyPeripheralName($description2, $model2, $manufacturer2);?> &nbsp; &nbsp;</td>
            <td><i><? echo $progText309."</i>: ".$pCount2;?></td>
          </tr>
        </table>
        <p><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>">
      </FORM>

<?
      writeFooter();

  // If user has not yet selected a second peripheral trait to merge with the first,
  // give them the opportunity to do so.
  } elseif ($peripheralTraitID) {

      writeHeader($progText125." ".$progText654);
      declareError(TRUE);

      echo $progTextBlock56; # instructions

      If (getOrPost('btnQuickFind')) {
          $strQuickFind = cleanFormInput(getOrPost('txtQuickFind'));
          $sqlCondition = "AND visDescription LIKE '%$strQuickFind%'";
      }

      // display all known peripheral traits ('types' to the user)
      $strSQL = "SELECT * FROM peripheral_traits WHERE peripheralTraitID!=$peripheralTraitID AND
        accountID=" . $_SESSION['accountID'] . " AND hidden='0' $sqlCondition ORDER BY visDescription ASC";
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
              <FORM METHOD="get" ACTION="merge_peripheral_types.php">
              <input type='hidden' name='btnQuickFind' value='1'>
              <input type='hidden' name='peripheralTraitID' value='<?=$peripheralTraitID;?>'>
              <table border='0' cellpadding='4' cellspacing='0'>
                <tr>
                  <td colspan='3'><?=$progText81;?> (<?=$progText58;?>):&nbsp;
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
            <TD><b><?=$progText122;?></b> &nbsp; </TD>
            <TD><b><?=$progText120;?></b> &nbsp; </TD>
            <TD><b><?=$progText121;?></b> &nbsp; </TD>
          </TR>
<?
          while ($row = mysql_fetch_array($result)) {
              $peripheralTraitID2  = $row['peripheralTraitID'];
              $description2        = $row['visDescription'];
              $model2              = $row['visModel'];
              $manufacturer2       = $row['visManufacturer'];

?>
          <TR class='<? echo alternateRowColor(); ?>'>
             <TD><a href='merge_peripheral_types.php?peripheralTraitID=<?=$peripheralTraitID;?>&peripheralTraitID2=<?=$peripheralTraitID2;?>'><?=$description2;?></a> &nbsp; </TD>
             <TD><? echo writeNA($model2); ?> &nbsp; </TD>
             <TD><? echo writeNA($manufacturer2); ?> &nbsp; </TD>
          </TR>
<?
          }

          echo "</table>";
          createPaging();
          if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
              echo "\n</FORM>";
          }
      }
      
      writeFooter();
  }
?>
