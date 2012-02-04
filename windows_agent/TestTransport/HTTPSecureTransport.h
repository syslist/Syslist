#ifndef HTTP_SECURE_TRANSPORT_H_INCLUDED
#define HTTP_SECURE_TRANSPORT_H_INCLUDED

#include <openssl/crypto.h>
#include <openssl/x509.h>
#include <openssl/pem.h>
#include <openssl/ssl.h>
#include <openssl/err.h>

#include "HTTPTransport.h"


class HTTPSecureTransport:
	public HTTPTransport
{

public:

	HTTPSecureTransport():
		m_SSL(NULL),
		m_SSL_CTX(NULL),
		m_ServerCert(NULL),
		m_SSL_METH(NULL),
		m_SSL_Connected(false)
	{
		m_DefaultPort = 443;
		m_ProxyTunneled = true;
		m_ProtoName = "HTTPS";
	}

	virtual ~HTTPSecureTransport()
	{

		if (m_ServerCert != NULL)
			X509_free (m_ServerCert);

		if (m_SSL != NULL)
			SSL_free (m_SSL);

		if (m_SSL_CTX != NULL)
			SSL_CTX_free (m_SSL_CTX);

		//if (m_SSL_METH != NULL)
			//SSL_method_free(m_SSL_METH);

	}

	virtual long SockRead(char * Buffer, long Length)
	{

		if (m_SSL_Connected == false)
			return HTTPTransport::SockRead(Buffer, Length);

		long Status;

		Status = SSL_read(m_SSL, Buffer, Length);
		if (Status < 0) {
			long SSL_Error = SSL_get_error(m_SSL, Status);
			return SOCKET_ERROR;
		}

		return Status;
	}

	virtual long SockWrite(const char *OutBuffer, long Length)
	{
		if (m_SSL_Connected == false)
			return HTTPTransport::SockWrite(OutBuffer, Length);

		long WriteRet;
		WriteRet = SSL_write(m_SSL, OutBuffer, Length);
		if(WriteRet < 0) {
			long SSL_ERROR = SSL_get_error(m_SSL,WriteRet);
			return ERROR_GEN_FAILURE;
		}

		return ERROR_SUCCESS;
	}

	virtual const char * Name() 
	{
		return "HTTPSecureTransport";
	}

	// This *MUST* be called *BEFORE* SSL_Connect 
	// So as to be sent to Proxy unencrtyped!
	long SendProxyHeader() 
	{
		long Status;

		Status = WriteString("CONNECT ");
		Status = WriteString(m_ConnHost.c_str());

		// Always send for clarity for us, the proxy 
		// and the final destination server.
		//if (m_FinalDocPort != m_DefaultPort) {
			char PortString[12];
			Status = WriteString(":");
			Status = WriteString(_itoa(m_FinalDocPort, PortString, 10));
		//}

		Status = WriteString(" HTTP/1.1\r\n");
		Status = WriteString("User-agent: ");
		Status = WriteString(m_ClientAgent.c_str());
		Status = WriteString("\r\n");
		Status = WriteString("\r\n");

		char ServerResponse[2048];
		long ReadLen = 2048;

		Status = ReadLine(ServerResponse, &ReadLen);
		if (Status != ERROR_SUCCESS)
			return Status;

		long ServerCode = 0;
		sscanf(ServerResponse, "%*s %d", &ServerCode);
		if (ServerCode != 200)
			return ERROR_GEN_FAILURE;

		while (ReadLen = 2048,
			   Status = ReadLine(ServerResponse, &ReadLen),
			   Status == ERROR_SUCCESS && ReadLen > 0) {

			// Just search for the empty line....
			if ( strcmp (ServerResponse, "\r\n") == 0
				|| strcmp (ServerResponse, "\n") == 0) {
				
				break;
			}
				
		}
		

		return Status;
	}

	long OpenURI(char * URIString)
	{
		long Status;
		Status = HTTPTransport::OpenURI(URIString);

		if (Status != ERROR_SUCCESS)
			return Status;

		SSLeay_add_ssl_algorithms();

		m_SSL_METH = SSLv23_client_method();
		if (m_SSL_METH == NULL)
			return ERROR_GEN_FAILURE;

		SSL_load_error_strings(); 

		m_SSL_CTX = SSL_CTX_new (m_SSL_METH);
		if (m_SSL_CTX == NULL)
			return ERROR_GEN_FAILURE;

		m_SSL = SSL_new(m_SSL_CTX);
		if (m_SSL == NULL)
			return ERROR_GEN_FAILURE;	

		// Proxy requires connect method before transmission.
		if (m_UsingProxy) {
			Status = SendProxyHeader();
			if (Status !=  ERROR_SUCCESS)
				return Status;
		}

		SSL_set_fd (m_SSL, m_Socket);

		int err;

		err = SSL_connect (m_SSL);
		if (err == -1)
			return ERROR_GEN_FAILURE;

		m_SSL_Connected = true;

		m_ServerCert = SSL_get_peer_certificate (m_SSL);
		if (m_ServerCert == NULL)
			return NULL;

		return ERROR_SUCCESS;
	}

	virtual long Close()
	{
		if (m_SSL)
			SSL_shutdown (m_SSL);

		m_SSL_Connected = false;

		return HTTPTransport::Close();
	}

private:
	SSL*  m_SSL;
	SSL_CTX * m_SSL_CTX;
	SSL_METHOD *m_SSL_METH;
	X509 * m_ServerCert;
	bool m_SSL_Connected;

};
#endif
