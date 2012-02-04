#ifndef MOUSE_COLLECT_H_INCLUDED
#define MOUSE_COLLECT_H_INCLUDED

#include "CollectProto.h"
#include "WMIUtil.h"
#include "WinSetupUtil.h"

class MouseCollector:
	public AutoCreateDataCollector<MouseCollector>,
	public WMIUtilLocal<MouseCollector>,
	public WinSetupClientLocal<MouseCollector>
{
public:
	MouseCollector() 
	{
	}

	long CollectFallback(NVDataItem *DataItems);
	long PreferredCollect(NVDataItem *DataItems);
	long Collect(NVDataItem ** ReturnItem);
	virtual ~MouseCollector() {};

private:

};
#endif
