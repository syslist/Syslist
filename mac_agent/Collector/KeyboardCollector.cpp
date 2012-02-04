#include "KeyboardCollector.h"
#include "IOUtil.h"

static const char * DCKL_TAG = "KeyboardList";
static const char * DCKI_TAG = "KeyBoard";

static const char * DCKI_MFR = "Manufacturer";
static const char * DCKI_NAME = "Name";
static const char * DCKI_DESC = "Description";
static const char * DCKI_TRANS = "Transport";

static long ExtractItemPropString(IOServiceUtil & Item, char * QueryKey, const char * DataTag, NVDataItem * Target)
{
	CFAuto<CFStringRef> ItemCFProp;
		
	ItemCFProp.Attach((CFStringRef) Item.Query(QueryKey));
	if (ItemCFProp.IsEmpty()
		|| CFGetTypeID(ItemCFProp) != CFStringGetTypeID())
			return ERROR_GEN_FAILURE;
	
	std::string ItemPropString;
	ItemCFProp.GetCString(ItemPropString);
	
	if (ItemPropString.length() > 0)
		Target->AddNVItem(DataTag, ItemPropString.c_str());
	else 
		return ERROR_GEN_FAILURE;
	
	return ERROR_SUCCESS;
}


long KeyboardCollector::Collect(NVDataItem ** ReturnItem)
{
	auto_ptr<NVDataItem> KeyboardItems (new NVDataItem(DCKL_TAG));

	IOServiceListUtil KeyboardDevices("IOHIDKeyboard");
	
	IOServiceUtil CurrKeyboard;
	
	while (CurrKeyboard.Attach(KeyboardDevices.Next()), CurrKeyboard.IsEmpty() != true) {

		IOServiceUtil ParentController;
		kern_return_t ParentStatus;
		
		ParentStatus = IORegistryEntryGetParentEntry(CurrKeyboard, kIOServicePlane, & ParentController);
		if (ParentStatus != KERN_SUCCESS || ParentController.IsEmpty())
			continue;
	
		auto_ptr<NVDataItem> CurrKeyboard (new NVDataItem(DCKI_TAG));

		ExtractItemPropString(ParentController, "Manufacturer", DCKI_MFR, CurrKeyboard.get());
		ExtractItemPropString(ParentController, "DeviceName", DCKI_NAME, CurrKeyboard.get());
		long Status = ERROR_SUCCESS;
		Status = ExtractItemPropString(ParentController, "Product", DCKI_DESC, CurrKeyboard.get());
		
		if (Status != ERROR_SUCCESS) {
			if (IOObjectConformsTo(ParentController,"IOAppleBluetoothHIDDriver")) {
		
				CurrKeyboard->AddNVItem (DCKI_DESC, "Wireless");
			}
			else {
				CurrKeyboard->AddNVItem (DCKI_DESC, "Unknown");
			}
		}
		
		ExtractItemPropString(ParentController, "Transport", DCKI_TRANS, CurrKeyboard.get());
		
		KeyboardItems->AddSubItem (CurrKeyboard.release());
	}

	*ReturnItem = KeyboardItems.release();
	
#ifdef TRACE
	printf("keyboard Complete\n");
#endif	
	return ERROR_SUCCESS;
}