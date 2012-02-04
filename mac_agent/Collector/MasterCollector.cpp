#include "MasterCollector.h"
#include "SyslistPrefs.h"
#include "SyslistVersion.h"
#include <SystemConfiguration/SCDynamicStoreCopySpecific.h>
#include <SystemConfiguration/SCPreferencesPath.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <net/if_dl.h>
#include <arpa/inet.h>
#include <net/if.h>
#include <ifaddrs.h>
#include <sstream>
#include <MacUtil/IOUtil.h>

//////////////////////////////////////////////////
// Tag Constants for transmission
//
static const char * MC_VERSION = "Version";

static const char * MC_SYSNAME = "SystemName";
static const char * MC_NETSYSNAME = "NetSystemName";

static const char * MCICompSys_TAG = "CompSystem";
static const char * MCICompSys_MFR = "Manufacturer";
static const char * MCICompSys_MODEL = "Model";
//static const char * MCICompSys_DESC = "Description";
static const char * MCICompSys_SN = "SerialNumber";

static const char * MCIIPAddr_TAG = "IPAddress";
static const char * MCIIPAddr_Attr_TAG = "Address";

static const char * SYS_REG_PWD_VALUE = "Syslist_Password";
static const char * SYS_REG_USER_VALUE = "Syslist_User";
static const char * SYS_REG_ID_VALUE = "Syslist_ID";
static const char * SYS_REG_CODE_VALUE = "Syslist_Account_Code";

void TestFunc()
{
	CFTypeRef typeRef;
	CFStringCreateFromExternalRepresentation(
		kCFAllocatorDefault,
		(CFDataRef) typeRef,
		kCFStringEncodingUTF8);
}
	
long MasterCollector::PreCollect(NVDataItem *MasterDataItem) 
{ 
	/////////////////////////////////////////////
	//
	// Base Syslist Server Information
	//
	
	MasterDataItem->AddNVItem(MC_VERSION, cstrSyslistVersionString);
	
	std::string DataStringVal;
	
	SyslistPrefs::getAcctUserName(DataStringVal);
	MasterDataItem->AddNVItem(SYS_REG_USER_VALUE, DataStringVal.c_str());
	
	SyslistPrefs::getAcctPwd(DataStringVal);
	MasterDataItem->AddNVItem(SYS_REG_PWD_VALUE, DataStringVal.c_str());
	
	SyslistPrefs::getMachID(DataStringVal);
	MasterDataItem->AddNVItem(SYS_REG_ID_VALUE, DataStringVal.c_str());
	
	SyslistPrefs::getAcctCode(DataStringVal);
	MasterDataItem->AddNVItem(SYS_REG_CODE_VALUE, DataStringVal.c_str());
	
	CFAuto<CFStringRef> PrefCFStringVal;
	
	CFAuto<SCDynamicStoreRef> DSObj;
	
	DSObj.Attach(
		SCDynamicStoreCreate(
			kCFAllocatorDefault,
			kProgramID,
			NULL,
			NULL));
		
	PrefCFStringVal.Attach(SCDynamicStoreCopyComputerName(DSObj, NULL));
	PrefCFStringVal.GetCString(DataStringVal);
	MasterDataItem->AddNVItem(MC_SYSNAME, DataStringVal.c_str());
	
	PrefCFStringVal.Attach(SCDynamicStoreCopyLocalHostName(DSObj));
	PrefCFStringVal.GetCString(DataStringVal);
	// consider adding NIS name here via gethostname
	MasterDataItem->AddNVItem(MC_NETSYSNAME, DataStringVal.c_str());
	
	/////////////////////////////////////////////
	//
	// IP Addresses
	//
	
	long Status;
	
	struct ifaddrs* Interfaces = NULL;
	
	Status = getifaddrs(&Interfaces);
	if (Status != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;
	
	struct ifaddrs* CurrIntf = Interfaces;
	
	while (CurrIntf != NULL) {
	
		if ( !(CurrIntf->ifa_flags & IFF_LOOPBACK) 
			&& !(CurrIntf->ifa_flags & IFF_POINTOPOINT)
			&& CurrIntf->ifa_addr->sa_family == AF_INET) {
		
			auto_ptr<NVDataItem> CurrIntfData (new NVDataItem(MCIIPAddr_TAG));
			
			std::string AddrBuf;
			
			switch ( CurrIntf->ifa_addr->sa_family ) {
			
			case AF_INET: {
				struct sockaddr_in * InetData;
				InetData = reinterpret_cast<sockaddr_in *> (CurrIntf->ifa_addr);
				AddrBuf = inet_ntoa(InetData->sin_addr);
				}
				break;
			
			case AF_LINK:
				AddrBuf = link_ntoa(reinterpret_cast<sockaddr_dl *> (CurrIntf->ifa_addr));
				break;
			
			default:
				continue;
			}
			
			CurrIntfData->AddNVItem(MCIIPAddr_Attr_TAG, AddrBuf.c_str());
			
			MasterDataItem->AddSubItem(CurrIntfData.release());
		}
		
		CurrIntf = CurrIntf->ifa_next;
	}
	
	freeifaddrs(Interfaces);
	
	/////////////////////////////////////////
	//
	// Computer System Info
	//
	
	auto_ptr<NVDataItem> CompSystemData (new NVDataItem(MCICompSys_TAG));
	
	CompSystemData->AddNVItem(MCICompSys_MFR, "Apple Computer, Inc.");

	IOServiceListUtil DevTree("IOPlatformExpertDevice", IOServiceListUtil::MatchDevClass);
	
	IOServiceUtil RootNode(DevTree.Next());

	CFAuto<CFStringRef> DataItemString;

	CFAuto<CFTypeRef> ModelName (RootNode.Query("model"));
	
	if (ModelName.IsEmpty()) {
		DataItemString.Attach(CFStringCreateWithCString(kCFAllocatorDefault,"Unknown Model",kCFStringEncodingUTF8));
	}
	else if (CFGetTypeID(ModelName) == CFDataGetTypeID()) {
		DataItemString.Attach(
			CFStringCreateFromExternalRepresentation(
				kCFAllocatorDefault,
				(CFDataRef) ModelName,
				kCFStringEncodingUTF8));
	}
	else if (CFGetTypeID(ModelName) == CFStringGetTypeID())
		DataItemString = (CFStringRef) ModelName;

	DataItemString.GetCString(DataStringVal);
	CompSystemData->AddNVItem(MCICompSys_MODEL, DataStringVal.c_str());
		
	CFAuto<CFTypeRef> SerialNumber (RootNode.Query("IOPlatformSerialNumber"));
	DataItemString = (CFStringRef) SerialNumber;
	DataItemString.GetCString(DataStringVal);
	CompSystemData->AddNVItem(MCICompSys_SN, DataStringVal.c_str());

	MasterDataItem->AddSubItem(CompSystemData.release());
#ifdef TRACE
	printf("Master Complete\n");
#endif
	return ERROR_SUCCESS;
}
