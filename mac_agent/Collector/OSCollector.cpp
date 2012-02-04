#include <OSCollector.h>
#include <MacUtil/CFUtil.h>
#include <SystemConfiguration/SCDynamicStoreCopySpecific.h>
#include <SystemConfiguration/SCPreferencesPath.h>

static const char * OSCL_TAG = "OperatingSystemList";
static const char * OSCI_TAG = "OperatingSystem";

static const char * OSCL_MFR	= "Manufacturer";
static const char * OSCL_NAME	= "OSName";
static const char * OSCL_VERSION = "CSDVersion";
static const char * OSCL_BUILD	= "BuildNumber";

long OSCollector::Collect(NVDataItem **ReturnItem) 
{

	auto_ptr<NVDataItem> DataItems (new NVDataItem(OSCL_TAG));
	auto_ptr<NVDataItem> OSItem (new NVDataItem(OSCI_TAG));
	
	OSItem->AddNVItem(OSCL_MFR, "Apple Computer, Inc.");
	
	CFAuto<CFStringRef> PrefCFStringVal;
	
	CFAuto<SCDynamicStoreRef> DSObj;
	
	DSObj.Attach(
		SCDynamicStoreCreate(
			kCFAllocatorDefault,
			kProgramID,
			NULL,
			NULL));
				
	std::string PrefStringVal;
	CFAuto<SCPreferencesRef> SysVersion;
	
	SysVersion.Attach(
		SCPreferencesCreate (
			kCFAllocatorDefault,
			kProgramID,
			CFSTR("/System/Library/CoreServices/SystemVersion.plist")));
	
	CFPropertyListRef SCPropVal;
	
	SCPropVal = 
		SCPreferencesGetValue(SysVersion, CFSTR("ProductName"));
	
	PrefCFStringVal = reinterpret_cast<CFStringRef>(SCPropVal);
	PrefCFStringVal.GetCString(PrefStringVal);
	OSItem->AddNVItem(OSCL_NAME, PrefStringVal.c_str());

	SCPropVal = 
		SCPreferencesGetValue(SysVersion, CFSTR("ProductUserVisibleVersion"));
	
	PrefCFStringVal = reinterpret_cast<CFStringRef>(SCPropVal);
	PrefCFStringVal.GetCString(PrefStringVal);
	OSItem->AddNVItem(OSCL_VERSION, PrefStringVal.c_str());
	
	SCPropVal =
		SCPreferencesGetValue(SysVersion, CFSTR("ProductBuildVersion"));
	
	PrefCFStringVal = reinterpret_cast<CFStringRef>(SCPropVal);
	PrefCFStringVal.GetCString(PrefStringVal);
	OSItem->AddNVItem(OSCL_BUILD, PrefStringVal.c_str());		
		
	DataItems->AddSubItem(OSItem.release());	
	*ReturnItem = DataItems.release();
#ifdef TRACE
	printf("OS Complete\n");
#endif
	return ERROR_SUCCESS;
}
