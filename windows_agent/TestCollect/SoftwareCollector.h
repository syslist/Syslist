#ifndef SOFTWARE_COLLECTOR_H_INCLUDED
#define SOFTWARE_COLLECTOR_H_INCLUDED

#include "CollectProto.h"
#include "RegUtil.h"

class SoftwareCollector:
	public AutoCreateDataCollector<SoftwareCollector>,
	public RegUtilLocal<SoftwareCollector>
{
public:
	long Collect(NVDataItem ** ReturnItem);
	virtual ~SoftwareCollector() {};

private:
	long CollectSingleProgram (HKEY SubKey, char * SubKeyName, NVDataItem * TargetData, bool * KeepItem);

};

#endif
