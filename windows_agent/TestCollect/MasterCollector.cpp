#include "stdafx.h"
#include "MasterCollector.h"
#include "../TestInstConfig/SyslistVersion.h"
#include "../TestInstConfig/SyslistRegistry.h"
#include "../TestWinCrypt/SimpleCrypt.h"
#include <winsock2.h>
#include <iptypes.h>
#include <Iphlpapi.h>
#include <Lmwksta.h>
#include <Lm.h>

//Null Terminated Ordered list of items to collect.

DataCollectCreatorFunc MasterCollector::m_SyslistCollectors[] = {
	ProcessorCollector::CreateCollector,
	OSCollector::CreateCollector,
	DiskCollector::CreateCollector,
	NetCollector::CreateCollector,
	SoftwareCollector::CreateCollector,
	DisplayCollector::CreateCollector,
	NULL
};


char * MC_VERSION = "Version";

char * MC_COLLECTTIME = "Time";

//const char * MC_ID = "Syslist_ID";
//const char * MC_ID_VALUE = "0x00000000";

char * MC_SYSNAME = "SystemName";
char * MC_NETSYSNAME = "NetSystemName";

static char * MCIBase_TAG = "BaseBoard";
static char * MCICompSys_TAG = "CompSystem";
static char * MCIBIOS_TAG = "BIOSInfo";
static char * MCISYSENCLOSURE_TAG = "SystemEnclosure";
static char * MCIIPAddr_TAG = "IPAddress";
static char * MCIIPAddr_Attr_TAG = "Address";

//static const char * REG_PWD_VALUE = "Password";
//static const char * REG_USER_VALUE = "UserName";
//static const char * REG_ID_VALUE = "ID";

static char * SYS_REG_PWD_VALUE = "Syslist_Password";
static char * SYS_REG_USER_VALUE = "Syslist_User";
static char * SYS_REG_ID_VALUE = "Syslist_ID";
static char * SYS_ACCT_ID_VALUE = "Syslist_Account_Code";

static const long CFG_STRING_LEN = 512;

static const DWORD LOOPBACK_EXCLUDE_ADDR = 0x0100007f;
static const DWORD EMPTY_EXCLUDE_ADDR = 0x00000000;

static char SyslistConfigUser[CFG_STRING_LEN] = "";
static char SyslistConfigPwd[CFG_STRING_LEN] = "";
static char SyslistConfigID[CFG_STRING_LEN] = "";
static char SyslistConfigAcctID[CFG_STRING_LEN] = "";

//static char * SYSLIST_REG_LOC = "Software\\Syslist Companion Agent\\Config";

static CComBSTR WMI_BASEBOARD_CLASSES(L"WIN32_BASEBOARD");
static CComBSTR WMI_COMPSYS_CLASSES(L"WIN32_ComputerSystem");
static CComBSTR WMI_BIOS_CLASSES(L"WIN32_BIOS");
static CComBSTR WMI_SYSENCLOSURE_CLASSES(L"WIN32_SystemEnclosure");

static WMIObjValueEntry_t BaseBoardEntries[] = {
	{ L"Manufacturer"},
	{ L"Model"},
	{ L"PartNumber"},
	{ L"Product"},
	{ L"Name"},
	{ L"SerialNumber"},
	{ L"OtherIdentifyingInfo"},
	{ NULL}
};


static WMIObjValueEntry_t CompSystemEntries[] = {
	{ L"Manufacturer"},
	{ L"Model"},
	{ L"Name"},
	{ L"SerialNumber"},
	{ L"Description"},
//	{ L"OEMStringArray"},
	{ NULL}
};


static WMIObjValueEntry_t SystemEnclosureEntries[] = {
	{ L"Manufacturer"},
	{ L"Model"},
	{ L"PartNumber"},
	{ L"SMBIOSAssetTag"},
	{ L"Name"},
	{ L"SerialNumber"},
	{ L"SecurityBreach"},
	{ L"BreachDescription"},
	{ L"OtherIdentifyingInfo"},
	{ NULL}
};

static WMIObjValueEntry_t BIOSEntries[] = {
	{ L"Manufacturer"},
	{ L"Name"},
	{ L"SMBIOSBIOSVersion"},
	{ L"SMBIOSMajorVersion"},
	{ L"SMBIOSMinorVersion"},
	{ L"SMBIOSPresent"},
	{ L"BIOSVersion"},
	{ L"Version"},
	{ L"Caption"},
	{ L"SerialNumber"},
	{ L"Status"},
	{ NULL}
};

typedef NET_API_STATUS (NET_API_FUNCTION  * NetWkstaGetInfo_t) (IN LMSTR   servername OPTIONAL, IN  DWORD   level, OUT LPBYTE  *bufptr);
typedef NET_API_STATUS (NET_API_FUNCTION  * NetApiBufferFree_t) ( IN LPVOID Buffer);

/////////////////////////////////////////////////////////////////////////////////////////

static void GetIPAddrString(DWORD & CompactIP, char * DestIPString)
{
	unsigned char * AltIP;

	AltIP = (unsigned char *) & CompactIP;

	sprintf (DestIPString,"%u.%u.%u.%u",
		AltIP[0],
		AltIP[1],
		AltIP[2],
		AltIP[3]);
}

long MasterCollector::PreCollect(NVDataItem* MasterDataItem)
{
	
	MasterDataItem->AddNVItem(MC_VERSION, SyslistVersionString);

	AutoRegKey SyslistRegKey;
	long Status;

	Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SYSLIST_REG_LOC, NULL, KEY_READ, & SyslistRegKey);
	if (Status != ERROR_SUCCESS) {
#ifdef INSTRUMENTED
		char failNum[256] = {0};
		sprintf (failNum, "Failed Reg Key Open (%d)", Status);
		MessageBox(NULL, failNum, "Fail Report", MB_OK);
#endif
		return Status;
	}

	char ReturnRegString[CFG_STRING_LEN];
	unsigned long ReturnSize;
	DWORD ReturnType;

	// These are encrypted via Windows Encryption...
	SimpleStringPWCrypt RegDecrypt(SyslistPhrase);

	ReturnSize = CFG_STRING_LEN;
	Status = RegQueryValueEx(SyslistRegKey, REG_USER_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigUser, ReturnRegString, ReturnSize, ReturnType);
	}
	MasterDataItem->AddNVItem(SYS_REG_USER_VALUE, SyslistConfigUser);

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_PWD_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigPwd, ReturnRegString, ReturnSize, ReturnType);
	}
	MasterDataItem->AddNVItem(SYS_REG_PWD_VALUE, SyslistConfigPwd);

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_ID_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigID, ReturnRegString, ReturnSize, ReturnType);
	}

	MasterDataItem->AddNVItem(SYS_REG_ID_VALUE, SyslistConfigID);

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_ACCTID_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigAcctID, ReturnRegString, ReturnSize, ReturnType);
	}
	MasterDataItem->AddNVItem(SYS_ACCT_ID_VALUE, SyslistConfigAcctID);

	const char * IDVal = NULL;

	wchar_t SystemName[MAX_COMPUTERNAME_LENGTH + 1];
	DWORD SysNameLen = MAX_COMPUTERNAME_LENGTH + 1;

	GetComputerNameW(SystemName, &SysNameLen);
	MasterDataItem->AddNVItem(MC_SYSNAME,SystemName);

	char NetSystemName[MAX_COMPUTERNAME_LENGTH + MAX_DOMAIN_NAME_LEN + 32];
	DWORD NetSysNameLen = MAX_COMPUTERNAME_LENGTH + 1;

	gethostname(NetSystemName, NetSysNameLen);
	BOOL DomainFound = FALSE;

	// No domain detected automatically, go one level further
	// and call system functions to extract.
	if (strchr(NetSystemName,'.') == NULL) {

		DWORD NetParamLen = 0;
		PFIXED_INFO NetParams = NULL;

		Status = GetNetworkParams(NULL, &NetParamLen);
		if (Status == ERROR_BUFFER_OVERFLOW) {

			NetParams = (PFIXED_INFO) new char[NetParamLen];
			if (NetParams == NULL)
				return ERROR_GEN_FAILURE;

			Status = GetNetworkParams(NetParams, &NetParamLen);
			if (Status == ERROR_SUCCESS && NetParams->DomainName != NULL && strlen(NetParams->DomainName) > 0) {
				strcat(NetSystemName, ".");
				strcat(NetSystemName, NetParams->DomainName);
				DomainFound = TRUE;
			}

			delete NetParams;
		}
		if (DomainFound == FALSE) {

			HMODULE NetApiDLL = NULL;
			NetWkstaGetInfo_t NetWkstaGetInfo = NULL;
			NetApiBufferFree_t NetApiBufferFree = NULL;

			NetApiDLL = LoadLibrary("NetApi32.DLL");

			if (NetApiDLL != NULL) {

				NetWkstaGetInfo = (NetWkstaGetInfo_t) GetProcAddress(NetApiDLL, "NetWkstaGetInfo");
				NetApiBufferFree = (NetApiBufferFree_t) GetProcAddress(NetApiDLL, "NetApiBufferFree");

			}
			
			if (NetApiDLL != NULL && NetWkstaGetInfo != NULL && NetApiBufferFree != NULL) {
			// For WinNT Fallback
				PWKSTA_INFO_100 LocalNetInfo = NULL;
				NET_API_STATUS NetStatus;
				NetStatus = NetWkstaGetInfo(NULL, 100, (LPBYTE *) & LocalNetInfo);

				if (NetStatus == NERR_Success) {
					if (wcslen((WCHAR *)LocalNetInfo->wki100_langroup) > 0) {
						USES_CONVERSION;
						strcat(NetSystemName, ".");
						strcat(NetSystemName,W2A((WCHAR *)LocalNetInfo->wki100_langroup));
						DomainFound = TRUE;
					}
					NetApiBufferFree ((LPVOID) LocalNetInfo);
				}
			}
		}

	}

	MasterDataItem->AddNVItem(MC_NETSYSNAME, NetSystemName);

	PMIB_IPADDRTABLE IPAddrTable = NULL;
	unsigned long IpLen = 0;
	Status = GetIpAddrTable(IPAddrTable, &IpLen, FALSE);

	if (Status == ERROR_INSUFFICIENT_BUFFER) {

		IPAddrTable = (PMIB_IPADDRTABLE) new char[IpLen];
		if (IPAddrTable == NULL) 
			return ERROR_GEN_FAILURE;

		memset(IPAddrTable, 0 , IpLen);

		Status = GetIpAddrTable(IPAddrTable, &IpLen, FALSE);
		if (Status == NO_ERROR) {
			
			char IPDest[64];

			long CurrIPIndex;
			MIB_IPADDRROW * CurrIPAddrInfo;

			for (CurrIPIndex = 0, CurrIPAddrInfo = IPAddrTable->table; 
				 CurrIPIndex < IPAddrTable->dwNumEntries; 
				 CurrIPIndex ++, CurrIPAddrInfo ++) {

				if (CurrIPAddrInfo->dwAddr != LOOPBACK_EXCLUDE_ADDR 
					&& CurrIPAddrInfo->dwAddr != EMPTY_EXCLUDE_ADDR) {

					auto_ptr<NVDataItem> IPAddrItem (new NVDataItem(MCIIPAddr_TAG));

					GetIPAddrString(CurrIPAddrInfo->dwAddr, IPDest);
					IPAddrItem->AddNVItem(MCIIPAddr_Attr_TAG, IPDest);

					MasterDataItem->AddSubItem(IPAddrItem.release());
				}
			}

		}
		delete IPAddrTable;
	}
	

	if (WMIServicesValid()) {
		WMIEnumerateClass(WMI_BASEBOARD_CLASSES, MCIBase_TAG, MasterDataItem, BaseBoardEntries, NULL);
		WMIEnumerateClass(WMI_COMPSYS_CLASSES, MCICompSys_TAG, MasterDataItem, CompSystemEntries, NULL);
		WMIEnumerateClass(WMI_SYSENCLOSURE_CLASSES, MCISYSENCLOSURE_TAG, MasterDataItem, SystemEnclosureEntries, NULL);
		WMIEnumerateClass(WMI_BIOS_CLASSES, MCIBIOS_TAG, MasterDataItem, BIOSEntries, NULL);
	}
#ifdef INSTRUMENTED
	MessageBox(NULL, "Master Complete!", "Report", MB_OK);
#endif
	return ERROR_SUCCESS;
}