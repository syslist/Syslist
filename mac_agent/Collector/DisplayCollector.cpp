#include "DisplayCollector.h"
#include "IOUtil.h"

#include <iostream>
#include <sstream>

static const char * DISPCL_TAG = "DisplayAdaptorList";
static const char * DISPCI_TAG = "DisplayAdaptor";

static const char * DISP_MFR = "Manufacturer";
static const char * DISP_DESC = "Description";
static const char * DISP_VENDORID = "PCIVendorID";
static const char * DISP_PRODUCTID = "PCIProdID";

static const char * DISP_MFR_ATI_STR = "ATI Technologies Inc";
static const long DISP_MFR_ATI_ID = 0x1002;

static const char * DISP_MFR_NV_STR = "NVidia Corporation";
static const long DISP_MFR_NV_ID = 0x10DE;

long DisplayCollector::Collect(NVDataItem ** ReturnItem)
{
	auto_ptr<NVDataItem> DisplayDevItems (new NVDataItem(DISPCL_TAG));

	CFAuto<CFMutableDictionaryRef> PCIDisplayDeviceMatch;
	
	PCIDisplayDeviceMatch.Attach(
		IOServiceMatching("IOPCIDevice"));
		
	CFDictionaryAddValue(PCIDisplayDeviceMatch, CFSTR("IOPCIClassMatch"), CFSTR("0x03000000&0xffff0000"));
		
	IOServiceUtil CurrPCIDispDevice; 

	IOAuto<io_iterator_t> PCIDispDeviceList;
	
	IOServiceGetMatchingServices(IOUtil::s_MasterPort, PCIDisplayDeviceMatch.Detach(), &PCIDispDeviceList);
	
	while (CurrPCIDispDevice.Attach(IOIteratorNext(PCIDispDeviceList)), CurrPCIDispDevice.IsEmpty() != true) {
	
		auto_ptr<NVDataItem> DisplayDev (new NVDataItem(DISPCI_TAG));
		
		CFAuto<CFTypeRef> DevModel;
		
		DevModel.Attach(CurrPCIDispDevice.Query("model"));
		
		CFAuto<CFStringRef> DataItemString;

		if (DevModel.IsEmpty() == false) {
			if (CFGetTypeID(DevModel) == CFDataGetTypeID()) {
				DataItemString.Attach(
					CFStringCreateFromExternalRepresentation(
						kCFAllocatorDefault,
						DevModel,
						kCFStringEncodingUTF8));
			}
			else if (CFGetTypeID(DevModel) == CFStringGetTypeID())
				DataItemString = (CFStringRef) DevModel;
				
			std::string DataStringVal;
			
			DataItemString.GetCString(DataStringVal);
			DisplayDev->AddNVItem(DISP_DESC, DataStringVal.c_str());
		}
		
		CFAuto<CFDataRef> DevVendor;
		
		DevVendor.Attach((CFDataRef) CurrPCIDispDevice.Query("vendor-id"));
		
		if (DevVendor.IsEmpty() == false 
			&& CFGetTypeID(DevVendor) == CFDataGetTypeID()
			&& CFDataGetLength(DevVendor) <= (CFIndex) sizeof(long)
			&& CFDataGetLength(DevVendor) > (CFIndex) 0) {
		
			long VendorNum = 0;
			ostringstream VendorNumString;
			
			UInt8 * InsertPoint = (UInt8 *) &VendorNum;
			
			// we are big endian, and we need to put the low end bytes into the
			// long we are using offset by the missing bytes so that we can 
			// get a proper string
			InsertPoint += sizeof(long) - CFDataGetLength(DevVendor);
			
			CFDataGetBytes(
				DevVendor,
				CFRangeMake(0,CFDataGetLength(DevVendor)),
				InsertPoint);
				
			VendorNumString << "0x" << std::hex << VendorNum;
			
			DisplayDev->AddNVItem(DISP_VENDORID, VendorNumString.str().c_str());
			
			if (VendorNum == DISP_MFR_ATI_ID) {
				DisplayDev->AddNVItem(DISP_MFR, DISP_MFR_ATI_STR);
			}
			else if (VendorNum == DISP_MFR_NV_ID) {
				DisplayDev->AddNVItem(DISP_MFR, DISP_MFR_NV_STR);
			}
		}
		
		CFAuto<CFDataRef> DevID;
		
		// This next batch of code is identical to the code above - and should be
		// turned into a function that both of them call. However, as usual time
		// dictates all decisions and this is a bad one
		
		DevID.Attach((CFDataRef) CurrPCIDispDevice.Query("device-id"));
		
		if (DevID.IsEmpty() == false 
			&& CFGetTypeID(DevID) == CFDataGetTypeID()
			&& CFDataGetLength(DevID) <= (CFIndex) sizeof(long)
			&& CFDataGetLength(DevID) > (CFIndex) 0) {
		
			long VendorID = 0;
			ostringstream VendorIDString;
			
			UInt8 * InsertPoint = (UInt8 *) &VendorID;
			
			// we are big endian, and we need to put the low end bytes into the
			// long we are using offset by the missing bytes so that we can 
			// get a proper string
			InsertPoint += sizeof(long) - CFDataGetLength(DevID);
			
			CFDataGetBytes(
				DevID,
				CFRangeMake(0,CFDataGetLength(DevID)),
				InsertPoint);
				
			VendorIDString << "0x" << std::hex << VendorID;
			
			DisplayDev->AddNVItem(DISP_PRODUCTID, VendorIDString.str().c_str());
		}

		DisplayDevItems->AddSubItem(DisplayDev.release());
	}
	
	*ReturnItem = DisplayDevItems.release();
#ifdef TRACE
	printf("Display Complete\n");
#endif	
	return ERROR_SUCCESS;
}

