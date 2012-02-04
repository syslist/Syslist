// TestWinEvent.cpp : Defines the entry point for the application.
//

#include "stdafx.h"
//#include "SimpleTimer.h"


int APIENTRY WinMain(HINSTANCE hInstance,
                     HINSTANCE hPrevInstance,
                     LPSTR     lpCmdLine,
                     int       nCmdShow)
{
 	// TODO: Place code here.
	int MsgBoxRtn;

	//MsgBoxRtn = MessageBox(NULL,  "Press Cancel to outright Abort\n","Messaging Test", MB_OKCANCEL);
	//if (MsgBoxRtn == IDCANCEL)
	//	return 0;

	SetTimer(NULL, 12, 10000, NULL);

	MSG msg;
	ZeroMemory( &msg, sizeof(msg) );

	int MsgRtn;

	while( true ) {
		MsgRtn = GetMessage( &msg, NULL, 0U, 0U);
		
		if (MsgRtn == 0)
			return 0;

		else if (MsgRtn < 0) {
			//MsgBoxRtn = MessageBox(NULL,  "Error During Msg Retrieval","Error", MB_OKCANCEL);
			//if (MsgBoxRtn == IDCANCEL)
			//	return 0;
		}
		
		else {
			TranslateMessage( &msg );
			DispatchMessage( &msg );
			//MsgBoxRtn = MessageBox(NULL, "Message Recievied!\nPress Cancel to outright Abort\n","Messaging Test", MB_OKCANCEL);
			//if (MsgBoxRtn == IDCANCEL)
			//	return 0;		
		}
	}

	return 0;
}



