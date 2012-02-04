<?
  Include("Includes/global.inc.php");
  Include("Includes/reportFunctions.inc.php");
  checkPermissions(2, 1800);

  writeHeader($progText701);

?>
  <a href="reportSystems.php"><?=$progText702;?></a><br><br>
  <a href="reportPeripherals.php"><?=$progText703;?></a><br><br>
  <a href="reportSoftware.php"><?=$progText704;?></a><br><br>
  <a href="reportTickets.php"><?= $progText706; ?></a><br><br>
  <a href="xmlByLocation.php"><?= $progText707; ?></a><br><br>
<?
  writeFooter();
?>
