#include <stdafx.h>

#define GROUP_BUF_SIZE 4096

BOOL TokenIsAdmin(HANDLE ExecHandle);

BOOL IsRunningAsAdmin()
{
	BOOL CallOK;
	OSVERSIONINFO WinVer;

	WinVer.dwOSVersionInfoSize = sizeof (OSVERSIONINFO);
	CallOK = GetVersionEx(&WinVer);

	if (CallOK == FALSE)
		return FALSE;

	if (WinVer.dwPlatformId == VER_PLATFORM_WIN32_WINDOWS) {
		return TRUE;
	}

	HANDLE ExecHandle;

	CallOK = OpenThreadToken( GetCurrentThread(), TOKEN_QUERY, TRUE, &ExecHandle);

	if ( CallOK == FALSE) {
		
		CallOK = OpenProcessToken(GetCurrentProcess(), TOKEN_QUERY, &ExecHandle);

		if (CallOK == FALSE)
			return FALSE;

	}

	BOOL RetVal = TokenIsAdmin(ExecHandle);
	
	CloseHandle(ExecHandle);

	return RetVal;
}

BOOL TokenIsAdmin(HANDLE ExecHandle)
{

	BOOL CallOK;

	OSVERSIONINFO WinVer;

	WinVer.dwOSVersionInfoSize = sizeof (OSVERSIONINFO);
	CallOK = GetVersionEx(&WinVer);

	if (CallOK == FALSE)
		return FALSE;

	if (WinVer.dwPlatformId == VER_PLATFORM_WIN32_WINDOWS) {
		return TRUE;
	}
	
	char LocalBuf[GROUP_BUF_SIZE];
	PTOKEN_GROUPS ptgGroups = (PTOKEN_GROUPS) LocalBuf;
	DWORD dwReturnSize;

	CallOK = GetTokenInformation(ExecHandle, TokenGroups, ptgGroups, GROUP_BUF_SIZE, &dwReturnSize);


	if (CallOK == FALSE)
		return FALSE;

	PSID psidAdministrators;
	SID_IDENTIFIER_AUTHORITY sidauthNTAuth = SECURITY_NT_AUTHORITY;

	CallOK = AllocateAndInitializeSid ( 
		&sidauthNTAuth, 
		2,
		SECURITY_BUILTIN_DOMAIN_RID,
		DOMAIN_ALIAS_RID_ADMINS, 
		0, 0, 0, 0, 0, 0,
		&psidAdministrators);

	BOOL FoundAdmin = FALSE;

	for (DWORD GroupIndex = 0; GroupIndex < ptgGroups->GroupCount; GroupIndex ++) {

		if (EqualSid(psidAdministrators, ptgGroups->Groups[GroupIndex].Sid)) {
			FoundAdmin = TRUE;
			break;
		}
	}

	FreeSid(psidAdministrators);

	return FoundAdmin;
}
