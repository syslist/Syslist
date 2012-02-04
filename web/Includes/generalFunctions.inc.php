<?
  Function writePeripheralClass($dbValue) {
      global $progText59, $progText61, $progText62, $progText65, $progText68;
      global $progText310, $progText311, $progText312, $progText313, $progText314;
      global $progText437, $progText127;

      switch ($dbValue) {
          case "processor":
              return $progText59;
              break;
          case "opticalStorage":
              return $progText310;
              break;
          case "diskStorage":
              return $progText311;
              break;
          case "netAdapter":
              return $progText68;
              break;
          case "keyboard":
              return $progText312;
              break;
          case "pointingDevice":
              return $progText313;
              break;
          case "printer":
              return $progText314;
              break;
          case "displayAdaptor":
              return $progText61;
              break;
          case "RAM":
              return $progText62;
              break;
          case "soundCard":
              return $progText65;
              break;
          case "monitor":
              return $progText127;
              break;
          default:
              return $progText437; # N/A
              break;
      }
  }

  // arg1: array you want to sort,
  // arg2, 4, 6, etc...: array key you want to sort
  // arg3, 5, 7, etc...: sort flag: SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING
  // arg2 and 3 are repeatable...
  Function arraySuperMultiSort() {
     $args = func_get_args();
     $sortArray = array_shift($args);

     $sortLine = "return(array_multisort(";
     foreach ($args as $arg) {
         $i++;
         if (is_string($arg)) {
             foreach ($sortArray as $row) {
                 $sortArray2[$i][] = $row[$arg];
             }
         } else {
             $sortArray2[$i] = $arg;
         }
         $sortLine .= "\$sortArray2[".$i."],";
     }
     $sortLine .= "\$sortArray));";

     eval($sortLine);
     return $sortArray;
}

  // takes the db value for license type and translates it into plain language, or N/A
  Function writeLicenseType($licenseType) {
      global $progText181, $progText182, $progText437;
      if ($licenseType == "peruser") {
          return $progText181;
      } elseif ($licenseType == "persystem") {
          return $progText182;
      } else {
          return $progText437;
      }
  }

  Function writePrettySystemName($description, $maker = "") {
      $strFullName = $description;
      If ($maker) {
          $strFullName .= " [".$maker."]";
      }
      return $strFullName;
  }


  Function writePrettyPeripheralName($description, $model = "", $maker = "") {
      global $windowWidth;

      $strFullName = $description;
      If ($model) {
          $strFullName .= " [".$model."]";
      }
      If ($maker) {
          $strFullName .= " [".$maker."]";
      }

      $stringLimit = round(($windowWidth / 13.3), 0);
      If (strlen($strFullName) > $stringLimit) {
          $strFullName = substr($strFullName, 0, ($stringLimit-1))." ...";
      }
      return $strFullName;
  }

  Function writePrettySoftwareName($name, $version = "", $maker = "") {
      global $windowWidth;

      $strFullName = $name;
      If ($version) {
          $strFullName .= " [".$version."]";
      }
      If ($maker) {
          $strFullName .= " [".$maker."]";
      }

      $stringLimit = round(($windowWidth / 14.3), 0);
      If (strlen($strFullName) > $stringLimit) {
          $strFullName = substr($strFullName, 0, ($stringLimit-1))." ...";
      }
      return $strFullName;
  }

  // $sqlColumn = database column you want results sorted by, if user clicks link
  // $aryParmas = extra querystring values you want added to the sort links;
  //              array values should include qs key and value (ie "var1=value1")
  Function sortColumnLinks($sqlColumn, $aryParams = "", $linkClass = "titlerow") {
      global $progText534, $progText535;

      If (is_array($aryParams)) {
          foreach ($aryParams as $value) {
              $qsText .= "&".$value;
          }
      }
      echo "<nobr>(<a class='$linkClass' href='";
      echo getPageName()."?asort=".$sqlColumn.$qsText."'>".$progText534."</a> / <a class='$linkClass' href='";
      echo getPageName()."?dsort=".$sqlColumn.$qsText."'>".$progText535."</a>)</nobr>";
  }

  # aryFiles *must* be declared as an array (ie. $aryFiles[0]='test')
  Function sendMail($fromEmail,$fromName,$toEmail,$toName,$subject,$message,$aryFiles=0) {
      $OB="----=_OuterBoundary_000";
      $IB="----=_InnerBoundery_001";

      $headers ="MIME-Version: 1.0\r\n";
      $headers.="From: ".$fromName." <".$fromEmail.">\n";
      $headers.="To: ".str_replace(",", "", $toName)." <".$toEmail.">\n";
      $headers.="Reply-To: ".$fromName." <".$fromEmail.">\n";
      $headers.="X-Priority: 1\n";
      $headers.="X-MSMail-Priority: High\n";
      # $headers.="X-Mailer: My PHP Mailer\n";
      $headers.="Content-Type: multipart/mixed;\n\tboundary=\"".$OB."\"\n";

      //Messages start with text/html alternatives in OB
      $Msg ="This is a multi-part message in MIME format.\n";
      $Msg.="\n--".$OB."\n";
      $Msg.="Content-Type: multipart/alternative;\n\tboundary=\"".$IB."\"\n\n";

      //plaintext section
      $Msg.="\n--".$IB."\n";
      $Msg.="Content-Type: text/plain;\n\tcharset=\"utf-8\"\n";
      $Msg.="Content-Transfer-Encoding: quoted-printable\n\n";
      // plaintext goes here
      $Msg.=$message."\n\n";

      // attachments
      if (is_array($aryFiles)) {
          foreach($aryFiles as $givenFile) {
              $patharray = explode ("/", $givenFile);
              $FileName  = $patharray[count($patharray)-1];
              $Msg .= "\n--".$OB."\n";
              $Msg .= "Content-Type: application/octetstream;\n\tname=\"".$FileName."\"\n";
              $Msg .= "Content-Transfer-Encoding: base64\n";
              $Msg .= "Content-Disposition: attachment;\n\tfilename=\"".$FileName."\"\n\n";

              //file goes here
              $fd = fopen($givenFile, "r");
              $FileContent = fread($fd,filesize($givenFile));
              fclose($fd);
              $FileContent = chunk_split(base64_encode($FileContent));
              $Msg .= $FileContent;
              $Msg .= "\n\n";
          }
      }

      //message ends
      $Msg.="\n--".$OB."--\n";
      mail($toEmail,$subject,$Msg,$headers);
      //syslog(LOG_INFO,"Mail: Message sent to $ToName <$To>");
  }

  Function writeActiveLink($linkURL, $navText, $linkKey, $linkChosen, $HTMLattribute="") {
      If ($HTMLattribute) {
          $HTMLattribute   = "<".$HTMLattribute.">";
          $HTMLattribute2  = "</".$HTMLattribute.">";
      }
      If ($linkKey == $linkChosen) {
          echo $HTMLattribute.$navText.$HTMLattribute2."\n";
      } Else {
          echo $HTMLattribute."<a href='".$linkURL."'>".$navText."</a>".$HTMLattribute2."\n";
      }
  }

  Function notifyUser($alertKey) {
     global $strError, $hardwareID, $spare;
     global $progText72, $progText71, $progText73, $progText536, $progText537, $progText828;
     if ($alertKey) {
         switch ($alertKey) {
             case "insert":
                 $strError = $progText72;
                 break;
             case "update":
                 $strError = $progText71;
                 break;
             case "s_moved":
                 $strError = $progText536;
                 break;
             case "p_moved":
                 $strError = $progText537;
                 break;
             case "delete":
                 $strError = $progText73;
                 break;
             case "merge":
                 $strError = $progText828;
                 break;
             default:
                 if (getOrPost('notify') != "") 
                 	$strError = $notify;
                 break;
         }
     }
  }

  Function cleanFormInput($value) {
     $value = Trim(strip_tags($value));
     return $value;
  }

  Function writeNA($value) {
      global $progText437;
      If ($value) {
          return $value;
      } Else {
          return $progText437;
      }
  }

  Function writeCommentStatus($dbValue) {
      global $progText14, $progText15, $progText16;
      If ($dbValue == "Open") {
          return $progText14;
      } ElseIf ($dbValue == "In Progress") {
          return $progText15;
      } ElseIf ($dbValue == "Resolved") {
          return $progText16;
      } Else {
          return "";
      }
  }

  Function writeStatus($statLetter) {
      global $progText413, $progText413A, $progText414, $progText415;
      If ($statLetter == "w") {
          Return "<font color='green'>".$progText413."</font>";
      } ElseIf ($statLetter == "i") {
          Return "<font color='cc6600'>".$progText415."</font>";
      } ElseIf ($statLetter == "n") {
          Return "<font color='red'>".$progText414."</font>";
      } ElseIf ($statLetter == "d") {
          Return "<font color='5C5C5C'>".$progText413A."</font>";
      }
  }
  
  Function writeStatusNoHTML($statLetter) {
      global $progText413, $progText413A, $progText414, $progText415;
      If ($statLetter == "w") {
          Return $progText413;
      } ElseIf ($statLetter == "i") {
          Return $progText415;
      } ElseIf ($statLetter == "n") {
          Return $progText414;
      } ElseIf ($statLetter == "d") {
          Return $progText413A;
      }
  }

  /* To use paging, first call determinePageNumber, passing it the sql select
     statement you intend to ultimately build off of. After you have done that,
     execute the query as normal.

     At the end of where you display the results of the query, call createPaging,
     which will build the paging nav. Also note: assuming your result set is
     built in part from user input via a form, make sure the form is GET, not POST.

     Before you call determinePageNumber, you may optionally choose to (globally)
     set $rowLimit equal to some number other than 30, which is the default
     record limit per page.
  */

  Function determinePageNumber($strSQL) {
      global $rowLimit, $rowOffset, $pageNumber;
      $rowOffset = getOrPost('rowOffset');

      If (!$rowLimit) {
          $rowLimit = 30;
      }
      if (!$rowOffset) {
          $rowOffset = 0;
      }
      $result = mysql_query($strSQL);
      $numrec = mysql_num_rows($result);
      $pageNumber = intval($numrec/$rowLimit);
      if ($numrec%$rowLimit) $pageNumber++; // add one page if remainder

      $strSQL .= " LIMIT $rowOffset, $rowLimit";
      Return $strSQL;

      # would be nice to return recordset in future...
      # result=mysql_query("select * from tablename $query_where limit $rowOffset,$rowLimit");
  }

  // $qsParamToRemove can be a string OR an array of strings; setting the var will
  // find all values and remove from the querystring.
  Function createPaging($qsParamToRemove="") {
    global $rowLimit, $rowOffset, $pageNumber, $progText538, $progText539;
    $QUERY_STRING = $_SERVER['QUERY_STRING'];
    if (!$rowOffset) {
        $rowOffset = getOrPost('rowOffset');
    }
    If (strpos($QUERY_STRING, "owOffset")) {
        $posQSMinusOffset = strpos($QUERY_STRING, "&")+1;
        $qstring = substr($QUERY_STRING, $posQSMinusOffset);
    } Else {
        $qstring = $QUERY_STRING;
    }

    If (is_array($qsParamToRemove)) {
        Foreach ($qsParamToRemove as $paramToRemove) {
            $pattern = "/".$paramToRemove."[\045|\w|\075]*[\046]?/";
            $qstring = preg_replace($pattern, "", $qstring);
        }
    } ElseIf ($qsParamToRemove) {
        $pattern = "/".$qsParamToRemove."[\045|\w|\075]*[\046]?/";
        $qstring = preg_replace($pattern, "", $qstring);
    }

    if ($pageNumber>1) {
      echo "&nbsp;<br>\n";
      echo "<TABLE cellpadding='0' cellspacing='0' border='0' width='100%'><TR><TD>";
          if ($rowOffset>=$rowLimit) {
              $newoff=$rowOffset-$rowLimit;

              echo "<A HREF=\"" . $_SERVER['PHP_SELF'] . "?rowOffset=$newoff&$qstring\">&lt;&lt;&nbsp; ".$progText538."</A> &nbsp;";
          # } else {
          #     echo "&lt;&lt;&nbsp; ".$progText538." ";
          }

          echo " &nbsp; ";

          for ($i=1; $i<=$pageNumber; $i++) {
              if ((($i-1)*$rowLimit)==$rowOffset) {
                  echo "$i &nbsp;";

              } else {
                  $marker = ($rowOffset / $rowLimit) + 1;
                  if ((($i < 5) OR ($i > ($pageNumber - 5))) OR
                      (($i > $marker - 2) AND ($i < $marker + 2)) ) {
                       $newoff=($i-1)*$rowLimit;
                       echo " <A HREF=\"" . $_SERVER['PHP_SELF'] . "?rowOffset=$newoff&$qstring\">$i</A> &nbsp;";
                       $wroteDots = FALSE;
                  } elseif (($i >= 5) AND !$wroteDots1) {
                       echo " ... &nbsp;";
                       $wroteDots = TRUE;
                       $wroteDots1 = TRUE;
                  } elseif (($i <= ($pageNumber - 5)) AND !$wroteDots2) {
                       If (!$wroteDots) {
                           echo " ... &nbsp;";
                           $wroteDots2 = TRUE;
                       }
                  }
              }
          }
          echo "&nbsp; ";
          if ($rowOffset!=$rowLimit*($pageNumber-1)) {
              $newoff=$rowOffset+$rowLimit;
              echo "<A HREF=\"" . $_SERVER['PHP_SELF']. "?rowOffset=$newoff&$qstring\">".$progText539." &nbsp;&gt;&gt;</A>";
          # }else{
          #    echo $progText539." &nbsp;&gt;&gt;";
          }
          echo "</TD></TR></TABLE>";
    }
  }

  Function makeSessionPaging() {
      if ($_SESSION['pagingScript'] == $_SERVER['PHP_SELF'] && getOrPost('rowOffset') == "") {
          $_GET['rowOffset'] = $_SESSION['pagingRowOffset'];
      } else {
          $_SESSION['pagingScript'] = $_SERVER['PHP_SELF'];
      }
      $_SESSION['pagingRowOffset'] = getOrPost('rowOffset');
  }
  
  # use this to set the class tag of alternating rows in tables (in conjunction with stylesheet)
  Function alternateRowColor() {
      global $rowStyle;
      $rowStyle ++;
      If ($rowStyle%2 == 1) {
           Return "row1";
      } Else {
           Return "row2";
      }
  }

  Function formatForBrowser($strIE, $strElse) {
      If (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
           echo $strIE;
      } Else {
           echo $strElse;
      }
  }

  Function makeAbsPath($pageName) {
      global $SCRIPT_FILENAME;
      $intPos = strpos($SCRIPT_FILENAME, $pageName);
      $strAbsPath = substr($SCRIPT_FILENAME, 0, $intPos);
      Return $strAbsPath;
  }

  Function buildName($strFirstName, $strMiddleName, $strLastName, $intShowType="") {
      If ($strMiddleName) {
           If ($intShowType == 1) {
                $strFullName = $strFirstName." ".$strMiddleName." ".$strLastName;
           } Else {
                $strFullName = $strLastName.", ".$strFirstName." ".$strMiddleName;
           }
      } Else {
           If ($intShowType == 1) {
                $strFullName = $strFirstName." ".$strLastName;
           } Else {
                $strFullName = $strLastName.", ".$strFirstName;
           }
      }
      Return $strFullName;
  }

  Function urlSafe($strQueryString) {
      $strQueryString = urlencode($strQueryString);
      $strQueryString = str_replace("%26", "&", $strQueryString);
      $strQueryString = str_replace("%3D", "=", $strQueryString);
      Return $strQueryString;
  }

  Function redirect($strURL, $strQueryString = "") {
      If ($strQueryString != "") {
         $strQueryString = urlSafe($strQueryString);
         $strQMark = "?";
      }
      $strLocation = $strURL.$strQMark.$strQueryString;
      header ("Location: $strLocation");
      header ("QUERY_STRING: $strQueryString");
      exit;
  }

  Function writeSelected($SelectValue,$OurValue) {
      If ($SelectValue==$OurValue) {
          Return "selected";
      }
  }

  Function writeChecked($SelectValue,$OurValue) {
      If ($SelectValue==$OurValue) {
          Return "checked";
      }
  }
  
  Function checkboxToBinary($checkboxValue) {
      if (($checkboxValue == "on") OR ($checkboxValue == "1")) {
          return 1;
      } else {
          return 0;
      }
  }
  
  Function makeNull($val, $includeSingleQuotes = "") {
      If ($val == "" OR $val == "00/00/0000") {
           return "NULL";
      } Else {
           If ($includeSingleQuotes) {
               return "'".$val."'";
           } Else {
               return $val;
           }
      }
  }

  Function antiSlash($strValue) {
      If ($strValue != "") {
          $strValue = stripslashes($strValue);
          $strValue = str_replace("\"", "&quot;", $strValue); # fixes broken html input field problem
      }
      Return $strValue;
  }

  Function fillError($strValue) {
      global $strError;
      If ($strError == "") {
           $strError = $strValue;
      }
  }

  Function validateText($strFieldName, $strValidate, $intMin, $intMax, $bolRequired, $bolHTML = FALSE, $bolLanguageFilter = TRUE) {
      global $strError, $languageFilterOK; # languageFilter var to be set in admin config file.
      global $progTextBlock28, $progText540, $progText541, $progText542, $progText543;
      If ($bolHTML == FALSE) {
          $strValidate = Trim(strip_tags($strValidate));
      } Else {
          $strValidate = Trim($strValidate);
      }
      If (($bolLanguageFilter == TRUE) AND $languageFilterOK) {
          $strPattern = $progTextBlock28;
          $strValidate = preg_replace($strPattern, "@%&!*#", $strValidate);
      }
      If ($bolRequired == TRUE OR $strValidate != "") {
          If ($strValidate=="") {
              fillError($strFieldName." ".$progText540); # is required
              Return $strValidate;
          } Else {
              $intField = strlen($strValidate);
              If (($intField >= $intMin) AND ($intField <= $intMax)) {
                  Return $strValidate;
              } Else {
                  If ($intMin==$intMax) {
                       fillError($strFieldName." ".$progText541." ".$intMax." ".$progText542);
                       Return $strValidate;
                  } Else {
                       fillError($strFieldName." ".$progText543." ".$intMin." and ".$intMax." ".$progText542);
                       Return $strValidate;
                  }
              }
          }
      } Else {
          Return $strValidate;
      }
  }

  Function validateChoice($strFieldName, $strValidate) {
      global $strError, $progText540;
      $strValidate = strip_tags($strValidate);
      If ($strValidate == "") {
           fillError($strFieldName." ".$progText540); # is required
      } Else {
           Return $strValidate;
      }
  }

  Function validateEmail($strFieldName, $strValidate, $bolRequired) {
      global $strError, $progText540, $progText544, $progText545;
      $strValidate = trim(strtolower(strip_tags($strValidate)));

      If ($bolRequired == TRUE OR $strValidate != "") {
          If ($strValidate=="") {
              fillError($strFieldName." ".$progText540);
              Return $strValidate;
          } Else {
              $Pos = strpos($strValidate, "@", 1);
              If ($Pos===FALSE) {
                  fillError($strFieldName." ".$progText544);
                  Return $strValidate;
              } Else {
                  $Pos2 = strpos($strValidate, ".", ($Pos+2));
                  If ($Pos2===FALSE) {
                      fillError($strFieldName." ".$progText544);
                      Return $strValidate;

                   } Else {
                       $intField = strlen($strValidate);
                       If ($intField>60) {
                            fillError($strFieldName." ".$progText545);
                            Return $strValidate;
                        } Else {
                            Return $strValidate;
                        }
                   }
              }
          }
      } Else {
          Return $strValidate;
      }
  }

  Function validateNumber($strFieldName, $strValidate, $intMin, $intMax, $bolRequired) {
      global $strError, $progText540, $progText541, $progText543, $progText546, $progText547;
      $strValidate = Trim(strip_tags($strValidate));
      $strValidate = str_replace(" ", "", $strValidate);
      $strValidate = str_replace(")", "", $strValidate);
      $strValidate = str_replace("(", "", $strValidate);
      $strValidate = str_replace("-", "", $strValidate);

      If ($bolRequired == TRUE OR $strValidate != "") {
          If ($strValidate=="") {
              fillError($strFieldName." ".$progText540);
              Return $strValidate;
          } Else {
              $intField = strlen($strValidate);
              If (($intField >= $intMin) AND ($intField <= $intMax)) {
                  If (is_numeric($strValidate)===TRUE) {
                       Return $strValidate;
                  } Else {
                       fillError($strFieldName." ".$progText546);
                       Return $strValidate;
                  }
              } Else {
                  If ($intMin==$intMax) {
                       fillError($strFieldName." ".$progText541." ".$intMax." ".$progText547);
                       Return $strValidate;
                  } Else {
                       fillError($strFieldName." ".$progText543." ".$intMin." and ".$intMax." ".$progText547);
                       Return $strValidate;
                  }
              }
          }
      } Else {
          Return $strValidate;
      }
  }

  Function validateExactNumber($strFieldName, $strValidate, $intMin, $intMax, $bolRequired, $intDecimals="") {
      global $strError, $progText540, $progText543, $progText546, $progText548, $progText549, $progText550;
      $strValidate = Trim(strip_tags($strValidate));
      If ($bolRequired == TRUE OR $strValidate != "") {
          If ($strValidate == "") {
              fillError($strFieldName." ".$progText540);
          } ElseIf (is_numeric($strValidate) === FALSE) {
              fillError($strFieldName." ".$progText546);
          } ElseIf (($strValidate < $intMin) OR ($strValidate > $intMax)) {
              fillError($strFieldName." ".$progText543." ".$intMin." and ".$intMax.".");
            # } ElseIf (strstr($strValidate, ".")) {
            #     If strstr(strstr($strValidate, "."), ".")
            #     fillError("too many decimals...");
          } ElseIf ($intDecimals !== "") {
              $decimalPlaces = strlen(strstr($strValidate, "."));
              If ($decimalPlaces > ($intDecimals+1)) {
                  If ($intDecimals == 0) {
                      fillError($strFieldName." ".$progText548." ".$intMin." and ".$intMax.".");
                  } Else {
                      fillError($strFieldName." ".$progText549." ".$intDecimals." ".$progText550);
                  }
              } ElseIf ($intDecimals == 0) {
                  $strValidate = round($strValidate);
              }
          }
      }
      Return $strValidate;
  }

  Function validateIP($fieldSuffix, $bolRequired, $formType="POST", $requireAllParts=TRUE) {
      global $strError, $progText551, $progText552;

          $ip1  = "txtIP1".$fieldSuffix;
          $ip2  = "txtIP2".$fieldSuffix;
          $ip3  = "txtIP3".$fieldSuffix;
          $ip4  = "txtIP4".$fieldSuffix;

          If ($formType == "GET") {
              $ip1  = Trim(strip_tags($_GET[$ip1]));
              $ip2  = Trim(strip_tags($_GET[$ip2]));
              $ip3  = Trim(strip_tags($_GET[$ip3]));
              $ip4  = Trim(strip_tags($_GET[$ip4]));
          } Else {
              $ip1  = Trim(strip_tags($_POST[$ip1]));
              $ip2  = Trim(strip_tags($_POST[$ip2]));
              $ip3  = Trim(strip_tags($_POST[$ip3]));
              $ip4  = Trim(strip_tags($_POST[$ip4]));
          }

          $ip1 = str_replace(".", "", $ip1);
          $ip2 = str_replace(".", "", $ip2);
          $ip3 = str_replace(".", "", $ip3);
          $ip4 = str_replace(".", "", $ip4);

          If ($bolRequired OR (($ip1 OR $ip2 OR $ip3 OR $ip4) AND $requireAllParts)) {
              $ipRequired = TRUE;
          }

          If ($ipRequired AND (($ip1=="") OR ($ip2=="") OR ($ip3=="") OR ($ip4==""))) {
              fillError($progText551);
          }

          $strIP1  = validateExactNumber($progText552, $ip1, 0, 255, $ipRequired, 0);
          $strIP2  = validateExactNumber($progText552, $ip2, 0, 255, $ipRequired, 0);
          $strIP3  = validateExactNumber($progText552, $ip3, 0, 255, $ipRequired, 0);
          $strIP4  = validateExactNumber($progText552, $ip4, 0, 255, $ipRequired, 0);

          If ($strIP1 OR $strIP2 OR $strIP3 OR $strIP4) {
              return $strIP1.".".$strIP2.".".$strIP3.".".$strIP4;
          } Else {
              return "";
          }
  }

  Function buildIP($value, $fieldSuffix) {
     global $progText553;
         If ($value) {
             $dot1 = strpos($value, ".", 0);
             $dot2 = strpos($value, ".", ($dot1+1));
             $dot3 = strpos($value, ".", ($dot2+1));
         }

         $strIP1 = substr($value, 0, $dot1);
         $strIP2 = substr($value, ($dot1+1), (($dot2-$dot1)-1));
         $strIP3 = substr($value, ($dot2+1), (($dot3-$dot2)-1));
         $strIP4 = substr($value, ($dot3+1));
?>
         <input type='text' name='txtIP1<?=$fieldSuffix; ?>' value='<?=$strIP1; ?>' size='3' maxlength='3'> <b>.</b>
         <input type='text' name='txtIP2<?=$fieldSuffix; ?>' value='<?=$strIP2; ?>' size='3' maxlength='3'> <b>.</b>
         <input type='text' name='txtIP3<?=$fieldSuffix; ?>' value='<?=$strIP3; ?>' size='3' maxlength='3'> <b>.</b>
         <input type='text' name='txtIP4<?=$fieldSuffix; ?>' value='<?=$strIP4; ?>' size='3' maxlength='3'>
<?
  }

  Function phoneLengthFromCountry($countryCode) {
      If ($countryCode == "840") {
          Return 10;
      } Else {
          Return "";
      }
  }

  Function buildPhone($varNameSuffix, $aryPhoneVals, $showIntlCode=FALSE, $showExtension=FALSE) {
      global $progText554;
      If (is_array($aryPhoneVals)) {
          $phone      = $aryPhoneVals[0];
          $intlCode   = $aryPhoneVals[1];
          $extension  = $aryPhoneVals[2];
      }

      echo "<input size='12' maxlength='14' type='text' name='txtPhone".$varNameSuffix."' value='".antiSlash($phone)."'>\n";
      If ($showExtension) {
          echo ",&nbsp; ext: <input size='3' maxlength='6' type='text' name='txtExtension".$varNameSuffix."' value='".antiSlash($extension)."'>\n";
      }
      If ($showIntlCode) {
          echo "<br><input size='3' maxlength='4' type='text' name='txtIntlCode".$varNameSuffix."' value='".antiSlash($intlCode)."'>\n";
          echo " <i>".$progText554."</i>";
      }
  }

  # Function reconstructPhoneAry($strPhoneNumber, $strPhoneCode, $strPhoneExt) {

  Function validatePhone($strFieldName, $varNameSuffix, $bolRequired, $numDigits="") {
      global $strError;
      global $progText555, $progText556, $progText557, $progText558, $progText546, $progText559, $progText540;

      $phone = "txtPhone".$varNameSuffix;
      $phone = Trim(strip_tags($_POST[$phone]));
      $phone = str_replace("-", "", $phone);
      $phone = str_replace("(", "", $phone);
      $phone = str_replace(")", "", $phone);
      $phone = str_replace(" ", "", $phone);
 	  $extension = "txtExtension".$varNameSuffix;
      $extension = Trim(strip_tags($_POST[$extension]));
      $intlCode = "txtIntlCode".$varNameSuffix;
      $intlCode = Trim(strip_tags($_POST[$intlCode]));

      If (!$bolRequired) {
          $extraErrorText = ", ".$progText555;
      } Else {
          $extraErrorText = ".";
      }
      If (!$numDigits) {
          $numDigits = 6;
      }

      If ($phone != "") {
         If (is_numeric($phone)
            AND (is_numeric($intlCode) OR ($intlCode==""))
            AND (is_numeric($extension) OR ($extension==""))) {
             $phoneLen = strlen($phone);
             If ($phoneLen < $numDigits) {
                 fillError($strFieldName." ".$progText556);
                 Return array ($phone, $intlCode, $extension);
             } ElseIf ($phoneLen > 10) {
                 fillError($strFieldName." ".$progText557);
                 Return array ($phone, $intlCode, $extension);
             } Else {
                 Return array ($phone, $intlCode, $extension);
             }
         } Else {
            fillError($strFieldName." ".$progText546);
            Return array ($phone, $intlCode, $extension);
         }
     } ElseIf (($intlCode != "") OR ($extension != "")) {
         fillError($progText558." ".$strFieldName." ".$progText559.$extraErrorText);
         Return array ($phone, $intlCode, $extension);
     } ElseIf ($bolRequired) {
         fillError($strFieldName." ".$progText540);
         Return array ($phone, $intlCode, $extension);
     } Else {
         Return array ("", "", "");
     }
  }

  # For use in general validation, and when comparing a "user-formatted date" (mm/dd/yyyy)
  # with a date in the db.
  ## Note - you don't need this to insert a date into the db - for that, use: date("Ymd", $dateVal);
  Function validateDate($fieldName, $dateVal, $intMin, $intMax, $bolRequired) {
      global $strError, $progText560, $progText561, $progText562, $progText563, $progText564, $progText540, $europeanDates;

      If (($dateVal == "mm/dd/yyyy") || ($dateVal == "dd/mm/yyyy")) {
            $dateVal = "";
      }

      If ($dateVal != "") {
            $dateVal = str_replace(".", "/", $dateVal);
            $dateVal = str_replace("-", "/", $dateVal);
            $tempDate = $dateVal;
            $tempDate = str_replace("/", "", $tempDate);

            If (is_numeric($tempDate)) {
                  if (!$europeanDates )
                  {
                      $intLoc = strpos($dateVal, "/");
                      If ($intLoc == 2) {
                          $strMonth = substr($dateVal, 0, 2);
                      } ElseIf ($intLoc == 1) {
                          $strMonth = "0".substr($dateVal, 0, 1);
                      } Else {
                          fillError($fieldName." ".$progText560);
                          Return "$dateVal";
                      }
    
                      $intLoc2 = strpos($dateVal, "/", ($intLoc + 1));
                      If ($intLoc2 == 4 AND $intLoc == 1) {
                          $strDay = substr($dateVal, 2, 2);
                      } ElseIf ($intLoc2 == 5) {
                          $strDay = substr($dateVal, 3, 2);
                      } ElseIf ($intLoc2 == 3) {
                          $strDay = "0".substr($dateVal, 2, 1);
                      } Else {
                          fillError($fieldName." ".$progText560);
                          Return "$dateVal";
                      }
                  }
                  else
                  {                      
                      $intLoc = strpos($dateVal, "/");
                      If ($intLoc == 2) {
                          $strDay = substr($dateVal, 0, 2);
                      } ElseIf ($intLoc == 1) {
                          $strDay = "0".substr($dateVal, 0, 1);
                      } Else {
                          fillError($fieldName." ".$progText560);
                          Return "$dateVal";
                      }
    
                      $intLoc2 = strpos($dateVal, "/", ($intLoc + 1));
                      If ($intLoc2 == 4 AND $intLoc == 1) {
                          $strMonth = substr($dateVal, 2, 2);
                      } ElseIf ($intLoc2 == 5) {
                          $strMonth = substr($dateVal, 3, 2);
                      } ElseIf ($intLoc2 == 3) {
                          $strMonth = "0".substr($dateVal, 2, 1);
                      } Else {
                          fillError($fieldName." ".$progText560);
                          Return "$dateVal";
                      }      
                  }
                  $strYear = substr($dateVal, ($intLoc2+1), 4);
                  If (strlen($strYear) != 4) {
                       fillError($fieldName." ".$progText561);
                       Return "$dateVal";
                  } ElseIf (($strYear > $intMax) OR ($strYear < $intMin)) {
                       fillError($fieldName." ".$progText562." ".$intMin." ".$progText563." ".$intMax.".");
                       Return "$dateVal";
                  }
                  
                  If (!checkdate($strMonth, $strDay, $strYear)) {
                       fillError($fieldName." ".$progText560);
                       Return "$dateVal";
                  }

                  Return $dateVal;
            } Else {
                  fillError($fieldName." ".$progText564);
                  Return "$dateVal";
            }
      } ElseIf ($bolRequired == TRUE) {
            fillError($fieldName." ".$progText540);
            Return "";
      }
  }

  # For use when retrieving a date from the db to display
  Function displayDate($dateVal) {
      global $europeanDates;
      If ($dateVal) {
            $dateVal  = str_replace("-", "", $dateVal);
            $strDay   = substr($dateVal, 6, 2);
            $strMonth = substr($dateVal, 4, 2);
            $strYear  = substr($dateVal, 0, 4);
            if (!$europeanDates)
                $dateVal  = "$strMonth/$strDay/$strYear";
            else
                $dateVal  = "$strDay/$strMonth/$strYear";
      }
      Return $dateVal;
  }

  # For use when retrieving a date from the db to display
  Function displayDateTime($dateVal) {
  	  global $europeanDates;
      If ($dateVal) {
            $dateVal   = str_replace("-", "", $dateVal);
            $strDay    = substr($dateVal, 6, 2);
            $strMonth  = substr($dateVal, 4, 2);
            $strYear   = substr($dateVal, 0, 4);

            $strHour   = substr($dateVal, 9, 2);
            $strMinute = substr($dateVal, 12, 2);
            if (!$europeanDates)
                $dateVal   = "$strMonth/$strDay/$strYear, $strHour:$strMinute";
            else
                $dateVal   = "$strDay/$strMonth/$strYear, $strHour:$strMinute";
      }
      Return $dateVal;
  }

  # Breaks a date from the db into d, m, and y components (array)
  Function breakDate($dateVal) {
      If ($dateVal) {
          $dateVal  = str_replace("-", "", $dateVal);
          $arr = array("d" => substr($dateVal, 6, 2), "m" => substr($dateVal, 4, 2), "y" => substr($dateVal, 0, 4));
      }
      Return $arr;
  }

  # Generates db-friendly date in the past or future.
  # If $startDate is provided (db format - Ymd), the change takes place from that date.
  Function generateDate($dayChange, $monthChange, $yearChange, $startDate="") {
      If ($startDate) {
          $arr = breakDate($startDate);
          $day = $arr["d"];
          $month = $arr["m"];
          $year = $arr["y"];
      } Else {
          $day = date("d");
          $month = date("m");
          $year = date("Y");
      }
      return date("Ymd", mktime(0,0,0,$month+$monthChange,$day+$dayChange,$year+$yearChange));
  }

  # For inserting or updating 'mm/dd/yyyy' dates in db, or comparing with dates in the db.
  # Returns a string, "NULL", if $dateVal is empty.
  Function dbDate($dateVal) {
      global $europeanDates;
      $dateVal = makeNull($dateVal, FALSE);
      If ($dateVal != "" AND $dateVal != "NULL") {
      	  if (!$europeanDates)
      	  {
	          $intLoc = strpos($dateVal, "/");
	          If ($intLoc == 2) {
	              $strMonth = substr($dateVal, 0, 2);
	          } Else {
	              $strMonth = "0".substr($dateVal, 0, 1);
	          }
	
	          $intLoc2 = strpos($dateVal, "/", ($intLoc + 1));
	          If ($intLoc2 == 4 AND $intLoc == 1) {
	              $strDay = substr($dateVal, 2, 2);
	          } ElseIf ($intLoc2 == 5) {
	              $strDay = substr($dateVal, 3, 2);
	          } Else {
	              $strDay = "0".substr($dateVal, 2, 1);
	          }
		  }
		  else
		  {
		  	  $intLoc = strpos($dateVal, "/");
	          If ($intLoc == 2) {
	              $strDay = substr($dateVal, 0, 2);
	          } Else {
	              $strDay = "0".substr($dateVal, 0, 1);
	          }
	
	          $intLoc2 = strpos($dateVal, "/", ($intLoc + 1));
	          If ($intLoc2 == 4 AND $intLoc == 1) {
	              $strMonth = substr($dateVal, 2, 2);
	          } ElseIf ($intLoc2 == 5) {
	              $strMonth = substr($dateVal, 3, 2);
	          } Else {
	              $strMonth = "0".substr($dateVal, 2, 1);
	          }
	      }
          $strYear = substr($dateVal, ($intLoc2+1), 4);
          $dateVal = "'".$strYear.$strMonth.$strDay."'";
      }
      Return $dateVal;
  }

  Function buildDate($fieldName, $dateVal) {
  	  global $europeanDates;
  	  
      $dateVal  = str_replace("-", "", $dateVal);

      If ($dateVal == "" OR
          $dateVal == "0000-00-00" OR
          $dateVal == "NULL" OR
          $dateVal == "mm/dd/yyyy" OR
          $dateVal == "dd/mm/yyyy") {
          if (!$europeanDates)
          	echo "<input type='text' name='$fieldName' size='10' value='mm/dd/yyyy' onClick=\"this.value=''\">";
		  else
		    echo "<input type='text' name='$fieldName' size='10' value='dd/mm/yyyy' onClick=\"this.value=''\">";
      } ElseIf (is_numeric($dateVal)) {
          echo "<input type='text' name='$fieldName' size='10' value='".displayDate($dateVal)."'>";

      } Else {
          echo "<input type='text' name='$fieldName' size='10' value='".$dateVal."'>";
      }
  }

  Function dateDiff($interval, $datefrom, $dateto, $using_timestamps = false) {
    /*
      $interval can be:
      yyyy - Number of full years
      q - Number of full quarters
      m - Number of full months
      y - Difference between day numbers
        (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The dateDiff is "-32".)
      d - Number of full days
      w - Number of full weekdays
      ww - Number of full weeks
      h - Number of full hours
      n - Number of full minutes
      s - Number of full seconds (default)
    */

    if (!$using_timestamps) {
        $datefrom = strtotime($datefrom, 0);
        $dateto = strtotime($dateto, 0);
    }
    $difference = $dateto - $datefrom; // Difference in seconds

    switch($interval) {

        case 'yyyy': // Number of full years

            $years_difference = floor($difference / 31536000);
            if (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom), date("j", $datefrom), date("Y", $datefrom)+$years_difference) > $dateto) {
                $years_difference--;
            }
            if (mktime(date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto), date("Y", $dateto)-($years_difference+1)) > $datefrom) {
                $years_difference++;
            }
            $dateDiff = $years_difference;
            break;

        case "q": // Number of full quarters

            $quarters_difference = floor($difference / 8035200);
            while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($quarters_difference*3), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                $months_difference++;
            }
            $quarters_difference--;
            $dateDiff = $quarters_difference;
            break;

        case "m": // Number of full months

            $months_difference = floor($difference / 2678400);
            while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                $months_difference++;
            }
            $months_difference--;
            $dateDiff = $months_difference;
            break;

        case 'y': // Difference between day numbers

            $dateDiff = date("z", $dateto) - date("z", $datefrom);
            break;

        case "d": // Number of full days

            $dateDiff = floor($difference / 86400);
            break;

        case "w": // Number of full weekdays

            $days_difference = floor($difference / 86400);
            $weeks_difference = floor($days_difference / 7); // Complete weeks
            $first_day = date("w", $datefrom);
            $days_remainder = floor($days_difference % 7);
            $odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
            if ($odd_days > 7) { // Sunday
                $days_remainder--;
            }
            if ($odd_days > 6) { // Saturday
                $days_remainder--;
            }
            $dateDiff = ($weeks_difference * 5) + $days_remainder;
            break;

        case "ww": // Number of full weeks

            $dateDiff = floor($difference / 604800);
            break;

        case "h": // Number of full hours

            $dateDiff = floor($difference / 3600);
            break;

        case "n": // Number of full minutes

            $dateDiff = floor($difference / 60);
            break;

        default: // Number of full seconds (default)

            $dateDiff = $difference;
            break;
    }

    return $dateDiff;
  }

  Function declareError($bolBold) {
      global $strError;
      if ($strError == "") {
          $strError = getOrPost('strError');
      }
      If ($strError != "") {
          If ($bolBold == TRUE) {
              echo "<b><font color='red'>$strError</font></b><p>";
          } Else {
              echo "<font color='red'>$strError</font><p>";
          }
      }
  }

  Function declareErrorBack($bolBold) {
      global $strError, $progTextBlock29;
      If ($strError != "") {
          If ($bolBold == TRUE) {
              echo "<b><font color='red'>".$strError." ".$progTextBlock29."</font></b><p>";
          } Else {
              echo "<font color='red'>".$strError." ".$progTextBlock29."</font><p>";
          }
      }
  }

   // Basic authentication: from the manual, Chapter 17
   Function authenticateUser($strUsername, $strPassword) {
        global $PHP_AUTH_USER, $PHP_AUTH_PW, $progText565;
        If (($PHP_AUTH_USER != $strUsername ) OR ($PHP_AUTH_PW   != $strPassword)) {
            Header("WWW-Authenticate: Basic realm=\"Authenticate\"");
            Header("HTTP/1.0 401 Unauthorized");
            echo $progText565;
            exit;
        }
   }

   // generates random num between min and max
   Function randomNumGen($intMin, $intMax) {
       srand ((double) microtime() * 1000000);
           $intRandom = rand($intMin, $intMax);
           Return $intRandom;
   }

   // picks random character from provided string, or entire alphanumeric set
   // if strChars is null
   Function randomCharGen($strChars) {
       srand ((double) microtime() * 1000000);
       If ($strChars) {
           $strBaseString = $strChars;
           $intMin = 0;
           $intMax = strlen($strChars);
       } Else {
           $strBaseString = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
           $intMin = 0;
           $intMax = 33;
       }

       $intRandom = rand($intMin, $intMax);
       Return substr($strBaseString, $intRandom, 1);
   }

   // hide an integer. This is security by obscurity; use mcrypt library if you have it!!!
   Function numHide($intValue) {
       Return md5(base64_encode($intValue));
   }

   // unhide an integer. $intUpperLimit is the maximum that integer could be.
   Function numShow($strValue, $intUpperLimit) {
       for ($i = 0; $i <= $intUpperLimit; $i++) {
           If ($strValue == md5(base64_encode($i))) {
                Return $i;
                break(2);
           }
       }
   }

   Function HTMLuntreat($strValue) {
       # need code for converting ampersands, but only if they are not in an anchor tag.
       $strValue = strip_tags($strValue, "<a><b><i><u><img>"); # 2nd param - allowable tags
       $strValue = str_replace("\n", "<br>", $strValue);
       $strValue = str_replace("  ", " &nbsp;", $strValue);
       Return $strValue;
   }

   Function HTMLtreat($strValue) {
        $strValue = str_replace("<br>", "\n", $strValue);
        $strValue = str_replace(" &nbsp;", "  ", $strValue);
        Return $strValue;
   }

   Function buildStates($strSelectState, $strComboName) {
       global $progText566;
       $strSelectState = Trim(strip_tags($strSelectState));

       echo "<select size='1' name='cbo$strComboName'>";

       echo "<option value=''>&nbsp;</option>\r\n";
       echo "<option value='XX'";
       IF ($strSelectState=="XX") {
           echo " selected  ";
       }
       echo ">-- ".$progText566." --</option>\r\n";
       echo "<option value=''>&nbsp;</option>\r\n";

       echo "<option value='AL'";
       IF ($strSelectState=="AL") {
           echo " selected  ";
       }
       echo ">ALABAMA</option>\r\n";
       echo "<option value='AK'";
       IF ($strSelectState=="AK") {
           echo " selected ";
       }
       echo ">ALASKA</option>\r\n";
       echo "<option value='AZ'";
       IF ($strSelectState=="AZ") {
           echo " selected  ";
       }
       echo ">ARIZONA</option>\r\n";
       echo "<option value='AR'";
       IF ($strSelectState=="AR") {
           echo " selected ";
       }
       echo ">ARKANSAS</option>\r\n";
       echo "<option value='CA'";
       IF ($strSelectState=="CA") {
           echo " selected ";
       }
       echo ">CALIFORNIA</option>\r\n";
       echo "<option value='CO'";
       IF ($strSelectState=="CO") {
           echo " selected ";
       }
       echo ">COLORADO</option>\r\n";
       echo "<option value='CT'";
       IF ($strSelectState=="CT") {
           echo " selected ";
       }
       echo ">CONNECTICUT</option>\r\n";
       echo "<option value='DE'";
       IF ($strSelectState=="DE") {
           echo " selected ";
       }
       echo ">DELAWARE</option>\r\n";
       echo "<option value='DC'";
       IF ($strSelectState=="DC") {
           echo " selected ";
       }
       echo ">DISTRICT OF COLUMBIA</option>\r\n";
       echo "<option value='FL'";
       IF ($strSelectState=="FL") {
           echo " selected ";
       }
       echo ">FLORIDA</option>\r\n";
       echo "<option value='GA'";
       IF ($strSelectState=="GA") {
           echo " selected ";
       }
       echo ">GEORGIA</option>\r\n";
       echo "<option value='HI'";
       IF ($strSelectState=="HI") {
           echo " selected ";
       }
       echo ">HAWAII</option>\r\n";
       echo "<option value='ID'";
       IF ($strSelectState=="ID") {
           echo " selected ";
       }
       echo ">IDAHO</option>\r\n";
       echo "<option value='IL'";
       IF ($strSelectState=="IL") {
           echo " selected ";
       }
       echo ">ILLINOIS</option>\r\n";
       echo "<option value='IN'";
       IF ($strSelectState=="IN") {
           echo " selected ";
       }
       echo ">INDIANA</option>\r\n";
       echo "<option value='IA'";
       IF ($strSelectState=="IA") {
           echo " selected ";
       }
       echo ">IOWA</option>\r\n";
       echo "<option value='KS'";
       IF ($strSelectState=="KS") {
           echo " selected ";
       }
       echo ">KANSAS</option>\r\n";
       echo "<option value='KY'";
       IF ($strSelectState=="KY") {
           echo " selected ";
       }
       echo ">KENTUCKY</option>\r\n";
       echo "<option value='LA'";
       IF ($strSelectState=="LA") {
           echo " selected ";
       }
       echo ">LOUISIANA</option>\r\n";
       echo "<option value='ME'";
       IF ($strSelectState=="ME") {
           echo " selected ";
       }
       echo ">MAINE</option>\r\n";
       echo "<option value='MD'";
       IF ($strSelectState=="MD") {
           echo " selected ";
       }
       echo ">MARYLAND</option>\r\n";
       echo "<option value='MA'";
       IF ($strSelectState=="MA") {
           echo " selected ";
       }
       echo ">MASSACHUSETTS</option>\r\n";
       echo "<option value='MI'";
       IF ($strSelectState=="MI") {
           echo " selected ";
       }
       echo ">MICHIGAN</option>\r\n";
       echo "<option value='MN'";
       IF ($strSelectState=="MN") {
           echo " selected ";
       }
       echo ">MINNESOTA</option>\r\n";
       echo "<option value='MS'";
       IF ($strSelectState=="MS") {
           echo " selected ";
       }
       echo ">MISSISSIPPI</option>\r\n";
       echo "<option value='MO'";
       IF ($strSelectState=="MO") {
           echo " selected ";
       }
       echo ">MISSOURI</option>\r\n";
       echo "<option value='MT'";
       IF ($strSelectState=="MT") {
           echo " selected ";
       }
       echo ">MONTANA</option>\r\n";
       echo "<option value='NE'";
       IF ($strSelectState=="NE") {
           echo " selected ";
       }
       echo ">NEBRASKA</option>\r\n";
       echo "<option value='NV'";
       IF ($strSelectState=="NV") {
           echo " selected ";
       }
       echo ">NEVADA</option>\r\n";
       echo "<option value='NH'";
       IF ($strSelectState=="NH") {
           echo " selected ";
       }
       echo ">NEW HAMPSHIRE</option>\r\n";
       echo "<option value='NJ'";
       IF ($strSelectState=="NJ") {
           echo " selected ";
       }
       echo ">NEW JERSEY</option>\r\n";
       echo "<option value='NM'";
       IF ($strSelectState=="NM") {
           echo " selected ";
       }
       echo ">NEW MEXICO</option>\r\n";
       echo "<option value='NY'";
       IF ($strSelectState=="NY") {
           echo " selected ";
       }
       echo ">NEW YORK</option>\r\n";
       echo "<option value='NC'";
       IF ($strSelectState=="NC") {
           echo " selected ";
       }
       echo ">NORTH CAROLINA</option>\r\n";
       echo "<option value='ND'";
       IF ($strSelectState=="ND") {
           echo " selected ";
       }
       echo ">NORTH DAKOTA</option>\r\n";
       echo "<option value='OH'";
       IF ($strSelectState=="OH") {
           echo " selected ";
       }
       echo ">OHIO</option>\r\n";
       echo "<option value='OK'";
       IF ($strSelectState=="OK") {
           echo " selected ";
       }
       echo ">OKLAHOMA</option>\r\n";
       echo "<option value='OR'";
       IF ($strSelectState=="OR") {
           echo " selected ";
       }
       echo ">OREGON</option>\r\n";
       echo "<option value='PA'";
       IF ($strSelectState=="PA") {
           echo " selected ";
       }
       echo ">PENNSYLVANIA</option>\r\n";
       echo "<option value='PR'";
       IF ($strSelectState=="PR") {
           echo " selected ";
       }
       echo ">PUERTO RICO</option>\r\n";
       echo "<option value='RI'";
       IF ($strSelectState=="RI") {
           echo " selected ";
       }
       echo ">RHODE ISLAND</option>\r\n";
       echo "<option value='SC'";
       IF ($strSelectState=="SC") {
           echo " selected ";
       }
       echo ">SOUTH CAROLINA</option>\r\n";
       echo "<option value='SD'";
       IF ($strSelectState=="SD") {
           echo " selected ";
       }
       echo ">SOUTH DAKOTA</option>\r\n";
       echo "<option value='TN'";
       IF ($strSelectState=="TN") {
           echo " selected ";
       }
       echo ">TENNESSEE</option>\r\n";
       echo "<option value='TX'";
       IF ($strSelectState=="TX") {
           echo " selected ";
       }
       echo ">TEXAS</option>\r\n";
       echo "<option value='UT'";
       IF ($strSelectState=="UT") {
           echo " selected ";
       }
       echo ">UTAH</option>\r\n";
       echo "<option value='VT'";
       IF ($strSelectState=="VT") {
           echo " selected ";
       }
       echo ">VERMONT</option>\r\n";
       echo "<option value='VA'";
       IF ($strSelectState=="VA") {
           echo " selected ";
       }
       echo ">VIRGINIA</option>\r\n";
       echo "<option value='WA'";
       IF ($strSelectState=="WA") {
           echo " selected ";
       }
       echo ">WASHINGTON</option>\r\n";
       echo "<option value='WV'";
       IF ($strSelectState=="WV") {
           echo " selected ";
       }
       echo ">WEST VIRGINIA</option>\r\n";
       echo "<option value='WI'";
       IF ($strSelectState=="WI") {
           echo " selected ";
       }
       echo ">WISCONSIN</option>\r\n";
       echo "<option value='WY'";
       IF ($strSelectState=="WY") {
           echo " selected ";
       }
       echo ">WYOMING</option>\r\n";
       echo "</select>";
   }

   Function buildCountries($strSelectCountry, $strComboName) {
       $strSelectCountry = Trim(strip_tags($strSelectCountry));
       If ($strSelectCountry == "USA") {
           $strSelectCountry = "840";
       }
?>
       <select size='1' name='cbo<?=$strComboName;?>'>
       <option value=''>&nbsp;</option>
       <option value="840" <?=writeSelected($strSelectCountry, "840");?>>United States</option>
       <option value="124" <?=writeSelected($strSelectCountry, "124");?>>Canada</option>
       <option value="004" <?=writeSelected($strSelectCountry, "004");?>>Afganistan</option>
       <option value="008" <?=writeSelected($strSelectCountry, "008");?>>Albania</option>
       <option value="012" <?=writeSelected($strSelectCountry, "012");?>>Algeria</option>
       <option value="016" <?=writeSelected($strSelectCountry, "016");?>>American Samoa</option>
       <option value="020" <?=writeSelected($strSelectCountry, "020");?>>Andorra</option>
       <option value="024" <?=writeSelected($strSelectCountry, "024");?>>Angola</option>
       <option value="660" <?=writeSelected($strSelectCountry, "660");?>>Anguilla</option>
       <option value="028" <?=writeSelected($strSelectCountry, "028");?>>Antigua And Barbuda</option>
       <option value="032" <?=writeSelected($strSelectCountry, "032");?>>Argentina</option>
       <option value="051" <?=writeSelected($strSelectCountry, "051");?>>Armenia</option>
       <option value="533" <?=writeSelected($strSelectCountry, "533");?>>Aruba</option>
       <option value="036" <?=writeSelected($strSelectCountry, "036");?>>Australia</option>
       <option value="040" <?=writeSelected($strSelectCountry, "040");?>>Austria</option>
       <option value="044" <?=writeSelected($strSelectCountry, "044");?>>Bahamas</option>
       <option value="048" <?=writeSelected($strSelectCountry, "048");?>>Bahrain</option>
       <option value="050" <?=writeSelected($strSelectCountry, "050");?>>Bangladesh</option>
       <option value="052" <?=writeSelected($strSelectCountry, "052");?>>Barbados</option>
       <option value="112" <?=writeSelected($strSelectCountry, "112");?>>Belarus</option>
       <option value="056" <?=writeSelected($strSelectCountry, "056");?>>Belgium</option>
       <option value="084" <?=writeSelected($strSelectCountry, "084");?>>Belize</option>
       <option value="204" <?=writeSelected($strSelectCountry, "204");?>>Benin</option>
       <option value="060" <?=writeSelected($strSelectCountry, "060");?>>Bermuda</option>
       <option value="064" <?=writeSelected($strSelectCountry, "064");?>>Bhutan</option>
       <option value="068" <?=writeSelected($strSelectCountry, "068");?>>Bolivia</option>
       <option value="070" <?=writeSelected($strSelectCountry, "070");?>>Bosnia and Herzegovina</option>
       <option value="072" <?=writeSelected($strSelectCountry, "072");?>>Botswana</option>
       <option value="076" <?=writeSelected($strSelectCountry, "076");?>>Brazil</option>
       <option value="092" <?=writeSelected($strSelectCountry, "092");?>>British Virgin Islands</option>
       <option value="096" <?=writeSelected($strSelectCountry, "096");?>>Brunei</option>
       <option value="100" <?=writeSelected($strSelectCountry, "100");?>>Bulgaria</option>
       <option value="854" <?=writeSelected($strSelectCountry, "854");?>>Burkina Faso</option>
       <option value="108" <?=writeSelected($strSelectCountry, "108");?>>Burundi</option>
       <option value="116" <?=writeSelected($strSelectCountry, "116");?>>Cambodia</option>
       <option value="120" <?=writeSelected($strSelectCountry, "120");?>>Cameroon</option>
       <option value="132" <?=writeSelected($strSelectCountry, "132");?>>Cape Verde</option>
       <option value="136" <?=writeSelected($strSelectCountry, "136");?>>Cayman Isl</option>
       <option value="140" <?=writeSelected($strSelectCountry, "140");?>>Central African Republic</option>
       <option value="148" <?=writeSelected($strSelectCountry, "148");?>>Chad</option>
       <option value="152" <?=writeSelected($strSelectCountry, "152");?>>Chile</option>
       <option value="156" <?=writeSelected($strSelectCountry, "156");?>>China</option>
       <option value="170" <?=writeSelected($strSelectCountry, "170");?>>Colombia</option>
       <option value="174" <?=writeSelected($strSelectCountry, "174");?>>Comoros</option>
       <option value="178" <?=writeSelected($strSelectCountry, "178");?>>Congo</option>
       <option value="188" <?=writeSelected($strSelectCountry, "188");?>>Costa Rica</option>
       <option value="384" <?=writeSelected($strSelectCountry, "384");?>>Cote D'Ivoire</option>
       <option value="191" <?=writeSelected($strSelectCountry, "191");?>>Croatia</option>
       <option value="196" <?=writeSelected($strSelectCountry, "196");?>>Cyprus</option>
       <option value="203" <?=writeSelected($strSelectCountry, "203");?>>Czech Republic</option>
       <option value="208" <?=writeSelected($strSelectCountry, "208");?>>Denmark</option>
       <option value="212" <?=writeSelected($strSelectCountry, "212");?>>Dominica</option>
       <option value="214" <?=writeSelected($strSelectCountry, "214");?>>Dominican Rep</option>
       <option value="626" <?=writeSelected($strSelectCountry, "626");?>>East Timor</option>
       <option value="218" <?=writeSelected($strSelectCountry, "218");?>>Ecuador</option>
       <option value="818" <?=writeSelected($strSelectCountry, "818");?>>Egypt</option>
       <option value="222" <?=writeSelected($strSelectCountry, "222");?>>El Salvador</option>
       <option value="226" <?=writeSelected($strSelectCountry, "226");?>>Equatorial Guinea</option>
       <option value="233" <?=writeSelected($strSelectCountry, "233");?>>Estonia</option>
       <option value="242" <?=writeSelected($strSelectCountry, "242");?>>Fiji</option>
       <option value="246" <?=writeSelected($strSelectCountry, "246");?>>Finland</option>
       <option value="250" <?=writeSelected($strSelectCountry, "250");?>>France</option>
       <option value="254" <?=writeSelected($strSelectCountry, "254");?>>French Guiana</option>
       <option value="258" <?=writeSelected($strSelectCountry, "258");?>>French Polynesia</option>
       <option value="270" <?=writeSelected($strSelectCountry, "270");?>>Gambia</option>
       <option value="268" <?=writeSelected($strSelectCountry, "268");?>>Georgia</option>
       <option value="276" <?=writeSelected($strSelectCountry, "276");?>>Germany</option>
       <option value="288" <?=writeSelected($strSelectCountry, "288");?>>Ghana</option>
       <option value="300" <?=writeSelected($strSelectCountry, "300");?>>Greece</option>
       <option value="304" <?=writeSelected($strSelectCountry, "304");?>>Greenland</option>
       <option value="308" <?=writeSelected($strSelectCountry, "308");?>>Grenada</option>
       <option value="312" <?=writeSelected($strSelectCountry, "312");?>>Guadeloupe</option>
       <option value="316" <?=writeSelected($strSelectCountry, "316");?>>Guam</option>
       <option value="320" <?=writeSelected($strSelectCountry, "320");?>>Guatemala</option>
       <option value="324" <?=writeSelected($strSelectCountry, "324");?>>Guinea</option>
       <option value="328" <?=writeSelected($strSelectCountry, "328");?>>Guyana</option>
       <option value="332" <?=writeSelected($strSelectCountry, "332");?>>Haiti</option>
       <option value="340" <?=writeSelected($strSelectCountry, "340");?>>Honduras</option>
       <option value="344" <?=writeSelected($strSelectCountry, "344");?>>Hong Kong</option>
       <option value="348" <?=writeSelected($strSelectCountry, "348");?>>Hungary</option>
       <option value="352" <?=writeSelected($strSelectCountry, "352");?>>Iceland</option>
       <option value="356" <?=writeSelected($strSelectCountry, "356");?>>India</option>
       <option value="360" <?=writeSelected($strSelectCountry, "360");?>>Indonesia</option>
       <option value="372" <?=writeSelected($strSelectCountry, "372");?>>Ireland</option>
       <option value="376" <?=writeSelected($strSelectCountry, "376");?>>Israel</option>
       <option value="380" <?=writeSelected($strSelectCountry, "380");?>>Italy</option>
       <option value="388" <?=writeSelected($strSelectCountry, "388");?>>Jamaica</option>
       <option value="392" <?=writeSelected($strSelectCountry, "392");?>>Japan</option>
       <option value="400" <?=writeSelected($strSelectCountry, "400");?>>Jordan</option>
       <option value="398" <?=writeSelected($strSelectCountry, "398");?>>Kazahstan</option>
       <option value="404" <?=writeSelected($strSelectCountry, "404");?>>Kenya</option>
       <option value="414" <?=writeSelected($strSelectCountry, "414");?>>Kuwait</option>
       <option value="418" <?=writeSelected($strSelectCountry, "418");?>>Laos</option>
       <option value="428" <?=writeSelected($strSelectCountry, "428");?>>Latvia</option>
       <option value="422" <?=writeSelected($strSelectCountry, "422");?>>Lebanon</option>
       <option value="426" <?=writeSelected($strSelectCountry, "426");?>>Lesotho</option>
       <option value="430" <?=writeSelected($strSelectCountry, "430");?>>Liberia</option>
       <option value="438" <?=writeSelected($strSelectCountry, "438");?>>Liechtenstein</option>
       <option value="440" <?=writeSelected($strSelectCountry, "440");?>>Lithuania</option>
       <option value="442" <?=writeSelected($strSelectCountry, "442");?>>Luxembourg</option>
       <option value="446" <?=writeSelected($strSelectCountry, "446");?>>Macau</option>
       <option value="807" <?=writeSelected($strSelectCountry, "807");?>>Macedonia</option>
       <option value="450" <?=writeSelected($strSelectCountry, "450");?>>Madagascar</option>
       <option value="458" <?=writeSelected($strSelectCountry, "458");?>>Malaysia</option>
       <option value="466" <?=writeSelected($strSelectCountry, "466");?>>Mali</option>
       <option value="470" <?=writeSelected($strSelectCountry, "470");?>>Malta</option>
       <option value="584" <?=writeSelected($strSelectCountry, "584");?>>Marshall Islands</option>
       <option value="474" <?=writeSelected($strSelectCountry, "474");?>>Martinique</option>
       <option value="480" <?=writeSelected($strSelectCountry, "480");?>>Mauritius</option>
       <option value="175" <?=writeSelected($strSelectCountry, "175");?>>Mayotte</option>
       <option value="484" <?=writeSelected($strSelectCountry, "484");?>>Mexico</option>
       <option value="498" <?=writeSelected($strSelectCountry, "498");?>>Moldova</option>
       <option value="492" <?=writeSelected($strSelectCountry, "492");?>>Monaco</option>
       <option value="496" <?=writeSelected($strSelectCountry, "496");?>>Mongolia</option>
       <option value="504" <?=writeSelected($strSelectCountry, "504");?>>Morocco</option>
       <option value="508" <?=writeSelected($strSelectCountry, "508");?>>Mozambique</option>
       <option value="104" <?=writeSelected($strSelectCountry, "104");?>>Myanmar</option>
       <option value="516" <?=writeSelected($strSelectCountry, "516");?>>Namibia</option>
       <option value="524" <?=writeSelected($strSelectCountry, "524");?>>Nepal</option>
       <option value="530" <?=writeSelected($strSelectCountry, "530");?>>Netherland Antilles</option>
       <option value="528" <?=writeSelected($strSelectCountry, "528");?>>Netherlands</option>
       <option value="540" <?=writeSelected($strSelectCountry, "540");?>>New Caledonia</option>
       <option value="554" <?=writeSelected($strSelectCountry, "554");?>>New Zealand</option>
       <option value="558" <?=writeSelected($strSelectCountry, "558");?>>Nicaragua</option>
       <option value="562" <?=writeSelected($strSelectCountry, "562");?>>Niger</option>
       <option value="566" <?=writeSelected($strSelectCountry, "566");?>>Nigeria</option>
       <option value="578" <?=writeSelected($strSelectCountry, "578");?>>Norway</option>
       <option value="512" <?=writeSelected($strSelectCountry, "512");?>>Oman</option>
       <option value="586" <?=writeSelected($strSelectCountry, "586");?>>Pakistan</option>
       <option value="591" <?=writeSelected($strSelectCountry, "591");?>>Panama</option>
       <option value="598" <?=writeSelected($strSelectCountry, "598");?>>Papua New Guinea</option>
       <option value="600" <?=writeSelected($strSelectCountry, "600");?>>Paraguay</option>
       <option value="604" <?=writeSelected($strSelectCountry, "604");?>>Peru</option>
       <option value="608" <?=writeSelected($strSelectCountry, "608");?>>Philippines</option>
       <option value="616" <?=writeSelected($strSelectCountry, "616");?>>Poland</option>
       <option value="620" <?=writeSelected($strSelectCountry, "620");?>>Portugal</option>
       <option value="630" <?=writeSelected($strSelectCountry, "630");?>>Puerto Rico</option>
       <option value="634" <?=writeSelected($strSelectCountry, "634");?>>Qatar</option>
       <option value="642" <?=writeSelected($strSelectCountry, "642");?>>Romania</option>
       <option value="643" <?=writeSelected($strSelectCountry, "643");?>>Russia</option>
       <option value="646" <?=writeSelected($strSelectCountry, "646");?>>Rwanda</option>
       <option value="882" <?=writeSelected($strSelectCountry, "882");?>>Samoa</option>
       <option value="674" <?=writeSelected($strSelectCountry, "674");?>>San Marino</option>
       <option value="682" <?=writeSelected($strSelectCountry, "682");?>>Saudi Arabia</option>
       <option value="686" <?=writeSelected($strSelectCountry, "686");?>>Senegal</option>
       <option value="694" <?=writeSelected($strSelectCountry, "694");?>>Sierra Leone</option>
       <option value="702" <?=writeSelected($strSelectCountry, "702");?>>Singapore</option>
       <option value="703" <?=writeSelected($strSelectCountry, "703");?>>Slovakia</option>
       <option value="705" <?=writeSelected($strSelectCountry, "705");?>>Slovenia</option>
       <option value="706" <?=writeSelected($strSelectCountry, "706");?>>Somalia</option>
       <option value="710" <?=writeSelected($strSelectCountry, "710");?>>South Africa</option>
       <option value="410" <?=writeSelected($strSelectCountry, "410");?>>South Korea</option>
       <option value="724" <?=writeSelected($strSelectCountry, "724");?>>Spain</option>
       <option value="144" <?=writeSelected($strSelectCountry, "144");?>>Sri Lanka</option>
       <option value="662" <?=writeSelected($strSelectCountry, "662");?>>St. Lucia</option>
       <option value="666" <?=writeSelected($strSelectCountry, "666");?>>St. Pierre and Miquelon</option>
       <option value="670" <?=writeSelected($strSelectCountry, "670");?>>St. Vincent and the Grenadines</option>
       <option value="748" <?=writeSelected($strSelectCountry, "748");?>>Swaziland</option>
       <option value="752" <?=writeSelected($strSelectCountry, "752");?>>Sweden</option>
       <option value="756" <?=writeSelected($strSelectCountry, "756");?>>Switzerland</option>
       <option value="158" <?=writeSelected($strSelectCountry, "158");?>>Taiwan</option>
       <option value="764" <?=writeSelected($strSelectCountry, "764");?>>Thailand</option>
       <option value="780" <?=writeSelected($strSelectCountry, "780");?>>Trinidad And Tobago</option>
       <option value="788" <?=writeSelected($strSelectCountry, "788");?>>Tunisia</option>
       <option value="792" <?=writeSelected($strSelectCountry, "792");?>>Turkey</option>
       <option value="795" <?=writeSelected($strSelectCountry, "795");?>>Turkmenistan</option>
       <option value="796" <?=writeSelected($strSelectCountry, "796");?>>Turks And Caicos Islands</option>
       <option value="850" <?=writeSelected($strSelectCountry, "850");?>>U.S. Virgin Islands</option>
       <option value="800" <?=writeSelected($strSelectCountry, "800");?>>Uganda</option>
       <option value="804" <?=writeSelected($strSelectCountry, "804");?>>Ukraine</option>
       <option value="784" <?=writeSelected($strSelectCountry, "784");?>>United Arab Emirates</option>
       <option value="826" <?=writeSelected($strSelectCountry, "826");?>>United Kingdom</option>
       <option value="858" <?=writeSelected($strSelectCountry, "858");?>>Uruguay</option>
       <option value="862" <?=writeSelected($strSelectCountry, "862");?>>Venezuela</option>
       <option value="704" <?=writeSelected($strSelectCountry, "704");?>>Vietnam</option>
       <option value="732" <?=writeSelected($strSelectCountry, "732");?>>Western Sahara</option>
       <option value="887" <?=writeSelected($strSelectCountry, "887");?>>Yemen</option>
       <option value="891" <?=writeSelected($strSelectCountry, "891");?>>Yugoslavia</option>
       <option value="180" <?=writeSelected($strSelectCountry, "180");?>>Zaire</option>
       <option value="894" <?=writeSelected($strSelectCountry, "894");?>>Zambia</option>
       <option value="716" <?=writeSelected($strSelectCountry, "716");?>>Zimbabwe</option>
       </select>
<?
  }

  Function resampleJpeg($forcedWidth, $forcedHeight, $sourceFile, $destFile, $imgType, $imgQual=100) {
    // imgQual from 0 (worst) to 100 (best)
    // imgType must be "jpg" or "gif"
    // At most, one of forcedWidth and forcedHeight will actually be forced. 
    // The other dimension will be calculated to maintain original aspect ratio.
    // If both dimensions are within forcedWidth and forcedHeight they are kept.
    
    if (file_exists($sourceFile))
    {
        $imgSize = getimagesize($sourceFile);
        if ($imgSize[0] > $forcedWidth)
        {
            $newWidth = $forcedWidth;
            $newHeight =($forcedWidth / $imgSize[0]) * $imgSize[1];
        }
        elseif ($imgSize[1] > $forcedHeight)
        {
            $newHeight = $forcedHeight;
            $newWidth = ($forcedHeight / $imgSize[1]) * $imgSize[0];    
        }
        else
        {
            $newWidth = $imgSize[0];
            $newHeight = $imgSize[1];
        }
        if ($imgType == "jpg") 
        {
            $imgSrc = @imagecreatefromjpeg($sourceFile);
        }
        elseif ($imgType == "gif")
        {
            $imgSrc = @imagecreatefromgif($sourceFile);
        }
        if (!$imgSrc)
        {
            return false;
        }
        // For (GD < 2.0.1) compatibility
        // imagecreate sux
        $imgDest = @imagecreatetruecolor($newWidth, $newHeight);
        if (!$imgDest)
        {
            $imgDest = @imagecreate($newWidth, $newHeight);
        }
        if (!$imgDest)
        {
            return false;
        }
        imagecopyresampled($imgDest, $imgSrc, 0, 0, 0, 0, $newWidth, $newHeight, $imgSize[0], $imgSize[1]);
        imagejpeg($imgDest, $destFile, $imgQual);
        imagedestroy($imgDest);
        return true;
    }
    else
    {
        return false;
    }
  }
  
  Function buildPeripheralClassSelect($selectedClass) {
      global $progText59, $progText310, $progText311, $progText68, $progText312, $progText313, $progText314, $progText61;
      global $progText62, $progText65, $progText127;
      ?> <select name='cboPeripheralClass' size='1'>
                  <option value=''></option>
                  <option value='processor' <?=writeSelected("processor", $selectedClass);?>><?=$progText59;?></option>
                  <option value='opticalStorage' <?=writeSelected("opticalStorage", $selectedClass);?>><?=$progText310;?></option>
                  <option value='diskStorage' <?=writeSelected("diskStorage", $selectedClass);?>><?=$progText311;?></option>
                  <option value='netAdapter' <?=writeSelected("netAdapter", $selectedClass);?>><?=$progText68;?></option>
                  <option value='keyboard' <?=writeSelected("keyboard", $selectedClass);?>><?=$progText312;?></option>
                  <option value='pointingDevice' <?=writeSelected("pointingDevice", $selectedClass);?>><?=$progText313;?></option>
                  <option value='printer' <?=writeSelected("printer", $selectedClass);?>><?=$progText314;?></option>
                  <option value='displayAdaptor' <?=writeSelected("displayAdaptor", $selectedClass);?>><?=$progText61;?></option>
                  <option value='RAM' <?=writeSelected("RAM", $selectedClass);?>><?=$progText62;?></option>
                  <option value='soundCard' <?=writeSelected("soundCard", $selectedClass);?>><?=$progText65;?></option>
                  <option value='monitor' <?=writeSelected("monitor", $selectedClass);?>><?=$progText127;?></option>
            </select> <?
  }   

  // $vendorID - vendorID that the drop down should default to
  // $showLink - if true, show 'add new vendor' link next to drop down
  // $formName is name of form this is going into; necessary for javascript onChange form submit.
      // Set $formName = "formXYZ" if you DON'T want the onChange submit, but do need a formName
      // If you don't need javascript or a form name, just set to false.
  // $showUnassigned - if true, add 'unassigned' to list of options in drop down.
  Function buildVendorSelect($vendorID, $showLink=TRUE, $formName=FALSE, $showUnassigned=FALSE, $useNamesForValues=FALSE, $numLines=1) {
      global $progText1225, $progText866;

      if ($formName AND ($formName != "formXYZ")) {
          $jsText = "onChange=\"document.$formName.submit();\"";
      }

      // Get all vendors for the drop down menu
       echo "<SELECT SIZE=\"$numLines\" " . (($numLines > 1) ? "MULTIPLE " : "") . "NAME=\"cboVendorID" . (($numLines > 1) ? "[]" : "") . "\" $jsText>";
      $strSQL = "SELECT * FROM vendors WHERE accountID=" . $_SESSION['accountID'] . "
        ORDER BY vendorName ASC";
      $result = dbquery($strSQL);
      if ($numLines == 1) {
          echo "   <OPTION VALUE=\"\">&nbsp;</OPTION>\n";
      }
      If ($showUnassigned) {
          echo "   <OPTION VALUE=\"unassigned\" ".writeSelected($vendorID, "unassigned").">* ".$progText866." *</OPTION>\n";
      }
      while ($row = mysql_fetch_array($result)) {
          echo "   <OPTION VALUE=\"" . (($useNamesForValues) ? $row['vendorName'] : $row['vendorID']) . "\" ";
          echo writeSelected($vendorID, $row['vendorID']);
          echo ">".$row['vendorName']."</OPTION>\n";
       }
       echo "</SELECT>";
       if ($showLink) {
           echo " &nbsp;<a href='admin_vendors.php'>".$progText1225."</a>";
       }
  }
  
    // Validation function for select fields that permit user to choose >, <, or = (and which will
    //   then be input into a sql query).
    function cleanComparatorInput($combo) {
        if (($combo != "lt") && ($combo != "gt") && ($combo != "eq")) {
            $combo = "";
        } else {
            return $combo;
        }
    }
    
    // Turn the output from cleanComparatorInput() into an actual operator for SQL query
    function convertSign($combo) {
        if ($combo == "lt") {
            return "<";
        } elseif ($combo == "gt") {
            return ">";
        } elseif ($combo == "eq") {
            return "=";
        } else {
            return "";
        }
    }
    
    function getOrPost($key) {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        } elseif (isset($_POST[$key])) {
            return $_POST[$key];
        } else {
            return '';
        }
    }
    
    function postOrGet($key) {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        } elseif (isset($_GET[$key])) {
            return $_GET[$key];
        } else {
            return '';
        }
    }    
    
    function destroySession() {
        $_SESSION = array();
        
        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        //if (isset($_COOKIE[session_name()])) {
        //   setcookie(session_name(), '', time()-42000, '/');
        //}
        
        // Finally, destroy the session.
        session_destroy();        
    }
?>
