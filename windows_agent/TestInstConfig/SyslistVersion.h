#ifdef SYSLIST_DEMO
#ifdef ASP_DEMO
const char * SyslistVersionString = "1.4.0(ASP-DEMO)";
#else
const char * SyslistVersionString = "1.4.0(DEMO)";
#endif
#else
#ifdef SYSLIST_ACC
const char * SyslistVersionString = "1.4.0(ASP)";
#else
const char * SyslistVersionString = "1.4.0";
#endif
#endif