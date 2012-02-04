#ifndef TRANSPORT_PROTO_H_INCLUDED
#define TRANSPORT_PROTO_H_INCLUDED

#include "SOMBase.h"

class DataTransportProto {

public:
	virtual const char * Name() = 0;
	virtual const char * HandlePrefix() = 0;
	virtual long OpenURI( const char * URI) = 0;
	virtual long TransmitData (NVDataItem * Data) = 0;
	virtual long Close() = 0;

public:
	virtual ~DataTransportProto() {}

};
	
#endif

