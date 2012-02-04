#ifndef SYSLIST_VERSION_H_INCLUDED
#define SYSLIST_VERSION_H_INCLUDED

#define SL_BASEVERSION "v1.1.5"

#define SL_PLATFORM_VERSION "MACOSX"

#define SL_SPACER " "

#if defined (SYSLIST_DEMO) && defined (SYSLIST_ASP)
#define SL_VERSIONQUAL "(ASP-DEMO)"
#elif defined (SYSLIST_DEMO)
#define SL_VERSIONQUAL "(DEMO)"
#elif defined (SYSLIST_ASP)
#define SL_VERSIONQUAL "(ASP)"
#else
#define SL_VERSIONQUAL ""
#endif

#define SL_COMPLETE_VERSION_STRING SL_BASEVERSION SL_SPACER SL_VERSIONQUAL SL_SPACER SL_PLATFORM_VERSION

const char * cstrSyslistVersionString = SL_COMPLETE_VERSION_STRING;

CFStringRef cfstrSyslistVersion = CFSTR(SL_COMPLETE_VERSION_STRING);

#if defined __OBJC__
NSString * nsstrSyslistVersion = (NSString *) cfstrSyslistVersion;
#endif

#endif
