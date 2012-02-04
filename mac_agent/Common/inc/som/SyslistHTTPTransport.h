#ifndef SYSLIST_HTTP_TRANSPORT_H_INCLUDED
#define SYSLIST_HTTP_TRANSPORT_H_INCLUDED

#include "HTTPSecureTransport.h"
#include <sys/sysctl.h>

static const long CFG_STRING_LEN = 512;
const char * LogPath = "SyslistHTTPResponse.txt";

template < class T >
class SyslistHTTPTransport: 
	public T
{
public:
	SyslistHTTPTransport()
	{
		this->m_MIMESeparator = "----------Syslist-XML-Data-Upload-Boundary-" + this->m_InstanceUUID;
		this->m_FileName = "SyslistAgentDump.xml";
		this->m_DataName = "SyslistAgentData";
#ifndef SYSLIST_BSA
		this->m_ServerDoc = "/agentReport.php";
#endif

#ifndef SYSLIST_BSA
		this->m_ClientAgent = "SyslistAgent";
#else
		this->m_ClientAgent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Q312461)";
#endif

	}

	virtual ~SyslistHTTPTransport() {};

	
	long ReadRegInfo()
	{
		SyslistPrefs::Sync();
		SyslistPrefs::getMachID(m_SyslistConfigID);
		return ERROR_SUCCESS;
	}

	long WriteRegInfo(char * WriteBackID )
	{
		SyslistPrefs::setMachID(std::string(WriteBackID));
		SyslistPrefs::Sync();
		return ERROR_SUCCESS;
	}
	
	long GetProcDir(std::string & Dest)
	{
		int ProcRequest[] = {CTL_KERN, KERN_PROC, KERN_PROC_PID, getpid()};
		int Status;
		
		size_t ProcInfoSize = 0;
		Status = sysctl(ProcRequest, 4, NULL, &ProcInfoSize, NULL, 0);
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;
		
		AutoCharBuf ProcInfoBuf(ProcInfoSize);
		Status = sysctl(ProcRequest, 4, (void *) ProcInfoBuf, &ProcInfoSize, NULL, 0);
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;
	
		// struct kinfo_proc * ProcInfo = (struct kinfo_proc *) ProcInfoBuf.m_Buf;
		
		CFAuto<CFURLRef> TestURL(
			CFBundleCopyExecutableURL(CFBundleGetMainBundle()));
			
		CFAuto<CFStringRef> PathCFString(
			CFURLCopyFileSystemPath(TestURL,kCFURLPOSIXPathStyle));
		
		long PathLen = CFStringGetLength(PathCFString) + 1;
		
		//AutoCharBuf PathString(PathLen);
		char PathString[PathLen];
		
		CFStringGetCString(PathCFString, PathString, PathLen ,kCFStringEncodingUTF8);
		
		char* PathTerm = strrchr(PathString, '/');
		
		if (PathTerm != NULL)
			*(PathTerm + 1) = '\0';
			
		Dest = PathString;
		
		return ERROR_SUCCESS;	
	}
		
		
		

	virtual long ReadResponse()
	{

		long ReturnStatus = -1;

		char ResponseLine[2048];
		long Status;
		
		long ReadLen;

		ReadRegInfo();

		ReadLen = 2048;
		T::ReadLine(ResponseLine, &ReadLen);
		
		ofstream LogStream;

		std::string FullLogPath;
		GetProcDir(FullLogPath);
		FullLogPath.append(LogPath);
		LogStream.open(FullLogPath.c_str());

#ifdef LOCAL_DEBUG_ECHO
		cout << ResponseLine;
#endif
		LogStream << ResponseLine;

		char WriteBackID[64];
		WriteBackID[0] = '\0';

		long ResponseCode;
		char ProtocolExtract[64] = "";

		sscanf (ResponseLine, "%63[^/]%*s %ld", ProtocolExtract, &ResponseCode);
		
		if (strcasecmp(ProtocolExtract, "HTTP") 
			|| ResponseCode != 200)
			ReturnStatus = ResponseCode;

		bool IsChunked = false, InBody=false;
		bool Saw0CRLF = false, SawCRLF2 = false;

		char ScanToken[256];
		char *ScanLoc;
	
		while (ReadLen = 2048,
				Status = this->ReadLine(ResponseLine, &ReadLen),
				Status == ERROR_SUCCESS) {
	
#ifdef LOCAL_DEBUG_ECHO
			cout << ResponseLine;
#endif
			LogStream << ResponseLine;

			// this is *NIX ! ReadLen == 0 means it's GONE!
			if (ReadLen == 0)
				break;

			//HardwareID
			//UpdateStatus
			//ErrorNumber
	
			ScanToken[0]='\0';
			
			ScanLoc = ResponseLine;
			while (*ScanLoc != '\0' && (*ScanLoc == '\t' || *ScanLoc == ' '))
				ScanLoc ++;

			sscanf(ScanLoc,"%[^:= \t]", ScanToken);
			
			if (!InBody && !strcasecmp(ScanToken,"TRANSFER-ENCODING")) {
				sscanf (ResponseLine,"%*[^:]:%s", ScanToken);

				if (!strcasecmp (ScanToken, "CHUNKED"))
					IsChunked = true;
			}

			else if (InBody && !strcasecmp(ScanToken, "HARDWAREID")) {

				sscanf (ResponseLine,"%*[^=]%*1[=]%*[\" \t]%[^\"]%*1[\"]", WriteBackID);
			}

			else if (InBody && !strcasecmp(ScanToken, "ERRORNUMBER")) {

				sscanf (ResponseLine,"%*[^=]%*1[=]%*[\" \t]%ld%*1[\"]", &ReturnStatus);
			}

			else if (InBody && !strcasecmp(ScanToken, "UPDATESTATUS")) {
				char ServerReturnStatus[64];
				sscanf (ResponseLine,"%*[^=]%*1[=]%*[\" \t]%[^\"]%*1[\"]", ServerReturnStatus);
			}


			//Check to see if the server has returned the end of 
			//it's data so we don't hang waiting.
			//Check only Whole lines rec'v others cannot possible
			//match the conditions we are looking for.
			if (ReadLen > 0 && ResponseLine[ReadLen] == '\0') {
				
				if (!strcmp("\r\n", ResponseLine) || !strcmp("\n",ResponseLine) || !strcmp("\r",ResponseLine)) {
					if (IsChunked && Saw0CRLF) {
						SawCRLF2 = true;
						break;
					}
					else {
						//If we see two of these in a row, we
						//have left the header and we are in the body
						//mark our state as such for other parts of the
						//line parser.
						if (!InBody && !Saw0CRLF)
							InBody = true;

					}

				}
				else if (!strcmp("0\r\n", ResponseLine) || !strcmp("0\n", ResponseLine) || !strcmp("0\r", ResponseLine)) {
					Saw0CRLF = true;
				}
				else {
					Saw0CRLF = SawCRLF2 = false;
				}
			}
		}

#ifdef LOCAL_DEBUG_ECHO
		cout << endl;
#endif
		BOOL WriteID = FALSE;

		switch (ReturnStatus) {

		case 0:
			if (m_SyslistConfigID != WriteBackID && WriteBackID[0] != '\0')
				WriteID = TRUE;

			if (WriteBackID[0] == '\0')
				ReturnStatus = -8;

			break;
		case 1:
			WriteBackID[0] = '\0';
			WriteID = TRUE;
			break;
		default:
			break;
		}

		if (WriteID) {
			Status = WriteRegInfo(WriteBackID);
			if (Status != ERROR_SUCCESS)
				ReturnStatus = -2;
		}

		LogStream.close();

		return ReturnStatus;
	}

private:
	 string m_SyslistConfigID;

};
#endif
