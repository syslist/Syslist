<?
  Include("Includes/global.inc.php");

  If (getOrPost('btnSubmit')) {
      $strEmail = validateEmail($progText256, getOrPost('txtEmail'), TRUE);

      If (!$strError) {
           $strSQL = "SELECT id, userID, accountID FROM tblSecurity WHERE email='$strEmail' AND hidden='0'";
           $result = dbquery($strSQL);
           $row = mysql_fetch_row($result);
           If ($row[0] == "") {
                $strError = $progText345;
           }

           If (!$strError) {
                $strTempString = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
                for ($i = 0; $i < 8; $i++) {
                     $intPos = rand(0, 33);
                     $strTempChar = substr($strTempString, $intPos, 1);
                     $strPassword = $strPassword.$strTempChar;
                }

                $strPassword2 = md5($strPassword);
                $strSQL3 = "UPDATE tblSecurity SET password='$strPassword2' WHERE id=".$row[0];
                $result3 = dbquery($strSQL3);

                $msgBody = $progText260." '".$row[1]."' ".$progText261." '$strPassword'.";
                mail($strEmail, ($progText346.": ".date("m-d-Y")), $msgBody,
                  "From: $adminEmail\r\nReply-To: $adminEmail\r\n");

                $strError = $progTextBlock20;
           }
      }
  }

  writeHeader($progText347);
  declareError(TRUE);

  echo "<font class='soft_instructions'>".$progText348."</font>";
?>

<form name="form1" method="POST" action="forgotPW.php">
  <p><table border='0' width='415' cellpadding='2'>

    <tr>
      <td width='115'><?=$progText256;?>:</td>
      <td width='300'><input type="text" name="txtEmail" value="<?echo antiSlash($strEmail);?>" size="30" maxlength="50"></td>
    </tr>

  </table><p>

  <input type="hidden" value="Submit" name="btnSubmit">
  <input type="submit" name="btnSubmit" value="<?=$progText21?>">
</form>

<?
  writeFooter();
?>
