#include "DiskCollector.h"
#include "IOUtil.h"
#include <sstream>

static const char * DCL_TAG = "StorageList";
static const char * DCI_TAG = "Disk";
static const char * DCO_TAG = "Optical";

//static const char * DCI_TYPE = "Type";
//static const char * DCI_TYPE_FIXED = "Fixed";
//static const char * DCI_TYPE_REMOVABLE = "Removeable";

static const char * DCI_MFR = "Manufacturer";
static const char * DCI_MODEL = "Model";
static const char * DCI_INTF = "InterfaceType";
static const char * DCI_SIZE = "Size";

template <CFNumberType N, class T>
void ExtractNumberString (CFNumberRef CFNum, std::string & Dest)
{
	ostringstream SizeConvert;
	T DiskSize;
	Boolean Success;
	
	Success = CFNumberGetValue(CFNum, N, &DiskSize);
	
	if (Success == false)
		return;
		
	Boolean WasMB = false;
	
	if (DiskSize > 1000000) {
		DiskSize /= 1000000;
		WasMB = true;
	}
	
	SizeConvert << DiskSize;
	
	if (WasMB == true)
		SizeConvert << " MB";

	Dest = SizeConvert.str();
}

template <>
void ExtractNumberString<kCFNumberSInt16Type, SInt16> (CFNumberRef CFNum, std::string & Dest) 
{
	Dest = "<1MB";
}

void FindSizeAttributes( io_registry_entry_t Item, NVDataItem * Target)
{
	IOAuto<io_iterator_t> DiskIter;
	
	IORegistryEntryCreateIterator(Item,kIOServicePlane,kIORegistryIterateRecursively,&DiskIter);
	IOServiceUtil ChildItem;

	while (ChildItem.Attach(IOIteratorNext(DiskIter)), ChildItem != 0) {
			
		if (!IOObjectConformsTo(ChildItem,"IOMedia"))
			continue;
		
		CFAuto<CFBooleanRef> IsLeaf((CFBooleanRef) ChildItem.Query("Leaf"));
		
		if (IsLeaf.IsEmpty() || CFGetTypeID(IsLeaf) != CFBooleanGetTypeID()
			|| CFBooleanGetValue(IsLeaf) == true)
			continue;
			
		CFAuto<CFBooleanRef> IsWhole((CFBooleanRef) ChildItem.Query("Whole"));
		
		if (IsWhole.IsEmpty() || CFGetTypeID(IsWhole) != CFBooleanGetTypeID()
			|| CFBooleanGetValue(IsWhole) == false)
			continue;
		
		CFAuto<CFNumberRef> MediaSize ((CFNumberRef) ChildItem.Query("Size"));
		if (MediaSize.IsEmpty() || CFGetTypeID(MediaSize) != CFNumberGetTypeID())
			return;
		
		CFNumberType SizeType = CFNumberGetType(MediaSize);
		
		//Boolean Success;
		std::string DiskStringSize;
		
		switch (SizeType) {
		
		case kCFNumberIntType: 
			ExtractNumberString<kCFNumberIntType, int>(MediaSize, DiskStringSize);
			break;
			
		case kCFNumberLongType : 
			ExtractNumberString<kCFNumberLongType, long>(MediaSize, DiskStringSize);
			break;
			
		case kCFNumberLongLongType: 
			ExtractNumberString<kCFNumberLongLongType, long long>(MediaSize, DiskStringSize);
			break;
		
		case kCFNumberSInt64Type: 
			ExtractNumberString<kCFNumberSInt64Type, SInt64>(MediaSize, DiskStringSize);
			break;
			
		case kCFNumberSInt32Type: 
			ExtractNumberString<kCFNumberSInt32Type, SInt32>(MediaSize, DiskStringSize);
			break;

		case kCFNumberSInt16Type:
			ExtractNumberString<kCFNumberSInt16Type, SInt16>(MediaSize, DiskStringSize);
			break;
			
		default:
			return;
		}
		
		if (DiskStringSize.length() > 0)
			Target->AddNVItem(DCI_SIZE, DiskStringSize.c_str());
			
		return;
	}
}

void ExtractDictString(CFDictionaryRef Source, CFStringRef Key, std::string & Dest)
{
	Boolean Present;
	CFStringRef CFDest;
	
	Present = CFDictionaryGetValueIfPresent(
		Source,
		Key, 
		(const void **) &CFDest);
		
	if (Present == true && CFGetTypeID(CFDest) == CFStringGetTypeID()) {
	
		long StringSize = 0;
		StringSize = CFStringGetLength(CFDest) + 1;
		char PropCStr[StringSize];
		
		CFStringGetCString(CFDest,PropCStr,StringSize,kCFStringEncodingUTF8);
		Dest = PropCStr;
	}
	else
		Dest.clear();
}

long DiskCollector::Collect(NVDataItem ** ReturnItem)
{
	auto_ptr <NVDataItem> DiskItems (new NVDataItem(DCL_TAG));
	IOServiceListUtil StorageList("IOBlockStorageDevice");

	IOServiceUtil StorageItem;

	while (StorageItem.Attach(StorageList.Next()), StorageItem != 0) {
		

		CFAuto<CFMutableDictionaryRef> ItemInfo;
		ItemInfo.Attach(
			(CFMutableDictionaryRef) StorageItem.Query("Device Characteristics"));
			
		if (ItemInfo.IsEmpty() || CFGetTypeID(ItemInfo) != CFDictionaryGetTypeID())
			continue;
			
		CFAuto<CFMutableDictionaryRef> ItemProtoInfo;
		ItemProtoInfo.Attach(
			(CFMutableDictionaryRef) StorageItem.Query("Protocol Characteristics"));
			
		if (ItemProtoInfo.IsEmpty() == false && CFGetTypeID(ItemInfo) != CFDictionaryGetTypeID())
			ItemProtoInfo.Clear();
		
		std::string ItemModel;
		std::string ItemMfr;
		std::string ItemIntf;
						
		ExtractDictString(ItemInfo, CFSTR("Product Name"), ItemModel);
		ExtractDictString(ItemInfo, CFSTR("Vendor Name"), ItemMfr);
		
		if (ItemProtoInfo.IsEmpty() == false) {
			ExtractDictString(ItemProtoInfo, CFSTR("Physical Interconnect"), ItemIntf);
		}
		
		if (ItemIntf == "Virtual Interface") // disk images
			continue;
		
		Boolean IsCD = CFDictionaryContainsKey(ItemInfo,CFSTR("CD Features"));
		Boolean IsDVD = CFDictionaryContainsKey(ItemInfo,CFSTR ("DVD Features"));

		const char * TypeTag;
		
		if (IsCD || IsDVD)
			TypeTag = DCO_TAG;
		else
			TypeTag = DCI_TAG;
			
		auto_ptr<NVDataItem> MasterItem(new NVDataItem(TypeTag));
		
		if (ItemModel.length() > 0)
			MasterItem->AddNVItem(DCI_MODEL, ItemModel.c_str());
		
		if (ItemMfr.length() > 0)
			MasterItem->AddNVItem(DCI_MFR, ItemMfr.c_str());
			
		if (ItemIntf.length() > 0)
			MasterItem->AddNVItem(DCI_INTF, ItemIntf.c_str());
		
		if (TypeTag == DCI_TAG)
			FindSizeAttributes (StorageItem, MasterItem.get());
			
		if (MasterItem->NVCount() > 0)
			DiskItems->AddSubItem(MasterItem.release());
	}
	
	*ReturnItem = DiskItems.release();
#ifdef TRACE
	printf("Disk Complete\n");
#endif	
	return ERROR_SUCCESS;
}
