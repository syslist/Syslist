#include <sys/types.h>
#include <sys/wait.h>
#include <math.h>
#include <SyslistPrefs.h>
#include <iostream>
#include <fstream>
#include <sstream>
#include <curses.h>
#include <unistd.h>

#ifdef __ppc__
#define  IS_NAN __isnand
#else
#define IS_NAN isnan
#endif

const char * LogFileName = "TimedExecLog.txt";

const char * CollectorName = "SCACollector";

const CFTimeInterval OneSecond = 1.0;
const CFTimeInterval OneMinute = OneSecond * 60;
const CFTimeInterval OneHour = OneMinute * 60;

const CFTimeInterval SurveyTime = OneHour * 4;
//const CFTimeInterval SurveyTime = OneHour * 1;
const CFTimeInterval DebugTime = OneMinute * 15;

const CFTimeInterval OneDay = OneHour * 24;
const CFTimeInterval OneWeek = OneDay * 7;
const CFTimeInterval OneMonth = OneDay * 30;

const CFTimeInterval BufferTime = 20 * OneSecond;

CFRunLoopRef g_MasterLoop = NULL;

long GetProcDir(std::string & Dest)
{
	CFAuto<CFURLRef> TestURL(
		CFBundleCopyExecutableURL(CFBundleGetMainBundle()));
		
	CFAuto<CFStringRef> PathCFString(
		CFURLCopyFileSystemPath(TestURL,kCFURLPOSIXPathStyle));
	
	long PathLen = CFStringGetLength(PathCFString) + 1;
	
	char PathString[PathLen];
	
	//AutoCharBuf PathString(PathLen);
	CFStringGetCString(PathCFString, PathString, PathLen ,kCFStringEncodingUTF8);
	
	char* PathTerm = strrchr(PathString, '/');
	
	if (PathTerm != NULL)
		*(PathTerm + 1) = '\0';
		
	Dest = PathString;
	
	return 0;	
}

void GetDateString(CFAbsoluteTime Time, std::string & DestString)
{	
	CFGregorianDate DateItems;
	
	DateItems = CFAbsoluteTimeGetGregorianDate(Time, NULL);
	
	std::ostringstream DateFormatter;
	
	DateFormatter 
		<< "["
		<< (long) DateItems.year << "-"
		<< (long) DateItems.month << "-"
		<< (long) DateItems.day << "]~["
		<< (long) DateItems.hour << ":" 
		<< (long) DateItems.minute << ":"  
		<< (long) DateItems.second << "]";
		
	DestString = DateFormatter.str();
}

long ExecuteCollector()
{
	pid_t ChildPID = 0;
	
	ChildPID = fork();
		
	if (ChildPID == 0)  { // Child needs to launch collector
	
		std::string ExecPath;
		GetProcDir (ExecPath);

		ExecPath += "/";
		ExecPath += CollectorName;
		
		signal (SIGTERM, SIG_DFL);
		signal (SIGHUP, SIG_DFL);
		
		//long ExecStat = 
		execl(ExecPath.c_str(), CollectorName, NULL);
		
		exit (99);
	}
	
	// Wait for execing child
	int ChildStatus = 0;
	pid_t WaitStatus;

	do {
		WaitStatus = waitpid(ChildPID, &ChildStatus, 0);
	} 
	while (WaitStatus == -1 && errno == EINTR);
	
	if (WaitStatus < 0) {
		//long ErrNumber = errno;
		return -1;
	}

	if (WIFSIGNALED(ChildStatus)) {
		//long ChildSig = WTERMSIG(ChildStatus);
		return -1;
	}
		
	if (!WIFEXITED(ChildStatus))
		return -1;
		
	long ChildExitCode = WEXITSTATUS (ChildStatus);
	
	return ChildExitCode;
}

bool CheckTime (bool IsStartup, CFAbsoluteTime & NextTime, CFAbsoluteTime &DeltaTime, CFAbsoluteTime & LastRunAlign)
{
	LastRunAlign = NAN;
	CFAbsoluteTime LastExecTime = NAN;
	enumSLFreq CheckFreq = kSLFreqDisabled;
	
	SyslistPrefs::Sync();

	SyslistPrefs::getFrequency(CheckFreq);
	SyslistPrefs::getAlignTime(LastRunAlign);
	SyslistPrefs::getLastTime(LastExecTime);
	
	CFAbsoluteTime DeltaCompare = NAN;
	
	switch (CheckFreq) {
	
	case kSLFreqDisabled:
		DeltaTime = 0;
		return false;
		
	case kSLFreqStartup:
		DeltaTime = 0;
		return IsStartup;
		
	case kSLFreqDay:
		DeltaCompare = OneDay;
		break;

	case kSLFreqWeek:
		DeltaCompare = OneWeek;
		break;

	case kSLFreqMonth:
		DeltaCompare = OneMonth;
		break;

	case kSLFreqDebug:
		DeltaCompare = DebugTime;
		break;

	default:
		DeltaTime = NAN;
		return FALSE;
	}
		
	CFAbsoluteTime CurrTime = CFAbsoluteTimeGetCurrent();

	if (!IS_NAN(LastRunAlign) )
		NextTime = LastRunAlign + DeltaCompare;
	else if (!IS_NAN(LastExecTime))
		NextTime = LastExecTime + DeltaCompare;
	else
		NextTime = DeltaCompare;

	CurrTime += BufferTime;
	
	if (CurrTime >= NextTime) {
	
		//if (!__isnand	(LastExecTime)) {
		if (!IS_NAN(LastExecTime)) {
			
			CFAbsoluteTime TimeDelta = CurrTime - NextTime;
			CFAbsoluteTime QuantTimeDelta;
			modf((TimeDelta / DeltaCompare), &QuantTimeDelta); // throw away fractional
			QuantTimeDelta *= DeltaCompare;
			
			LastRunAlign = NextTime + QuantTimeDelta;
		}
		
		return true;
	}
	
	return false;
}

void CheckRun(bool IsStartup, bool ForceRun = false)	
{
	std::string LogPath;
	GetProcDir (LogPath);
	
	CFAbsoluteTime DeltaTime;
	CFAbsoluteTime NextTime;
	CFAbsoluteTime AlignTime;
	
	bool ShouldRun = ForceRun;
	if (ForceRun == false)
		ShouldRun = CheckTime (IsStartup, NextTime, DeltaTime, AlignTime);
	
	LogPath += '/';
	
	LogPath += LogFileName;
	
	std::ofstream LogStream;
	
	LogStream.open (LogFileName);
	
	LogStream << "SCATimeCheck Log" << std::endl;
	
	std::string TimeString;

	CFAbsoluteTime CurrTime;
	CurrTime = CFAbsoluteTimeGetCurrent();
	GetDateString(CurrTime, TimeString);
	
	LogStream << "Current Time is: " << TimeString << std::endl;
	
	if (ShouldRun) {
		
		LogStream << "It is time to run the Collector" << std::endl;
		
		long Status;
		
		Status = ExecuteCollector();
		
		if (Status == 0) {
			
			// Write back the aligned exec time if we suceeded.
			SyslistPrefs::setAlignTime (AlignTime);
			SyslistPrefs::Sync();
		}
			
		LogStream << "Collector returned: " << Status << std::endl;
	}
	else {
		LogStream << "It is not yet time to run the collector" << std::endl;
	}
		
	LogStream.close();

}

void InventoryCheckTimerFunc( CFRunLoopTimerRef Timer, void * info)
{
	CheckRun(false);
}

void CancelExec (int WTFIT)
{
	CFRunLoopStop (g_MasterLoop);
}

void ForceStartup (int WTFIT)
{
	CheckRun(true, false);
}

void ForceInventory (int WTFIT)
{
	CheckRun(false, true);
}

int main (int argc, char * const argv[]) 
{	
	g_MasterLoop = CFRunLoopGetCurrent();
		
	signal (SIGTERM, CancelExec);
	signal (SIGHUP, ForceStartup);
	signal (SIGALRM, ForceInventory);
	
	// a bit of runway for network services
	sleep(30);
	
	CheckRun(true); // initial check

	CFAuto<CFRunLoopTimerRef> SCATimer;
	
	SCATimer.Attach(
		CFRunLoopTimerCreate(
			kCFAllocatorDefault,
			CFAbsoluteTimeGetCurrent() + SurveyTime,
			SurveyTime, 
			0, 0, 
			InventoryCheckTimerFunc,
			NULL));
	
	CFRunLoopAddTimer(g_MasterLoop, SCATimer, kCFRunLoopDefaultMode);
	
	CFRunLoopRun();

    CFRunLoopTimerInvalidate( SCATimer );
}

	