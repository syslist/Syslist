<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  if (getOrPost('btnSubmit')) {
      $rowLimit   = validateNumber($progText394, getOrPost('txtRowLimit'), 1, 2, TRUE);
      $siteWidth  = validateExactNumber($progText395, getOrPost('txtSiteWidth'), 640, 2500, TRUE, 0);
      If (!$strError) {
          setcookie("rowLimit", $rowLimit, (time()+18000000)); # 5000 hour expiration
          setcookie("windowWidth", $siteWidth, (time()+18000000)); # 5000 hour expiration
          $windowWidth = $siteWidth; # show change immediately, for user's sake.
          $strError = $progText396;
      }
  } else {
      $siteWidth = $windowWidth;
  }
  
  if (getOrPost('btnSubmit2') AND ($_SESSION['sessionSecurity'] < 1)) {
      $intHelpdeskUserID     = validateChoice($progText399, getOrPost('cboHelpdeskUserID'));
      $intSystemAlertUserID  = validateChoice($progText400, getOrPost('cboSystemAlertUserID'));
      $bolTicketCreate       = checkboxToBinary(cleanFormInput(getOrPost('chkTicketCreate')));
      $bolTicketUpdate       = checkboxToBinary(cleanFormInput(getOrPost('chkTicketUpdate')));
      $bolSoftwareTypeCreate = checkboxToBinary(cleanFormInput(getOrPost('chkSoftwareTypeCreate')));
      
      If (!$strError) {
          $strSQL = "UPDATE account_settings SET primaryHelpdeskUserID=".$intHelpdeskUserID.",
            systemAlertUserID=".$intSystemAlertUserID.", ccTicketCreate='$bolTicketCreate',
            ccTicketUpdate='$bolTicketUpdate', alertSoftwareTypeCreate='$bolSoftwareTypeCreate'
             WHERE accountID=" . $_SESSION['accountID'] . "";
          $result = dbquery($strSQL);
          $strError = $progText396;
      }
  } else {
      $strSQL = "SELECT * FROM account_settings WHERE accountID=" . $_SESSION['accountID'] . "";
      $result = dbquery($strSQL);
      while ($row = mysql_fetch_array($result)) {
          $intHelpdeskUserID     = $row["primaryHelpdeskUserID"];
          $intSystemAlertUserID  = $row["systemAlertUserID"];
          $bolTicketCreate       = $row["ccTicketCreate"];
          $bolTicketUpdate       = $row["ccTicketUpdate"];
          $bolSoftwareTypeCreate = $row["alertSoftwareTypeCreate"];
      }
  }

  # writeHeader($progText397);
  writeHeader();
  declareError(TRUE);
  
  If ($_SESSION['sessionSecurity'] < 1) {
      echo "<font class='instructions' size='+1'>".$progText398."</font><P>";
?>
 <form method='post' action='settings.php'>
 <table width='100%' border='0' cellpadding='4' cellspacing='0'>
  <tr>
    <td width='110' valign='top'><u><?=$progText399;?></u> &nbsp;</td>
    <td><?=$progTextBlock70;?></td>
  </tr>
  <tr>
    <td width='110'>&nbsp;</td>
    <td><?=buildUserSelect($intHelpdeskUserID, false, '', false, false, "AND securityLevel < 2", "cboHelpdeskUserID");?></td>
  </tr>
  <tr>
    <td width='110'>&nbsp;</td>
    <td><input type="checkbox" name="chkTicketCreate" value="1" <?=writeChecked(1, $bolTicketCreate)?>><?=$progText1228?>
        <br><input type="checkbox" name="chkTicketUpdate" value="1" <?=writeChecked(1, $bolTicketUpdate)?>><?=$progText1229?></td>
  </tr>
  <tr>
    <td colspan='2'>&nbsp;</td>
  </tr>
  <tr>
    <td width='110' valign='top'><u><?=$progText400;?></u> &nbsp;</td>
    <td><?=$progTextBlock71;?></td>
  </tr>
  <tr>
    <td width='110'>&nbsp;</td>
    <td><?=buildUserSelect($intSystemAlertUserID, false, '', false, false, "AND securityLevel < 2", "cboSystemAlertUserID");?></td>
  </tr>
  <tr>
    <td width='110'>&nbsp;</td>
    <td><input type="checkbox" name="chkSoftwareTypeCreate" value="1" <?=writeChecked(1, $bolSoftwareTypeCreate)?>><?=$progText401?>
  </tr>  
  <tr>
    <td colspan='2'>&nbsp;</td>
  </tr>

  <tr>
    <td colspan='2' align='right'><input type='submit' name='btnSubmit2' value='<?=$progText21;?>'></td>
  </tr>
 </table>
</form>

<table border='0' cellpadding='0' cellspacing='0'>
<tr><td><img src='Images/1pix.gif' border='0' width='1' height='8'></td></tr></table>

<?
  }
  echo "<font class='instructions' size='+1'>".$progText397."</font><P>";
?>
 <form method='post' action='settings.php'>
 <table width='100%' border='0' cellpadding='4' cellspacing='0'>
  <tr>
    <td width='110' valign='top'><u><?=$progText394;?></u> &nbsp;</td>
    <td><?=$progTextBlock22;?></td>
  </tr>
  <tr>
    <td width='110'>&nbsp;</td>
    <td><input type='text' name='txtRowLimit' value='<?=$rowLimit?>'></td>
  </tr>
  <tr>
    <td colspan='2'>&nbsp;</td>
  </tr>
  <tr>
    <td width='110' valign='top'><u><?=$progText395;?></u> &nbsp;</td>
    <td><?=$progTextBlock23;?></td>
  </tr>
  <tr>
    <td width='110'></td>
    <td><input type='text' name='txtSiteWidth' value='<?=$siteWidth?>'></td>
  </tr>
  <tr>
    <td colspan='2'>&nbsp;</td>
  </tr>

  <tr>
    <td colspan='2' align='right'><input type='submit' name='btnSubmit' value='<?=$progText21;?>'></td>
  </tr>
 </table>
</form>

<?
writeFooter();
?>
