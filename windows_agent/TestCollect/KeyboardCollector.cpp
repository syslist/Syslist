
#include "stdafx.h"
#include "KeyboardCollector.h"


static char * DCKL_TAG = "KeyboardList";
static char * DCKI_TAG = "KeyBoard";

static CComBSTR WMI_KEYBOARD_CLASSES(L"WIN32_KeyBoard");

static WMIObjValueEntry_t KeyboardEntries[] = {
	{ L"Manufacturer"},
	{ L"Name"},
	{ L"Description"},
	{ NULL}
};

long KeyboardCollector::CollectFallback(NVDataItem *DataItems) 
{
	WinSetupEnumerateDevClass((GUID) GUID_DEVCLASS_KEYBOARD, DCKI_TAG, DataItems, NULL);

	return ERROR_SUCCESS;
}
long KeyboardCollector::PreferredCollect(NVDataItem *DataItems) 

{
	WMIEnumerateClass(WMI_KEYBOARD_CLASSES, DCKI_TAG, DataItems, KeyboardEntries, NULL);

	return ERROR_SUCCESS;
}

long KeyboardCollector::Collect(NVDataItem **ReturnItem) 
{
	auto_ptr<NVDataItem> DataItems (new NVDataItem(DCKL_TAG));

	if (!WMIServicesValid())
		CollectFallback(DataItems.get());
	else 
		PreferredCollect(DataItems.get());
		
	*ReturnItem = DataItems.release();

#ifdef INSTRUMENTED
	MessageBox(NULL, "Keyboard Complete!", "Report", MB_OK);
#endif

	return ERROR_SUCCESS;
}

