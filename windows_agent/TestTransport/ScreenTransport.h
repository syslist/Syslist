#ifndef	SCREEN_TRANSPORT_H_INCLUDED
#define	SCREEN_TRANSPORT_H_INCLUDED

#include "FileHandleTransport.h"

class ScreenTransport:
	protected FileHandleTransport
{

public:

	ScreenTransport() {
		m_OStream = &cout;
	}

	virtual const char * Name() 
	{
		return "ScreenTransport";
	}

	virtual const char * HandlePrefix()
	{
		return "Screen";
	}

	virtual long OpenURI( char * URIString)
	{
		if (strncmp(URIString,"Screen:",strlen("Screen")))
			return -1;
		
		return 0;
	};


	virtual long TransmitData (NVDataItem * Data)
	{
		FileHandleTransport::DumpDataItems(Data);
		return 0;
	}

	virtual long Close()
	{
		return 0;
	};

};

#endif