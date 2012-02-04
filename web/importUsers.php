<?
    // Basic structure
    // 1. Display CSV file and field order inputs
    // 2. Display confirmation
    // 3. Update database
    //  a. Read in state of database
    //  b. Add rows to this local state
    //  c. Write out new rows--that is, the rows in our local state that are not in the database
    // This process allows us to check for duplicates and other conditions in the database state
    // just by comparing array elements and without additional database queries
    
    // We create a new row in the location database iff locationName does not exist    
    // We create a new row in the users database iff firstName . middleInit . lastName, email, userID do not exist
    Include("Includes/global.inc.php");
    checkPermissions(1, 1800);
    
    // The set up--you need this
    if ($_SESSION['stuckAtLocation']) {
        $importFields = array($progText249, $progText250, $progText251, 
            $progText255, $progText256, $progText437);
    } else {
        $importFields = array($progText249, $progText250, $progText251, $progText91, 
            $progText255, $progText256, $progText437);
    }
     
    // These are used in a few different places, might as well define them here   
    function varFieldsToQueryString()
    {
        global $importFields;
        // Put field order back into GET query string so the user gets their inputted order back in case of error
        $fieldTerms = "";
        for ($i = 0; $i < count($importFields) - 1; $i++)            
            $fieldTerms .= "varFields$i=" . $_POST['varFields' . $i] . (($i != count($importFields) - 2) ? "&" : "");
        return $fieldTerms;
    }
    function cleanHeaderFields()
    {
        global $importFields;
        for ($i = 0; $i < count($importFields) - 1; $i++)
            $_POST['varFields' . $i] = cleanFormInput($_POST['varFields' . $i]);
    }
    // ***
    // Submit clicked? Confirm input.    
    // ***
    if ($_POST['btnSubmit'])
    {
        cleanHeaderFields();
        // Check that two fields (e.g. Field 1 and Field 2) don't both represent the same attribute (e.g. First Name)
        $duplicateFields = "";
        for ($i = 0; $i < count($importFields) - 1 && $duplicateFields == ""; $i++)
            for ($j = $i + 1; $j < count($importFields) - 1 && $duplicateFields == ""; $j++)
                if ($_POST['varFields' . $i] == $_POST['varFields' . $j] && $_POST['varFields' . $i] != $progText437)
                    $duplicateFields = $_POST['varFields' . $i];   
        if ($duplicateFields != "")
            redirect("importUsers.php", "strError=" . $progText1055 . $duplicateFields . "&" . varFieldsToQueryString()); // two fields are representing [field name]
        else
        {
            // Check that required fields are present in user's order selection
            // Relies on the last 3 importFields not being required
            $missingField == "";
            for ($i = 0; $i < count($importFields) - 3 && $missingField == ""; $i++)
            {
                $found = 0;
                for ($j = 0; $j < count($importFields) && $found == 0; $j++)
                    if ($_POST['varFields' . $j] == $importFields[$i])
                        $found = 1;
                if ($i != 1 && ($_SESSION['stuckAtLocation'] || $i != 3) && !$found)
                    $missingField = $importFields[$i];
            } 
            if ($missingField != "")
                redirect("importUsers.php", "strError=" . $missingField . " " . $progText540 . "&" . varFieldsToQueryString()); // [field name] is required
            else
            {
                if ($_FILES['csvFile']['size'] <= 0 ||
                    ($_FILES['csvFile']['type'] != "application/octet-stream" AND
                     $_FILES['csvFile']['type'] != "application/vnd.ms-excel" AND
                     $_FILES['csvFile']['type'] != "text/csv" AND
                     $_FILES['csvFile']['type'] != "text/comma-separated-values" AND
                     $_FILES['csvFile']['type'] != "application/csv" AND
                     $_FILES['csvFile']['type'] != "text/plain"))
                    redirect("importUsers.php", "strError=" . $progText1056 . "&" . varFieldsToQueryString()); // Error uploading file
                else
                {
                    // Show the table of input value to confirm
                    // Include hidden inputs to pass along values from file
                    writeHeader($progText1050);
                    declareError(TRUE);
                    $strLines = file($_FILES['csvFile']['tmp_name']);
                    echo "<p>$progText1054</p>\n";
                    echo "<form action=\"importUsers.php\" method=\"post\">\n";
                    echo "<input type='hidden' name='fileLinesCount' value='" . count($strLines) . "'>\n";
                    
                    // Must separate hidden inputs that preserve header fields from header cells
                    //  because we don't display header cells if they contain N/A
                    for ($i = 0; $i < count($importFields) - 1; $i++)
                        echo "<input type='hidden' name='varFields$i' value='" . $_POST['varFields' . $i] . "'>";
                    echo "<table cellpadding='0' cellspacing='0' border='0' width='500'><tr>";
                    echo "<td valign='top'><input type='checkbox' name='chkConservative' checked>&nbsp;</td>\n";
                    echo "<td align='left'><font class='instructions'>$progText1120</font> - $progText1122</td>\n";
                    echo "</tr></table><p>\n\n<table cellpadding='2' cellspacing='2' border='0'><tr>\n";
                    // Header cells
                    for ($i = 0; $i < count($importFields) - 1; $i++)
                        if ($_POST['varFields' . $i] != $progText437) // display only if not an N/A field
                            echo "<td><strong>" . $_POST['varFields' . $i] . "</strong>&nbsp;</td>\n";
                    echo "</tr>";
                    // Rest of table
                    for ($i = 0; $i < count($strLines); $i++)
                    {
                        $strLines[$i] = cleanFormInput($strLines[$i]);
                        // Weed out blank lines
                        if ($strLines[$i] != "") 
                        {
                            echo "<tr>";
                            // Split table cells on commas in file
                            $fileVals = explode(",", $strLines[$i]);
                            for ($j = 0; $j < count($importFields) - 1; $j++)
                                if ($_POST['varFields' . $j] != $progText437) // display if not an N/A field
                                {
                                    if ($_POST['varFields' . $j] == $progText250 && $fileVals[$j])
                                        echo "<td>" . substr($fileVals[$j], 0, 1) . "&nbsp;</td>\n";
                                    else
                                        echo "<td>" . $fileVals[$j] . "&nbsp;</td>\n";
                                }
                            echo "<input type='hidden' name='fileLines$i' value='" . $strLines[$i] . "'>";
                            echo "</td></tr>\n";
                        }
                    }
                    echo "</table>";                
                    echo "<p><input type='submit' name='btnConfirm' value='$progText1057'> <input type='submit' name='btnCancel' value='$progText1060'></p></form>\n";
                }      
            }
        }
    }
    // ***
    // Cancel clicked? Return to data collecting (first) page
    // ***
    elseif ($_POST['btnCancel'])
        redirect("importUsers.php", varFieldsToQueryString());
    // ***
    // Confirm clicked? Do database update.
    // ***
    elseif ($_POST['btnConfirm'])
    {
        function getKeys($total, $hashIn)
        {
            return array_merge($total, array_keys($hashIn));
        }
        function getUserIDs($total, $hashIn)
        {
            $total[] = $hashIn['userID'];
            return $total;
        }
        function getEmails($total, $hashIn)
        {
            $total[] = $hashIn['email'];
            return $total;
        }
        function addImportError(&$strErrors, $i, $builtName, $progTextError)
        {
            global $progText1063, $progText1064;
            $strErrors[count($strErrors)] = $progText1063 . " #" . ($i + 1) . " - " . $builtName . " - " . $progText1064 . ": " . $progTextError;
        }

        cleanHeaderFields();
        $_POST['fileLinesCount']  = cleanFormInput($_POST['fileLinesCount']);
        $chkConservative = cleanFormInput($_POST['chkConservative']);
        // Init array we'll use to display errors
        unset($strErrors);
        // Init front end => db style field name mapper
        if ($_SESSION['stuckAtLocation']) {
            $frontEndToDBStyleFields = array($importFields[0] => 'firstName',
                                    $importFields[1] => 'middleInit',
                                    $importFields[2] => 'lastName',
                                    $importFields[3] => 'userID',
                                    $importFields[4] => 'email');
        } else {
            $frontEndToDBStyleFields = array($importFields[0] => 'firstName',
                                    $importFields[1] => 'middleInit',
                                    $importFields[2] => 'lastName',
                                    $importFields[3] => 'locationName',
                                    $importFields[4] => 'userID',
                                    $importFields[5] => 'email');
        }
        
        /* Read in State of Database */
        // Get our current list of locations, key on locationName
        // initialize key prefixes -- these prevent collisions among keys of different fields
        // --useless for locations..!
        $keyPrefix['locationName'] = md5("locationName");
        $strSQL = "SELECT locationID, locationName FROM locations WHERE accountID=" . $_SESSION['accountID'] . "";
        $result = dbquery($strSQL);
        while ($row = mysql_fetch_assoc($result))        
            $dbLocs[$keyPrefix['locationName'] . $row['locationName']] = $row;                
        // Get users, key on firstName . lastName, email, userID
        // initialize key prefixes
        $keyPrefix['fullName'] = md5("fullName");
        $keyPrefix['userID'] = md5("userID");
        $keyPrefix['email'] = md5("email");
        $strSQL = "SELECT * FROM tblSecurity WHERE hidden='0' AND accountID=" . $_SESSION['accountID'] . "";
        $result = dbquery($strSQL);
        while ($row = mysql_fetch_assoc($result))
        {
            $dbUsers[$keyPrefix['fullName'] . $row['firstName'] . $row['middleInit'] . $row['lastName']][] = $row;
            if (isset($row['userID']))
                $dbUsers[$keyPrefix['userID'] . $row['userID']][] = $row;
            if (isset($row['email']))
                $dbUsers[$keyPrefix['email'] . $row['email']][] = $row;
        }
        
        /* Add Rows to Local State */
        // For each row in the input file...
        for ($i = 0; $i < $_POST['fileLinesCount']; $i++)
        {
            if ($_POST['fileLines' . $i] == "")
                continue;
            // Explode values on commas from POST input from confirmation page
            $fileLineVals = explode(',', $_POST['fileLines' . $i]);
            // Generate an associative array that maps a field name (User ID, Last Name, ...) to its value
            for ($j = 0; $j < count($importFields); $j++)
                $currentAssVals[$frontEndToDBStyleFields[$_POST['varFields' . $j]]] = $fileLineVals[$j];                
            // Middle initial fix
            if ($currentAssVals['middleInit'])
                $currentAssVals['middleInit'] = substr($currentAssVals['middleInit'], 0, 1);
            // Save a bit of typing:
            $currentAssVals['fullName']  = $currentAssVals['firstName'] . $currentAssVals['middleInit'] . $currentAssVals['lastName'];
            $currentAssVals['builtName'] = buildName($currentAssVals['firstName'], $currentAssVals['middleInit'], $currentAssVals['lastName']);
            // Validate input
            $currentAssVals['firstName']    = validateText($progText249, $currentAssVals['firstName'], 2, 40, TRUE, FALSE);
            $currentAssVals['middleInit']   = validateText($progText250, $currentAssVals['middleInit'], 1, 1, FALSE, FALSE);
            $currentAssVals['lastName']     = validateText($progText251, $currentAssVals['lastName'], 2, 40, TRUE, FALSE);
            if ($_SESSION['stuckAtLocation']) {
                $currentAssVals['locationName'] = "";
            } else {
                $currentAssVals['locationName'] = validateText($progText91, $currentAssVals['locationName'], 2, 60, FALSE, FALSE);
            }
            $currentAssVals['userID']       = validateText($progText255, $currentAssVals['userID'], 3, 20, FALSE, FALSE);
            $currentAssVals['email']        = validateEmail($progText256, $currentAssVals['email'], FALSE);
            if ($strError != "") 
            {
                $strErrors[count($strErrors)] = $progText1063 . " #" . ($i + 1) . " - " . $currentAssVals['builtName'] . " - " . $progText1064 . ": " . $strError;
                $strError = "";
            }
            else
            {
                // Check if this user is already in the db using full name, email address, and userID
                // There's a couple ways to do this. The problem is that PHP doesn 't want to do an 
                // array search on something that's not an array. Ideally it would just return 0 if 
                // it weren't an array. So we just suppress the error. I don't know if this solution
                // is faster than checking to see whether the DB entry is an array before searching it.
                $builtKeys      = @array_reduce($dbUsers[$keyPrefix['fullName'] . $currentAssVals['fullName']], "getKeys", array());
                $existsDbUserId = @in_array("userID", $builtKeys);
                $existsDbEmail  = @in_array("email", $builtKeys);
                $builtUserIDs   = @array_reduce($dbUsers[$keyPrefix['fullName'] . $currentAssVals['fullName']], "getUserIDs", array());
                $matchUserId    = @in_array($currentAssVals['userID'], $builtUserIDs);
                $builtEmails    = @array_reduce($dbUsers[$keyPrefix['fullName'] . $currentAssVals['fullName']], "getEmails", array());
                $matchEmail     = @in_array($currentAssVals['email'], $builtEmails);
                if  ($currentAssVals['userID']
                    && $dbUsers[$keyPrefix['userID'] . $currentAssVals['userID']])
                {
                    addImportError(&$strErrors, $i, $currentAssVals['builtName'], $progText257);
                }
                elseif ($currentAssVals['email']
                    && $dbUsers[$keyPrefix['email'] . $currentAssVals['email']])
                {
                    addImportError(&$strErrors, $i, $currentAssVals['builtName'], $progText258);
                }    
                elseif ((($chkConservative == "on")
                     && ($dbUsers[$keyPrefix['fullName'] . $currentAssVals['fullName']])
                     && (($existsDbUserId && !$currentAssVals['userID'])
                      || (!$existsDbUserId && $currentAssVals['userID'])
                      || ($existsDbEmail && !$currentAssVals['email'])
                      || (!$existsDbEmail && $currentAssVals['email'])))
                    && !(($currentAssVals['userID'] && $existsDbUserId && !$matchUserId)
                      || ($currentAssVals['email'] && $existsDbEmail && !$matchEmail)))
                { 
                    addImportError(&$strErrors, $i, $currentAssVals['builtName'], $progText1059);
                }
                elseif (($dbUsers[$keyPrefix['fullName'] . $currentAssVals['fullName']])
                     && $matchUserId && $matchEmail)
                {
                    addImportError(&$strErrors, $i, $currentAssVals['builtName'], $progText1059);
                }
                else
                {
                    // Check if this user is associated with a new location
                    if (!isset($dbLocs[$keyPrefix['locationName'] . $currentAssVals['locationName']]))
                    {
                        // Add location to $dbLocs hash table
                        // and to sequential array representing incremental change in 'locations' table
                        $dbLocs[$keyPrefix['locationName'] . $currentAssVals['locationName']] = $currentAssVals;
                        // deltaLocs is just an array of locationNames, not an array of associative arrays
                        $dbDeltaLocs[count($dbDeltaLocs)] = $currentAssVals['locationName'];
                    }
                    else
                        $dbDeltaLocs[count($dbDeltaLocs)] = "";
                    // Add user to $dbUsers hash table
                    //  and to sequential array representing incremental change in 'tblSecurity' table
                    $dbUsers[$keyPrefix['fullName'] . $currentAssVals['fullName']][] = $currentAssVals;                
                    if ($currentAssVals['userID'])
                        $dbUsers[$keyPrefix['userID'] . $currentAssVals['userID']][] = $currentAssVals; 
                    if ($currentAssVals['email'])
                        $dbUsers[$keyPrefix['email'] . $currentAssVals['email']][] = $currentAssVals;
                    $dbDeltaUsers[count($dbDeltaUsers)] = $currentAssVals;
                }
            }
        }
        /* Write Out New Rows */
        // For each new user..
        for ($i = 0; $i < count($dbDeltaUsers); $i++)
        {
            // Add new location if needed
            if ($dbDeltaLocs[$i] != "" && !$_SESSION['stuckAtLocation'])
            {
                dbquery("INSERT INTO locations (locationName, accountID) VALUES ('" . $dbDeltaLocs[$i] . "', " . $_SESSION['accountID'] . ")");
                // Not sure what the db is going to assign for locationID, so grab it
                $result = dbquery("SELECT locationID FROM locations WHERE locationName='" . $dbDeltaLocs[$i] . "' AND accountID=" . $_SESSION['accountID'] . "");
                $row = mysql_fetch_assoc($result);
                $dbDeltaUsers[$i]['locationID'] = $row['locationID'];
            }
            else {
                if ($_SESSION['stuckAtLocation']) {
                    $dbDeltaUsers[$i]['locationID'] = $_SESSION['locationStatus'];
                } else {
                    $dbDeltaUsers[$i]['locationID'] = $dbLocs[$keyPrefix['locationName'] . $dbDeltaUsers[$i]['locationName']]['locationID'];
                }
            }
            // Add user
            $result = dbquery("INSERT INTO tblSecurity (userID, firstName, middleInit, lastName, email, securityLevel, userLocationID, accountID) 
                VALUES (" . makeNull($dbDeltaUsers[$i]['userID'], TRUE) . ", '" . $dbDeltaUsers[$i]['firstName'] . "', '"
                . $dbDeltaUsers[$i]['middleInit'] . "', '" . $dbDeltaUsers[$i]['lastName'] . "', " . makeNull($dbDeltaUsers[$i]['email'], TRUE) . ", 3, "
                . makeNull($dbDeltaUsers[$i]['locationID'], TRUE) . ", " . $_SESSION['accountID'] . ")");
        }
        if (count($strErrors) == 0)
            $strError = $progText1061; // success
        else
        {
            $strError = $progText1062 . "<br>"; // completed, but with errors
            for ($i = 0; $i < count($strErrors); $i++)
                $strError .= "<br>" . $strErrors[$i];
        }
            
        redirect("importUsers.php", "strError=$strError&" . varFieldsToQueryString());
    }
    // ***
    // No submit buttons clicked? Display file and field order inputs.     
    // ***
    else
    {
        writeHeader($progText1050);
        declareError(TRUE);
        function getFieldComboBox($index)
        {
            global $importFields;
            $toReturn = "<select name='varFields$index'>\n";
            for ($i = 0; $i < count($importFields); $i++)
            {
                if (isset($_GET['varFields' . $index]))
                    $strSelected = ($importFields[$i] == $_GET['varFields' . $index] ? "selected" : "");
                else
                    $strSelected = ($i == $index ? "selected" : "");
                $toReturn .= "<option value='" . $importFields[$i] . "' " . $strSelected . ">$importFields[$i]</option>\n";
            }
            return $toReturn;
        }
        echo "<p>$progText1053</p>";
        echo "<form enctype=\"multipart/form-data\" action=\"importUsers.php\" method=\"post\">";
        echo "<table cellpadding=2 cellspacing=2>";
        // echo "<input type='hidden' name='MAX_FILE_SIZE' value='500000'>"
        echo "<tr><td>$progText1052</td><td><input name='csvFile' type='file' size='20'></td></tr>";
        for ($i = 0; $i < count($importFields) - 1; $i++)
        {
            echo "<tr><td>" . $progText1051 . " " . ($i + 1) . 
                "</td><td>" . getFieldComboBox($i) . "</td></tr>";
        }
        // Yeah, gonna unrem these when David feels like QAing Default Location
        //echo "<tr><td>" . $progText1121 . ": </td><td>";
        //buildLocationSelect($_POST['cboLocationID']);
        echo "</td></tr>\n";
        echo "</table>";
        echo "<p><input type='submit' name='btnSubmit' value='$progText21'></p></form>";
    }
    writeFooter();
?>
    
