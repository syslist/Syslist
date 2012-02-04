#ifndef LOGICAL_DISK_COLLECTOR_H_INCLUDED
#define LOGICAL_DISK_COLLECTOR_H_INCLUDED

#include <CollectProto.h>

class LogicalDiskCollector:
	public AutoCreateDataCollector<LogicalDiskCollector>
{
public:
	LogicalDiskCollector() {}
	virtual ~LogicalDiskCollector() {}
	
	virtual long Collect(NVDataItem ** ReturnItem); 
};


#endif
