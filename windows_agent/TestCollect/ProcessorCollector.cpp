#include "stdafx.h"
#include "ProcessorCollector.h"

char PROC_INFO_PATH[] = "HARDWARE\\DESCRIPTION\\System\\CentralProcessor";
long PROC_INFO_PATH_LEN = sizeof (PROC_INFO_PATH);

char* PCL_TAG = "ProcessorList";
char* PC_TAG = "CPU";
char* PC_PROC_COUNT = "Count";

static RegObjValueEntry ProcessorItems[] = {
	RegObjValueEntry("ProcessorNameString", "Name"),
	RegObjValueEntry("Identifier", "ID"),
	RegObjValueEntry("~MHz", "RawSpeed"),
	RegObjValueEntry("VendorIdentifier", "Vendor"),
	RegObjValueEntry("UpdateStatus"),
	(NULL)
};

static const CComBSTR WMI_PROCESSOR_CLASSES(L"WIN32_Processor");

static WMIObjValueEntry_t ProcessorEntries[] = {
	WMIObjValueEntry_t(L"Manufacturer", "Vendor"),
	WMIObjValueEntry_t( L"Name"),
	WMIObjValueEntry_t(L"Description", "ID"),
	WMIObjValueEntry_t(L"MaxClockSpeed", "RawSpeed"),
	{ NULL}
};

long ProcessorCollector::CollectFallback(NVDataItem *DataItems) 
{
	long Status;

	Status = RegEnumerateSubKeys(HKEY_LOCAL_MACHINE, PROC_INFO_PATH, PC_TAG, DataItems, ProcessorItems, CollectSingleProcessor, CollectProcessorCount);
	W32_RETURN_ON_ERROR(Status);

	return ERROR_SUCCESS;
}

long ProcessorCollector::CollectSingleWMIProcessor (IWbemClassObject * WMIProcessor, NVDataItem *TargetItem, bool * KeepItem)
{
	CComVariant varProcessor;
	HRESULT hr;

	hr = WMIProcessor->Get(CComBSTR(L"Name"), 0, &varProcessor, NULL, NULL);

	if (FAILED(hr) || varProcessor.vt != VT_BSTR || varProcessor.bstrVal == NULL || SysStringLen(varProcessor.bstrVal) == 0) {
		*KeepItem = false;
		return ERROR_GEN_FAILURE;
	}

	wcslwr(varProcessor.bstrVal);

	wchar_t * FoundUnkStr;
	FoundUnkStr = wcsstr(varProcessor.bstrVal, L"unknown");

	if (FoundUnkStr != NULL) {
		*KeepItem = false;
		return ERROR_SUCCESS;
	}

	*KeepItem = true;

	RoundProcessorSpeed (TargetItem);

	return ERROR_SUCCESS;
}

long ProcessorCollector::PreferredCollect(NVDataItem *DataItems) 

{
	long Status;

	Status = WMIEnumerateClass(WMI_PROCESSOR_CLASSES , PC_TAG, DataItems, ProcessorEntries, CollectSingleWMIProcessor);

	char NumBuf[NUM_BUF_SIZE];

	if (Status == ERROR_SUCCESS && DataItems->SubItemCount() > 0)
		DataItems->AddNVItem(PC_PROC_COUNT, _itoa(DataItems->SubItemCount(), NumBuf, 10));

	return ERROR_SUCCESS;
}

long ProcessorCollector::CollectProcessorCount (long Count, NVDataItem * TargetData)
{
	char NumBuf[NUM_BUF_SIZE];
	TargetData->AddNVItem(PC_PROC_COUNT, _itoa(Count, NumBuf, 10));

	return ERROR_SUCCESS;
}

void ProcessorCollector::RoundProcessorSpeed (NVDataItem * TargetData )
{
	const char * RawSpeedString = NULL;
	TargetData->GetValueByName("RawSpeed", &RawSpeedString);

	if (RawSpeedString != NULL) {
		long RoundedSpeed = atoi (RawSpeedString);
		
		long RoundFactor = 1;

		if (RoundedSpeed > 1000)
			RoundFactor = 10;
		else if (RoundedSpeed > 100)
			RoundFactor = 5;

		RoundedSpeed = ((RoundedSpeed + (RoundFactor /2)) / RoundFactor) *  RoundFactor;

		char RoundedSpeedString [64];
		_itoa (RoundedSpeed, RoundedSpeedString, 10);

		TargetData->AddNVItem ("Speed", RoundedSpeedString);
	}
}

long ProcessorCollector::CollectSingleProcessor (HKEY SubKey, char * SubKeyName, NVDataItem * TargetData, bool * KeepItem)
{
	RoundProcessorSpeed (TargetData);
	
	return ERROR_SUCCESS;
}


long ProcessorCollector::Collect(NVDataItem **ReturnItem) 
{

	auto_ptr<NVDataItem> DataItems (new NVDataItem(PCL_TAG));

	if (WMIServicesValid()) {
		PreferredCollect(DataItems.get());
	}

	if (DataItems->SubItemCount() <= 0) {
		CollectFallback(DataItems.get());
	}

	*ReturnItem = DataItems.release();

#ifdef INSTRUMENTED
	MessageBox(NULL, "Processor Complete!", "Report", MB_OK);
#endif
	return ERROR_SUCCESS;
}