// TestNetTask.cpp : Defines the entry point for the console application.
//

#include "stdafx.h"
#include "iostream.h"
#include "sddl.h"
#include "ACLApi.h"

static const WCHAR * INSTALL_TASK_NAME = L"SyslistInstallC";
static const DWORD MAX_RUN_TIME_MS = 600000;
static const DWORD TASK_POLL_INTERVAL_MS = 500;

static const TCHAR * SCHED_SERVICE_APP_NAME = _T("mstask.exe");
static const TCHAR * SCHED_SERVICE_NAME = _T("Schedule");

static const WCHAR * LOCAL_SHARE_EXEC = L"TestCollect.exe";

static TCHAR * REMOTE_NAME = _T("NSLAINST");
static TCHAR * LOCAL_SHARE_PATH = _T("C:\\Documents and Settings\\Karl\\Desktop\\SCADist");
static TCHAR LOCAL_EXEC_PATH[MAX_PATH + 1];

static TCHAR LOCAL_NAME[MAX_COMPUTERNAME_LENGTH + 1];
static TCHAR LOCAL_INSTALL_NAME[ MAX_COMPUTERNAME_LENGTH + 257];

PACL OrigDirDACL = NULL;
PACL OrigFileDACL = NULL;
BOOL OrigDACLValid = FALSE;

// This function take from MS Task Manger demo
long StartTaskSched (WCHAR * RemoteName)
{
	USES_CONVERSION;

	SC_HANDLE   hSC = NULL;
	SC_HANDLE   hSchSvc = NULL;

	hSC = OpenSCManager(W2T(RemoteName), NULL, SC_MANAGER_CONNECT);
	if (hSC == NULL)
	{
		return GetLastError();
	}

	hSchSvc = OpenService(hSC,
						  SCHED_SERVICE_NAME,
						  SERVICE_START | SERVICE_QUERY_STATUS);

	CloseServiceHandle(hSC);
	if (hSchSvc == NULL)
	{
	  return GetLastError();
	}

	SERVICE_STATUS SvcStatus;

	if (QueryServiceStatus(hSchSvc, &SvcStatus) == FALSE)
	{
	  CloseServiceHandle(hSchSvc);
	  return GetLastError();
	}

	if (SvcStatus.dwCurrentState == SERVICE_RUNNING)
	{
	  // The service is already running.
	  CloseServiceHandle(hSchSvc);
	  //printf("Task Scheduler is already running.\n");
	  return 0;
	}

	if (StartService(hSchSvc, 0, NULL) == FALSE)
	{
	  CloseServiceHandle(hSchSvc);
	  //printf("Could not start Task Scheduler.\n");
	  return GetLastError();
	}

	CloseServiceHandle(hSchSvc);
	//printf("Task Scheduler has been started.\n");
	return ERROR_SUCCESS;
}

long InstallTask (WCHAR * RemoteName)
{
	USES_CONVERSION;

	CComPtr<ITaskScheduler> Sched;

	HRESULT hr;
	hr = Sched.CoCreateInstance(CLSID_CTaskScheduler);
	if (FAILED(hr))
		return hr;

	hr = Sched->SetTargetComputer(RemoteName);
	if (FAILED(hr)) 
		return hr;

	hr = Sched->Delete(INSTALL_TASK_NAME);

	CComPtr<ITask> InstallTask;
	hr = Sched->NewWorkItem(INSTALL_TASK_NAME, CLSID_CTask, IID_ITask, (IUnknown **) &InstallTask);
	if (FAILED(hr))
		return hr;

	hr = InstallTask->SetAccountInformation(L"Administrator",L"admin4u");
	if (FAILED(hr))
		return hr;

	hr = InstallTask->SetApplicationName(T2W(LOCAL_INSTALL_NAME));
	if (FAILED(hr))
		return hr;

	hr = InstallTask->SetParameters(L"/S");
	if (FAILED(hr))
		return hr;

	hr = InstallTask->SetMaxRunTime(MAX_RUN_TIME_MS);
	if (FAILED(hr))
		return hr;

	hr = InstallTask->SetMaxRunTime(MAX_RUN_TIME_MS);
	if (FAILED(hr))
		return hr;

	hr = InstallTask->SetFlags(0);
	if (FAILED(hr))
		return hr;

	CComPtr<ITaskTrigger> InstallTrigger;

	WORD InstTriggerNum;

	hr = InstallTask->CreateTrigger(&InstTriggerNum, & InstallTrigger);
	if (FAILED(hr))
		return hr;

	TASK_TRIGGER InstallTriggerData;

	ZeroMemory(&InstallTriggerData, sizeof(TASK_TRIGGER));

	SYSTEMTIME CurrTime;
	GetSystemTime(&CurrTime);

	InstallTriggerData.wBeginDay = 1;
	InstallTriggerData.wBeginMonth = 1;
	InstallTriggerData.wBeginYear = CurrTime.wYear + 10;
	InstallTriggerData.cbTriggerSize = sizeof (TASK_TRIGGER);

	InstallTriggerData.TriggerType = TASK_TIME_TRIGGER_ONCE;

	hr = InstallTrigger->SetTrigger(& InstallTriggerData);
	if (FAILED(hr))
		return hr;

	// Persist!
	CComQIPtr<IPersistFile> TaskPersist = InstallTask;
	if (TaskPersist == NULL)
		return E_FAIL;
	
	hr = TaskPersist->Save(NULL, TRUE);
	if (FAILED(hr))
		return hr;	

	SYSTEMTIME LastRunTime;
	hr = InstallTask->GetMostRecentRunTime(&LastRunTime);
	if (FAILED(hr))
		return hr;

	HRESULT TaskStat;

	InstallTask.Release();

	hr = Sched->Activate(INSTALL_TASK_NAME, IID_ITask,(IUnknown **) & InstallTask);
	if (FAILED(hr))
		return hr;

	hr = InstallTask->GetStatus(&TaskStat);
	if (FAILED(hr))
		return hr;

	hr = InstallTask->Run();
	if (FAILED(hr))
		return hr;


	do {
		Sleep(TASK_POLL_INTERVAL_MS);

		InstallTask.Release();

		hr = Sched->Activate(INSTALL_TASK_NAME, IID_ITask,(IUnknown **) & InstallTask);
		if (FAILED(hr))
			return hr;

		hr = InstallTask->GetStatus(&TaskStat);
		if (FAILED(hr))
			return hr;

	} while (hr == SCHED_S_TASK_HAS_NOT_RUN || TaskStat == SCHED_S_TASK_RUNNING);

	DWORD TaskExit = 0XDEAD;
	hr = InstallTask->GetMostRecentRunTime(&LastRunTime);
	if (hr != S_OK)
		return hr;

	hr = InstallTask->GetExitCode(&TaskExit);
	if (hr != S_OK)
		return hr;

	hr = Sched->Delete(INSTALL_TASK_NAME);

	return ERROR_SUCCESS;
}

long InstallRemote (WCHAR * RemoteName)
{
	if (StartTaskSched(RemoteName) == ERROR_SUCCESS)
		return InstallTask (RemoteName);

	return ERROR_GEN_FAILURE;
}

long UnShareSource()
{
	if (OrigDACLValid) {
		DWORD Status;

		Status = SetNamedSecurityInfo(LOCAL_SHARE_PATH, SE_FILE_OBJECT, DACL_SECURITY_INFORMATION, NULL, NULL, OrigDirDACL, NULL);
		Status = SetNamedSecurityInfo(LOCAL_EXEC_PATH, SE_FILE_OBJECT, DACL_SECURITY_INFORMATION, NULL, NULL, OrigFileDACL, NULL);
		OrigDACLValid = FALSE;
	}

	NetShareDel(NULL, REMOTE_NAME, NULL);
	return ERROR_SUCCESS;
}

void PrintSID( PSID SomeSID)
{
	if (!SomeSID)
		return;

	DWORD Error;
	PSID_IDENTIFIER_AUTHORITY SIDAuth;
	SIDAuth = GetSidIdentifierAuthority(SomeSID);
	Error = GetLastError();

	DWORD SIDSubAuthCount = 0;
	SIDSubAuthCount = (DWORD) GetSidSubAuthorityCount(SomeSID);
	Error = GetLastError();

	DWORD CurrSubAuthIndex;

	PDWORD CurrSubAuth;
	for (CurrSubAuthIndex  = 0; CurrSubAuthIndex < SIDSubAuthCount; CurrSubAuthIndex++) {
		CurrSubAuth = GetSidSubAuthority(SomeSID, CurrSubAuthIndex);
	}
}

void GetShareInfo()
{
	SHARE_INFO_502 * LocalShareInfo;
	BOOL SecStatus;
	NET_API_STATUS NetStatus;

	NetStatus = NetShareGetInfo(
					_T("\\\\DRAGON"),
					//_T("DOWNLOAD"),
					//NULL,
					REMOTE_NAME,
					502,
					(LPBYTE *) &LocalShareInfo);
	


	if (LocalShareInfo != NULL && LocalShareInfo->shi502_security_descriptor != NULL) {

		SID * ShareSID = NULL;
		SECURITY_DESCRIPTOR * ShareSec = (SECURITY_DESCRIPTOR *) LocalShareInfo->shi502_security_descriptor;
		BOOL ShareBool;

		//TCHAR * SIDString[1024];

		SecStatus = GetSecurityDescriptorGroup(ShareSec, (PSID *) &ShareSID, &ShareBool);
		//ConvertSidToStringSid(ShareSID, SIDString);
		PrintSID(ShareSID);
	
		SecStatus = GetSecurityDescriptorOwner(ShareSec, (PSID *) &ShareSID, &ShareBool);
		//ConvertSidToStringSid(ShareSID, SIDString);
		PrintSID(ShareSID);

		PACL ShareDACL = NULL;
		BOOL ShareDACLPresent;
		
		ACL_SIZE_INFORMATION ShareACLSize;
		SecStatus = GetSecurityDescriptorDacl(ShareSec, &ShareDACLPresent, &ShareDACL, &ShareBool);

		if (ShareDACL) {
			SecStatus = GetAclInformation(ShareDACL, &ShareACLSize, sizeof (ShareACLSize), AclSizeInformation);
			
			DWORD ACEIndex = 0;
			void * CurrAce = NULL;

			for (ACEIndex = 0; ACEIndex < ShareACLSize.AceCount; ACEIndex ++) {
				GetAce(ShareDACL, ACEIndex, & CurrAce);
			}

			unsigned long ShareExplicitCount = 0;
			PEXPLICIT_ACCESS ShareExplicitList = NULL;

			GetExplicitEntriesFromAcl( ShareDACL, &ShareExplicitCount, &ShareExplicitList);
			
			PEXPLICIT_ACCESS CurrExpl;
			DWORD ShareExplicitIndex;

			for (ShareExplicitIndex = 0, CurrExpl = ShareExplicitList; 
			ShareExplicitIndex < ShareExplicitCount; 
			ShareExplicitIndex++, CurrExpl ++) {
				cout << CurrExpl->grfAccessMode;
				cout << " ";
				cout << CurrExpl->grfAccessPermissions;
				cout << endl;
			}
		}

	}
}

long ShareSource ()
{

	SHARE_INFO_502 LocalShareInfo;
	//SHARE_INFO_2 LocalShareInfo;

	UnShareSource();
#if 1
	BOOL SecStatus;

	static TCHAR * EveryoneAcct = _T("Everyone");

	DWORD SIDSize = 128;
	PSID EveryoneSID = NULL;
	TCHAR DomainName[DNLEN + 1];
	DWORD DomainLength = DNLEN;
	SID_NAME_USE SIDUse;


	EveryoneSID = HeapAlloc(GetProcessHeap(), 0, SIDSize);
	if (EveryoneSID == NULL)
		return ERROR_GEN_FAILURE;

	SecStatus = LookupAccountName (NULL, EveryoneAcct, EveryoneSID, &SIDSize, 
					DomainName, &DomainLength, &SIDUse);

	if (SecStatus == FALSE) {

		HeapFree(GetProcessHeap(), 0, EveryoneSID);
		EveryoneSID = NULL;

		long LastError = GetLastError();
		
		if (LastError != ERROR_INSUFFICIENT_BUFFER)
			return ERROR_GEN_FAILURE;

		EveryoneSID = HeapAlloc(GetProcessHeap(), 0, SIDSize);
		if (EveryoneSID == NULL)
			return ERROR_GEN_FAILURE;

		SecStatus = LookupAccountName (NULL, EveryoneAcct, &EveryoneSID, &SIDSize, 
					DomainName, &DomainLength, &SIDUse);

		if (SecStatus == FALSE) 
			return ERROR_GEN_FAILURE;
	}

	ACL * pPathDacl = NULL;
	ACL * pExecDacl = NULL;
	DWORD dwAclSize = sizeof(ACL) +  1 * ( sizeof(ACCESS_ALLOWED_ACE) - sizeof(DWORD)) 
				+ GetLengthSid(EveryoneSID);

	pPathDacl = (ACL *) HeapAlloc(GetProcessHeap(), 0, dwAclSize);

    if(pPathDacl == NULL) {
		HeapFree(GetProcessHeap(), 0,EveryoneSID);
		return ERROR_GEN_FAILURE;
	}

	pExecDacl = (ACL *) HeapAlloc(GetProcessHeap(), 0, dwAclSize);

    if(pExecDacl == NULL) {
		HeapFree(GetProcessHeap(), 0,EveryoneSID);
		HeapFree(GetProcessHeap(), 0,pPathDacl);
		return ERROR_GEN_FAILURE;
	}

    InitializeAcl(pPathDacl, dwAclSize, ACL_REVISION);

	//use both GENERIC_READ and ACCESS_ATRIB or else windows won't
	//recognize the share as read only...
    AddAccessAllowedAce(pPathDacl, ACL_REVISION, 
		  GENERIC_READ 
		| GENERIC_WRITE 
		| GENERIC_EXECUTE 
		| GENERIC_ALL 
		| READ_CONTROL 
		//| MAXIMUM_ALLOWED
		| ACCESS_ATRIB
		| DELETE,
		EveryoneSID);
	
   InitializeAcl(pExecDacl, dwAclSize, ACL_REVISION);

	//use both GENERIC_READ and ACCESS_ATRIB or else windows won't
	//recognize the share as read only...
   AddAccessAllowedAce(pExecDacl, ACL_REVISION, 
		  GENERIC_READ 
		| GENERIC_EXECUTE 
		| READ_CONTROL
		| ACCESS_ATRIB
		| DELETE,
		EveryoneSID);
	
	SECURITY_DESCRIPTOR ShareSec;

    SecStatus = InitializeSecurityDescriptor(&ShareSec, SECURITY_DESCRIPTOR_REVISION) ;
    SecStatus = SetSecurityDescriptorDacl(&ShareSec, TRUE, pPathDacl, FALSE);
#endif

	LocalShareInfo.shi502_netname  = REMOTE_NAME;
	LocalShareInfo.shi502_type = STYPE_DISKTREE;
	LocalShareInfo.shi502_permissions = ACCESS_ALL;
	LocalShareInfo.shi502_max_uses = SHI_USES_UNLIMITED;
	LocalShareInfo.shi502_path = LOCAL_SHARE_PATH;
	LocalShareInfo.shi502_reserved = 0;
	LocalShareInfo.shi502_passwd = NULL;
	LocalShareInfo.shi502_remark = NULL;
	LocalShareInfo.shi502_security_descriptor = & ShareSec;
	//LocalShareInfo.shi502_security_descriptor = NULL;

	DWORD ErrorIndex = -1;
	NET_API_STATUS Status;

	Status = NetShareAdd(NULL, 502, (LPBYTE) &LocalShareInfo, &ErrorIndex);

	if (Status != NERR_Success)
		return ERROR_GEN_FAILURE;

	//Status = GetNamedSecurityInfo (LOCAL_SHARE_PATH, SE_FILE_OBJECT, DACL_SECURITY_INFORMATION, NULL, NULL, &OrigDirDACL, NULL, NULL);
	//Status = GetNamedSecurityInfo (LOCAL_EXEC_PATH, SE_FILE_OBJECT, DACL_SECURITY_INFORMATION, NULL, NULL, &OrigFileDACL, NULL, NULL);
	//OrigDACLValid = TRUE;

	Status = SetNamedSecurityInfo(LOCAL_SHARE_PATH, SE_FILE_OBJECT, DACL_SECURITY_INFORMATION, NULL, NULL, pPathDacl, NULL);
	Status = SetNamedSecurityInfo(LOCAL_EXEC_PATH, SE_FILE_OBJECT, DACL_SECURITY_INFORMATION, NULL, NULL, pExecDacl, NULL);

	#if 0
	HeapFree(GetProcessHeap(), 0,EveryoneSID);
	HeapFree(GetProcessHeap(), 0,pDacl);
	HeapFree(GetProcessHeap(), 0,pExecDacl);
	#endif

	return ERROR_SUCCESS;
}

long GetLocalNames()
{
	BOOL NameStatus;
	unsigned long NameLen = MAX_COMPUTERNAME_LENGTH;

	NameStatus = GetComputerName(LOCAL_NAME, &NameLen);
	if (NameStatus == FALSE)
		return ERROR_GEN_FAILURE;
	
	wsprintf(LOCAL_INSTALL_NAME,_T("\\\\%s\\%s\\%s"), LOCAL_NAME, REMOTE_NAME, LOCAL_SHARE_EXEC);
	wsprintf (LOCAL_EXEC_PATH ,_T("%s\\%s"), LOCAL_SHARE_PATH, LOCAL_SHARE_EXEC);

	return ERROR_SUCCESS;
}

int main(int argc, char* argv[])
{
	CoInitialize(NULL);

	cout << "Task Scheduler Test\n";

	GetLocalNames();
	ShareSource();
	//GetShareInfo();
	InstallRemote (L"\\\\Mandark");
	UnShareSource();

	CoUninitialize();

	return 0;
}

