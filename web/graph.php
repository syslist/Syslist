<?
    Include("Includes/generalFunctions.inc.php");
	Function Hex2Dec($hex) {
		$color = str_replace('#', '', $hex);
		$ret = array(
		  'r' => hexdec(substr($color, 0, 2)),
		  'g' => hexdec(substr($color, 2, 2)),
		  'b' => hexdec(substr($color, 4, 2))
		);
		return $ret;
	}
  
	
	
    session_start(); 

    $sess1 = getOrPost('sess1');
    $sess2 = getOrPost('sess2');
	$arySortedResult = unserialize(stripslashes(urldecode($_SESSION[$sess1])));
	$tempColorArr =  unserialize(stripslashes(urldecode($_SESSION[$sess2])));
	
	//$_SESSION['arrResult'] = urlencode(serialize($arrResult));
	//$_SESSION['arrColor'] = urlencode(serialize($arrColor));

	//$xAxis = count($arySortedResult);


	$imgWidth=300;
	$imgHeight=300;
	$numResults = getOrPost('numResults');
	$xAxis = getOrPost('xAxis');
	if ($numResults > 40) {
		$imgHeight = 8 * $numResults;
	}
	if ($numResults > 62) {
		$imgHeight = 500;
	}
	if ($xAxis > 6) {
		$imgWidth = 50 * $xAxis;
	}
	//if ($xAxis > 14) {
	//	$imgWidth = 700;
	//}

	//$gridX = $xAxis;
	//$gridY = $yAxis;


	$vertical = $imgHeight / $numResults;

	$horizontal = $imgWidth / $xAxis;

	// Create image and define colors
	$image=imagecreate($imgWidth, $imgHeight);
	$colorWhite=imagecolorallocate($image, 255, 255, 255);
	$colorGrey=imagecolorallocate($image, 192, 192, 192);
	$colorDarkBlue=imagecolorallocate($image, 104, 157, 228);
	$colorLightBlue=imagecolorallocate($image, 184, 212, 250);

	foreach ($arySortedResult as $v){
	 reset($v);  
	   while (list ($key, $val) = each ($v))
	   {
	    //assign colors
		$colorPieces = Hex2Dec($tempColorArr[$key]);
		$tempcolor[$key] = imagecolorallocate($image, $colorPieces['r'], $colorPieces['g'], $colorPieces['b']);
		}
	}

	// create grids
	if ($numResults < 40) {
		// Create grid
		for ($i=1; $i<$numResults ; $i++){
			//imageline($image, $i*$horizontal, 0, $i*$horizontal, $imgHeight, $colorGrey);
			imageline($image, 0, $i*$vertical , $imgWidth , $i*$vertical, $colorGrey);
		}
	}
	if ($xAxis < 40) {
		for ($i=1; $i<$xAxis ; $i++){
			imageline($image, $i*$horizontal, 0, $i*$horizontal, $imgHeight, $colorGrey);
			//imageline($image, 0, $i*$vertical , $imgWidth , $i*$vertical, $colorGrey);
		}
	}
	
	//create segmentation
	$x = 0;
	reset($arySortedResult);
	while (list ($key, $column) = each ($arySortedResult)){
		$y = 0;
		$lasty = 0;
		arsort($column);
		reset($column);
		while (list ($key2, $val) = each ($column)){
			$colorUsed=$tempcolor[$key2];
			//imagefilledrectangle($image, $x*$horizontal ,(($imgHeight-$vertical*$val)-$lasty), ($x+1)*$horizontal , ($imgHeight-$lasty), $colorDarkBlue);
			imagefilledrectangle($image, ($x*$horizontal )+1, (($imgHeight-$vertical*$val)-$lasty)+1, ($x+1)*$horizontal,  ($imgHeight-$lasty) , $colorUsed);
			$lasty = $vertical * $val + $lasty;
			$y++;
		}
		$x++;
	}

	// Create border around image
	imageline($image, 0, 0, 0, $imgHeight, $colorGrey);
	imageline($image, 0, 0, $imgWidth, 0, $colorGrey);
	imageline($image, $imgWidth - 1, 0, $imgWidth - 1, $imgHeight - 1, $colorGrey);
	imageline($image, 0, $imgHeight - 1, $imgWidth -1, $imgHeight -1, $colorGrey);


	header("Content-type: image/jpeg"); 
	imagepng($image);
	imagedestroy($image);
	unset($arySortedResult);
	unset($tempColorArr);
	unset($_SESSION[$sess1]);
	unset($_SESSION[$sess2]);
	
?>
