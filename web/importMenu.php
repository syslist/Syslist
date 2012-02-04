<?
  Include("Includes/global.inc.php");
  checkPermissions(2, 1800);

  writeHeader($progText1050);
?>
  <a href="importUsers.php"><?=$progText1034;?></a><p>
  <a href="ldapImport.php"><?=$progText1035;?></a>
<?
  writeFooter();
?>
