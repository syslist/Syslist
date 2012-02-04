<?

Function writeNavBody() {
  global $urlPrefix, $homeURL, $windowWidth;
  global $progText636, $progText637, $progText638, $progText639, $progText640, $progText641;
  global $progText642, $progText643, $progText644, $progText645, $progText646, $progText647;
  global $progText648, $progText649, $progText650, $progText651, $progText651A, $progText652, $progText653;
  global $progText654, $progText655, $progText656, $progText657, $progText658, $progText659;
  global $progText660, $progText661, $progText662, $progText663, $progText664, $progText665;
  global $progText666, $progText667, $progText668, $progText669, $progText670, $progText671;
  global $progText672, $progText678, $progText688, $progText680, $progText1219;
  global $progText238, $progText681;
?>
  <tr>
    <td width='5'>&nbsp;</td>
    <td><img src='Images/dot.gif' border='0' width='7' height='9'><a href='help.php?navHit=1'><?=$progText645;?></a></td></tr>

  <tr><td colspan='2'>&nbsp;</td></tr>

  <tr><td valign='top' colspan='2'><b><font color='#9F0000'><?=$progText646;?></font></b><br>&nbsp;<br></td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href='systems.php'><?=$progText647;?></a></td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href='detailed_p.php'><?=$progText648;?></a></td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href='detailed_sw.php'><?=$progText649;?></a></td></tr>
    
  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href="viewUsers.php"><?=$progText657;?></a></td></tr>

  <tr><td colspan='2'>&nbsp;</td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href='reportMenu.php'><?=$progText688;?></a></td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href='search.php'><?=$progText650;?></a></td></td></tr>

  <tr><td colspan='2'>&nbsp;</td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href='commentLog.php'><?=$progText651;?></a></td></tr>
  
  <tr><td colspan='2'>&nbsp;</td></tr>
  
  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href='faq.php'><?=$progText651A;?></a></td></tr>

  <tr><td colspan='2'>&nbsp;</td></tr>

  <?
   If (is_numeric($_SESSION['sessionSecurity']) AND ($_SESSION['sessionSecurity'] < 2)) {
  ?>

  <tr><td valign='top' colspan='2'><b><font color='#9F0000'><?=$progText652;?></font></b></td></tr>

  <tr><td colspan='2'>&nbsp;</td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href="admin_hw_types.php"><?=$progText653;?></A></td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href="admin_peripheral_types.php"><?=$progText654;?></A></td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href="admin_software_types.php"><?=$progText655;?></A></td></tr>
<? if (!$_SESSION['stuckAtLocation']) { ?>
  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href="admin_locations.php"><?=$progText656;?></A></td></tr>
<? } ?>
  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href="admin_vendors.php"><?=$progText1219;?></A></td></tr>

  <tr><td colspan='2'>&nbsp;</td></tr>

  <?
   }
  ?>

  <tr><td valign='top' colspan='2'><b><font color='#9F0000'><?=$progText658;?></font></b></td></tr>

  <tr><td colspan='2'>&nbsp;</td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href="editUser.php?editID=<?=$_SESSION['userID'];?>"><?=$progText659;?></A></td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href="settings.php"><?=$progText660;?></A></td></tr>

  <tr><td colspan='2'>&nbsp;</td></tr>

  <tr>
    <td width='5'>&nbsp;</td>
    <td valign='top'><img src='Images/dot.gif' border='0' width='7' height='9'><a href="logout.php"><?=$progText664;?></A></td></tr>

  <tr><td colspan='2'>&nbsp;</td></tr>

<?
}

Function writeHeader($strPageTitle = "", $siteWidth = "", $bolShowLocationBar = FALSE, $strRightOfHeaderText = "&nbsp;", $strPicURL = "", $rowID = 0, $strHWorUSER = "") {
  // New args: strPicURL contains the rel. URL to the picture
  //   delete.php needs to get passed these two to know what to delete:
  //   $rowID contains the user or hardware ID the picture's associated with
  //   $strHWorUSER specifies whether it's a "hw" or "user" ID so we know which table it's in
  

  global $urlPrefix, $homeURL, $windowWidth;
  global $progTextBlock24A, $progText411A, $progText523, $progText673, $progText674, $progText686, $progText687, $progText677;
  global $pageName, $progTextBlock53, $progText679;

  If ($strPageTitle == "") {
       $strPageTitle = "Syslist v4.1.0";
  } Else {
       $showHeader = TRUE;
  }

  If (!$siteWidth) {
      $siteWidth = $windowWidth;
  }
  $rightWidth = $siteWidth - 148;
  
?>
<HTML>
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<TITLE><? echo $strPageTitle; ?></TITLE>

<SCRIPT LANGUAGE="javascript">
<!--
function warn_on_submit(msg)
{
        if (!confirm(msg)) {
                alert("<?=$progText687;?>");
                return false;
        }
}

function popupWin(myLink, windowName, myWidth, myHeight) {
     var defaultWidth=300;
     var defaultHeight=175;
     var href;
     if (! window.focus) return true;
     if (typeof(myLink) == 'string') href=myLink;
     else href=myLink.href;
     if (myWidth=='') myWidth=defaultWidth;
     if (myHeight=='') myHeight=defaultHeight;
     myLeft=screen.width-(myWidth+20);
     myTop=screen.height-(myHeight+65);
     window.open(href,windowName,'width='+myWidth+',height='+myHeight+',left='+myLeft+',top='+myTop+',scrollbars=yes,dependent=yes,resizable=no');
     return false;
}
//-->
</script>
<LINK REL=StyleSheet HREF="<?=$includeFolder;?>styles.css" TYPE="text/css">

<STYLE>
pre { font-family: courier,serif }
</STYLE>

</HEAD>
<BODY bgcolor="#FFFFFF" vlink='blue' alink='blue' onunload='return bodyOnUnload();'>

<TABLE border='0' cellpadding='0' cellspacing='0' width='<?=$siteWidth;?>'>
<TR><TD colspan='3'>
   <TABLE border='0' cellpadding='0' cellspacing='0' width='<?=$siteWidth;?>'>
    <TR>
     <TD>
       <a href='<?=$includeFolder;?>index.php'><img src='<?=$includeFolder;?>Images/logo.gif' border='0' alt="<?=$progText674;?>"></a>
     </TD>
     <TD align='right'>
  <?
    If ($bolShowLocationBar && !$_SESSION['stuckAtLocation']) {
        buildLocationDropDown();
    } ElseIf (strpos(("!".$pageName),"login.php")) {
  ?>
     <!--
     <FORM NAME="LanguageSelect" METHOD="post" ACTION="<?=$pageName;?>">
       <b><?=$progText686;?>:</b>&nbsp;
       <SELECT class="smaller" SIZE="1" NAME="cboLanguage" onChange="document.LanguageSelect.submit();">
         <OPTION value=""> - Choose - </OPTION>
         <OPTION value="english">English</OPTION>
         <OPTION value="german">German</OPTION>
	   </SELECT> &nbsp;
       <INPUT TYPE="submit" NAME="btnSubmitLang" class= 'smaller' VALUE="<?=$progText523;?>">
     </FORM>
     -->&nbsp;
  <?
    }
  ?>
     </TD>
    </TR>
   </TABLE>
</td></tr>
<tr><td valign='top' width='136'><table cellpadding="1" cellspacing="0" border="0" bgcolor="gray" width='136'>
  <tr><td><table border="0" cellspacing="0" cellpadding="3" bgcolor="#FFF7F7" width='136' height='505'>
    <tr><td valign='top'><table border="0" cellspacing="2" cellpadding="0" width='100%'>
<?
  writeNavBody();

?>
      </td></tr></table>
    </td></tr></table>
</td></tr></table>


<? // Show picture if available, along with Delete Picture link if permitted
if ($strPicURL != "") 
{
	echo "<table cellpadding=0 cellspacing=0 width='136'><tr><td style='padding-top: 10px; padding-bottom: 5px;' align='center'><img style='border: 1px solid c0c0c0;' src='$strPicURL'></td></tr>\n";
	if ((($strHWorUSER == "hw") && ($_SESSION['sessionSecurity'] < 2)) || (($strHWorUSER == "user") && ($_SESSION['sessionSecurity'] < 1)))
		echo "<tr><td><A HREF='delete.php?id=$rowID&target=$strHWorUSER' onClick=\"return warn_on_submit('" . $progTextBlock24A . "');\">" . $progText411A . "</A></td></tr>\n"; # delete picture
	echo "</table>\n";
} 
?>
	
</td>
<td width='12'>&nbsp;</td>
<td valign='top' width='<?=$rightWidth;?>'>
<?
  If ($showHeader) {
?>
  <table width='100%' border='0' cellpadding='0' cellspacing='0'>
  <tr>
    <td align='left'><b><font size='+1'><?=$strPageTitle;?></font></b></td>
    <td align='right'><?=$strRightOfHeaderText;?></td>
  </tr>
  <tr><td colspan='2'>&nbsp;</td></tr>
  </table>
<?
  }
}

function writeFooter() {
    echo " </TD></TR></TABLE></BODY></HTML>";
}

function writePopHeader($strPageTitle = "") {
    global $progText687;
?>
<HTML>
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<TITLE><? echo $strPageTitle; ?></TITLE>

<SCRIPT LANGUAGE="javascript">
<!--
function warn_on_submit(msg)
{
        if (!confirm(msg)) {
                alert("<?=$progText687;?>");
                return false;
        }
}

function popupWin(myLink, windowName, myWidth, myHeight) {
     var defaultWidth=300;
     var defaultHeight=175;
     var href;
     if (! window.focus) return true;
     if (typeof(myLink) == 'string') href=myLink;
     else href=myLink.href;
     if (myWidth=='') myWidth=defaultWidth;
     if (myHeight=='') myHeight=defaultHeight;
     myLeft=screen.width-(myWidth+20);
     myTop=screen.height-(myHeight+65);
     window.open(href,windowName,'width='+myWidth+',height='+myHeight+',left='+myLeft+',top='+myTop+',scrollbars=yes,dependent=yes,resizable=no');
     return false;
}
//-->
</script>

<LINK REL=StyleSheet HREF="styles.css" TYPE="text/css">
</HEAD>
<BODY bgcolor="#FFFFFF" vlink='blue' alink='blue'>
<?
}

function writePopFooter() {
    echo "</BODY></HTML>";
}

?>
