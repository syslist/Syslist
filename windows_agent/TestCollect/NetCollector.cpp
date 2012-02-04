
#include "stdafx.h"
#include "NetCollector.h"
#include "Iphlpapi.h"

static char * NCL_TAG = "NetAdapterList";
static char * NCI_TAG = "NetAdapter";

static char * NCCL_TAG = "NetAdapterConfigList";
static char * NCCI_TAG = "NetAdapterConfig";

static const unsigned long NET_ADAPT_ROWS = 16;

static CComBSTR WMI_NET_CLASSES(L"WIN32_NetworkAdapter");
static CComBSTR WMI_NETCFG_CLASSES(L"WIN32_NetworkAdapterConfiguration");
static CComBSTR WMI_NETADAPTCFG_CLASSES(L"Win32_NetworkAdapterSetting");

static WMIObjValueEntry_t NetEntries[] = {
	WMIObjValueEntry_t(L"AdapterType", "Type"),
	{ L"Manufacturer"},
	{ L"Model"},
	{ L"Caption"},
	{ L"InterfaceType"},
	{ L"Description"},
	{ L"Status"},
	//{ L"MACAddress"},
	//{ L"Index"},
	//{ L"NetworkAddresses"},
	{ L"PNPDeviceID"},
	{ L"ProductName"},
	{ L"ServiceName"},
	{ NULL}
};

static WMIObjValueEntry_t NetCfgEntries[] = {
	//{ L"Index"},
	{ L"MACAddress"},
	{ L"IPAddress"},
	{ L"IPSubNet" },
	{ L"DHCPEnabled" },
	{ L"DHCPServer" },
	{ L"DNSDomain" },
	{ NULL }
};


long NetCollector::CollectSingleAdaptor (IWbemClassObject * WMINetAdpaterSetting, NVDataItem *TargetItem, bool * KeepItem  )
{
	HRESULT hr;

	CComVariant varNetAdapter;
	hr = WMINetAdpaterSetting->Get(CComBSTR(L"Element"), 0, &varNetAdapter, NULL, NULL);
	if (FAILED(hr) || varNetAdapter.vt != VT_BSTR) {
		*KeepItem = false;
		return ERROR_SUCCESS;
	}

	CComPtr<IWbemClassObject> NetAdapter;
	hr = m_WMIServices->GetObject(varNetAdapter.bstrVal, 
							WBEM_FLAG_RETURN_WBEM_COMPLETE,
							NULL,
							&NetAdapter,
							NULL);

	if (FAILED(hr) || NetAdapter == NULL) {
		*KeepItem = false;
		return ERROR_SUCCESS;
	}

	CComVariant varNetConfig;
	WMINetAdpaterSetting->Get(L"Setting", 0, &varNetConfig, NULL, NULL);
	if (FAILED(hr) || varNetAdapter.vt != VT_BSTR) {
		*KeepItem = false;
		return ERROR_SUCCESS;
	}

	CComPtr<IWbemClassObject> NetConfig;
	hr = m_WMIServices->GetObject(varNetConfig.bstrVal, 
							WBEM_FLAG_RETURN_WBEM_COMPLETE,
							NULL,
							&NetConfig,
							NULL);

	if (FAILED(hr) || NetConfig == NULL) {
		*KeepItem = false;
		return ERROR_SUCCESS;
	}

	WMICollectAuto(NetAdapter, TargetItem, NetEntries);
	const char * TypeName = NULL;

	TargetItem->GetValueByName("Type", &TypeName);

	if (TypeName == NULL) {
		*KeepItem = false;
		return ERROR_SUCCESS;
	}

	WMICollectAuto(NetConfig, TargetItem, NetCfgEntries);
	const char * MACAddress = NULL;

	TargetItem->GetValueByName("MACAddress", &MACAddress);

	if (MACAddress == NULL)  {

		*KeepItem = false;

		return ERROR_SUCCESS;
	}

	long MACAddrNum[6] = {0};

	sscanf (MACAddress,"%x:%x:%x:%x:%x:%x",
			&(MACAddrNum[0]),
			&(MACAddrNum[1]),
			&(MACAddrNum[2]),
			&(MACAddrNum[3]),
			&(MACAddrNum[4]),
			&(MACAddrNum[5]));


	if (MACAddrNum[0] == 0
		&& MACAddrNum[1] == 0 
		&& MACAddrNum[2] == 0 
		&& MACAddrNum[3] == 0 
		&& MACAddrNum[4] == 0 
		&& MACAddrNum[5] == 0) {

		*KeepItem = false;

		return ERROR_SUCCESS;
	}

	return ERROR_SUCCESS;
}


long NetCollector::PreferredCollect(NVDataItem *DataItems) 
{
	WMIEnumerateClass(WMI_NETADAPTCFG_CLASSES, NCI_TAG, DataItems, NULL, CollectSingleAdaptor);
	return ERROR_SUCCESS;
}

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

long NetCollector::CollectFallback(NVDataItem *DataItems) 
{
	USES_CONVERSION;

	DWORD Status;

	DWORD NumIfEntries;

	Status = GetNumberOfInterfaces( &NumIfEntries);
	if (Status != NO_ERROR)
		return ERROR_SUCCESS;

	PMIB_IFTABLE IfTable = NULL;
	PMIB_IPADDRTABLE IPAddrTable = NULL;

	unsigned long IfLen = 0;
	unsigned long IpLen = 0;

	Status = GetIfTable(IfTable, &IfLen, TRUE);
	if (Status != ERROR_INSUFFICIENT_BUFFER)
		return ERROR_SUCCESS;

	Status = GetIpAddrTable(IPAddrTable, &IpLen, FALSE);
	if (Status != ERROR_INSUFFICIENT_BUFFER)
		return ERROR_SUCCESS;
	
	IfTable = (PMIB_IFTABLE) new char[IfLen];
	memset(IfTable, 0 , IfLen);

	IPAddrTable = (PMIB_IPADDRTABLE) new char[IpLen];
	memset(IPAddrTable, 0 , IpLen);

	Status = GetIfTable(IfTable, &IfLen, TRUE);
	if (Status != NO_ERROR) {
		delete IfTable;
		delete IPAddrTable;
		return ERROR_SUCCESS;
	}

	Status = GetIpAddrTable(IPAddrTable, &IpLen, FALSE);
	if (Status != NO_ERROR) {
		delete IfTable;
		delete IPAddrTable;
		return ERROR_SUCCESS;
	}

	DWORD CurrIf;

	char TransString[64];
	char IPString[64];

	PMIB_IFROW CurrEntry = NULL;

	for (CurrIf = 0, CurrEntry = IfTable->table;
		 CurrIf < IfTable->dwNumEntries; 
		 CurrIf ++, CurrEntry ++) {

		auto_ptr<NVDataItem> SubItem (new NVDataItem(NCI_TAG));

		TransString[0] = '\0';

		if (Status != NO_ERROR) {
			delete IfTable;
			return ERROR_SUCCESS;
		}
#if 0
		if (CurrEntry.dwPhysAddrLen < 1)
			continue;
#endif
		char * IfTypeName;
		switch (CurrEntry->dwType) {
		case MIB_IF_TYPE_ETHERNET:
			IfTypeName = "Ethernet 802.3";
			break;

		case MIB_IF_TYPE_TOKENRING:
			IfTypeName = "Token Ring 802.5";
			break;

		case MIB_IF_TYPE_FDDI:
			IfTypeName = "Fiber Distributed Data Interface (FDDI)";
			break;

		case MIB_IF_TYPE_PPP:
			IfTypeName = "PPP";
			break;

		case MIB_IF_TYPE_SLIP:
			IfTypeName = "SLIP";
			break;

		default:
			IfTypeName = "Unknown";
//			continue;
		}

		SubItem->AddNVItem("InterfaceType", IfTypeName);
		
		//if (wcslen(IfTable->Adapter[CurrIf].Name) > 0) {
		//	SubItem->AddNVItem("SysName", W2A(IfTable->Adapter[CurrIf].Name));
		//}

		if (CurrEntry->wszName != NULL && wcslen(CurrEntry->wszName) > 0) {
			SubItem->AddNVItem("Description", CurrEntry->wszName);
		}
		else if (strlen((const char *) CurrEntry->bDescr) > 0) {
			SubItem->AddNVItem("Description", (const char *) CurrEntry->bDescr);
		}

		//_ASSERT (CurrIfTableEntry.dwPhysAddrLen == 6);

		if (CurrEntry->bPhysAddr[0] == 0
		    && CurrEntry->bPhysAddr[1] == 0
		    && CurrEntry->bPhysAddr[2] == 0
		    && CurrEntry->bPhysAddr[3] == 0
		    && CurrEntry->bPhysAddr[4] == 0
			&& CurrEntry->bPhysAddr[5] == 0) {

			continue;
		}

		sprintf (TransString,"%02X:%02X:%02X:%02X:%02X:%02X",
					CurrEntry->bPhysAddr[0],
					CurrEntry->bPhysAddr[1],
					CurrEntry->bPhysAddr[2],
					CurrEntry->bPhysAddr[3],
					CurrEntry->bPhysAddr[4],
					CurrEntry->bPhysAddr[5]);

		SubItem->AddNVItem("MACAddress", TransString);


		string IPAddrList;
		
		long CurrIPIndex;
		MIB_IPADDRROW * CurrIPAddrInfo;
		long FoundIP = 0;

		for (CurrIPIndex = 0, CurrIPAddrInfo = IPAddrTable->table; 
			 CurrIPIndex < IPAddrTable->dwNumEntries; 
			 CurrIPIndex ++, CurrIPAddrInfo ++) {

			if (IPAddrTable->table[CurrIPIndex].dwIndex == CurrEntry->dwIndex) {
				FoundIP ++;
				if (FoundIP > 1)
					IPAddrList.append(",");
				else {
					GetIPAddrString (CurrIPAddrInfo->dwMask, IPString);
					SubItem->AddNVItem("IPSubNet", IPString);
				}
					

				GetIPAddrString(CurrIPAddrInfo->dwAddr, IPString);
				IPAddrList.append(IPString);
			}
		}

		SubItem->AddNVItem("IPAddress", IPAddrList.c_str());

		DataItems->AddSubItem(SubItem.release());
	}

	delete IfTable;
	delete IPAddrTable;

	// WinSetupEnumerateDevClass((GUID) GUID_DEVCLASS_NET, NCI_TAG, DataItems, NULL);

	return ERROR_SUCCESS;
}

long NetCollector::Collect(NVDataItem **ReturnItem) 
{
	auto_ptr<NVDataItem> DataItems (new NVDataItem(NCL_TAG));

	if (!WMIServicesValid())
		CollectFallback(DataItems.get());
	else 
		PreferredCollect(DataItems.get());
		
	*ReturnItem = DataItems.release();

#ifdef INSTRUMENTED
	MessageBox(NULL, "Network Complete!", "Report", MB_OK);
#endif

	return ERROR_SUCCESS;
}

