// TestWinCrypt.cpp : Defines the entry point for the console application.
//

#include "stdafx.h"
#include "SimpleCrypt.h"

const char * Phrase = "But sharp knives will suffice";
const char * TestData = "And it was to his demise that I let him believe his own retort.";
const unsigned long MaxDataLen = 1024;

int main(int argc, char* argv[])
{
	char DataBuf[MaxDataLen] = "";

	strcpy (DataBuf,TestData);

	unsigned long DataLen = strlen(TestData);

	DWORD LastError;

	BOOL Status;

	{
		SimpleStringPWCrypt EnCrypt(Phrase);

		Status = EnCrypt.Encrypt((PBYTE) DataBuf, &DataLen, MaxDataLen);
		
		if (!Status) {
			LastError = GetLastError();
			return 1;
		}
	}


	{

		SimpleStringPWCrypt Decrypt(Phrase);
		Status = Decrypt.Decrypt( (PBYTE) DataBuf, &DataLen);
		DataBuf[DataLen] = '\0';
		if (!Status) {
			LastError = GetLastError();
			return 2;
		}
	}
	
	cout << DataBuf;
	cout << endl;

	cin >> LastError;

	return 0;
}

