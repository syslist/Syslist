#ifndef SYSLIST_SOFTWARE_COLLECTOR_H_INCLUDED
#define SYSLIST_SOFTWARE_COLLECTOR_H_INCLUDED

#include "CollectProto.h"
#include <string>


class SoftwareCollector:
	public AutoCreateDataCollector<SoftwareCollector>
{

public:
	typedef bool (* SWFilterFunc_t) ( NVDataItem * );
	
	SoftwareCollector() {}
	virtual ~SoftwareCollector() {}
		
	void ExtractSWDictItems(
		std::string VisibleAppName,
		NVDataItem * SWReportItem,
		CFDictionaryRef SWItemDict,
		const char * PkgNameDest,
		const char * FileNameDest);
	
	long AddSoftwareItemsFromDir (
		std::string SourceDirectory, std::string Type,
		SWFilterFunc_t FilterFunc,
		const char * PkgNameDest, const char * FileNameDest, 
		long MaxDepth, long & TotalCount, NVDataItem * Dest, long CurrDepth = 0);

	virtual long Collect(NVDataItem ** ReturnItem); 

};

#endif
