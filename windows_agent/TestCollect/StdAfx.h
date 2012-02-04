// stdafx.h : include file for standard system include files,
//  or project specific include files that are used frequently, but
//      are changed infrequently
//

#if !defined(AFX_STDAFX_H__67C5D266_050C_471F_A255_04C7C490F6A7__INCLUDED_)
#define AFX_STDAFX_H__67C5D266_050C_471F_A255_04C7C490F6A7__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

#define WIN32_LEAN_AND_MEAN		// Exclude rarely-used stuff from Windows headers

#define _X86_
//#define WINVER 0x0400

#include <excpt.h>
#include <stdarg.h>
#include <windef.h>
#include <winbase.h>
#include <winreg.h>
#include <wingdi.h>
#include <winuser.h>

#include <setupapi.h>
#include <devguid.h>
#include <regstr.h>

#define _WIN32_DCOM

#include <atlbase.h>

#include "Wbemidl.h"
#include "Wbemcli.h"

#include "assert.h"

const long NUM_BUF_SIZE = 32;
#define W32_RETURN_ON_ERROR(ERRVAR) if (ERRVAR != ERROR_SUCCESS) return -1;

#pragma warning(disable: 4786) // identifier was truncated in the debug information
// TODO: reference additional headers your program requires here

//{{AFX_INSERT_LOCATION}}
// Microsoft Visual C++ will insert additional declarations immediately before the previous line.

#endif // !defined(AFX_STDAFX_H__67C5D266_050C_471F_A255_04C7C490F6A7__INCLUDED_)
