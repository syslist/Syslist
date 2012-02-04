#import <Cocoa/Cocoa.h>
#include "macutil/CFUtil.h"
#include "CoreFoundation/CFDictionary.h"
#include "BSAConfig.h"

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

int ConfigureAndShow(bool ShouldConfig, bool ShouldShow)
{
	NSString * ResPath;
	NSString * PListPath;
	NSBundle * AppBundle;
	
	AppBundle = [NSBundle mainBundle];
	ResPath = [AppBundle resourcePath];
	
	PListPath = [ResPath stringByAppendingPathComponent:@"FieldConfig.plist"];
	
	CFAuto<CFMutableDictionaryRef> ConfigProps;

	if ( [[NSFileManager defaultManager] fileExistsAtPath:PListPath] == YES) {
	
		NSData * xmlData = [NSData dataWithContentsOfFile:PListPath];
		
		CFAuto<CFPropertyListRef> RawPropList =
			CFPropertyListCreateFromXMLData(
				kCFAllocatorDefault,
				(CFDataRef) xmlData,
				kCFPropertyListMutableContainersAndLeaves,
				NULL);
		
		if (CFGetTypeID(RawPropList) == CFDictionaryGetTypeID()) {
			ConfigProps.Copy(RawPropList);
		}
	}
	
	if (ConfigProps.IsEmpty()) {
	
		ConfigProps.Attach(
			CFDictionaryCreateMutable(
				kCFAllocatorDefault,
				5,
				nil, nil));
	}
			
	NSArray * ArgValList;
	
	NSString * ReportURL = nil;
	NSNumber * ReportPort = nil;
	
	NSString * ReportPath = nil;

	if (ShouldConfig) {
		ArgValList = [ArgsDict valueForKey:@"-url"];
		if (ArgValList != nil) {
			if ( [ArgValList count] > 0 ) {
				ReportURL = [ArgValList objectAtIndex:0];
				CFDictionarySetValue(ConfigProps, kBSAKey_URL, ReportURL);
			}
			else {
				CFDictionaryRemoveValue(ConfigProps,kBSAKey_URL);
			}
		}
		
		ArgValList = [ArgsDict valueForKey:@"-port"];
		if (ArgValList != nil) {
			if ( [ArgValList count] > 0) {
				ReportPort = [NSNumber numberWithInt:[[ArgValList objectAtIndex:0] intValue]];
				CFDictionarySetValue(ConfigProps, kBSAKey_Port, ReportPort);
			}
			else {
				CFDictionaryRemoveValue(ConfigProps,kBSAKey_Port);
			}
		}
		
		ArgValList = [ArgsDict valueForKey:@"-path"];
		if (ArgValList != nil) {
			if ([ArgValList count] > 0) {
				ReportPath = [ArgValList objectAtIndex:0];
				CFDictionarySetValue(ConfigProps, kBSAKey_Path, ReportPath);
			}
			else {
				CFDictionaryRemoveValue(ConfigProps, kBSAKey_Path);
			}
		}
	
	NSData * outData = (NSData *) CFPropertyListCreateXMLData(
									kCFAllocatorDefault,
									ConfigProps);
									
	[outData writeToFile:PListPath atomically:YES];
	}
	
	if (ShouldShow || ShouldConfig) {
		
		NSString * OutString = [NSString string];
		ReportURL = (NSString *)CFDictionaryGetValue(ConfigProps ,kBSAKey_URL);
		
		if (ReportURL == nil || [ReportURL length] == 0){
			OutString = @"URL attribute is not set";
		}
		else {
			OutString = @"URL : ";
			OutString = [OutString stringByAppendingString:ReportURL];
		}
		
		fprintf (stdout,"%s\n", [OutString cString]);
		
		ReportPort = (NSNumber *) CFDictionaryGetValue(ConfigProps ,kBSAKey_Port);
		
		if (ReportPort == nil || [ReportPort intValue] == -1){
			OutString = @"Port attribute is not set (defaulted)";
		}
		else {
			OutString = @"TCP/IP Port : ";
			OutString = [OutString stringByAppendingString:[ReportPort stringValue]];
		}
		
		fprintf (stdout,"%s\n", [OutString cString]);
		
		ReportPath = (NSString *) CFDictionaryGetValue(ConfigProps ,kBSAKey_Path);
		
		if (ReportPath == nil || [ReportPath length] == 0) {
			OutString = @"Path attribute is not set";
		}
		else {
			OutString = @"Fallback Path : ";
			OutString = [OutString stringByAppendingString:ReportPath];
		}
		
		fprintf (stdout,"%s\n", [OutString cString]);
		fflush (stdout);
		
	}
		
	return 0;
}

int Execute ()
{

	NSTask * CollectorTask = nil;
	
	NSString * AppPath;
	NSString * TaskPath;
	NSBundle * AppBundle = [NSBundle mainBundle];
	
	AppPath = [AppBundle executablePath];
	TaskPath = [[AppPath stringByDeletingLastPathComponent] 
		stringByAppendingPathComponent:@"SCACollector"];
	
	if ( [[NSFileManager defaultManager] fileExistsAtPath:TaskPath] == YES) {
		CollectorTask = [NSTask launchedTaskWithLaunchPath:TaskPath arguments:[NSArray array]];
	}
	
	if (CollectorTask == nil) {
		//NSRunAlertPanel(@"Unable to launch the collector",
		//@"The application was unable to process this machine because it could not launch the collection applications",
		//@"OK",@"test",@"test");
		return 2;
	}
	
	[CollectorTask waitUntilExit];
	
	return [CollectorTask terminationStatus];

}

int main(int argc, char *argv[])
{
	
	NSAutoreleasePool * pool = [[NSAutoreleasePool alloc] init];

	ParseArgs();
	
	NSArray * ArgValList;

	bool ShouldShow = false;
	bool ShouldConfig = false;
	
	ArgValList = [ArgsDict valueForKey:@"-config"];
	if (ArgValList != nil)
		ShouldConfig = true;
	
	ArgValList = [ArgsDict valueForKey:@"-show"];
	if (ArgValList != nil)
		ShouldShow = true;
		
	int RetVal;
	
	if (ShouldConfig or ShouldShow ) {
		RetVal = ConfigureAndShow(ShouldConfig, ShouldShow);
	}
	else {
		RetVal = Execute();
	}
		
	[pool release];
	
	return RetVal;
	
    //return NSApplicationMain(argc,  (const char **) argv);
}
