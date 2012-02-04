#ifndef DISK_COLLECT_H_INCLUDED
#define DISK_COLLECT_H_INCLUDED

#include "CollectProto.h"
#include "WMIUtil.h"
#include "WinSetupUtil.h"

class DiskCollector:
	public AutoCreateDataCollector<DiskCollector>,
	public WMIUtilLocal<DiskCollector>,
	public WinSetupClientLocal<DiskCollector>
{
public:
	DiskCollector() 
	{
	}


	long Collect(NVDataItem ** ReturnItem);
	virtual ~DiskCollector() {};

private:
	long CollectSingleDisk (IWbemClassObject * WMIDisk, NVDataItem *TargetItem, bool * KeepItem);
	long CollectSingleCDROM (IWbemClassObject * WMIDisk, NVDataItem *TargetItem, bool * KeepItem);

};
#endif
