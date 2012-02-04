<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  if (getOrPost('btnSubmit')) {
    $strStartDate = validateDate($progText1261, getOrPost('txtStartDate'), 1900, (date("Y")+20), FALSE);
    $strEndDate = validateDate($progText1262, getOrPost('txtEndDate'), 1900, (date("Y")+20), FALSE);

    If (!$strError) {
       $strSQL = "SELECT s.softwareID, s.serial, s.vendorID, v.vendorName, t.visName, t.visMaker, t.visVersion,
          s.hardwareID, h.hostname, s.softwareTraitID, ht.visDescription, ht.visManufacturer, s.creationDate
          FROM (software as s, software_traits as t)
          LEFT JOIN vendors as v ON v.vendorID=s.vendorID LEFT JOIN hardware AS h ON h.hardwareID=s.hardwareID
          LEFT JOIN hardware_types as ht ON h.hardwareTypeID=ht.hardwareTypeID
          WHERE t.softwareTraitID=s.softwareTraitID AND
            t.accountID=" . $_SESSION['accountID'] . " AND s.hidden='0' AND
            creationDate >= " . dbDate($strStartDate) . " AND creationDate <= " . dbDate($strEndDate) . "
          ORDER BY t.visName ASC, t.visVersion ASC";
       $result = dbquery($strSQL);
     }
  }

  writeHeader($progText1260);
  declareError(TRUE);

?>
<form name='frmMain' method='post' action='findSwByDate.php'>
<TABLE>
  <TR>
      <TD colspan='2' class='soft_instructions'><?=$progText1263;?></TD>
  </TR>
  <TR><TD colspan='2'>&nbsp;</TD></TR>
  <TR>
      <TD width='120'><?=$progText1261;?>:</TD>
      <TD><? buildDate('txtStartDate', $strStartDate); ?></TD>
  </TR>
  <TR>
      <TD width='120'><?=$progText1262;?>:</TD>
      <TD><? buildDate('txtEndDate', $strEndDate); ?></TD>
  </TR>
  <TR><TD colspan='2'>&nbsp;</TD></TR>
  <TR>
      <TD colspan='2'><INPUT type='submit' name='btnSubmit' value='<?=$progText21;?>'></TD>
  </TR>
</TABLE>
</form>
<p>

<?
 if (getOrPost('btnSubmit') AND !$strError) {
?>
<table border='0' cellpadding='4' cellspacing='0'>
      <tr class='title'>
        <td valign='bottom'><b><nobr><?=$progText380 ?></b>&nbsp; </td>
        <!--<td valign='bottom'><b><nobr><?=$progText44 ?></b>&nbsp; </td>-->
        <td valign='bottom'><b><nobr><?=$progText1264;?></b>&nbsp; </td>
        <td valign='bottom'><b><nobr><?=$progText217;?></b>&nbsp; </td>
        <td valign='bottom'><b><nobr><?=$progText37;?></b>&nbsp; </td>
      </tr>
<?
     while ($row = mysql_fetch_array($result)) {

         echo "<tr class='".alternateRowColor()."'>\n";
         if ($row["softwareTraitID"] != $lastSoftwareTraitID) {
           echo "<td>".writePrettySoftwareName($row["visName"], $row["visVersion"], $row["visMaker"])." &nbsp; &nbsp;</td>\n";
         } else {
           echo "<td>&nbsp;</td>\n";
         }
         # echo "<td>".$row["serial"]." &nbsp;</td>\n";
         echo "<td>".$row["creationDate"]." &nbsp;</td>\n";
         echo "<td><nobr><a href='showfull.php?hardwareID=".$row['hardwareID']."'>";
         echo writePrettySystemName($row['visDescription'], $row['visManufacturer'])."</a> &nbsp;</td>\n";
         echo "<td>".$row["hostname"]."</td>\n";

         $lastSoftwareTraitID = $row["softwareTraitID"];
     }

     echo "</table>";
  }

  writeFooter();
?>
