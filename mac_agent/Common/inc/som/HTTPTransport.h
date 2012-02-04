#ifndef HTTP_TRANSPORT_H_INCLUDED
#define HTTP_TRANSPORT_H_INCLUDED

#include <sstream>
#include <sys/stat.h>
#include <fcntl.h>
#include <string>
#include <sys/socket.h>
#include <sys/types.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>
#include "FileTransport.h"
#include "SyslistPrefs.h"
#include <SystemConfiguration/SCDynamicStoreCopySpecific.h>
#include <SystemConfiguration/SCSchemaDefinitions.h>

const int INVALID_SOCKET = -1;
const int SOCKET_ERROR = -1;

typedef int SOCKET;
typedef struct hostent HOSTENT;

const int INVALID_HANDLE_VALUE = -1;

typedef bool BOOL;

#define FAILED(X) ((X & 0x80000000) <> 0)

class HTTPTransport:
	public DataTransportProto
{

public:

	HTTPTransport():
		m_Socket(INVALID_SOCKET), 
		m_DefaultPort(80),
		m_Inited(false),		 		
		m_Connected(false), 
		m_ProxyTunneled(false),
		m_ProxyMethod(kSLProxyNone)
	{
		m_ProtoName = "HTTP";
		m_DefaultProxyProto = m_ProtoName;
		
		m_MIMESeparator = "--------XML-Data-Upload-Boundary";

		long RandSepNum = random();
		std::ostringstream RandSepText; 
		
		RandSepText << std::hex << RandSepNum;
		
		m_MIMESeparator.append("-");
		m_MIMESeparator += RandSepText.str();
		m_InstanceUUID = RandSepText.str();

		m_Inited = true;
	}

	virtual ~HTTPTransport()
	{
		if (m_Connected)
			Close();

	}

	virtual const char * Name() 
	{
		return "HTTPTransport";
	}

	virtual const char * HandlePrefix()
	{
		return m_ProtoName.c_str();
	}

protected:
	virtual long ChooseSystemProxy (char * URIHostOnly, long *URIPort)
	{
		return ResolveProxyForProto (
			kSCPropNetProxiesHTTPEnable,
			kSCPropNetProxiesHTTPPort,
			kSCPropNetProxiesHTTPProxy,
			URIHostOnly,
			URIPort);
	}
							
	long ResolveProxyForProto (CFStringRef ProxyEnableKey, CFStringRef ProxyPortKey, CFStringRef ProxyServerKey,
								char * URIHostOnly,  long * URIPort)
	{		
		CFAuto<CFDictionaryRef> ProxySettings;
		Boolean Success;
		
		// Extract the Dictionary
		ProxySettings.Attach(SCDynamicStoreCopyProxies(NULL));
		if (ProxySettings.IsEmpty())
			return ERROR_GEN_FAILURE; // perhaps we should just try without proxy?

		// Extract the enabled setting for the proxy, leave if not enabled.
		CFNumberRef ProxyEnableVal;
		
		ProxyEnableVal = 
			(CFNumberRef) CFDictionaryGetValue(ProxySettings, ProxyEnableKey);
		
		if (ProxyEnableVal == NULL
			|| CFGetTypeID(ProxyEnableVal) != CFNumberGetTypeID())
			
			return ERROR_GEN_FAILURE;
			
		Boolean IsEnabled = false;
		
		Success = CFNumberGetValue(ProxyEnableVal, kCFNumberIntType, &IsEnabled);
		if (Success == false)
			return ERROR_GEN_FAILURE;
		
		if (IsEnabled == false)
			return ERROR_SUCCESS;
			
		// Extract the Server and Port - Finally
		
		// Server extraction
		CFAuto<CFStringRef> ProxyServerVal;
		ProxyServerVal.Attach (
			(CFStringRef) CFDictionaryGetValue(ProxySettings, ProxyServerKey));
			
		if(ProxyServerVal.IsEmpty())
			return ERROR_SUCCESS;
			
		if(CFGetTypeID(ProxyServerVal) != CFStringGetTypeID())
			return ERROR_GEN_FAILURE;
			
		Success = CFStringGetCString(ProxyServerVal,URIHostOnly,1024,kCFStringEncodingUTF8);
		if (Success == false)
			return ERROR_GEN_FAILURE;
		
		m_UsingProxy = true;
		
		// Port Extraction
		*URIPort = m_DefaultPort;
		
		CFAuto<CFNumberRef> ProxyPortVal;
		ProxyPortVal.Attach(
			(CFNumberRef) CFDictionaryGetValue(ProxySettings,ProxyPortKey));
			
		if (ProxyPortVal.IsEmpty())
			return ERROR_SUCCESS;
			
		if (CFGetTypeID(ProxyPortVal) != CFNumberGetTypeID());
			return ERROR_GEN_FAILURE;
			
		Success = CFNumberGetValue(ProxyPortVal,kCFNumberIntType, URIPort);
		if (Success == false)
			return ERROR_GEN_FAILURE;
		
		return ERROR_SUCCESS;
	}

private:
	
	virtual long ResolveProxy( char * URIHostOnly,  long * URIPort ) 
	{
		string ChosenAddr;
		m_UsingProxy = false;
		
		switch (m_ProxyMethod) {
		
		case kSLProxyNone:
			return ERROR_SUCCESS;

		case kSLProxySystem: 
			return ChooseSystemProxy(URIHostOnly, URIPort);
			break;

		case kSLProxyManual: 
			ChosenAddr = m_ProxyServer;	
			break;

		default:
			return ERROR_GEN_FAILURE;
		}

		if (ChosenAddr.length() == 0)
			return ERROR_GEN_FAILURE;

		*URIPort = m_DefaultPort;
		sscanf (ChosenAddr.c_str(), "%[^:]:%ld", URIHostOnly, URIPort);

		m_UsingProxy = true;
		
		return ERROR_SUCCESS;
	}

public:
	virtual long OpenURI( const char * URIString)
	{
		if (m_Connected)
			Close();

		char URIProtoOnly[256]= "";
		char URIHostPage[1024] = "";
		char URIHostOnly[1024]= "";
		char URIDir[1024] = "";
		long URIPort = m_DefaultPort;

		// char URIInetString[32] = "";

		// Cheap parsing of URI String;
		sscanf (URIString, "%[^:]://%[^:]:%ld", URIProtoOnly, URIHostPage, &URIPort);
		sscanf (URIHostPage, "%[^/]/%s", URIHostOnly,URIDir);
		
		if (URIHostOnly[0] == '\0' || strcasecmp(URIProtoOnly,m_ProtoName.c_str()))
			return ERROR_GEN_FAILURE;

		m_FinalDocPort = URIPort;
		m_ConnHost = URIHostOnly;

		// Save port in case the proxy changes it
		long SavedDocPort = URIPort;
		
		// May change host and port to proxy if needed.
		ResolveProxy(URIHostOnly, &URIPort);
				
		// Only direct && Tunnel can use relative document path.
		if (m_UsingProxy == false || m_ProxyTunneled == true) {
			m_FinalServerDoc = URIDir + m_ServerDoc;

			if (m_FinalServerDoc.length() > 0 && m_FinalServerDoc[0] != '/')
				m_FinalServerDoc.insert(0, "/");
		}
		else {
			m_FinalServerDoc = URIProtoOnly;
			m_FinalServerDoc += "://";
			m_FinalServerDoc += URIHostPage;

			if (m_ServerDoc[0] != '/')
				m_FinalServerDoc += "/";

			m_FinalServerDoc += m_ServerDoc;
			
			if (URIPort != m_DefaultPort) {
				ostringstream PortNum;
				m_FinalServerDoc += ":";
				
				PortNum << SavedDocPort;
				
				m_FinalServerDoc += PortNum.str();
			}
		}

		struct sockaddr_in URIAddr;

		// Convert Host Name or dot notation to addr. 
		// First try by name.
		URIAddr.sin_addr.s_addr = inet_addr(URIHostOnly);

		if (URIAddr.sin_addr.s_addr == INADDR_NONE) {

			HOSTENT * HostInfo;
			HostInfo = gethostbyname(URIHostOnly);

			if (HostInfo == NULL || HostInfo->h_length != 4 || HostInfo->h_addrtype != AF_INET)
					return ERROR_GEN_FAILURE;

			URIAddr.sin_addr.s_addr = *(long *)HostInfo->h_addr_list[0];
		}

		URIAddr.sin_family = AF_INET;
		URIAddr.sin_port = htons(URIPort);
		memset((void *) URIAddr.sin_zero, 0, sizeof(URIAddr.sin_zero));

		if (URIAddr.sin_addr.s_addr == INADDR_NONE)
			return ERROR_GEN_FAILURE;

		// OK. Now attempt the create the endpoints via socket
		m_Socket = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
		if (m_Socket == INVALID_SOCKET)
			return ERROR_GEN_FAILURE;
		
		// Set up the new socket according to how we need it.
		// nothing for now, but ioctlsocket is to be used in
		// future.

		// now connect
		long Status;
		Status = connect(m_Socket, (const sockaddr*) & URIAddr, sizeof (URIAddr));
		
		if (Status == SOCKET_ERROR) {
			// long Error = errno;
			return ERROR_GEN_FAILURE;
		}

		return ERROR_SUCCESS;
	};

	virtual long SockWrite(const char * OutBuffer, long Length)
	{
		return send (m_Socket, OutBuffer, Length, 0);
	}

	virtual long WriteString(const char * OutString)
	{
		long Status;

#ifdef LOCAL_DEBUG_ECHO
		cout << OutString;
#endif
		long OutStringLen  = strlen(OutString);

		if (OutStringLen == 0)
			return ERROR_SUCCESS;

		Status = SockWrite(OutString, OutStringLen);

		if (Status == SOCKET_ERROR)
			return ERROR_GEN_FAILURE;

		return ERROR_SUCCESS;
	}

	virtual long SockRead(char * Buffer, long Length)
	{
		return recv (m_Socket, Buffer, Length, 0);
	}

	virtual long ReadLine(char * InBuf, long * MaxString)
	{
		long ReadCount;
		bool SawCR = false;
		bool SawCRLF = false;

		char * CurrLoc = InBuf;
		*CurrLoc = '\0';
		long BufCount = 0;

		while(SawCRLF == false && BufCount < *MaxString) {

			ReadCount = SockRead( CurrLoc, 1);

			if (ReadCount != 1)
				break;

			switch (*CurrLoc) {

			case 0x0D:
				SawCR = true;
					break;

			case 0x0A:
				//if (SawCR)
				SawCRLF = true;
				break;
			
			default:
				SawCR = false;
				break;
			}

			CurrLoc++;
			BufCount++;
		}

		if (BufCount < *MaxString)
			*CurrLoc = '\0';

		*MaxString = BufCount;

		return ERROR_SUCCESS;
	}

	long GenerateTmpFile(NVDataItem * Data, string & RetTmpFileName, long & FileSize) {
		FileTransport FileDest;
		char TmpExportURI[1024];
		long Status;

		char CurrDir[1024];
		CurrDir[0] = '\0';
		
		if (getcwd(CurrDir, 1023) == NULL)
			return ERROR_GEN_FAILURE;
		
		char *TempFileName = tempnam(CurrDir,"SLTransmit");

		if (TempFileName == NULL)
			return ERROR_GEN_FAILURE;

		RetTmpFileName = TempFileName;
		
		if (RetTmpFileName.length() == 0)
			return ERROR_GEN_FAILURE;

		sprintf (TmpExportURI,"File://%s",RetTmpFileName.c_str());

		Status = FileDest.OpenURI(TmpExportURI);
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		Status = FileDest.TransmitData(Data);
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		Status = FileDest.Close();
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		struct stat TempFileStats;

		Status = stat(RetTmpFileName.c_str(), &TempFileStats);
		if (Status != 0)
			return ERROR_GEN_FAILURE;

		FileSize = TempFileStats.st_size;

		return ERROR_SUCCESS;
	}
	
	long SendStaticHeaders() {
		char FmtString[1024];
		long Status;

		sprintf (FmtString, "POST %s HTTP/1.1\r\n", m_FinalServerDoc.c_str());
		Status = WriteString(FmtString);
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;
		
		sprintf (FmtString,"Host: %s\r\n", m_ConnHost.c_str());
		Status =  WriteString(FmtString);
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		sprintf (FmtString,"User-Agent: %s\r\n", m_ClientAgent.c_str());
		Status =  WriteString(FmtString);
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		Status =  WriteString("connection: close\r\n");
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		sprintf (FmtString, "Content-Type: multipart/form-data; boundary=%s\r\n", m_MIMESeparator.c_str());
		Status =  WriteString(FmtString);
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		return ERROR_SUCCESS;
	}

	virtual long ReadResponse()
	{
		char ResponseLine[2048];
		long Status;
		
		long ReadLen;

		while (ReadLen = 2048,
				Status = ReadLine(ResponseLine, &ReadLen),
				Status == ERROR_SUCCESS && ReadLen > 0) {
	
#ifdef LOCAL_DEBUG_ECHO
			cout << ResponseLine;
#endif
		}

#ifdef LOCAL_DEBUG_ECHO
		cout << endl;
#endif

		return ERROR_SUCCESS;
	}

	virtual long SendFileContents(int FileHandle)
	{
		char FileBuf[512];

		unsigned long Remaining;
		// unsigned long RemainingHigh;

		struct stat FileInfo;
		fstat (FileHandle, & FileInfo);
		 
		Remaining = FileInfo.st_size;
		
		ssize_t CurrRead;
		ssize_t ReadRequest;
		
		long WriteStatus;
		
		while (Remaining > 0) {

			if (Remaining >= 512)
				ReadRequest = 512;
			else
				ReadRequest = Remaining;
			
			CurrRead = read(FileHandle, (void *) FileBuf, ReadRequest);

			if (CurrRead == -1)
				return ERROR_GEN_FAILURE;
			
			WriteStatus = SockWrite(FileBuf,CurrRead);
			if (WriteStatus == -1)
				return ERROR_GEN_FAILURE;

			Remaining -= CurrRead;
		}
			
		return ERROR_SUCCESS;
	}

	virtual long SetAdditionalHeaderInfo(string &AdditionalInfoTarget)
	{
		return ERROR_SUCCESS;
	}

	virtual long SendDynamicData(string TempFileName, long TempFileLength)
	{
		char FmtString[1024];
		long Status;

		long TotalLength;

		string MimeStart = "--" + m_MIMESeparator + "\r\n";

		string AdditionalHeaderInfo;

		SetAdditionalHeaderInfo(AdditionalHeaderInfo);

		string ContentHeader = "Content-Disposition: form-data;"
							   "name=\"" + m_DataName +"\"; filename=\"" + m_FileName + "\"\r\n"
							   "Content-Type:text/plain; charset=ISO-8859-2\r\n"
							   "Content-transfer-encoding:binary\r\n\r\n";

		string MimeEnd = "\r\n--" + m_MIMESeparator + "--\r\n";
						   
		TotalLength = AdditionalHeaderInfo.length() 
					+ MimeStart.length() 
					+ ContentHeader.length() 
					+ TempFileLength 
					+ MimeEnd.length();

		sprintf (FmtString, "Content-Length: %ld\r\n\r\n",TotalLength);
		Status =  WriteString(FmtString);
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		Status = WriteString(AdditionalHeaderInfo.c_str());
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		Status = WriteString (MimeStart.c_str());
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		Status = WriteString (ContentHeader.c_str());
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		int TempFileHandle;

		TempFileHandle = open(TempFileName.c_str(), 
							O_RDONLY, 
							S_IRUSR | S_IWUSR | S_IRGRP | S_IWGRP);

		if (TempFileHandle == INVALID_HANDLE_VALUE)
			return ERROR_GEN_FAILURE;

		Status = SendFileContents(TempFileHandle);
		
		// Close file first! then figure out if it was a flop!
		close(TempFileHandle);

		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		Status = WriteString (MimeEnd.c_str());
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		return ERROR_SUCCESS;

	}

	virtual long TransmitData (NVDataItem * Data)
	{
		string TempFileName;
		long TempFileLength;

		long Status;

		Status = GenerateTmpFile(Data, TempFileName, TempFileLength);
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;


		Status = SendStaticHeaders();
		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		Status = SendDynamicData(TempFileName, TempFileLength);

		unlink(TempFileName.c_str());

		if (Status != ERROR_SUCCESS)
			return ERROR_GEN_FAILURE;

		return ReadResponse();
	}

	virtual long Close()
	{
		if (m_Socket != INVALID_SOCKET) {
			close(m_Socket);
			m_ConnHost = "";
		}

		return ERROR_SUCCESS;
	};

	long SetServerDoc(const char * NewServerDoc) 
	{
		m_ServerDoc = NewServerDoc;
		return ERROR_SUCCESS;
	}

	long GetServerDoc(const char ** ReturnServerDoc)
	{
		*ReturnServerDoc = m_ServerDoc.c_str();
		return ERROR_SUCCESS;
	}

	long SetClientAgent(const char * NewClientAgent) 
	{
		m_ClientAgent = NewClientAgent;
		return ERROR_SUCCESS;
	}

	long GetClientAgent(const char ** ReturnClientAgent)
	{
		*ReturnClientAgent = m_ClientAgent.c_str();
		return ERROR_SUCCESS;
	}


	long SetDataName(const char * NewDataName) 
	{
		m_DataName = NewDataName;
		return ERROR_SUCCESS;
	}

	long GetDataName(const char ** ReturnDataName)
	{
		*ReturnDataName = m_DataName.c_str();
		return ERROR_SUCCESS;
	}


	long SetFileName(const char * NewFileName) 
	{
		m_FileName = NewFileName;
		return ERROR_SUCCESS;
	}

	long GetFileName(const char ** ReturnFileName)
	{
		*ReturnFileName = m_FileName.c_str();
		return ERROR_SUCCESS;
	}

	long SetMIMESeparator(const char * NewMIMESeparator) 
	{
		m_MIMESeparator = NewMIMESeparator;
		return ERROR_SUCCESS;
	}

	long GetMIMESeparator(const char ** ReturnMIMESeparator)
	{
		*ReturnMIMESeparator = m_MIMESeparator.c_str();
		return ERROR_SUCCESS;
	}

	long SetProxyInfo (const char * NewProxyServer, enumSLProxyMode NewProxyMethod)
	{
		m_ProxyServer = NewProxyServer;
		m_ProxyMethod = NewProxyMethod;
		return ERROR_SUCCESS;
	}

	long GetProxyInfo (const char ** ReturnProxyServer, enumSLProxyMode * ReturnProxyMethod)
	{
		*ReturnProxyServer = m_ProxyServer.c_str();
		*ReturnProxyMethod = m_ProxyMethod;
		return ERROR_SUCCESS;
	}

protected:
	
	int m_Socket;

	long m_DefaultPort;
	long m_FinalDocPort;

	bool m_Inited;
	bool m_Connected;

	bool m_ProxyTunneled;
	bool m_UsingProxy;
	
	string m_ServerDoc;
	string m_ClientAgent;
	string m_DataName;
	string m_FileName;
	string m_MIMESeparator;
	string m_InstanceUUID;

	string m_FinalServerDoc;

	string m_ConnHost;
	string m_ProtoName;
	string m_ProxyServer;

	string m_DefaultProxyProto;

	enumSLProxyMode m_ProxyMethod;
};
#endif

