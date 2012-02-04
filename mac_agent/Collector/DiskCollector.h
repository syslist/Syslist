#ifndef DISK_COLLECTOR_H_INCLUDED
#define DISK_COLLECTOR_H_INCLUDED

#include <CollectProto.h>

class DiskCollector :
	public AutoCreateDataCollector<DiskCollector>
{
public:
	DiskCollector() {}
	virtual ~DiskCollector() {}
	
	virtual long Collect(NVDataItem ** ReturnItem);
};


#endif
