#include "stdafx.h"
#include "RasUser.h"
#include "SyslistAcct.h"

#include <lmaccess.h>
#include <lmerr.h>
#include <ntsecapi.h>
#include <stdio.h>

static const TCHAR * SPEC_ACCT_LOC = "SOFTWARE\\Microsoft\\Windows NT\\CurrentVersion\\Winlogon\\SpecialAccounts\\UserList";

//////////////////////////////////////////////////////////////////////
// Construction/Destruction
//////////////////////////////////////////////////////////////////////

CRasUser::CRasUser()
{
	ULONG size = MAX_COMPUTERNAME_LENGTH + 1;
	GetComputerName(m_computerName, &size);
	m_pComputerName = m_computerName;
}

CRasUser::CRasUser(TCHAR * ComputerName)
{
	lstrcpy (m_computerName, ComputerName);
	m_pComputerName = m_computerName;
}

CRasUser::~CRasUser()
{
}

BOOL CRasUser::AddUserAccount(TCHAR * Account, LPTSTR pComputerName)
{

	USES_CONVERSION;

	BOOL bRet = FALSE;
	USER_INFO_1 ui;
	DWORD dwLevel = 1;
	DWORD dwError = 0;
	NET_API_STATUS nStatus;

	WCHAR wAccount[128];
	wcscpy(wAccount, T2W(Account));

	WCHAR password[128];
	ZeroMemory(password, sizeof(password));

	if (pComputerName != NULL)
		m_pComputerName = pComputerName;
	else
		m_pComputerName = m_computerName;

	MakeRasUserPassword(password, m_pComputerName);

	// DEBUG
	//char TheMessage[1024];
	//sprintf (TheMessage, "The Computer Name to be used is \"\\\\%s\"", m_computerName);
	//MessageBox(NULL, TheMessage, "Name Info", MB_OK);

	ZeroMemory(&ui, sizeof(ui));
	ui.usri1_name = wAccount;
	ui.usri1_password = password;
	ui.usri1_priv = USER_PRIV_USER;
	ui.usri1_home_dir = NULL;
	ui.usri1_comment = NULL;
	ui.usri1_flags = UF_SCRIPT | UF_DONT_EXPIRE_PASSWD | UF_NORMAL_ACCOUNT;
	ui.usri1_script_path = NULL;

	WCHAR tzComputer[80];
	wcscpy(tzComputer,L"\\\\");
	wcscat(tzComputer,T2W(m_pComputerName));

	nStatus = NetUserAdd(NULL, dwLevel, (LPBYTE)&ui, &dwError);
	switch ( nStatus ) {
	case NERR_UserExists:
		{
		//ATLTRACE(L"User %s already on %s\n", wAccount, m_pComputerName);
		bRet = TRUE;
		}
		break;
	case NERR_Success:
		{
		//ATLTRACE(L"User %s has been successfully added on %s\n", wAccount,	m_pComputerName);
		bRet = TRUE;
		}
		break;
	default:
		return nStatus;
		break;
	}


	PSID                  principalSID;
	DWORD dwGetAccount = GetPrincipalSID (Account, &principalSID);

	LOCALGROUP_MEMBERS_INFO_0 AcctGroupMember;

	AcctGroupMember.lgrmi0_sid = principalSID;

	// Some people don't like this, but some query API's 
	// require it.... 
	//nStatus = NetLocalGroupAddMembers(tzComputer,L"Administrators", 0,(PBYTE) &AcctGroupMember, 1); 
	nStatus = NetLocalGroupAddMembers(tzComputer,L"Users", 0,(PBYTE) &AcctGroupMember, 1); 

	// Access this computer from the network
	DWORD dwReturnVal = SetAccountRights (Account,	_T("SeNetworkLogonRight"), TRUE);
	// Back up files and directory
	dwReturnVal = SetAccountRights (Account, _T("SeBackupPrivilege"), TRUE);
	// Change System Time
	dwReturnVal = SetAccountRights (Account, _T("SeSystemtimePrivilege"));
	// Load and unload device drivers
	dwReturnVal = SetAccountRights (Account, _T("SeLoadDriverPrivilege"));
	// Logon at a batch job
	dwReturnVal = SetAccountRights (Account, _T("SeBatchLogonRight"));
	//Log on locally
	dwReturnVal = SetAccountRights (Account, _T("SeInteractiveLogonRight"), TRUE);
	// Logon as service
	dwReturnVal = SetAccountRights (Account, _T("SeServiceLogonRight"), TRUE);
	// Manage auditing and security log
	dwReturnVal = SetAccountRights (Account, SE_SECURITY_NAME);
	// Restore files and directories
	dwReturnVal = SetAccountRights (Account, _T("SeRestorePrivilege"));
	// Take ownership of files or other objects
	dwReturnVal = SetAccountRights (Account, _T("SeTakeOwnershipPrivilege"));

	dwReturnVal = SetAccountRights (Account, SE_TCB_NAME, TRUE);

	dwReturnVal = SetAccountRights (Account, SE_AUDIT_NAME);

	dwReturnVal = SetAccountRights (Account, SE_LOAD_DRIVER_NAME);

	dwReturnVal = SetAccountRights (Account, SE_TAKE_OWNERSHIP_NAME);

	dwReturnVal = SetAccountRights (Account, SE_DEBUG_NAME);

	HKEY AcctKey;

	long Status;

	// Hide the account in XP and Later
	//Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SPEC_ACCT_LOC, NULL, KEY_WRITE, & AcctKey);
	Status = RegCreateKeyEx(HKEY_LOCAL_MACHINE, SPEC_ACCT_LOC, 0, NULL, 0,
				KEY_WRITE | KEY_WOW64_64KEY , NULL, &AcctKey, NULL);
	if (Status == ERROR_SUCCESS) {
		DWORD Value = 0;
		Status = RegSetValueEx (AcctKey, Account, NULL, REG_DWORD, (BYTE *)&Value, sizeof (DWORD));
	}

   return bRet;
}

DWORD CRasUser::SetAccountRights(TCHAR * User, LPTSTR Privilege, BOOL bRemove)
{
	USES_CONVERSION;

	LSA_HANDLE            policyHandle;
	LSA_OBJECT_ATTRIBUTES objectAttributes;
	PSID                  principalSID;
	LSA_UNICODE_STRING    lsaPrivilegeString;
	WCHAR                 widePrivilege [256];
	NTSTATUS ntstat;

	wcscpy (widePrivilege, T2W(Privilege));

	memset (&objectAttributes, 0, sizeof(LSA_OBJECT_ATTRIBUTES));
	if (LsaOpenPolicy (NULL,
					   &objectAttributes,
					   POLICY_CREATE_ACCOUNT | POLICY_LOOKUP_NAMES,
					   &policyHandle) != ERROR_SUCCESS) {

		//ATLTRACE(L"Fail to add account right privilege: %s\n", Privilege);
		return GetLastError();
	}

	DWORD dwGetAccount = GetPrincipalSID (User, &principalSID);

	if (dwGetAccount != ERROR_SUCCESS)
	{
	  //ATLTRACE(L"Fail to add account right privilege: %s\n", Privilege);
	  return dwGetAccount;
	}

	lsaPrivilegeString.Length = (USHORT) (wcslen (widePrivilege) * sizeof (WCHAR));
	lsaPrivilegeString.MaximumLength = (USHORT) (lsaPrivilegeString.Length + sizeof (WCHAR));
	lsaPrivilegeString.Buffer = widePrivilege;

	if (!bRemove) {

		ntstat = LsaAddAccountRights (policyHandle,
			principalSID,
			&lsaPrivilegeString,
			1);
	}
	else {
		ntstat = LsaRemoveAccountRights (policyHandle,
			principalSID,
			FALSE,
			&lsaPrivilegeString,
			1);
	}

	free (principalSID);
	ntstat = LsaClose (policyHandle);

	if (ntstat != ERROR_SUCCESS) {
		//ATLTRACE(L"Fail to add account right privilege: %s\n", Privilege);
		return GetLastError();
	}
	else
		return ERROR_SUCCESS;

}

DWORD CRasUser::GetPrincipalSID(LPTSTR lzAccount, PSID *Sid)
{
    DWORD        sidSize;
    TCHAR        refDomain [256];
    DWORD        refDomainSize;
    DWORD        returnValue;
    SID_NAME_USE snu;

    sidSize = 0;
    refDomainSize = 255;

    LookupAccountName (NULL,
                       lzAccount,
                       *Sid,
                       &sidSize,
                       refDomain,
                       &refDomainSize,
                       &snu);

    returnValue = GetLastError();
    if (returnValue != ERROR_INSUFFICIENT_BUFFER)
        return returnValue;

    *Sid = (PSID) malloc (sidSize);
    refDomainSize = 255;

    if (!LookupAccountName (NULL,
                            lzAccount,
                            *Sid,
                            &sidSize,
                            refDomain,
                            &refDomainSize,
                            &snu))
    {
        return GetLastError();
    }

    return ERROR_SUCCESS;
}

BOOL CRasUser::RemoveUserAccount(TCHAR * Account, LPTSTR pComputerName)
{
	USES_CONVERSION;

	BOOL bRet = FALSE;
	NET_API_STATUS nStatus;

	TCHAR password[15];
	ZeroMemory(password, sizeof(password));

	if (pComputerName != NULL)
		m_pComputerName = pComputerName;
	else
		m_pComputerName = m_computerName;

	WCHAR wcComputer[80];
	wcscpy(wcComputer,L"\\\\");
	wcscat(wcComputer,T2W(m_pComputerName));

	WCHAR wAccount[128];
	wcscpy(wAccount,  T2W(Account));

	nStatus = NetUserDel(NULL,wAccount);

	switch ( nStatus ) {
	case NERR_Success:
		{
		//ATLTRACE(L"User %s has been successfully removed on %s\n", wAccount, m_pComputerName);
		bRet = TRUE;
		}
		break;
	case NERR_UserNotFound:
		{
		//ATLTRACE(L"User %s not found on %s\n", wAccount, m_pComputerName);
		bRet = FALSE;
		}
		break;
	default:
		break;
	}
	return bRet;
}

BOOL CRasUser::UserExists(LPTSTR wUserName)
{
    DWORD        sidSize;
    TCHAR        refDomain [256];
    DWORD        refDomainSize;
    DWORD        returnValue;
    SID_NAME_USE snu;

	 void*   Sid = 0;
	 BOOL   bRet =  TRUE;

    sidSize = 0;
    refDomainSize = 255;

    LookupAccountName (NULL,
                       wUserName,
                       Sid,
                       &sidSize,
                       refDomain,
                       &refDomainSize,
                       &snu);

    returnValue = GetLastError();
    if (returnValue != ERROR_INSUFFICIENT_BUFFER)
        return FALSE;

    Sid = (void*) malloc (sidSize);
    refDomainSize = 255;

    if (!LookupAccountName (NULL,
                            wUserName,
                            Sid,
                            &sidSize,
                            refDomain,
                            &refDomainSize,
                            &snu))
    {
  bRet = FALSE;
 }

 free(Sid);
    return bRet;
}


DWORD CRasUser::SetRunAsPassword(TCHAR* tzAppID, LPTSTR pComputerName)
{
	USES_CONVERSION;

    LSA_OBJECT_ATTRIBUTES objectAttributes;
    HANDLE                policyHandle = NULL;
    LSA_UNICODE_STRING    lsaKeyString;
    LSA_UNICODE_STRING    lsaPasswordString;
    WCHAR                 key [40];
    DWORD                 returnValue;

    wcscpy (key, L"SCM:");
    wcscat (key, T2W(tzAppID));

    lsaKeyString.Length = (USHORT) ((wcslen (key) + 1) * sizeof (TCHAR));
    lsaKeyString.MaximumLength = 40 * sizeof (TCHAR);
    lsaKeyString.Buffer = key;

    WCHAR password[128];
    ZeroMemory(password, sizeof(password));

	if (pComputerName != NULL)
		m_pComputerName = pComputerName;
	else
		m_pComputerName = m_computerName;

	MakeRasUserPassword(password, m_pComputerName);

    lsaPasswordString.Length = (USHORT) ((wcslen (password) + 1) * sizeof (TCHAR));
    lsaPasswordString.Buffer = password;
    lsaPasswordString.MaximumLength = lsaPasswordString.Length;

    //
    // Open the local security policy
    //

    memset (&objectAttributes, 0x00, sizeof (LSA_OBJECT_ATTRIBUTES));
    objectAttributes.Length = sizeof (LSA_OBJECT_ATTRIBUTES);

    returnValue = LsaOpenPolicy (NULL,
                                 &objectAttributes,
                                 POLICY_CREATE_SECRET,
                                 &policyHandle);

    if (returnValue != ERROR_SUCCESS)
        return returnValue;

    //
    // Store the user's password
    //

    returnValue = LsaStorePrivateData (policyHandle,
                                       &lsaKeyString,
                                       &lsaPasswordString);

    LsaClose (policyHandle);

    if (returnValue != ERROR_SUCCESS)
    {
        return returnValue;
    }

    return ERROR_SUCCESS;
}

void CRasUser::MakeRasUserPassword(LPWSTR password, LPTSTR pComputerName)
{
	USES_CONVERSION;

	wcscpy(password, T2W(SYSLIST_ACCT_PWD));
}
