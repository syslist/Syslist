<? 
    Include("Includes/global.inc.php");
    checkPermissions(3, 1800);
    
    writeHeader($progText950);
    
    $strError = getOrPost('strError');
    declareError(TRUE);

    $strSQL = "SELECT * FROM faq_categories AS cats
               LEFT JOIN faq_content AS cont ON cats.faqCatID=cont.faqCatID
               WHERE cats.accountID=" . $_SESSION['accountID'] . " AND cont.accountID=" . $_SESSION['accountID'] . "
               ORDER BY cats.faqCatID, cont.faqContentID";
    $result = dbquery($strSQL);
?>
    <table border='0' cellpadding='0' cellspacing='0'>
        <tr>
            <td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><a class='action' href='faqEdit.php?action=addnew&type=cat'><?=$progText952;?></a></td>
            <td><nobr>&nbsp; &nbsp; &nbsp; </nobr></td>
            <td><img src='Images/bdot.gif' align='absmiddle' width='18' height='11' border='0'><a class='action' href='faqEdit.php?action=addnew&type=cont'><?=$progText953;?></a></td>
        </tr>
        <!--<tr><td colspan='3'>&nbsp</td></tr>-->
    </table><p>
<?
    if (mysql_num_rows($result) == 0)
    {
        echo $progText958;
    }
    else
    {
        echo "<table border='1' cellpadding='6' cellspacing='0' width='100%'><tr><td>";
        $tempCat = "";
        $i = 0;
        while ($row = mysql_fetch_array($result))
        {
            $strCats[$i] = $row['faqCatName'];
            $strQues[$i] = $row['faqQuestion'];
            $strAns[$i] = $row['faqAnswer'];
            $faqCatID[$i] = $row['faqCatID'];
            $faqContentID[$i] = $row['faqContentID'];
            if ($strCats[$i] != $tempCat)
            {
                if ($i) {
                    echo "</ol>";
                } else {
                    echo "<ul>";
                }
                echo "\n<li style='line-height: 140%; margin-left: -8;'><a href=\"#$strCats[$i]\">$strCats[$i]</a></li><ol>\n\n";
                $tempCat = $strCats[$i];
            }
            echo "<li style='line-height: 140%; margin-left: -8;'><a href=\"#$strQues[$i]\">$strQues[$i]</a></li>\n";
            $i++;
        }
        echo "</ol></td></tr></table>\n\n";
        $tempCat = "";
        
        echo "<table cellspacing='0' cellpadding='4' width='100%'>\n";
        for ($j = 0; $j < $i; $j++)
        {
            if ($strCats[$j] != $tempCat)
            {
                echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
                echo "<tr><td colspan='2'><font size='+1'><b><a name=\"$strCats[$j]\">$strCats[$j]</a></b></font>\n";
                if ($_SESSION['sessionSecurity'] < 2)
                {
                    echo " &nbsp;(<a href=\"faqEdit.php?action=edit&type=cat&id=" . $faqCatID[$j] . "\" class=\"action\">$progText75</a>)\n";
                }
                echo "</td></tr>\n\n";
                $tempCat = $strCats[$j];
                $ctrQues = 1;
            }
            echo "<tr><td width='7'>&nbsp;</td>\n<td align='left'><b><a name=\"$strQues[$j]\">$ctrQues. $strQues[$j]</a></b>\n";
            if ($_SESSION['sessionSecurity'] < 2)
            {
                echo " &nbsp;(<a href=\"faqEdit.php?action=edit&type=cont&id=" . $faqContentID[$j] . "\" class=\"action\">$progText75</a>)\n";
            }
            echo "</td></tr>\n";
            echo "<tr><td width='7'>&nbsp;</td>\n<td align='left'>" . $strAns[$j] . "</td></tr>\n\n";
            $ctrQues++;
        }
        echo "</table>\n\n";
    }
?>
