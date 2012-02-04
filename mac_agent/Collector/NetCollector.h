#ifndef NET_COLLECTOR_H_INCLUDED
#define NET_COLLECTOR_H_INCLUDED

#include <CollectProto.h>

class NetCollector:
	public AutoCreateDataCollector<NetCollector>
{
public:
	NetCollector() {}
	virtual ~NetCollector() {}
	
	virtual long Collect(NVDataItem ** ReturnItem);
};


#endif
