#include "stdafx.h"
#include "SoftwareCollector.h"
#include "KeyDecode.h"
#include <sstream>

char SW_INFO_PATH[] = "Software\\Microsoft\\Windows\\CurrentVersion\\Uninstall";
char SW_MS_OFFICE_PATH[] = "Software\\Microsoft\\Office";
long SW_INFO_PATH_LEN = sizeof (SW_INFO_PATH);

char* SWCL_TAG = "SoftwareList";
char* SWC_TAG = "Program";

static RegObjValueEntry ProcessorItems[] = {
	RegObjValueEntry("DisplayName"),
	RegObjValueEntry("DisplayVersion"),
	RegObjValueEntry("Publisher"),
	RegObjValueEntry("VersionMajor"),
	RegObjValueEntry("VersionMinor"),
	(NULL)
};

static std::map<std::string,std::string> MSOffice10PlusMap;
static std::map<std::string,std::string> MSOffice10PlusMapID;
static std::map<int,std::string> MSOfficePre10Map;


long PopulateOfficeMap()
{
		AutoRegKey EnumKey;
		long Status;
#ifdef INSTRUMENTED
	MessageBox (NULL, "Office Zero", "Report", MB_OK);
#endif
		Status = RegOpenKeyEx (HKEY_LOCAL_MACHINE, SW_MS_OFFICE_PATH, 0, KEY_READ, &EnumKey);
		W32_RETURN_ON_ERROR(Status);
		
#ifdef INSTRUMENTED
	MessageBox (NULL, "Office Alpha", "Report", MB_OK);
#endif
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


#ifdef INSTRUMENTED
	MessageBox (NULL, "Office Beta", "Report", MB_OK);
#endif
		long EnumIndex;
		char EnumSubKeyNameBuf[256];

		DWORD EnumSubKeyNameLen;

		for (EnumIndex = 0; EnumIndex < ItemCount; EnumIndex ++) {
			
			EnumSubKeyNameLen = 256;

			Status = RegEnumKeyExA(EnumKey, EnumIndex, EnumSubKeyNameBuf, &EnumSubKeyNameLen, NULL, NULL, NULL, NULL);
			if (Status == ERROR_NO_MORE_ITEMS) 
				break;
			else {
				W32_RETURN_ON_ERROR(Status);
			}
#ifdef INSTRUMENTED_DEEP
	MessageBox (NULL, "Office Gamma", "Report", MB_OK);
#endif			
			// Skip it this entry if it was not a version number
			if (!isdigit(EnumSubKeyNameBuf[0])) {
				continue;
			}

			int VersionNum;
			int ScanCount = 0;
			ScanCount = sscanf (EnumSubKeyNameBuf,"%d", &VersionNum);
			if (ScanCount != 1) {
				continue;
			}
			
			AutoRegKey EnumSubKey;
			std::ostringstream RegInfoSubPath;
			
			RegInfoSubPath << SW_MS_OFFICE_PATH << "\\" << EnumSubKeyNameBuf << "\\" << "Registration";

			// Pre version 10 cuts straight to the chase with a sub key called ProductID
			if (VersionNum < 10) {
				RegInfoSubPath << "\\" << "ProductID";
			}

			Status = RegOpenKeyEx (HKEY_LOCAL_MACHINE, RegInfoSubPath.str().c_str(), 0, KEY_READ, &EnumSubKey);

			if (Status != ERROR_SUCCESS) {
				continue;
			}
#ifdef INSTRUMENTED_DEEP
	MessageBox (NULL, "Office Delta", "Report", MB_OK);
#endif
			if (VersionNum < 10) {
				// Old Style Prog ID extraction - The registration
				// key has the information we need - simply extract
				// it and add to the pre version 10 table - We index
				// off of the version number later to match it up

				char ProdID[256];
				unsigned long ItemSize = 256;
				DWORD ItemType = REG_SZ;

#ifdef INSTRUMENTED_DEEP
	MessageBox (NULL, "Office EpsilonAlpha", "Report", MB_OK);
#endif
				Status = RegQueryValueEx(
					EnumSubKey,	// handle to key to query
					NULL,		// address of name of value to query
					NULL,		// reserved
					&ItemType,  // address of buffer for value type
					(unsigned char *) ProdID,     // address of data buffer
					&ItemSize	// address of data buffer size
				);

				if (Status != ERROR_SUCCESS) {
					continue;
				}
#ifdef INSTRUMENTED_DEEP
	MessageBox (NULL, "Office EpsilonBeta", "Report", MB_OK);
#endif
				MSOfficePre10Map[VersionNum] = std::string(ProdID);
			}
			else {
				// New Style Prog ID extraction - The registration
				// key has the information in a series of GUID
				// indexed sub keys, Those GUID keys have a ProductID
				// value that has what we need - we index off of the
				// GUID later to match it up
				
				unsigned long RegCount = 0;

				Status = RegQueryInfoKey(EnumSubKey, // HKEY
						 NULL, // lpClass
						 NULL, // lpcbClass
						 NULL, // lpReserved
						 &RegCount, //lpcSubKeys
						 NULL, // lpcbMaxSubKeyLen
						 NULL, // lpcbMaxClassLen
						 NULL, // lpcValues 
						 NULL, // lpcbMaxValueNameLen
						 NULL, // lpcbMaxValueLen
						 NULL, // lpcbSecurityDescriptor
						 NULL); // lpftLastWriteTime

				if (Status != ERROR_SUCCESS) {
					continue;
				}

				// Iterate over the GUID keys to extract the ProductID keys
				for (long SubEnumIndex = 0; SubEnumIndex < RegCount; SubEnumIndex ++) {
					
					EnumSubKeyNameLen = 256;
					char ProdGUID[256];

#ifdef INSTRUMENTED_DEEP
	MessageBox (NULL, "Office IotaAlpha", "Report", MB_OK);
#endif					
	
					Status = RegEnumKeyExA(EnumSubKey, SubEnumIndex, ProdGUID, &EnumSubKeyNameLen, NULL, NULL, NULL, NULL);
					if (Status == ERROR_NO_MORE_ITEMS) {
						break;
					}
					else if (Status != ERROR_SUCCESS){
						continue;
					}

					AutoRegKey ProdIDKey;


#ifdef INSTRUMENTED_DEEP
	MessageBox (NULL, "Office IotaBeta", "Report", MB_OK);
#endif
					Status = RegOpenKeyEx(EnumSubKey, ProdGUID, 0, KEY_READ, &ProdIDKey);
					if (Status != ERROR_SUCCESS) {
						continue;
					}

					char ProdID[256];
					unsigned long ItemSize = 256;
					DWORD ItemType = REG_SZ;
#ifdef INSTRUMENTED_DEEP
	MessageBox (NULL, "Office IotaDelta", "Report", MB_OK);
#endif
					Status = RegQueryValueEx(
						ProdIDKey,		// handle to key to query
						"ProductID",	// address of name of value to query
						NULL,			// reserved
						&ItemType,		// address of buffer for value type
						(unsigned char *) ProdID,     // address of data buffer
						&ItemSize		// address of data buffer size
					);

					if (Status == ERROR_SUCCESS) {
						MSOffice10PlusMap[std::string(ProdGUID)] = std::string(ProdID);
					}

					ItemSize = 256;
					ItemType = REG_BINARY;
					Status = RegQueryValueEx(
						ProdIDKey,		// handle to key to query
						"DigitalProductID",	// address of name of value to query
						NULL,			// reserved
						&ItemType,		// address of buffer for value type
						(unsigned char *) ProdID,     // address of data buffer
						&ItemSize		// address of data buffer size
					);

					if (Status == ERROR_SUCCESS) {
						char decodeKey[kDecodeKeyLen + 1];
						if (DecodeMSKeyReg(decodeKey, ProdID) == ERROR_SUCCESS) {
							MSOffice10PlusMapID[std::string(ProdGUID)] = std::string(decodeKey);
						}
					}

				}
			}
		}
		
#ifdef INSTRUMENTED
	MessageBox (NULL, "Office EXIT!", "Report", MB_OK);
#endif
			return ERROR_SUCCESS;
}

long SoftwareCollector::CollectSingleProgram (HKEY SubKey, char * SubKeyName, NVDataItem * TargetData, bool * KeepItem)
{
	const char* DispName = NULL;
	TargetData->GetValueByName("DisplayName", & DispName);

	if (DispName == NULL) {
		*KeepItem = false;
		return ERROR_SUCCESS;
	}

	if (strstr(DispName, "Microsoft Office") != NULL) {
		
		const char * VersionString = NULL;
		TargetData->GetValueByName ("DisplayVersion", & VersionString);
		if (VersionString == NULL)
			return ERROR_SUCCESS;

		int ScanCount = 0;
		int VersionNum = 0;

		ScanCount = sscanf(VersionString,"%d",& VersionNum);
		if (ScanCount != 1)
			return ERROR_SUCCESS;

		if (VersionNum < 10) {
			std::map<int, std::string>::iterator VersionItem;
			VersionItem = MSOfficePre10Map.find(VersionNum);
			if (VersionItem == MSOfficePre10Map.end())
				return ERROR_SUCCESS;

			TargetData->AddNVItem("SerialNumber", VersionItem->second.c_str());
		}
		else {
			std::map<std::string, std::string>::iterator VersionItem;
			VersionItem = MSOffice10PlusMap.find(std::string(SubKeyName));
			if (VersionItem != MSOffice10PlusMap.end()) {
				TargetData->AddNVItem("SerialNumber", VersionItem->second.c_str());
			}

			VersionItem = MSOffice10PlusMapID.find(std::string(SubKeyName));
			if (VersionItem != MSOffice10PlusMapID.end()) {
				TargetData->AddNVItem("ProductKey", VersionItem->second.c_str());
			}

		}

		return ERROR_SUCCESS;

	}

	const char * MfrName = NULL;

	TargetData->GetValueByName ("Publisher", & MfrName);

	if (MfrName != NULL && strstr(MfrName, "Adobe") != NULL) {

		long Status;
		DWORD ItemType = REG_SZ;
		char SerialFromKey[256] = {0};
		unsigned long ItemSize = 256;

		Status = RegQueryValueEx(
					SubKey,
					"SERIAL",
					NULL,
					&ItemType,
					(unsigned char *) SerialFromKey,
					&ItemSize);
		
		if (Status != ERROR_SUCCESS) {
			return ERROR_SUCCESS;
		}

		TargetData->AddNVItem("SerialNumber", SerialFromKey);
	}

	
	return ERROR_SUCCESS;
}

long SoftwareCollector::Collect(NVDataItem **ReturnItem) 
{
#ifdef INSTRUMENTED
	MessageBox (NULL, "Software STARTING!", "Report", MB_OK);
#endif
	auto_ptr<NVDataItem> DataItems (new NVDataItem(SWCL_TAG));
	long Status;

#ifdef INSTRUMENTED
	MessageBox(NULL, "Starting Office Detection", "Report", MB_OK);
#endif

	Status = PopulateOfficeMap();

#ifdef INSTRUMENTED
	MessageBox(NULL, "Finished Office Starting Main!", "Report", MB_OK);
#endif

	Status = RegEnumerateSubKeys(HKEY_LOCAL_MACHINE, SW_INFO_PATH, SWC_TAG, DataItems.get(), ProcessorItems, CollectSingleProgram, NULL);
	W32_RETURN_ON_ERROR(Status);

	*ReturnItem = DataItems.release();

#ifdef INSTRUMENTED
	MessageBox(NULL, "Software Complete!", "Report", MB_OK);
#endif
	return ERROR_SUCCESS;
}