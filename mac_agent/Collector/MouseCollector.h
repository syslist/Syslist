#ifndef MOUSE_COLLECTOR_H_INCLUDED
#define MOUSE_COLLECTOR_H_INCLUDED

#include <CollectProto.h>

class MouseCollector:
	public AutoCreateDataCollector<MouseCollector>
{
public:
	MouseCollector() {}
	virtual ~MouseCollector() {}
	
	virtual long Collect(NVDataItem ** ReturnItem);
};


#endif