<?
  Include("Includes/global.inc.php");
  checkPermissions(1, 1800);

  global $strError, $progText415A, $progText890, $progText891, $progText892, $progText893;

  $id      = cleanFormInput(getOrPost('id'));
  $target  = cleanformInput(getOrPost('target'));
  
  // Generalize SQL statements and image locations
  // so this script handles both system and user images
  // Easily extented to support pictures of anything else that has a table
  if ($target == "hw")
  {
  	$targetDir = "Systems"; // subdir of "Images" to store uploaded image
	$targetTable = "hardware"; // table to store image URL, must have picURL field
	$targetID = "hardwareID"; // key for table row
	$targetRedirect = "showfull.php?hardwareID=$id&notify=update"; // redirect here when finished
  }
  elseif ($target == "user")
  {
  	$targetDir = "Users";
  	$targetTable = "tblSecurity";
  	$targetID = "id";
  	$targetRedirect = "editUser.php?editID=$id&notify=update";
  }	
  if (getOrPost('btnSubmit'))
  {
	// Allow only JPEGs and GIFs, sizes between 0 and 500k exclusive
  	if ((($_FILES['filePic']['type'] == "image/jpeg") || ($_FILES['filePic']['type'] == "image/pjpeg") || ($_FILES['filePic']['type'] == "image/gif")) && ($_FILES['filePic']['size'] < 500000) && ($_FILES['filePic']['size'] > 0))
  	{
  		// Set GIF or JPEG
  		if ($_FILES['filePic']['type'] == "image/pjpeg" || ($_FILES['filePic']['type'] == "image/jpeg"))
  			$imgType = "jpg";
  		else
  			$imgType = "gif";
  			
  		// Generate unique server-side file names. 
  		// 50% chance of looping after 50% of 16^8 filenames are taken
  		$strNewName = "";
  		do 
  		{
  			$strNewName = substr(md5($_FILES['filePic']['name'] . $strNewName), 0, 8);
  			$strNewURL = "Images/$targetDir/" . $strNewName . ".$imgType";	
	  	} while (file_exists($strNewURL));
	  	
	  	// Move file, scale image, update database, redirect
  		if (move_uploaded_file($_FILES['filePic']['tmp_name'], $strNewURL))
  		{
  			resampleJpeg(136, 200, $strNewURL, $strNewURL, $imgType);
  			$strSQL = "SELECT picURL FROM $targetTable WHERE $targetID=$id AND accountID=" . $_SESSION['accountID'] . "";
  			$dbResult = dbquery($strSQL);
           		$aryRow = mysql_fetch_row($dbResult);
           		if (file_exists($aryRow[0])) 
           			unlink($aryRow[0]);
  			//$strNewURL = "Images/$targetDir/" . $strNewName . ".$imgType";
  			$strSQL = "UPDATE $targetTable SET picURL='$strNewURL' WHERE $targetID=$id AND accountID=" . $_SESSION['accountID'] . "";
                	dbquery($strSQL);
                	
			redirect($targetRedirect);
  		}
  		else
  			$strError = $progText890; // Error uploading file
  	}
  	else
  		$strError = $progText890; // Error uploading file
  }

  writeHeader($progText415A);
  declareError(TRUE);
  
  // Potential current picture.. URL contained in aryRow[0]
  $strSQL = "SELECT picURL FROM $targetTable WHERE $targetID=$id AND accountID=" . $_SESSION['accountID'] . "";
  $dbResult = dbQuery($strSQL);
  $aryRow = mysql_fetch_row($dbResult);
  echo "$progText893:<br>"; // Current Picture	
  if ($aryRow[0] != "")   		
  	{ ?> <img style='border: 1px solid c0c0c0;' src='<?=$aryRow[0]?>'> <? }
  else
  	{ ?> [none] <? } ?>
  	
  <form enctype="multipart/form-data" action="admin_pic.php?id=<?=$id?>&target=<?=$target?>" method="POST">
  <input type="hidden" name="MAX_FILE_SIZE" value="500000">
  <p>
  <?=$progText892?>
  <table border='0' cellpadding='2'>
    <tr>
      <td><?=$progText891;?>: &nbsp;</td>
      <td><input name="filePic" type="file" size="20"></td>
    </tr>
  </table>
  <p>
  <input type="submit" name="btnSubmit" value="<?=$progText21;?>">
  </form>
  <?
	writeFooter();
  ?>
  		
