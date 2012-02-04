<?
  $licenseKey = "545+sumpX7Pn70DhntQQ66zeBSw/XzI"; # Enter the license key you were assigned with your purchase.
                                 # Do not share this key with anyone - it contains your name, and belongs to you alone.

  $adminEmail  = "david@literaltech.com"; # Where rare administrative emails will go

  $secureAdmin = 0; # Set to 1 if SSL is available
  $sslPort = 443; # What port, if using SSL?

  $adminDefinedCategory = ""; # Extra configurable system category, in addition to 'user', 'spare', and 'independent'.
                              # Leave blank if you don't want to create your own category. Otherwise, limit value to 15 characters in length.

  If (!$_COOKIE['rowLimit']) {
      $rowLimit = 15; # How many records any given page should show at one time
  } else {
      $rowLimit = $_COOKIE['rowLimit'];
  }

  If (!$_COOKIE['windowWidth']) {
      $windowWidth = 940; # how wide should the site be (including leftnav)
                          # Lower than 768 is NOT recommended!
                          # If you're using $adminDefinedCategory, lower than 790 is NOT recommended!
  } else {
      $windowWidth = $_COOKIE['windowWidth'];
  }

  $languageFilterOK = TRUE; # Set to FALSE if you don't want foul language filtered out.

  $defaultLanguage = "english"; # Can also be set to "dutch" or "german"

  $autoCreateDefaults = 1; # Setting this to 0 (zero) will prevent the Syslist Companion Agent from
                           # defining default software and peripherals for new system types. Not recommended.
                           
  $longHostNames = 0; # Setting this to 1 causes the Companion Agent to report extra-long hostnames
                      # for PCs (ie, hostnames that include domain information, if detectable.)

  $recordIP = 1; # Setting this to 1 causes the Companion Agent to report the IP address of the PC
                 # on which it is installed. If you are using DHCP for most systems on your network, or
                 # have a firewall between your systems and Syslist, you should probably leave this set to 0.
  
  $europeanDates = 0; # Setting this to 1 causes dates to be validated and displayed in the format
                      # dd/mm/yyyy instead of mm/dd/yyyy
                      
  $extraSystemField = "Other"; # If you would like an additional field with which to capture system data, rename this
                               # whatever you wish. If you do NOT want an additional field (even one named "Other"),
                               # just leave this blank ("";)
                               
  $captureSoftwareHistory = 1; # Setting this to 1 causes software history to be tracked just like peripheral history:
                               #   i.e. software "uninstallations" (deletes) by the agent or by syslist admins, as well as moves by admins, are recorded. 
                               #   Syslist does not track the installation (creation) of software.

  # -------------------------------------------------------------------- #

      session_start();
      /* This is the definitive list of session vars
      session_register("userID");
      session_register("accountID");
      session_register("sessionTime");
      session_register("sessionSecurity");
      session_register("locationStatus");
      session_register("languageChoice");
      */

  # $defaultCurrency = "$";

  unset($includeFolder, $includeFolder2);
  $includeFolder  .= "Includes";
  $includeFolder2 .= "Lang";
  Include($includeFolder."/generalFunctions.inc.php");
  
  $cboLanguage = strip_tags(getOrPost('cboLanguage'));
  $languageCookie = $_COOKIE['languageCookie'];
  If ($cboLanguage) {
  // If user selected a language from the dropdown.
      $_SESSION['languageChoice'] = $cboLanguage;
      If ($languageCookie) {
      // Assign user's new language choice to cookie, if cookie exists.
          setcookie("languageCookie", $_SESSION['languageChoice'], 31104000); # expires in 360 days.
      }

  } ElseIf ($languageCookie) {
  // If user has a cookie that specifies their previous language choice
      $_SESSION['languageChoice'] = $languageCookie;

  } ElseIf (!$_SESSION['languageChoice']) {
  // If user has not specified a language choice, go with default
      $_SESSION['languageChoice'] = $defaultLanguage;
  }

  $cboNameLocation = strip_tags(getOrPost('cboNameLocation'));
  If ($cboNameLocation) {
      list($_SESSION['locationStatus'], $spare, $systemStatus) = explode(",", $cboNameLocation);
  }

  Include($includeFolder."/securityFunctions.inc.php");
  Include($includeFolder."/headerFunctions.inc.php");
  Include($includeFolder."/userFunctions.inc.php");
  Include($includeFolder."/showfull.inc.php");
  Include($includeFolder."/locationFunctions.inc.php");

  Include($includeFolder2."/".$_SESSION['languageChoice'].".inc.php");
  Include($includeFolder."/db.inc.php");

  Function getPageName() {
      $returnString = strrchr($_SERVER['PHP_SELF'], "/");
      $returnString = substr($returnString, 1);
      Return $returnString;
  }

  Function makeHomeURL($stringToRemove = "") {
      $strURL = $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
      If ($stringToRemove != "") {
         $intPos = strpos($strURL, $stringToRemove);
         $strURL = substr($strURL, 0, ($intPos-1));
      }
      Return $strURL;
  }

  If ($secureAdmin) {
      $urlPrefix = "https";
  } Else {
      $urlPrefix = "http";
  }
  $pageName = getPageName();
  $homeURL = makeHomeURL($pageName);
  $pageURL = $homeURL."/".$pageName;

  $_SESSION['accountID'] = 1;

  forceSSL();

  // The code below enables the user to change peripheral status and system status from 
  //   many different scripts throughout syslist.
  $hardwareID    = getOrPost('hardwareID');
  $peripheralID  = getOrPost('peripheralID');
  $setStatus     = getOrPost('setStatus');
  
  If ($_SESSION['sessionSecurity'] < 2) {
      If (($hardwareID OR $peripheralID) AND $setStatus) {
          If ($peripheralID) {
              $strSQL_ps = "UPDATE peripherals SET peripheralStatus='$setStatus' WHERE peripheralID=$peripheralID
                AND accountID=" . $_SESSION['accountID'];
              $result_ps = dbquery($strSQL_ps);
          } Else {  # hardware
              $strSQL_hs = "UPDATE hardware SET hardwareStatus='$setStatus', lastManualUpdate='".date("Ymd")."',
                lastManualUpdateBy=" . $_SESSION['userID'] . " WHERE hardwareID=$hardwareID AND accountID=" . $_SESSION['accountID'];
              $result_hs = dbquery($strSQL_hs);
          }
      }
  }
?>
