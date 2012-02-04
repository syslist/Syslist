<SCRIPT LANGUAGE="javascript">
<!--

var openedDialog;
function confirm_on_delete(p_entityType, p_message, p_traitID, p_btn1, p_btn2, p_btn3)
{
	if (window.showModalDialog) // IE Code
	{
		var url = 'confirmOnDelete.php?entityType='+p_entityType+'&traitID='+p_traitID+'&ie=true&msg='+p_message+'&btn1='+p_btn1+'&btn2='+p_btn2+'&btn3='+p_btn3;
		var arguments = [ window, confirm_callbackFunction ];
        var width = '520px';
        var height = '200px';
        var features = 'status:no; scroll:no; dialogHeight:' + height + '; dialogWidth:' + width + '; help=no';
        window.showModalDialog(url, arguments, features);
	}
	else // Non IE Code
	{
		//avoid two instances of dialog
		if (openedDialog) 
		{
			//close opened dialog without callback
			try 
			{
				openedDialog.closeWithoutCallback();
				openedDialog = null;
			}
			catch (e) { }
		}
		var url ='confirmOnDelete.php?entityType='+p_entityType+'&traitID='+p_traitID+'&ie=false&msg='+p_message+'&btn1='+p_btn1+'&btn2='+p_btn2+'&btn3='+p_btn3;
		var args='width=520,height=175,left=325,top=300,toolbar=0,';
		args+='location=0,status=0,menubar=0,scrollbars=0,resizable=0,modal=yes';
		openedDialog = window.open(url, '_blank', args);
		openedDialog.opener = window;
		openedDialog.callbackFunction = confirm_callbackFunction;
	}
}

function confirm_callbackFunction(p_entityType, p_buttonClicked, p_traitID)
{
	openedDialog = null;

	var page;
	var traitType;
	switch (p_entityType)
	{
		case 'p': // A Peripheral type is being deleted
			page = 'admin_peripheral_types.php';
			traitType='peripheralTraitID';
			break;

		case 's': // A Software type is being deleted
			page = 'admin_software_types.php';
			traitType='softwareTraitID';
			break;
	}

	switch (p_buttonClicked)
	{
		case '1': // Delete: I never want to see this type again
			location.href = page + '?' + traitType + '=' + p_traitID + '&delete=yes&delType=1';
			break;
		
		case '2': // Delete: just a one-time database purge.
			location.href = page + '?' + traitType + '=' + p_traitID + '&delete=yes&delType=2';
			break;

		case '3': // Cancel
			break;
	}
}

function bodyOnUnload()
{
	//avoid dialog remain open on unload
	if (openedDialog) 
	{
		try
		{
			//close opened dialog (which will automatically callback)
			openedDialog.close();
			openedDialog = null;
		}
		catch (e) { }
	}
}

//-->
</script>
