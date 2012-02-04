#ifndef SYSLIST_HARDWARE_COLLECTOR_H_INCLUDED
#define SYSLIST_HARDWARE_COLLECTOR_H_INCLUDED

#include "CollectProto.h"
#include "SeqListCollector.h"

#include "ProcessorCollector.h"
#include "DiskCollector.h"
#include "NetCollector.h"
#include "MemoryCollector.h"
#include "LogicalDiskCollector.h"
#include "DisplayCollector.h"
#include "KeyboardCollector.h"
#include "MouseCollector.h"
//#include "PrinterCollector.h"
//#include "MonitorCollector.h"

class HardwareCollector:
	public SeqListCollector<HardwareCollector>
{

public:
	
	HardwareCollector() { m_ItemTag = "Hardware";};
	virtual ~HardwareCollector() {}
	
	BEGIN_SEQCOLLIST() 
	COLL_ENTRY(ProcessorCollector)
	COLL_ENTRY(DiskCollector)
	COLL_ENTRY(LogicalDiskCollector)
	COLL_ENTRY(NetCollector)
	COLL_ENTRY(MemoryCollector)	
	COLL_ENTRY(KeyboardCollector)
	//COLL_ENTRY(PrinterCollector)
	COLL_ENTRY(MouseCollector)
	COLL_ENTRY(DisplayCollector)
	//COLL_ENTRY(MonitorCollector)
	END_SEQCOLLIST()

};

#endif
