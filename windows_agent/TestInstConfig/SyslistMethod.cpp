#include "stdafx.h"
#include "SyslistMethod.h"

CmdMethodInfo g_CmdLineMethods[] = {
	{"Disable", MethodDisable},
	{"Startup", MethodStartup},
	{"Day", MethodDay},
	{"Week", MethodWeek},
	{"Month", MethodMonth},
	{"Debug", MethodDebug},
	NULL
};

CmdMethodIndex MethodFromString(const char * MethodStr)
{
	CmdMethodInfo * CurrMethod;

	for (CurrMethod = g_CmdLineMethods; CurrMethod->MethodStr != NULL; CurrMethod++){
		if (!stricmp(CurrMethod->MethodStr, MethodStr))
			return CurrMethod->MethodIndex;
	}

	return MethodDisable;
}

const char * StringFromMethod(const Index)
{
	CmdMethodInfo * CurrMethod;

	for (CurrMethod = g_CmdLineMethods; CurrMethod->MethodStr != NULL; CurrMethod++){
		if (CurrMethod->MethodIndex == Index)
			return CurrMethod->MethodStr;
	}

	return NULL;
}