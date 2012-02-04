<?
  Include("Includes/global.inc.php");
  Include("Includes/reportFunctions.inc.php");

  checkPermissions(2, 1800);

if (getOrPost('btnSubmit')) {
    if ($_SESSION['stuckAtLocation']) {
        $intLocationID = $_SESSION['locationStatus'];
    } else {
        $intLocationID  = cleanFormInput(getOrPost('cboLocationID'));
    }
    if ($intLocationID == "unassigned") {
        $whereClause = "locationID IS NULL";
    } else {
        $whereClause = "locationID=" . $intLocationID;
    }
    $strSQL = "SELECT * FROM hardware 
               LEFT JOIN hardware_types ON hardware.hardwareTypeID=hardware_types.hardwareTypeID 
               WHERE $whereClause AND hardware.accountID=" . $_SESSION['accountID'];
    $hwResult = dbquery($strSQL);
    $xml = new XmlWriter_Compat();
    $xml->push('system_list');
    while ($hwRow = mysql_fetch_assoc($hwResult)) {        
        $sysAttribs = array();
        $sysAttribs['description'] = $hwRow['visDescription'];
        $sysAttribs['manufacturer'] = $hwRow['visManufacturer'];
        $sysAttribs['hostname'] = $hwRow['hostname'];
        $sysAttribs['serial'] = $hwRow['serial'];
        $sysAttribs['status'] = writeStatusNoHTML($hwRow['hardwareStatus']);
        $sysAttribs['ip_address'] = $hwRow['ipAddress'];
        $sysAttribs['asset_tag'] = $hwRow['assetTag'];
        $sysAttribs['nic_mac_1'] = $hwRow['nicMac1']; 
        $sysAttribs['nic_mac_2'] = $hwRow['nicMac2']; 
        $sysAttribs['purchase_date'] = $hwRow['purchaseDate']; 
        $sysAttribs['purchase_price'] = $hwRow['purchasePrice']; 
        $xml->push('system', $sysAttribs);
        
        // User
        if ($hwRow['userID']) {
            $strSQL = "SELECT * FROM tblSecurity WHERE id=" . $hwRow['userID'] . " AND accountID=" . $_SESSION['accountID'];
            $userResult = dbquery($strSQL); 
            if (mysql_num_rows($userResult) > 0) {
                $userRow = mysql_fetch_assoc($userResult);                
                $userAttribs = array();
                $userAttribs['user_id'] = $userRow['userID'];
                $userAttribs['full_name'] = buildName($userRow['firstName'], $userRow['middleInit'], $userRow['lastName']);
                $userAttribs['email_address'] = $userRow['email'];
                $xml->emptyelement('user', $userAttribs);
            }
        }
        
        // Logical disks
        $strSQL = "SELECT * FROM logicaldisks WHERE logicaldisks.hardwareID=" . $hwRow['hardwareID'] . " AND logicaldisks.accountID=" . $_SESSION['accountID'];
        $ldResult = dbquery($strSQL); 
        $xml->push('logicaldisk_list'); 
        while ($ldRow = mysql_fetch_assoc($ldResult)) {             
            $diskAttribs = array();
            $diskAttribs['name'] = $ldRow['name'];
            $diskAttribs['volume_name'] = $ldRow['volumeName'];
            $diskAttribs['file_system'] = $ldRow['fileSystem'];
            $diskAttribs['size'] = $ldRow['size'];
            $diskAttribs['free_space'] = $ldRow['freeSpace'];
            $xml->emptyelement('logicaldisk', $diskAttribs);
        }        
        $xml->pop(); // logicaldisk_list

        // Peripherals
        $strSQL = "SELECT * FROM peripherals 
                   LEFT JOIN peripheral_types ON peripherals.peripheralTypeID=peripheral_types.peripheralTypeID 
                   WHERE peripherals.hardwareID=" . $hwRow['hardwareID'] . " AND peripherals.accountID=" . $_SESSION['accountID'];
        $periphResult = dbquery($strSQL); 
        $xml->push('peripheral_list');
        while ($periphRow = mysql_fetch_assoc($periphResult)) { 
            $periphAttribs = array();
            $periphAttribs['manufacturer'] = $periphRow['manufacturer'];
            $periphAttribs['model'] = $periphRow['model'];
            $periphAttribs['description'] = $periphRow['description'];
            $xml->emptyelement('peripheral', $periphAttribs);
        }
        $xml->pop(); // peripheral_list
        
        // Software
        $strSQL = "SELECT * FROM software 
                   LEFT JOIN software_types ON software.softwareTypeID=software_types.softwareTypeID 
                   WHERE software.hardwareID=" . $hwRow['hardwareID'] . " AND software.accountID=" . $_SESSION['accountID'];;;
        $swResult = dbquery($strSQL); 
        $xml->push('software_list');
        while ($swRow = mysql_fetch_assoc($swResult)) { 
            $swAttribs = array();
            $swAttribs['name'] = $swRow['Name'];
            $swAttribs['maker'] = $swRow['Maker'];
            $swAttribs['version'] = $swRow['Version'];
            $xml->emptyelement('software', $swAttribs);  
        }        
        $xml->pop(); // software_list        
        
        // Tickets
        $strSQL = "SELECT s.firstName as assFirstName, s.middleInit as assMiddleInit, s.lastName as assLastName, s.userID as assUserID, 
            s2.firstName as authFirstName, s2.middleInit as authMiddleInit, s2.lastName as authLastName, s2.userID as authUserID, 
            c.*, o.categoryName
            FROM (comments as c, tblSecurity as s)
            LEFT JOIN commentCategories as o ON o.categoryID=c.categoryID 
            LEFT JOIN tblSecurity as s2 ON c.authorID=s2.id 
            WHERE c.assignedUserID=s.id AND c.subjectType='h'
            AND c.commentStatus IS NOT NULL AND c.subjectID=" . $hwRow['hardwareID'] . "
            AND c.accountID=" . $_SESSION['accountID'];
        $tktResult = dbquery($strSQL); 
        $xml->push('ticket_list'); 
        while ($tktRow = mysql_fetch_array($tktResult)) { 
            $tktAttribs = array();
            $tktAttribs['category_name'] = $tktRow['categoryName'];
            $tktAttribs['author_id'] = $tktRow['authUserID'];
            $tktAttribs['author_full_name'] = buildName($tktRow['authFirstName'], $tktRow['authMiddleInit'], $tktRow['authLastName']);
            $tktAttribs['assigned_user_id'] = $tktRow['assUserID'];
            $tktAttribs['assigned_user_full_name'] = buildName($tktRow['assFirstName'], $tktRow['assMiddleInit'], $tktRow['assLastName']);
            $tktAttribs['date_time'] = displayDateTime($tktRow['commentDate']);
            $tktAttribs['priority'] = $tktRow['commentPriority'];
            $tktAttribs['status'] = writeCommentStatus($tktRow['commentStatus']);
            $tktAttribs['text'] = str_replace("&nbsp;", "", strip_tags($tktRow['commentText']));
            $xml->emptyelement('ticket', $tktAttribs);
        }        
        $xml->pop(); // ticket_list          
        
        $xml->pop(); // system
    }
    $xml->pop(); // system_list

    header("Cache-Control: private");
    header("Content-Type: text/xml");
    header("Content-Disposition: attachment; filename=location_report.xml");
    header("Pragma: public");
    header("Expires: 0");    
    print $xml->getXml();
} else {
  writeHeader($progText707);
?>
<form name="form1" method="POST" action="xmlByLocation.php">
<table border='0' cellpadding='2'>
    <tr>
      <td><?=$progText34;?>: &nbsp;</td>
<? 
     if ($_SESSION['stuckAtLocation']) {
         $strSQL = "SELECT locationName FROM locations WHERE locationID=" . $_SESSION['locationStatus'] . " AND accountID=" . $_SESSION['accountID'];
         $locResult = dbquery($strSQL);
         $locRow = mysql_fetch_assoc($locResult);
?>
        <td><?= $locRow['locationName'] ?></td>
<? 
     } else { 
?>
        <td><? buildLocationSelect($intLocationID, TRUE, FALSE, TRUE); ?></td>
<?
     }
?>
    </tr>
    <tr>
      <td></td>
      <td><input type='submit' name='btnSubmit' value='<?= $progText21 ?>'></td>
    </tr>
</table>
</form>
<?
  writeFooter();
}
?>
