#ifndef LOG_DISK_COLLECT_H_INCLUDED
#define LOG_DISK_COLLECT_H_INCLUDED

#include "CollectProto.h"
#include "WMIUtil.h"


class LogicalDiskCollector:
	public AutoCreateDataCollector<LogicalDiskCollector>,
	public WMIUtilLocal<LogicalDiskCollector>
{
public:
	LogicalDiskCollector() 
	{
	}


	long Collect(NVDataItem ** ReturnItem);
	virtual ~LogicalDiskCollector() {};

private:


};
#endif