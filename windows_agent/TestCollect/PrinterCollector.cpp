#include "stdafx.h"
#include "PrinterCollector.h"


static char * DCPL_TAG = "PrinterList";
static char * DCPI_TAG = "Printer";

static CComBSTR WMI_PRINTER_CLASSES(L"WIN32_Printer");

static WMIObjValueEntry_t PrinterEntries[] = {
	{ L"Manufacturer"},
	{ L"Name"},
	{ L"Description"},
	{ L"Attributes"},
	{ L"PortName"},
	{ L"Hidden"},
	{ L"PNPDeviceID"},
	{ L"Caption"},
	{ NULL}
};

long PrinterCollector::CollectSinglePrinter (IWbemClassObject * WMIPrinter, NVDataItem *TargetItem, bool * KeepItem  )
{
	HRESULT hr;
	CComVariant ValueVariant;

	hr = WMIPrinter->Get (CComBSTR("Attributes"),	// Name
							0,				// Flags
							&ValueVariant,	// return Variant
							NULL, NULL);    // Type and Origin (optional parms)

	if (FAILED(hr))
		return  hr;

	if (ValueVariant.vt != VT_I4 || (ValueVariant.lVal & 0x10)) {
		*KeepItem = false;
		return ERROR_SUCCESS;
	}
	
	return ERROR_SUCCESS;
}

long PrinterCollector::PreferredCollect(NVDataItem *DataItems) 

{
	WMIEnumerateClass(WMI_PRINTER_CLASSES, DCPI_TAG, DataItems, PrinterEntries, CollectSinglePrinter);

	return ERROR_SUCCESS;
}

long PrinterCollector::CollectFallback(NVDataItem *DataItems) 
{
	WinSetupEnumerateDevClass((GUID) GUID_DEVCLASS_PRINTER, DCPI_TAG, DataItems, NULL);

	return ERROR_SUCCESS;
}


long PrinterCollector::Collect(NVDataItem **ReturnItem) 
{
	auto_ptr<NVDataItem> DataItems (new NVDataItem(DCPL_TAG));

	if (!WMIServicesValid())
		CollectFallback(DataItems.get());
	else 
		PreferredCollect(DataItems.get());
		
	*ReturnItem = DataItems.release();

#ifdef INSTRUMENTED
	MessageBox(NULL, "Printer Complete!", "Report", MB_OK);
#endif

	return ERROR_SUCCESS;
}

