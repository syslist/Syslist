#include "MemoryCollector.h"
#include <sys/sysctl.h>
#include <mach/mach.h>

#include <sstream> 

static const char * DCML_TAG = "MemoryInfo";
//static const char * DCMI_TAG = "MemoryItem";

static const char * DCML_TOTAL = "TotalMemory";
//static const char * DCMI_SIZE = "Size";


long MemoryCollector::Collect(NVDataItem ** ReturnItem)
{
	auto_ptr<NVDataItem> MemoryList (new NVDataItem(DCML_TAG));
	
	long Status;

	uint64_t TotalMem;
	int SearchMIB[2];
	size_t DataSize;

	SearchMIB[0] = CTL_HW;	
	SearchMIB[1] = HW_MEMSIZE;
	DataSize = sizeof(TotalMem);
	
	Status = sysctl(SearchMIB, 2, &TotalMem, &DataSize, NULL, 0);
	if (Status != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;
	
	TotalMem >>= 20; // divide by a 2 ^ 20 for Memory MB
	
	ostringstream TotalMemConv;
	TotalMemConv << TotalMem << " MB";
	
	MemoryList->AddNVItem(DCML_TOTAL, TotalMemConv.str().c_str());
	
#ifdef TRACE
	printf("Memory Complete\n");
#endif
	*ReturnItem = MemoryList.release();
	
	return ERROR_SUCCESS;
}
