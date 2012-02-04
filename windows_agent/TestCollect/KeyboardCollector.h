#ifndef KEYBOARD_COLLECT_H_INCLUDED
#define KEYBOARD_COLLECT_H_INCLUDED

#include "CollectProto.h"
#include "WMIUtil.h"
#include "WinSetupUtil.h"

class KeyboardCollector:
	public AutoCreateDataCollector<KeyboardCollector>,
	public WMIUtilLocal<KeyboardCollector>,
	public WinSetupClientLocal<KeyboardCollector>
{
public:
	KeyboardCollector() 
	{
	}


	long CollectFallback(NVDataItem *DataItems);
	long PreferredCollect(NVDataItem *DataItems);
	long Collect(NVDataItem ** ReturnItem);
	virtual ~KeyboardCollector() {};

private:

};
#endif
