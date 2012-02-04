#include "stdafx.h"


char* BSTRToCString (const BSTR bstrIn)
{
  LPSTR pszOut = NULL;
  if (bstrIn != NULL)
  {
	int nInputStrLen = SysStringLen (bstrIn);

	// Double NULL Termination
	int nOutputStrLen = WideCharToMultiByte(CP_UTF8, 0, bstrIn, nInputStrLen, NULL, 0, 0, 0) + 2; 
	pszOut = new char [nOutputStrLen];

	if (pszOut)
	{
	  memset (pszOut, 0x00, sizeof (char)*nOutputStrLen);
	  WideCharToMultiByte (CP_UTF8, 0, bstrIn, nInputStrLen, pszOut, nOutputStrLen, 0, 0);
	}
  }
  return pszOut;
}

char* WStringToCString (const LPWSTR lpwszStrIn)
{
  LPSTR pszOut = NULL;
  if (lpwszStrIn != NULL)
  {
	int nInputStrLen = wcslen (lpwszStrIn);

	// Double NULL Termination
	int nOutputStrLen = WideCharToMultiByte (CP_UTF8, 0, lpwszStrIn, nInputStrLen, NULL, 0, 0, 0) + 2;
	pszOut = new char [nOutputStrLen];

	if (pszOut)
	{
	  memset (pszOut, 0x00, nOutputStrLen);
	  WideCharToMultiByte(CP_UTF8, 0, lpwszStrIn, nInputStrLen, pszOut, nOutputStrLen, 0, 0);
	}
  }
  return pszOut;
}