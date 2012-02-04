#ifndef NET_COLLECTOR_H_INCLUDED
#define NET_COLLECTOR_H_INCLUDED

#include "CollectProto.h"
#include "WMIUtil.h"
#include "WinSetupUtil.h"

class NetCollector:
	public AutoCreateDataCollector<NetCollector>,
	public WMIUtilLocal<NetCollector>,
	public WinSetupClientLocal<NetCollector>
{
public:
	NetCollector() 
	{
	}

	long CollectFallback(NVDataItem *DataItems);
	long PreferredCollect(NVDataItem *DataItems);
	long Collect(NVDataItem ** ReturnItem);
	virtual ~NetCollector() {};

private:
	long CollectSingleAdaptor (IWbemClassObject * WMIDisk, NVDataItem *TargetItem, bool * KeepItem );

};
#endif