#ifndef SYSLIST_PROXY_METHOD_H_INCLUDED
#define SYSLIST_PROXY_METHOD_H_INCLUDED


typedef enum ProxyMethodIndex {
	ProxyMethodDirect = 0,
	ProxyMethodIE,
	ProxyMethodManual,
	//ProxyMethodAuto,
} ProxyMethodIndex;

typedef struct ProxyMethodInfo {
	char * ProxyMethodStr;
	ProxyMethodIndex MethodIndex;
} ProxyMethodInfo_t;

extern ProxyMethodInfo g_ProxyMethods[];

extern ProxyMethodIndex ProxyMethodFromString(const char * MethodStr);

extern const char * StringFromProxyMethod(const ProxyMethodIndex Index);


#endif