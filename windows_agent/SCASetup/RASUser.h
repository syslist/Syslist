#ifndef RAS_USER_H_INCLUDED
#define RAS_USER_H_INCLUDED

class CRasUser 
{
public:
	 CRasUser();
	 CRasUser(TCHAR * ComputerName);

	 virtual ~CRasUser();

	 BOOL AddUserAccount(TCHAR * wAccount, LPTSTR pComputerName = NULL);

	 BOOL RemoveUserAccount(TCHAR * wAccount, LPTSTR pComputerName = NULL);

	 DWORD SetAccountRights(TCHAR * User, LPTSTR Privilege, BOOL bRemove = FALSE);

	 BOOL UserExists(LPTSTR wUserName);

	 virtual void MakeRasUserPassword(LPWSTR password, LPTSTR pComputerName = NULL);

	 DWORD SetRunAsPassword(TCHAR* tzAppID, LPTSTR pComputerName = NULL);

protected:
	 DWORD GetPrincipalSID(LPTSTR lzAccount, PSID *Sid);

private:
	 TCHAR m_defaultUser[40];
	 TCHAR m_computerName[MAX_COMPUTERNAME_LENGTH + 1];
	 TCHAR* m_pComputerName;

};

#endif
