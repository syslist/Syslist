#include <iostream>
#include <som/SyslistHTTPTransport.h>
#include <som/ScreenTransport.h>
#include "MasterCollector.h"
#include "MacUtil/IOUtil.h"
#include <math.h>
#include "BSAConfig.h"

#ifdef __ppc__
#define  IS_NAN __isnand
#else
#define IS_NAN isnan
#endif

USING_IO_UTIL

const long ExpirationDays = 30;
sig_t g_OrigBusHandler;
sig_t g_OrigSegHandler; 

void AppendPort (string & DestURL, long PortNum)
{
	ostringstream ProxyPortConvert;
	ProxyPortConvert << PortNum;
	
	DestURL += ":";
	DestURL += ProxyPortConvert.str();
}

void FinalSignalReturn(sig_t origHandler, int sigArg, int exitVal)
{
	pid_t ChildPID = 0;
	
	ChildPID = fork();
	
	// Child needs to be allowed to handle signal normally
	// So that we can get crash reports if they are generated.		
	if (ChildPID == 0)  { 
		origHandler(sigArg);
		// note fall through to exit below, though the above should also exit
	}
	
	exit (exitVal); // We need to leave with the right exit code to notify parent
}	

void BusErrExit(int sigArg) 
{
	FinalSignalReturn(g_OrigBusHandler, sigArg, 64);
}

void SegErrExit(int sigArg)
{
	FinalSignalReturn(g_OrigBusHandler, sigArg, 65);
}

int main (int argc, char * const argv[]) 
{
	// We'll handle the pipe errors in line instead of in signal code;
	signal(SIGPIPE, SIG_IGN);

	// We need to handle bus and segfault too - cocoa NSTask cannot distinguish them
	// when we ask for the return values, creating unhappy customers by issuing
	// scary (mistaken) messages
	g_OrigBusHandler = signal(SIGBUS, BusErrExit);
	g_OrigSegHandler = signal(SIGSEGV, SegErrExit);
	 
	SyslistPrefs::Sync();
	
	long FailedContact = 0;
	SyslistPrefs::getFailedContact(FailedContact);
	
#ifdef SYSLIST_DEMO
	// Stop trying after 10 failures
	if (FailedContact > 10)
		return -15;
		
	// Stop collecting after the evaluation period in Demo mode.
	CFAbsoluteTime FirstInstall = NAN;
	SyslistPrefs::getReferenceTime (FirstInstall);
	CFAbsoluteTime CurrTime = CFAbsoluteTimeGetCurrent();
	
	// Set the first inventory into storage if need be
	if (IS_NAN(FirstInstall)) {
		FirstInstall = CurrTime;
		SyslistPrefs::setReferenceTime (CurrTime);
		SyslistPrefs::Sync();
	}
	
	// Add the expiration period to the current time
	CFGregorianUnits CutOffInterval = {0};
	CutOffInterval.days = ExpirationDays;
	
	CFAbsoluteTime CutOffTime = 
		CFAbsoluteTimeAddGregorianUnits(
			FirstInstall, NULL, CutOffInterval);
	
	// Compare our times - After the demo period - leave now with error;
	if (CurrTime > CutOffTime) 
		return -7; // Demo expired error;
#endif 

	string SyslistConfigServer;
	string SyslistConfigProxyServer;
	enumSLProxyMode SyslistConfigProxyMode = kSLDefProxyMode;
	
	long SyslistConfigServerPort = kSLDefServerPort;
	long SyslistConfigProxyPort = kSLDefProxyPort;
	
#ifndef SYSLIST_BSA	
	SyslistPrefs::getServerURL(SyslistConfigServer);
	SyslistPrefs::getProxyAddress(SyslistConfigProxyServer);
	SyslistPrefs::getProxyMode(SyslistConfigProxyMode);
	
	SyslistPrefs::getServerPort(SyslistConfigServerPort);
	SyslistPrefs::getProxyPort (SyslistConfigProxyPort);
#else
	string FallBackFilePath;
	
	SyslistConfigProxyMode = kSLProxySystem;
	SyslistConfigProxyPort = -1;
	
	CFAuto<CFDataRef> XMLData;
	CFAuto<CFDictionaryRef> ConfigInfo;
	CFAuto<CFBundleRef> AppBundle;
	CFAuto<CFStringRef> ConfigDirPath;
	CFAuto<CFStringRef> ConfigFilePath;
	CFAuto<CFURLRef> ConfigFilePathURL;
	
	AppBundle.Copy(CFBundleGetMainBundle());
	
/*	ConfigFilePathURL.Attach( 
		CFBundleCopyBundleURL(AppBundle));
*/

	ConfigFilePathURL.Attach(
		CFBundleCopyResourceURL(
			AppBundle, 
			CFSTR("FieldConfig.plist"),
			NULL, NULL));
	
	if (ConfigFilePathURL.IsEmpty()){
		
		fprintf(stderr, "Unable to locate the configuration information\n");
		return 13;
	}
	
	CFAuto<CFStringRef> URLPath;
	URLPath.Attach( CFURLGetString(ConfigFilePathURL));
	string URLPathCString;
	URLPath.GetCString(URLPathCString);
	
	fprintf(stdout, "Config File Location: '%s'\n" , URLPathCString.c_str());
	
	CFRetain(ConfigFilePathURL);

	SInt32 ErrorCode;
	CFURLCreateDataAndPropertiesFromResource(
		kCFAllocatorDefault,
		ConfigFilePathURL,
		&XMLData,
		NULL,
		NULL,
		&ErrorCode);
	
	if (XMLData.IsEmpty()) {
		fprintf (stderr, "Unable to load data from configuration file\n");
		return 14;
	}

	ConfigInfo.Attach( 
		(CFDictionaryRef)
		CFPropertyListCreateFromXMLData(
			kCFAllocatorDefault,
			XMLData,
			kCFPropertyListImmutable,
			NULL));
	
	if (ConfigInfo.IsEmpty()) {
		fprintf (stderr, "The configuration file is corrupt");
		return 15;
	}

	CFAuto<CFStringRef> ReportURL;
	CFAuto<CFNumberRef> ReportPort;
	CFAuto<CFStringRef> ReportPath;
	
	if (CFDictionaryContainsKey(ConfigInfo, kBSAKey_URL)) {
		ReportURL.Copy (
			(CFStringRef) CFDictionaryGetValue(ConfigInfo,kBSAKey_URL));
		ReportURL.GetCString(SyslistConfigServer);
	}
	
	if (CFDictionaryContainsKey(ConfigInfo, kBSAKey_Port)) {
		ReportPort.Copy (
			(CFNumberRef) CFDictionaryGetValue(ConfigInfo,kBSAKey_Port));
		CFNumberGetValue(ReportPort, kCFNumberSInt32Type,& SyslistConfigServerPort);	
	}		
	
	if (CFDictionaryContainsKey(ConfigInfo, kBSAKey_Path)) {
		ReportPath.Copy (
			(CFStringRef) CFDictionaryGetValue(ConfigInfo,kBSAKey_Path));
		ReportPath.GetCString(FallBackFilePath);
	}
	
	fprintf (stdout, "Config is Server: '%s' , Port: '%ld', Path:'%s'\n\n",
				SyslistConfigServer.c_str(), SyslistConfigServerPort, FallBackFilePath.c_str());
	fflush(stdout);
#endif	
	
	SyslistHTTPTransport<HTTPTransport> HTTPTransportObj ;
	SyslistHTTPTransport<HTTPSecureTransport> HTTPSecureTransportObj;

	FileTransport FileTransportObj;

	DataTransportProto * ReportTransport = NULL;
	HTTPTransport * ProxyTransport = NULL;

	char TransportScan[64] = "";

	sscanf(SyslistConfigServer.c_str(),"%[^:]", TransportScan);
	
	if (!strcasecmp(TransportScan, HTTPTransportObj.HandlePrefix())) {
		ReportTransport = &HTTPTransportObj;
		ProxyTransport = &HTTPTransportObj;
	}
	else if (!strcasecmp(TransportScan, HTTPSecureTransportObj.HandlePrefix())) {
		ReportTransport = &HTTPSecureTransportObj;
		ProxyTransport = &HTTPSecureTransportObj;
	}	
	else if (!strcasecmp(TransportScan, FileTransportObj.HandlePrefix()))
		ReportTransport  = & FileTransportObj;
	else
		return -3;

	if (ProxyTransport != NULL) {
	
		if (SyslistConfigProxyPort != kSLDefProxyPort) 
			AppendPort(SyslistConfigProxyServer, SyslistConfigProxyPort);
		
		ProxyTransport->SetProxyInfo (SyslistConfigProxyServer.c_str(), SyslistConfigProxyMode);
	}

	long Status;

	IOUtil::Init();

	MasterCollector SyslistDataCollector;
	
	NVDataItem *SyslistData;
	Status = SyslistDataCollector.Collect ( & SyslistData);
	if (Status != ERROR_SUCCESS)
		return -4;
	
	// for automatic deletion
	auto_ptr<NVDataItem> AutoSyslistData(SyslistData);
		
	if (SyslistConfigServerPort != kSLDefServerPort) 
		AppendPort(SyslistConfigServer, SyslistConfigServerPort);
	
#ifdef SYSLIST_BSA
	bool ReportFailed = false;
#endif

	Status = ReportTransport->OpenURI(SyslistConfigServer.c_str());
	if (Status != ERROR_SUCCESS) {
		FailedContact ++;
		SyslistPrefs::setFailedContact(FailedContact);
		SyslistPrefs::Sync();
#ifndef SYSLIST_BSA
		return -6;
#else
		ReportFailed = true;
#endif
	}
	else {
		Status = ReportTransport->TransmitData (SyslistData);
		if (Status != ERROR_SUCCESS) {
			FailedContact ++;
			SyslistPrefs::setFailedContact(FailedContact);
			SyslistPrefs::Sync();
#ifndef SYSLIST_BSA
			return Status;
#else
			ReportFailed = true;
#endif
		}
	}
	
	ReportTransport->Close();	

#ifdef SYSLIST_BSA
	// Write to specified file if the system failed.
	if (ReportFailed) {
		string FullFileURI("File://");
		FullFileURI.append (FallBackFilePath);
		Status = FileTransportObj.OpenURI(FullFileURI.c_str());
		
		if (Status != ERROR_SUCCESS)
			return -6;
		
		Status = FileTransportObj.TransmitData(SyslistData);
		if (Status != ERROR_SUCCESS)
			return Status;
	
		FileTransportObj.Close();
	}
#endif

	CFAbsoluteTime InventoryTime = CFAbsoluteTimeGetCurrent();
	
	SyslistPrefs::setLastTime(InventoryTime);
	
	SyslistPrefs::Sync();

	return 0;
}
