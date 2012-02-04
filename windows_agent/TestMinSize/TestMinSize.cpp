#include "stdafx.h"

const long K_TimeWaitSec = 30;

int APIENTRY WinMain(HINSTANCE hInstance,
                     HINSTANCE hPrevInstance,
                     LPSTR     lpCmdLine,
					 int       nCmdShow)
{
	//This costs ~200KB
	//SetTimer(NULL, 12, 10000, NULL);

	int SecCnt;
	for (SecCnt = 0; SecCnt < K_TimeWaitSec; SecCnt ++) {
		Sleep (1000);
	}

	MSG msg;
	ZeroMemory( &msg, sizeof(msg) );
	int MsgBoxRtn;
	int MsgRtn;

	while( true ) {
		MsgRtn = GetMessage( &msg, NULL, 0U, 0U);
		
		if (MsgRtn == 0)
			return 0;

		else if (MsgRtn < 0) {
			MsgBoxRtn = MessageBox(NULL,  "Error During Msg Retrieval","Error", MB_OKCANCEL);
			if (MsgBoxRtn == IDCANCEL)
				return 0;
		}
		
		else {
			TranslateMessage( &msg );
			DispatchMessage( &msg );
			MsgBoxRtn = MessageBox(NULL, "Message Recievied!\nPress Cancel to outright Abort\n","Messaging Test", MB_OKCANCEL);
			if (MsgBoxRtn == IDCANCEL)
				return 0;		
		}
	}

	return 0;

}
