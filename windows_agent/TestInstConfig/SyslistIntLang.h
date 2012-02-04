#ifndef SYSLIST_INT_LANG_H_INCLUDED
#define SYSLIST_INT_LANG_H_INCLUDED

#include <map>
#include <string>

using namespace std;

typedef struct LangInfo {
	LCID LangID;
	string ShortName;
	string LongName;
	HINSTANCE Instance;

	LangInfo():LangID(-1),Instance(0) {};

	LangInfo & operator=(LangInfo& OtherInfo){
		LangID = OtherInfo.LangID;
		ShortName = OtherInfo.ShortName;
		LongName = OtherInfo.LongName;
		Instance = OtherInfo.Instance;

		return *(this);
	}

} LangInfo_t;

typedef struct LangItem {
	long CtrlID;
	long WinMsgID;
	long MsgRsrcID;
} LangItem_t;

typedef map<LCID, LangInfo> LangIDInfoMap;
typedef map<string, LangInfo> LangNameInfoMap;

extern LangIDInfoMap g_LangIDInfo;
extern LangNameInfoMap g_SLangNameInfo;
extern LangNameInfoMap g_LLangNameInfo;

const long LANG_END = -1;
const long RES_STRING_SIZE = 1024;

const long LC_LANG_LEN = 256;

extern HINSTANCE g_hLangInstance;

extern long InitLangDLLInfo(HINSTANCE LocalInstance, int SResID, int LResID, int IResID, const char * FileSpec);
extern long InitDlgLangStrings (HWND Dlg, LangItem * DlgStringTable, HINSTANCE StringSource);
extern long InitDlgLangCombo(HWND Dlg, int ComboID, long CurrLangID);
extern long SetLangInstance(char * LangName, LangNameInfoMap & SearchMap, long & DestID);
extern long SetDefaultLangInstance(long & DestID);
#endif