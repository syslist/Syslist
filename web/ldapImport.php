<?
    Include("Includes/global.inc.php");
    checkPermissions(1, 1800);
     
    $importFields = array($progText249, $progText250, $progText251, $progText91, 
        $progText255, $progText256, $progText437);
        
    function cleanHeaderFields()
    {
        global $importFields;
        for ($i = 0; $i < count($importFields) - 1; $i++)
            $_POST['varFields' . $i] = cleanFormInput($_POST['varFields' . $i]);
    }
    function postFieldsToInputs()
    {
        $inputs = "";
        foreach ($_POST as $key => $value)
            if (substr($key, 0, 3) != "btn")
                $inputs .= "<input type='hidden' name='$key' value='$value'>";
        return $inputs;
    }
    function queryFieldsToInputs()
    {
        $arrFields = array("ldapProto", "ldapHost", "ldapPort", "baseDn", "filter", "rdn", "password", "scope", "version", "attrs");
        $inputs = "";
        foreach ($arrFields as $key)
            $inputs .= "<input type='hidden' name='$key' value='" . $_POST[$key] . "'>";
        return $inputs;
    }
   
    /*
     * Update database
     */
    if ($_POST['btnConfirm'])
    {
        cleanHeaderFields();
        // Some function to use with array_filter, and the ubiquitous addImportError
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
        function addImportError(&$strErrors, $i, $builtName)
        {
            global $progText1063, $progText1064, $progText1059;
            $strErrors[count($strErrors)] = $progText1063 . " #" . ($i + 1) . " - " . $builtName . " - " . $progText1064 . ": " . $progText1059;
        }
        
        $_POST['ldapLinesCount'] = cleanFormInput($_POST['ldapLinesCount']);
        $_POST['commonLoc']      = cleanFormInput($_POST['commonLoc']);
        $chkConservative         = cleanFormInput($_POST['chkConservative']);
        // Init array we'll use to display errors
        unset($strErrors);
        // Init front end => db style field name mapper
        $frontEndToDBStyleFields = array($importFields[0] => 'firstName',
                                        $importFields[1] => 'middleInit',
                                        $importFields[2] => 'lastName',
                                        $importFields[3] => 'locationName',
                                        $importFields[4] => 'userID',
                                        $importFields[5] => 'email');
        
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
        // initialize key prefixes--why did I do it this way I'm not sure
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
        for ($i = 0; $i < $_POST['ldapLinesCount']; $i++)
        {
            if ($_POST['ldapLines' . $i] == "")
                continue;
            // Explode values on commas from POST input from confirmation page
            unset($ldapLineVals);
            unset($currentAssVals);
            $ldapLineVals = explode(',', $_POST['ldapLines' . $i]);
            // Generate an associative array that maps a field name (User ID, Last Name, ...) to its value
            for ($j = 0; $j < count($importFields); $j++)
                $currentAssVals[$frontEndToDBStyleFields[$importFields[$j]]] = $ldapLineVals[$j];                
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
            $currentAssVals['locationName'] = validateText($progText91, $currentAssVals['locationName'], 2, 60, FALSE, FALSE);
            $currentAssVals['userID']       = validateText($progText255, $currentAssVals['userID'], 3, 20, FALSE, FALSE);
            $currentAssVals['email']        = validateEmail($progText256, $currentAssVals['email'], FALSE);
            if ($currentAssVals['locationName'] == "")
                $currentAssVals['locationName'] = $_POST['commonLoc'];
            if ($strError != "") 
            {
                $strErrors[count($strErrors)] = $progText1063 . " #" . ($i + 1) . " - " . $currentAssVals['builtName'] . " - " . $progText1064 . ": " . $strError;
                $strError = "";
            }
            else
            {
                // Check if this user is already in the db using full name, email address, and userID
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
                    addImportError(&$strErrors, $i, $currentAssVals['builtName']);
                }
                elseif ($currentAssVals['email']
                    && $dbUsers[$keyPrefix['email'] . $currentAssVals['email']])
                {
                    addImportError(&$strErrors, $i, $currentAssVals['builtName']);
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
                    addImportError(&$strErrors, $i, $currentAssVals['builtName']);
                }
                elseif (($dbUsers[$keyPrefix['fullName'] . $currentAssVals['fullName']])
                     && $matchUserId && $matchEmail)
                {
                    addImportError(&$strErrors, $i, $currentAssVals['builtName']);
                }
                else
                {
                    // Check if this user is associated with a new location
                    if (!isset($dbLocs[$keyPrefix['locationName'] . $currentAssVals['locationName']]))
                    {
                        // Add location to $dbLocs hash table
                        // and to sequential array representing incremental change in 'locations' table
                        $dbLocs[$keyPrefix['locationName'] . $currentAssVals['locationName']] = $currentAssVals;
                        // deltaLocs is just an array of locationNames, 
                        //  not an associative array of arrays of associative arrays like $dbUsers
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
            if ($dbDeltaLocs[$i] != "")
            {
                dbquery("INSERT INTO locations (locationName, accountID) VALUES ('" . $dbDeltaLocs[$i] . "', " . $_SESSION['accountID'] . ")");
                // Not sure what the db is going to assign for locationID, so grab it
                $result = dbquery("SELECT locationID FROM locations WHERE locationName='" . $dbDeltaLocs[$i] . "' AND accountID=" . $_SESSION['accountID'] . "");
                $row = mysql_fetch_assoc($result);
                $dbDeltaUsers[$i]['locationID'] = $row['locationID'];
            }
            else
                $dbDeltaUsers[$i]['locationID'] = $dbLocs[$keyPrefix['locationName'] . $dbDeltaUsers[$i]['locationName']]['locationID'];
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
    }  
    /*
     * Apply mappings and show "confirm import" interface
     */
    if ($_POST['btnMap'])
    {
        cleanHeaderFields();
        $_POST['ldapLinesCount'] = cleanFormInput($_POST['ldapLinesCount']);
        if ($_SESSION['stuckAtLocation']) {
            $_POST['commonLoc'] = $_SESSION['locationStatus'];
        } else {
            $_POST['commonLoc']      = cleanFormInput($_POST['cboLocationID']);
        }
        
        // Check for missing required fields
        $missingField == "";
        for ($i = 0; $i < count($importFields) - 3 && $missingField == ""; $i++)
        {
            if ($i != 1 && $i != 3 && $_POST['varFields' . $i] == "")
                $missingField = $importFields[$i];
        } 
        if ($missingField != "")
            fillError($missingField . " " . $progText540);
            
        if ($strError == "")
        {
            writeHeader($progText1105);
            declareError(TRUE);
            echo "<p>$progText1054</p>\n";
            echo "<form action=\"ldapImport.php\" method=\"post\">\n";
            echo postFieldsToInputs();
            // Must separate hidden inputs that preserve header fields from header cells
            //  because we don't display header cells if they contain N/A
            echo "<table cellpadding='0' cellspacing='0' border='0' width='500'><tr>";
            echo "<td valign='top'><input type='checkbox' name='chkConservative' checked>&nbsp;</td>\n";
            echo "<td align='left'><font class='instructions'>$progText1120</font> - $progText1122</td>\n";
            echo "</tr></table><p>\n\n<table cellpadding='2' cellspacing='2' border='0'><tr>\n";
            // Header cells
            for ($i = 0; $i < count($importFields) - 1; $i++)
                if (($_POST['varFields' . $i] != "") || ($importFields[$i] == $progText91 && $_POST['commonLoc'] != "")) // display only if not an N/A field
                    echo "<td><strong>" . $importFields[$i] . "</strong>&nbsp;</td>\n";
            echo "</tr>";
            // Rest of table
            for ($i = 0; $i < $_POST['ldapLinesCount']; $i++)
            {
                echo "<tr>";
                for ($j = 0; $j < count($importFields) - 1; $j++)
                {
                    $ldapVal = $_POST[$i . "//" . $_POST['varFields' . $j]];
                    $ldapLine .= "," . $ldapVal;
                    // Don't show empty categories, unless the category is "Location" and we have a common location
                    if (($_POST['varFields' . $j] != "") || ($importFields[$j] == $progText91 && $_POST['commonLoc'] != "")) // display if not an N/A field
                    {
                        // If it's middle initial, trim to 1 character
                        if ($importFields[$j] == $progText250 && $ldapVal)
                            echo "<td>" . substr($ldapVal, 0, 1) . "&nbsp;</td>\n";
                        // If it's location, and there's no location for this entry, set it to the default
                        elseif ($importFields[$j] == $progText91 && $ldapVal == "")
                            echo "<td>" . $_POST['commonLoc'] . "&nbsp;</td>\n";
                        else
                            echo "<td>" . $ldapVal . "&nbsp;</td>\n";
                    }
                }
                echo "<input type='hidden' name='ldapLines$i' value='" . substr($ldapLine, 1) . "'>";
                $ldapLine = "";
                echo "</td></tr>\n";
            }
            echo "</table>";                
            echo "<p><input type='submit' name='btnConfirm' value='$progText1057'> <input type='submit' name='btnCancel' value='$progText1060'></p></form>\n";
        }
    }
    /*
     * Query server, show "apply mappings" interface
     */   
    // A taste of strange logic--we want this page displayed iff "Query Server" has been clicked OR we tried to 
    // Apply Mappings and there was an error
    if ($_POST['btnQuery'] || ($_POST['btnMap'] && $strError != ""))
    {
        $ldapProto = cleanFormInput($_POST['ldapProto']);
        $ldapHost  = validateText($progText37, $_POST['ldapHost'], 1, 100, TRUE, FALSE);
        $ldapPort  = validateNumber($progText1102, $_POST['ldapPort'], 1, 65535, FALSE);
        $baseDn    = validateText($progText1100, $_POST['baseDn'], 3, 100, TRUE, FALSE);
        $filter    = validateText($progText1101, $_POST['filter'], 5, 300, TRUE, FALSE);
        $rdn       = validateText($progText1103, $_POST['rdn'], 1, 100, FALSE, FALSE);
        $ldapPass  = validateText($progText496, $_POST['password'], 1, 30, FALSE, FALSE);
        $scope     = validateChoice($progText1107, $_POST['scope']);
        $version   = validateChoice($progText1116, $_POST['version']);
        $attrs     = validateText($progText1101, $_POST['attrs'], 1, 300, FALSE, FALSE);
        if ($attrs)
            $attribs = explode(",", $attrs);
        if (!in_array("ldap", get_loaded_extensions()))
            fillError($progText1123);
            
        if ($strError == "" || $_POST['btnMap'])
        {
            // @'s for incompatible PHP setups--but should never get here anyway, right?
                
            if ($ldapPort)
                $connect = @ldap_connect($ldapProto . "://" . $ldapHost, $ldapPort);
            else
                $connect = @ldap_connect($ldapProto . "://" . $ldapHost);
            if ($version == "3")
                @ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
            
            if (!($bind = @ldap_bind($connect, $rdn, $ldapPass)))
                fillError($progText1106 . ": " . @ldap_error($connect));
                
            if ($scope == "search")
                if ($attribs)
                    $read = @ldap_search($connect, $baseDn, $filter, $attribs);
                else
                    $read = @ldap_search($connect, $baseDn, $filter);
            elseif ($scope == "list")
                if ($attribs)
                    $read = @ldap_list($connect, $baseDn, $filter, $attribs);
                else
                    $read = @ldap_list($connect, $baseDn, $filter);
            else
                fillError($progText1104);
            if (!$read)
            {
                if (!($ldapError = @ldap_error($connect)))
                    $ldapError = $progText1117;
                fillError($progText1106 . ": " . $ldapError);
            }
                
            $info = @ldap_get_entries($connect, $read);
            @ldap_close($connect);
        }        
        if ($strError == "" || $_POST['btnMap']) 
        {   
            function buildLdapSelect($selectName)
            {
                global $attrsList;
                $options = "<option value='' " . writeSelected($_POST[$selectName], "") . "></option>";
                for ($i = 0; $i < count($attrsList); $i++)
                    $options .= "<option value='$attrsList[$i]' " . writeSelected($_POST[$selectName], $attrsList[$i]) . ">$attrsList[$i]</option>";
                return $options;
            }       
            writeHeader($progText1105); 
            declareError(TRUE);
            
            echo "<p>$progText1109</p>";
            echo "<form method='post' action='ldapImport.php'>";
            // $i = entries
            // $j = attributes for entry
            // $k = values per attribute
            echo "<p>" . $info['count'] . " entries returned</p>";
            echo "<input type='hidden' name='ldapLinesCount' value='" . $info['count'] . "'>";
            for ($i = 0; $i < $info['count']; $i++) 
            {
                for ($j = 0; $j < $info[$i]['count']; $j++)
                {
                    $data = $info[$i][$j];
                    if ($attrsHash[$data] !== 1)
                    {
                        $attrsHash[$data] = 1;
                        $attrsList[count($attrsList)] = $data;
                    }
                    // Some day I might want to change that // to some sort of hash or something
                    // to further prevent collisions
                    for ($k = 0; $k < $info[$i][$data]['count']; $k++)                 
                        echo "<input type='hidden' name='$i//$data' value='" . $info[$i][$data][$k] . "'>";
                }
            }
            echo "<table cellspacing=2 cellpadding=2>";
            echo "<tr><td><strong>$progText1110</strong></td><td><strong>$progText1111</strong></td></tr>";
            for ($i = 0; $i < count($importFields) - 1; $i++)
            {
                echo "<tr><td>" . $importFields[$i]. "</td><td><select name='varFields$i'>" . buildLdapSelect("varFields$i") . "</select>";
                if ($importFields[$i] == $progText91 && !$_SESSION['stuckAtLocation']) 
                {
                    echo " &nbsp;" . $progText1119 . ": &nbsp;";
                    buildLocationSelect($_POST['cboLocationID'], FALSE, FALSE, FALSE, TRUE);
                    echo "</td></tr>\n";
                }
            }
            echo "</table><p />\n";
            echo queryFieldsToInputs();
            echo "\n<p><input type='submit' name='btnMap' value='$progText1118'> <input type='submit' name='btnCancel' value='$progText1060'></p></form>";
        }
    }
    
    /*
     * Show "query server" interface
     */ 
    if ((!$_POST['btnQuery'] && !$_POST['btnMap'] && !$_POST['btnCancel']) || ($_POST['btnQuery'] && $strError != "") || ($_POST['btnConfirm'] && $strError != "") || $_POST['btnCancel'])
    {
        writeHeader($progText1105); 
        declareError(TRUE);
        if (!$_POST['version'])
            $_POST['version'] = "3";
        if (!$_POST['scope'])
            $_POST['scope'] = "search";
?>
        <p><font color='ff0000'>*</font> <?=$progText13;?>.</p>
        <form method='post' action='ldapImport.php'>
        <table cellpadding=2 cellspacing=2>
        <tr><td><font color='ff0000'>*</font> <?= $progText37 ?>:</td>
        <td><select name='ldapProto'><option value='ldap' <?= writeSelected("ldap://", $_POST['ldapProto']); ?>>ldap://</option><option value='ldaps' <?= writeSelected("ldaps://", $_POST['ldapProto']); ?>>ldaps://</option></select>
        <input type='text' name='ldapHost' size='25' value='<?= $_POST['ldapHost'] ?>'></td></tr>
        <tr><td><?= $progText1102 ?>:</td><td><input type='text' name='ldapPort' size='5' value='<?= $_POST['ldapPort'] ?>'></td></tr>
        <tr><td><?= $progText1116 ?>:</td><td><select name='version'><option value='3' <?= writeSelected("3", $_POST['version']); ?>>3</option><option value='2' <?= writeSelected("2", $_POST['version']); ?>>2</option></td></tr>
        <tr><td><?= $progText1103 ?>:</td><td><input name='rdn' type='text' size='40' value='<?= $_POST['rdn'] ?>'></td></tr>
        <tr><td><?= $progText496 ?>:</td><td><input name='password' type='password' size='20' value='<?= $_POST['password'] ?>'></td></tr>
        <tr><td><font color='ff0000'>*</font> <?= $progText1100 ?>:</td><td><input name='baseDn' type='text' size='40' value='<?= $_POST['baseDn'] ?>'></td></tr>
        <tr><td><font color='ff0000'>*</font> <?= $progText1101 ?>:</td><td><input name='filter' type='text' size='40'  value='<?= $_POST['filter'] ?>'></td></tr>
        <tr><td><font color='ff0000'>*</font> <?= $progText1107 ?>:</td>
        <td><select name='scope'><option value='search' <?= writeSelected("search", $_POST['scope']); ?>><?= $progText1114 ?></option><option value='list' <?= writeSelected("list", $_POST['scope']); ?>><?= $progText1115 ?></option></td></tr>
        <tr><td><?= $progText1108 ?>:</td><td><input name='attrs' type='text' size='40'></td></tr>
        </table>
        <p><input type='submit' name='btnQuery' value='Query LDAP Server'></p>
        </form>
<?
    }
    writeFooter();
?>
