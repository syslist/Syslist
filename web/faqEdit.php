<?
    // Although Syslist requires register_globals to be on, this module uses the $_GET superglobal
    // because dynamically named post variables need to be inputted

    Include("Includes/global.inc.php");
    checkPermissions(1, 1800);
    
    if ($_GET['action'] == "delete")
    {
        if ($_GET['type'] == "cat")
        {
            $_GET['id'] = validateNumber($progText960, $_GET['id'], 1, 1000, TRUE);
            if ($strError == "")
            {
                dbquery("DELETE FROM faq_categories WHERE faqCatID=" . $_GET['id'] . " AND accountID=" . $_SESSION['accountID'] . "");
                dbquery("DELETE FROM faq_content WHERE faqCatID=" . $_GET['id'] . " AND accountID=" . $_SESSION['accountID'] . "");
            }
        }
        elseif ($_GET['type'] == "cont")
        {
            $_GET['id'] = validateNumber($progText961, $_GET['id'], 1, 1000, TRUE);
            if ($strError == "")
            {
                dbquery("DELETE FROM faq_content WHERE faqContentID=" . $_GET['id'] . " AND accountID=" . $_SESSION['accountID'] . "");
            }
        }
        fillError($progText957);
        redirect("faq.php", "strError=$strError");
    }
    elseif ($_GET['action'] == "addnew")
    {
        // Submit button not pressed here
        writeHeader($progText951);

        if ($_GET['type'] == "cont")
        {
            $result = dbquery("SELECT faqCatID, faqCatName FROM faq_categories WHERE accountID=" . $_SESSION['accountID'] . "");
            If (mysql_num_rows($result) > 0)
            {
                declareError(TRUE);
                echo "<p><form name='faqEdit' action='faqEdit.php?action=addnew" . ((isset($_POST['cat']) || ($_GET['type'] == "cat")) ? "submit" : "") . "&type=" . $_GET['type'] . "' method='post'>\n";
                function buildCategorySelect()
                {
                    $result = dbquery("SELECT faqCatID, faqCatName FROM faq_categories WHERE accountID=" . $_SESSION['accountID'] . "");
                    $strTemp = "<select name='cat' size='1' " . ((!isset($_POST['cat'])) ? "onChange=\"document.faqEdit.submit();\"" : "") . ">";
                    while ($row = mysql_fetch_array($result))
                    {
                        $strTemp .= "<option value='" . $row['faqCatID'] . "' " . writeSelected($_POST['cat'], $row['faqCatID']) . ">" . $row['faqCatName'] . "</option>";
                    }
                    $strTemp .= "</select>";
                    return $strTemp;
                }
                echo $progText959.": &nbsp;" . buildCategorySelect() . "<p>";
                if (isset($_POST['cat']))
                {
                    echo "<table border='0' cellpadding='4' cellspacing='0'>\n";
                    echo "<tr><td>&nbsp;</td><td>$progText953:</td></tr>\n";
                    echo "<tr><td valign='top'>$progText955</td><td colspan=2><textarea name='txtNewQues' cols='60' rows='3'></textarea></td></tr>\n";
                    echo "<tr><td valign='top'>$progText956</td><td colspan=2><textarea name='txtNewAns' cols='60' rows='3'></textarea></td></tr></table>\n";
                }
            }
            else
            {
                $strError = $progText962;
                declareError(TRUE);
            }
        }
        elseif ($_GET['type'] == "cat")
        {
            echo "<p><form name='faqEdit' action='faqEdit.php?action=addnew" . ((isset($_POST['cat']) || ($_GET['type'] == "cat")) ? "submit" : "") . "&type=" . $_GET['type'] . "' method='post'>
                  <table border='0' cellpadding='4' cellspacing='0'>\n";
            echo "<tr><td colspan='2'>$progText952</td></tr>";
            echo "<tr><td colspan='2'><input type='text' name='txtNewCat' size='50' maxlength='50'></td></tr></table>";
        }   
        echo "<p /><input type='submit' name='btnSubmit' value='$progText21'></form>";
    }
    elseif ($_GET['action'] == "addnewsubmit")
    {
        // This action is only encountered when submit is pressed
        if ($_POST['btnSubmit'])
        {
            if ($_GET['type'] == "cat")
            {
                $_POST['txtNewCat'] = validateText($progText960, $_POST['txtNewCat'], 1, 100, TRUE);
                if ($strError == "")
                {
                    dbquery("INSERT INTO faq_categories (faqCatName, accountID) VALUES ('" . $_POST['txtNewCat'] . "', " . $_SESSION['accountID'] . ")");
                }
            }
            elseif ($_GET['type'] == "cont")
            {
                $_POST['txtNewQues'] = validateText($progText961, $_POST['txtNewQues'], 1, 200, TRUE);
                $_POST['txtNewAns'] = validateText($progText961, $_POST['txtNewAns'], 1, 10000, TRUE);
                if ($strError == "")
                {
                    dbquery("INSERT INTO faq_content (faqCatID, faqQuestion, faqAnswer, accountID) VALUES (" . $_POST['cat'] . ", '" . $_POST['txtNewQues'] . "', '" . $_POST['txtNewAns'] . "', " . $_SESSION['accountID'] . ")");
                }
            }
            fillError($progText957);
            redirect("faq.php", "strError=$strError");
        }    
    }
    elseif ($_GET['action'] == "edit")
    {
        if ($_GET['type'] == "cat")
        {
            $_GET['id'] = validateNumber($progText960, $_GET['id'], 1, 1000, TRUE);
            if ($strError == "")
            {
                $row = mysql_fetch_array($result = dbquery("SELECT faqCatID, faqCatName FROM faq_categories WHERE faqCatID=" . $_GET['id'] . " AND accountID=" . $_SESSION['accountID'] . ""));
                if ($_POST['btnSubmit'])
                {
                    $_POST['txtCat'] = validateText($progText960, $_POST['txtCat'], 1, 100, TRUE);
                    if ($row['faqCatName'] != $_POST['txtCat'] && $strError == "")
                    {
                        dbquery("UPDATE faq_categories SET faqCatName='" . $_POST['txtCat'] . "' WHERE faqCatID=" . $_GET['id'] . " AND accountID=" . $_SESSION['accountID'] . "");
                    }
                }
                else // !($_POST['btnSubmit'])
                {
                    writeHeader($progText951);
                    declareError(TRUE);
                    echo "<p><form name='faqEdit' action='faqEdit.php?action=edit&type=" . $_GET['type'] . "&id=" . $_GET['id'] . "' method='post'><table border='0' cellpadding='4' cellspacing='0'>\n";
                    echo "<tr><td colspan='2'><input type='text' name='txtCat' value=\"" . $row['faqCatName'] . "\" size='50' maxlength='50'></td><td>(<a class='action' onClick=\"return warn_on_submit('" . $progTextBlock66 . "');\" href='faqEdit.php?action=delete&type=cat&id=" . $row['faqCatID'] . "'>$progText80</a>)</td></tr>\n";                
                }
            }
            else // ($strError != "")
            {
                writeHeader($progText951);
                declareError(TRUE);
            }    
        }
        elseif ($_GET['type'] == "cont")
        {
            $_GET['id'] = validateNumber($progText960, $_GET['id'], 1, 1000, TRUE);
            if ($strError == "")
            {
                $row = mysql_fetch_array($result = dbquery("SELECT faqContentID, faqQuestion, faqAnswer FROM faq_content WHERE faqContentID=" . $_GET['id'] . " AND accountID=" . $_SESSION['accountID'] . ""));
                if ($_POST['btnSubmit'])
                {
                    $_POST['txtQues'] = validateText($progText961, $_POST['txtQues'], 1, 200, TRUE);
                    $_POST['txtAns'] = validateText($progText961, $_POST['txtAns'], 1, 10000, TRUE);
                    if (($row['faqAnswer'] != $_POST['txtAns'] || $row['faqQuestion'] != $_POST['txtQues']) && $strError == "")
                    {
                        dbquery("UPDATE faq_content SET faqAnswer='" . $_POST['txtAns'] . "', faqQuestion='" . $_POST['txtQues'] . "' WHERE faqContentID=" . $_GET['id'] . " AND accountID=" . $_SESSION['accountID'] . "");
                    }   
                }
                else // !($_POST['btnSubmit']
                {
                    writeHeader($progText951);
                    declareError(TRUE);
                    echo "<p><form name='faqEdit' action='faqEdit.php?action=edit&type=" . $_GET['type'] . "&id=" . $_GET['id'] . "' method='post'><table border='0' cellpadding='4' cellspacing='0'>\n";
                    echo "<tr><td width='5' valign='top'>$progText955</td><td><textarea name='txtQues' cols='60' rows='3'>" . $row['faqQuestion'] . "</textarea></td><td valign='top' width='100%'>(<a class='action' onClick=\"return warn_on_submit('" . $progTextBlock66 . "');\" href='faqEdit.php?action=delete&type=cont&id=" . $row['faqContentID'] . "'>$progText80</a>)</td></tr>\n";
                    echo "<tr><td width='5' valign='top'>$progText956</td><td><textarea name='txtAns' cols='60' rows='3'>" . $row['faqAnswer'] . "</textarea></td></tr>\n";
                }
            }
            else // ($strError != "")
            {
                writeHeader($progText951);
                declareError(TRUE);
            }
        }
        if ($_POST['btnSubmit'])
        {
            fillError($progText957);
            redirect("faq.php", "strError=$strError");
        }
        else
        {
            echo "</table><p /><input type='submit' name='btnSubmit' value='$progText21'></form>";
            writeFooter();
        }
    }
 
?>
