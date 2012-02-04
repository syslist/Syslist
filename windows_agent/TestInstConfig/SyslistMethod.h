#ifndef SYSLIST_METHOD_H_INCLUDED
#define SYSLIST_METHOD_H_INCLUDED

typedef enum CmdMethodIndex {
	MethodDisable = 0,
	MethodStartup,
	MethodDay,
	MethodWeek,
	MethodMonth,
	MethodDebug,
	MethodIndexCount
} CmdMethodIndex;

typedef struct CmdMethodInfo {
	char * MethodStr;
	CmdMethodIndex MethodIndex;
} CmdMethodInfo_t;

extern CmdMethodInfo g_CmdLineMethods[];

extern CmdMethodIndex MethodFromString(const char * MethodStr);

extern const char * StringFromMethod(const Index);

#endif
