<?
    Include("Includes/global.inc.php");
    checkPermissions(2, 1800);

    $hardwareID = getOrPost('hardwareID');
    If ($hardwareID) { # called from systems.php
        $strSQL = "SELECT * FROM hardware as h, hardware_types as t WHERE
          h.hardwareTypeID=t.hardwareTypeID AND h.hardwareID=$hardwareID
          AND t.accountID=" . $_SESSION['accountID'] . "";
        $result = dbquery($strSQL);
        $row = mysql_fetch_array($result);
        $strHeader = $progText417A.": &nbsp;".$row['visDescription'];
        If ($row['hostname']) {
            $strHeader .= ", &nbsp;".$row['hostname'];
        }
        writePopHeader($strHeader);
    } Else {
        writePopHeader($progText920.$progText102);
    }

    // Table-collapsing JavaScript follows
    ?>
<script type="text/javascript">
<!--

function toggleRow(link, numAffected)
{
    newRowClass = (link.firstChild.nodeValue == '+') ? 'visible' : 'hidden';
    link.firstChild.nodeValue = (link.firstChild.nodeValue == '+') ? '–' : '+';
    // nodeArray consists of TR's (and, if NS, possibly empty nodes as well)
    var nodeArray = link.parentNode.parentNode.parentNode.childNodes;
    for (i = 0; i < nodeArray.length; i++)
        if (nodeArray[i] == link.parentNode.parentNode)
            startIndex = i + 1;
    for (i = startIndex; i < startIndex + numAffected; i++)
        nodeArray[i].className = newRowClass;
    fixRowColors(link.parentNode.parentNode.parentNode);
    return false;
}

function fixRowColors(table)
{
    // nodeArray consists of TR's (and, if NS, possibly empty nodes as well)
    var nodeArray = table.childNodes;
    ctr = 0;
    for (i = 1; i < nodeArray.length; i++)
    {
        node = nodeArray[i];
        removeClassName(node, "row1");
        removeClassName(node, "row2");
        if (node.className == "visible")
            ctr++;
        node.className += (ctr % 2 == 1) ? " row1" : " row2";
    }
}

function removeClassName(el, name)
{
    var i, curList, newList;
    if (el.className == null)
        return;
      // Remove the given class name from the element's className property.
    newList = new Array();
    curList = el.className.split(" ");
    for (i = 0; i < curList.length; i++)
        if (curList[i] != name)
            newList.push(curList[i]);
    el.className = newList.join(" ");
}
//-->
</script>

<?
    // ip history display
    if ($hardwareID)
    {   // print ip only if hardware id is valid (systems.php)
    	echo "<b><font size='+1'>" . $strHeader . "</font></b><br>&nbsp;<br>&nbsp;<br>";
    	echo "<b>" . $progText926 . "</b><p>";

    	//Grab everything from ip_history table

	    $strSQL1 = "SELECT ipAddress, firstReportedDate FROM ip_history WHERE hardwareID = $hardwareID AND accountID=" . $_SESSION['accountID'] . " ORDER BY firstReportedDate DESC";
	    $resultHistory = dbquery($strSQL1);
	    $k = 0; // move all ip history data to array
	    while($rowHistory[$k] = mysql_fetch_array($resultHistory))
	    {
    		$k++;
    	}

	    If(mysql_num_rows($resultHistory) != 0)
	    {    // write table header
            ?>
    		<p><table border='0' cellspacing='0' cellpadding='4' width='420'>
    		<tr class='title'>
                <td width='15' style='background-color: white;'></td>
                <td valign='bottom'><b><?=$progText45;?></b></td>
                <td valign='bottom'><b><?=$progText922;?></b></td>
    		</tr>
            <?
    		for($j = 0; $j < $k; $j++)
    		{ // generate table rows
            ?>
    				<tr class='<?= $rowClass; ?>'>
    					<td width='15' style='background-color: white;'></td>
    					<td class="smaller"> <?=$rowHistory[$j]['ipAddress']; ?></td>
    					<td class="smaller"> <?=displayDateTime($rowHistory[$j]['firstReportedDate']); ?></td>
    				</tr>
    	    <?
    		}
    		echo "</table>";
    	}
    	else
    	{
    		echo "<ul><li>".$progText437."</li></ul>\n";
    	}
    }

    //
    // peripheral history display
    //

    echo "<br>&nbsp;<br><b>" . $progText920 . "</b><p>";

    If ($hardwareID) { # called from systems.php
       $sqlCondition = "hardwareID=$hardwareID";
    } Else { # called from sparePeripherals.php
       $sqlCondition = "hardwareID=0";
    }

    // Grab everything from actions, deal with organizing as we go
    $strSQL = "SELECT peripheralTraitID, actionType, actionDate, peripheral_actions.userID,
      firstName, middleInit, lastName
      FROM peripheral_actions
      LEFT JOIN tblSecurity ON tblSecurity.id=peripheral_actions.userID
      WHERE $sqlCondition AND peripheral_actions.accountID=" . $_SESSION['accountID'] . "
      ORDER BY peripheralTraitID, actionType, actionDate";
    $resultActions = dbquery($strSQL);
    if (mysql_num_rows($resultActions))
    {
        echo "<ul>";
    }

    // Build array of row actions, because we need to see ahead to determine whether to collapse
    $i = 0;
    while ($rowAction[$i] = mysql_fetch_array($resultActions))
    {
        $i++;
    }
    $oldTraitID = 0;
    for ($j = 0; $j < $i; $j++)
    {
        // If this transaction has a new trait ID, get data for the peripheral it's associated with,
        //   start a table and add a row.
        // Otherwise, add a new row to the table for this peripheral's transactions
        if ($rowAction[$j]['peripheralTraitID'] != $oldTraitID)
        {
            if ($oldTraitID != 0)
            {
                echo "\n</table><p>";
                $rowStyle = 0;
            }
            $oldTraitID = $rowAction[$j]['peripheralTraitID'];
            $strSQL = "SELECT visDescription, visModel, visManufacturer FROM peripheral_traits WHERE peripheralTraitID=$oldTraitID AND accountID=" . $_SESSION['accountID'] . "";
            $resultType = dbquery($strSQL);
            $rowType = mysql_fetch_array($resultType);
            echo "\n<li>" . writePrettyPeripheralName($rowType['visDescription'], $rowType['visModel'], $rowType['visManufacturer']) . "</li>\n";
            // The following HTML may _NOT_ have new line breaks for DOM browser compatibility
            ?>
<p><table border='0' cellspacing='0' cellpadding='4' width='420'><tr class='title'><td width='15' style='background-color: white;'></td><td valign='bottom'><b><?=$progText921;?></b></td><td valign='bottom'><b><?=$progText922;?></b></td></tr><?
            // outputCollapseRow updates collapse state in $aryCollapse and outputs the current row
            $aryCollapse = outputCollapseRow($rowAction, $j, $aryCollapse);
        }
        else
        {
            $aryCollapse = outputCollapseRow($rowAction, $j, $aryCollapse);
        }
    }
    if (mysql_num_rows($resultActions))
    {
        echo "</table></ul>";
    }
    else
    {
        echo "<ul><li>".$progText437."</li></ul>\n";
    }

    //
    // software history display
    //

    // If software history capture is enabled in global.inc:
    If ($captureSoftwareHistory) {

       // Reset rowstyle
       global $rowStyle;
       $rowStyle = 0;
       echo "<br>&nbsp;<br><b>" . $progText927 . "</b><p>";

       If ($hardwareID) { # called from systems.php
          $sqlCondition = "hardwareID=$hardwareID";
       } Else { # called from sparePeripherals.php
          $sqlCondition = "hardwareID=0";
       }

       // Grab everything from actions, deal with organizing as we go
       $strSQL = "SELECT softwareTraitID, actionType, actionDate, software_actions.userID,
         firstName, middleInit, lastName
         FROM software_actions
         LEFT JOIN tblSecurity ON tblSecurity.id=software_actions.userID
         WHERE $sqlCondition AND software_actions.accountID=" . $_SESSION['accountID'] . "
         ORDER BY softwareTraitID, actionType, actionDate";
       $resultActions = dbquery($strSQL);
       if (mysql_num_rows($resultActions))
       {
           echo "<ul>";
       }

       // Build array of row actions, because we need to see ahead to determine whether to collapse
       $i = 0;
       while ($rowAction[$i] = mysql_fetch_array($resultActions))
       {
           $i++;
       }
       $oldTraitID = 0;
       for ($j = 0; $j < $i; $j++)
       {
           // If this transaction has a new trait ID, get data for the peripheral it's associated with,
           //   start a table and add a row.
           // Otherwise, add a new row to the table for this peripheral's transactions
           if ($rowAction[$j]['softwareTraitID'] != $oldTraitID)
           {
                if ($oldTraitID != 0)
               {
                   echo "\n</table><p>";
                   $rowStyle = 0;
               }
               $oldTraitID = $rowAction[$j]['softwareTraitID'];
               $strSQL = "SELECT visName, visMaker, visVersion FROM software_traits WHERE softwareTraitID=$oldTraitID AND accountID=" . $_SESSION['accountID'] . "";
               $resultType = dbquery($strSQL);
               $rowType = mysql_fetch_array($resultType);
               echo "\n<li>" . writePrettySoftwareName($rowType['visName'], $rowType['visVersion'], $rowType['visMaker']) . "</li>\n";
               // The following HTML may _NOT_ have new line breaks for DOM browser compatibility
               ?>
<p><table border='0' cellspacing='0' cellpadding='4' width='420'><tr class='title'><td width='15' style='background-color: white;'></td><td valign='bottom'><b><?=$progText921;?></b></td><td valign='bottom'><b><?=$progText922;?></b></td></tr><?
               // outputCollapseRow updates collapse state in $aryCollapse and outputs the current row
               $aryCollapse = outputCollapseRow($rowAction, $j, $aryCollapse, "software");
           }
           else
           {
               $aryCollapse = outputCollapseRow($rowAction, $j, $aryCollapse, "software");
           }
       }
       if (mysql_num_rows($resultActions))
       {
           echo "</table></ul>";
       }
       else
       {
           echo "<ul><li>".$progText437."</li></ul>\n";
       }
   }

   writePopFooter();

function buildActionType($rowTransaction)
{
    // Inputs an action type enum from db; outputs corresponding plain text
    global $progText923, $progText924, $progText925;
    if (isset($rowTransaction['userID']))
    {
        $userName = writeNA(buildName($rowTransaction['firstName'], $rowTransaction['middleInit'], $rowTransaction['lastName'], 1));
    }
    switch($rowTransaction['actionType'])
    {
        case "agentDel":
            $strAction = $progText923;
            break;
        case "userMove":
            $strAction = $progText924 . $userName;
            break;
        case "userDel";
            $strAction = $progText925 . $userName;
            break;
    }
    return $strAction;
}

function outputTransactionRow($rowTransaction, $numRowCollapses = 0, $numLeftToHide = 0)
{
    // To prevent the top row of a collapsed set from being affected by hiding and showing rows
    $numAffected = $numRowCollapses - 1;
    // Put "expand" plus-box link next to rows that have collapsed rows underneath
    $plusLink = ($numRowCollapses > 0) ? "<a href='' class='collapseLink' onClick='return toggleRow(this, $numAffected);'>+</a>" : "";
    // Hide rows that have been collapsed
    $rowClass = ($numLeftToHide == $numRowCollapses) ? (alternateRowColor() . " visible") : "hidden";
    // The following HTML may _NOT_ have new line breaks for DOM browser compatibility
      ?>
<tr class='<?= $rowClass; ?>'><td width='15' style='background-color: white;'><?= $plusLink; ?></td><td class='smaller'><?= buildActionType($rowTransaction); ?></td><td class='smaller'><?= displayDateTime($rowTransaction['actionDate']); ?></td></tr><?
}

function outputCollapseRow($rowAction, $j, $aryCollapse, $sORp = "peripheral")
{
    // $rowAction is an array of table rows; it is exactly what was queried from peripheral_actions
    // $j is our current index
    // $aryCollapse stores the current state of collapsing
    //   'numRowCollapses' is the number of rows the current row represents; that is, the number of rows it collapses
    //   'indexCollapseStart' stores the top row (the one that's visible) of a collapsed set
    //   'numLeftToHide' is a counter that determines whether this row will be hidden, i.e., collapsed
    // This function decides where to collapse, then calls outputTransactionRow
    //     and is called for every transaction row.
    //     It takes and returns the $aryCollapse state variable to keep track of collapsing
    $k = $j;
    // The following while condition specifies what rows must have in common to be collapsed
    while ($rowAction[$j]['actionType'] == $rowAction[$k]['actionType'] && $rowAction[$j][$sORp . 'TraitID'] == $rowAction[$k][$sORp . 'TraitID'])
    {
        $k++;
    }
    if ($k - $j > 2 && $aryCollapse['numLeftToHide'] == 0)
    {
        $aryCollapse['numRowCollapses'] = $k - $j;
        $aryCollapse['indexCollapseStart'] = $j;
        $aryCollapse['numLeftToHide'] = $aryCollapse['numRowCollapses'];
    }
    else
    {
        $aryCollapse['numRowCollapses'] = 0;
    }
    outputTransactionRow($rowAction[$j], $aryCollapse['numRowCollapses'], $aryCollapse['numLeftToHide']);
    if ($aryCollapse['numLeftToHide'] > 0)
    {
        $aryCollapse['numLeftToHide']--;
    }
    return $aryCollapse;
}
?>
