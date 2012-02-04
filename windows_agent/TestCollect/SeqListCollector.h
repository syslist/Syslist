#ifndef SEQLIST_COLLECTOR_H_INCLUDED
#define SEQLIST_COLLECTOR_H_INCLUDED

#include "WMIUtil.h"
#include "CollectProto.h"

#define BEGIN_SEQCOLLIST() virtual DataCollectCreatorFunc * GetCollectorList() {\
		static DataCollectCreatorFunc SyslistCollectors[] = {

#define COLL_ENTRY(I) I::CreateCollector,

#define END_SEQCOLLIST() NULL\
		};\
		return SyslistCollectors;\
		};\

template < class T >
class SeqListCollector:
	public AutoCreateDataCollector<T>
{
public:

	virtual long Collect(NVDataItem** ReturnItem)
	{
		auto_ptr<NVDataItem> SeqListDataItem ( new NVDataItem(m_ItemTag));

		long ItemStatus;

		ItemStatus = PreCollect (SeqListDataItem.get());
		if (ItemStatus != 0)
			return ItemStatus;

		for (DataCollectCreatorFunc * CurrItem = GetCollectorList(); 
			 (*CurrItem) != NULL;
			 CurrItem ++) {

			DataCollector *CurrCollector;
			NVDataItem *CurrItemData = NULL;

			(*CurrItem)(&CurrCollector);
			ItemStatus = CurrCollector->Collect(&CurrItemData);
			
			if (ItemStatus != 0)
				return ItemStatus;

			if (CurrItemData != NULL)
				SeqListDataItem->AddSubItem(CurrItemData);
		}
		
		ItemStatus = PostCollect (SeqListDataItem.get());
		if (ItemStatus != 0)
			return ItemStatus;

		*ReturnItem = SeqListDataItem.release();

		return 0;
	};	

virtual ~SeqListCollector() {};

protected:
	virtual long PreCollect(NVDataItem *CurrCollect) { return ERROR_SUCCESS; };
	virtual long PostCollect(NVDataItem *CurrCollect) { return ERROR_SUCCESS; };

	BEGIN_SEQCOLLIST() 
	END_SEQCOLLIST()

	char * m_ItemTag;

private:
};

#endif
