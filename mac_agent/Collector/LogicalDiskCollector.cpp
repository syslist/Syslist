#include "LogicalDiskCollector.h"

#include <sys/param.h>
#include <sys/ucred.h>
#include <sys/mount.h>

#include <sstream>

static const char * LDCL_TAG = "LogicalDiskList";
static const char * LDCI_TAG = "LogicalDisk";

static const char * LDCI_TYPE = "Type";
static const char * LDCI_NAME = "Name";
static const char * LDCI_SIZE = "Size";
static const char * LDCI_FREE = "FreeSpace";
//static const char * LDCI_FSNAME = "FileSystem";
static const char * LDCI_VOLNAME = "VolumeName";

static const long long DiskAdj = 1000000L;

void ExtractNumberString (SInt64 SourceNum, std::string & Dest)
{
	ostringstream SizeConvert;

	Boolean WasMB = false;
	
	if (SourceNum > DiskAdj) {
		SourceNum /= DiskAdj;
		WasMB = true;
	}
	
	SizeConvert << SourceNum;
	
	if (WasMB == true)
		SizeConvert << " MB";

	Dest = SizeConvert.str();
}

long LogicalDiskCollector::Collect(NVDataItem ** ReturnItem)
{
	auto_ptr<NVDataItem> MountItems (new NVDataItem(LDCL_TAG));

	struct statfs * MountTable = NULL;
	
	int MntSize = getmntinfo(&MountTable, MNT_WAIT);
	if (MntSize < 0) 
		return ERROR_GEN_FAILURE;

	int MntIndex;
	struct statfs * CurrMount = MountTable;
	
	for (MntIndex = 0; MntIndex < MntSize; MntIndex++, CurrMount++) {
	
		if (CurrMount->f_bsize == 0 || CurrMount->f_blocks == 0)
			continue;
		
		if (strcasecmp(CurrMount->f_fstypename, "volfs") == 0
			|| strcasecmp(CurrMount->f_fstypename, "devfs") == 0
			|| strcasecmp(CurrMount->f_fstypename, "fdesc") == 0)
			continue;
			
		auto_ptr<NVDataItem> SingleMount (new NVDataItem(LDCI_TAG));
		
		SingleMount->AddNVItem(LDCI_TYPE, CurrMount->f_fstypename);
		SingleMount->AddNVItem(LDCI_VOLNAME, CurrMount->f_mntfromname);
		SingleMount->AddNVItem(LDCI_NAME, CurrMount->f_mntonname);
		
		std::string SizeString;
		SInt64 MountSize; 
		
		MountSize = (SInt64) CurrMount->f_blocks * (SInt64) CurrMount->f_bsize;
		ExtractNumberString(MountSize, SizeString);
		SingleMount->AddNVItem(LDCI_SIZE, SizeString.c_str());
		
		MountSize = (SInt64) CurrMount->f_bavail * (SInt64) CurrMount->f_bsize;
		ExtractNumberString(MountSize, SizeString);
		SingleMount->AddNVItem(LDCI_FREE, SizeString.c_str());
		
		MountItems->AddSubItem(SingleMount.release());
	}
	
	//BUG in BSD implementation: do not FREE
	//free (MountTable);
	
	*ReturnItem = MountItems.release();
	
#ifdef TRACE
	printf("logical disk Complete\n");
#endif	
	return ERROR_SUCCESS;
}
