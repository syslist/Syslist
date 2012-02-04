#ifndef KEYBOARD_COLLECTOR_H_INCLUDED
#define KEYBOARD_COLLECTOR_H_INCLUDED

#include <CollectProto.h>

class KeyboardCollector:
	public AutoCreateDataCollector<KeyboardCollector>
{
public:
	KeyboardCollector() {}
	virtual ~KeyboardCollector() {}
	
	virtual long Collect(NVDataItem ** ReturnItem);
};


#endif