//
//  ConfigMain.m
//  SCAConfig
//
//  Created by Karl Holland on 9/22/04.
//  Copyright Literal Technology 2004. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#include "SyslistPrefs.h"
#include "FileConfig.h"

bool IsInstall = false;

NSMutableDictionary * ArgsDict;

void ParseArgs()
{
	NSArray * Args = [[NSProcessInfo processInfo] arguments];
	ArgsDict = [NSMutableDictionary  dictionary];
	
	unsigned Index;
	unsigned ArgCount = [Args count]; 
	
	Index = 0;
	while (Index < ArgCount) {
		
		NSString * CurrItem = [Args objectAtIndex:Index];
		
		Index ++;
		
		// Search for "-" commands
		if ([CurrItem length] > 0 && [CurrItem characterAtIndex:0] == (unichar) '-') {
					
			NSMutableArray * ItemArray = [NSMutableArray array];
			
			NSString * SubItem;
			
			// Search for the next argument specifier after a '-' command
			// Also reap the arguments for the '-' command
			while (Index < ArgCount) {
				
				SubItem = [Args objectAtIndex:Index];
				
				// Handle "--" escaping and also locate the next '-' command
				if ([SubItem length] > 0 && [SubItem characterAtIndex:0] == (unichar) '-') {
				
					// If the string starts with "--" then it is an escaped "-" and the rest of the 
					// command stands for inclusion.
					if ([SubItem length] > 1 && [SubItem characterAtIndex:1] == (unichar) '-') {
						SubItem = [SubItem substringFromIndex:1];
					}
					// Otherwise we found the next "-" command
					else {
						break;
					}
				}
				
				// Push the argument on to the list for the 
				// current '-' command on to its list
				[ItemArray addObject:SubItem];
				
				Index ++;
			}			
			
			// add the argument list to the '-' command dictionary.
			[ArgsDict setObject:ItemArray forKey:[CurrItem lowercaseString]];
		}
			
	}	
}

void SetPrefsPossiblyFromFile()
{
    NSArray * fileArgList;
    
    fileArgList = [ArgsDict valueForKey:@"-config"];
    if (fileArgList == nil || [fileArgList count] == 0)
        return;
        
    NSString * configFilePath = [fileArgList objectAtIndex:0];
    FileConfig conf;
    const char * cStrPath = [configFilePath cString];
    
    if (ReadFileConf((char *) cStrPath , &conf))
        return;
        
    if (conf.AcctName[0] != '\0') {
        SyslistPrefs::setAcctUserName(conf.AcctName);
    }
    
    if (conf.AcctPwd[0] != '\0') {
        SyslistPrefs::setAcctPwd(conf.AcctPwd);
    }
    
    if (conf.AcctCode[0] != '\0') {
        SyslistPrefs::setAcctCode(conf.AcctCode);
    }
    
#ifndef SYSLIST_ASP
    if (conf.ServerURL[0] != '\0') {
        SyslistPrefs::setServerURL(conf.ServerURL);
    }
    
    if (conf.ServerPort[0] != '\0') {
        SyslistPrefs::setServerPort(atoi(conf.ServerPort));
    }
#endif

    if (conf.ScanFreq[0] != '\0') {
		enumSLFreq FreqVal = kSLFreqDisabled;
        
        if (!strcasecmp(conf.ScanFreq, "day")) {
            FreqVal = kSLFreqDay;
        }
        else if (!strcasecmp(conf.ScanFreq, "week")) {
            FreqVal = kSLFreqWeek;
        }
        else if (!strcasecmp(conf.ScanFreq, "month")) {
            FreqVal = kSLFreqMonth;
        }
        else if (!strcasecmp(conf.ScanFreq, "startup")) {
            FreqVal = kSLFreqStartup;
        }
        
        SyslistPrefs::setFrequency(FreqVal);
    }
    
    if (conf.ProxyMethod[0] != '\0') {
		enumSLProxyMode ProxyVal = kSLProxyNone;
        
        if (!strcasecmp(conf.ProxyMethod, "system")) {
            ProxyVal = kSLProxySystem;
        }
        else if (!strcasecmp(conf.ProxyMethod, "manual")) {
            ProxyVal = kSLProxyManual;
        }
        
        SyslistPrefs::setProxyMode(ProxyVal);
    }
    
    if (conf.ProxyServer[0] != '\0') {
        SyslistPrefs::setProxyAddress(conf.ProxyServer);
    }
    
    if (conf.ProxyPort[0] != '\0') {
        SyslistPrefs::setProxyPort(atoi(conf.ProxyPort));
    }
    
    SyslistPrefs::Sync();

}

void SetPrefsFromArgs()
{
	// Any time this program runs, we have to reset the failed contact variable
	// to give future attempts a chance at collection and delivery
	SyslistPrefs::setFailedContact(0);
	
	NSArray * ArgValList;
	
	ArgValList = [ArgsDict valueForKey:@"-user"];
	if (ArgValList != nil && [ArgValList count] != 0) {
		SyslistPrefs::setAcctUserName((CFStringRef) [ArgValList objectAtIndex:0]);
	}
	
	ArgValList = [ArgsDict valueForKey:@"-pwd"];
	if (ArgValList != nil && [ArgValList count] != 0) {
		SyslistPrefs::setAcctPwd((CFStringRef) [ArgValList objectAtIndex:0]);
	}
	
	ArgValList = [ArgsDict valueForKey:@"-accountid"];
	if (ArgValList != nil && [ArgValList count] != 0) {
		SyslistPrefs::setAcctCode((CFStringRef) [ArgValList objectAtIndex:0]);
	}

#ifndef SYSLIST_ASP	
	//server & port
	ArgValList = [ArgsDict valueForKey:@"-serverurl"];
	if (ArgValList != nil && [ArgValList count] != 0) {
		SyslistPrefs::setServerURL((CFStringRef) [ArgValList objectAtIndex:0]);
	}
	
	ArgValList = [ArgsDict valueForKey:@"-serverport"];
	if (ArgValList != nil && [ArgValList count] != 0) {
		SyslistPrefs::setServerPort([[ArgValList objectAtIndex:0] intValue] );
	}
#endif

	//method (frequency)
	ArgValList = [ArgsDict valueForKey:@"-frequency"];
	if (ArgValList != nil && [ArgValList count] != 0) {
		NSString * FreqArgVal = [ArgValList objectAtIndex:0];
		enumSLFreq FreqVal = kSLFreqDisabled;
		
		if ([FreqArgVal caseInsensitiveCompare:@"day"] == NSOrderedSame) {
			FreqVal = kSLFreqDay;
		}
		else if ([FreqArgVal caseInsensitiveCompare:@"week"] == NSOrderedSame) {
			FreqVal = kSLFreqWeek;
		}
		else if ([FreqArgVal caseInsensitiveCompare:@"month"] == NSOrderedSame) {
			FreqVal = kSLFreqMonth;
		}
		else if ([FreqArgVal caseInsensitiveCompare:@"startup"] == NSOrderedSame) {
			FreqVal = kSLFreqStartup;
		}

		SyslistPrefs::setFrequency(FreqVal);
	}
	
	//proxymethod (direct/system/manual)
	ArgValList = [ArgsDict valueForKey:@"-proxymethod"];
	if (ArgValList != nil && [ArgValList count] != 0) {
		NSString * ProxyArgVal = [ArgValList objectAtIndex:0];
		enumSLProxyMode ProxyVal = kSLProxyNone;
		
		if ([ProxyArgVal caseInsensitiveCompare:@"system"] == NSOrderedSame) {
			ProxyVal = kSLProxySystem;
		}
		else if ([ProxyArgVal caseInsensitiveCompare:@"manual"] == NSOrderedSame) {
			ProxyVal = kSLProxyManual;
		}
		SyslistPrefs::setProxyMode(ProxyVal);
	}
	
	//proxyserver & port
	ArgValList = [ArgsDict valueForKey:@"-proxyserver"];
	if (ArgValList != nil && [ArgValList count] != 0) {
		SyslistPrefs::setProxyAddress((CFStringRef) [ArgValList objectAtIndex:0]);
	}
	
	ArgValList = [ArgsDict valueForKey:@"-proxyport"];
	if (ArgValList != nil && [ArgValList count] != 0) {
		SyslistPrefs::setProxyPort([[ArgValList objectAtIndex:0] intValue] );
	}
	
	SyslistPrefs::Sync();
}

int main(int argc, char *argv[])
{

	NSAutoreleasePool *CmdLineReleasePool = [[NSAutoreleasePool alloc] init];
	
	ParseArgs();
	
    SetPrefsPossiblyFromFile();
    
	SetPrefsFromArgs();
	
	int AppReturn = 0;
	
	if ([ArgsDict valueForKey:@"-no_ui"] == nil)
	{
		
		if ([ArgsDict valueForKey:@"-install"] != nil)
			IsInstall = true;
		
		AppReturn = NSApplicationMain(argc,  (const char **) argv);
	}
	
	[CmdLineReleasePool release];
	
	return AppReturn; 
}