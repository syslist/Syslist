#ifndef WMI_UTIL_H_INCLUDED
#define WMI_UTIL_H_INCLUDED

#include "../TestData/DataItem.h"

typedef struct WMIObjValueEntry {

	CComBSTR ObjAttr;
	char * DataName;
	char * DefaultVal;

	WMIObjValueEntry(BSTR InitAttr = NULL, char * InitDataName = NULL, char * InitDefault = NULL) :
		ObjAttr(InitAttr),
		DataName(InitDataName),
		DefaultVal(InitDefault)
	{
	}

} WMIObjValueEntry_t;


class WMIUtil
{
public:
	WMIUtil():m_WMITimeout(2000){};
	virtual ~WMIUtil(){};

	static long Init(BSTR Server = NULL, BSTR User = NULL, BSTR PWD = NULL, BSTR Locale = NULL, long SecFlags = 0L, BSTR Authority = NULL); 
	static long Uninit();
	static bool WMIServicesValid() { return m_WMIServices.p != NULL; }

	long WMIObjAddNVItem (IWbemClassObject * Obj, BSTR ObjAttr, NVDataItem * TargetData, char * DataName, char * DefaultVal = NULL);
	//long WMIEnumerateClass (BSTR ClassName, const char *ItemName, NVDataItem * DestItem, WMIObjValueEntry * AutoAttr = NULL, WMIObjCallback = NULL); 

protected:
	static CComPtr<IWbemServices> m_WMIServices;
	long m_WMITimeout;

protected:
	long WMICollectAuto (IWbemClassObject *Obj, NVDataItem * TargetData, WMIObjValueEntry * AutoAttr);

};

template <class T>
class WMIUtilLocal :
	public WMIUtil
{
protected:
	typedef long (T::* WMIObjCallback) (IWbemClassObject * Obj, NVDataItem * TargetData, bool * KeepItem);

	long WMIEnumerateClass (BSTR ClassName, char *ItemName, NVDataItem * DestItem, WMIObjValueEntry * AutoAttr = NULL, WMIObjCallback ManualCollect = NULL)
	{
		CComPtr<IEnumWbemClassObject> ClassEnum;

		HRESULT hr;

		hr = m_WMIServices->CreateInstanceEnum(
								ClassName,			// name of class
								0,
								NULL,
								&ClassEnum);	// pointer to enumerator
		if (FAILED(hr)) {
			return ERROR_GEN_FAILURE;
		}

		unsigned long NumReturned = 1; // assume at least one
		long Status;

		T* pT = static_cast<T*>(this);

		while (NumReturned > 0) {

			bool KeepItem = true;

			CComPtr<IWbemClassObject> CurrObj;

			hr = ClassEnum->Next(m_WMITimeout,	// timeout in two seconds
								1,				// return just one storage device
								&CurrObj,		// pointer to storage device
								&NumReturned); 	// number obtained: one or zero

			if (FAILED(hr) || NumReturned == 0 || CurrObj == NULL)
				break;

			auto_ptr<NVDataItem> CurrData (new NVDataItem(ItemName));

			if (AutoAttr != NULL) {
				Status = WMICollectAuto(CurrObj, CurrData.get(), AutoAttr);
				W32_RETURN_ON_ERROR(Status);
			}
			

			if (ManualCollect != NULL) {
				Status = (pT->*(ManualCollect))(CurrObj, CurrData.get(), &KeepItem);
				W32_RETURN_ON_ERROR(Status);
			}

			if (KeepItem) {
				DestItem->AddSubItem ( CurrData.release());
			}
		}

		return ERROR_SUCCESS;
	}
};
#endif
