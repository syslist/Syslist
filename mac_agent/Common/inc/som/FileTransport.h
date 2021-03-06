#ifndef FILE_TRANSPORT_H_INCLUDED
#define FILE_TRANSPORT_H_INCLUDED

#include "FileHandleTransport.h"

#include <fstream>
#include <algorithm>
#include <cctype>

class FileTransport:
	public FileHandleTransport
{

public:

	FileTransport() {}

	virtual const char * Name() 
	{
		return "FileTransport";
	}

	virtual const char * HandlePrefix()
	{
		return "File";
	}

	virtual long OpenURI( const char * URIString)
	{
		string lCaseURI(URIString);
		transform(lCaseURI.begin(), lCaseURI.end(), lCaseURI.begin(), (int(*) (int)) std::tolower);
	
		if (strncmp(lCaseURI.c_str(),"file://",strlen("file://")))
			return -1;
		
		long FileStart = strlen ("File://");
		
		m_OFStream.open (&(URIString[FileStart]));
		m_OStream = &m_OFStream;

		return 0;
	};


	virtual long TransmitData (NVDataItem * Data)
	{
		FileHandleTransport::DumpDataItems(Data);
		return 0;
	}

	virtual long Close()
	{
		m_OFStream.close();
		return 0;
	};

protected:
	ofstream m_OFStream;

};
#endif
