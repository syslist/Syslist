<?
  Include("Includes/global.inc.php");

  $strError = getOrPost('strError');
  $strRedir = getOrPost('strRedir');  
  
  If ((getOrPost('btnSubmit') != "") AND ($_COOKIE['failedLoginCount'] > 4) AND (time() < $_COOKIE['failedLoginTime'])) {
    $strError = $progTextBlock30;

  } ElseIf (getOrPost('btnSubmit') != "") {
    $strPassword = validateText($progText496, getOrPost('txtPassword'), 6, 10, TRUE, FALSE);
    $strUserName = validateText($progText255, getOrPost('txtUserName'), 3, 20, TRUE, FALSE);
    
    // retrieve extra input from agent, if appropriate
    $hardwareID = cleanFormInput(getOrPost('hardwareID'));

    If ($strError == "") {
        $strPassword = md5($strPassword);
        $strSQL = "SELECT id, securityLevel, email, stuckAtLocation, userLocationID FROM tblSecurity WHERE hidden='0' AND
          userID='$strUserName' AND password='$strPassword'";
        $result = dbquery($strSQL);
        $row = mysql_fetch_row($result);

        If ($row[0] != "") {

            // Save $_SESSION['languageChoice'], which is the only session value that cannot be
            // destroyed on login.
            $tempLanguageChoice = $_SESSION['languageChoice'];

            // Destroy session, which may have been created in an unsecured (ie http) environment
            destroySession();
             
            // Recreate session, now secure.
            session_start();
            /*
            session_register("userID");
            session_register("sessionTime");
            session_register("sessionSecurity");
            session_register("locationStatus");
            session_register("languageChoice");
            */

            // If user has selected a language (other than the default), and does not yet
            // have a languageCookie, give him one for future reference.
            If (!$languageCookie AND ($tempLanguageChoice!=$defaultLanguage)) {
                setcookie("languageCookie", $tempLanguageChoice, 31104000); # expires in 360 days.
                $_SESSION['languageChoice'] = $tempLanguageChoice;
            }

            $_SESSION['userID']           = $row[0];
            $_SESSION['sessionTime']      = time();
            $_SESSION['sessionSecurity']  = $row[1];
            // just for SELF-hosted version
            $userEmail                    = $row[2]; 
	    
            // Full access users don't get stuck
            if ($_SESSION['sessionSecurity'] == 0) {
                $_SESSION['stuckAtLocation'] = 0;
            } else {	       
                $_SESSION['stuckAtLocation'] = $row[3];
            }
            // Hard fix locationStatus if user is stuck there 
            if ($_SESSION['stuckAtLocation']) {
                $_SESSION['locationStatus'] = $row[4];
            }

            $strSQL2 = "Update tblSecurity Set lastLogin=".date("YmdHis")." Where id = ".$row[0];
            $result2 = dbquery($strSQL2);

            setcookie("failedLoginCount", "", time()-36000);
            setcookie("failedLoginTime", "", time()-3600);

            $hardwareID = getOrPost('hardwareID');
            If (!$strError AND ($userEmail=="name@address.com")) {
                redirect("editUser.php", "editID=" . $_SESSION['userID']);
            } ElseIf (!$strError AND $hardwareID) {
                redirect("showfull.php", "hardwareID=$hardwareID");
            } ElseIf (!$strError) {
                header ("Location: $strRedir");
                exit;
            }

        } Else {
            $strError = $progText609; # username or password incorrect
            If (!$failedLoginCount OR ($failedLoginCount > 4)) {
                $failedLoginCount = 1;
            } Else {
                $failedLoginCount++;
            }
            setcookie("failedLoginCount", $failedLoginCount, (time()+300000));
            setcookie("failedLoginTime", (time()+300), (time()+300000));
        }
    }
  }

  writeHeader("", $altWindowWidth);

  switch ($strError) {
    case "timeout":
        $strError = $progText610;
        break;
    case "security":
        $strError = $progText611;
        $intNote = 1;
        break;
    case "login":
        $strError = $progText612;
        break;
    case "":
        $strError = "";
        break;
  }

  $strSQL_license  = "Select decode('".addslashes(base64_decode($licenseKey))."','Development*Test.Key')";
  $result_license  = dbquery($strSQL_license);
  $row_license     = mysql_fetch_row($result_license);

  $commaCount = substr_count($row_license[0], ",");
  If ($commaCount !== 3) {
      $strError = $progTextZZ1;
      $licenseFailure = 1;
  } Else {
      $aryLicense = explode(",", $row_license[0]);
  }
  
  If (!$licenseFailure AND !is_numeric($aryLicense[3])) {
      $strError = $progTextZZ1;
      $licenseFailure = 1;
  }
  
  If (!$licenseFailure) {
      echo "<p><b>".$progTextZZ2.":</b> &nbsp;<i>".$aryLicense[1]."</i>\n";
      echo "<br><b>".$progTextZZ3.":</b> &nbsp;<i>".$aryLicense[3]." ".$progText100."</i>\n";
  
      $strSQL_count  = "SELECT count(*) FROM hardware";
      $result_count  = dbquery($strSQL_count);
      $row_count     = mysql_fetch_row($result_count);
      
      $systemsRemaining = $aryLicense[3] - $row_count[0];
      If ($systemsRemaining < 0) {
          $systemsRemaining = 0;
      }

      echo "<br><b>".$progTextZZ4.":</b> &nbsp;<i>".$row_count[0]."</i>\n";
      echo "<p><b>".$progTextZZ5.":</b> &nbsp;<i>".$systemsRemaining."</i><br>&nbsp;<br>\n";
  }

  If ($strError != "") {
      echo "<b><font color='red'>$strError</font></b><br>";
  }

  If (!$licenseFailure) {
?>

<form name="form1" method="POST" action="login.php">
  <p><table border='0' width='480'>
    <tr>
      <td width='80'><?=$progText255;?>:</td>
      <td width='400'><input type="text" name="txtUserName" value="<?echo antiSlash($strUserName);?>" size="20"></td>
    </tr>
    <tr>
      <td width='80'><?=$progText496;?>:</td>
      <td width='400'><input type="password" name="txtPassword" size="20"></td>
    </tr>
  </table><p>
  <?
       If (!$strRedir OR $intNote) {
		   $strRedir = "systems.php";
       } Else {
           $strRedir = str_replace("%26", "&", $strRedir);
           $strRedir = str_replace("%3D", "=", $strRedir);
           $strRedir = preg_replace("/&setStatus=\w/", "", $strRedir);
       }
  ?>
  <input type="hidden" name="strRedir" value="<?echo $strRedir;?>">
  <input type="submit" name="btnSubmit" value="<?=$progText21;?>">
  &nbsp; &nbsp;<a href='forgotPW.php'><font size='-1'><?=$progText613;?></font></a>
</form>

<?
  }

  writeFooter();
?>
