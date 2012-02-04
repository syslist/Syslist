#include "stdafx.h"
#include "WinSetupUtil.h"

WinSetupEntry WinSetupClient::s_DefaultEntries[] = {
	WinSetupEntry(SPDRP_FRIENDLYNAME, "Description"),
	WinSetupEntry(SPDRP_DEVICEDESC, "Description"),
	WinSetupEntry(SPDRP_MFG, "Manufacturer"),
	WinSetupEntry(SPDRP_LOCATION_INFORMATION, "LocationInfo"),
	WinSetupEntry(SPDRP_PHYSICAL_DEVICE_OBJECT_NAME, "PhysDevName"),
	WinSetupEntry(SPDRP_ADDRESS, "ADDRESS"),
	{ NULL}
};


static const long VAL_BUF_SIZE = 1024;

long WinSetupClient::WinSetupCollectAuto (HDEVINFO  DeviceInfoSet, PSP_DEVINFO_DATA  DeviceInfoData, NVDataItem * TargetData, WinSetupEntry * AutoAttr)
{
	if (AutoAttr == NULL)
		return ERROR_SUCCESS;

	WinSetupEntry * CurrEntry;
	
	long Status;

	for (CurrEntry = AutoAttr; CurrEntry->DataName != NULL; CurrEntry ++) {
		
		Status = WinSetupObjAddNVItem( DeviceInfoSet, DeviceInfoData, CurrEntry->SetupAttr, TargetData, CurrEntry->DataName, CurrEntry->DefaultVal);
		//W32_RETURN_ON_ERROR(Status);
	}

	return ERROR_SUCCESS;
}

long WinSetupClient::WinSetupObjAddNVItem (HDEVINFO  DeviceInfoSet, PSP_DEVINFO_DATA  DeviceInfoData, DWORD SetupAttr, NVDataItem * TargetData, char * DataName, char * DefaultVal)
{

	DWORD ValType;
	wchar_t ValBuf[VAL_BUF_SIZE];
	DWORD ValBufLen = VAL_BUF_SIZE;

	long Status;
	const char * TestReturn = NULL;
	Status = TargetData->GetValueByName(DataName, &TestReturn);
	if (Status == 1)
		return ERROR_SUCCESS;

	if (SetupDiGetDeviceRegistryPropertyW(
           DeviceInfoSet,
           DeviceInfoData,
           SetupAttr,
           &ValType,
           (PBYTE)ValBuf,
           ValBufLen,
           &ValBufLen) == FALSE) {

		return ERROR_GEN_FAILURE;
	}
	
	switch (ValType) {
	case REG_SZ:
		TargetData->AddNVItem(DataName, ValBuf);

		break;

	case REG_EXPAND_SZ:
		wchar_t ExpValBuf[VAL_BUF_SIZE];
		ExpandEnvironmentStringsW((const wchar_t *)ValBuf, ExpValBuf, VAL_BUF_SIZE);

		TargetData->AddNVItem(DataName, ExpValBuf);

		break;

	//case REG_DWORD_REG_DWORD_LITTLE_ENDIAN:
	case REG_DWORD:
		char NumBuf[NUM_BUF_SIZE];

		TargetData->AddNVItem(DataName, _itoa(*(int*) ValBuf, NumBuf, 10));
		break;

	default:
		return ERROR_GEN_FAILURE;
	}

	return ERROR_SUCCESS;
}
