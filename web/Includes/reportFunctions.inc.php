<?
  Function updateCheckboxArray($totalAttributes, $aryCheckboxes, &$aryShow) {

  // This function checks and updates the "show" status of every attribute possible
  // on the report configuration page by taking the values of the checkboxes submitted
  // through the form.
  //
  // $totalAttributes  = constant number of potential attributes to show
  // $aryCheckboxes    = user submitted data via form for changes to the "show" column
  // $aryShow          = actual array storing conditional on/off for each attribute
  //                     (values are stored and returned to the call)

      for ($i=1; $i<=$totalAttributes; $i++) {
          if ($aryCheckboxes[$i] == 1) {
              $aryShow[$i] = 1;
          } else {
              $aryShow[$i] = 0;
          }
      }
  }

  Function upDownSubmitHandler($numAttributes, $btnUp, $btnDown, &$btnSort, &$aryShow, &$aryAttribute) {

  // This function manipulates the items of the array of attributes the user is working
  // with and is responsible for "swapping" the order of the items when the user presses
  // an 'UP' or 'DOWN' submit button
  //
  // $numAttributes    = constant number of potential attributes to show
  // $btnUp            = array containing each distinct 'UP' button to distinguish
  //                     which one was submitted
  // $btnDown          = array containing each distinct 'DOWN' button to distinguish
  //                     which one was submitted
  // $btnSort          = value of the ordered attribute selected to be the sorted column (returned)
  // $aryShow          = actual array storing conditional on/off for each attribute (returned)
  // $aryAttribute     = array containing the names of attributes with the keys
  //                     corresponding to their present order (returned)
  //
  // NOTE:  When and UP/DOWN button is submitted, keys/values in $btnSort and $aryShow
  // will need to be swapped as well to keep attribute properties together

      $i=1;
      $bolUpDown = FALSE;
      while (($i <= $numAttributes) AND ($bolUpDown == FALSE)) {
          $tempShow = $aryShow[$i];
          $tempAttribute = $aryAttribute[$i];
          if (isset($btnDown[$i])) {  # Adjust array to reflect downward move (if 'DOWN')
              $aryShow[$i] = $aryShow[$i+1];
              $aryAttribute[$i] = $aryAttribute[$i+1];
              $aryShow[$i+1] = $tempShow;
              $aryAttribute[$i+1] = $tempAttribute;
              if ($i == $btnSort) {
                  $btnSort++;
              } elseif (($i+1) == $btnSort) {
                  $btnSort--;
              }
              $bolUpDown = TRUE; # Break out of loop if action is complete
          }
          if (isset($btnUp[$i])) {  # Adjust array to reflect upward move (if 'UP')
              $aryShow[$i] = $aryShow[$i-1];
              $aryAttribute[$i] = $aryAttribute[$i-1];
              $aryShow[$i-1] = $tempShow;
              $aryAttribute[$i-1] = $tempAttribute;
              if ($i == $btnSort) {
                  $btnSort--;
              } elseif (($i-1) == $btnSort) {
                  $btnSort++;
              }
              $bolUpDown = TRUE; # Break out of loop if action is complete
          }
          $i++;
      }
  }

  Function validateSort($numAttributes, $btnSort, $aryShow) {

  // This functions makes sure that the user has not chose to sort by an attibute that
  // is not shown.  Sorted attribute must be shown.
  //
  // $numAttributes    = constant number of potential attributes to show
  // $btnSort          = value of the ordered attribute selected to be the sorted column
  // $aryShow          = actual array storing conditional on/off for each attribute

      global $progText754;

      $strError = "";
      for ($i=1; $i<=$numAttributes; $i++) {
          if (($btnSort == $i) AND ($aryShow[$i] != 1)) {
              $strError = $progText754;
          }

      }
      return $strError;
  }

  Function compressAttributeArray($numAttributes, &$numToShow, &$btnSort, $aryShow, $aryAttribute, &$aryCompAttribute) {

  // This function takes the complete array of attributes and reduces it to a smaller
  // array based on what attributes the users selects to show.  Therefore, if an attribute
  // is chosen NOT to be shown, no further operations will be performed with it.
  //
  // $numAttributes    = constant number of potential attributes to show
  // $numToShow        = the smaller sub-selection of attributes chosed to be shown (returned)
  // $btnSort          = value of the ordered attribute selected to be the sorted column (returned)
  // $aryShow          = actual array storing conditional on/off for each attribute
  // $aryAttribute     = array containing the names of attributes with the keys
  //                     corresponding to their present order (returned)
  // $aryCompAttribute = array containing the names of the attributes chosen to be shown
  //                     with the keys corresponding to their present order (returned)

      $strError = "";
      $numToShow=0;
      for ($i=1; $i<=$numAttributes; $i++) {
          if ($aryShow[$i] == 1) {
              $numToShow++;
              $aryCompAttribute[$numToShow] = $aryAttribute[$i];
              if ($btnSort == $i) {
                  $btnSortTemp = $numToShow;
              }
          }
      }
      $btnSort = $btnSortTemp; # adjust sort value with compressed array
      if ($numToShow < 1) { # check for at least one field to be shown
               $strError = $progText755;
          }
      return $strError;
  }

  Function loadLookupTable($index, $txtColumnTitle, $bolFromArray, $sqlColumn, &$aryLookupTable) {

  // This function defines the values of a lookup table to make recursive SQL queries
  // more efficient.
  //
  // $index            = sequential order of attribute (after compression)
  // $txtColumnTitle   = the 'nice looking' string representing the attribute item
  // $bolFromArray     = boolean to determine if the desired value will need to be
  //                     looked up in another array (the lookup table array) or if
  //                     the value can be extracted directly by SQL query
  // $sqlColumn        = proper 'ugly looking' column name for the attribute in the DB
  // $aryLookupTable   = a multi-dimensional array loaded by minimal SQL queries to contain
  //                     all of the information necessary for recursive lookups and matching
  //                     IDs to their respective textual strings (returned)

      $aryLookupTable[$index]['columnTitle'] = $txtColumnTitle;
      $aryLookupTable[$index]['fromArray'] = $bolFromArray;
      $aryLookupTable[$index]['sqlColumn'] = $sqlColumn;
  }

  Function sortBySecondIndex($aryResult, $btnSort) {

  // This funtion takes an array of results generated and filled by the SQL query and sorts
  // it by the 'secondary' index since this is a multi-dimensional array
  //
  // $aryResult        = multi-dim array that contains the row results of the user submitted
  //                     report storing all attributes chosen to be shown and their data
  // $btnSort          = value of the ordered attribute selected to be the sorted column

      while (list($firstIndex, ) = each($aryResult)) {
          $indexMap[$firstIndex] = $aryResult[$firstIndex][$btnSort];
      }
      asort($indexMap);
      while (list($firstIndex, ) = each($indexMap)) {
          if (is_numeric($firstIndex)) {
              $sortedArray[]= $aryResult[$firstIndex];
          } else {
              $sortedArray[$firstIndex] = $aryResult[$firstIndex];
          }
      }
      return $sortedArray;
  }

  Function generateHTMLTable($arySortedResult, $aryLookupTable, $totalColumns, $numResults) {

  // This function takes the final sorted array and produces an HTML table where the number of
  // rows corresponds to the number of results and the number of columns corresponds to the
  // number of attributes selected to be shown
  //
  // $arySortedResult  = multi-dimensional array that contains the row results of the user
  //                     submitted report after sorting
  // $aryLookupTable   = a multi-dimensional array loaded by minimal SQL queries to contain
  //                     all of the information necessary for recursive lookups and matching
  //                     IDs to their respective textual strings
  // $totalColumns     = total number of attributes to show
  // $numResults       = total number of rows or matches in the report

      global $progText710;

?>
      <table border='0' cellspacing='1' cellpadding='4'>
      <tr class='title'>
<?
      for ($i=1; $i<=$totalColumns; $i++) {  # Generate column headers
          echo "<td><b>".$aryLookupTable[$i]['columnTitle']."</b></td>\n";
      }
      echo "</tr>\n\n";
      foreach ($arySortedResult as $row) {
          echo "<tr class='".alternateRowColor()."'>";
          for ($i=1; $i<=$totalColumns; $i++) {
              echo "<td>".writeNA($row[$i])."</td>\n";
          }
          echo "</tr>\n\n";
      }
      echo "</table>\n\n";
      echo "<p></p><p><b>".$progText710.":&nbsp;".$numResults."</b></p>";
  }

  Function generateExcelFile($arySortedResult, $aryLookupTable, $numToShow) {
      global $secureAdmin;
  
  // This function takes in the sorted array of results and outputs all of the data
  // to a formatted MS Excel .xls file
  //
  // $arySortedResult  = multi-dimensional array that contains the row results of the user
  //                     submitted report after sorting
  // $aryLookupTable   = a multi-dimensional array loaded by minimal SQL queries to contain
  //                     all of the information necessary for recursive lookups and matching
  //                     IDs to their respective textual strings
  // $numToShow        = the smaller sub-selection of attributes chosed to be shown

      header("Cache-Control: private");
      header("Content-Type: application/vnd.ms-excel");
      header("Content-Disposition: attachment; filename=report.xls");
      header("Pragma: public");
      header("Expires: 0");
      #If ($secureAdmin) {
      #    header("Keep-Alive: timeout=15, max=100");
      #   header("Connection: Keep-Alive");
      #} Else {
      #    header("Pragma: no-cache");
      #}
      
      for ($i=1; $i<=$numToShow; $i++) {
          $header .= $aryLookupTable[$i]['columnTitle'] ."\t";
      }
      foreach ($arySortedResult as $row) {
          $line = '';
          for ($i=1; $i<=$numToShow; $i++) {
               $line .= writeNA($row[$i])."\t";
          }
          $line = str_replace("\t".'$', '', $line);
          $data .= trim($line)."\t \n";
      }
      echo $header."\n".$data;
  }

  Function generateTxtFile($arySortedResult, $aryLookupTable, $numToShow, $delimiter) {

  // This function takes in the sorted array of results and outputs all of the data
  // to a delimited text file
  //
  // $arySortedResult  = multi-dimensional array that contains the row results of the user
  //                     submitted report after sorting
  // $aryLookupTable   = a multi-dimensional array loaded by minimal SQL queries to contain
  //                     all of the information necessary for recursive lookups and matching
  //                     IDs to their respective textual strings
  // $numToShow        = the smaller sub-selection of attributes chosed to be shown
  // $delimiter        = character used to separate the fields (colon (:) by default)

      header("Content-Type: text/plain");
      header("Content-Disposition: attachment; filename=report.txt");
      header("Pragma: no-cache");
      header("Expires: 0");
      for ($i=1; $i<=$numToShow; $i++) {
          $header .= $aryLookupTable[$i]['columnTitle'];
          if ($i !=$numToShow) {
              $header .= $delimiter;
          }
      }
      foreach ($arySortedResult as $row) {
         $line = '';
         for ($i=1; $i<=$numToShow; $i++) {
              if ($row[$i] != "") {
                  $line .= $row[$i];
                  if ($i !=$numToShow) {
                      $line .= $delimiter;
                  }
              } else {
                  if ($i !=$numToShow) {
                      $line .= " ".$delimiter;
                  }
              }
          }
      $data .= $line."\n";
      }
      echo $header."\n".$data;
  }

  Function createExportButtons($includeGraphOption = FALSE) {

      // This function is designed to work as a footer to all report generation pages

      global $progText750, $progText751, $progText752, $progText760, $includeGraph;

      echo "<p><input type='submit' name='exportMethod' value='".$progText750."'>";
      If ($includeGraphOption) {
          echo " &nbsp;<input type='checkbox' name='includeGraph' value='1' ".writeChecked($includeGraph, "1")."> &nbsp;".$progText760."<p>";
      } Else {
          echo " &nbsp; &nbsp; ";
      }
      echo "<input type='submit' name='exportMethod' value='".$progText751."'>";
      # <!-- &nbsp; &nbsp; <input type='submit' name='exportMethod' value='".$progText752."'>-->
  }
  
  Function displayUpDownButton($index, $total) {

  // This function displays the appropriate UP/DOWN buttons for each attribute line.
  // If it's the first line, display just DOWN, if the last display just UP.
  //
  // $index          = sequential order number of the attribute
  // $total          = total amount of potential attributes (determines the LAST item)

  global $progText732, $progText733;

    if ($index == 1) {
        echo "<input type=\"submit\" class=\"smaller\" name=\"btnDown[1]\" value=\"".$progText733."\">";
    } elseif ($index == $total) {
        echo "<input type=\"submit\" class=\"smaller\" name=\"btnUp[".$total."]\" value=\"".$progText732."\">";
    } else {
        echo "<input type=\"submit\" class=\"smaller\" name=\"btnUp[".$index."]\" value=\"".$progText732."\">";
        echo "<input type=\"submit\" class=\"smaller\" name=\"btnDown[".$index."]\" value=\"".$progText733."\">";
    }
  }

  Function preloadLocationLookup() {

  // This function queries the SQL database for locations and loads the IDs as keys
  // and the full text strings as the values into a lookup array

    $strSQLx = "SELECT locationID, locationName FROM locations where accountID=" . $_SESSION['accountID'] . "";
    $resultx = dbquery($strSQLx);
    while ($rowx = mysql_fetch_array($resultx)) {
        $aryLocationLookup[$rowx['locationID']] = $rowx['locationName'];
    }
    return $aryLocationLookup;
  }

  Function preloadHardWareLocation() {

  // This function queries the SQL database for hardware locations and loads the IDs as keys
  // and the location IDs as the values into a lookup array

    $strSQLy = "SELECT hardwareID, locationID FROM hardware where accountID=" . $_SESSION['accountID'] . "";
    $resulty = dbquery($strSQLy);
    while ($rowy = mysql_fetch_array($resulty)) {
        $aryHardwareLocation[$rowy['hardwareID']] = $rowy['locationID'];
    }
    return $aryHardwareLocation;
  }

  Function preloadHardWareRoom() {

  // This function queries the SQL database for hardware rooms and loads the IDs as keys
  // and the room names as the values into a lookup array

    $strSQLy = "SELECT hardwareID, roomName FROM hardware where accountID=" . $_SESSION['accountID'] . "";
    $resulty = dbquery($strSQLy);
    while ($rowy = mysql_fetch_array($resulty)) {
        $aryHardwareRoom[$rowy['hardwareID']] = $rowy['roomName'];
    }
    return $aryHardwareRoom;
  }
  
  
  Function preloadGraphColors(){
  
  $colorArray = array(
    "DARKBLUE" => "#00008B",
    "DARKRED" => "#8B0000",
    "DARKGREEN" => "#006400",
    "YELLOW" => "#FFFF00",
    "ORANGE" => "#FFA500",
   	"LIGHTBLUE" => "#ADD8E6",
	"RED" => "#FF0000",   	
	"LIGHTGREEN" => "#90EE90",
	"PURPLE" => "#800080",	
	"LIGHTGRAY" => "#D3D3D3",
	"AQUA" => "#00FFFF",
	"BLUEVIOLET" => "#8A2BE2",
	"BURLYWOOD" => "#DEB887",
	"CADETBLUE" => "#5F9EA0",
	"CHARTREUSE" => "#7FFF00",
	"CHOCOLATE" => "#D2691E",
	"CORAL" => "#FF7F50",
	"CORNFLOWERBLUE" => "#6495ED",
	"CRIMSON" => "#DC143C",
	"CYAN" => "#00FFFF",
	"DARKORANGE" => "#FF8C00",
	"DARKCYAN" => "#008B8B",
	"DARKGOLDENROD" => "#B8860B",
	"DARKKHAKI" => "#BDB76B",
	"DARKMAGENTA" => "#8B008B",
	"DARKOLIVEGREEN" => "#556B2F",
	"DARKORCHID" => "#9932CC",
	"DARKSALMON" => "#E9967A",
	"DARKSEAGREEN" => "#8FBC8F",
	"DARKSLATEBLUE" => "#483D8B",
	"DARKSLATEGRAY" => "#2F4F4F",
	"DARKTURQUOISE" => "#00CED1",
	"DARKVIOLET" => "#9400D3",
	"DEEPPINK" => "#FF1493",
	"DEEPSKYBLUE" => "#00BFFF",
	"DIMGRAY" => "#696969",
	"DODGERBLUE" => "#1E90FF",
	"FIREBRICK" => "#B22222",
	"FORESTGREEN" => "#228B22",
	"GOLD" => "#FFD700",
	"GOLDENROD" => "#DAA520",
	"GRAY" => "#808080",
	"GREEN" => "#008000",
	"GREENYELLOW" => "#ADFF2F",
	"HOTPINK" => "#FF69B4",
	"INDIANRED" => "#CD5C5C",
	"INDIGO" => "#4B0082",
	"KHAKI" => "#F0E68C",
	"LAWNGREEN" => "#7CFC00",
	"LIGHTCORAL" => "#F08080",
	"LIGHTPINK" => "#FFB6C1",
	"LIGHTSALMON" => "#FFA07A",
	"LIGHTSEAGREEN" => "#20B2AA",
	"LIGHTSKYBLUE" => "#87CEFA",
	"LIGHTSLATEBLUE" => "#8470FF",
	"LIGHTSLATEGRAY" => "#778899",
	"LIGHTSTEELBLUE" => "#B0C4DE",
	"LIME" => "#00FF00",
	"LIMEGREEN" => "#32CD32",
	"MAGENTA" => "#FF00FF",
	"MAROON" => "#800000",
	"MEDIUMAQUAMARINE" => "#66CDAA",
	"MEDIUMBLUE" => "#0000CD",
	"MEDIUMORCHID" => "#BA55D3",
	"MEDIUMPURPLE" => "#9370D8",
	"MEDIUMSEAGREEN" => "#3CB371",
	"MEDIUMSLATEBLUE" => "#7B68EE",
	"MEDIUMSPRINGGREEN" => "#00FA9A",
	"MEDIUMTURQUOISE" => "#48D1CC",
	"MEDIUMVIOLETRED" => "#C71585",
	"MIDNIGHTBLUE" => "#191970",
	"NAVY" => "#000080",
	"OLIVE" => "#808000",
	"OLIVEDRAB" => "#6B8E23",
	"ORANGERED" => "#FF4500",
	"ORCHID" => "#DA70D6",
	"PALEGREEN" => "#98FB98",
	"PALETURQUOISE" => "#AFEEEE",
	"PALEVIOLETRED" => "#D87093",
	"PERU" => "#CD853F",
	"PLUM" => "#DDA0DD",
	"POWDERBLUE" => "#B0E0E6",
	"ROSYBROWN" => "#BC8F8F",
	"ROYALBLUE" => "#4169E1",
	"SADDLEBROWN" => "#8B4513",
	"SALMON" => "#FA8072",
	"SANDYBROWN" => "#F4A460",
	"SEAGREEN" => "#2E8B57",
	"SIENNA" => "#A0522D",
	"SKYBLUE" => "#87CEEB",
	"SLATEBLUE" => "#6A5ACD",
	"SLATEGRAY" => "#708090",
	"SPRINGGREEN" => "#00FF7F",
	"STEELBLUE" => "#4682B4",
	"TAN" => "#D2B48C",
	"TEAL" => "#008080",
	"TOMATO" => "#FF6347",
	"TURQUOISE" => "#40E0D0",
	"VIOLET" => "#EE82EE",
	"VIOLETRED" => "#D02090",
	"YELLOWGREEN" => "#9ACD32");
	  
	return $colorArray;
  }
  
	function randomString($len = 8)
	{
	$pass = '';
	$lchar = 0;
	$char = 0;
	for($i = 0; $i < $len; $i++)
		{
			while($char == $lchar)
		{
	$char = rand(48, 109);
	if($char > 57) $char += 7;
		if($char > 90) $char += 6;
		}
		$pass .= chr($char);
		$lchar = $char;
		}
		return $pass;
	}


  Function createGraph($numToShow,$aryLookupTable,$arySortedResult ,$xLabel, $zLabel, $numToShow, $numResults ){
 
	global $progText773, $progText309, $progText774, $progText775;
	//count number of records (x, y coordinates) where column matches the label
	for ($i=1; $i<=$numToShow; $i++) {  
	     if ($aryLookupTable[$i]['columnTitle'] == $xLabel) {			     
			$xColumn = $i;      
	     }
	}				
	for ($i=1; $i<=$numToShow; $i++) {  
	     if ($aryLookupTable[$i]['columnTitle'] == $zLabel ) {
			$zColumn = $i;      
	     }
	}
	$numX = 0;
					
	//count the no of Groups
	foreach ($arySortedResult as $row) {
	     if ($tempArr[$row[$xColumn]] != 1){
			$numX++;
			$tempArr[$row[$xColumn]] = 1;						
	     }
	     $arrResult[$row[$xColumn]] = "" ;       
	}
	
	//group items (segmentation) in array to create the segments and create count of itms			 
	reset ($arrResult); 
	while (list($key, $value) = each ($arrResult)) { 
		arsort($arySortedResult);
		foreach ($arySortedResult as $row) {
						
		        if ($row[$xColumn] == $key){
					$arrLoc[$row[$zColumn]]++;
					$arrResult[$row[$xColumn]] = $arrLoc;
		        }     
		}
		unset($arrLoc);
	} 
	
	//count the x axis
	$xAxis = count($arrResult);

	//load colors
	$colors = preloadGraphColors();
	
	//get colors by html color names (keys)
	$rand_color = array_keys($colors);
	
	//assign color to segmentation 
	$intColor = 0;
	foreach ($arrResult as $v){
	 reset($v);  
	   while (list ($key, $val) = each ($v))
	   {
	    //assign colors 
	    if ($arrColor[$key] == ""){

			$colorCode = $colors[$rand_color[$intColor]];
			$arrColor[$key] = $colorCode;
			$intColor++;
			}
		}
	}
		
	// x and y axis labels
	echo "$progText773 = $progText309<br>";
	echo "$progText774 = $xLabel<br>";

	// show segmentation labels
	echo "$progText775 = ".$zLabel."<br>";
	reset($arrColor);  
	while (list ($key, $val) = each ($arrColor))
	{
	   
	   echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font size=2 color=".$val."><b>".$key ."</b></font><br>";
	}
	
	//set minimum rows and columns	
	if ($numResults < 6){
		$numResults = 6;
	}
	
	if ($xAxis < 3){
		$xAxis =3;
	}
		
	$columnCount = $xAxis;
	//display column labels
	reset ($arrResult); 
	session_start(); 
	
	$randval1 = randomString();
	$randval2 = randomString();
	
	$_SESSION[$randval1] = urlencode(serialize($arrResult));
	$_SESSION[$randval2] = urlencode(serialize($arrColor));
	
	?>	
		<img src="graph.php?numResults=<?=$numResults;?>&xAxis=<?=$xAxis;?>&sess1=<?=$randval1;?>&sess2=<?=$randval2;?>">
	<table border=0 cellpadding=0 cellspacing=0 width=100% >
		<tr>
	<?
		while (list($key, $value) = each ($arrResult)) { 
		$intCount = 0;
		while (list ($key1, $val1) = each ($value))
	    {
			$intCount = $intCount + $val1;
		}

		//break labels by table column
		$label =  "";
		$boolBreak = FALSE;
		$labelPieces = explode(" ", $key); 
		foreach ($labelPieces as $value) { 
		   if (strlen($value) > 10){
			 $label = $label.wordwrap($value, 8, "<br />", 1)."<br>";
			 $boolBreak == TRUE;
		   }else{
			 $label = $label.$value."<br>";
		   }
		} 
			
		$labelCount = " (".$intCount.")";
		//if ($boolBreak == TRUE) {
		//	echo "<td valign=top style='width:".(100/$xAxis)."%; word-break: break-all;' width='". (100/$xAxis)  ."%'>";
		//}else{
			echo "<td valign=top width='". (100/$xAxis)  ."%'>";
		//}

		//echo "<td valign=top width='". (100/$xAxis)  ."%'>";
		echo "<font size=1>$label</font><font size=1 color=red>$labelCount</font>";
		echo "</td>";
		$columnCount--;
	}
		for ($i = 1; $i <= $columnCount; $i++) { 
		echo "<td valign=top width='". (100/$xAxis) ."%'>";
		echo "<font size=1>&nbsp;</font>";
		echo "</td>";
		} 
	unset($arrResult);
	unset($arrColor);
	?>
		</tr>
	</table>
	<br>
	<?
  }
  
  // ORs together all the items from a combo box we want to match in a WHERE clause
  Function listToOrString($list, $lhs, $useQuotes = FALSE) {
      $strWhereClause = "(";
      $first = 1;
      foreach ($list as $id) {
          if ($first == 1) {
              $first = 0;
          } else {
              $strWhereClause .= " OR ";
          } 
	  if ($useQuotes) {
	      $strWhereClause .= "$lhs='$id'";
	  } else {
              $strWhereClause .= "$lhs=$id";
          } 
      }
      $strWhereClause .= ") AND ";
      return $strWhereClause;
  }
  

// Simon Willison, 16th April 2003
// Based on Lars Marius Garshol's Python XMLWriter class
// See http://www.xml.com/pub/a/2003/04/09/py-xml.html
class XmlWriter_Compat {
    var $xml;
    var $indent;
    var $stack = array();
    function XmlWriter($indent = '  ') {
        $this->indent = $indent;
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>'."\n";
    }
    function _indent() {
        for ($i = 0, $j = count($this->stack); $i < $j; $i++) {
            $this->xml .= $this->indent;
        }
    }
    function push($element, $attributes = array()) {
        $this->_indent();
        $this->xml .= '<'.$element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' '.$key.'="'.htmlentities($value).'"';
        }
        $this->xml .= ">\n";
        $this->stack[] = $element;
    }
    function element($element, $content, $attributes = array()) {
        $this->_indent();
        $this->xml .= '<'.$element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' '.$key.'="'.htmlentities($value).'"';
        }
        $this->xml .= '>'.htmlentities($content).'</'.$element.'>'."\n";
    }
    function emptyelement($element, $attributes = array()) {
        $this->_indent();
        $this->xml .= '<'.$element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' '.$key.'="'.htmlentities($value).'"';
        }
        $this->xml .= " />\n";
    }
    function pop() {
        $element = array_pop($this->stack);
        $this->_indent();
        $this->xml .= "</$element>\n";
    }
    function getXml() {
        return $this->xml;
    }
}

/* Test

$xml = new XmlWriter();
$array = array(
    array('monkey', 'banana', 'Jim'),
    array('hamster', 'apples', 'Kola'),
    array('turtle', 'beans', 'Berty'),
);

$xml->push('zoo');
foreach ($array as $animal) {
    $xml->push('animal', array('species' => $animal[0]));
    $xml->element('name', $animal[2]);
    $xml->element('food', $animal[1]);
    $xml->pop();
}
$xml->pop();

print $xml->getXml();

*/  
?>
