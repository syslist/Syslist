#include "stdafx.h"
#include "OSCollector.h"
#include "AutoRegKey.h"
#include "KeyDecode.h"

static char * OSCL_TAG = "OperatingSystemList";
static char * OSCI_TAG = "OperatingSystem";

char WINDOWS_OS_INFO_KEY[] = "Software\\Microsoft\\Windows NT\\CurrentVersion";

static const CComBSTR WMI_OS_CLASSES(L"WIN32_OperatingSystem");

static WMIObjValueEntry_t NetEntries[] = {
	{ L"Manufacturer"},
	WMIObjValueEntry_t(L"Caption", "OSName"),
	{ L"Version"},
	{ L"BuildNumber"},
	{ L"CSName"},
	{ L"ServicePackMajorVersion"},
	{ L"ServicePackMinorVersion"},
	{ L"CSDVersion"},
	{ L"PlusProductID"},
	{ L"PlusVersionNumber"},
	{ L"BootDevice"},
	{ L"WindowsDirectory"},
	{ L"SystemDrive"},
	{ L"SerialNumber"},
	{ NULL}
};

long OSCollector::PreferredCollect(NVDataItem *DataItems) 
{
	WMIEnumerateClass(WMI_OS_CLASSES, OSCI_TAG, DataItems, NetEntries, NULL);

	AutoRegKey OSIDKey;
	long Status;

	Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, WINDOWS_OS_INFO_KEY, NULL, KEY_READ, & OSIDKey);
	if (Status == ERROR_SUCCESS) {
		char ProdID[256];
		unsigned long ItemSize = 256;
		DWORD ItemType = REG_BINARY;
		
		Status = RegQueryValueEx(
			OSIDKey,		// handle to key to query
			"DigitalProductID",	// address of name of value to query
			NULL,			// reserved
			&ItemType,		// address of buffer for value type
			(unsigned char *) ProdID,     // address of data buffer
			&ItemSize		// address of data buffer size
		);

		if (Status == ERROR_SUCCESS) {
			char decodeKey[kDecodeKeyLen + 1];
			if (DecodeMSKeyReg(decodeKey, ProdID) == ERROR_SUCCESS) {
				DataItems->AddNVItem ("ProductKey", decodeKey);
			}
		}
	}

	return ERROR_SUCCESS;
}

long OSCollector::AppendNTCommonVersionString(string & VersionString, OSVERSIONINFOEX & WinVer)
{
	if (WinVer.dwOSVersionInfoSize != sizeof (OSVERSIONINFOEX))
		return ERROR_SUCCESS;

	if (WinVer.wSuiteMask & VER_SUITE_DATACENTER)	//Windows 2000 DataCenter Server is installed. 
		VersionString += " DataCenter";

	if (WinVer.wSuiteMask & VER_SUITE_ENTERPRISE) {	// Windows 2000 Advanced Server or Windows .NET Enterprise Server is installed.. 
		switch (WinVer.dwMajorVersion) {
		case 5:
			switch (WinVer.dwMinorVersion) {
			case 0:
				VersionString += "Advanced";
				break;
			case 1:
				VersionString += ".NET Enterprise";
				break;
			default:
				VersionString += "Enterprise";
				break;
			}
			break;

		default:
			VersionString += "Enterprise";
		}
	}

	if (WinVer.wSuiteMask & VER_SUITE_PERSONAL) //Windows XP: Windows XP Home Edition is installed. 
		VersionString += " Home";
 
	if (WinVer.wSuiteMask & VER_SUITE_SMALLBUSINESS) //Microsoft Small Business Server is installed.  
		VersionString += " Small Business Server";

	if (WinVer.wSuiteMask & VER_SUITE_SMALLBUSINESS_RESTRICTED) //Microsoft Small Business Server is installed with the restrictive client license in force. 
		VersionString += " Small Business Server (Restricted)";

	if (WinVer.wSuiteMask & VER_SUITE_TERMINAL)		//Terminal Services is installed. 
		VersionString += " Terminal";

	if (WinVer.wSuiteMask & VER_SUITE_BACKOFFICE)	//Microsoft BackOffice components are installed.
		VersionString += " (BackOffice Components)";


	switch (WinVer.wProductType) {
	case VER_NT_WORKSTATION:	//The system is running Windows NT 4.0 Workstation, Windows 2000 Professional, 
								//Windows XP Home Edition, or Windows XP Professional. 
		switch (WinVer.dwMajorVersion) {
		case 5:
			if ( ! (WinVer.wSuiteMask & VER_SUITE_PERSONAL))
				VersionString += " Professional";
			break;

		default:
			VersionString += " Workstation";
			break;
		}
		break;

	case VER_NT_DOMAIN_CONTROLLER:  //The system is a domain controller. 
	case VER_NT_SERVER:				// The system is a server. 
		VersionString += " Server";

	default:
		VersionString += " Unknown Product Type";
		break;
	}

	return ERROR_SUCCESS;
}

long OSCollector::AppendNTVersionString(string & VersionString, string & CSDVersion, OSVERSIONINFOEX & WinVer)
{
	char NumBuf[12];

	switch (WinVer.dwMajorVersion) {

	case 3:
		if (WinVer.dwMinorVersion == 51) {
			VersionString += " NT 3.51";
		}
		else {
			VersionString += " Unknown NT 3 Minor Version ";
			VersionString += _itoa(WinVer.dwMinorVersion, NumBuf, 10);
		}
		break;

	case 4:
		if (WinVer.dwMinorVersion == 0) {
			VersionString += " NT 4.0";
		}
		else {
			VersionString += " Unknown NT 4 Minor Version ";
			VersionString += _itoa(WinVer.dwMinorVersion, NumBuf, 10);
		}
		break;

	case 5:
		switch (WinVer.dwMinorVersion) {
		
		case 0:
			VersionString += " 2000";
			break;

		case 1:
			VersionString += " XP";
			break;

		case 2:
			VersionString += " 2003";
			break;

		default:
			VersionString += " Unknown NT 5 Minor Version ";
			VersionString += _itoa(WinVer.dwMinorVersion, NumBuf, 10);
		}

		break;

	case 6:

		VersionString += " Vista";
		break;

	default:
		VersionString += "Unknown NT Version ";
		VersionString += _itoa(WinVer.dwMajorVersion, NumBuf, 10);
		VersionString += ".";
		VersionString += _itoa(WinVer.dwMinorVersion, NumBuf, 10);
		break;
	}

	AppendNTCommonVersionString(VersionString, WinVer);

	CSDVersion += WinVer.szCSDVersion;

	return ERROR_SUCCESS;
}

long OSCollector::AppendPreNTVersionString (string & VersionString, string & CSDVersion, OSVERSIONINFOEX & WinVer)
{
	char NumBuf[12];

	switch (WinVer.dwMajorVersion) {

	case 4:
		switch (WinVer.dwMinorVersion) {

		case 0:
			VersionString += " 95";
			if (!strcmp(WinVer.szCSDVersion, " C "))
				CSDVersion = "OSR2";
			else
				CSDVersion += WinVer.szCSDVersion;

			break;

		case 10:
			VersionString += " 98";
			if (!strcmp(WinVer.szCSDVersion, " A "))
				CSDVersion += "Second Edition";
			else
				CSDVersion += WinVer.szCSDVersion;
			break;

		case 90:
			VersionString += " ME ";
			CSDVersion = WinVer.szCSDVersion;
			break;

		default:
			VersionString += " Unknown Non-NT Series 4 Minor Version ";
			VersionString += _itoa(WinVer.dwMinorVersion, NumBuf, 10);
			VersionString += " ";
			CSDVersion += WinVer.szCSDVersion;
			break;
		}

		break;

	default:
		VersionString += "Unknown Non-NT Major Version ";
		VersionString += _itoa(WinVer.dwMajorVersion, NumBuf, 10);
		CSDVersion += WinVer.szCSDVersion;

		break;
	}

	return ERROR_SUCCESS;
}

long OSCollector::CollectFallback(NVDataItem *DataItems)
{

	BOOL VersionOK;
	BOOL ExtendedVersion;
	OSVERSIONINFOEX WinVer;

	// Try for the good stuff first
	WinVer.dwOSVersionInfoSize = sizeof (OSVERSIONINFOEX);

	VersionOK = ExtendedVersion = GetVersionEx((LPOSVERSIONINFO) & WinVer);

	if (VersionOK == FALSE) {
		WinVer.dwOSVersionInfoSize = sizeof (OSVERSIONINFO);
		VersionOK = GetVersionEx((LPOSVERSIONINFO) &WinVer);
		
		if (VersionOK == FALSE)
			return ERROR_GEN_FAILURE;
	}

	auto_ptr<NVDataItem> OSItem (new NVDataItem(OSCI_TAG));

	char NumBuf[12];

	string VersionString;
	string CSDVersion;
	string VersionNumString;
	string BuildNumString;
	
	OSItem->AddNVItem("Manufacturer", "Microsoft Corporation");

	// Figure out Windows Version String

	VersionString = "MicroSoft Windows";

	switch (WinVer.dwPlatformId) {

	case VER_PLATFORM_WIN32_WINDOWS:
		AppendPreNTVersionString ( VersionString, CSDVersion, WinVer);
		break;

	case VER_PLATFORM_WIN32_NT:
		AppendNTVersionString ( VersionString, CSDVersion, WinVer);
		break;

	default:
		VersionString += " Unknown Version";
		break;
	}
	
	OSItem->AddNVItem("OSName", VersionString.c_str());
    OSItem->AddNVItem("CSDVersion", CSDVersion.c_str());

	// Get the Build Number
	VersionNumString = _itoa(WinVer.dwMajorVersion, NumBuf, 10);
	VersionNumString += ".";
	VersionNumString += _itoa(WinVer.dwMinorVersion, NumBuf, 10);
	VersionNumString += ".";

	if (WinVer.dwPlatformId == VER_PLATFORM_WIN32_WINDOWS) 
		BuildNumString = _itoa(LOWORD(WinVer.dwBuildNumber), NumBuf, 10);
	else
		BuildNumString += _itoa(WinVer.dwBuildNumber, NumBuf, 10);

	VersionNumString += BuildNumString;

	OSItem->AddNVItem("BuildNumber", BuildNumString.c_str());
	OSItem->AddNVItem("Version", VersionNumString.c_str());

	if (ExtendedVersion)  {
		OSItem->AddNVItem ("ServicePackMajorVersion", _itoa(WinVer.wServicePackMajor, NumBuf, 10));
		OSItem->AddNVItem ("ServicePackMinorVersion", _itoa(WinVer.wServicePackMinor, NumBuf, 10));
	}

	DataItems->AddSubItem(OSItem.release());
	
	return ERROR_SUCCESS;
}

long OSCollector::Collect(NVDataItem **ReturnItem) 
{

	auto_ptr<NVDataItem> DataItems (new NVDataItem(OSCL_TAG));

	if (!WMIServicesValid())
		CollectFallback(DataItems.get());
	else 
		PreferredCollect(DataItems.get());
		
	*ReturnItem = DataItems.release();

#ifdef INSTRUMENTED
	MessageBox(NULL, "OS Complete!", "Report", MB_OK);
#endif
	return ERROR_SUCCESS;
}



