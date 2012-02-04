#ifndef WIN_SETUP_UTIL_H_INCLUDED
#define WIN_SETUP_UTIL_H_INCLUDED

#include "../TestData/DataItem.h"
#include <memory>

#define INVALID_DEV_CLASS -1
#define INVALID_SETUP_ATTR -1

typedef struct WinSetupEntry {

	DWORD SetupAttr;
	char * DataName;
	char * DefaultVal;

	WinSetupEntry(DWORD InitAttr = INVALID_DEV_CLASS, char * InitDataName = NULL, char * InitDefault = NULL) :
		SetupAttr(InitAttr),
		DataName(InitDataName),
		DefaultVal(InitDefault)
	{
	}

} WinSetupEntry_t;


class WinSetupClient
{
protected:
static WinSetupEntry s_DefaultEntries[];

public:
	long WinSetupCollectAuto (HDEVINFO  DeviceInfoSet, PSP_DEVINFO_DATA  DeviceInfoData, NVDataItem * TargetData, WinSetupEntry * AutoAttr);
	long WinSetupObjAddNVItem (HDEVINFO  DeviceInfoSet, PSP_DEVINFO_DATA  DeviceInfoData, DWORD SetupAttr, NVDataItem * TargetData, char * DataName, char * DefaultVal);

	virtual ~WinSetupClient() {};
};



template <class T>
class WinSetupClientLocal :
	WinSetupClient
{
protected:
	typedef long (T::* WinSetupCallback) (HDEVINFO  DeviceInfoSet, PSP_DEVINFO_DATA  DeviceInfoData, NVDataItem * TargetData, bool * KeepItem);

	long WinSetupEnumerateDevClass(GUID & DevClassGUID, char *ItemName, NVDataItem * TargetData, WinSetupEntry * AutoItems = NULL, WinSetupCallback ItemCallback = NULL)
	{
		long Status;

		HDEVINFO hDevInfo;
		SP_DEVINFO_DATA DeviceInfoData;
		DWORD i;

		// Create a HDEVINFO with all present devices.
		hDevInfo = SetupDiGetClassDevs(&DevClassGUID,
		   0, // Enumerator
		   0,
		   DIGCF_PRESENT );

		if (hDevInfo == INVALID_HANDLE_VALUE)
		{
		   // Insert error handling here.
		   return 1;
		}

		T* pT = static_cast<T*>(this);

		// Enumerate through all devices in Set.
		DeviceInfoData.cbSize = sizeof(SP_DEVINFO_DATA);
		for (i=0;
			SetupDiEnumDeviceInfo(hDevInfo,i, &DeviceInfoData);
			i++)
		{
			bool KeepItem = true;
			
			auto_ptr<NVDataItem> CurrData (new NVDataItem(ItemName));

			Status = WinSetupCollectAuto(hDevInfo, &DeviceInfoData, CurrData.get(), s_DefaultEntries);
			W32_RETURN_ON_ERROR(Status);

			if (AutoItems != NULL) {
				Status = WinSetupCollectAuto(hDevInfo, &DeviceInfoData, CurrData.get(), AutoItems);
				W32_RETURN_ON_ERROR(Status);
			}

			if (ItemCallback != NULL) {
				Status = (pT->*(ItemCallback))(hDevInfo, &DeviceInfoData, CurrData.get(), & KeepItem); 
				W32_RETURN_ON_ERROR(Status);
			}

			if (KeepItem) {
				TargetData->AddSubItem(CurrData.release());
			}
		}

		SetupDiDestroyDeviceInfoList(hDevInfo);

		return ERROR_SUCCESS;
	}


};
#endif
