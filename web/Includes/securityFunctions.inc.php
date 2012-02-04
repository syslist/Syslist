<?
  Function checkPermissions($intSecurity, $intTimeOut) {
    global $secureAdmin, $homeURL, $pageURL;

    If ($secureAdmin) {
         $strRedir   = "https://".$pageURL;
         $strHeader  = "https://".$homeURL."/login.php";
    } Else {
         $strRedir   = "http://".$pageURL;
         $strHeader  = "http://".$homeURL."/login.php";
    }

    If ($_SERVER['QUERY_STRING'] != "") {
         $encodedQS  = str_replace("&", "%26", $_SERVER['QUERY_STRING']);
         $encodedQS  = str_replace("=", "%3D", $encodedQS);
         $strRedir   = $strRedir."?".$encodedQS;
    }

    $securityLvl = $_SESSION['sessionSecurity'];

    if (is_numeric($securityLvl)) { // are they logged in?
        if ($_SESSION['sessionTime'] < (time() - $intTimeOut)) {
            // if too much time (as defined by the page) has passed since last request
            destroySession();
            redirect($strHeader, "strError=timeout&strRedir=$strRedir");

        } elseif ($securityLvl > $intSecurity) {
            // if user's security level is too low (as defined by the page)
            $_SESSION['sessionTime'] = time(); # current time in seconds
            redirect($strHeader, "strError=security&strRedir=$strRedir");

        } else {
            // let user in!
            $_SESSION['sessionTime'] = time(); # current time in seconds
        }
    } Else {
        redirect($strHeader, "strError=login&strRedir=$strRedir");
    }
  }

  Function forceSSL() {
      global $secureAdmin, $sslPort, $pageURL;
      If ($secureAdmin AND ($_SERVER['SERVER_PORT'] != $sslPort)) {
          $strRedir = "https://".$pageURL;
          redirect($strRedir, $_SERVER['QUERY_STRING']);
      }
  }

  Function writeSecurityLevel($intLevel) {
      global $progText269, $progText270, $progText271, $progText272;
      If ($intLevel == "0") {
          $strLevel = $progText269;
      } ElseIf ($intLevel == "1") {
          $strLevel = $progText270;
      } ElseIf ($intLevel == "2") {
          $strLevel = $progText271;
      } ElseIf ($intLevel == "3") {
          $strLevel = $progText272;
      }
      Return "<font color='green'>$strLevel</font>";
  }
?>
