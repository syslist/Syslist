#ifndef SYSLIST_OS_COLLECTOR_H_INCLUDED
#define SYSLIST_OS_COLLECTOR_H_INCLUDED

#include "CollectProto.h"

class OSCollector:
	public AutoCreateDataCollector<OSCollector>
{

public:
	
	OSCollector() {}
	virtual ~OSCollector() {}
	
	virtual long Collect(NVDataItem ** ReturnItem); 

};
#endif
