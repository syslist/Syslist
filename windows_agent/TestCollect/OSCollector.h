#ifndef OS_COLLECTOR_H_INCLUDED
#define OS_COLLECTOR_H_INCLUDED

#include "CollectProto.h"
#include "WMIUtil.h"

class OSCollector:
	public AutoCreateDataCollector<OSCollector>,
	public WMIUtilLocal<OSCollector>
{
public:
	OSCollector() 
	{
	}


	long Collect(NVDataItem ** ReturnItem);
	virtual ~OSCollector() {};

private:
	long CollectSingleOS (IWbemClassObject * WMIDisk, NVDataItem *TargetItem );
	long AppendNTCommonVersionString(string & VersionString, OSVERSIONINFOEX & WinVer);	
	long AppendPreNTVersionString (string & VersionString, string & CSDVersion, OSVERSIONINFOEX & WinVer);
	long AppendNTVersionString(string & VersionString, string & CSDVersion, OSVERSIONINFOEX & WinVer);
	long CollectFallback (NVDataItem *DataItems);
	long PreferredCollect(NVDataItem *DataItems);

};

#endif
