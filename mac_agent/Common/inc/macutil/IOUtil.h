#ifndef IOUTIL_H_INCLUDED
#define IOUTIL_H_INCLUDED

#include <CoreFoundation/CoreFoundation.h>
#include <IOKit/IOKitLib.h>
#include "CFUtil.h"
#include <string>

// This class is used by all clients of the IOService
// to handle the creation and lifetime of the MasterPort.
class IOUtil 
{
public:
	IOUtil() {};
	virtual ~IOUtil(){}
	
	static kern_return_t Init() {
		kern_return_t Result;
		Result = IOMasterPort(MACH_PORT_NULL, &s_MasterPort);
		
		return Result;
	}
	
public:
	static mach_port_t s_MasterPort;
};

#define USING_IO_UTIL mach_port_t IOUtil::s_MasterPort;

template <class T>
class IOAutoMgr
{

public:
	static void AutoRelease(T Obj) 
	{
		IOObjectRelease(Obj);
	}
	
	static void AutoRetain(T Obj) {
		IOObjectRetain(Obj);
	}
	
	inline static T EmptyVal() { return 0; }
};

// Templatized Specialization of AutoUtil for IOService Objects
template <class T>
class IOAuto :
	public AutoObj<T, IOAutoMgr<T> > 
{

public:
	IOAuto(T AttachObj = 0) :
		AutoObj<T, IOAutoMgr<T> >(AttachObj)
	{}
	
	virtual ~IOAuto()
	{}
};

// This class is used to wrap the lists recieved from the nodes
// as well as querying the tree to find various lists.
class IOServiceListUtil :
	public IOUtil
{
public:
	typedef enum MatchTypeEnum {
		MatchDevClass = 0,
		MatchDevName
	};
	
public: 
	IOServiceListUtil(char * ServiceID, MatchTypeEnum MatchType = MatchDevClass)
	{
		if (ServiceID == NULL)
			return;
		
		CFAuto<CFMutableDictionaryRef> DevTreeDict;
		
		switch (MatchType) {
		
		case MatchDevName:
			DevTreeDict.Attach(IOServiceNameMatching(ServiceID));
			break;
			
		case MatchDevClass:
		default:
			DevTreeDict.Attach(IOServiceMatching(ServiceID));
			break;
		}
		
		if (DevTreeDict.IsEmpty())
			return;
		
		kern_return_t Result;
		
		//Note that this function eats the reference on the Dictionary, thus the detach
		Result = IOServiceGetMatchingServices(s_MasterPort, DevTreeDict.Detach(), &m_ServiceList);
	}

	IOServiceListUtil(io_iterator_t AttachObj = 0)
	{	
		m_ServiceList.Attach(AttachObj);
	}
	
	virtual ~IOServiceListUtil() {}
	
	bool IsEmpty()
	{
		if (m_ServiceList == 0 || !IOIteratorIsValid(m_ServiceList))
			return true;
	}
		
	io_service_t Next()
	{
		if (m_ServiceList == 0)
			return 0;
			
		return IOIteratorNext(m_ServiceList);
	}
	
	void Reset()
	{	
		if (m_ServiceList != 0)
			IOIteratorReset(m_ServiceList);
	}
		
protected:
	IOAuto<io_iterator_t> m_ServiceList;
};

// This class is used to access the individual nodes on the IOTree.
class IOServiceUtil :
	public IOUtil,
	public IOAuto<io_service_t>
{
public:
	IOServiceUtil(io_service_t Service = 0) :
		IOAuto<io_service_t>(Service)
	{
	}
	
	virtual ~IOServiceUtil() {}
	
	inline CFTypeRef Query(char * Key) 
	{
		CFAuto<CFStringRef> KeyStr(Key);
		
		return IORegistryEntryCreateCFProperty(
			m_Obj, 
			KeyStr,
			kCFAllocatorDefault, 0);
	}
	
	CFMutableDictionaryRef GetProperties ( IOOptionBits Options = 0 )
	{
		CFAuto<CFMutableDictionaryRef> ReturnDict;
		kern_return_t Status;
		
		Status = IORegistryEntryCreateCFProperties (
			m_Obj,
			& ReturnDict,
			kCFAllocatorDefault,
			Options);
		
		return  ReturnDict.Detach();
	}

};

#endif