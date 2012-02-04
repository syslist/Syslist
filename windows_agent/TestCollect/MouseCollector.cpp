#include "stdafx.h"
#include "MouseCollector.h"


static char * DCML_TAG = "PointingDeviceList";
static char * DCMI_TAG = "PointingDevice";

static CComBSTR WMI_MOUSE_CLASSES(L"WIN32_PointingDevice");

static WMIObjValueEntry_t MouseEntries[] = {
	{ L"Manufacturer"},
	{ L"Name"},
	{ L"Description"},
	{ NULL}
};

long MouseCollector::CollectFallback(NVDataItem *DataItems) 
{
	WinSetupEnumerateDevClass((GUID) GUID_DEVCLASS_MOUSE, DCMI_TAG, DataItems, NULL);

	return ERROR_SUCCESS;
}

long MouseCollector::PreferredCollect(NVDataItem *DataItems) 

{
	WMIEnumerateClass(WMI_MOUSE_CLASSES, DCMI_TAG, DataItems, MouseEntries, NULL);

	return ERROR_SUCCESS;
}

long MouseCollector::Collect(NVDataItem **ReturnItem) 
{
	auto_ptr<NVDataItem> DataItems (new NVDataItem(DCML_TAG));

	if (!WMIServicesValid())
		CollectFallback(DataItems.get());
	else 
		PreferredCollect(DataItems.get());
		
	*ReturnItem = DataItems.release();

#ifdef INSTRUMENTED
	MessageBox(NULL, "Mouse Complete!", "Report", MB_OK);
#endif

	return ERROR_SUCCESS;
}

