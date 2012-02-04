#include "stdafx.h"
#include "DisplayCollector.h"

static char * DISPCL_TAG = "DisplayAdaptorList";
static char * DISPCI_TAG = "DisplayAdaptor";

static char * DISP_MFR = "Manufacturerer";
static char * DISP_DESC = "Description";
static char * DISP_INTDESC = "IntDescription";
static char * DISP_FID = "FileID";


long DisplayCollector::Collect(NVDataItem **ReturnItem) 
{
	auto_ptr<NVDataItem> DataItems (new NVDataItem(DISPCL_TAG));
	
	WinSetupEnumerateDevClass ((GUID) GUID_DEVCLASS_DISPLAY, DISPCI_TAG, DataItems.get());

	if (DataItems->SubItemCount() == 0)
		CollectFallback(DataItems.get());

	*ReturnItem = DataItems.release();

	return ERROR_SUCCESS;
}

long DisplayCollector::CollectFallback(NVDataItem * TargetItem)
{
	DISPLAY_DEVICEW CurrDevInfo;
	long CurrDevIndex;

	for (CurrDevIndex = 0, CurrDevInfo.cb = sizeof (DISPLAY_DEVICE);
		EnumDisplayDevicesW(NULL, CurrDevIndex, &CurrDevInfo, 0);
		CurrDevIndex ++, CurrDevInfo.cb = sizeof (DISPLAY_DEVICE))
	{
		
		if ((CurrDevInfo.StateFlags & DISPLAY_DEVICE_MIRRORING_DRIVER) == DISPLAY_DEVICE_MIRRORING_DRIVER)
			continue;

		auto_ptr<NVDataItem> ReportItem (new NVDataItem(DISPCI_TAG));
		
		ReportItem->AddNVItem(DISP_DESC, CurrDevInfo.DeviceString);
		ReportItem->AddNVItem(DISP_FID, CurrDevInfo.DeviceName);
		TargetItem->AddSubItem(ReportItem.release());

	}

#ifdef INSTRUMENTED
	MessageBox(NULL, "Display Complete!", "Report", MB_OK);
#endif
	return ERROR_SUCCESS;
}

