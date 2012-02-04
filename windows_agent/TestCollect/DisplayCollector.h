#ifndef DISPLAY_COLLECT_H_INCLUDED
#define DISPLAY_COLLECT_H_INCLUDED

#include "CollectProto.h"
#include "WinSetupUtil.h"

class DisplayCollector:
	public AutoCreateDataCollector<DisplayCollector>,
	public WinSetupClientLocal<DisplayCollector>
{
public:
	DisplayCollector() 
	{
	}


	long Collect(NVDataItem ** ReturnItem);
	long CollectFallback(NVDataItem * TargetItem);
	virtual ~DisplayCollector() {};

private:

};
#endif
