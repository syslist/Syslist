#ifndef FILE_HANDLE_TRANSPORT_H_INCLUDED
#define FILE_HANDLE_TRANSPORT_H_INCLUDED

#include "TransportProto.h"
#include <iostream>

class HTMLStringOut {

public:
	HTMLStringOut(char * InitStr = NULL): m_StrData(InitStr){};
	
	virtual ~HTMLStringOut() {};

	const char ** operator& () { return &m_StrData; };

private:
	const char * m_StrData;


	friend ostream& operator<< ( ostream &StreamOut, HTMLStringOut & HString) 
	{
		const char * CurrLoc;

		for (CurrLoc = HString.m_StrData; CurrLoc != NULL && *CurrLoc != '\0'; CurrLoc ++) {


			//if ( (unsigned char) (*CurrLoc) > (unsigned char) 127) {
			//	StreamOut << "&#" << (unsigned int)(unsigned char) *CurrLoc << ';';
			//}
			//else {
				switch (*CurrLoc) {
				case '&':
					StreamOut << "&amp;";
					break;
				
				case '<':
					StreamOut << "&lt;";
					break;
				
				case '>':
					StreamOut << "&gt;";
					break;

				case '\'':
					StreamOut << "&apos;";
					break;

				case '\"':
					StreamOut << "&quot;";
					break;

				default:
					StreamOut << (*CurrLoc);
					break;
				}
			//}
		}

		return StreamOut;
	}
};


				

class FileHandleTransport:
	public DataTransportProto
{
public:
	FileHandleTransport(){};
	virtual ~FileHandleTransport() {};

protected:

	void DumpPrefix (long Depth)
	{
		long Prefix;

		for (Prefix = 0; Prefix < Depth; Prefix ++)
			*m_OStream << '\t';
	}

	void DumpDataItems(NVDataItem * DataItem, long Depth = 0)
	{

		DumpPrefix (Depth);

		if (Depth == 0) 
			*m_OStream << "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>" << endl;

		HTMLStringOut CurrTag;

		DataItem->GetItemTag(&CurrTag);
		*m_OStream << "<" << CurrTag ;

		if (DataItem->NVCount() > 0)
			*m_OStream << endl;

		Depth++;

		long NVIndex;
		HTMLStringOut CurrName;
		HTMLStringOut CurrVal;

		for (NVIndex = 0; NVIndex < DataItem->NVCount(); NVIndex ++) {
			DataItem->GetNVItem( NVIndex, &CurrName, &CurrVal);
			DumpPrefix (Depth);
			*m_OStream << CurrName << "=\"" << CurrVal << "\"" << endl;
		}

		//DumpPrefix(Depth);
		//if (NVIndex == 0)
		//	*m_OStream << "\\>" << endl;
		//else
		//	*m_OStream << ">" << endl;

		if (NVIndex > 0)
			DumpPrefix(Depth);

		if (DataItem->SubItemCount() > 0)
			*m_OStream << ">" << endl;
		else
			*m_OStream << "/>" << endl;

		NVDataItem * SubDataItem;
		long SubIndex;

		for (SubIndex = 0; SubIndex < DataItem->SubItemCount(); SubIndex ++) {
			DataItem->GetSubItem(SubIndex, & SubDataItem);
			DumpDataItems(SubDataItem, Depth);
		}
		
		Depth --;

		if (SubIndex > 0) {
			DumpPrefix(Depth);
			*m_OStream << "</" << CurrTag << ">" << endl;
		}
	}

protected:
		
	ostream * m_OStream;
};

#endif
