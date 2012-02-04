#ifndef SYSLIST_HTTP_TRANSPORT_H_INCLUDED
#define SYSLIST_HTTP_TRANSPORT_H_INCLUDED

#include "HTTPSecureTransport.h"
#include "../TestWinCrypt/SimpleCrypt.h"
#include "../TestInstConfig/SyslistRegistry.h"

static const long CFG_STRING_LEN = 512;

template < class T >
class SyslistHTTPTransport: 
	public T
{
public:
	SyslistHTTPTransport()
	{
		m_MIMESeparator= "----------Syslist-XML-Data-Upload-Boundary-" + m_InstanceUUID;
		m_FileName = "SyslistAgentDump.xml";
		m_DataName = "SyslistAgentData";
		m_ServerDoc = "/agentReport.php";
		m_ClientAgent = "SyslistAgent";
	}

	virtual ~SyslistHTTPTransport() {};

	
	long ReadRegInfo()
	{

		HKEY SyslistRegKey;
		long Status;

		Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SYSLIST_REG_LOC, NULL, KEY_READ, & SyslistRegKey);
		if (Status != ERROR_SUCCESS)
			return Status;

		char ReturnRegString[CFG_STRING_LEN];
		char SyslistConfigID[CFG_STRING_LEN];
		unsigned long ReturnSize;
		DWORD ReturnType;

		// These are encrypted via Windows Encryption...
		SimpleStringPWCrypt RegDecrypt(SyslistPhrase);

		ReturnRegString[0] = '\0';
		SyslistConfigID[0] = '\0';

		ReturnSize = CFG_STRING_LEN;
		Status = RegQueryValueEx(SyslistRegKey, "ID", NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
		if (Status == ERROR_SUCCESS) {
			Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigID, ReturnRegString, ReturnSize, ReturnType);
		}
		m_SyslistConfigID = SyslistConfigID;

		RegCloseKey(SyslistRegKey);
		return ERROR_SUCCESS;

	}

	long WriteRegInfo(char * WriteBackID )
	{

		HKEY SyslistRegKey;
		long Status;

		Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SYSLIST_REG_LOC, NULL, KEY_WRITE, & SyslistRegKey);
		if (Status != ERROR_SUCCESS)
			return Status;

		if (WriteBackID != NULL && WriteBackID[0] != '\0') {
			SimpleStringPWCrypt RegEncrypt(SyslistPhrase);
			char CryptWorkBuf[CFG_STRING_LEN];
			DWORD RegType;
			unsigned long RegLen;
			
			Status = RegEncrypt.EncodePossibleRegCrypt(CryptWorkBuf, WriteBackID, &RegLen, CFG_STRING_LEN, &RegType);
			Status = RegSetValueEx (SyslistRegKey, "ID", NULL, RegType, (BYTE *) CryptWorkBuf, RegLen);
		}
		else {
			Status = RegDeleteValue(SyslistRegKey, "ID");
			// Not Finding the key to delete is A-OK!
			if (Status == ERROR_FILE_NOT_FOUND)
				Status = ERROR_SUCCESS;
		}

		RegCloseKey(SyslistRegKey);

		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		return ERROR_SUCCESS;

	}

	long FindLocalDir(char * ReturnDir)
	{
		char ExecDrive[MAX_PATH];
		char ExecDir[MAX_PATH];
		char WholeExecPath[MAX_PATH];

		// here we make a find file spec representing all
		// language dlls. These should be located in a 
		// folder under the executables directory.
		GetModuleFileName(NULL, WholeExecPath, MAX_PATH);
		_splitpath(WholeExecPath, ExecDrive, ExecDir, NULL, NULL);
		_makepath(ReturnDir, ExecDrive, ExecDir, NULL, NULL);
		ReturnDir[strlen(ReturnDir) - 1] = '\0';

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
		ReadLine(ResponseLine, &ReadLen);
		
		ofstream LogStream;
		char LogDir [2* MAX_PATH + 1];
		char LogWholePath[ 2* MAX_PATH +1];
		FindLocalDir(LogDir);
		_makepath(LogWholePath, NULL, LogDir, "HTTPResponse.txt", NULL);

		LogStream.open(LogWholePath);

#ifdef LOCAL_DEBUG_ECHO
		cout << ResponseLine;
#endif
		LogStream << ResponseLine;

		char WriteBackID[64];
		WriteBackID[0] = '\0';

		long ResponseCode;
		char ProtocolExtract[32] = "";

		sscanf (ResponseLine, "%31[^/]%*s %d", ProtocolExtract, &ResponseCode);

		if (stricmp(ProtocolExtract, "http") 
			|| ResponseCode != 200)
			ReturnStatus = ResponseCode;

		bool IsChunked = false, InBody=false;
		bool Saw0CRLF = false, SawCRLF2 = false;

		char ScanToken[256];
		char *ScanLoc;
	
		while (ReadLen = 2048,
				Status = ReadLine(ResponseLine, &ReadLen),
				Status == ERROR_SUCCESS) {
	
#ifdef LOCAL_DEBUG_ECHO
			cout << ResponseLine;
#endif
			LogStream << ResponseLine;

			//hmmm.. I'm Assuming this is like *nix where
			//A Successful read of zero bytes means the
			//end of the socket....
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

			if (!InBody && !stricmp(ScanToken,"Transfer-Encoding")) {
				sscanf (ResponseLine,"%*[^:]:%s", ScanToken);
				if (!stricmp (ScanToken, "Chunked"))
					IsChunked = true;
			}

			else if (InBody && !stricmp(ScanToken, "HardwareID")) {

				sscanf (ResponseLine,"%*[^=]%*1[=]%*[\" \t]%[^\"]%*1[\"]", WriteBackID);
			}

			else if (InBody && !stricmp(ScanToken, "ErrorNumber")) {

				sscanf (ResponseLine,"%*[^=]%*1[=]%*[\" \t]%d%*1[\"]", &ReturnStatus);
			}

			else if (InBody && !stricmp(ScanToken, "UpdateStatus")) {
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
