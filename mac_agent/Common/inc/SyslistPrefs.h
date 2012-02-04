#ifndef SYSLIST_PREFS_H_INCLUDED
#define SYSLIST_PREFS_H_INCLUDED

#include <string>
#include "macutil/CFUtil.h"
#include "openssl/evp.h"

const CFStringRef kSLAppID = CFSTR("com.literal.syslist.agent");

const CFStringRef kSLKeyServerURL = CFSTR("ServerAddress");
const CFStringRef kSLDefServerURL = CFSTR("HTTPS://www.Syslist.com");

const CFStringRef kSLKeyServerPort = CFSTR("ServerPort");
const long        kSLDefServerPort = -1;

typedef enum {
	kSLProxyNone = 0,
	kSLProxySystem,
	kSLProxyManual
} enumSLProxyMode;

const CFStringRef     kSLKeyProxyMode = CFSTR ("ProxyMode");
const enumSLProxyMode kSLDefProxyMode = kSLProxyNone;

const CFStringRef kSLKeyProxyAddress = CFSTR ("ProxyAddress");
const CFStringRef kSLDefProxyAddress = CFSTR ("");

const CFStringRef kSLKeyProxyPort = CFSTR ("ProxyPort");
const long        kSLDefProxyPort = -1;

typedef enum {
	kSLFreqDisabled = 0,
	kSLFreqStartup,
	kSLFreqDay,
	kSLFreqWeek,
	kSLFreqMonth,
	kSLFreqDebug
} enumSLFreq;

const CFStringRef kSLKeyFreq = CFSTR("Frequency");
const enumSLFreq  kSLDefFreq = kSLFreqStartup;

const CFStringRef kSLKeySyslistAcctUserName = CFSTR("AcctUserName");
const CFStringRef kSLDefSyslistAcctUserName = CFSTR("");

const CFStringRef kSLKeySyslistAcctPwd = CFSTR ("AcctPwd");
const CFStringRef kSLDefSyslistAcctPwd = CFSTR ("");
	
const CFStringRef kSLKeySyslistAcctCode = CFSTR ("AcctCode");
const CFStringRef kSLDefSyslistAcctCode = CFSTR ("");

const CFStringRef kSLKeySyslistMachID = CFSTR ("MachID");
const CFStringRef kSLDefSyslistMachID = CFSTR ("");

const CFStringRef kSLKeyReference	= CFSTR("Reference");
const CFStringRef kSLKeyLastInv		= CFSTR("LastInv");
const CFStringRef kSLKeyAlign		= CFSTR("Align");

const CFStringRef kSLKeyFailContact = CFSTR("FailContact");
const long kSLDefFailContact = 0;

//////////////////////////////////////////////
//
// Encryption Keys
const unsigned char kSLCryptKey[] = "DeusEnigmaMachinmaOrator";
const unsigned char kSLCryptIV[] = "PaxEternae";
	
///////////////////////////////////////////////
// 
// SyslistPrefs - Accessor class for all Prefs
class SyslistPrefs 
{
public:

	static bool Sync() 
	{
		return CFPreferencesSynchronize (
			kSLAppID, 
			kCFPreferencesAnyUser, 
			kCFPreferencesCurrentHost);
	}
	
public:
	
	static CFStringRef getServerURL()
	{
		return getCFStringValue (
			kSLKeyServerURL,
			kSLDefServerURL);
	}
	
	static void getServerURL(std::string & RetVal) 
	{
		getStringValue (
			kSLKeyServerURL,
			RetVal,
			kSLDefServerURL);
	}
	
	static void setServerURL (CFStringRef NewVal)
	{
		setCFStringValue(kSLKeyServerURL,NewVal);
	}
	
	static void setServerURL(std::string NewVal)
	{
		setStringValue (
			kSLKeyServerURL,
			NewVal);
	}
	
	static void getServerPort(long & RetVal)
	{
		RetVal = kSLDefServerPort;
		
		getLongValue (
			kSLKeyServerPort,
			RetVal);
	}
	
	static void setServerPort (long NewVal)
	{
		setLongValue (
			kSLKeyServerPort,
			NewVal);
	}
	
	static void getProxyMode (enumSLProxyMode & RetVal)
	{
		RetVal = kSLDefProxyMode;
		
		getLongValue (
			kSLKeyProxyMode,
			(long &) RetVal);
	}
	
	static void setProxyMode (enumSLProxyMode NewVal)
	{		
		setLongValue (
			kSLKeyProxyMode,
			(long) NewVal);
	}
	
	static CFStringRef getProxyAddress()
	{
		return getCFStringValue(
			kSLKeyProxyAddress,
			kSLDefProxyAddress);
	}
	
	static void getProxyAddress (std::string & RetVal)
	{
		getStringValue (
			kSLKeyProxyAddress,
			RetVal);
	}
	
	static void setProxyAddress (CFStringRef NewVal)
	{
		setCFStringValue(
			kSLKeyProxyAddress,
			NewVal);
	}
	
	static void setProxyAddress (std::string NewVal)
	{
		setStringValue (
			kSLKeyProxyAddress,
			NewVal);
	}
	
	static void getProxyPort (long & RetVal)
	{
		RetVal = kSLDefProxyPort;
		
		getLongValue(
			kSLKeyProxyPort,
			RetVal);
	}
	
	static void setProxyPort (long NewVal)
	{
		setLongValue (
			kSLKeyProxyPort,
			NewVal);
	}
	
	static void getFrequency (enumSLFreq & RetVal)
	{
		RetVal = kSLDefFreq;
		
		getLongValue(
			kSLKeyFreq,
			(long &) RetVal);
	}
	
	static void setFrequency (enumSLFreq NewVal)
	{
		setLongValue(
			kSLKeyFreq,
			(long) NewVal);
	}
	
	static CFStringRef getAcctUserName()
	{
		CFAuto<CFStringRef> StringVal;
		
		getCryptCFStringValue(
			kSLKeySyslistAcctUserName,
			&StringVal);
			
		return StringVal.Detach();
	}
	
	static void getAcctUserName (std::string & RetVal)
	{
		getCryptStringValue (
			kSLKeySyslistAcctUserName,
			RetVal);
	}
	
	static void setAcctUserName (CFStringRef NewVal)
	{
		setCryptCFStringValue(kSLKeySyslistAcctUserName, NewVal);
	}
	
	static void setAcctUserName (std::string NewVal)
	{
		setCryptStringValue (
			kSLKeySyslistAcctUserName,
			NewVal);
	}
	
	static CFStringRef getAcctPwd()
	{
		CFAuto<CFStringRef> StringVal;
		
		getCryptCFStringValue(
			kSLKeySyslistAcctPwd,
			&StringVal);
			
		return StringVal.Detach();
	}
		
	static void getAcctPwd (std::string & RetVal)
	{
		getCryptStringValue (
			kSLKeySyslistAcctPwd,
			RetVal);
	}

	static void setAcctPwd (CFStringRef NewVal)
	{
		setCryptCFStringValue(kSLKeySyslistAcctPwd, NewVal);
	}
		
	static void setAcctPwd (std::string NewVal)
	{
		setCryptStringValue (
			kSLKeySyslistAcctPwd,
			NewVal);
	}
	
	static CFStringRef getAcctCode()
	{
		CFAuto<CFStringRef> StringVal;
		
		getCryptCFStringValue(
			kSLKeySyslistAcctCode,
			&StringVal);
			
		return StringVal.Detach();
	}
		
	static void getAcctCode (std::string & RetVal)
	{
		getCryptStringValue (
			kSLKeySyslistAcctCode,
			RetVal);
	}
	
	static void setAcctCode (CFStringRef NewVal)
	{
		setCryptCFStringValue(kSLKeySyslistAcctCode, NewVal);
	}
		
	static void setAcctCode(std::string NewVal)
	{
		setCryptStringValue (
			kSLKeySyslistAcctCode,
			NewVal);
	}
	
	static CFStringRef getMachID ()
	{
		CFAuto<CFStringRef> StringVal;
		
		getCryptCFStringValue(
			kSLKeySyslistMachID,
			&StringVal);
		
		return StringVal.Detach();
	}
	
	static void getMachID(std::string & RetVal)
	{
		getCryptStringValue (
			kSLKeySyslistMachID,
			RetVal);
	}
	
	static void setMachID(CFStringRef NewVal)
	{
		setCryptCFStringValue(
			kSLKeySyslistMachID,
			NewVal);
	}
	
	static void setMachID(std::string NewVal)
	{
		setCryptStringValue (
			kSLKeySyslistMachID,
			NewVal);
	}
	
	static void getReferenceTime(CFAbsoluteTime & RetVal)
	{
		getCryptValue (kSLKeyReference, &RetVal);
	}
	
	static void setReferenceTime(CFAbsoluteTime NewVal)
	{
		setCryptValue (kSLKeyReference, NewVal);
	}
	
	static void getAlignTime(CFAbsoluteTime & RetVal)
	{
		getCryptValue(kSLKeyAlign, &RetVal);
	}
	
	static void setAlignTime(CFAbsoluteTime NewVal)
	{
		setCryptValue (kSLKeyAlign, NewVal);
	}
	
	static void getLastTime(CFAbsoluteTime & RetVal)
	{
		getCryptValue(kSLKeyLastInv, &RetVal);
	}

	static void setLastTime(CFAbsoluteTime NewVal)
	{
		setCryptValue (kSLKeyLastInv, NewVal);
	}
	
	static void getFailedContact(long & RetVal)
	{
		RetVal = kSLDefFailContact;
		getLongValue (
			kSLKeyFailContact,
			RetVal);
	}
	
	static void setFailedContact (long NewVal)
	{
		setLongValue (
			kSLKeyFailContact,
			NewVal);
	}	
		
protected:

	static bool getStringValue ( CFStringRef Key, std::string & RetVal, CFStringRef Default = NULL ) 
	{
		CFAuto<CFStringRef> PropValue;
		
		PropValue.Attach(getCFStringValue (Key, Default));
		
		if (PropValue.IsEmpty() || (CFGetTypeID(PropValue) != CFStringGetTypeID())) {
			RetVal.clear();
			return false;
		}
		
		CFIndex PropLen = CFStringGetLength((const CFStringRef) PropValue) + 1;
		char RetBuf[PropLen];
		
		Boolean Success = 
			CFStringGetCString(
				(const CFStringRef) PropValue, 
				RetBuf, PropLen,
				kCFStringEncodingUTF8);
		
		if (Success == true)
			RetVal = RetBuf;
		else
			RetVal.clear();
			
		return Success;
	}
	
	static bool setStringValue (CFStringRef Key, std::string & NewVal )
	{
		CFAuto<CFStringRef> PropStringValue(NewVal.c_str());
		
		return setCFValue(Key, PropStringValue);
	}
	
	static CFStringRef getCFStringValue (CFStringRef Key, CFStringRef Default = NULL)
	{
		CFAuto<CFPropertyListRef> PropValue;
		
		getCFValue (Key, &PropValue);
		
		if (PropValue.IsEmpty() || (CFGetTypeID(PropValue) != CFStringGetTypeID())) {
			if (Default != NULL)
				return CFStringCreateCopy(kCFAllocatorDefault,Default);
			else 
				return NULL;
		}
		
		return reinterpret_cast<CFStringRef>(PropValue.Detach());
	}
	
	static void setCFStringValue (CFStringRef Key, CFStringRef NewVal)
	{
		setCFValue (Key, NewVal);
	}
	
	static bool getLongValue (CFStringRef Key, long & RetVal)
	{
		CFAuto<CFPropertyListRef> PropValue;
		
		getCFValue (Key, &PropValue);
		
		if (PropValue.IsEmpty() || (CFGetTypeID(PropValue) != CFNumberGetTypeID())) {
			return false;
		}
		
		return CFNumberGetValue (reinterpret_cast<CFNumberRef>((CFPropertyListRef)PropValue), kCFNumberLongType, & RetVal);
	}

	static bool setLongValue (CFStringRef Key, long NewVal)
	{
		CFAuto<CFNumberRef> PropNumberValue;
		
		PropNumberValue.Attach (
			CFNumberCreate (kCFAllocatorDefault, kCFNumberLongType, & NewVal));
		
		return setCFValue (Key, PropNumberValue);
		
	}
	
	static bool getCryptCFStringValue (CFStringRef Key, CFStringRef * RetVal)
	{
		std::string cstrRetVal;
		
		getCryptStringValue (Key, cstrRetVal);
		
		*RetVal = CFStringCreateWithCString(
			kCFAllocatorDefault,
			cstrRetVal.c_str(),
			kCFStringEncodingUTF8);
			
		return true;
	}	
	
	static bool getCryptStringValue (CFStringRef Key, std::string & RetVal)
	{
		AutoCharBuf PlainPrefData;
		long PlainPrefDataLen = 0;
		
		bool Success;
		
		RetVal.clear();
		
		Success = getCryptCDataValue (
			Key,
			&PlainPrefData,
			&PlainPrefDataLen);
		
		if (Success == false)
			return false;
		
		if (PlainPrefDataLen == 0 || PlainPrefData == NULL)
			return true;
		
		assert(PlainPrefData[PlainPrefDataLen - 1] == '\0');
		
		RetVal = reinterpret_cast<char *>((unsigned char *)PlainPrefData);
		
		return true;
	}
	
	template <class T>
	static bool getCryptValue (CFStringRef Key, T * RetVal)
	{
		AutoCharBuf PlainPrefData;
		long PlainPrefDataLen = 0;
		
		bool Success;
		
		Success = getCryptCDataValue (
			Key,
			&PlainPrefData,
			&PlainPrefDataLen);
		
		if (Success == false || PlainPrefData.IsEmpty() || PlainPrefDataLen != sizeof(T))
			return false;
	
		memcpy(RetVal, PlainPrefData, sizeof(T));
		
		return true;
	}
	
	static bool getCryptCDataValue (CFStringRef Key, unsigned char ** RetData, long * RetDataLen)
	{
		unsigned char * CryptPrefData = NULL;
		long CryptPrefDataLen;
		
		bool Success;
				
		Success = getCDataValue (Key, &CryptPrefData, &CryptPrefDataLen);
		
		if (Success == false)
			return false;
		
		AutoCharBuf PlainPrefData(CryptPrefDataLen);
		
		EVP_CIPHER_CTX ctx;
        EVP_CIPHER_CTX_init(&ctx);
        EVP_DecryptInit(&ctx, EVP_bf_cbc(), const_cast<unsigned char *>(kSLCryptKey), const_cast<unsigned char *>(kSLCryptIV));

		int RealPlainPrefDataLen = 0;
		
        if(!EVP_DecryptUpdate(
			&ctx, 
			(unsigned char *) PlainPrefData,
			&RealPlainPrefDataLen,
			CryptPrefData,
			CryptPrefDataLen))
				return false;
			
        /* Buffer passed to EVP_EncryptFinal() must be after data just
         * encrypted to avoid overwriting it.
         */
		int CleanupLen = 0;
		
        if(!EVP_DecryptFinal(
			&ctx, 
			PlainPrefData + RealPlainPrefDataLen,
			&CleanupLen))
				return false;
			
        RealPlainPrefDataLen += CleanupLen;
		
		*RetDataLen = RealPlainPrefDataLen;
		*RetData = 	PlainPrefData.Detach();
				
		return true;
	}
	
	static bool setCryptCFStringValue (CFStringRef Key, CFStringRef NewVal)
	{
		long ValueLen = CFStringGetLength(NewVal) + 1;
		
		char cstrValueBuf[ValueLen];
		
		CFStringGetCString(
			NewVal,
			cstrValueBuf,
			ValueLen,
			kCFStringEncodingUTF8);
			
		return setCryptStringValue(Key, std::string(cstrValueBuf));
	}
	
	static bool setCryptStringValue (CFStringRef Key, std::string NewVal)
	{
		return setCryptCDataValue (Key, (unsigned char *) NewVal.c_str(), NewVal.length() + 1);
	}
	
	template <class T>
	static bool setCryptValue(CFStringRef Key, T & NewVal)
	{
		long DataSize = sizeof(T);
		
		return setCryptCDataValue (
			Key,
			reinterpret_cast<unsigned char*>(& NewVal),
			DataSize);
	}
	
	static bool setCryptCDataValue (CFStringRef Key, unsigned char * NewData, long NewDataLen)
	{	
        EVP_CIPHER_CTX ctx;
        EVP_CIPHER_CTX_init(&ctx);
			
        if (!EVP_EncryptInit(&ctx, EVP_bf_cbc(), const_cast<unsigned char *> (kSLCryptKey), const_cast<unsigned char *> (kSLCryptIV)))
			return false;

		long PossibleCryptPrefDataLen = NewDataLen + (EVP_CIPHER_CTX_block_size(&ctx) * 2);
		unsigned char CryptPrefData[PossibleCryptPrefDataLen];

		int RealCryptPrefDataLen = 0;
		
        if(!EVP_EncryptUpdate(
			&ctx, 
			CryptPrefData,
			&RealCryptPrefDataLen,
			NewData,
			NewDataLen))
				return false;
			
        /* Buffer passed to EVP_EncryptFinal() must be after data just
         * encrypted to avoid overwriting it.
         */
		int CleanupLen = 0;
		
        if(!EVP_EncryptFinal(
			&ctx, 
			CryptPrefData + RealCryptPrefDataLen,
			&CleanupLen))
				return false;
			
        RealCryptPrefDataLen += CleanupLen;
						
		return setCDataValue (Key, CryptPrefData, RealCryptPrefDataLen);
	}
		
	static bool getCDataValue (CFStringRef Key, unsigned char ** RetData, long * RetDataLen)
	{		
		*RetData = NULL; // Assume failure
		
		CFAuto<CFDataRef> PropDataValue;
		
		bool Success;
		
		Success = getCFDataValue(Key, &PropDataValue);
		
		if (Success == false || PropDataValue.IsEmpty())
			return false;
			
		*RetDataLen = CFDataGetLength (PropDataValue);
	
		if (*RetDataLen <= 0)
			return false;
			
		*RetData = new unsigned char[*RetDataLen];
		
		if (*RetData == NULL)
			return false;
			
		CFDataGetBytes (
			PropDataValue,
			CFRangeMake(0,*RetDataLen),
			*RetData);
		
		return true;
	}
		
	static bool setCDataValue (CFStringRef Key, unsigned char * NewData, long NewDataLen)
	{
		assert(NewDataLen >=0);

		CFAuto<CFDataRef> PropDataValue;
		
		PropDataValue.Attach (
			CFDataCreate (
				kCFAllocatorDefault,
				NewData,
				NewDataLen));
		
		return setCFDataValue (Key, PropDataValue);
	}
	
	static bool getCFDataValue (CFStringRef Key, CFDataRef * RetVal)
	{
		CFAuto<CFPropertyListRef> PropDataValue;
		
		getCFValue (Key, &PropDataValue);
		
		if (PropDataValue.IsEmpty() || (CFGetTypeID(PropDataValue) != CFDataGetTypeID()))
			return false;
			
		*RetVal = (CFDataRef) PropDataValue.Detach();
		
		return true;
	}
	
	static bool setCFDataValue (CFStringRef Key, CFDataRef NewVal)
	{
		setCFValue (Key, NewVal);
		
		return true;
	}
	
	static bool getCFValue (CFStringRef Key, CFPropertyListRef * RetVal) 
	{
		* RetVal =
			CFPreferencesCopyValue (
				Key, kSLAppID,
				kCFPreferencesAnyUser, kCFPreferencesCurrentHost);
				
		if (RetVal == NULL)
			return false;
			
		return true;
	}
	
	static bool setCFValue (CFStringRef Key, CFPropertyListRef NewVal)
	{
		CFPreferencesSetValue (
			Key, NewVal, kSLAppID,
			kCFPreferencesAnyUser, kCFPreferencesCurrentHost);
			
		return true;
	}			

};
#endif