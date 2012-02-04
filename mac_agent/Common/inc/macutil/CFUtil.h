#ifndef TEST_CF_UTIL_H_INCLUDED
#define TEST_CF_UTIL_H_INCLUDED
/*
 *  TestCFUtil.h
 *  ProtoPlist
 *
 *  Created by Karl Holland on 11/26/04.
 *  Copyright 2004 Characteristic Software. All rights reserved.
 *
 */
#include <CoreFoundation/CoreFoundation.h>
#include <iostream>
#include <string>

// This is a smart pointer that automatically
// decrements the ref count for CF types
// when leaving scope...
template <class T, class M>
class AutoObj 
{
		
public:
	AutoObj(T AttachObj = M::EmptyVal())
		: m_Obj(AttachObj)
	{
	}

	AutoObj(const AutoObj<T,M> & Source)
		: m_Obj(M::EmptyVal())
	{
		Copy(Source.m_Obj);
	}

	virtual ~AutoObj() 
	{
		if (m_Obj != M::EmptyVal())
			M::AutoRelease(m_Obj);
	}
#if 1	
	virtual operator T ()
	{
		return m_Obj;
	}
#endif
	
	virtual T * operator & ()
	{
		//assert (m_Obj == NULL); // NEED A SYSTEM TO DEAL WITH ASSERTS IN BUILD CODE
		return & m_Obj;
	}
	
	void Attach(T Source) 
	{
		if (m_Obj != M::EmptyVal())
			M::AutoRelease(m_Obj);
			
		m_Obj = Source;
	}
	
	void Copy(T Source)
	{
		if (m_Obj != M::EmptyVal())
			M::AutoRelease(m_Obj);
			
		m_Obj = Source;
		
		if (m_Obj != M::EmptyVal())
			M::AutoRetain(m_Obj);
	}
		
	virtual T operator= (T Source) 
	{
		Copy(Source);
		return m_Obj;
	}

	template <class U>
	operator U ()
	{
		return (U) m_Obj;
	}
	
#if 0
	virtual T operator= (T & Source) 
	{
		Copy(Source);
		return m_Obj;
	}
	
	
public:	
	virtual T operator= (const AutoObj<T,M> & Source)
	{
		Copy(Source.m_Obj);
		return m_Obj;
	}
#endif

public: 

	T Detach() 
	{
		T ReturnObj = m_Obj;
		m_Obj = M::EmptyVal();
		return ReturnObj;
	}
	
	void Clear()
	{
		if (m_Obj != M::EmptyVal())
		M::AutoRelease(m_Obj);
			
		m_Obj = NULL;
	}

	bool IsEmpty()
	{
		if (m_Obj == M::EmptyVal())
			return true;
		else 
			return false;
	}
		
public:
	T m_Obj;

};

template <class T>
class CFAutoMgr
{
public:
	static void AutoRelease(T Obj) 
	{
		CFRelease(Obj);
	}
	
	static void AutoRetain(T Obj) {
		CFRetain(Obj);
	}
	
	inline static T EmptyVal() { return 0; };
};

template <class T>
class CFAuto; 

// Specialization for String Refs
template <>
class CFAuto<CFStringRef> :
	public AutoObj<CFStringRef, CFAutoMgr<CFStringRef> >
{
public:
	CFAuto(CFStringRef AttachObj = NULL) :
		AutoObj<CFStringRef, CFAutoMgr<CFStringRef> > (AttachObj)
	{}

	using AutoObj<CFStringRef, CFAutoMgr<CFStringRef> >::operator=;

	CFAuto(const char *Value) :
		AutoObj<CFStringRef, CFAutoMgr<CFStringRef> >()
	{
		m_Obj = CFStringCreateWithCString (
			NULL,
			Value,
			kCFStringEncodingUTF8
		);
	}

	void GetCString(std::string & RetVal)
	{
		RetVal.clear();
		
		if (IsEmpty())
			return;
		
		long Length = CFStringGetLength(m_Obj) + 1;
		char RetString[3*Length]; // 3 times is worst case for UTF8
		
		CFStringGetCString(m_Obj, RetString, Length , kCFStringEncodingUTF8);
		
		RetVal = RetString;
	}

	virtual ~CFAuto() {}; 
};

	
// Templatized Specialization of AutoObj for CoreFoundation Objects
template <class T>
class CFAuto :
	public AutoObj<T, CFAutoMgr<T> > 
{

public:
	CFAuto(T AttachObj = NULL) :
		AutoObj<T, CFAutoMgr<T> >(AttachObj)
	{}
	
	using AutoObj<T, CFAutoMgr<T> >::operator=;
	
	void PrintToStdOut()	{
	
		if (AutoObj<T, CFAutoMgr<T> >::IsEmpty()) {
			std::cout << "Empty Object" << std::endl;
		}
		else if (CFGetTypeID(this->m_Obj) == CFDataGetTypeID()) {
			CFAuto<CFStringRef> IDString;
			
			IDString.Attach(CFStringCreateFromExternalRepresentation (
				NULL,
				(CFDataRef) this->m_Obj,
				kCFStringEncodingUTF8)
			);
			
			if (IDString.IsEmpty()) {
				std::cout << "Data not in string form!" << std::endl;
			}
			else { 
				std::cout << "From CFData: \n";
				CFShow (IDString);
			}
		}
		else if (CFGetTypeID(this->m_Obj) == CFStringGetTypeID()
				 || CFGetTypeID (this->m_Obj) == CFNumberGetTypeID()
				 || CFGetTypeID (this->m_Obj) == CFBooleanGetTypeID()){
			CFShow (this->m_Obj);
		}
		else {
			CFShow (CFAuto<CFStringRef>(CFCopyTypeIDDescription(CFGetTypeID(this->m_Obj))));
		}
	};
};

///////////////////////////////////////////////
//
// AutoCharBuf - utility class for handling char * buffers
class AutoCharBuf
{
public:
	AutoCharBuf(long InitSize = 0) : m_Buf(NULL)
	{
		if (InitSize > 0)
			m_Buf = new unsigned char[InitSize];
	}
	
	virtual ~AutoCharBuf()
	{
		if (m_Buf != NULL)
			delete[] m_Buf;
	}
	
	virtual unsigned char ** operator& ()
	{
		return &m_Buf;
	}
	
	virtual operator unsigned char * ()
	{
		return m_Buf;
	}	
	
#if 0
	virtual unsigned char operator[] (long index)
	{
		return m_Buf[index];
	}
#endif

	virtual unsigned char * Detach()
	
	{
		unsigned char * ReturnVal = m_Buf;
		m_Buf = NULL;
		return ReturnVal;
	}
	
	virtual bool IsEmpty()
	{
		if (m_Buf == NULL)
			return true;

		return false;
	}
		
public:
	unsigned char * m_Buf;
};	

#endif