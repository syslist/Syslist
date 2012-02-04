#ifndef PROCESSOR_COLLECTOR_H_INCLUDED
#define PROCESSOR_COLLECTOR_H_INCLUDED

#include "CollectProto.h"
#include "RegUtil.h"
#include "WMIUtil.h"


class ProcessorCollector:
	public AutoCreateDataCollector<ProcessorCollector>,
	public RegUtilLocal<ProcessorCollector>,
	public WMIUtilLocal<ProcessorCollector>
{
public:
	long Collect(NVDataItem ** ReturnItem);
	virtual ~ProcessorCollector() {};

private:
	long CollectFallback(NVDataItem *DataItems);
	long CollectProcessorCount (long Count, NVDataItem * TargetData);
	long PreferredCollect(NVDataItem *DataItems);
	long CollectSingleProcessor (HKEY SubKey, char * SubKeyName, NVDataItem * TargetData, bool * KeepItem);
	long ProcessorCollector::CollectSingleWMIProcessor (IWbemClassObject * WMIProcessor, NVDataItem *TargetItem, bool * KeepItem);
	void ProcessorCollector::RoundProcessorSpeed (NVDataItem * TargetData );
};

#endif
