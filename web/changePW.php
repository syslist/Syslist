<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 900);

  If (getOrPost('btnSubmit')) {
      $strOldPassword   = validateText($progText191, getOrPost('txtOldPassword'), 6, 10, TRUE, FALSE);
      $strNewPassword   = validateText($progText192, getOrPost('txtNewPassword'), 6, 10, TRUE, FALSE);
      $strConfPassword  = validateText($progText193, getOrPost('txtConfPassword'), 6, 10, TRUE, FALSE);

      If (!$strError) {
           $strOldPassword = md5($strOldPassword);

           $strSQL = "SELECT userID FROM tblSecurity WHERE ID=".$_SESSION['userID']." AND password='$strOldPassword' AND accountID=" . $_SESSION['accountID'] . "";
           $result = dbquery($strSQL);
           $intFound = mysql_num_rows($result);
           If ($intFound == 0) {
                $strError = $progText194;
           } ElseIf ($strNewPassword != $strConfPassword) {
                $strError = $progText195;
           } Else {
                $strPassword = md5($strNewPassword);

                $strSQL = "UPDATE tblSecurity SET password='$strPassword' WHERE accountID=" . $_SESSION['accountID'] . " AND id=".$_SESSION['userID'];
                $result = dbquery($strSQL);
                $strError = "Your password has been updated successfully.";

                $strSQL = "SELECT email FROM tblSecurity WHERE accountID=" . $_SESSION['accountID'] . " AND id=".$_SESSION['userID'];
                $result = dbquery($strSQL);
                $row = mysql_fetch_row($result);
                $strEmail = $row[0];

                $msgBody = $msgBody."From: ".$urlPrefix."://".$homeURL."\n\n";
                $msgBody = $msgBody.$progTextBlock14;

                $strSubject = $progText196;
                mail($strEmail, $strSubject, $msgBody, "From: $adminEmail\r\nReply-To: $adminEmail\r\n");
           }
      }
  }

  writeHeader($progText198);
  declareError(TRUE);
?>

<form name="form1" method="POST" action="changePW.php">
  <p><table border='0' cellpadding='2'>
    <tr>
      <td><?=$progText191;?>: &nbsp;</td>
      <td><input type="password" name="txtOldPassword" size="10"></td>
    </tr>
    <tr><td colspan='2'>&nbsp;</td></tr>
    <tr>
      <td><?=$progText192;?>: &nbsp;</td>
      <td><input type="password" name="txtNewPassword" size="10"></td>
    </tr>
    <tr>
      <td><?=$progText193;?>: &nbsp;</td>
      <td><input type="password" name="txtConfPassword" size="10"></td>
    </tr>
  </table><p>

  <input type="submit" value="<?=$progText21;?>" name="btnSubmit">
</form>

<?
  writeFooter();
?>
