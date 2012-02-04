#ifndef REG_UTIL_H_INCLUDED
#define REG_UTIL_H_INCLUDED

#include "../TestData/DataItem.h"
#include "AutoRegKey.h"
#include <memory>

typedef struct RegObjValueEntry {

	char * RegAttr;
	char * DataName;
	char * DefaultVal;

	RegObjValueEntry(char * InitAttr = NULL, char * InitDataName = NULL, char * InitDefault = NULL) :
		RegAttr(InitAttr),
		DataName(InitDataName),
		DefaultVal(InitDefault)
	{
	}

} RegObjValueEntry_t;

class RegUtil
{
public:
	long RegCollectAuto (HKEY SourceKey, NVDataItem * TargetData, RegObjValueEntry * AutoAttr);
	long RegObjAddNVItem (HKEY SourceKey, char * ObjAttr, NVDataItem * TargetData, char * DataName, char * DefaultVal);

	virtual ~RegUtil() {};
};



template <class T>
class RegUtilLocal :
	RegUtil
{
protected:
	typedef long (T::* EnumSubKeyItemCallback) (HKEY SubKey, char * SubKeyOnlyName, NVDataItem * TargetData, bool * KeepItem);
	typedef long (T::* EnumSubKeyCountCallback) (long Count, NVDataItem * TargetData);

	long RegEnumerateSubKeys(HKEY RootKey, char *KeyName, char *ItemName, NVDataItem * TargetData, RegObjValueEntry * AutoItems, EnumSubKeyItemCallback ItemCallback = NULL, EnumSubKeyCountCallback CountCallback = NULL )
	{

		AutoRegKey EnumKey;
		long Status;

		Status = RegOpenKeyEx (RootKey, KeyName, 0, KEY_READ, &EnumKey);
		W32_RETURN_ON_ERROR(Status);
		
		DWORD ItemCount = 0;

		Status = RegQueryInfoKey(EnumKey, // HKEY
								 NULL, // lpClass
								 NULL, // lpcbClass
								 NULL, // lpReserved
								 &ItemCount, //lpcSubKeys
								 NULL, // lpcbMaxSubKeyLen
								 NULL, // lpcbMaxClassLen
								 NULL, // lpcValues 
								 NULL, // lpcbMaxValueNameLen
								 NULL, // lpcbMaxValueLen
								 NULL, // lpcbSecurityDescriptor
								 NULL); // lpftLastWriteTime
		W32_RETURN_ON_ERROR(Status);

		T* pT = static_cast<T*>(this);

		if (CountCallback != NULL) {
			Status = (pT->*(CountCallback))(ItemCount, TargetData);
			W32_RETURN_ON_ERROR(Status);
		}

		long EnumIndex;
		char EnumSubKeyNameBuf[256];

		DWORD EnumSubKeyNameLen;

		for (EnumIndex = 0; EnumIndex < ItemCount; EnumIndex ++) {

			bool KeepItem = true;
			
			EnumSubKeyNameLen = 256;

			Status = RegEnumKeyExA(EnumKey, EnumIndex, EnumSubKeyNameBuf, &EnumSubKeyNameLen, NULL, NULL, NULL, NULL);
			if (Status == ERROR_NO_MORE_ITEMS) 
				break;
			else {
				W32_RETURN_ON_ERROR(Status);
			}

			AutoRegKey EnumSubKey;
			Status = RegOpenKeyEx (EnumKey, EnumSubKeyNameBuf, 0, KEY_READ, &EnumSubKey);
			W32_RETURN_ON_ERROR(Status);

			auto_ptr<NVDataItem> CurrData (new NVDataItem(ItemName));

			if (AutoItems != NULL) {
				Status = RegCollectAuto(EnumSubKey, CurrData.get(), AutoItems);
				W32_RETURN_ON_ERROR(Status);
			}

			if (ItemCallback != NULL) {
				Status = (pT->*(ItemCallback))(EnumSubKey, EnumSubKeyNameBuf, CurrData.get(), & KeepItem); 
				W32_RETURN_ON_ERROR(Status);
			}

			if (KeepItem) {
				TargetData->AddSubItem(CurrData.release());
			}

		}

		return ERROR_SUCCESS;
	}


};
#endif
