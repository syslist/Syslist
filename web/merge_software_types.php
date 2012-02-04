<?
  Include("Includes/global.inc.php");
  checkPermissions(0, 1800);

  $softwareTraitID   = getOrPost('softwareTraitID');
  $softwareTraitID2  = getOrPost('softwareTraitID2');  

  // Has the form been submitted?
  if (getOrPost('btnSubmit')) {
      $radMerge = cleanFormInput(getOrPost('radMerge'));

      If (!$radMerge) {
          $strError = $progText827;
      }

      // Are the required fields filled out?
      if  (!$strError) {

          // Determine which trait is being kept, and which is being deprecated.
          If ($softwareTraitID == $radMerge) {
              $primaryID     = $softwareTraitID;
              $deprecatedID  = $softwareTraitID2;
          } Else {
              $primaryID     = $softwareTraitID2;
              $deprecatedID  = $softwareTraitID;
          }

          $strSQLlock = "LOCK TABLES hardware as h WRITE, software as s1 WRITE, software as s2 WRITE,
            hardware_type_defaults WRITE, software WRITE, software_types WRITE, software_traits WRITE,
            software_traits_licenses WRITE, software_licenses WRITE, software_actions WRITE";
          $resultLock = dbquery($strSQLlock);

          // delete the deprecated software actions (history)
          $strSQL = "DELETE FROM software_actions WHERE accountID=" . $_SESSION['accountID'] . " AND
            softwareTraitID=$deprecatedID";
          $result = dbquery($strSQL);

          // find and eliminate instances of deprecatedID which otherwise would become
          //   duplicates (because primaryID is also associated with the machine.)
          $strSQL_dupe = "SELECT h.hardwareID FROM hardware as h, software as s1, software as s2
				      WHERE s1.softwareTraitID=$primaryID AND s2.softwareTraitID=$deprecatedID AND 
				      s1.hardwareID=h.hardwareID AND s2.hardwareID=h.hardwareID AND 
							h.accountID=" . $_SESSION['accountID'] . "";
          $result_dupe = dbquery($strSQL_dupe);
          While ($row_dupe = mysql_fetch_array($result_dupe)) {
              $strSQL = "DELETE FROM software WHERE accountID=" . $_SESSION['accountID'] . " AND
                 softwareTraitID=$deprecatedID AND hardwareID=" . $row_dupe['hardwareID'] . "";
              $result = dbquery($strSQL);
          }

          // update every software instance associated with the deprecated software trait
          $strSQL = "UPDATE software SET softwareTraitID=$primaryID WHERE
            softwareTraitID=$deprecatedID AND accountID=" . $_SESSION['accountID'] . "";
          $result = dbquery($strSQL);

          // update every software type associated with the deprecated software trait
          $strSQL = "UPDATE software_types SET softwareTraitID=$primaryID WHERE
            softwareTraitID=$deprecatedID AND accountID=" . $_SESSION['accountID'] . "";
          $result = dbquery($strSQL);

          // update default software (= to deprecated trait) in hardware_types_defaults
          $strSQL = "UPDATE hardware_type_defaults SET objectID=$primaryID WHERE
            objectID=$deprecatedID AND objectType='s' AND accountID=" . $_SESSION['accountID'] . "";
          $result = dbquery($strSQL);

          // select all licenses associated with deprecated software trait
          $strSQL = "SELECT software_licenses.licenseID
            FROM software_traits_licenses, software_licenses
            WHERE software_traits_licenses.softwareTraitID=$deprecatedID AND
            software_traits_licenses.licenseID=software_licenses.licenseID AND
            software_licenses.accountID=" . $_SESSION['accountID'] . "";
          $result = dbquery($strSQL);

          While ($row = mysql_fetch_array($result)) {
              $licenseID = $row["licenseID"];

              // If license is not associated with any software trait other than the
              // deprecated one, delete it.
              $strSQL2 = "SELECT softwareTraitID FROM software_traits_licenses WHERE
                softwareTraitID!=$deprecatedID AND licenseID=$licenseID AND accountID=" . $_SESSION['accountID'] . "";
              $result2 = dbquery($strSQL2);
              If (mysql_num_rows($result2) == 0) {
                  $strSQL3 = "DELETE FROM software_licenses WHERE licenseID=$licenseID AND
                    accountID=" . $_SESSION['accountID'] . "";
                  $result3 = dbquery($strSQL3);
              }
          }

          // Delete any license associations for the deprecated software trait.
          $strSQL = "DELETE FROM software_traits_licenses WHERE softwareTraitID=$deprecatedID
            AND accountID=" . $_SESSION['accountID'] . "";
          $result = dbquery($strSQL);

          // delete the deprecated software_trait
          $strSQL = "DELETE FROM software_traits WHERE accountID=" . $_SESSION['accountID'] . " AND
            softwareTraitID=$deprecatedID";
          $result = dbquery($strSQL);

          $strSQLunlock = "UNLOCK TABLES";
          $resultUnlock = dbquery($strSQLunlock);

          redirect("admin_software_types.php", "notify=merge");
      }
  }

  // If user has selected the two software traits to be merged, show their info
  // and present the form for joining them.
  if ($softwareTraitID AND $softwareTraitID2) {

      $strSQL = "SELECT t.softwareTraitID, t.visName, t.visMaker, t.visVersion, t.operatingSystem,
        t.canBeMoved, l.licenseType, l.numLicenses, l.licenseID
        FROM software_traits as t
        LEFT JOIN software_traits_licenses as tl ON t.softwareTraitID=tl.softwareTraitID
        LEFT JOIN software_licenses as l ON tl.licenseID=l.licenseID
        WHERE t.softwareTraitID=$softwareTraitID AND t.accountID=" . $_SESSION['accountID'] . "";
      $result = dbquery($strSQL);

      While ($row = mysql_fetch_array($result)) {
          $softwareTraitID  = $row["softwareTraitID"];
          $strName          = $row["visName"];
          $strManufacturer  = $row["visMaker"];
          $strVersion       = $row["visVersion"];
          $bolOS            = $row["operatingSystem"];
          $bolMovable       = $row["canBeMoved"];
          $strLicense       = $row["licenseType"];
          $intNumLicense    = $row["numLicenses"];
          $licenseID        = $row["licenseID"];
      }

      $strSQL = "SELECT t.softwareTraitID, t.visName, t.visMaker, t.visVersion, t.operatingSystem,
        t.canBeMoved, l.licenseType, l.numLicenses, l.licenseID
        FROM software_traits as t
        LEFT JOIN software_traits_licenses as tl ON t.softwareTraitID=tl.softwareTraitID
        LEFT JOIN software_licenses as l ON tl.licenseID=l.licenseID
        WHERE t.softwareTraitID=$softwareTraitID2 AND t.accountID=" . $_SESSION['accountID'] . "";
      $result = dbquery($strSQL);

      While ($row = mysql_fetch_array($result)) {
          $softwareTraitID2  = $row["softwareTraitID"];
          $strName2          = $row["visName"];
          $strManufacturer2  = $row["visMaker"];
          $strVersion2       = $row["visVersion"];
          $bolOS2            = $row["operatingSystem"];
          $bolMovable2       = $row["canBeMoved"];
          $strLicense2       = $row["licenseType"];
          $intNumLicense2    = $row["numLicenses"];
          $licenseID2        = $row["licenseID"];
      }
      
      writeHeader($progText125." ".$progText655);
      declareError(TRUE);

      echo $progTextBlock55; # instructions
?>
      <p><FORM METHOD="post" ACTION="merge_software_types.php">
        <INPUT TYPE="hidden" NAME="softwareTraitID" VALUE="<?=$softwareTraitID;?>">
        <INPUT TYPE="hidden" NAME="softwareTraitID2" VALUE="<?=$softwareTraitID2;?>">

        <table border='0' cellpadding='4' cellspacing='0'>
          <tr>
            <td><input type='radio' name='radMerge' value='<?=$softwareTraitID;?>'> </td>
            <td><?=writePrettySoftwareName($strName, $strVersion, $strManufacturer);?> &nbsp; &nbsp;</td>
            <td><i><? echo $progText179."</i>: ".writeLicenseType($strLicense)." ".$intNumLicense;?></td>
          </tr>
          <tr>
            <td><input type='radio' name='radMerge' value='<?=$softwareTraitID2;?>'> </td>
            <td><?=writePrettySoftwareName($strName2, $strVersion2, $strManufacturer2);?> &nbsp; &nbsp;</td>
            <td><i><? echo $progText179."</i>: ".writeLicenseType($strLicense2)." ".$intNumLicense2;?></td>
          </tr>
        </table>
        <p><INPUT TYPE="submit" NAME="btnSubmit" VALUE="<?=$progText21;?>">
      </FORM>

<?
      writeFooter();

  // If user has not yet selected a second software trait to merge with the first,
  // give them the opportunity to do so.
  } elseif ($softwareTraitID) {

      writeHeader($progText125." ".$progText655);
      declareError(TRUE);

      echo $progTextBlock54; # instructions

      If (getOrPost('btnQuickFind')) {
          $strQuickFind = cleanFormInput(getOrPost('txtQuickFind'));
          $sqlCondition = "AND t.visName LIKE '%$strQuickFind%'";
      }

      // display all known software traits ('types' to the user), except for the already
      // chosen trait.
      $strSQL = "SELECT t.softwareTraitID, t.visName, t.visMaker, t.visVersion, l.numLicenses
        FROM software_traits as t
        LEFT JOIN software_traits_licenses as tl ON t.softwareTraitID=tl.softwareTraitID
        LEFT JOIN software_licenses as l ON tl.licenseID=l.licenseID
        WHERE t.accountID=" . $_SESSION['accountID'] . " AND t.hidden='0' AND t.softwareTraitID!=$softwareTraitID
        $sqlCondition ORDER BY t.visName ASC";
      $strSQL = determinePageNumber($strSQL);
      $result = dbquery($strSQL);
      $records = mysql_num_rows($result);

      // If there are software traits in the database (or user performed a search, which
      // implies that traits were found earlier in the session), show table and search form.
      if (($records > 0) OR getOrPost('btnQuickFind')) {

          // If results will span more than one page, give user option to quick find as well
          // If user searched, give them quick find (again) in case the search did not turn
          // up what they were looking for.
          if (($records >= $rowLimit) OR getOrPost('btnQuickFind')) {
?>
              <p><FORM METHOD="get" ACTION="merge_software_types.php">
                <input type='hidden' name='btnQuickFind' value='1'>
                <input type='hidden' name='softwareTraitID' value='<?=$softwareTraitID;?>'>
              <table border='0' cellpadding='4' cellspacing='0'>
                <tr>
                  <td colspan='4'><?=$progText81;?> (<?=$progText173;?>):&nbsp;
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
             <TD><b><?=$progText173;?></b> &nbsp; </TD>
             <TD><b><?=$progText120;?></b> &nbsp; </TD>
             <TD><b><?=$progText160;?></b> &nbsp; </TD>
             <TD><b><?=$progText162;?></b> &nbsp; </TD>
          </TR>
<?
          while ($row = mysql_fetch_array($result)) {
              $softwareTraitID2  = $row['softwareTraitID'];
              $strName2          = $row['visName'];
              $strMaker2         = $row['visMaker'];
              $strVersion2       = $row['visVersion'];
              $strLicenses2      = $row['numLicenses'];
?>
              <TR class='<? echo alternateRowColor(); ?>'>
                 <TD><a href='merge_software_types.php?softwareTraitID=<?=$softwareTraitID;?>&softwareTraitID2=<?=$softwareTraitID2;?>'><?=$strName2;?></a> &nbsp;</TD>
                 <TD><? echo writeNA($strMaker2); ?> &nbsp;</TD>
                 <TD><? echo writeNA($strVersion2); ?> &nbsp;</TD>
                 <TD><? echo writeNA($strLicenses2); ?> &nbsp;</TD>
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
