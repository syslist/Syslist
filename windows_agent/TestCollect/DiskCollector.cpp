
#include "stdafx.h"
#include "DiskCollect.h"


static char * DCL_TAG = "StorageList";
static char * DCI_TAG = "Disk";
static char * DCO_TAG = "Optical";

static char * DCI_TYPE = "Type";
static char * DCI_TYPE_FIXED = "Fixed";
static char * DCI_TYPE_REMOVABLE = "Removeable";

static const CComBSTR WMI_DISK_CLASSES(L"WIN32_DiskDrive");
static const CComBSTR WMI_CDROM_CLASSES(L"Win32_CDROMDrive");

static const double DiskAdj = 1e6;

static WMIObjValueEntry_t DiskEntries[] = {
	{ L"Manufacturer"},
	//{ L"Name"},
	{ L"Model"},
	{ L"PNPDeviceID"},
	{ L"InterfaceType"},
	{ L"MediaType"},
	{ L"MediaLoaded"},
	{ L"DeviceID"},
	{ L"Partitions"},
	{ L"Signature"},
	//{ L"Size"},
	{ NULL}
};

static WMIObjValueEntry_t CDROMEntries[] = {
	{ L"Manufacturer"},
	WMIObjValueEntry_t(L"Name", "Model"),
	//{ L"Model"},
	//{ L"InterfaceType"},
	//{ L"Partitions"},
	//{ L"MediaType"},
	//{ L"MediaLoaded"},
	//{ L"PNPDeviceID"},
	//{ L"DeviceID"},
	//{ L"Size"},
	{ NULL}
};


long DiskCollector::CollectSingleDisk (IWbemClassObject * WMIDisk, NVDataItem *TargetItem, bool * KeepItem  )
{

	HRESULT hr;
	CComVariant ValueVariant;

	hr = WMIDisk->Get (CComBSTR("Size"),	// Name
							0,				// Flags
							&ValueVariant,	// return Variant
							NULL, NULL);    // Type and Origin (optional parms)

	
	if (ValueVariant.vt == VT_BSTR) {

		double DiskSize;
		WCHAR * StopLoc;
		DiskSize = wcstod(ValueVariant.bstrVal, &StopLoc);

		DiskSize = DiskSize / DiskAdj;

		char ConvertedSize[256];

		sprintf(ConvertedSize,"%.0f MB", DiskSize);

		TargetItem->AddNVItem("Size", ConvertedSize);
	}

	TargetItem->AddNVItem (DCI_TYPE, DCI_TYPE_FIXED);
	return ERROR_SUCCESS;
}

long DiskCollector::CollectSingleCDROM (IWbemClassObject * WMIDisk, NVDataItem *TargetItem, bool * KeepItem  )
{
	TargetItem->AddNVItem (DCI_TYPE, DCI_TYPE_REMOVABLE);
	return ERROR_SUCCESS;
}

long DiskCollector::Collect(NVDataItem **ReturnItem) 
{
	auto_ptr<NVDataItem> DataItems (new NVDataItem(DCL_TAG));

	WinSetupEnumerateDevClass ((GUID) GUID_DEVCLASS_CDROM, DCO_TAG, DataItems.get(), NULL);
	
	if (WMIServicesValid()) {
		WMIEnumerateClass(WMI_DISK_CLASSES, DCI_TAG, DataItems.get(), DiskEntries, CollectSingleDisk);
	}
	else 
		WinSetupEnumerateDevClass ((GUID) GUID_DEVCLASS_DISKDRIVE, DCI_TAG, DataItems.get(), NULL);


	*ReturnItem = DataItems.release();

#ifdef INSTRUMENTED
	MessageBox(NULL, "Disk Complete!", "Report", MB_OK);
#endif

	return ERROR_SUCCESS;
}

