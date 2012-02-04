#ifndef MEMORY_COLLECT_H_INCLUDED
#define MEMORY_COLLECT_H_INCLUDED

#include "CollectProto.h"
#include "WMIUtil.h"

class MemoryCollector:
	public AutoCreateDataCollector<MemoryCollector>,
	public WMIUtilLocal<MemoryCollector>
{
public:
	MemoryCollector():m_TotalMemory(0.0f) 
	{
	}

	long CollectFallback(NVDataItem *DataItems);
	long PreferredCollect(NVDataItem *DataItems);
	long CollectSingleMemory (IWbemClassObject * WMIMemory, NVDataItem *TargetItem, bool * KeepItem  );
	long Collect(NVDataItem ** ReturnItem);
	virtual ~MemoryCollector() {};

private:
	double m_TotalMemory;

};
#endif
