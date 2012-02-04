#ifndef COLLECT_PROTO_H_INCLUDED
#define COLLECT_PROTO_H_INCLUDED

#include "../TestData/DataItem.h"
#include "assert.h"

class DataCollector 
{
public:
	virtual long Collect(NVDataItem ** ReturnItem) = 0;
	static long CreateCollector (DataCollector ** ReturnCollector) 
	{ 
		assert(0);
		return 0; 
	};
	virtual ~DataCollector() {};

};

typedef long (* DataCollectCreatorFunc)(DataCollector **);

template < class T >
class AutoCreateDataCollector:
	public DataCollector
{
public:

	static long CreateCollector (DataCollector ** ReturnCollector) { *ReturnCollector = new T; return 0; };
	virtual ~AutoCreateDataCollector() {};
};
#endif