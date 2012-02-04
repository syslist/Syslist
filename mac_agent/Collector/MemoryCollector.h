#ifndef MEMORY_COLLECTOR_H_INCLUDED
#define MEMORY_COLLECTOR_H_INCLUDED

#include "CollectProto.h"

class MemoryCollector:
	public AutoCreateDataCollector<MemoryCollector>
{
public:
	MemoryCollector() {}
	virtual ~MemoryCollector() {}
	
	virtual long Collect(NVDataItem ** ReturnItem);
};


#endif
