#import "ConfigUIController.h"
#include "SyslistPrefs.h"
#include "SyslistVersion.h"

#include <iostream>
#include <sstream>
#include <sys/types.h>
#include <sys/wait.h>

extern bool IsInstall;

@implementation ConfigUIController

using namespace std;

- (IBAction)btnCancelPushed:(id)sender
{
	if (IsInstall) {
		NSRunAlertPanel(
			@"Setup Incomplete",
			@"Setup is Incomplete. You might not recieve future updates from this machine. Run SCAConfig again to configure.",
			@"OK", nil, nil);
#if 0
		NSBeginAlertSheet(
			@"Setup Incomplete",
			@"OK", nil, nil,
			[NSApp mainWindow],
			nil, nil, nil, nil,
			@"Setup is Incomplete. You might not recieve future updates from this machine. Run SCAConfig again to configure.");
#endif
	}
	
	[NSApp terminate:self];
}

- (IBAction)btnInventoryPushed:(id)sender
{
	[self fillPrefsFromUI:true];
	[self ExecuteCollector];
	[self fillUIFromPrefs:true];	
}

- (IBAction)btnOKPushed:(id)sender
{
	long CollectStatus = 0;
	
	[self fillPrefsFromUI:true];
	
	if (IsInstall) {

		CollectStatus = [self ExecuteCollector];
		[self fillUIFromPrefs:false];
		if (CollectStatus == 0) {
		
			NSRunAlertPanel(
				@"Installation Complete",
				@"Installation is complete, a Web Browser showing the results will be displayed shortly",
				@"OK", nil, nil);

#if 0		
			NSBeginInformationalAlertSheet(
				@"Installation Complete",
				@"OK", nil, nil,
				[NSApp mainWindow],
				nil, nil, nil, nil,
				@"Installation is complete, a Web Browser showing the results will be displayed shortly");
#endif				
			long BrowserStatus = [self showBrowser];
			
			if (BrowserStatus != 0) {
				NSRunAlertPanel(
					@"Unable To Run Browser",
					@"The Configuration program was unable to show the browser",
					@"OK", nil, nil);
			}
		}
	}
	
	if (IsInstall == false || CollectStatus == 0)
		[NSApp terminate:self];
}

- (IBAction)popProxyChanged:(id)sender
{
	[self setDependentUIItems];
}

- (long) ExecuteCollector
{
	NSString * CollectorDir;
	NSBundle * CurrBundle = [NSBundle mainBundle];
	CollectorDir = [[CurrBundle bundlePath] stringByDeletingLastPathComponent];
	
	NSString * CollectorPath = [CollectorDir stringByAppendingPathComponent:@"SCACollector"];
	
	NSTask * CollectorTask = nil;
	
	if ( [[NSFileManager defaultManager] isExecutableFileAtPath:CollectorPath]) {
		CollectorTask = [NSTask launchedTaskWithLaunchPath:
					CollectorPath 
					arguments:[NSArray arrayWithObject:CollectorPath]];
	}
				
	int ReturnVal = 0;
	NSString * ErrMsg = nil;
	
	// Check to see if it was run
	if (CollectorTask == nil) {
		ErrMsg = @"The Collector could not be run. Please check installation and try again";
		ReturnVal = -1;
	}
	// Success ! now wait and process the result
	else {
	
		[CollectorTask waitUntilExit];
		ReturnVal = [CollectorTask terminationStatus];
		
#if 0
		pid_t CollectorPID = [CollectorTask processIdentifier];
		pid_t WaitReturn;
		
		do {
				WaitReturn = waitpid(CollectorPID, &ReturnVal, 0);
				if (WaitReturn == -1) {
					if (errno != EAGAIN && errno != EINTR)
						break;
				}
				
				if (WaitReturn == CollectorPID)
					break;
		}
		while (1);		
		
		if (WaitReturn == -1) {
			ErrMsg = @"Unable to determine the success of the Agent";
			
			// reroute the return value for higher level callers to understand what happened.
			ReturnVal = -1;
		}
			
		else if (WIFSIGNALED(ReturnVal)) {
			ErrMsg = [NSString 
						stringWithFormat:@"The Agent Did not Complete Collecting Due to an internal error while doing inventory (%d). Please Contact Syslist Support at Support@Syslist.com", 
						WTERMSIG(ReturnVal)];
			
			// reroute the return value for higher level callers to understand what happened.
			ReturnVal = -1;
			
		}
		else {
#endif
		
			//switch (WEXITSTATUS(ReturnVal)) {
			switch(ReturnVal & 0xFF) {
			case 0: // No Error
				break;
			case -8:
			case 248:
				//ErrMsgID = IDS_ERR_COLLECT_NO_ID;
				ErrMsg = @"The Agent was unable to get an ID from the Server";
				break;
			case -7:
			case 249:			
				//ErrMsgID = IDS_ERR_COLLECT_DEMO_EXPIRED;
				ErrMsg = @"Your Demo period has expired. Please contact sales support";
				break;
			case -6:
			case 250:
				//ErrMsgID = IDS_ERR_COLLECT_OPEN_URI;
				ErrMsg = @"The Agent could not contact the server at the specified URL";
				break;
			case -5:
			case 251:
				//ErrMsgID = IDS_ERR_COLLECT_READ_REG;
				ErrMsg = @"The Agent was unable to read settings.";
				break;
			case -4:
			case 252:
				//ErrMsgID = IDS_ERR_COLLECT_FAIL;
				ErrMsg = @"The Agent was unable to inventory this machine. Please check account permissions";
				break;
			case -3:
			case 253:
				//ErrMsgID = IDS_ERR_COLLECT_PROTO;
				ErrMsg = @"The specified protocol in the URL is not recognized";
				break; 
			case -2:
			case 254:
				//ErrMsgID = IDS_ERR_COLLECT_WRITE_ID;
				ErrMsg = @"The Agent was unable to store the ID returned by the Server";
				break;
			case 1:
				//ErrMsgID = IDS_ERR_SYSLIST_REJECT;
				ErrMsg = @"The Syslist Server has indicated that the ID stored on this machine is Invalid";
				break;
			case 2:
				//ErrMsgID = IDS_ERR_SYSLIST_AUTH;
				ErrMsg = @"The Syslist Server did not recognize the Username and Password supplied";
				break;
			case 3:
				//ErrMsgID = IDS_ERR_SYSLIST_INCOMPLETE_XML;
				ErrMsg = @"The Syslist Server did not recieve a complete inventory record from this machine";
				break;
			case 4:
				//ErrMsgID = IDS_ERR_SYSLIST_NO_INFO;
				ErrMsg = @"The Syslist Server did not recieve enough information to uniquely identify this machine";
				break;
			case 9:
				//ErrMsgID = IDS_ERR_SYSLIST_LIC_INVALID;
				ErrMsg = @"The Syslist Server has inidicate that your license is invalid";
				break;
			case 10:
				//ErrMsgID = IDS_ERR_SYSLIST_LIC_MAXED;
				ErrMsg = @"The Syslist Server has indicated that you have exceeded the maximum number of machines for your license";
				break;
			case 64:
			case 65:
				//These are from caught signals - If that code is turned on
				ErrMsg = [ NSString stringWithFormat:@"The Agent Did not Complete Collecting Due to an internal error while doing inventory (%d). Please Contact Syslist Support at Support@Syslist.com", 
						(ReturnVal & 0xFF) - 64 ];
				break;
			case 404:
			case 148: // cause the return values are only 8 bit here
				//ErrMsgID = IDS_ERR_SYSLIST_NO_AGENT_PAGE;
				ErrMsg = @"The Agent was unable to locate the reporting page on the server";
				break;
			default:
				//ErrMsgID = IDS_ERR_SYSLIST_COMM;
				ErrMsg = @"The Agent encountered an error while communicating with the server";
				break;
			}
		}
#if 0
	}
#endif 
	
	if (ErrMsg != nil) {

		NSRunAlertPanel(
			@"Inventory Error",
			ErrMsg,
			@"OK", nil, nil);

#if 0		
		NSBeginAlertSheet(
			@"Inventory Error",
			@"OK", nil, nil,
			[NSApp mainWindow],
			nil, nil, nil, nil,
			ErrMsg);
#endif
	}
	
	return ReturnVal;	
}

- (BOOL) applicationShouldTerminateAfterLastWindowClosed:(NSApplication *)theApplication
{
	return TRUE;
}

- (void) awakeFromNib
{
    [[btnOK window] makeKeyAndOrderFront:self];
	[lblVersionText setStringValue: nsstrSyslistVersion];
	
#ifndef SYSLIST_ASP
	[lblSyslistCode setTextColor:[NSColor disabledControlTextColor]];
	[txtSyslistCode setTextColor:[NSColor disabledControlTextColor]];
	[txtSyslistCode setBackgroundColor:[NSColor windowBackgroundColor]];
	[txtSyslistCode setEditable:false];
#endif

	[self fillUIFromPrefs:false];
	
	if (IsInstall) {
		[NSApp activateIgnoringOtherApps:YES];
		[btnInventory setEnabled:false];
		[btnInventory setBordered:false];
		[btnInventory setTitle:@"---"];
	}

}

void URLEncode (string& TargetString)
{
	long TargetLen = TargetString.length();
	
	char Dest[TargetLen * 3];
	
	long CurrLoc;
	char * TransLoc;
	 
	const char * Source = TargetString.c_str();
	
	for (CurrLoc = 0, TransLoc = Dest;
		 Source[CurrLoc] != L'\0'; 
		 CurrLoc ++) {

		switch (Source[CurrLoc]) {
		case L' ':
			*(TransLoc++) = L'+';
			break;
		case L'&':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'2';
			*(TransLoc++) = L'6';
			break;
		case '\t':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'0';
			*(TransLoc++) = L'9';
			break;
		case '\n':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'0';
			*(TransLoc++) = L'A';
			break;
		case '/':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'2';
			*(TransLoc++) = L'F';
			break;
		case '~':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'7';
			*(TransLoc++) = L'E';
			break;
		case ':':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'3';
			*(TransLoc++) = L'A';
			break;
		case ';':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'3';
			*(TransLoc++) = L'B';
			break;
		case '@':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'4';
			*(TransLoc++) = L'0';
			break;
		default:
			*(TransLoc++) = Source[CurrLoc];
			break;
		 }
	}
	
	*TransLoc = L'\0';

	TargetString = Dest;
}

- (long) showBrowser
{
	NSString * CurlPath = @"/usr/bin/curl";
	std::ostringstream PostStream;
	NSTask * CurlTask;
	
	std::string HardwareIDVal;
	std::string UserNameVal;
	std::string PasswordVal;
	std::string AcctCodeVal;
	
	SyslistPrefs::getMachID(HardwareIDVal);
	SyslistPrefs::getAcctUserName(UserNameVal);
	SyslistPrefs::getAcctPwd(PasswordVal);
	SyslistPrefs::getAcctCode(AcctCodeVal);
	
	URLEncode (HardwareIDVal);
	URLEncode (UserNameVal);
	URLEncode (PasswordVal);
	URLEncode (AcctCodeVal);
	
	//[[NSWorkspace sharedWorkspace] openURL:[NSURL URLWithString:@"https://www.syslist.com"]];
	
	std::ostringstream PostAddrStream;
	std::string PostAddr;
	long PostPort;
	
	SyslistPrefs::getServerURL(PostAddr);
	SyslistPrefs::getServerPort(PostPort);
	PostAddrStream << PostAddr << "/" << "login.php";
	if (PostPort != kSLDefServerPort)
		PostAddrStream << ":" << PostPort;
	
	NSString * BrowserFileDir;
	BrowserFileDir = [[[NSBundle mainBundle] bundlePath] stringByDeletingLastPathComponent];
	NSString * BrowserFilePath = [BrowserFileDir stringByAppendingPathComponent:@"SCAInstallInfo.html"];

	[[NSFileManager defaultManager] removeFileAtPath:BrowserFilePath handler:nil];
	
	PostStream 
		<< "hardwareID=" << HardwareIDVal << "&"
		<< "txtUserName=" << UserNameVal << "&"
		<< "txtPassword=" << PasswordVal << "&"
		<< "txtAcctCode=" << AcctCodeVal << "&"
		<< "btnSubmit=1&strRedir=showfull.php";
	
	//NSArray* ArrayArgs = [NSArray arrayWithObjects: nil]
	CurlTask = [NSTask launchedTaskWithLaunchPath:
					CurlPath 
					arguments:[NSArray arrayWithObjects:
						@"-o",
						BrowserFilePath,						
						@"-k",
						@"-L",
						@"-H",
						//@"Content-Type: application/x-www-form-urlencoded",
						//@"--include",
						@"-s",
						@"-d",
						[NSString stringWithCString:PostStream.str().c_str()],
						[NSString stringWithCString:PostAddrStream.str().c_str()],
						nil]];
	
	[CurlTask waitUntilExit];
	
	int CurlStatus = [CurlTask terminationStatus];
	
	if (CurlStatus != 0)
		return CurlStatus;
		
	bool LaunchStatus = 
		[[NSWorkspace sharedWorkspace] openFile: BrowserFilePath];
		
	if (LaunchStatus != true)
		return 1;
		
	return 0;	
}

- (void) fillPrefsFromUI:(BOOL) resync
{	
	SyslistPrefs::setServerURL((CFStringRef)[txtSyslistServer stringValue]);
	
	long LongPrefVal;
	
	LongPrefVal = kSLDefServerPort;
	if ([[txtServerPort stringValue] length] > 0 && [txtServerPort intValue] > 0)
		LongPrefVal = [txtServerPort intValue];
		
	SyslistPrefs::setServerPort(LongPrefVal);
	
	SyslistPrefs::setProxyMode((enumSLProxyMode) [popProxyMode indexOfSelectedItem]);
	
	SyslistPrefs::setProxyAddress((CFStringRef)[txtProxyServer stringValue]);
	
	LongPrefVal = kSLDefProxyPort;
	if ([[txtProxyPort stringValue] length] > 0 && [txtProxyPort intValue] > 0)
		LongPrefVal = [txtProxyPort intValue];
		
	SyslistPrefs::setProxyPort(LongPrefVal);

	SyslistPrefs::setFrequency((enumSLFreq) [popFrequency indexOfSelectedItem]);

	SyslistPrefs::setAcctUserName((CFStringRef) [txtSyslistUser stringValue]);
	
	SyslistPrefs::setAcctPwd((CFStringRef) [txtSyslistPwd stringValue]);
	
	SyslistPrefs::setAcctCode((CFStringRef) [txtSyslistCode stringValue]);
	
	if (resync)
		SyslistPrefs::Sync();
}

- (void) fillUIFromPrefs:(BOOL) resync
{
	if (resync)
		SyslistPrefs::Sync();
	
	NSString * PrefStringVal;
	long PrefLongVal;
	
	PrefStringVal = (NSString *) SyslistPrefs::getServerURL();
	[txtSyslistServer setStringValue: PrefStringVal];
	
	SyslistPrefs::getServerPort(PrefLongVal);
	if (PrefLongVal != kSLDefServerPort)
		[txtServerPort setIntValue:PrefLongVal];
	else
		[txtServerPort setStringValue:@""];

	enumSLProxyMode ProxyModeSetting;
	SyslistPrefs::getProxyMode(ProxyModeSetting);
	[popProxyMode selectItemAtIndex:(long)ProxyModeSetting];
	//[cmbProxyMode setObjectValue:[cmbProxyMode objectValueOfSelectedItem]];
			
	PrefStringVal = (NSString *) SyslistPrefs::getProxyAddress();
	[txtProxyServer setStringValue: PrefStringVal];

	SyslistPrefs::getProxyPort(PrefLongVal);
	if (PrefLongVal != kSLDefProxyPort)
		[txtProxyPort setIntValue:PrefLongVal];
	else
		[txtProxyPort setStringValue:@""];
	
	enumSLFreq FreqSetting;
	SyslistPrefs::getFrequency(FreqSetting);
	[popFrequency selectItemAtIndex:(long)FreqSetting];
	//[cmbFrequency setObjectValue:[cmbFrequency objectValueOfSelectedItem]];
	
	PrefStringVal = (NSString *) SyslistPrefs::getAcctUserName();
	[txtSyslistUser setStringValue:PrefStringVal];
	
	PrefStringVal = (NSString *) SyslistPrefs::getAcctPwd();
	[txtSyslistPwd setStringValue:PrefStringVal];
	
	PrefStringVal = (NSString *) SyslistPrefs::getAcctCode();
	[txtSyslistCode setStringValue:PrefStringVal];
	
	PrefStringVal = (NSString *) SyslistPrefs::getMachID();
	if ([PrefStringVal length] > 0)
		[txtPCStatus setStringValue:@"Identified"];
	else
		[txtPCStatus setStringValue:@"Not Identified"];
		
	[self setDependentUIItems];

}

- (BOOL) canExecuteOK
{	
	if ( (enumSLProxyMode) [popProxyMode indexOfSelectedItem] == kSLProxyManual
		&& [[txtProxyServer stringValue] length] == 0) {
		return false;
	}
	
#ifdef SYSLIST_ASP
	if ([[txtSyslistCode stringValue] length] == 0)
		return false;
#endif

	if ([[txtSyslistServer stringValue] length] == 0
	    || [[txtSyslistPwd stringValue] length]== 0
		|| [[txtSyslistUser stringValue] length]== 0) {
		return false;
	}
	
	return true;
}

- (void) setDependentUIItems
{
	enumSLProxyMode ProxySetting = (enumSLProxyMode) [popProxyMode indexOfSelectedItem];
	
	BOOL NeedProxy;
	NSColor * FGStateColor;
	NSColor * BGStateColor;

	if (ProxySetting != kSLProxyManual){ 
		NeedProxy = false;
		BGStateColor = [NSColor windowBackgroundColor];
		FGStateColor = [NSColor disabledControlTextColor];
	}
	else {
		NeedProxy = true;
		BGStateColor = [NSColor controlBackgroundColor];
		FGStateColor = [NSColor controlTextColor];
	}
	
	[lblProxyServer setTextColor:FGStateColor];
	
	[txtProxyServer setEditable:NeedProxy];
	[txtProxyServer setBackgroundColor:BGStateColor];
	[txtProxyServer setTextColor:FGStateColor];
	
	[lblProxyPort setTextColor:FGStateColor];
	
	[txtProxyPort setEditable:NeedProxy];
	[txtProxyPort setBackgroundColor:BGStateColor];
	[txtProxyPort setTextColor:FGStateColor];
	
	[btnOK setEnabled:[self canExecuteOK]];
}

- (void)controlTextDidChange:(NSNotification *)aNotification
{
	[self setDependentUIItems];
}
@end
