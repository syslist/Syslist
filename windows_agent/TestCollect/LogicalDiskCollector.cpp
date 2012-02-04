#include "stdafx.h"
#include "LogicalDiskCollector.h"


static char * LDCL_TAG = "LogicalDiskList";
static char * LDCI_TAG = "LogicalDisk";

static char * LDCI_TYPE = "Type";
static char * LDCI_NAME = "Name";
static char * LDCI_SIZE = "Size";
static char * LDCI_FREE = "FreeSpace";
static char * LDCI_FSNAME = "FileSystem";
static char * LDCI_VOLNAME = "VolumeName";

static const __int64 DiskAdj = 1e6;


long LogicalDiskCollector::Collect(NVDataItem **ReturnItem) 
{
	auto_ptr<NVDataItem> DataItems (new NVDataItem(LDCL_TAG));
	
	long DriveStrLen = 0;
	wchar_t dummy[1];

	DriveStrLen = GetLogicalDriveStringsW (0, dummy);

	auto_ptr<wchar_t> DriveDest(new wchar_t[DriveStrLen + 1]);

	DriveStrLen = GetLogicalDriveStringsW (DriveStrLen, DriveDest.get());

	if (DriveStrLen == 0) 
		return ERROR_GEN_FAILURE;

	for (wchar_t * CurrItem = DriveDest.get();
		 *CurrItem != 0;
		 CurrItem += (wcslen(CurrItem) + 1)) {

		auto_ptr<NVDataItem> CurrDisk (new NVDataItem(LDCI_TAG));
		
		CurrDisk->AddNVItem(LDCI_NAME, CurrItem);

		wchar_t VolName[257];
		wchar_t FileSysName[257];
		DWORD VolSerialNum;
		DWORD MaxVolCompLen;
		DWORD FileSystemFlags;
		
		BOOL Success;


 
		UINT DriveType = GetDriveTypeW(CurrItem);
		char * AddType;

		switch (DriveType) {

		case DRIVE_FIXED:
			AddType = "Fixed";
			break;

		case DRIVE_RAMDISK:
			AddType = "RAM Disk";
			break;

		case DRIVE_CDROM:
		case DRIVE_UNKNOWN:
		case DRIVE_NO_ROOT_DIR:
		case DRIVE_REMOVABLE:
		default:
			continue;
			break;
		}

		CurrDisk->AddNVItem(LDCI_TYPE, AddType);

		Success = GetVolumeInformationW (CurrItem, 
										VolName, 257, 
										&VolSerialNum, 
										&MaxVolCompLen,
										&FileSystemFlags,
										FileSysName, 257);

		if (!Success) 
			continue;

		CurrDisk->AddNVItem(LDCI_VOLNAME, VolName);
		CurrDisk->AddNVItem(LDCI_FSNAME, FileSysName);

		ULARGE_INTEGER UserFree = {0};
		ULARGE_INTEGER DiskSize;
		ULARGE_INTEGER DiskFree;


		Success = GetDiskFreeSpaceExW(CurrItem, &UserFree, &DiskSize, &DiskFree);

		if (Success) {

			DiskSize.QuadPart /= DiskAdj;
			DiskFree.QuadPart /= DiskAdj;

			char ConvertedSize[256];			
			
			sprintf(ConvertedSize,"%I64u MB", DiskSize.QuadPart);

			CurrDisk->AddNVItem(LDCI_SIZE, ConvertedSize);
			
			sprintf(ConvertedSize,"%I64u MB", DiskFree.QuadPart);

			CurrDisk->AddNVItem(LDCI_FREE, ConvertedSize);


		}

		DataItems->AddSubItem(CurrDisk.release());
	}

	*ReturnItem = DataItems.release();

#ifdef INSTRUMENTED
	MessageBox(NULL, "Logicial Disk Complete!", "Report", MB_OK);
#endif

	return ERROR_SUCCESS;
}

