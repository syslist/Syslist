#ifndef DISPLAY_COLLECTOR_H_INCLUDED
#define DISPLAY_COLLECTOR_H_INCLUDED

#include <CollectProto.h>

class DisplayCollector:
	public AutoCreateDataCollector<DisplayCollector>
{
public:
	DisplayCollector() {}
	virtual ~DisplayCollector() {}
	
	virtual long Collect(NVDataItem ** ReturnItem);
};


#endif