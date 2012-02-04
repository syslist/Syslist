#ifndef MONITOR_COLLECT_H_INCLUDED
#define MONITOR_COLLECT_H_INCLUDED

#include "CollectProto.h"
#include "WinSetupUtil.h"

class MonitorCollector:
	public AutoCreateDataCollector<MonitorCollector>,
	public WinSetupClientLocal<MonitorCollector>
{
public:
	MonitorCollector() 
	{
	}


	long Collect(NVDataItem ** ReturnItem) {

		auto_ptr<NVDataItem> DataItems (new NVDataItem("MonitorList"));
	
		WinSetupEnumerateDevClass ((GUID) GUID_DEVCLASS_MONITOR, "Monitors", DataItems.get());

		*ReturnItem = DataItems.release();

		return ERROR_SUCCESS;
	};

	virtual ~MonitorCollector() {};

private:

};
#endif
