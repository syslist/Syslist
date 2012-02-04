#ifndef SYSLIST_MASTER_COLLECTOR_INCLUDED
#define SYSLIST_MASTER_COLLECTOR_INCLUDED

#include "SeqListCollector.h"

#include "HardwareCollector.h"
#include "OSCollector.h"
#include "SoftwareCollector.h"

class MasterCollector :
	public SeqListCollector<MasterCollector>
{

public:
	MasterCollector() {m_ItemTag = "SyslistData";}

	BEGIN_SEQCOLLIST() 
	COLL_ENTRY(HardwareCollector)
	COLL_ENTRY(OSCollector)
	COLL_ENTRY(SoftwareCollector)
	END_SEQCOLLIST()
	
	virtual long PreCollect(NVDataItem *CurrCollect);	

};

#endif