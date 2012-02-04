#ifndef PROCESSOR_COLLECTOR_H_INCLUDED
#define PROCESSOR_COLLECTOR_H_INCLUDED

#include <CollectProto.h>

class ProcessorCollector:
	public AutoCreateDataCollector<ProcessorCollector>
{
public:
	ProcessorCollector() {}
	virtual ~ProcessorCollector() {}
	
	virtual long Collect(NVDataItem ** ReturnItem);
	
private:
	long ProcessorCollector::GetCPUNames (long MainType, long SubType, 	std::string & ReturnName, std::string & ReturnDesc);

};


#endif
