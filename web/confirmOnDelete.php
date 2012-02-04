<?
    Include("Includes/global.inc.php");
    
    $ie          = getOrPost('ie');
	$entityType  = getOrPost('entityType');
	$traitID     = getOrPost('traitID');
?>
<HTML>
<HEAD>
<script language="javascript">
<?
	if ($ie == 'true') {
?>
    window.opener = window.dialogArguments[0];
   	window.callbackFunction = window.dialogArguments[1];
<?
    }
?>
	var buttonClicked = false;
	var disableCallback = false;
	
	function button1OnClick()
	{
		buttonClicked = true;
		callback('1');
	}

	function button2OnClick()
	{
		buttonClicked = true;
		callback('2');
	}

	function button3OnClick()
	{
		buttonClicked = true;
		callback('3');
	}

	function callback(p_buttonClicked)
	{
		try
		{
			window.callbackFunction('<?=$entityType?>', p_buttonClicked, '<?=$traitID?>');
			window.close();
		}
		catch (e) { }
	}

	function closeWithoutCallback()
	{
		try
		{
			disableCallback = true;
			window.close();
		}
		catch (e) { }		
	}

	function bodyOnUnload()
	{
		if (buttonClicked == false && disableCallback == false)
		{
			//default callback on unload
			callback('3');
		}
	}

</script>    
</HEAD>
<? if ($ie == 'true') { ?>
<BODY onunload="bodyOnUnload();">
<? } else { ?>
<BODY onunload="bodyOnUnload();" onblur="window.focus();">
<? } 
$msg   = getOrPost('msg');
$btn1  = getOrPost('btn1');
$btn2  = getOrPost('btn2');
$btn3  = getOrPost('btn3');
?>
<table border='0' cellpadding='5' cellspacing='0'><tr><td align='left'><?=$msg?></td>
</tr><tr>
<td align='center'><input type="button" onclick="button1OnClick();" style="width:440px" value=" <?=$btn1?> "/><br>
<input type="button" onclick="button2OnClick();" style="width:440px" value=" <?=$btn2?> "/><br>
<input type="button" onclick="button3OnClick();" style="width:440px" value=" * <?=$btn3?> * "/></td>
</tr></table>
</BODY>
</HTML>
