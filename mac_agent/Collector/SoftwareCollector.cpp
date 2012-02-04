#include "SoftwareCollector.h"
#include "CFUtil.h"

#include <sstream>
#include <dirent.h>
#include <set>
#include <sys/types.h>
#include <sys/stat.h>

#include <ApplicationServices/ApplicationServices.h>

static const char* SWCL_TAG = "SoftwareList";
static const char* SWC_TAG = "Program";

static const char * SWCL_COUNT = "Count";

static const char * SWC_APPNAME = "ApplicationName";
static const char * SWC_DISPNAME = "DisplayName";
static const char * SWC_DISPVERSION = "DisplayVersion";
static const char * SWC_PUBLISHER = "Publisher";
//static const char * SWC_VERS_MAJOR = "VersionMajor";
//static const char * SWC_VERS_MINOR = "VersionMinor";

static const long MAX_APP_SCAN_DEPTH = 2;

static const long MAX_PKG_SCAN_DEPTH = 0;

typedef bool (* SWFilterFunc_t) ();

set<std::string> FilterAppsDirSet;

static const char * FilterAppsDirList[] = {
	"Activity Monitor",
	"Address Book",
	"AirPort Admin Utility",
	"AirPort Setup Assistant",
	"Audio MIDI Setup",
	"Bluetooth File Exchange",
	"Bluetooth Serial Utility",
	"Bluetooth Setup Assistant",
	"BSD",
	"Calculator",
	"Chess",
	"ColorSync Utility",
	"Console",
	"Dictionary",
	"DigitalColor Meter",
	"Directory Access",
	"Disk Utility",
	"DVD Player",
	"Explorer",
	"Internet Explorer",
	"Folder Actions Setup",
	"Font Book",
	"Grab",
	"Hard Drive Update 1",
	"Hard_Disk_Update_1",
	"HexEdit",
	"iCal",
	"iChat",
	"Image Capture",
	"Install Script Menu",
	"Installer",
	"Internet Explorer",
	"Internet Connect",
	"iSync",
	"iTunes",
	"Java 1.4.2",
	"Java 1.4.2 Update 2",
	"Keychain Access",
	"Mail",
	"NetInfo Manager",
	"Network Utility",
	"ODBC Administrator",
	"PowerMacSuperDriveUpdt",
	"Preview",
	"Printer Setup Utility",
	"QuickTime Player",
	"Remove Script Menu",
	"Safari",
	"Script Editor",
	"Sherlock",
	"Stickies",
	"System Preferences",
	"System Profiler",
	"Terminal",
	"TextEdit",
	"X11",
	NULL,
};

void InitFilterList() {
	const char ** CurrItem = NULL;
	
	for (CurrItem = FilterAppsDirList; *CurrItem != NULL; CurrItem ++) {
		FilterAppsDirSet.insert (string(*CurrItem));
	}	
}

bool AppsDirFilter(NVDataItem * TestItem)
{	
	const char * NameVal = false;
	
	TestItem->GetValueByName(SWC_DISPNAME , &NameVal);
	
	if (NameVal == NULL)
		return false;
		
	std::set<std::string>::iterator FoundItem = FilterAppsDirSet.find(NameVal);
	
	if (FoundItem != FilterAppsDirSet.end())
		return false;
	
	return true;
}

bool PKGDirFilter (NVDataItem * TestItem)
{
	const char * NameVal = NULL;
	
	if (AppsDirFilter (TestItem) == false)
		return false;
		
	TestItem->GetValueByName(SWC_PUBLISHER , &NameVal);
	
	if (NameVal == NULL)
		return true;
	
	if (strncasecmp( NameVal, "com.apple.macosx.lang", 21) == 0)
		return false;
		
	return true;
	
}

void SoftwareCollector::ExtractSWDictItems(
	std::string VisibleAppName,
	NVDataItem * SWReportItem,
	CFDictionaryRef SWItemDict,
	const char * PkgNameDest,
	const char * FileNameDest)
{
				
	CFAuto<CFStringRef> AppCFName;
	
	Boolean ValPresent;
	ValPresent = CFDictionaryGetValueIfPresent(SWItemDict, kCFBundleNameKey, (const void **) & AppCFName);
	
	if (ValPresent && CFGetTypeID(AppCFName) == CFStringGetTypeID()) {
		CFRetain (AppCFName);
	
		std::string AppName;
		AppCFName.GetCString (AppName);
		
		SWReportItem->AddNVItem(PkgNameDest, AppName.c_str());
	}
	else {
		SWReportItem->AddNVItem(PkgNameDest, VisibleAppName.c_str());
	}
	
	SWReportItem->AddNVItem(FileNameDest, VisibleAppName.c_str());
	
	CFAuto<CFStringRef> AppCFVersion;
	ValPresent = CFDictionaryGetValueIfPresent(SWItemDict, kCFBundleVersionKey, (const void **) & AppCFVersion);
	
	if (ValPresent && CFGetTypeID(AppCFVersion) == CFStringGetTypeID()) {
		CFRetain (AppCFVersion);
	
		std::string AppVersion;
		AppCFVersion.GetCString (AppVersion);
		
		SWReportItem->AddNVItem (SWC_DISPVERSION, AppVersion.c_str());
	}
	else {
		AppCFVersion.Detach(); // just in case we had a value that is not a string
		ValPresent = CFDictionaryGetValueIfPresent(SWItemDict, CFSTR("CFBundleShortVersionString"), (const void **) & AppCFVersion);
		
		if (ValPresent && CFGetTypeID(AppCFVersion) == CFStringGetTypeID()) {
			CFRetain (AppCFVersion);
		
			std::string AppVersion;
			AppCFVersion.GetCString (AppVersion);
			
			SWReportItem->AddNVItem (SWC_DISPVERSION, AppVersion.c_str());
		}
	}
	
	CFAuto<CFStringRef> AppCFVendorID;
	ValPresent = CFDictionaryGetValueIfPresent(SWItemDict, kCFBundleIdentifierKey, (const void **) & AppCFVendorID);
	
	if (ValPresent && CFGetTypeID(AppCFVendorID) == CFStringGetTypeID()) {
		CFRetain (AppCFVendorID);
	
		std::string AppVendorID;
		AppCFVendorID.GetCString (AppVendorID);
		
		SWReportItem->AddNVItem (SWC_PUBLISHER, AppVendorID.c_str());
	}
}

long SoftwareCollector::AddSoftwareItemsFromDir (
	std::string SourceDirectory, std::string Type, 
	SWFilterFunc_t FilterFunc,
	const char * PkgNameDest, const char * FileNameDest, 
	long MaxDepth, long & TotalCount, NVDataItem * Dest, 
	long CurrDepth)
{
	DIR * SWDirStream = NULL;
	struct dirent *SWDirEnt = NULL;
	
	SWDirStream = opendir (SourceDirectory.c_str());
	if (SWDirStream == NULL)
		return ERROR_SUCCESS;

	while ( SWDirEnt = readdir (SWDirStream), SWDirEnt != NULL) {
			
		// Dont follow hierarchy entries
		if (strcmp (".", SWDirEnt->d_name) == 0 
			|| strcmp ("..", SWDirEnt->d_name) == 0)
			continue;
	
		std::string EntryName;
		EntryName.assign (SWDirEnt->d_name, SWDirEnt->d_namlen);
        
		std::string EntryPath = SourceDirectory;
		EntryPath.append("/");
		EntryPath.append(EntryName);

#ifdef TEST_CONVERSION
        FSRef testRef;
        
        OSStatus testStatus = FSPathMakeRef((const UInt8 *) EntryPath.c_str(), 
                                &testRef, NULL);
        
        if (testStatus == noErr) {
            
            HFSUniStr255 retStr;
            OSErr testErr;
            
            testErr = FSGetCatalogInfo(
                & testRef,
                kFSCatInfoNone, NULL,
                &retStr,
                NULL, NULL);
            if (testErr == noErr) {
                int x = 5;
                x++;
            }
        }
#endif
        
		CFAuto<CFURLRef> SWItemURL;
					
		SWItemURL.Attach (
			CFURLCreateFromFileSystemRepresentation(
				kCFAllocatorDefault,
				(const UInt8 *) EntryPath.c_str(),
				EntryPath.length(),
				false
			)
		);
					
		if (SWItemURL.IsEmpty() || CFGetTypeID(SWItemURL) != CFURLGetTypeID())
			continue;

		LSItemInfoRecord SystemFileInfo = {0};
		
		LSCopyItemInfoForURL(SWItemURL, kLSRequestAllFlags, &SystemFileInfo);
		long FileFlags = SystemFileInfo.flags;
		
		if ((FileFlags & kLSItemInfoIsApplication) == 0
			&& (FileFlags & kLSItemInfoIsPackage) ==0) {
		
			if (SWDirEnt->d_type == DT_DIR) {
			
				// These are Plain directories: Recurse
				if (CurrDepth < MaxDepth) {
					AddSoftwareItemsFromDir (
						EntryPath, Type, 
						FilterFunc,
						PkgNameDest, FileNameDest,
						MaxDepth, TotalCount, Dest, 
						CurrDepth + 1);
				}
			}

			continue;
		}
				
		
		// length of the bundle extension we are looking for (Type Argument)
		long ExtLen = Type.length();

		// Check to see if this matches the bundle type
		if (ExtLen > 0 
			&&
			(ExtLen > SWDirEnt->d_namlen
				||  strcasecmp( Type.c_str(), & (SWDirEnt->d_name[SWDirEnt->d_namlen - ExtLen])) != 0))
		{
			// suffix specified and item did not match: go to next item.
			continue;
		}

		auto_ptr<NVDataItem> SWReportItem (new NVDataItem(SWC_TAG));
		CFAuto<CFDictionaryRef> SWItemDict;

		std::string VisibleAppName;
		VisibleAppName.assign (SWDirEnt->d_name, SWDirEnt->d_namlen - ExtLen);
		
		if (ExtLen == 0) {
			if (strcasecmp(VisibleAppName.c_str() + VisibleAppName.length() - 4 ,".app") == 0) {
			
				VisibleAppName.erase (VisibleAppName.length() - 4, 4);
			}
		}

		if ((FileFlags & kLSItemInfoIsPackage) == 0) 
		{
			//SWReportItem->AddNVItem(SWC_APPNAME, SWDirEnt->d_name);
			
			short ResFile;
			FSRef FileRef;
			
			OSStatus Status;

			// Should be able to get this with the FSRef Iterators
			// instead of back and forth with UNIX, CF, and FS
			Status = FSPathMakeRef( (const UInt8 *) EntryPath.c_str(), &FileRef, NULL);
			
			if (Status != 0)
				continue;
			
			ResFile = FSOpenResFile(&FileRef,fsRdPerm);
			if (ResFile == 0)
				continue;
				
			UseResFile (ResFile);
			
			// There exist applications that don't even follow this
			// ancient rule: They do have the standard about box though
			// Identified as resource DITL #128 for future reference
			// We need to figure out what to do about these.... 
			Handle ResVal = Get1Resource('plst',0);
			if (ResVal == NULL) {
				CloseResFile(ResFile);
				continue;
			}
			
			LoadResource(ResVal);
			
			CFAuto<CFDataRef> ResourceData;
			ResourceData.Attach( 
				CFDataCreate(kCFAllocatorDefault,
					(UInt8 *) *ResVal, GetHandleSize(ResVal)));
			if (ResourceData.IsEmpty() || CFGetTypeID(ResourceData) != CFDataGetTypeID()) {
				CloseResFile(ResFile);
				continue;
			}
			
			SWItemDict.Attach(
				(CFDictionaryRef) CFPropertyListCreateFromXMLData(kCFAllocatorDefault,
					ResourceData,
					kCFPropertyListImmutable,
					NULL));
				
			CloseResFile(ResFile);
		}	
		else //Standard (but sometimes misnamed) Application Packages for OS X
		{
			//These are the target bundles: Catalog

			SWItemDict.Attach (
				CFBundleCopyInfoDictionaryForURL(SWItemURL)
			);
		}		

		if (SWItemDict.IsEmpty() || CFGetTypeID(SWItemDict) != CFDictionaryGetTypeID())
			continue;

		// Get the information from the dictionary for the item
		ExtractSWDictItems (VisibleAppName, SWReportItem.get(), SWItemDict, PkgNameDest, FileNameDest);

		// Filter the application out if neccesary
		if (FilterFunc == NULL 
			|| FilterFunc (SWReportItem.get())) {
			Dest->AddSubItem(SWReportItem.release());
			TotalCount++;
		}
	}
		
	closedir(SWDirStream);
	
	return ERROR_SUCCESS;
}

long SoftwareCollector::Collect(NVDataItem ** ReturnItem)
{
	auto_ptr<NVDataItem> SoftwareList (new NVDataItem(SWCL_TAG));
	
	long SWCount = 0;
	
	long Success;
	
	InitFilterList();
	
	Success = AddSoftwareItemsFromDir (
		std::string("/Applications"), std::string(),
		AppsDirFilter,
		SWC_APPNAME, SWC_DISPNAME,
		MAX_APP_SCAN_DEPTH, SWCount, SoftwareList.get());
		
	Success = AddSoftwareItemsFromDir (
		std::string("/Library/Receipts"), std::string(".pkg"),
		PKGDirFilter, 
		SWC_DISPNAME, SWC_APPNAME, 
		MAX_PKG_SCAN_DEPTH, SWCount, SoftwareList.get());
	
	if (SWCount > 0) {
		ostringstream SWCountStream;
		SWCountStream << SWCount;
		
		SoftwareList->AddNVItem (SWCL_COUNT, SWCountStream.str().c_str());
	}
		
	*ReturnItem = SoftwareList.release();

#ifdef TRACE
	printf ("Software Complete\n");
#endif
	return ERROR_SUCCESS;
}