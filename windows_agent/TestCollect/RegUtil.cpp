#include "stdafx.h"
#include "RegUtil.h"

static const long VAL_BUF_SIZE = 1024;

long RegUtil::RegCollectAuto (HKEY SourceKey, NVDataItem * TargetData, RegObjValueEntry * AutoAttr)
{
	if (AutoAttr == NULL)
		return ERROR_SUCCESS;

	RegObjValueEntry * CurrEntry;
	
	long Status;
	char * ResolvedDataName;

	for (CurrEntry = AutoAttr; CurrEntry->RegAttr != (char *) NULL; CurrEntry ++) {
		
		if (CurrEntry->DataName == NULL)
			ResolvedDataName = CurrEntry->RegAttr;
		else
			ResolvedDataName = CurrEntry->DataName;

		Status = RegObjAddNVItem( SourceKey, CurrEntry->RegAttr, TargetData, ResolvedDataName, CurrEntry->DefaultVal);
		//W32_RETURN_ON_ERROR(Status);
	}

	return ERROR_SUCCESS;
}

long RegUtil::RegObjAddNVItem (HKEY SourceKey, char * RegAttr, NVDataItem * TargetData, char * DataName, char * DefaultVal)
{
	USES_CONVERSION;

	long Status;

	DWORD ValType;
	wchar_t ValBuf[VAL_BUF_SIZE];
	DWORD ValBufLen = VAL_BUF_SIZE;

	wchar_t * wideRegAttr = A2W(RegAttr);

	Status = RegQueryValueExW(SourceKey, wideRegAttr, NULL, &ValType, (unsigned char *) ValBuf, &ValBufLen);
	W32_RETURN_ON_ERROR(Status);

	switch (ValType) {
	case REG_SZ:
		TargetData->AddNVItem(DataName, ValBuf);
		break;

	case REG_EXPAND_SZ:
		wchar_t ExpValBuf[VAL_BUF_SIZE];
		ExpandEnvironmentStringsW ((wchar_t *)ValBuf, ExpValBuf, VAL_BUF_SIZE);

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
