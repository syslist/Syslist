#ifndef HARDWARE_COLLECTOR_H_INCLUDED
#define HARDWARE_COLLECTOR_H_INCLUDED

#include "WMIUtil.h"
#include "CollectProto.h"
#include "SeqListCollector.h"

#include "ProcessorCollector.h"
#include "DiskCollect.h"
#include "NetCollector.h"
#include "SoftwareCollector.h"
#include "OSCollector.h"
#include "DisplayCollector.h"
#include "KeyboardCollector.h"
#include "MouseCollector.h"
#include "PrinterCollector.h"
#include "MonitorCollector.h"
#include "MemoryCollector.h"
#include "LogicalDiskCollector.h"

class HardwareCollector:
	public SeqListCollector<HardwareCollector>,
	public WMIUtilLocal<HardwareCollector>
{
public:

	HardwareCollector() { m_ItemTag = "Hardware";};

	BEGIN_SEQCOLLIST() 
	COLL_ENTRY(ProcessorCollector)
	COLL_ENTRY(DiskCollector)
	COLL_ENTRY(LogicalDiskCollector)
	COLL_ENTRY(NetCollector)
	COLL_ENTRY(KeyboardCollector)
	COLL_ENTRY(PrinterCollector)
	COLL_ENTRY(MouseCollector)
	COLL_ENTRY(DisplayCollector)
	COLL_ENTRY(MonitorCollector)
	COLL_ENTRY(MemoryCollector)
	END_SEQCOLLIST()

	virtual ~HardwareCollector() {};

private:

	static DataCollectCreatorFunc m_SyslistCollectors[];
};

#endif