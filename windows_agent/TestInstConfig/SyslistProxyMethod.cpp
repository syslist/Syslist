#include "stdafx.h"
#include "SyslistProxyMethod.h"

ProxyMethodInfo g_ProxyMethods[] = {
	{"Direct", ProxyMethodDirect},
	{"IE", ProxyMethodIE},
	{"Manual", ProxyMethodManual},
//	{"Week", ProxyMethodAuto},
	NULL
};

ProxyMethodIndex ProxyMethodFromString(const char * MethodStr)
{
	ProxyMethodInfo * CurrMethod;

	for (CurrMethod = g_ProxyMethods; CurrMethod->ProxyMethodStr != NULL; CurrMethod++){
		if (!stricmp(CurrMethod->ProxyMethodStr, MethodStr))
			return CurrMethod->MethodIndex;
	}

	return ProxyMethodDirect;
}

const char * StringFromProxyMethod(const ProxyMethodIndex Index)
{
	ProxyMethodInfo * CurrMethod;

	for (CurrMethod = g_ProxyMethods; CurrMethod->ProxyMethodStr != NULL; CurrMethod++){
		if (CurrMethod->MethodIndex == Index)
			return CurrMethod->ProxyMethodStr;
	}

	return NULL;
}