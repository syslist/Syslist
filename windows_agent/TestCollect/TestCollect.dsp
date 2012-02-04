# Microsoft Developer Studio Project File - Name="TestCollect" - Package Owner=<4>
# Microsoft Developer Studio Generated Build File, Format Version 6.00
# ** DO NOT EDIT **

# TARGTYPE "Win32 (x86) Console Application" 0x0103

CFG=TestCollect - Win32 Debug_Instrument
!MESSAGE This is not a valid makefile. To build this project using NMAKE,
!MESSAGE use the Export Makefile command and run
!MESSAGE 
!MESSAGE NMAKE /f "TestCollect.mak".
!MESSAGE 
!MESSAGE You can specify a configuration when running NMAKE
!MESSAGE by defining the macro CFG on the command line. For example:
!MESSAGE 
!MESSAGE NMAKE /f "TestCollect.mak" CFG="TestCollect - Win32 Debug_Instrument"
!MESSAGE 
!MESSAGE Possible choices for configuration are:
!MESSAGE 
!MESSAGE "TestCollect - Win32 Release" (based on "Win32 (x86) Console Application")
!MESSAGE "TestCollect - Win32 Debug" (based on "Win32 (x86) Console Application")
!MESSAGE "TestCollect - Win32 Demo Debug" (based on "Win32 (x86) Console Application")
!MESSAGE "TestCollect - Win32 Demo Release" (based on "Win32 (x86) Console Application")
!MESSAGE "TestCollect - Win32 ASP Demo Debug" (based on "Win32 (x86) Console Application")
!MESSAGE "TestCollect - Win32 ASP Demo Release" (based on "Win32 (x86) Console Application")
!MESSAGE "TestCollect - Win32 ACC Debug" (based on "Win32 (x86) Console Application")
!MESSAGE "TestCollect - Win32 ACC Release" (based on "Win32 (x86) Console Application")
!MESSAGE "TestCollect - Win32 Debug_Instrument" (based on "Win32 (x86) Console Application")
!MESSAGE 

# Begin Project
# PROP AllowPerConfigDependencies 0
# PROP Scc_ProjName ""
# PROP Scc_LocalPath ""
CPP=cl.exe
RSC=rc.exe

!IF  "$(CFG)" == "TestCollect - Win32 Release"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "Release"
# PROP BASE Intermediate_Dir "Release"
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "Release"
# PROP Intermediate_Dir "Release"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /GX /O2 /D "WIN32" /D "NDEBUG" /D "_CONSOLE" /D "_MBCS" /Yu"stdafx.h" /FD /c
# ADD CPP /nologo /W3 /GX /O2 /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "NDEBUG" /D "_CONSOLE" /D "_MBCS" /FR /Yu"stdafx.h" /FD /c
# ADD BASE RSC /l 0x409 /d "NDEBUG"
# ADD RSC /l 0x409 /d "NDEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /subsystem:console /machine:I386
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /machine:I386 /out:"Release/SCAInv.exe" /libpath:"$(OPENSSL)\lib\VC" /libpath:"$(MSSDK)\lib"
# SUBTRACT LINK32 /pdb:none

!ELSEIF  "$(CFG)" == "TestCollect - Win32 Debug"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 1
# PROP BASE Output_Dir "Debug"
# PROP BASE Intermediate_Dir "Debug"
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 1
# PROP Output_Dir "Debug"
# PROP Intermediate_Dir "Debug"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /Gm /GX /ZI /Od /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /Yu"stdafx.h" /FD /GZ /c
# ADD CPP /nologo /W3 /Gm /GX /ZI /Od /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /D "LOCAL_DEBUG_ECHO" /FR /Yu"stdafx.h" /FD /GZ /c
# ADD BASE RSC /l 0x409 /d "_DEBUG"
# ADD RSC /l 0x409 /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /subsystem:console /debug /machine:I386 /pdbtype:sept
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib ole32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /debug /machine:I386 /out:"Debug/SCAInv.exe" /pdbtype:sept /libpath:"$(OPENSSL)\lib\VC" /libpath:"$(MSSDK)\lib"
# SUBTRACT LINK32 /pdb:none

!ELSEIF  "$(CFG)" == "TestCollect - Win32 Demo Debug"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 1
# PROP BASE Output_Dir "TestCollect___Win32_Demo_Debug"
# PROP BASE Intermediate_Dir "TestCollect___Win32_Demo_Debug"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 1
# PROP Output_Dir "DebugDemo"
# PROP Intermediate_Dir "DebugDemo"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /Gm /GX /ZI /Od /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /D "LOCAL_DEBUG_ECHO" /FR /Yu"stdafx.h" /FD /GZ /c
# ADD CPP /nologo /W3 /Gm /GX /ZI /Od /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /D "LOCAL_DEBUG_ECHO" /D "SYSLIST_DEMO" /FR /Yu"stdafx.h" /FD /GZ /c
# ADD BASE RSC /l 0x409 /d "_DEBUG"
# ADD RSC /l 0x409 /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib ole32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /debug /machine:I386 /out:"Debug/SCAInv.exe" /pdbtype:sept /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT BASE LINK32 /pdb:none
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib ole32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /debug /machine:I386 /out:"DebugDemo/SCAInv.exe" /pdbtype:sept /libpath:"$(OPENSSL)\lib\VC\Static" /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT LINK32 /pdb:none

!ELSEIF  "$(CFG)" == "TestCollect - Win32 Demo Release"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "TestCollect___Win32_Demo_Release"
# PROP BASE Intermediate_Dir "TestCollect___Win32_Demo_Release"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "ReleaseDemo"
# PROP Intermediate_Dir "ReleaseDemo"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /GX /O2 /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "NDEBUG" /D "_CONSOLE" /D "_MBCS" /FR /Yu"stdafx.h" /FD /c
# ADD CPP /nologo /W3 /GX /O2 /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "NDEBUG" /D "_CONSOLE" /D "_MBCS" /D "SYSLIST_DEMO" /FR /Yu"stdafx.h" /FD /c
# SUBTRACT CPP /u
# ADD BASE RSC /l 0x409 /d "NDEBUG"
# ADD RSC /l 0x409 /d "NDEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /machine:I386 /out:"Release/SCAInv.exe" /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT BASE LINK32 /pdb:none
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /machine:I386 /out:"ReleaseDemo/SCAInv.exe" /libpath:"$(OPENSSL)\lib\VC\Static" /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT LINK32 /pdb:none

!ELSEIF  "$(CFG)" == "TestCollect - Win32 ASP Demo Debug"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 1
# PROP BASE Output_Dir "TestCollect___Win32_ASP_Demo_Debug"
# PROP BASE Intermediate_Dir "TestCollect___Win32_ASP_Demo_Debug"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 1
# PROP Output_Dir "DebugASPDemo"
# PROP Intermediate_Dir "DebugASPDemo"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /Gm /GX /ZI /Od /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /D "LOCAL_DEBUG_ECHO" /D "SYSLIST_DEMO" /FR /Yu"stdafx.h" /FD /GZ /c
# ADD CPP /nologo /W3 /Gm /GX /ZI /Od /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /D "LOCAL_DEBUG_ECHO" /D "SYSLIST_DEMO" /D "ASP_DEMO" /D "SYSLIST_ACC" /FR /Yu"stdafx.h" /FD /GZ /c
# ADD BASE RSC /l 0x409 /d "_DEBUG"
# ADD RSC /l 0x409 /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib ole32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /debug /machine:I386 /out:"DebugDemo/SCAInv.exe" /pdbtype:sept /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT BASE LINK32 /pdb:none
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib ole32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /debug /machine:I386 /out:"DebugASPDemo/SCAInv.exe" /pdbtype:sept /libpath:"$(OPENSSL)\lib\VC\Static" /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT LINK32 /pdb:none

!ELSEIF  "$(CFG)" == "TestCollect - Win32 ASP Demo Release"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "TestCollect___Win32_ASP_Demo_Release"
# PROP BASE Intermediate_Dir "TestCollect___Win32_ASP_Demo_Release"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "ReleaseASPDemo"
# PROP Intermediate_Dir "ReleaseASPDemo"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /GX /O2 /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "NDEBUG" /D "_CONSOLE" /D "_MBCS" /D "SYSLIST_DEMO" /FR /Yu"stdafx.h" /FD /c
# SUBTRACT BASE CPP /u
# ADD CPP /nologo /W3 /GX /O2 /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "NDEBUG" /D "_CONSOLE" /D "_MBCS" /D "SYSLIST_DEMO" /D "ASP_DEMO" /D "SYSLIST_ACC" /FR /Yu"stdafx.h" /FD /c
# SUBTRACT CPP /u
# ADD BASE RSC /l 0x409 /d "NDEBUG"
# ADD RSC /l 0x409 /d "NDEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /machine:I386 /out:"ReleaseDemo/SCAInv.exe" /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT BASE LINK32 /pdb:none
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /machine:I386 /out:"ReleaseASPDemo/SCAInv.exe" /libpath:"$(OPENSSL)\lib\VC\Static" /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT LINK32 /pdb:none

!ELSEIF  "$(CFG)" == "TestCollect - Win32 ACC Debug"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 1
# PROP BASE Output_Dir "TestCollect___Win32_ACC_Debug"
# PROP BASE Intermediate_Dir "TestCollect___Win32_ACC_Debug"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 1
# PROP Output_Dir "DebugACC"
# PROP Intermediate_Dir "DebugACC"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /Gm /GX /ZI /Od /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /D "LOCAL_DEBUG_ECHO" /D "SYSLIST_DEMO" /D "ASP_DEMO" /FR /Yu"stdafx.h" /FD /GZ /c
# ADD CPP /nologo /W3 /Gm /GX /ZI /Od /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /D "LOCAL_DEBUG_ECHO" /D "SYSLIST_DEMO" /D "ASP_DEMO" /FR /Yu"stdafx.h" /FD /GZ /c
# ADD BASE RSC /l 0x409 /d "_DEBUG"
# ADD RSC /l 0x409 /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib ole32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /debug /machine:I386 /out:"DebugASPDemo/SCAInv.exe" /pdbtype:sept /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT BASE LINK32 /pdb:none
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib ole32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /debug /machine:I386 /out:"DebugACC/SCAInv.exe" /pdbtype:sept /libpath:"$(OPENSSL)\lib\VC\Static" /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT LINK32 /pdb:none

!ELSEIF  "$(CFG)" == "TestCollect - Win32 ACC Release"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "TestCollect___Win32_ACC_Release"
# PROP BASE Intermediate_Dir "TestCollect___Win32_ACC_Release"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "ReleaseACC"
# PROP Intermediate_Dir "ReleaseACC"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /GX /O2 /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "NDEBUG" /D "_CONSOLE" /D "_MBCS" /D "SYSLIST_DEMO" /D "ASP_DEMO" /FR /Yu"stdafx.h" /FD /c
# SUBTRACT BASE CPP /u
# ADD CPP /nologo /W3 /GX /O2 /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "NDEBUG" /D "_CONSOLE" /D "_MBCS" /D "SYSLIST_DEMO" /D "ASP_DEMO" /FR /Yu"stdafx.h" /FD /c
# SUBTRACT CPP /u
# ADD BASE RSC /l 0x409 /d "NDEBUG"
# ADD RSC /l 0x409 /d "NDEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /machine:I386 /out:"ReleaseASPDemo/SCAInv.exe" /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT BASE LINK32 /pdb:none
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /machine:I386 /out:"ReleaseACC/SCAInv.exe" /libpath:"$(OPENSSL)\lib\VC" /libpath:"$(MSSDK)\lib"
# SUBTRACT LINK32 /pdb:none

!ELSEIF  "$(CFG)" == "TestCollect - Win32 Debug_Instrument"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 1
# PROP BASE Output_Dir "TestCollect___Win32_Debug_Instrument"
# PROP BASE Intermediate_Dir "TestCollect___Win32_Debug_Instrument"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 1
# PROP Output_Dir "Win32_Debug_Instrument"
# PROP Intermediate_Dir "Debug_Instrument"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /Gm /GX /ZI /Od /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /D "LOCAL_DEBUG_ECHO" /FR /Yu"stdafx.h" /FD /GZ /c
# ADD CPP /nologo /W3 /Gm /GX /ZI /Od /I "$(MSSDK)\include" /I "$(OPENSSL)\include" /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /D "LOCAL_DEBUG_ECHO" /D "INSTRUMENTED" /FR /Yu"stdafx.h" /FD /GZ /c
# ADD BASE RSC /l 0x409 /d "_DEBUG"
# ADD RSC /l 0x409 /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib ole32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32.lib libeay32.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /debug /machine:I386 /out:"Debug/SCAInv.exe" /pdbtype:sept /libpath:"$(MSSDK)\lib" /libpath:"$(OPENSSL)\lib\VC"
# SUBTRACT BASE LINK32 /pdb:none
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib ole32.lib Wbemuuid.lib SetupAPI.lib Iphlpapi.lib Ws2_32.lib Mswsock.lib rpcrt4.lib ssleay32MD.lib libeay32MD.lib wininet.lib Netapi32.lib /nologo /subsystem:windows /debug /machine:I386 /out:"Debug_Instrument/SCAInv.exe" /pdbtype:sept /libpath:"$(OPENSSL)\lib\VC" /libpath:"$(MSSDK)\lib"
# SUBTRACT LINK32 /pdb:none

!ENDIF 

# Begin Target

# Name "TestCollect - Win32 Release"
# Name "TestCollect - Win32 Debug"
# Name "TestCollect - Win32 Demo Debug"
# Name "TestCollect - Win32 Demo Release"
# Name "TestCollect - Win32 ASP Demo Debug"
# Name "TestCollect - Win32 ASP Demo Release"
# Name "TestCollect - Win32 ACC Debug"
# Name "TestCollect - Win32 ACC Release"
# Name "TestCollect - Win32 Debug_Instrument"
# Begin Group "Source Files"

# PROP Default_Filter "cpp;c;cxx;rc;def;r;odl;idl;hpj;bat"
# Begin Source File

SOURCE=.\DiskCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\DisplayCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\KeyboardCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\KeyDecode.cpp
# End Source File
# Begin Source File

SOURCE=.\LogicalDiskCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\MasterCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\MemoryCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\MouseCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\NetCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\OSCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\PrinterCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\ProcessorCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\RegUtil.cpp
# End Source File
# Begin Source File

SOURCE=.\SoftwareCollector.cpp
# End Source File
# Begin Source File

SOURCE=.\StdAfx.cpp
# ADD CPP /Yc"stdafx.h"
# End Source File
# Begin Source File

SOURCE=..\TestInstConfig\SyslistMethod.cpp
# End Source File
# Begin Source File

SOURCE=..\TestWinCrypt\SyslistPhrase.cpp
# End Source File
# Begin Source File

SOURCE=..\TestInstConfig\SyslistProxyMethod.cpp
# End Source File
# Begin Source File

SOURCE=..\TestInstConfig\SyslistRegistry.cpp
# End Source File
# Begin Source File

SOURCE=.\TestCollect.cpp
# End Source File
# Begin Source File

SOURCE=..\TestData\TextConv.cpp
# End Source File
# Begin Source File

SOURCE=.\WinSetupUtil.cpp
# End Source File
# Begin Source File

SOURCE=.\WMIUtil.cpp
# End Source File
# End Group
# Begin Group "Header Files"

# PROP Default_Filter "h;hpp;hxx;hm;inl"
# Begin Source File

SOURCE=.\AutoRegKey.h
# End Source File
# Begin Source File

SOURCE=.\CollectProto.h
# End Source File
# Begin Source File

SOURCE=.\DiskCollect.h
# End Source File
# Begin Source File

SOURCE=.\DisplayCollector.h
# End Source File
# Begin Source File

SOURCE=..\TestTransport\FileTransport.h
# End Source File
# Begin Source File

SOURCE=.\HardwareCollector.h
# End Source File
# Begin Source File

SOURCE=..\TestTransport\HTTPSecureTransport.h
# End Source File
# Begin Source File

SOURCE=..\TestTransport\HTTPTransport.h
# End Source File
# Begin Source File

SOURCE=.\KeyboardCollector.h
# End Source File
# Begin Source File

SOURCE=.\KeyDecode.h
# End Source File
# Begin Source File

SOURCE=.\LogicalDiskCollector.h
# End Source File
# Begin Source File

SOURCE=.\MasterCollector.h
# End Source File
# Begin Source File

SOURCE=.\MemoryCollector.h
# End Source File
# Begin Source File

SOURCE=.\MonitorCollector.h
# End Source File
# Begin Source File

SOURCE=.\MouseCollector.h
# End Source File
# Begin Source File

SOURCE=.\NetCollector.h
# End Source File
# Begin Source File

SOURCE=.\OSCollector.h
# End Source File
# Begin Source File

SOURCE=.\PrinterCollector.h
# End Source File
# Begin Source File

SOURCE=.\ProcessorCollector.h
# End Source File
# Begin Source File

SOURCE=.\RegUtil.h
# End Source File
# Begin Source File

SOURCE=..\TestInstConfig\resource.h
# End Source File
# Begin Source File

SOURCE=.\SeqListCollector.h
# End Source File
# Begin Source File

SOURCE=.\SoftwareCollector.h
# End Source File
# Begin Source File

SOURCE=.\StdAfx.h
# End Source File
# Begin Source File

SOURCE=..\TestTransport\SyslistHTTPTransport.h
# End Source File
# Begin Source File

SOURCE=..\TestInstConfig\SyslistMethod.h
# End Source File
# Begin Source File

SOURCE=..\TestInstConfig\SyslistProxyMethod.h
# End Source File
# Begin Source File

SOURCE=..\TestInstConfig\SyslistRegistry.h
# End Source File
# Begin Source File

SOURCE=.\WinSetupUtil.h
# End Source File
# Begin Source File

SOURCE=.\WMIUtil.h
# End Source File
# End Group
# Begin Group "Resource Files"

# PROP Default_Filter "ico;cur;bmp;dlg;rc2;rct;bin;rgs;gif;jpg;jpeg;jpe"
# Begin Source File

SOURCE=".\companion_agent_icon-16.ico"
# End Source File
# Begin Source File

SOURCE=".\companion_agent_icon-32.ico"
# End Source File
# Begin Source File

SOURCE=.\SyslistConfigResources.rc
# End Source File
# End Group
# Begin Source File

SOURCE=.\ReadMe.txt
# End Source File
# End Target
# End Project
