#ifndef SIMPLE_CRYPT_INCLUDED
#define SIMPLE_CRYPT_INCLUDED

#include <wincrypt.h>
#include <string>

using namespace std;

extern const char * SyslistPhrase;

class SimpleStringPWCrypt 
{
public:
	SimpleStringPWCrypt(const char * Phrase, DWORD ProvType = PROV_RSA_FULL, ALG_ID Alg = CALG_RC4):
		m_Phrase(Phrase), 
		m_Key(NULL),
		m_Hash(NULL),
		m_Alg(Alg),
		m_ProvType (ProvType),
		m_Inited(FALSE)
	{
		BOOL CallSuccess;

		CallSuccess = CryptAcquireContext(&m_Prov, NULL, NULL, m_ProvType, CRYPT_VERIFYCONTEXT );
		if (!CallSuccess) {
			//MessageBox (NULL, "CryptInitFail 0", "Crypt", MB_OK);
			return;
		}

		CallSuccess = CryptCreateHash (m_Prov, CALG_SHA, NULL, NULL, &m_Hash);
		if (!CallSuccess) {
			//MessageBox (NULL, "CryptInitFail 1", "Crypt", MB_OK);
			return;
		}

		CallSuccess = CryptHashData (m_Hash, (BYTE *) m_Phrase.data(), m_Phrase.length(), NULL);
		if (!CallSuccess) {
			//MessageBox (NULL, "CryptInitFail 2", "Crypt", MB_OK);
			return;
		}

		CallSuccess = CryptDeriveKey (m_Prov, m_Alg, m_Hash, NULL, &m_Key); 
		if (!CallSuccess) {
			//MessageBox (NULL, "CryptInitFail 3", "Crypt", MB_OK);
			return;
		}

		m_Inited = TRUE;

	};

	virtual ~SimpleStringPWCrypt() 
	{

		if (m_Prov != NULL)
			CryptReleaseContext (m_Prov, NULL);

		if (m_Hash != NULL)
			CryptDestroyHash(m_Hash);

		if (m_Key != NULL)
			CryptDestroyKey(m_Key);

	};

	BOOL IsInited() 
	{
		return m_Inited;
	};

	BOOL Encrypt( BYTE * Data, unsigned long * DataLen, unsigned long MaxBufLen)
	{
		return CryptEncrypt (m_Key, NULL, TRUE, NULL, (BYTE *) Data, DataLen, MaxBufLen);
	}

	BOOL Decrypt ( BYTE * Data, unsigned long *DataLen)
	{
		return CryptDecrypt (m_Key, NULL, TRUE, NULL, Data, DataLen);
	}

	long  DecodePossibleRegCrypt(char * Dest, char * Source, unsigned long SourceLen, DWORD RegType)
	{
		unsigned long DecryptLen = SourceLen;
		memcpy(Dest, Source, SourceLen);

		long Status = ERROR_GEN_FAILURE;

		switch (RegType) {

		case REG_BINARY:

			if (Decrypt ( (PBYTE) Dest, &DecryptLen)) {
				Dest[DecryptLen] = '\0';
				Status = ERROR_SUCCESS;
			}
			break;

		case REG_SZ:

			Status = ERROR_SUCCESS;
			break;

		default:

			Status = ERROR_GEN_FAILURE;
			break;

		}

		if (Status != ERROR_SUCCESS)
			Dest[0] = '\0';

		return Status;
	};

	long EncodePossibleRegCrypt(char * Dest, char * Source, unsigned long *ReturnLen, unsigned long MaxIOLen, DWORD *ReturnRegType)
	{

		unsigned long OrigLen = strlen(Source);
		memcpy(Dest, Source, OrigLen);
		Dest[OrigLen] = '\0';

		unsigned long WorkLen = OrigLen;

		//if (!IsInited())
		//	MessageBox(NULL, "CRYPT FAIL", "FAIL", MB_OK);

		if (!IsInited() || !Encrypt((PBYTE) Dest, &WorkLen, MaxIOLen)) {
			*ReturnLen = OrigLen;
			*ReturnRegType = REG_SZ;
			return ERROR_GEN_FAILURE;
		}

		*ReturnRegType = REG_BINARY;
		*ReturnLen = WorkLen;

		return ERROR_SUCCESS;
	};

private:
	BOOL m_Inited;
	HCRYPTHASH m_Hash;
	HCRYPTKEY m_Key;
	HCRYPTPROV m_Prov;
	ALG_ID m_Alg;
	string m_Phrase;
	DWORD m_ProvType;
};


#endif