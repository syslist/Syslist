#ifndef MASTER_COLLECTOR_H_INCLUDED
#define MASTER_COLLECTOR_H_INCLUDED

#include "RegUtil.h"
#include "WMIUtil.h"
#include "CollectProto.h"
#include "SeqListCollector.h"

#include "HardwareCollector.h"

class MasterCollector:
	public SeqListCollector<MasterCollector>,
	public WMIUtilLocal<MasterCollector>,
	public RegUtil
{
public:

	MasterCollector() { m_ItemTag = "SyslistData";};

	BEGIN_SEQCOLLIST() 
	COLL_ENTRY(HardwareCollector)
	COLL_ENTRY(OSCollector)
	COLL_ENTRY(SoftwareCollector)
	END_SEQCOLLIST()

	virtual long PreCollect(NVDataItem *MasterDataItem);

	virtual ~MasterCollector() {};

private:

	static DataCollectCreatorFunc m_SyslistCollectors[];
};

#endif
