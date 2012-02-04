;NSIS Modern User Interface version 1.63
;Syslist Agent Install Prototype Installer
;Based on ModernUI NullSoft Example

!include "InstallVersion.nsi"

!ifndef SYSL_OUTPUT_FILE
    !define SYSL_OUTPUT_FILE "SCA_Remote.exe"
!endif

!ifndef MUI_PRODUCT
    !define MUI_PRODUCT "Syslist Companion Agent Net Install"
!endif

!define SYSL_BASE_NAME "Syslist Companion Agent Network Installer" ;Define your own software name here
;# !define MUI_VERSION "1.1.3"
!define SYSL_REGPATH "Software\${SYSL_BASE_NAME}"
!define SYSL_REGPATH_CONFIG "${SYSL_REGPATH}\Config"
!define SYSL_UNINST_ID "${MUI_PRODUCT} v${MUI_VERSION}"
!define SYSL_WIN_UNINST "Software\Microsoft\Windows\CurrentVersion\Uninstall\${SYSL_BASE_NAME}"


;Controls the background gradien (DUH!)
;Goes from Pure white (FFFFFF) to Light Grey (BBBBBB)
;Red Text (C00000)
BGGradient FFFFFF BBBBBB C00000

!include "MUI.nsh"

;--------------------------------
;Configuration

  ;General
  OutFile "${SYSL_OUTPUT_FILE}"

  ;Folder selection page
  InstallDir "$PROGRAMFILES\${SYSL_BASE_NAME}"
  
  ;Remember install folder
  InstallDirRegKey HKLM SYSL_REGPATH ""
  
  ;Remember the installer language
  !define MUI_LANGDLL_REGISTRY_ROOT "HKLM" 
  !define MUI_LANGDLL_REGISTRY_KEY "${SYSL_REGPATH_CONFIG}" 
  !define MUI_LANGDLL_REGISTRY_VALUENAME "Language"
  
  ;The window title of the language selection dialog.
  !define MUI_TEXT_LANGDLL_WINDOWTITLE "${SYSL_UNINST_ID} Installer Language"

  ;The text to display on the language selection dialog.
  !define MUI_TEXT_LANGDLL_INFO "Please select the language for ${SYSL_UNINST_ID}." 

;--------------------------------
;Modern UI Configuration

  !define MUI_WELCOMPAGE
  !define MUI_LICENSEPAGE
;  !define MUI_COMPONENTSPAGE
  !define MUI_DIRECTORYPAGE
  
  !define MUI_ABORTWARNING
  !define MUI_FINISHPAGE
  !define MUI_FINISHPAGE_RUN "$INSTDIR\SCANet.exe"
  !define MUI_UNINSTALLER
  !define MUI_UNCONFIRMPAGE
 
  !define MUI_HEADERBITMAP "..\Images\syslist-logo-inst.bmp"
  
;--------------------------------
;Languages and Branding
 
  !define MUI_BRANDINGTEXT "Literal Technology - Syslist Companion Agent"
  !insertmacro MUI_LANGUAGE "English"
  
;;  !define MUI_BRANDINGTEXT "Literal Technology - Syslist Companion Agent(ESP)"
;;  !insertmacro MUI_LANGUAGE "Spanish"
  
  !define MUI_BRANDINGTEXT "Literal Technology - Syslist Companion Agent(NLD)"
  !insertmacro MUI_LANGUAGE "Dutch"
  
;;  !define MUI_BRANDINGTEXT "Literal Technology - Syslist Companion Agent(FRA)"
;;  !insertmacro MUI_LANGUAGE "French"

  !define MUI_BRANDINGTEXT "Literal Technology - Syslist Agent(DE)"
  !insertmacro MUI_LANGUAGE "German"
;--------------------------------
;Language Strings

  ;Description
  LangString DESC_SecCopyUI ${LANG_ENGLISH} "Install the Syslist Agent Network Installer in the Installation Folder"
;  LangString DESC_SecCopyUI ${LANG_SPANISH} "{SPANISH} Install the Syslist Agent Network Installer in the Installation Folder"
  LangString DESC_SecCopyUI ${LANG_DUTCH} "Installeer de Syslist Agent Network Installer in de Installatiefolder"
 ; LangString DESC_SecCopyUI ${LANG_FRENCH} "{FRENCH} Install the Syslist Agent Network Installer in the Installation Folder"
  LangString DESC_SecCopyUI ${LANG_GERMAN} "Install the Syslist Agent Network Installer in the Installation Folder"

;--------------------------------
;Data
  
  LicenseData /LANG=${LANG_ENGLISH} "SyslistAgentLicense.txt"
 ; LicenseData /LANG=${LANG_SPANISH} "SyslistAgentLicense_Spanish.txt"
  LicenseData /LANG=${LANG_DUTCH} "SyslistAgentLicense_Dutch.txt"
 ; LicenseData /LANG=${LANG_FRENCH} "SyslistAgentLicense_French.txt"
  LicenseData /LANG=${LANG_GERMAN} "SyslistAgentLicense_German.txt"  

;--------------------------------
;Installer Sections

Section "NetInstaller" SecCopyUI

  ;ADD YOUR OWN STUFF HERE!

  SetOutPath "$INSTDIR"
  !ifdef SYSL_DEMO_INSTALL
 
   !ifdef SYSL_ASP_DEMO_INSTALL
      File "..\TestNetEnum\ReleaseASPDemo\SCANet.exe"
      File "SCA_ASP_Demo.exe"
    !else
      File "..\TestNetEnum\ReleaseDemo\SCANet.exe"
      File "SCA_Demo.exe"
    !endif

  !else
  
    !ifdef SYSL_ACC
      File "..\TestNetEnum\ReleaseACC\SCANet.exe"
      File "SCA_ASP.exe"
    !else
      File "..\TestNetEnum\Release\SCANet.exe"
      File "SCA_Install.exe"
    !endif
     
  !endif
  
  File "..\SCARISvc\Release\SCARISvc.exe"
  File "..\SCANetUnInstall\Release\SCAUn.exe"

  
  SetOutPath "$INSTDIR\LangDLL"
  File /nonfatal "..\TestNetEnum\LangDLL\NetLang_*.dll"
  
  ;Store install folder
  WriteRegStr HKLM "${SYSL_REGPATH}" "" $INSTDIR

  ;Windows Uninstall information  
  DeleteRegKey  HKLM "${SYSL_WIN_UNINST}"
  WriteRegStr HKLM "${SYSL_WIN_UNINST}" "DisplayName" "${SYSL_UNINST_ID}"
  WriteRegStr HKLM "${SYSL_WIN_UNINST}" "DisplayVersion" "${MUI_VERSION}"
  WriteRegStr HKLM "${SYSL_WIN_UNINST}" "Publisher" "Literal Technology"
  WriteRegStr HKLM "${SYSL_WIN_UNINST}" "UninstallString" "$INSTDIR\Uninstall.exe"
  WriteRegStr HKLM "${SYSL_WIN_UNINST}" "URLInfoAbout" "www.Syslist.com"
  WriteRegStr HKLM "${SYSL_WIN_UNINST}" "URLUpdateInfo" "www.Syslist.com"
  
  ;Create uninstaller
  WriteUninstaller "$INSTDIR\Uninstall.exe"

;;  StrCmp $0 0 NoSilentConfig
;; 
;;  ExecWait '"$INSTDIR\SCAConf.exe" /install $CMDLINE'
;;  ifErrors ConfigFailed
;;  Return
;;  
;;ConfigFailed:
;;  Quit
;;  
;;NoSilentConfig:
SectionEnd

;Display the Finish header
;Insert this macro after the sections if you are not using a finish page
!insertmacro MUI_SECTIONS_FINISHHEADER

;--------------------------------
;Descriptions

!insertmacro MUI_FUNCTIONS_DESCRIPTION_BEGIN
  !insertmacro MUI_DESCRIPTION_TEXT ${SecCopyUI} $(DESC_SecCopyUI)
!insertmacro MUI_FUNCTIONS_DESCRIPTION_END
 
;--------------------------------
;Uninstaller Section

Section "Uninstall"

  Delete "$INSTDIR\modern.exe"
  Delete "$INSTDIR\Uninstall.exe"

  RMDir /r "$INSTDIR"

  DeleteRegKey  HKLM "${SYSL_REGPATH}"
  DeleteRegKey  HKLM "${SYSL_WIN_UNINST}"
  DeleteRegKey  "${MUI_LANGDLL_REGISTRY_ROOT}" "${MUI_LANGDLL_REGISTRY_KEY}"
  
  ;Display the Finish header
  !insertmacro MUI_UNFINISHHEADER

SectionEnd

;----------------------------------
; CallBacks
Function .onInit
  Call IsSilent
  Pop $0
  StrCmp $0 1 NoLangDisplay
  !ifndef MUI_LANG_DLL_ALWAYSSHOW
  	!define MUI_LANGDLL_ALWAYSSHOW
  !endif
  !insertmacro MUI_LANGDLL_DISPLAY
NoLangDisplay:

FunctionEnd

;; Run Syslist Configurator Executable
;;Function .onInstSuccess
;;  strcmp $0 1 NoExitConfig
;;  ClearErrors
;;  ExecWait '"$INSTDIR\SCAConf.exe" /install $CMDLINE'
;;NoExitConfig:
;;FunctionEnd

Function .onInstFailed
  StrCmp $0 1 NoFailDisplay
    MessageBox MB_OK "The ${MUI_PRODUCT} has failed to install correctly. Please contact support and try again"
NoFailDisplay:
FunctionEnd

Function un.onInit
 ;; Call IsSilent
 ;; Pop $0
  IfFileExists $INSTDIR\SCANet.exe found
;;    strcmp $0 1 NoPathMB
    Messagebox MB_OK "Uninstall path for the ${MUI_PRODUCT} is incorrect. The product cannot be uninstalled"
NoPathMB:
  Abort
found:
FunctionEnd

;----------------------------------
; Useful Stuff
Function IsSilent
  Push $0
  Push $CMDLINE
  Push "/S"
  Call StrStr
  Pop $0
  StrCpy $0 $0 3
  StrCmp $0 "/S" silent
  StrCmp $0 "/S " silent
    StrCpy $0 0
    Goto notsilent
  silent: StrCpy $0 1
  notsilent: Exch $0
FunctionEnd

Function StrStr
  Exch $R1 ; st=haystack,old$R1, $R1=needle
  Exch    ; st=old$R1,haystack
  Exch $R2 ; st=old$R1,old$R2, $R2=haystack
  Push $R3
  Push $R4
  Push $R5
  StrLen $R3 $R1
  StrCpy $R4 0
  ; $R1=needle
  ; $R2=haystack
  ; $R3=len(needle)
  ; $R4=cnt
  ; $R5=tmp
  loop:
    StrCpy $R5 $R2 $R3 $R4
    StrCmp $R5 $R1 done
    StrCmp $R5 "" done
    IntOp $R4 $R4 + 1
    Goto loop
  done:
  StrCpy $R1 $R2 "" $R4
  Pop $R5
  Pop $R4
  Pop $R3
  Pop $R2
  Exch $R1
FunctionEnd