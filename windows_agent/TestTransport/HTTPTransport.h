#ifndef HTTP_TRANSPORT_H_INCLUDED
#define HTTP_TRANSPORT_H_INCLUDED

#include <RPC.h>
#include <Winsock2.h>
#include <Mswsock.h>
#include <sys/stat.h>
#include <WinInet.h>
#include "FileTransport.h"
#include "../TestInstConfig/SyslistProxyMethod.h"

class HTTPTransport:
	public DataTransportProto
{

public:

	HTTPTransport():
		m_Socket(INVALID_SOCKET), 
		m_Inited(false),
		m_Connected(false), 
		m_DefaultPort(80), 
		m_ProxyMethod(ProxyMethodDirect),
		m_ProxyTunneled(false)
	{
		m_ProtoName = "HTTP";
		m_DefaultProxyProto = m_ProtoName;

		m_MIMESeparator = "--------XML-Data-Upload-Boundary";

		UUID MIME_UUID;
		RPC_STATUS UUIDStatus;

		UUIDStatus = UuidCreate(&MIME_UUID);

		if (UUIDStatus == RPC_S_OK) {
			char * UUIDString;
			
			UUIDStatus = UuidToString(&MIME_UUID,(unsigned char **) &UUIDString);

			if (UUIDStatus == RPC_S_OK) {
				m_InstanceUUID = UUIDString;
				m_MIMESeparator += "-" + m_InstanceUUID;
			}
		}


		DWORD VersionSupported;
		WSADATA wsaData;
		VersionSupported = MAKEWORD(2,2);
		WSAStartup(VersionSupported, &wsaData);

		if ( LOBYTE( wsaData.wVersion ) != 2 ||
			HIBYTE( wsaData.wVersion ) != 2 ) {
			WSACleanup( );
			return;
		}

		m_Inited = true;
	}

	virtual ~HTTPTransport()
	{
		if (m_Connected)
			Close();

		if (m_Inited)
			WSACleanup();
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
	virtual long ChooseIEProxy()
	{
		return ERROR_SUCCESS;
	}

private:
	
	virtual long ResolveProxy( char * URIHostOnly,  long * URIPort ) 
	{
		m_UsingProxy = false;

		char ChosenAddr[256] = "\0";

		switch (m_ProxyMethod) {
		
		case ProxyMethodDirect:
			return ERROR_SUCCESS;

		case ProxyMethodIE: {

				unsigned long        nSize = 8192;
				char                 szBuf[8192] = { 0 };
				BOOL QueryOK;
				INTERNET_PROXY_INFO* pInfo = (INTERNET_PROXY_INFO*)szBuf;

				QueryOK = InternetQueryOption(NULL, INTERNET_OPTION_PROXY, pInfo, &nSize);
				if(QueryOK == FALSE)
				   return ERROR_GEN_FAILURE;

				if (pInfo->dwAccessType == INTERNET_OPEN_TYPE_DIRECT)
					return ERROR_SUCCESS;

				char DefaultAddr[256] = "\0";

				BOOL SawChosenProto = FALSE;

				char * CurrEntry = strtok((char *) pInfo->lpszProxy, ", \t\n");

				while (CurrEntry != NULL && SawChosenProto == FALSE) {
	
					long EqLoc = strcspn(CurrEntry, "=");
					long EntryLen = strlen(CurrEntry);

					// A default entry: w/o proto assignement, copy in case none other found.
					if (EqLoc == EntryLen)
						strcpy (ChosenAddr, CurrEntry);
					else {

						// search for proto assignment - copy if match found
						if (EqLoc == m_ProtoName.length() 
							&& strnicmp(CurrEntry, m_ProtoName.c_str(), EqLoc) == 0 ) {
							strcpy (ChosenAddr, CurrEntry + EqLoc + 1);
							SawChosenProto = TRUE;
						}
						// See if the default matches, and copy in case none ound
						else if (EqLoc == m_DefaultProxyProto.length()
								&& strnicmp (CurrEntry, m_DefaultProxyProto.c_str(), EqLoc) == 0) {
							strcpy(ChosenAddr, CurrEntry + EqLoc + 1);
						}
					}

					CurrEntry = strtok (NULL, ", \t\n");
				}
			}

			break;

		case ProxyMethodManual: 
			strcpy (ChosenAddr, m_ProxyServer.c_str());	
			break;

		default:
			return ERROR_GEN_FAILURE;

		}

		if (ChosenAddr[0] == '\0')
			return ERROR_GEN_FAILURE;

		*URIPort = m_DefaultPort;
		sscanf (ChosenAddr, "%[^:]:%d", URIHostOnly, URIPort);

		m_UsingProxy = true;

		return ERROR_SUCCESS;
	}

public:
	virtual long OpenURI( char * URIString)
	{
		if (m_Connected)
			Close();

		char URIProtoOnly[256]= "";
		char URIHostPage[1024] = "";
		char URIHostOnly[256]= "";
		char URIDir[1024] = "";
		long URIPort = m_DefaultPort;

		char URIInetString[32] = "";

		// Cheap parsing of URI String;
		sscanf (URIString, "%[^:]://%[^:]:%d", URIProtoOnly, URIHostPage, &URIPort);
		sscanf (URIHostPage, "%[^/]/%s", URIHostOnly,URIDir);

		m_FinalDocPort = URIPort;
		m_ConnHost = URIHostOnly;

		// Save Doc Port in case Proxy Changes it.
		long SavedDocPort = URIPort;

		// May change host and port to proxy if needed.
		ResolveProxy(URIHostOnly, &URIPort);
		
		if (URIHostOnly[0] == '\0' || stricmp(URIProtoOnly,m_ProtoName.c_str()))
			return ERROR_GEN_FAILURE;
		
		// Only direct && Tunnel can use relative document path.
		if (m_ProxyMethod == ProxyMethodDirect || m_ProxyTunneled == true) {
			m_FinalServerDoc = URIDir + m_ServerDoc;

			if (m_FinalServerDoc.length() > 0 && m_FinalServerDoc[0] != '/')
				m_FinalServerDoc.insert(0, '/');
		}
		else {
			m_FinalServerDoc = URIProtoOnly;
			m_FinalServerDoc += "://";
			m_FinalServerDoc += URIHostPage;

			if (m_ServerDoc[0] != '/')
				m_FinalServerDoc += "/";

			m_FinalServerDoc += m_ServerDoc;
			
			if (URIPort != m_DefaultPort) {
				char PortNum[12];
				m_FinalServerDoc += ":";
				m_FinalServerDoc += _itoa(SavedDocPort, PortNum, 10);
			}
		}

		sockaddr_in URIAddr;

		// Convert Host Name or dot notation to addr. 
		// First try by name.
		URIAddr.sin_addr.S_un.S_addr = inet_addr(URIHostOnly);

		if (URIAddr.sin_addr.S_un.S_addr == INADDR_NONE) {

			HOSTENT * HostInfo;
			HostInfo = gethostbyname(URIHostOnly);

			if (HostInfo == NULL || HostInfo->h_length != 4 || HostInfo->h_addrtype != AF_INET)
					return ERROR_GEN_FAILURE;

			URIAddr.sin_addr.S_un.S_addr = *(long *)HostInfo->h_addr_list[0];
		}

		URIAddr.sin_family = AF_INET;
		URIAddr.sin_port = htons(URIPort);
		ZeroMemory((void *) URIAddr.sin_zero, sizeof(URIAddr.sin_zero));

		if (URIAddr.sin_addr.S_un.S_addr == INADDR_NONE)
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
			long Error = WSAGetLastError();
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
		
		if (GetCurrentDirectory(1023, CurrDir) == FALSE)
			return ERROR_GEN_FAILURE;

		RetTmpFileName = CurrDir;
		
		char *TempFileName = tmpnam(NULL);

		if (TempFileName == NULL)
			return ERROR_GEN_FAILURE;

		RetTmpFileName.append(TempFileName);
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

		struct _stat TempFileStats;

		Status = _stat(RetTmpFileName.c_str(), &TempFileStats);
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

	virtual long SendFileContents(HANDLE FileHandle)
	{
		char FileBuf[512];

		unsigned long Remaining;
		unsigned long RemainingHigh;

		Remaining = GetFileSize(FileHandle, &RemainingHigh);
		
		BOOL ReadStatus;
		long WriteStatus;
		
		unsigned long CurrRead;
		long ReadRequest;

		while (Remaining > 0) {

			if (Remaining >= 512)
				ReadRequest = 512;
			else
				ReadRequest = Remaining;
			
			ReadStatus = ReadFile(FileHandle, (LPVOID) FileBuf, ReadRequest, &CurrRead, NULL);

			if (ReadStatus == FALSE)
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

		sprintf (FmtString, "Content-Length: %d\r\n\r\n",TotalLength);
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

		HANDLE TempFileHandle;

		TempFileHandle = CreateFile(TempFileName.c_str(), 
									GENERIC_READ, 
									FILE_SHARE_READ, 
									NULL, 
									OPEN_EXISTING, 
									FILE_ATTRIBUTE_NORMAL, 
									NULL);

		if (TempFileHandle == INVALID_HANDLE_VALUE)
			return ERROR_GEN_FAILURE;

		Status = SendFileContents(TempFileHandle);
		
		// Close file first! then figure out if it was a flop!
		CloseHandle(TempFileHandle);

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
		if (Status != ERROR_SUCCESS) {
			//return -21;
			return ERROR_GEN_FAILURE;
		}


		Status = SendStaticHeaders();
		if (Status != ERROR_SUCCESS) {
			//return -22;
			return ERROR_GEN_FAILURE;
		}

		Status = SendDynamicData(TempFileName, TempFileLength);

		_unlink(TempFileName.c_str());

		if (Status != ERROR_SUCCESS) {
			//return -24;
			return ERROR_GEN_FAILURE;
		}

		return ReadResponse();
	}

	virtual long Close()
	{
		if (m_Socket != INVALID_SOCKET) {
			closesocket(m_Socket);
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

	long SetProxyInfo (const char * NewProxyServer, ProxyMethodIndex NewProxyMethod)
	{
		m_ProxyServer = NewProxyServer;
		m_ProxyMethod = NewProxyMethod;
		return ERROR_SUCCESS;
	}

	long GetProxyInfo (const char ** ReturnProxyServer, ProxyMethodIndex * ReturnProxyMethod)
	{
		*ReturnProxyServer = m_ProxyServer.c_str();
		*ReturnProxyMethod = m_ProxyMethod;
		return ERROR_SUCCESS;
	}

protected:

	SOCKET m_Socket;

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

	ProxyMethodIndex m_ProxyMethod;
};
#endif
