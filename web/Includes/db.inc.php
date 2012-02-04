<?php

If (!$db) {
   function dbquery($strSQL, $dieOnError = TRUE) {
       global $db;
       If ((!$queryValue = @mysql_query($strSQL, $db)) AND $dieOnError) {
           die("<p><font color='red'>Error: ".mysql_error()."<p>SQL: ".$strSQL);
       } Else {
           return $queryValue;
       }
   }

   function dbqueryWithAlert($strSQL, $adminEmail, $errorMessage) {
       global $db, $strError, $criticalTransactionError;
       If (!$queryValue = @mysql_query($strSQL, $db)) {
           mail($adminEmail, "Syslist Critical Error: ".date("m-d-Y"), ($errorMessage." \n\n ".$strSQL." \n\n ".mysql_error()));
           $strError = $errorMessage;
           $criticalTransactionError = TRUE;
       } Else {
           return $queryValue;
       }
   }

   $db = mysql_connect('localhost','syslist_devuser','h736saa');
   $dbOK = mysql_select_db('syslist_dev2',$db);
   $dbSetNames = dbquery("SET NAMES 'utf8'");

    if(!$dbOK) {
       die('Error selecting db: '.mysql_error());
    }
}

?>
