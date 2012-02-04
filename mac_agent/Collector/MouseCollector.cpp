#include "MouseCollector.h"
#include "IOUtil.h"

static const char * DCML_TAG = "PointingDeviceList";
static const char * DCMI_TAG = "PointingDevice";

static const char * DCMI_MFR = "Manufacturer";
static const char * DCMI_NAME = "Name";
static const char * DCMI_DESC = "Description";
static const char * DCMI_TRANS = "Transport";

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


long MouseCollector::Collect(NVDataItem ** ReturnItem)
{
	auto_ptr<NVDataItem> MouseItems (new NVDataItem(DCML_TAG));

	IOServiceListUtil MouseDevices("IOHIDPointing");
	
	IOServiceUtil CurrMouse;
	
	while (CurrMouse.Attach(MouseDevices.Next()), CurrMouse.IsEmpty() != true) {

		IOServiceUtil ParentController;
		kern_return_t ParentStatus;
		
		ParentStatus = IORegistryEntryGetParentEntry(CurrMouse, kIOServicePlane, & ParentController);
		if (ParentStatus != KERN_SUCCESS || ParentController.IsEmpty())
			continue;
	
		auto_ptr<NVDataItem> CurrMouse (new NVDataItem(DCMI_TAG));

		ExtractItemPropString(ParentController, "Manufacturer", DCMI_MFR, CurrMouse.get());
		ExtractItemPropString(ParentController, "DeviceName", DCMI_NAME, CurrMouse.get());
		long Status = ERROR_SUCCESS;
		Status = ExtractItemPropString(ParentController, "Product", DCMI_DESC, CurrMouse.get());
		if (Status != ERROR_SUCCESS) {
			if (IOObjectConformsTo(ParentController,"IOAppleBluetoothHIDDriver")) {
		
				CurrMouse->AddNVItem (DCMI_DESC, "Wireless");
			}
			else {
				CurrMouse->AddNVItem (DCMI_DESC, "Unknown");
			}
		}
		ExtractItemPropString(ParentController, "Transport", DCMI_TRANS, CurrMouse.get());
		
		MouseItems->AddSubItem (CurrMouse.release());
	}

	*ReturnItem = MouseItems.release();
#ifdef TRACE
	printf("mouse Complete\n");
#endif	
	return ERROR_SUCCESS;
}