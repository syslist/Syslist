<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

  $action      = cleanFormInput(getOrPost('action'));
  $categoryID  = cleanFormInput(getOrPost('categoryID'));
  
  If ($categoryID AND ($action == 'delete') AND ($_SESSION['sessionSecurity'] < 1)) {

      $strSQL1  = "UPDATE comments SET categoryID=NULL WHERE categoryID=$categoryID AND accountID=" . $_SESSION['accountID'] . "";
      $result1  = dbquery($strSQL1);
                
      $strSQL2     = "DELETE FROM commentCategories WHERE categoryID=$categoryID AND accountID=" . $_SESSION['accountID'] . "";
      $result2     = dbquery($strSQL2);
      $strError    = $progText73; # delete successful
      $categoryID  = "";
          
  } ElseIf (getOrPost('btnSubmit') AND $categoryID) {
      $strCategory = validateText($progText1010, getOrPost('txtCategory'), 2, 28, TRUE, FALSE);
      If (!$strError) {
          $strSQL = "UPDATE commentCategories SET categoryName='$strCategory' WHERE
            categoryID=$categoryID AND accountID=" . $_SESSION['accountID'] . "";
          $result       = dbquery($strSQL);
          $strError     = $progText71; # update successful
          $strCategory  = "";
          $categoryID   = "";
      }
  } ElseIf (getOrPost('btnSubmit')) {
      $strCategory = validateText($progText1010, getOrPost('txtCategory'), 2, 28, TRUE, FALSE);
      If (!$strError) {
          $strSQL = "INSERT INTO commentCategories (categoryName, accountID)
            VALUES ('$strCategory', " . $_SESSION['accountID'] . ")";
          $result       = dbquery($strSQL);
          $strError     = $progText72; # update successful
          $strCategory  = "";
      }
  } ElseIf ($categoryID) {
      $strSQL = "SELECT categoryName FROM commentCategories WHERE accountID=" . $_SESSION['accountID'] . "
        AND categoryID=$categoryID";
      $result = dbquery($strSQL);
      While ($row = mysql_fetch_array($result)) {
          $strCategory = $row["categoryName"];
      }
      $inputText = $progText1010; # category name
  }
  
  If (!$inputText) {
      $inputText = $progText1013; # new category name
  }

  writeHeader($progText1011);
  declareError(TRUE);
  
  // write out current list of categories for editing or deleting.
  $strSQL = "SELECT categoryName, categoryID FROM commentCategories WHERE accountID=" . $_SESSION['accountID'] . "
    ORDER BY categoryName ASC";
  $result = dbquery($strSQL);
  If (mysql_num_rows($result) < 1) {
      echo $progText1012."<P>"; # no categories exist
  } Else {
      echo "<ul>\n";
      While ($row = mysql_fetch_array($result)) {
          echo "<li>".$row["categoryName"]."&nbsp;
            (<a class='action' href='commentCategories.php?categoryID=".$row["categoryID"]."'>".$progText75."</a>";
          If ($_SESSION['sessionSecurity'] < 1) {
              echo ",&nbsp; <a class='action' href='commentCategories.php?action=delete&categoryID=".$row["categoryID"]."' onClick=\"return warn_on_submit('".$progText3."');\">".$progText80."</a>";
          }
          echo ")</li>\n";
      }
      echo "</ul>\n";
  }
?>

  <form method='post' action='commentCategories.php'>
  <table border='0' cellspacing='0' cellpadding='4'>
    <tr>
      <td>
         <?=$inputText;?>:&nbsp;
         <INPUT SIZE="20" maxlength="28" TYPE="Text" NAME="txtCategory" VALUE="<? echo antiSlash($strCategory);?>">&nbsp;
      </td>
      <td>
        <input type='submit' name='btnSubmit' value='<?=$progText21;?>'>
<?
  If ($categoryID) {
      echo " &nbsp;(<a class='action' href='commentCategories.php'>".$progText417."</a>)";
  }
?>
        <input type='hidden' name='categoryID' value='<?=$categoryID;?>'>
      </td>
    </tr>
  </table>
  </form>
  
<?
  writeFooter();
?>
