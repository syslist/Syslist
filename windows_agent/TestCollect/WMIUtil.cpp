#include "stdafx.h"
#include "WMIUtil.h"

CComPtr<IWbemServices> WMIUtil::m_WMIServices;

static const CComBSTR WMI_DEFAULT_SERVER(L"\\\\.\\root\\cimv2"); //  local root == "\\.\root\cimv2"


long WMIUtil::Init(BSTR Server, BSTR User, BSTR PWD, BSTR Locale, long SecFlags, BSTR Authority)
{
	BSTR ResolvedServer;

	if (Server == NULL)
		ResolvedServer = WMI_DEFAULT_SERVER;
	else
		ResolvedServer = Server;

	CComPtr<IWbemLocator> WMILocator;
	HRESULT hr;

	hr = WMILocator.CoCreateInstance(CLSID_WbemLocator, NULL, CLSCTX_INPROC_SERVER);
	if (FAILED(hr)) {
	//	MessageBox(NULL, "No Locator", "WMI", MB_OK);
		return ERROR_GEN_FAILURE;
	}
	
	hr = WMILocator->ConnectServer(ResolvedServer,
								User,  
								PWD,
								Locale,		// locale
								SecFlags,	// securityFlags
								Authority,	// authority (domain for NTLM)
								NULL,		// context
								&m_WMIServices);

	if (FAILED(hr))
		return ERROR_GEN_FAILURE;

	return ERROR_SUCCESS;
}

long WMIUtil::Uninit()
{
	m_WMIServices.Release();
	return ERROR_SUCCESS;
}

long WMIUtil::WMICollectAuto (IWbemClassObject *Obj, NVDataItem * TargetData, WMIObjValueEntry * AutoAttr)
{
	USES_CONVERSION;

	if (AutoAttr == NULL)
		return ERROR_SUCCESS;

	WMIObjValueEntry * CurrEntry;
	
	long Status;
	char * ResolvedDataName;

	for (CurrEntry = AutoAttr; CurrEntry->ObjAttr != (BSTR) NULL; CurrEntry ++) {
		
		if (CurrEntry->DataName == NULL)
			ResolvedDataName = OLE2A(CurrEntry->ObjAttr);
		else
			ResolvedDataName = CurrEntry->DataName;

		Status = WMIObjAddNVItem( Obj, CurrEntry->ObjAttr, TargetData, ResolvedDataName, CurrEntry->DefaultVal);
		//W32_RETURN_ON_ERROR(Status);
	}


	return ERROR_SUCCESS;
}


long WMIUtil::WMIObjAddNVItem (IWbemClassObject * Obj, BSTR ObjAttr, NVDataItem * TargetData, char * DataName, char * DefaultVal)
{
	USES_CONVERSION;

	char NumBuf[NUM_BUF_SIZE];

	CComVariant ValueVariant;

	HRESULT hr;

	hr = Obj->Get (ObjAttr,	// Name
					0,				// Flags
					&ValueVariant,	// return Variant
					NULL, NULL);    // Type and Origin (optional parms)

	if (FAILED(hr))
		return ERROR_GEN_FAILURE;

	//cout << "Partition VT = " << PartitionVariant.vt << endl;

	switch (ValueVariant.vt) {

	case VT_BSTR:
		TargetData->AddNVItem (DataName, ValueVariant.bstrVal);
		
		break;

	case VT_I4:
		TargetData->AddNVItem (DataName, _itoa(ValueVariant.lVal, NumBuf , 10));
		break;

	case VT_I2:
		TargetData->AddNVItem (DataName, _itoa(ValueVariant.iVal, NumBuf , 10));
		break;

	case VT_BOOL:
		TargetData->AddNVItem (DataName, ValueVariant.boolVal ? "Yes" : "No");
		break;

//	case VT_I8:
//		TargetData->AddNVItem (DataName, _i64toa(ValueVariant.ullVal, NumBuf , 10));
//		break;

	case VT_R4:
		TargetData->AddNVItem (DataName, _gcvt(ValueVariant.fltVal, 10, NumBuf));
		break;

	case VT_R8:
		TargetData->AddNVItem (DataName, _gcvt(ValueVariant.dblVal, 10, NumBuf));
		break;

	case VT_I1:
		TargetData->AddNVItem (DataName, _itoa(ValueVariant.bVal, NumBuf , 10));
		break;

	case VT_NULL:
	case VT_EMPTY:
		if (DefaultVal != NULL)
			TargetData->AddNVItem (DataName, DefaultVal);
		break;

	case (VT_BSTR | VT_ARRAY): 
		{
			if (ValueVariant.parray->cDims > 1)
				return ERROR_GEN_FAILURE;

			CComBSTR ConcatValue;
			BSTR * BSTRAccess;
			
			hr = SafeArrayAccessData (ValueVariant.parray, (void **) & BSTRAccess);
			if (FAILED(hr))
				return ERROR_GEN_FAILURE;

			long Index;
			long NumElements = ValueVariant.parray->rgsabound[0].cElements;

			for (Index = 0; Index < NumElements; Index ++) {

				if (Index > 0)
					ConcatValue.Append(L",");

				ConcatValue.AppendBSTR(BSTRAccess[Index]);

			}

			SafeArrayUnaccessData(ValueVariant.parray);

			TargetData->AddNVItem (DataName, ConcatValue);
		}

		break;

	case VT_I4 | VT_ARRAY:
	case VT_I2 | VT_ARRAY:
	default:
		assert(0);
		return ERROR_GEN_FAILURE;
	}

	return ERROR_SUCCESS;
}