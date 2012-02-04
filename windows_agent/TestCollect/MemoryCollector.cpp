#include "stdafx.h"
#include "MemoryCollector.h"


static char * DCML_TAG = "MemoryInfo";
static char * DCMI_TAG = "MemoryItem";

static char * DCML_TOTAL = "TotalMemory";
static char * DCMI_SIZE = "Size";

static CComBSTR WMI_MEMORY_CLASSES(L"WIN32_PhysicalMemory");

static const double MemAdj = (1<<20);  // 1 true MegaByte == 2^20 == 1<<20

static WMIObjValueEntry_t MemoryEntries[] = {
	{ L"Model"},
	{ L"Manufacturer"},
	{ L"Name"},
	{ L"Description"},
	{ L"Caption"},
	{ L"Speed"},
	{ L"TypeDetail"},
	{ L"FormFactor"},
	{ L"SerialNumber"},
	{ L"TotalWidth"},
	{ L"MemoryType"},
	{ NULL}
};

long MemoryCollector::CollectFallback(NVDataItem *DataItems) 
{
	MEMORYSTATUS MemInfoBuffer;
	
	GlobalMemoryStatus(&MemInfoBuffer);
	
	double TotalMemSize = MemInfoBuffer.dwTotalPhys;
	char ConvertedSize[64];

	TotalMemSize /= MemAdj;

	sprintf (ConvertedSize, "%.0f MB", TotalMemSize);

	DataItems->AddNVItem(DCML_TOTAL, ConvertedSize);

	return ERROR_SUCCESS;
}


long MemoryCollector::CollectSingleMemory (IWbemClassObject * WMIMemory, NVDataItem *TargetItem, bool * KeepItem  )
{

	HRESULT hr;
	CComVariant ValueVariant;
	char ConvertedSize[64];

	hr = WMIMemory->Get (CComBSTR("Capacity"),	// Name
							0,				// Flags
							&ValueVariant,	// return Variant
							NULL, NULL);    // Type and Origin (optional parms)

	switch (ValueVariant.vt) {

	case VT_BSTR: {

			double MemSize;
			WCHAR * StopLoc;
			MemSize = wcstod(ValueVariant.bstrVal, &StopLoc);

			m_TotalMemory += MemSize;
			MemSize /= MemAdj;
			sprintf(ConvertedSize,"%.0f MB", MemSize);

		}
		break;

	case VT_I4:
		m_TotalMemory += ValueVariant.lVal;
		ValueVariant.lVal /= MemAdj;
		sprintf(ConvertedSize,"%d MB", ValueVariant.lVal);
		break;

	case VT_I2:
		m_TotalMemory += ValueVariant.iVal;
		ValueVariant.iVal /= MemAdj;
		sprintf(ConvertedSize,"%d MB", ValueVariant.iVal);
		break;

	case VT_R4:
		m_TotalMemory += ValueVariant.fltVal;
		ValueVariant.fltVal /= MemAdj;
		sprintf(ConvertedSize,"%.0f MB", ValueVariant.fltVal);
		break;

	case VT_R8:
		m_TotalMemory += ValueVariant.dblVal;
		ValueVariant.dblVal /= MemAdj;
		sprintf(ConvertedSize,"%.0f MB", ValueVariant.dblVal);
		break;

	default:

		return ERROR_SUCCESS;
	}

	TargetItem->AddNVItem(DCMI_SIZE, ConvertedSize);
		
	return ERROR_SUCCESS;
}

long MemoryCollector::PreferredCollect(NVDataItem *DataItems) 
{
	m_TotalMemory = 0.0f;

	WMIEnumerateClass(WMI_MEMORY_CLASSES, DCMI_TAG, DataItems, MemoryEntries, CollectSingleMemory);

	m_TotalMemory = m_TotalMemory / MemAdj;

	char ConvertedSize[64];

	sprintf(ConvertedSize,"%.0f MB", m_TotalMemory);

	if (m_TotalMemory == 0.0) 
		return CollectFallback(DataItems);

	DataItems->AddNVItem(DCML_TOTAL, ConvertedSize);

	return ERROR_SUCCESS;
}

long MemoryCollector::Collect(NVDataItem **ReturnItem) 
{
	auto_ptr<NVDataItem> DataItems (new NVDataItem(DCML_TAG));

	if (!WMIServicesValid())
		CollectFallback(DataItems.get());
	else 
		PreferredCollect(DataItems.get());
		
	*ReturnItem = DataItems.release();

#ifdef INSTRUMENTED
	MessageBox(NULL, "Memory Complete!", "Report", MB_OK);
#endif
	return ERROR_SUCCESS;
}
