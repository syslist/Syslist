<?
   Include("Includes/global.inc.php");

   #This variable is used to determine which of the two tests for checking if the
   #system was previously added, succeded.
   global $checkFlag;
   $checkFlag = 0;

   global $hardwareIDFromXML;
   global $accountID;

   global $numOfElementsReqForSystems;
   $numOfElementsReqForSystems = 0;

   #This array stores the softwareTraitIDs of the elements in the xml file.
   global $newSoftTraitIdArray;

   global $newSystemTypeID;

   #These two arrays contain the (peripheralTraitID,peripheralTypeID) from the XML and
   #the DB. Elements which are present in $aryXML and not in $aryDB are added to the
   #peripherals table and elements in $aryDB which are not $aryXML are deleted from DB.
   global $aryDB;
   global $aryXML;
   $aryDB = array();
   $aryXML = array();
   
   # Same deal with $aryLD for logical disks, except we don't bother diffing.
   global $aryLD;
   $aryLD = array();

   // This is a utility function
   // Sends mail to system contact, if syslist administrators chose this in settings.php
   //   -- purpose is to alert admins about new software types
   function maybeAlertAboutNewSoftwareType($attrName, $attrVersion, $attrPublisher) {
       global $hardwareIDFromXML, $adminEmail, $accountID, $urlPrefix, $homeURL;
       global $progText1006, $progText1007, $progText370, $progText769, $progText472, $progText787;
       $strSQLAlert = "SELECT account_settings.alertSoftwareTypeCreate, tblSecurity.email
         FROM account_settings, tblSecurity 
         WHERE account_settings.systemAlertUserID=tblSecurity.id AND account_settings.accountID=$accountID";
       $resultSQLAlert  = dbquery($strSQLAlert);
       $rowSQLAlert     = mysql_fetch_row($resultSQLAlert);
       $swTypeCreate  = $rowSQLAlert[0];
       $contactEmail   = $rowSQLAlert[1];
       if ($swTypeCreate == '1') {
           $result = dbquery("SELECT hardware.hostname, tblSecurity.firstName, tblSecurity.middleInit, tblSecurity.lastName FROM hardware LEFT JOIN tblSecurity ON (hardware.userID=tblSecurity.id) WHERE hardware.hardwareID=$hardwareIDFromXML AND hardware.accountID=" . $accountID . "");
           $row = mysql_fetch_array($result);
           $msgBody = "$progText1007\n\n $progText370: " . $row['hostname'] . "\n $progText769: " .
                             (($row['firstName'] != "") ? (buildName($row['firstName'], $row['middleInit'], $row['lastName'])) : "[ $progText472 ]") .
                            "\n $progText787: " . writePrettySoftwareName($attrName, $attrVersion, $attrPublisher) .
                            "\n\n" . $urlPrefix . "://" . $homeURL . "/showfull.php?hardwareID=" . $hardwareIDFromXML;
            mail($contactEmail, ($progText1007.": ".date("m-d-Y")), $msgBody,
             "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
       }
   }

   class cleaningFunctions {

       Function cleanName($attrName, $attrPublisher, $attrVersion) {

           $attrName       = trim($attrName);
           $attrPublisher  = trim($attrPublisher);
           $attrVersion    = trim($attrVersion);
/* deprecated
           $toBeReplacedArray1 = array(
             "/(remove only)/i", "/see /i", "/ for more information/i"
           );

           $toBeReplacedArray2 = array(
             "standard edition", "edition", "microsoft", "internet explorer", "service pack"
           );

           $replacementArray2 = array (
             "SE", "Ed", "MS", "IE", "SP"
           );

           $wordToDigit = array(
             "/\bzero\b/i", "/\bone\b/i", "/\btwo\b/i", "/\bthree\b/i", "/\bfour\b/i",
             "/\bfive\b/i", "/\bsix\b/i", "/\bseven\b/i", "/\beight\b/i", "/\bnine\b/i"
           );

           $aryDigit = array(
             "0", "1", "2", "3", "4",
             "5", "6", "7", "8", "9"
           );
deprecated */
           # Version is stripped from the name if the length of version is not equal to the length of the name.
           If ($attrVersion) {
               If (strlen($attrVersion) != strlen($attrName)) {
                   $varAttrVersion1 = 'v'.$attrVersion;
                   If (strpos($attrName, $varAttrVersion1) !== FALSE) {
                       $attrName = str_replace($varAttrVersion1, "", $attrName);
                   } ElseIf (strpos($attrName, $attrVersion) !== FALSE) {
                       $attrName = str_replace($attrVersion, "", $attrName);
                   }
               }
           }
/* deprecated
           $attrName = preg_replace($toBeReplacedArray1, "", $attrName);
           $attrName = preg_replace($wordToDigit, $aryDigit, $attrName);

           If (strlen($attrName) > 40) {
               $i = 0;
               foreach ($toBeReplacedArray2 as $rep) {
                   $attrName = eregi_replace($rep, $replacementArray2[$i], $attrName);
                   $i++;
               }
           }
deprecated */
           Return $attrName;
       }

       Function cleanManufacturer($attrManufacturer, $attrName="") {
           global $attrSCAVersion;
           /* $attrName is only passed for software; peripherals (and Windows SCA-reported entities in general)
              don't have the funky publisher name problem that necessitates the extra block of code below */
           If ((strpos($attrSCAVersion, 'MACOS') !== FALSE) AND $attrName AND $attrManufacturer) {
               $firstPeriod   = strpos($attrManufacturer, ".");
               $secondPeriod  = strpos($attrManufacturer, ".", ($firstPeriod+1));
               If ($firstPeriod AND $secondPeriod) {
                   $attrManufacturer = substr($attrManufacturer, ($firstPeriod+1), ($secondPeriod - $firstPeriod));
                   $attrManufacturer = substr(strtoupper($attrManufacturer), 0, 1).substr($attrManufacturer, 1);
               }
           }
/* deprecated
           $toBeReplacedArray = array(
             "/\binc\b/i", "/\bcorporation\b/i", "/\binternational\b/i", "/\bsoftware\b/i",
             "/\bltd\b/i", "/\bsystem manufacturer\b/i", "/\bsystem name\b/i", "/,/", "/\bcorp\b/i",
             "/\bincorporated\b/i", "/\bllc\b/i", "/\./"
           );

           $attrManufacturer = preg_replace($toBeReplacedArray, "", $attrManufacturer);
           $attrManufacturer = trim($attrManufacturer);
deprecated */
           Return $attrManufacturer;
       }
   }

   class System extends cleaningFunctions {
       var $parser;

       Function System ($fileName) {

           $strSQL1 = "LOCK TABLES hardware WRITE, hardware_types WRITE, hardware_type_defaults WRITE,
                       software WRITE, software_types WRITE, software_traits WRITE, peripherals WRITE,
                       peripheral_types WRITE, ip_history WRITE, peripheral_traits WRITE,
                       peripheral_actions WRITE, software_actions WRITE, software_licenses WRITE,
                       software_traits_licenses WRITE, tblSecurity WRITE, account_settings WRITE, 
                       logicaldisks WRITE";
           $result1 = dbquery($strSQL1);

           $this->parser = xml_parser_create("UTF-8");
           xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
           xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
           xml_set_object($this->parser, $this);
           xml_set_element_handler($this->parser, 'start_element', 'end_element');

           xml_parse($this->parser, $fileName);
           xml_parser_free($this->parser);
       }


        /* if (last reported ip is same as currently reported  ip) do nothing; return;
           else
           case 1: no of rows <= 7
                insert the new ip found (this will create a new row)
           case 2: no of rows > 7
               update the oldest row to current ip and date */
       Function IPAddressHistory($reportedIPAddress)
       {
            global $hardwareIDFromXML, $accountID;

            If ($reportedIPAddress) {
                // This gives the ip history result in ascending order of date
                $strSQL = "SELECT ipHistoryID, ipAddress FROM ip_history WHERE hardwareID = '$hardwareIDFromXML'
                  AND accountID = '$accountID' ORDER BY firstReportedDate";
                $result        = dbquery($strSQL);
                $numberOfRows  = mysql_num_rows($result);

                If ($numberOfRows > 0) {
                    $row = mysql_fetch_row($result);
                    $oldestIpHistoryID = $row[0]; // this is the ipHistoryID of the oldest IP entry in the DB
                    If ($numberOfRows > 1) {
                        mysql_data_seek($result, ($numberOfRows-1)); // seek to last (most recent) record
                        $row = mysql_fetch_row($result); // seek to latest IP history record, if appropriate
                    }
                    $latestIPAddress = $row[1]; // This will give the newest ipaddress in the DB (which may be the ONLY address in the DB)
                }

                If ($latestIPAddress != $reportedIPAddress) // if reported ip is same as last reported - do nothing
                {
                    If ($numberOfRows < 7)
                    {
                        $strSQL2 = "INSERT INTO ip_history (ipAddress, hardwareID, firstReportedDate, accountID)
                           VALUES ('$reportedIPAddress', $hardwareIDFromXML, '".date("YmdHis")."', $accountID)";
                    }
                    Else
                    {
                        $strSQL2 = "UPDATE ip_history SET ipAddress='$reportedIPAddress',
                          firstReportedDate='".date("YmdHis")."' WHERE ipHistoryID=$oldestIpHistoryID
                          AND accountID=" . $accountID . "";
                    }
                    dbquery($strSQL2);
                }
            }
       }


       Function start_element ($p, $element, $attributes) {
           global $longHostNames; # When this variable is 1, the xml parser uses 'NetSystemName' element from xml for all purposes.
           global $recordIP; # When set to 1, IP address will be read from XML
           global $checkFlag, $hardwareIDFromXML, $attrUserName, $attrPassword, $attrAccountCode;
           global $accountID, $systemAlertEmail, $numOfElementsReqForSystems, $progText840;
           global $limitReached;
           global $newSystemTypeID;
           global $autoCreateDefaults; # hardware_type_defaults is updated only when this variable is 1.
           global $attrSCAVersion; # the version of the syslist companion agent that transmitted this XML

           #This variable keeps count so that the macAddress of 1st netAdapter is compared to nicMac1 and macAddress
           #of 2nd netAdapter is compared with nicMac2.
           static $netAdapterCount = 1;

           #As the xml file is parsed the value of all relevant attributes is stored in 'static' variables so that
           #these variables can be used to perform necessary tasks after multiple calls to 'start_element'.
           static $attrSystemNameFromSyslistData, $attrProductFromBaseBoard, $attrModelFromCompSystem;
           static $attrManufacturerFromCompSystem, $attrSerialNumberFromBIOSInfo, $attrIDFromCPU;
           static $attrMacAddress1, $attrMacAddress2, $attrIPAddress, $attrFallbackIPAddress;
           static $firstIPAddress, $firstFallbackIPAddress, $attrSerialNumberFromCompSystem, $chosenSystemSerial;
           static $executionCount = 1;
           static $logicalDisks;

           Switch($element) {
               case 'SyslistData':
               # This case statement gets the hardwareID and SystemName from SyslistData element.

                   #This statement gets the value of hardwareID from 'Syslist_ID' which is an attribute in 'SyslistData' tag.
                   $hardwareIDFromXML = $attributes['Syslist_ID'];

                   #hostName gets the value of attribute 'NetSystemName' in xml if $longHostNames is 1 in global.inc.php and
                   #the attribute 'NetSystemName' is not blank.
                   $systemName     = trim($attributes['SystemName']);
                   $netSystemName  = trim($attributes['NetSystemName']);
                   If ((($longHostNames == 1) && $netSystemName) OR !$systemName) {
                       $attrSystemNameFromSyslistData = $netSystemName;
                   } Else {
                       $attrSystemNameFromSyslistData = $systemName;
                   }

                   $attrSCAVersion   = trim($attributes['Version']);
                   $attrUserName     = trim($attributes['Syslist_User']);
                   $attrPassword     = trim($attributes['Syslist_Password']);
                   $attrPassword     = md5($attrPassword);

                   break;

               case 'IPAddress':
               # Only used if no IP address is reported in the individual NetAdapter elements (below), which
               # happens sometimes for strange reasons...

                   If ($firstFallbackIPAddress != 1) { # only read the first IP address in the list
                       $attrFallbackIPAddress = trim($attributes['Address']);
                       $firstFallbackIPAddress = 1; # set to 1 so that no other IP addresses are read
                   }
                   break;

               case 'BaseBoard':
               # This case statement gets the value of 'product' from 'BaseBoard'. This value may be used as 'description'
               # in hardware_types.

                   $attrProductFromBaseBoard = trim($attributes['Product']);
                   break;

               case 'CompSystem':
               # This case statement gets the value of 'Model' from 'CompSystem'. This value may be used as 'description'
               # in hardware_types.

                   $attrModelFromCompSystem         = trim($attributes['Model']);
                   $attrManufacturerFromCompSystem  = trim($attributes['Manufacturer']);
                   $attrSerialNumberFromCompSystem  = trim($attributes['SerialNumber']);
                   $testCompSystemSerial            = strtolower($attrSerialNumberFromCompSystem);
                   If ($attrSerialNumberFromCompSystem) {
                       If ((strpos($testCompSystemSerial, '0000000') !== FALSE) OR
                           (strpos($testCompSystemSerial, 'not available') !== FALSE) OR
                           (strpos($testCompSystemSerial, 'n/a') !== FALSE) OR
                           (strpos($testCompSystemSerial, '123456789') !== FALSE) OR
                           (strlen($testCompSystemSerial) < 4)) {
                            $attrSerialNumberFromCompSystem = "";
                       }
                   }
                   break;

               case 'BIOSInfo':
               # This case statement gets the value of 'SerialNumber' from 'BIOSInfo'.
               # In this context, SerialNumber is generally the SN of the PC itself.

                   $attrSerialNumberFromBIOSInfo  = trim($attributes['SerialNumber']);
                   $testBiosSerial                = strtolower($attrSerialNumberFromBIOSInfo);
                   If ($attrSerialNumberFromBIOSInfo) {
                       If ((strpos($testBiosSerial, '0000000') !== FALSE) OR
                           (strpos($testBiosSerial, 'not available') !== FALSE) OR
                           (strpos($testBiosSerial, 'n/a') !== FALSE) OR
                           (strpos($testBiosSerial, '123456789') !== FALSE) OR
                           (strlen($testBiosSerial) < 4)) {
                            $attrSerialNumberFromBIOSInfo = "";
                       }
                   }
                   break;

               case 'CPU':
               # This case statement gets the value of 'ID' from 'CPU'. This value may be used as 'description'
               # in hardware_types.
                   $attrIDFromCPU = trim($attributes['ID']);
                   break;

               case 'NetAdapter':
               # This case statement stores the value of MACADDress of 1st netAdapter in $attrMacAddress1 and the
               # the value of MACADDress of 2nd netAdapter in $attrMacAddress2.
                   $attrNADescription = trim($attributes['Description']);
                   $attrManufacturer  = trim($attributes['Manufacturer']);
                   $attrType          = trim($attributes['Type']);
                   $attrCaption       = trim($attributes['Caption']);
                   $attrIPSubNet1     = trim($attributes['IPSubnet']);
                   $attrIPSubNet2     = trim($attributes['IPSubNet']);
                   $tempIP            = trim($attributes['IPAddress']);

                   If ((strpos(strtolower($attrManufacturer), 'microsoft') === FALSE) &&
                       (strpos($attrCaption, 'WAN') === FALSE) && (strpos($attrNADescription, 'WAN') === FALSE) &&
                       (strpos($attrCaption, 'VPN') === FALSE) && (strpos($attrNADescription, 'VPN') === FALSE) &&
                       (($tempIP != "0.0.0.0") OR (($attrIPSubNet1 != "0.0.0.0") AND ($attrIPSubNet2 != "0.0.0.0"))) &&
                       (strpos(strtolower($attrCaption), 'miniport') === FALSE) &&
                         (strpos(strtolower($attrNADescription), 'miniport') === FALSE) &&
                       (strpos(strtolower($attrCaption), 'ppp adapter') === FALSE) &&
                         (strpos(strtolower($attrNADescription), 'ppp adapter') === FALSE) &&
                       (strpos(strtolower($attrType), 'cowan') === FALSE)) {

                       // remove advanced IP data from string (i.e. starting from ",fe80" in: "192.168.1.2,fe80::e125:f8cf:6837:b8a1")
                       $tempIPcomma = strpos($tempIP, ",");
                       If ($tempIPcomma) {
                           $tempIP = substr($tempIP, 0, $tempIPcomma);
                       }

                       // only read the first IP address in the XML
                       If (($firstIPAddress != 1) AND ($tempIP != "0.0.0.0") AND $tempIP) {
                           $attrIPAddress = $tempIP;
                           $firstIPAddress = 1; # set to 1 so that no other IP addresses are read
                       }

                       If ($netAdapterCount == 1) {
                           $attrMacAddress1 = trim($attributes['MACAddress']);
                           $netAdapterCount++;
                       } ElseIf ($netAdapterCount == 2) {
                           $attrMacAddress2 = trim($attributes['MACAddress']);
                           $netAdapterCount++;
                       }
                    // mattd: It appears this break should be below the next }
                       break;
                   }
           }

           # The function 'cleanManufacturer is used to clean both model and manufacturer in compsystem.
           # 'system manufacturer' and 'system name' along with other things are replaced by "".
           $attrManufacturerFromCompSystem = $this->cleanManufacturer($attrManufacturerFromCompSystem);
           $attrModelFromCompSystem = $this->cleanManufacturer($attrModelFromCompSystem);

           # The correct system serial number could be found in either BIOSInfo or CompSystem.
           # To our knowledge BIOSInfo is always the correct first place to look. So we try that, then CompSystem.
           If ($attrSerialNumberFromBIOSInfo) {
               $chosenSystemSerial = trim($attrSerialNumberFromBIOSInfo);
           } ElseIf ($attrSerialNumberFromCompSystem) {
               $chosenSystemSerial = trim($attrSerialNumberFromCompSystem);
           } Else {
               $chosenSystemSerial = "";
           }

           # The variable '$numOfElementsReqForSystems' is incremented everytime end of the 4 relevant elements
           # (BaseBoard, CompSystem, ProcessorList, NetAdapterList) is reached. This variable makes sure that database
           # is updated only after all the necessary attributes have been stored in static variables.
           If ($numOfElementsReqForSystems == 4) {

               # $executionCount makes sure that the database is updated only once.
               If ($executionCount == 1) {

                   If (!$attrIPAddress) { # we didn't find an IP address in the netAdapter list for some reason
                       $attrIPAddress  = $attrFallbackIPAddress;
                       $firstIPAddress = 1; # set to 1 so that no other IP addresses are read
                   }

                   #This IF statement checks for the validity of hardwareID, if we get it from the xml file.
                   If ($hardwareIDFromXML) {

                       $this->funcForGivenHardwareID($attrMacAddress1, $attrMacAddress2, $attrSystemNameFromSyslistData, $attrIPAddress, $chosenSystemSerial);

                   } Else {

                       # In this case as syslist_id = "", we cannot determine the accountID.
                       # The agent will supply a syslist username and password (from tblSecurity) that
                       # we will use to find the accountID.
                       $strSQL = "SELECT accountID, stuckAtLocation, userLocationID 
                         FROM tblSecurity 
                         WHERE userID = '$attrUserName' AND password = '$attrPassword' AND hidden='0' AND securityLevel < 2";
                       $result = dbquery($strSQL);
                       $numOfElements = mysql_num_rows($result);
                       If ($numOfElements == 0) {
                           $strSQL99 = "UNLOCK TABLES";
                           $result99 = dbquery($strSQL99);
                           $updateStatus = "Fail";
                           $errorNum = 2;
                           errorReporting($hardwareIDFromXML, $updateStatus, $errorNum);
                           exit();
                       } Else {
                           $row = mysql_fetch_array($result);
                           $accountID = $row['accountID'];
                           $stuckAtLocation  = $row['stuckAtLocation'];
                           If ($stuckAtLocation) { # user is locked to a location, therefore new systems will be assigned to that location
                               $userLocationID = $row['userLocationID'];
                           }
                       }

                       # The following 3 functions set the value of 'checkFlag' to decide if the system
                       # was previously added. $checkFlag = 1 means that system was previously added.
                       If (($attrMacAddress1 OR $attrMacAddress2) AND $attrSystemNameFromSyslistData) {
                           $this->identityCheck($attrMacAddress1, $attrMacAddress2, $attrSystemNameFromSyslistData);
                       }

                       /* This functionality was merged into the function above
                       If (($checkFlag != 1) && $attrSystemNameFromSyslistData) {
                           $this->hostNameCheck($attrSystemNameFromSyslistData);
                       }
                       */

                       /*
                       If ($checkFlag != 1) {
                           $this->ipAddressCheck();
                       }
                       */

                       # If system was determined to be previously added and its hardware ID was stored in
                       # the global var, then we call funcForGivenHardwareID
                       If ($checkFlag == 1) {
                           $this->funcForGivenHardwareID($attrMacAddress1, $attrMacAddress2, $attrSystemNameFromSyslistData, $attrIPAddress, $chosenSystemSerial);
                       }

                       If (!$hardwareIDFromXML) {
                           # If we dont get a hardwareID from xml file then we get the description of this system from
                           # the relevant attributes in xml file.
                           
                           If ($limitReached) {
                               $strSQL99 = "UNLOCK TABLES";
                               $result99 = dbquery($strSQL99);
                               errorReporting("", "Fail", 10);
                               exit();
                           }

                           If ($attrModelFromCompSystem) {
                               $systemDescriptionFromXML = $attrModelFromCompSystem;
                           } ElseIf ($attrProductFromBaseBoard) {
                               $systemDescriptionFromXML = $attrProductFromBaseBoard;
                           } ElseIf ($attrIDFromCPU) {
                               $systemDescriptionFromXML = $attrIDFromCPU;
                           } Else {
                               $systemDescriptionFromXML = $progText840;
                           }

                           $systemDescriptionFromXML     = trim($systemDescriptionFromXML);
                           $systemVisDescriptionFromXML  = $systemDescriptionFromXML;

                           # This query gives the set of description in the database which is then compared with the
                           # new description from the xml file. If a match is found, a hardware instance is created
                           # with that particular hardwareTypeID and if no match is found then  a new hardware_types instance
                           # is created along with new hardware instance.
                           $strSQL3 = "SELECT hardwareTypeID, universalVendorID FROM hardware_types WHERE accountID = $accountID AND description = '$systemDescriptionFromXML'";
                           $result3 = dbquery($strSQL3);
                           $numOfElements = mysql_num_rows($result3);

                            // If $recordIP is set to 1 in global, then include IP address in hardware SQL.
                            If ($recordIP) {
                                $ipSQL1 = "ipAddress,";
                                $ipSQL2 = makeNull($attrIPAddress, TRUE).",";
                            }

                           If ($numOfElements == 0) {
                               $strSQL4 = "INSERT INTO hardware_types (description, visDescription, manufacturer, visManufacturer, accountID) VALUES ('".addslashes($systemDescriptionFromXML)."', '".addslashes($systemVisDescriptionFromXML)."', ".makeNull(addslashes($attrManufacturerFromCompSystem), TRUE).", ".makeNull(addslashes($attrManufacturerFromCompSystem), TRUE).", $accountID)";
                               $result4 = dbquery($strSQL4);

                               $hwTypeID = mysql_insert_id();

                               $strSQL5 = "INSERT INTO hardware (hardwareTypeID, ".$ipSQL1." hostname, sparePart, nicMac1, nicMac2, userID, lastAgentUpdate, purchasePrice, warrantyEndDate, purchaseDate, serial, locationID, accountID) VALUES ($hwTypeID, ".$ipSQL2." ".makeNull(addslashes($attrSystemNameFromSyslistData), TRUE).", '2', ".makeNull($attrMacAddress1, TRUE).", ".makeNull($attrMacAddress2, TRUE).", NULL, '".date("Ymd")."', NULL, NULL, NULL, ".makeNull(addslashes($chosenSystemSerial), TRUE).", ".makeNull($userLocationID).", $accountID)";
                               $result5 = dbquery($strSQL5);

                               If ($autoCreateDefaults == 1) {
                                   $newSystemTypeID = $hwTypeID;
                               }

                               $hardwareIDFromXML = mysql_insert_id();
                               $this->IPAddressHistory($attrIPAddress);

                           } ElseIf ($numOfElements === 1) {
                               While ($row3 = mysql_fetch_array($result3)) {
                                   $hardwareTypeIDFromHwTypes  = $row3['hardwareTypeID'];
                                   $intVendorID                = $row3['universalVendorID'];
                                   $strSQL4 = "INSERT INTO hardware (hardwareTypeID, ".$ipSQL1." hostname, sparepart, nicMac1, nicMac2, userID, lastAgentUpdate, purchasePrice, warrantyEndDate, purchaseDate, serial, vendorID, locationID, accountID) VALUES ($hardwareTypeIDFromHwTypes, ".$ipSQL2." ".makeNull(addslashes($attrSystemNameFromSyslistData), TRUE).", '2', ".makeNull($attrMacAddress1, TRUE).", ".makeNull($attrMacAddress2, TRUE).", NULL, '".date("Ymd")."', NULL, NULL, NULL, ".makeNull(addslashes($chosenSystemSerial), TRUE).", ".makeNull($intVendorID).", ".makeNull($userLocationID).", $accountID)";
                                   $result4 = dbquery($strSQL4);

                                   $hardwareIDFromXML = mysql_insert_id();
                                   $this->IPAddressHistory($attrIPAddress);
                               }
                           } Else {
                               die("Error: too many hwtypes found.");
                           }
                       }
                   }
                   $executionCount++;
               }
           }
       }

       # This functiom is used when the end of an element is reached in xml file.
       Function end_element($p, $element) {
           global $numOfElementsReqForSystems;
           If (($element == "BaseBoard") OR ($element == "CompSystem") OR ($element == "ProcessorList") OR ($element == "NetAdapterList")) {
               $numOfElementsReqForSystems++;
           }
       }

       # This function is executed in one of the two cases. 1. when hardwareId is given in XML.
       # 2. When hardwareID = "" in XML but its correct value is found using the macAddress or hostName check.
       Function funcForGivenHardwareID($attrMacAddress1, $attrMacAddress2, $attrSystemNameFromSyslistData, $attrIPAddress, $chosenSystemSerial) {
           global $hardwareIDFromXML;
           global $attrUserName;
           global $attrPassword;
           global $accountID;
           global $longHostNames; # When this variable is 1, the xml parser uses 'NetSystemName' element from xml for all purposes.
           global $recordIP; # When set to 1, IP address will be read from XML

           $validityKey = $this->validateHardwareID();

           # 'validityKey = 0' means that the hardwareID in xml file is invalid.
           If ($validityKey == 0) {

               $strSQL99 = "UNLOCK TABLES";
               $result99 = dbquery($strSQL99);
               $updateStatus = "Fail";
               $errorNum = 1;
               errorReporting($hardwareIDFromXML, $updateStatus, $errorNum);
               exit();

           } ElseIf ($validityKey == 1) {
               # 'validityKey = 1' means that the hardwareID in xml file is valid.
               $this->IPAddressHistory($attrIPAddress);

               $strSQL = "SELECT nicMac1, nicMac2, hostname, ipAddress, serial FROM hardware WHERE
                 hardwareID = $hardwareIDFromXML AND accountID = $accountID";
               $result = dbquery($strSQL);
               $row = mysql_fetch_array($result);

               $MacAddress1FromDB  = $row['nicMac1'];
               $MacAddress2FromDB  = $row['nicMac2'];
               $hostnameFromDB     = $row['hostname'];
               $ipAddressFromDB    = $row['ipAddress'];
               $serialFromDB       = $row['serial'];

               # begin building the extra sql that will be used to update the DB
               $hardwareSQL = "";

               # update hostname, if it has changed, for this hardwareID.
               If ($hostnameFromDB != $attrSystemNameFromSyslistData) {
                   $hardwareSQL = ", hostname = ".makeNull(addslashes($attrSystemNameFromSyslistData), TRUE);
               }

               # update IP address, if it has changed, for this hardwareID.
               If ($recordIP AND ($ipAddressFromDB != $attrIPAddress)) {
                   $hardwareSQL .= ", ipAddress = ".makeNull($attrIPAddress, TRUE);
               }

               # update system serial number, if currently NULL, for this hardwareID.
               If (!$serialFromDB) {
                   $hardwareSQL .= ", serial = ".makeNull(addslashes($chosenSystemSerial), TRUE);
               }

               # update nicMac1 and nicMac2 for this hardwareID.
               If (($attrMacAddress1 != $MacAddress1FromDB) && ($attrMacAddress2 != $MacAddress2FromDB) && ($attrMacAddress1 != $MacAddress2FromDB)
               && ($attrMacAddress2 != $MacAddress1FromDB)) {
                   $hardwareSQL .= ", nicMac1 = ".makeNull($attrMacAddress1, TRUE).", nicMac2 = ".makeNull($attrMacAddress2, TRUE);
               } ElseIf (($attrMacAddress1 != $MacAddress1FromDB) && ($attrMacAddress1 != $MacAddress2FromDB) && ($attrMacAddress2 != $MacAddress1FromDB)) {
                   $hardwareSQL .= ", nicMac1 = ".makeNull($attrMacAddress1, TRUE);
               } ElseIf (($attrMacAddress2 != $MacAddress2FromDB) && ($attrMacAddress2 != $MacAddress1FromDB) && ($attrMacAddress1 != $MacAddress2FromDB)) {
                   $hardwareSQL .= ", nicMac2 = ".makeNull($attrMacAddress2, TRUE);
               }

               # make the update!
               $strSQL2 = "UPDATE hardware SET lastAgentUpdate = '".date("Ymd")."' ".$hardwareSQL." WHERE
                 hardwareID = $hardwareIDFromXML AND accountID = $accountID";
               $result2 = dbquery($strSQL2);
           }
       }

       Function identityCheck($attrMacAddress1, $attrMacAddress2, $attrSystemNameFromSyslistData) {
           global $accountID;
           global $checkFlag;
           global $hardwareIDFromXML;

           If ($attrMacAddress1) {
               $strSQL = "SELECT hardwareID FROM hardware WHERE hostname = '".addslashes($attrSystemNameFromSyslistData)."'
                 AND accountID = $accountID AND hostname IS NOT NULL AND
                 (nicMac1 = '$attrMacAddress1' OR nicMac2 = '$attrMacAddress1')";
               $result = dbquery($strSQL);
               $numOfElements = mysql_num_rows($result);
           }

           If ($attrMacAddress2) {
               $strSQL2 = "SELECT hardwareID FROM hardware WHERE hostname = '".addslashes($attrSystemNameFromSyslistData)."'
                 AND accountID = $accountID AND hostname IS NOT NULL AND
                 (nicMac1 = '$attrMacAddress2' OR nicMac2 = '$attrMacAddress2')";
               $result2 = dbquery($strSQL2);
               $numOfElements2 = mysql_num_rows($result2);
           }

           If (($numOfElements != 1) && ($numOfElements2 != 1)) {
               $checkFlag = 0;
           } ElseIf ($numOfElements == 1) {
               $row = mysql_fetch_array($result);
               $hardwareIDFromXML = $row['hardwareID'];
               $checkFlag = 1;
           } ElseIf ($numOfElements2 == 1) {
               $row2 = mysql_fetch_array($result2);
               $hardwareIDFromXML = $row2['hardwareID'];
               $checkFlag = 1;
           }
       }

       /*Function hostNameCheck($attrSystemNameFromSyslistData) {
           global $accountID;
           global $checkFlag;
           global $hardwareIDFromXML;
           $strSQL = "SELECT hardwareID FROM hardware WHERE hostname = '".addslashes($attrSystemNameFromSyslistData)."' AND accountID = $accountID AND hostname IS NOT NULL";
           $result = dbquery($strSQL);
           $numOfElements = mysql_num_rows($result);
           If ($numOfElements != 1) {
               $checkFlag = 0;
           } Else {
               $row = mysql_fetch_array($result);
               $hardwareIDFromXML = $row['hardwareID'];
               $checkFlag = 1;
           }
       }*/

       /* This function may be used in future.
       Function ipAddressCheck() {
           global $REMOTE_ADDR;
           global $accountID;
           global $checkFlag;
           global $hardwareIDFromXML;

           $strSQL = "SELECT hardwareID FROM hardware WHERE ipAddress = '$REMOTE_ADDR' AND accountID = $accountID AND ipAddress IS NOT NULL";
           $result = dbquery($strSQL);
           $numOfElements = mysql_num_rows($result);
           If ($numOfElements != 1) {
               $checkFlag = 0;
           } Else {
               $row = mysql_fetch_array($result);
               $hardwareIDFromXML = $row['hardwareID'];
               $checkFlag = 1;
           }
       }
       */

       #If a hardwareID is not numeric, it is invalid and if it is numeric but has no
       #corresponding accountID in the DB then it is invalid.
       Function validateHardwareID() {
           global $hardwareIDFromXML;
           global $attrUserName;
           global $attrPassword;
           global $accountID;
           global $systemAlertEmail;

           If (is_numeric($hardwareIDFromXML)) {
               $strSQL6 = "SELECT hardware.accountID FROM hardware, tblSecurity WHERE
                           tblSecurity.userID='$attrUserName' AND tblSecurity.password='$attrPassword'
                           AND tblSecurity.hidden='0' AND tblSecurity.securityLevel < 2 AND
                           hardware.hardwareID=$hardwareIDFromXML AND
                           hardware.accountID=tblSecurity.accountID";
               $result6 = dbquery($strSQL6);
               $row6    = mysql_fetch_array($result6);

               $numOfElements = mysql_num_rows($result6);

               If ($numOfElements == 0) {
                   Return 0;
               } Else {
                   $accountID = $row6['accountID'];
                   $strSQL7 = "SELECT tblSecurity.email FROM account_settings, tblSecurity WHERE
                     tblSecurity.id=account_settings.systemAlertUserID AND tblSecurity.accountID=" . $accountID . "";
                   $result7 = dbquery($strSQL7);
                   $row7    = mysql_fetch_row($result7);
                   $systemAlertEmail = $row7[0];
                   Return 1;
               }

           } Else {
               Return 0;
           }

       }
   }

   class SoftwareList extends cleaningFunctions {
       var $parser;

       Function SoftwareList ($fileName) {
           global $newSoftTraitIdArray;
           global $hardwareIDFromXML;
           global $accountID;
           global $progText1003, $progText370, $progText769, $progText472, $progText787, $progText1005;
           global $systemAlertEmail;
           global $captureSoftwareHistory;

           global $numOfElements_softTypes;
           global $result_softTypes;
           global $numOfElements_softTraitIDs;
           global $result_softTraitIDs;
           
           $this->parser = xml_parser_create("UTF-8");
           xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
           xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
           xml_set_object($this->parser, $this);
           xml_set_element_handler($this->parser, 'start_element', 'end_element');

           xml_parse($this->parser, $fileName);
           xml_parser_free($this->parser);

           # query for getting the complete list of softwareTraitID after the database
           # has been updated with entries from XML but the old entries in database which are
           # not present in the XML file are not yet deleted.
           $strSQL7 = "SELECT softwareTraitID FROM software WHERE hardwareID = $hardwareIDFromXML AND accountID = $accountID";
           $result7 = dbquery($strSQL7);

           $i = 0;
           While ($row7 = mysql_fetch_array($result7)) {
              # '$fullSoftIdArray' gives the array of values from the query above.
              $fullSoftIdArray[$i] = $row7['softwareTraitID'];
              $i++;
           }

           # Delete those 'softwareTraitID' which are not in 'newSoftTraitIdArray'.
           $toBeDeletedSoftIdArray = array_diff($fullSoftIdArray, $newSoftTraitIdArray);
           foreach($toBeDeletedSoftIdArray as $traitID) {
               $strSQL9 = "SELECT canBeMoved, uninstallNotify, visName, visVersion, visMaker FROM software_traits
                 WHERE softwareTraitID = $traitID AND accountID = $accountID";
               $result9 = dbquery($strSQL9);
               $row9 = mysql_fetch_array($result9);
               $varCanBeMoved = $row9['canBeMoved'];
               $uninstallNotify = $row9['uninstallNotify'];

               //Should we notify admin of uninstalled Software?
               if ($uninstallNotify=='1')
               {
                       $result = dbquery("SELECT hardware.hostname, tblSecurity.firstName, tblSecurity.middleInit, tblSecurity.lastName FROM hardware LEFT JOIN tblSecurity ON (hardware.userID=tblSecurity.id) WHERE hardware.hardwareID=$hardwareIDFromXML AND hardware.accountID=" . $accountID . "");
                       $row = mysql_fetch_array($result);
                       global $urlPrefix;
                       global $homeURL;
                       $msgBody = "$progText1003\n\n $progText370: " . $row['hostname'] . "\n $progText769: " .
                       (($row['firstName'] != "") ? (buildName($row['firstName'], $row['middleInit'], $row['lastName'])) : "[ $progText472 ]") .
                       "\n $progText787: " . writePrettySoftwareName($row9['visName'], $row9['visVersion'], $row9['visMaker']) .
                       "\n\n" . $urlPrefix . "://" . $homeURL . "/showfull.php?hardwareID=" . $hardwareIDFromXML;
                       mail($systemAlertEmail, ($progText1005.": ".date("m-d-Y H:i")), $msgBody, "From: $systemAlertEmail\r\nReply-To: $systemAlertEmail\r\n");
               }

               If ($varCanBeMoved == '0') {
                   $strSQL8 = "UPDATE software SET hidden = '2' WHERE softwareTraitID = $traitID AND hardwareID = $hardwareIDFromXML AND softwareTypeID IS NOT NULL AND accountID = $accountID";
                   $result8 = dbquery($strSQL8);
               } Else {
                   // Record software delete
                   if ($captureSoftwareHistory) {
                       $result = dbquery("INSERT INTO software_actions (softwareTraitID, hardwareID, actionType, actionDate, userID, movedToID, accountID) VALUES ($traitID, $hardwareIDFromXML, 'agentDel', " . date("YmdHis") . ", NULL, NULL, $accountID)");
                   }
                   $strSQL8 = "DELETE FROM software WHERE softwareTraitID = $traitID AND hardwareID = $hardwareIDFromXML AND softwareTypeID IS NOT NULL AND accountID=" . $accountID . "";
                   $result8 = dbquery($strSQL8);
               }
           }
       }

       Function start_element ($p, $element, $attributes) {
           global $numOfElements_softTypes;
           global $result_softTypes;
           global $numOfElements_softTraitIDs;
           global $result_softTraitIDs;

           global $hardwareIDFromXML;
           global $newSystemTypeID;

           # This array maintains the softwareTraitID of elements of xml file as it is being parsed.
           static $softTraitIdArray = array();
           static $softwareFromXMLArray = array();
           static $arrayIndex = 0;
           static $arrayIndex2 = 0;
           global $newSoftTraitIdArray;
           global $accountID;
           global $attrSCAVersion;
           global $progText1002, $progText370, $progText769, $progText472, $progText787, $progText1004;
           global $systemAlertEmail;

           #This variable makes sure that if the name of software is "" than it is not added to the DB.
           #If $isValid = 0 -> do not add the software to the DB
           #If $isValid = 1 -> add the software to the database.
           $isValid = 1;

           If (($element == "Program") OR ($element == "OperatingSystem")) {
               If ($element == "Program") {
                   $intOSinSoftwareTraits = '0';
                   $attrName            = trim($attributes['DisplayName']);
                   $attrDisplayVersion  = $attributes['DisplayVersion'];
                   $attrVersionMajor    = $attributes['VersionMajor'];
                   $attrVersionMinor    = $attributes['VersionMinor'];
                   $attrPublisher       = trim($attributes['Publisher']);
                   $attrSerial          = trim($attributes['SerialNumber']);
                   If ($attrName == "") {
                       $isValid = 0;
                   } ElseIf ((strpos($attrSCAVersion, 'MACOS') !== FALSE) AND
                             (strpos(strtolower($attrPublisher), "com.apple.pkg") !== FALSE) AND
                             (strpos(strtolower($attrPublisher), "com.apple.pkg.secupd") === FALSE) AND
                             (strpos(strtolower($attrPublisher), "com.apple.pkg.macosxupdate") === FALSE)) {
                       $isValid = 0;
                   }

               } ElseIf ($element == "OperatingSystem") {
                   $intOSinSoftwareTraits = '1';
                   $attrName            = trim($attributes['OSName']);
                   $attrDisplayVersion  = $attributes['CSDVersion'];
                   $attrPublisher       = $attributes['Manufacturer'];
                   $attrSerial          = trim($attributes['SerialNumber']);
                   If ($attrName == "") {
                       $isValid = 0;
                   }
               }
              
			   // Looking for exact duplicate software entries in the XML, in order to eliminate them
               $softCompareVar = $attrName.",".$attrVersion.",".$attrPublisher;
               If (array_search($softCompareVar, $softwareFromXMLArray)!==FALSE) {
                   // The xml contains duplicate records for this software type; move on to the next element
                   $isValid = 0; # dupe found
               } Else {
                   // put current xml software type in hash for future comparison
                   $softwareFromXMLArray[$arrayIndex2] = $softCompareVar;
                   $arrayIndex2++;
               }
                          
               If ($isValid == 1) {

                   # 'Version' selected according to order of precedence.
                   $attrVersion = $this->selectVersion($attrDisplayVersion, $attrVersionMajor, $attrVersionMinor);
                   If (strlen($attrVersion) == strlen($attrName)) {
                       $attrVersion = "";
                   }

                   // attrVisName must come after attrPublisher for software
                   $attrVisPublisher = $this->cleanManufacturer($attrPublisher, $attrName);
                   $attrVisName = $this->cleanName($attrName, $attrPublisher, $attrVersion);

                   If ($numOfElements_softTypes) {
                       mysql_data_seek($result_softTypes, 0);
                   }

                   $count = 1;
                   # This 'If' statement makes sure that if the database is empty, the xml values are inserted without any further checks.
                   If ($numOfElements_softTypes == 0) {
                       $strSQL4 = "INSERT INTO software_traits (visName, visMaker, visVersion, operatingSystem, accountID) VALUES ('".addslashes($attrVisName)."', ".makeNull(addslashes($attrVisPublisher), TRUE).", ".makeNull(addslashes($attrVersion), TRUE).", '$intOSinSoftwareTraits', $accountID)";
                       $result4 = dbquery($strSQL4);
                       
                       // Sends alert to system contact if user set the setting
                       maybeAlertAboutNewSoftwareType($attrVisName, $attrVersion, $attrPublisher);

                       $softTraitID = mysql_insert_id();
                       $strSQL5 = "INSERT INTO software_types (softwareTraitID, Name, Maker, Version, accountID) VALUES ($softTraitID, '".addslashes($attrName)."', ".makeNull(addslashes($attrPublisher), TRUE).", ".makeNull(addslashes($attrVersion), TRUE).", $accountID)";
                       $result5 = dbquery($strSQL5);

                       $softTypeID = mysql_insert_id();
                       $strSQL6 = "INSERT INTO software (softwareTypeID, softwareTraitID, hardwareID, serial, creationDate, accountID) VALUES (".makeNull($softTypeID, FALSE).", $softTraitID, ".makeNull($hardwareIDFromXML, FALSE).", ".makeNull($attrSerial, TRUE).", NOW(), $accountID)";
                       $result6 = dbquery($strSQL6);

                       If ($newSystemTypeID) {
                           $strSQL7 = "INSERT INTO hardware_type_defaults (hardwareTypeID, objectID, objectType, accountID) VALUES ($newSystemTypeID, $softTraitID, 's', $accountID)";
                           $result7 = dbquery($strSQL7);
                       }

                       $softTraitIdArray[$arrayIndex] = $softTraitID;
                       $arrayIndex++;

                   } Else {

                       If ($numOfElements_softTraitIDs) {
                           mysql_data_seek($result_softTraitIDs, 0);
                       }

                      While ($row = mysql_fetch_array($result_softTypes)) {
                          $nameFromSoftTypesTable     = $row['Name'];
                          $versionFromSoftTypesTable  = $row['Version'];
                          $makerFromSoftTypesTable    = $row['Maker'];
                          $traitIDFromSoftTypesTable  = $row['softwareTraitID'];
                          $varSoftTypeID              = $row['softwareTypeID'];
                          $hiddenVar                  = $row['hidden'];
                          $universalSerialVar         = $row['universalSerial'];
                          $installNotify              = $row['installNotify'];
                          $intVendorID                = $row['universalVendorID'];
                          $forMailVisName             = $row['visName'];
                          $forMailVisVersion          = $row['visVersion'];
                          $forMailVisMaker            = $row['visMaker'];

                          #Check to see if the software_type already exists. This is done by comparing 'name', 'version' and 'publisher'.
                          If (($attrName == $nameFromSoftTypesTable) && ($attrVersion == $versionFromSoftTypesTable) && ($attrPublisher == $makerFromSoftTypesTable)) {

                              # This 'If' statement handles the case when a new xml file with a new hardware ID is parsed.
                              If ($numOfElements_softTraitIDs == 0) {
                                  #This query creates a software instance with the new hardware ID.
                                  $strSQL6 = "INSERT INTO software (softwareTypeID, softwareTraitID, hardwareID, serial, creationDate, accountID) VALUES (".makeNull($varSoftTypeID, FALSE).", $traitIDFromSoftTypesTable, ".makeNull($hardwareIDFromXML, FALSE).", ".makeNull($attrSerial, TRUE).", NOW(), $accountID)";
                                  $result6 = dbquery($strSQL6);

                                  If ($newSystemTypeID) {
                                      $strSQL7 = "INSERT INTO hardware_type_defaults (hardwareTypeID, objectID, objectType, accountID) VALUES ($newSystemTypeID, $traitIDFromSoftTypesTable, 's', $accountID)";
                                      $result7 = dbquery($strSQL7);
                                  }

                                  $softTraitIdArray[$arrayIndex] = $traitIDFromSoftTypesTable;
                                  $arrayIndex++;
                              }

                              $count2 = 1;
                              While($row2 = mysql_fetch_array($result_softTraitIDs)) {
                                  $traitIDfromSoftwareTable = $row2['softwareTraitID'];

                                  #If 'software' has a 'softwareTraitID' of this 'software type', just store the
                                  #softwareTraitID in the static array and get the next element from xml file.
                                  If ($traitIDfromSoftwareTable == $traitIDFromSoftTypesTable) {
                                      $softTraitIdArray[$arrayIndex] = $traitIDFromSoftTypesTable;
                                      $arrayIndex++;
                                      break;
                                  } Else {

                                      #If 'software' does not have this 'sofwareTraitID', insert it into 'software'.
                                      #Do this only if the 'software_traits' is not marked 'hidden = 1' for this 'softwareTraitID'.
                                      If ($count2 == $numOfElements_softTraitIDs) {

                                          //Should we notify admin of installed Software?
                                          if ($installNotify=='1')
                                          {
                                                   $result = dbquery("SELECT hardware.hostname, tblSecurity.firstName, tblSecurity.middleInit, tblSecurity.lastName FROM hardware LEFT JOIN tblSecurity ON (hardware.userID=tblSecurity.id) WHERE hardware.hardwareID=$hardwareIDFromXML AND hardware.accountID=" . $accountID . "");
                                                   $row = mysql_fetch_array($result);
                                                   global $urlPrefix;
                                                   global $homeURL;
                                                   $msgBody = "$progText1002\n\n $progText370: " . $row['hostname'] . "\n $progText769: " .
                                                    (($row['firstName'] != "") ? (buildName($row['firstName'], $row['middleInit'], $row['lastName'])) : "[ $progText472 ]") .
                                                   "\n $progText787: " . writePrettySoftwareName($forMailVisName, $forMailVisVersion, $forMailVisMaker) .
                                                   "\n\n" . $urlPrefix . "://" . $homeURL . "/showfull.php?hardwareID=" . $hardwareIDFromXML;
                                                   mail($systemAlertEmail, ($progText1004.": ".date("m-d-Y H:i")), $msgBody, "From: $systemAlertEmail\r\nReply-To: $systemAlertEmail\r\n");
                                          }
                                          If ($hiddenVar != '1') {
                                              If ($universalSerialVar) {
                                                  $strSerialNum = $universalSerialVar;
                                              } Else {
                                                  $strSerialNum = $attrSerial;
                                              }
                                              $strSQL3 = "INSERT INTO software (softwareTypeID, softwareTraitID, hardwareID, serial, vendorID, creationDate, accountID) VALUES (".makeNull($varSoftTypeID, FALSE).", $traitIDFromSoftTypesTable, ".makeNull($hardwareIDFromXML, FALSE).", ".makeNull($strSerialNum, TRUE).", ".makeNull($intVendorID).", NOW(), $accountID)";
                                              $result3 = dbquery($strSQL3);

                                              If ($newSystemTypeID) {
                                                  $strSQL7 = "INSERT INTO hardware_type_defaults (hardwareTypeID, objectID, objectType, accountID) VALUES ($newSystemTypeID, $traitIDFromSoftTypesTable, 's', $accountID)";
                                                  $result7 = dbquery($strSQL7);
                                              }

                                              $softTraitIdArray[$arrayIndex] = $traitIDFromSoftTypesTable;
                                              $arrayIndex++;
                                          }
                                      }
                                      $count2++;
                                  }
                              }
                              break;

                          } ElseIf ($count == $numOfElements_softTypes) {
                              #If 'software type' does not exist, create it, create new 'software_traits' and create new 'software' instance.

                              $strSQL4 = "INSERT INTO software_traits (visName, visMaker, visVersion, operatingSystem, accountID) VALUES ('".addslashes($attrVisName)."', ".makeNull(addslashes($attrVisPublisher), TRUE).", ".makeNull(addslashes($attrVersion), TRUE).", '$intOSinSoftwareTraits', $accountID)";
                              $result4 = dbquery($strSQL4);

                              // Sends alert to system contact if user set the setting
                              maybeAlertAboutNewSoftwareType($attrVisName, $attrVersion, $attrPublisher);

                              $softTraitID = mysql_insert_id();
                              $strSQL5 = "INSERT INTO software_types (softwareTraitID, Name, Maker, Version, accountID) VALUES ($softTraitID, '".addslashes($attrName)."', ".makeNull(addslashes($attrPublisher), TRUE).", ".makeNull(addslashes($attrVersion), TRUE).", $accountID)";
                              $result5 = dbquery($strSQL5);

                              $softTypeID = mysql_insert_id();
                              $strSQL6 = "INSERT INTO software (softwareTypeID, softwareTraitID, hardwareID, serial, creationDate, accountID) VALUES (".makeNull($softTypeID, FALSE).", $softTraitID, ".makeNull($hardwareIDFromXML, FALSE).", ".makeNull($attrSerial, TRUE).", NOW(), $accountID)";
                              $result6 = dbquery($strSQL6);

                              If ($newSystemTypeID) {
                                  $strSQL7 = "INSERT INTO hardware_type_defaults (hardwareTypeID, objectID, objectType, accountID) VALUES ($newSystemTypeID, $softTraitID, 's', $accountID)";
                                  $result7 = dbquery($strSQL7);
                              }

                              $softTraitIdArray[$arrayIndex] = $softTraitID;
                              $arrayIndex++;
                          }
                          $count++;
                      }
                   }
                   
               # Store the softwareTraitIDs in a 'global' array so that it can be used to filter out old values, which is done
               # in the constructor.
               $newSoftTraitIdArray = $softTraitIdArray;
               }
           }
       }
       # This function is used when the end of an element is reached in xml file.
       Function end_element($p, $element) {
       }

       # Function to select the 'version' from the attributes in the order of precedence.
       Function selectVersion($attrDispVer, $attrVerMaj, $attrVerMin) {
           If (($attrVerMaj) && (($attrVerMin == "0") OR ($attrVerMin))) {
               $attrVer = "$attrVerMaj."."$attrVerMin";
           } ElseIf ($attrVerMaj) {
               $attrVer = $attrVerMaj;
           } ElseIf ($attrVerMin) {
               $attrVer = $attrVerMin;
           } ElseIf ($attrDispVer) {
               $attrVer = $attrDispVer;
           }
           $attrVer = trim($attrVer);
           Return $attrVer;
       }
   }

   class PeripheralList extends cleaningFunctions {
       var $parser;

       Function PeripheralList ($fileName) {
           global $newPeriphTraitIdArray;
           global $hardwareIDFromXML;
           global $accountID;

           $this->parser = xml_parser_create("UTF-8");
           xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
           xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
           xml_set_object($this->parser, $this);
           xml_set_element_handler($this->parser, 'start_element', 'end_element');

           xml_parse($this->parser, $fileName);
           xml_parser_free($this->parser);

           $strSQL99 = "UNLOCK TABLES";
           $result99 = dbquery($strSQL99);

           $updateStatus = "Pass";
           $errorNum = 0;
           errorReporting($hardwareIDFromXML, $updateStatus, $errorNum);
       }

       Function start_element ($p, $element, $attributes) {
           global $hardwareIDFromXML;
           global $newSystemTypeID;

           # This array maintains the peripheralTraitID of elements of xml file as it is being parsed.
           static $periphTraitIdArray = array();

           static $arrayIndex = 0;
           global $newPeriphTraitIdArray;
           global $accountID;

           global $aryDB;
           global $aryXML;
           global $aryLD;

           If (($element == "CPU") OR ($element == "Optical") OR ($element == "Disk") OR ($element == "NetAdapter") OR ($element == "KeyBoard")
           OR ($element == "Printer") OR ($element == "PointingDevice") OR ($element == "DisplayAdaptor") OR ($element == "Monitors") OR ($element == "MemoryInfo")
           OR ($element == "LogicalDisk")) {

               Switch ($element) {
                   case 'CPU':
                       $isValid = 1;

                       $attrManufacturer = $attributes['Vendor'];
                       $attrName = $attributes['Name'];
                       $attrSpeed = $attributes['Speed'];
                       $attrID = $attributes['ID'];

                       # This 'If' statement gives the correct combination of attributes to be placed
                       # in the database as 'description'.
                       If($attrName) {
                           If($attrSpeed) {
                               $attrDescription = $attrName."  (".$attrSpeed.")";
                           } Else {
                               $attrDescription = $attrName;
                           }
                       } ElseIf ($attrSpeed) {
                           If ($attrID) {
                               $attrDescription = $attrSpeed." ".$attrID;
                           }
                       } Else {
                           $attrDescription = $attrID;
                       }
                       $attrDescription = trim($attrDescription);

                       $attrTypeClass = 'processor';
                       $attrVisTypeClass = 'processor';

                       #'$attrModel' may change in future. Its NULL for now.
                       $attrModel = "";
                       break;

                   case 'Optical':
                       $isValid = 1;

                       $attrManufacturer = $attributes['Manufacturer'];
                       $attrManufacturer = $this->checkForStandard($attrManufacturer);

                       $attrDescription = trim($attributes['Model']);
                       If (!$attrDescription) {
                           $attrDescription = trim($attributes['Description']);
                       }

                       $attrTypeClass = 'opticalStorage';
                       $attrVisTypeClass = 'opticalStorage';

                       $attrModel = "";
                       break;

                   case 'Disk':
                       $isValid = 1;

                       $attrManufacturer = $attributes['Manufacturer'];
                       $attrManufacturer = $this->checkForStandard($attrManufacturer);

                       $attrDescription = $attributes['Model'];
                       If (!$attrDescription) {
                           $attrDescription = $attributes['Description'];
                       }

                       $attrSize = $attributes['Size'];
                       If ($attrSize) {
                           $attrDescription = $attrDescription."  (".$attrSize.")";
                       }

                       $attrTypeClass = 'diskStorage';
                       $attrVisTypeClass = 'diskStorage';

                       $attrModel = "";
                       break;

                   case 'NetAdapter':
                       $isValid = 1;
                       $attrDescription   = trim($attributes['Description']);
                       $attrManufacturer  = trim($attributes['Manufacturer']);
                       $attrType          = trim($attributes['Type']);
                       $attrCaption       = trim($attributes['Caption']);
                       $attrIPSubNet1     = trim($attributes['IPSubnet']);
                       $attrIPSubNet2     = trim($attributes['IPSubNet']);
                       $attrIPAddress     = trim($attributes['IPAddress']);

                       # If not a valid physical netadapter, no instance of this netadapter is
                       # created in peripherals, peripheral_types and peripheral_traits. This is done by
                       # making the variable $isValid = 0.
                       If ((strpos(strtolower($attrManufacturer), 'microsoft') !== FALSE) OR
                           (strpos($attrCaption, 'WAN') !== FALSE) OR (strpos($attrDescription, 'WAN') !== FALSE) OR
                           (strpos($attrCaption, 'VPN') !== FALSE) OR (strpos($attrDescription, 'VPN') !== FALSE) OR
                           (($attrIPAddress == "0.0.0.0") AND (($attrIPSubNet1 == "0.0.0.0") OR ($attrIPSubNet2 == "0.0.0.0"))) OR
                           (strpos(strtolower($attrCaption), 'miniport') !== FALSE) OR
                             (strpos(strtolower($attrDescription), 'miniport') !== FALSE) OR
                           (strpos(strtolower($attrCaption), 'ppp adapter') !== FALSE) OR
                             (strpos(strtolower($attrDescription), 'ppp adapter') !== FALSE) OR
                           (strpos(strtolower($attrType), 'cowan') !== FALSE)) {
                               $isValid = 0;
                       }

                       $attrTypeClass = 'netAdapter';
                       $attrVisTypeClass = 'netAdapter';

                       $attrModel = "";
                       break;

                   case 'KeyBoard':
                       $isValid = 1;

                       $attrManufacturer = $attributes['Manufacturer'];
                       $attrManufacturer = $this->checkForStandard($attrManufacturer);

                       $attrDescription = $attributes['Description'];

                       $attrTypeClass = 'keyboard';
                       $attrVisTypeClass = 'keyboard';

                       $attrModel = $attributes['Model'];
                       break;

                   case 'Printer':
                       $attrPortName      = $attributes['PortName'];
                       $attrManufacturer  = $attributes['Manufacturer'];
                       $attrDescription   = $attributes['Name'];

                       $isValid = $this->checkValidityForPrinter($attrPortName, $attrDescription);

                       $attrTypeClass = 'printer';
                       $attrVisTypeClass = 'printer';

                       $attrModel = $attributes['Model'];
                       break;

                   case 'PointingDevice':
                       $isValid = 1;

                       $attrManufacturer = $attributes['Manufacturer'];
                       $attrManufacturer = $this->checkForStandard($attrManufacturer);

                       $attrDescription = $attributes['Description'];

                       $attrTypeClass = 'pointingDevice';
                       $attrVisTypeClass = 'pointingDevice';

                       $attrModel = $attributes['Model'];
                       break;

                   case 'DisplayAdaptor':
                       $isValid = 1;

                       $attrManufacturer = $attributes['Manufacturer'];
                       $attrDescription = $attributes['Description'];

                       $attrTypeClass = 'displayAdaptor';
                       $attrVisTypeClass = 'displayAdaptor';

                       $attrModel = $attributes['Model'];
                       $attrFileID = $attributes['FileID'];

                       break;

                   case 'Monitors':
                       $isValid = 1;

                       $attrTypeClass = 'monitor';
                       $attrVisTypeClass = 'monitor';

                       $attrManufacturer = $attributes['Manufacturer'];
                       $attrManufacturer = $this->checkForStandard($attrManufacturer);


                       $attrDescription = $attributes['Description'];
                       If ((strpos(strtolower($attrDescription), 'default monitor') !== FALSE) OR
                           (strpos(strtolower($attrDescription), 'generic monitor') !== FALSE) OR
                           (strpos(strtolower($attrDescription), 'generic television') !== FALSE)) {

                           // Only add one of these generic types if there is no other option
                           // (this should be improved to actually check the other options...)
                           global $xmlFile;
                           If (substr_count($xmlFile, "<Monitors") > 1) {
                               $isValid = 0;
                           }
                        }

                        $attrModel = "";

                        break;

                   case 'MemoryInfo':
                        $isValid = 1;

                        $attrTypeClass = 'RAM';
                        $attrVisTypeClass = 'RAM';

                        $attrDescription = $attributes['TotalMemory'];
                        $attrDescription = $attrDescription." ".'RAM';

                        $attrManufacturer = $attributes['Manufacturer'];

                        $attrModel = "";

                        break;
                        
                   case 'LogicalDisk':
                        $isValid = 0; # not really invalid, just handled differently
                        $isLogicalDisk = 1;
                        $attrName = $attributes['Name'];
                        $attrVolumeName = $attributes['VolumeName'];
                        $attrFileSystem = $attributes['FileSystem'];
                        $attrSize = $attributes['Size'];
                        $attrFreeSpace = $attributes['FreeSpace'];
                        
                        break;
                        
               }

               $attrVersion = ""; # the agent does not provide peripheral version text, so we make this parameter ""
               $attrDescription = trim($attrDescription);
               If ((strpos(strtolower($attrDescription), 'vmware') !== FALSE) OR ($attrDescription == "")) {
                   $isValid = 0;
               }

               $attrVisDescription = $this->cleanName($attrDescription, $attrManufacturer, $attrVersion);
               $attrVisManufacturer = $this->cleanManufacturer($attrManufacturer);

               $strSQL77 = "SELECT * FROM peripherals WHERE hardwareID = $hardwareIDFromXML AND accountID = $accountID";
               $result77 = dbquery($strSQL77);
               $numOfElements77 = mysql_num_rows($result77);

               $i = 0;
               While ($row77 = mysql_fetch_array($result77)) {
                   // This is ugly. Don't add to this list if the peripheral's disconnected,
                   // that way we can sneak the peripheral into the diff routine as an insert
                   if ($row77['peripheralStatus'] != 'd') {
                       # $aryDB store the peripheralTraitID and peripheralTypeID that already exist in the DB.
                       $aryDB[$i] = $row77['peripheralTraitID'].",".$row77['peripheralTypeID'];
                       $i++;
                   }
               }

               # This query gets the values in 'peripheral_types' in order to compare it with values in xml attributes.
               $strSQL = "SELECT manufacturer, description, model, peripheralTypeID, peripheralTraitID FROM peripheral_types WHERE accountID = $accountID";
               $result = dbquery($strSQL);
               $numOfElements = mysql_num_rows($result);
               $count = 1;

               # This 'If' statement makes sure that if the database is empty, the xml values are inserted without any further checks.
               If (($numOfElements == 0) && ($isValid == 1)) {

                   $strSQL4 = "INSERT INTO peripheral_traits (visManufacturer, visDescription, visTypeClass, visModel, accountID) VALUES (".makeNull(addslashes($attrVisManufacturer), TRUE).", '".addslashes($attrVisDescription)."', ".makeNull($attrVisTypeClass, TRUE).", ".makeNull(addslashes($attrModel), TRUE).", $accountID)";
                   $result4 = dbquery($strSQL4);

                   $periphTraitID = mysql_insert_id();
                   $strSQL5 = "INSERT INTO peripheral_types (peripheralTraitID, manufacturer, description, typeClass, model, accountID) VALUES ($periphTraitID, ".makeNull(addslashes($attrManufacturer), TRUE).", '".addslashes($attrDescription)."', ".makeNull($attrTypeClass, TRUE).", ".makeNull(addslashes($attrModel), TRUE).", $accountID)";
                   $result5 = dbquery($strSQL5);

                   $periphTypeID = mysql_insert_id();

                   #$aryXML stores the traitID and typeID of the elements in the XML file.
                   $aryXML[$arrayIndex] = $periphTraitID.",".$periphTypeID;
                   $arrayIndex++;
               }

               If ($isValid == 1) {

                   While ($row = mysql_fetch_array($result)) {

                       $manufacturerFromPeriphTypesTable = $row['manufacturer'];
                       $descriptionFromPeriphTypesTable = $row['description'];
                       $traitIDFromPeriphTypesTable = $row['peripheralTraitID'];
                       $varPeriphTypeID = $row['peripheralTypeID'];

                       #Check to see if the peripheral_type already exists. This is done by comparing 'manufacturer' and 'description'.
                       If (($attrManufacturer == $manufacturerFromPeriphTypesTable) && ($attrDescription == $descriptionFromPeriphTypesTable)) {

                           $aryXML[$arrayIndex] = $traitIDFromPeriphTypesTable.",".$varPeriphTypeID;
                           $arrayIndex++;

                       break;
                       } ElseIf ($count == $numOfElements) {

                           #If 'peripheral type' does not exist, create it and create new 'peripheral_traits' instance.
                           #store the new typeID and traitID into $aryXML.
                           $strSQL4 = "INSERT INTO peripheral_traits (visManufacturer, visDescription, visTypeClass, visModel, accountID) VALUES (".makeNull(addslashes($attrVisManufacturer), TRUE).", '".addslashes($attrVisDescription)."', '$attrVisTypeClass', ".makeNull(addslashes($attrModel), TRUE).", $accountID)";
                           $result4 = dbquery($strSQL4);

                           $periphTraitID = mysql_insert_id();
                           $strSQL5 = "INSERT INTO peripheral_types (peripheralTraitID, manufacturer, description, typeClass, model, accountID) VALUES ($periphTraitID, ".makeNull(addslashes($attrManufacturer), TRUE).", '".addslashes($attrDescription)."', ".makeNull($attrTypeClass, TRUE).", ".makeNull(addslashes($attrModel), TRUE).", $accountID)";
                           $result5 = dbquery($strSQL5);

                           $periphTypeID = mysql_insert_id();

                           $aryXML[$arrayIndex] = $periphTraitID.",".$periphTypeID;
                           $arrayIndex++;

                       }
                       $count++;
                   }
               } elseif ($isLogicalDisk == 1) {
                   // Avoid pass by reference issues
                   array_push($aryLD, array($attrName, $attrVolumeName, $attrFileSystem, $attrSize, $attrFreeSpace));
               }                   
           }
       }

       # This functiom is used when the end of an element is reached in xml file.
       Function end_element($p, $element) {
           global $aryDB;
           global $aryXML;
           global $aryLD;
           
           $insert = 1;
           $delete = 2;
           If ($element == 'SyslistData') {
               $this->customArrayDiff($aryXML, $aryDB, $insert);
               $this->customArrayDiff($aryDB, $aryXML, $delete);
               $this->logicalDiskDiff($aryLD);
           }

       }
       
       Function logicalDiskDiff($ary) {
           global $hardwareIDFromXML, $accountID;
           $result = dbquery("DELETE FROM logicaldisks WHERE hardwareID = $hardwareIDFromXML AND accountID = $accountID");
           foreach($ary as $ld) {
               $result = dbquery(sprintf("INSERT INTO logicaldisks (hardwareID, name, volumeName, fileSystem, size, freeSpace, accountID) 
                                                 VALUES (%s, %s, %s, %s, %s, %s, %s)",
                                                 $hardwareIDFromXML, makeNull(addslashes($ld[0]), TRUE), makeNull(addslashes($ld[1]), TRUE), makeNull(addslashes($ld[2]), TRUE), makeNull(addslashes($ld[3]), TRUE), makeNull(addslashes($ld[4]), TRUE), $accountID));
           }
       }
       
       #Inserts values in $ary1 which are not present in $ary2 when $ary1 is from
       #XML and $ary2 is from DB.
       #Deletes values in $ary1 which are not in $ary2, from DB when $ary1 is from
       #DB and $ary2 is from XML.
       Function customArrayDiff($ary1, $ary2, $insertORdelete) {
           $numOfRepeats = 0;
           global $hardwareIDFromXML, $accountID, $newSystemTypeID, $systemAlertEmail;
           global $progText370, $progText472, $progText767, $progText769, $progText1000, $progText1001;

           $num1 = count($ary1);
           $num2 = count($ary2);

           $copyOfary1= $ary1;
           foreach($ary1 as $traitTypeID) {
               // Um, a weird way to figure out how many times an item appears in two lists.
               $count = 0;
               $counter1 = 0;
               While($count < $num1) {
                   If ($traitTypeID == $copyOfary1[$count]) {
                       $copyOfary1[$count] = "";
                       $counter1++;
                   }
                   $count++;
               }
               $count = 0;
               $counter2 = 0;
               While ($count < $num2) {
                   If ($traitTypeID == $ary2[$count]) {
                       $counter2++;
                   }
                   $count++;
               }

               If ($counter1 > $counter2) {
                   #variable $numOfRepeats gives the number of times a particular element should be inserted
                   #or deleted.
                   $numOfRepeats = $counter1 - $counter2;
                   $aryPeriphIDs = explode(",", $traitTypeID);

                   $aryPeriphTrait = $aryPeriphIDs[0];
                   $aryPeriphType = $aryPeriphIDs[1];

                   $strSQL66 = "SELECT hidden, preserve, notify, visManufacturer, visModel, visDescription, visTypeClass, universalVendorID FROM peripheral_traits WHERE peripheralTraitID = $aryPeriphTrait AND accountID = $accountID";
                   $result66 = dbquery($strSQL66);
                   $row66 = mysql_fetch_array($result66);
                   $hiddenVar    = $row66['hidden'];
                   $intVendorID  = $row66['universalVendorID'];
                   // We need the peripheral status of the current peripheral to determine whether
                   // (1) the peripheral doesn't exist at all and we're inserting it or
                   // (2) it's just disconnected and being reconnected.
                   $resultOfPeriphStatus = dbquery("SELECT peripheralStatus FROM peripherals WHERE peripheralTraitID=$aryPeriphTrait AND hardwareID=$hardwareIDFromXML AND peripheralTypeID IS NOT NULL AND accountID=" . $accountID . "");
                   For ($i=0; $i < $numOfRepeats; $i++) {
                        $rowOfPeriphStatus = mysql_fetch_array($resultOfPeriphStatus);
                        // Since we need the fact that the peripheral status is going to be 'd' (disconnected)
                        // to determine whether we should send the notification e-mail, the following conditionals
                        // need to be somewhat in this order

                        // Should we notify admin of disconnected peripheral?
                        If (($insertORdelete === 2) && ($numOfRepeats !== 0) && ($row66['notify'] == '1') && ($rowOfPeriphStatus['peripheralStatus'] != 'd')) {
                           $result = dbquery("SELECT hardware.hostname, tblSecurity.firstName, tblSecurity.middleInit, tblSecurity.lastName FROM hardware LEFT JOIN tblSecurity ON (hardware.userID=tblSecurity.id) WHERE hardware.hardwareID=$hardwareIDFromXML AND hardware.accountID=" . $accountID . "");
                           $row = mysql_fetch_array($result);

                           global $urlPrefix;
                           global $homeURL;
                           $msgBody = "$progText1000\n\n $progText370: " . $row['hostname'] . "\n $progText769: " .
                             (($row['firstName'] != "") ? (buildName($row['firstName'], $row['middleInit'], $row['lastName'])) : "[ $progText472 ]") .
                             "\n $progText767: " . writePrettyPeripheralName($row66['visDescription'], $row66['visModel'], $row66['visManufacturer']) .
                             "\n\n" . $urlPrefix . "://" . $homeURL . "/showfull.php?hardwareID=" . $hardwareIDFromXML;
                           mail($systemAlertEmail, ($progText1001.": ".date("m-d-Y H:i")), $msgBody, "From: $systemAlertEmail\r\nReply-To: $systemAlertEmail\r\n");
                        }
                        // Is the peripheral being reconnected?
                        If (($insertORdelete === 1) && ($row66['preserve'] == '1') && ($rowOfPeriphStatus['peripheralStatus'] === 'd')) {
                           dbquery("UPDATE peripherals SET peripheralStatus='w' WHERE peripheralStatus='d' AND peripheralTraitID=$aryPeriphTrait AND hardwareID=$hardwareIDFromXML AND peripheralTypeID IS NOT NULL AND accountID=" . $accountID . " LIMIT 1");
                        }
                        // Inserted?
                        If (($insertORdelete === 1) && ($hiddenVar != 1) && (($row66['preserve'] == '0') || (!isset($rowOfPeriphStatus['peripheralStatus'])))) {
                           $strSQL = "INSERT INTO peripherals (peripheralTraitID, peripheralTypeID, hardwareID, vendorID, accountID) VALUES ($aryPeriphTrait, $aryPeriphType, $hardwareIDFromXML, ".makeNull($intVendorID).", $accountID)";
                           $result = dbquery($strSQL);
                           If ($newSystemTypeID) {
                               $strSQL7 = "INSERT INTO hardware_type_defaults (hardwareTypeID, objectID, objectType, accountID) VALUES ($newSystemTypeID, $aryPeriphTrait, 'p', $accountID)";
                               $result7 = dbquery($strSQL7);
                           }
                        // Deleted?
                        } ElseIf (($insertORdelete === 2) && ($numOfRepeats !== 0) && ($row66['preserve'] == '0')) {
                           $strSQL = "DELETE FROM peripherals WHERE peripheralTraitID = $aryPeriphTrait AND hardwareID = $hardwareIDFromXML AND peripheralTypeID IS NOT NULL AND accountID = $accountID LIMIT 1";
                           $result = dbquery($strSQL);
                           // Record this transaction
                           $result = dbquery("INSERT INTO peripheral_actions (peripheralTraitID, hardwareID, actionType, actionDate, userID, movedToID, accountID) VALUES ($aryPeriphTrait, $hardwareIDFromXML, 'agentDel', " . date("YmdHis") . ", NULL, NULL, $accountID)");
                        // Disconnected?
                        } ElseIf (($insertORdelete === 2) && ($numOfRepeats !== 0) && ($row66['preserve'] == '1')) {
                           dbquery("UPDATE peripherals SET peripheralStatus='d' WHERE peripheralStatus<>'d' AND peripheralTraitID=$aryPeriphTrait AND hardwareID=$hardwareIDFromXML AND peripheralTypeID IS NOT NULL AND accountID=" . $accountID . " LIMIT 1");
                        }
                   }
               }
           }
       }

       #This function looks for the following five strings in the manufacturer name and
       #and if found replaces them by ""
       Function checkForStandard($str) {

           $str0 = strtolower($str);
           $str1 = 'standard disk drive';
           $str2 = 'standard cdrom drive';
           $str3 = 'standard keyboard';
           $str4 = 'standard cd-rom drive';
           $str5 = 'standard monitor type';
           $str6 = 'standard system device';
           $str7 = 'standard mouse type';

           $pos1 = strpos($str0, $str1);
           $pos2 = strpos($str0, $str2);
           $pos3 = strpos($str0, $str3);
           $pos4 = strpos($str0, $str4);
           $pos5 = strpos($str0, $str5);
           $pos6 = strpos($str0, $str6);
           $pos7 = strpos($str0, $str7);

           If (($pos1 !== FALSE) OR ($pos2 !== FALSE) OR ($pos3 !== FALSE) OR ($pos4 !== FALSE) OR
               ($pos5 !== FALSE) OR ($pos6 !== FALSE) OR ($pos7 !== FALSE)) {

               $str = "";
           }
           Return $str;
       }

       Function checkValidityForPrinter($attrPortName, $attrDescription) {
           $validPrinter = 0;

           If ((strpos(strtolower($attrPortName), 'usb') !== FALSE) OR
               (strpos(strtolower($attrPortName), 'lpt') !== FALSE)) {

               If ((strpos($attrDescription, 'PDF') === FALSE) &&
                   (strpos(strtolower($attrDescription), 'driver') === FALSE) &&
                   (strpos(strtolower($attrDescription), 'acrobat') === FALSE) &&
                   (strpos(strtolower($attrDescription), 'document loader') === FALSE)) {

                   $validPrinter = 1;
               }
           }
           Return $validPrinter;
       }
   }

   # xml file is checked for invalid (special) character and if present, deleted
   $xmlFileName = $_FILES['SyslistAgentData']['tmp_name'];
   If (is_uploaded_file($xmlFileName)) {
       If ($_FILES['SyslistAgentData']['size'] < 200000) {
           // better performance, requires PHP 4.3.0
           # $xmlFile = file_get_contents($xmlFileName);
           $fbaby = fopen($xmlFileName, "r");
           $xmlFile = fread($fbaby, filesize($xmlFileName));
           fclose($fbaby);
           unlink($xmlFileName);
       }
   }

   $isXMLComplete = strpos($xmlFile, "</SyslistData>");
   If (!$isXMLComplete) {
       $updateStatus = 'Fail';
       $errorNum = 4;
       $hardwareID = "";
       errorreporting($hardwareID, $updateStatus, $errorNum);
       exit();
   }
   If (strpos($xmlFile, '<BaseBoard') === FALSE) {
       $numOfElementsReqForSystems++;
   }
   If (strpos($xmlFile, '<CompSystem') === FALSE) {
       $numOfElementsReqForSystems++;
   }
   If (strpos($xmlFile, '<ProcessorList') === FALSE) {
       $numOfElementsReqForSystems++;
   }
   If ($numOfElementsReqForSystems === 3) {
       #XML file does not have any of the 3 (BaseBoard, CompSystem, ProcessorList) required elements.
       $updateStatus = 'Fail';
       $errorNum = 3;
       $hardwareID = "";
       errorreporting($hardwareID, $updateStatus, $errorNum);
       exit();
   }
   If (strpos($xmlFile, '<NetAdapterList') === FALSE) {
       $numOfElementsReqForSystems++;
   }

   // do not filter anything that comes before "Syslist_ID"
   $xmlPos   = strpos($xmlFile, "Syslist_ID");
   $xmlFile1 = substr($xmlFile, 0, $xmlPos);
   $xmlFile2 = substr($xmlFile, $xmlPos);

/* deprecated
 # Clear out everything but letters, numbers, and a few other characters to
 # ensure that XML is clean and does not cause problems with regular expressions
 # found in this script
   $xmlFile2 = preg_replace("/[^0-9A-Za-z\\\_\|\:\=\/\<\>\s\-\.\"\&\#\;]/", "", $xmlFile2);
deprecated */
   $xmlFile  = $xmlFile1.$xmlFile2;

   Function errorReporting($hwID, $updateStatus, $errorNum) {
       $strXML = "<?xml version=\"1.0\"?>\n";
       $strXML .= "  <ErrorReport\n";
       $strXML .= "     HardwareID=\"$hwID\"\n";
       $strXML .= "     UpdateStatus=\"$updateStatus\"\n";
       $strXML .= "     ErrorNumber=\"$errorNum\" />\n";

       echo $strXML;

       # global $xmlFileName, $xmlFile, $adminEmail;
       # mail($adminEmail, $xmlFileName, $strXML);
   }
   
   $strSQL_license  = "Select decode('".addslashes(base64_decode($licenseKey))."','Development*Test.Key')";
   $result_license  = dbquery($strSQL_license);
   $row_license     = mysql_fetch_row($result_license);

   $commaCount = substr_count($row_license[0], ",");
   If ($commaCount !== 3) {
       errorReporting("", "Fail", 9);
       exit();
   } Else {
       $aryLicense = explode(",", $row_license[0]);
   }

   If (!is_numeric($aryLicense[3])) {
       errorReporting("", "Fail", 9);
       exit();
   }

   $strSQL_count = "SELECT count(*) FROM hardware";
   $result_count = dbquery($strSQL_count);
   $row_count    = mysql_fetch_row($result_count);

   If (($aryLicense[3] - $row_count[0]) < 1) {
       $limitReached = 1;
   }
   
   $xml_systems = new System($xmlFile);

                   # This query gets the values in 'software_types' in order to compare it with values in xml attributes.
                   $strSQL_softTypes = "SELECT software_types.Name, software_types.Version, software_types.Maker,
                      software_types.softwareTypeID, software_types.softwareTraitID, software_traits.hidden,
                      software_traits.universalSerial, software_traits.installNotify, software_traits.visName,
                      software_traits.visVersion, software_traits.visMaker, software_traits.universalVendorID
                      FROM software_types, software_traits
                      WHERE software_types.softwareTraitID=software_traits.softwareTraitID AND software_traits.accountID = $accountID";
                   $result_softTypes = dbquery($strSQL_softTypes);
                   $numOfElements_softTypes = mysql_num_rows($result_softTypes);

                   $strSQL_softTraitIDs = "SELECT softwareTraitID FROM software WHERE hardwareID = $hardwareIDFromXML AND accountID = $accountID";
                   $result_softTraitIDs = dbquery($strSQL_softTraitIDs);
                   $numOfElements_softTraitIDs = mysql_num_rows($result_softTraitIDs);
   
   $xml_software = new SoftwareList($xmlFile);
   $xml_hardware = new PeripheralList($xmlFile);
?>
