// TestTransport.cpp : Defines the entry point for the console application.
//

#include "stdafx.h"
#include "TransportProto.h"
#include "ScreenTransport.h"
#include "FileTransport.h"
#include "SyslistHTTPTransport.h"
#include "HTTPSecureTransport.h"

void CreateDataItems (NVDataItem ** NewItem)
{
	NVDataItem * DataItem = new NVDataItem("SyslistData");

	DataItem->AddNVItem("Version","0.3b");
	DataItem->AddNVItem("TimeDate", "3-May-2003-11-45");

	NVDataItem * SystemInfo = new NVDataItem("SysInfo");
	SystemInfo->AddNVItem("Name","Bart");
	SystemInfo->AddNVItem("O/S", "Windows XP SP1");
	DataItem->AddSubItem(SystemInfo);

	NVDataItem * HardwareItem = new NVDataItem ("Hardware");
	HardwareItem->AddNVItem("Version", "0.0a");
	DataItem->AddSubItem(HardwareItem);

	NVDataItem * StorageItem  = new NVDataItem ("Storage");
	StorageItem->AddNVItem ("Version", "0.0a");
	HardwareItem->AddSubItem(StorageItem);

	NVDataItem * DiskItem = new NVDataItem("Disk");
	DiskItem->AddNVItem("Manufacturer","IBM\'mine\'");
	DiskItem->AddNVItem("Model", "120GXP \"deadly!\'");
	DiskItem->AddNVItem("Revision", "0439a");
	DiskItem->AddNVItem("Capacity", "40GB");
	DiskItem->AddNVItem("Bus", "IDE");
	StorageItem->AddSubItem(DiskItem);

	DiskItem = new NVDataItem("Disk");
	DiskItem->AddNVItem("Manufacturer","Creative & ME!");
	DiskItem->AddNVItem("Model", "uber12 <THE BEST!>");
	DiskItem->AddNVItem("Revision", "1223");
	DiskItem->AddNVItem("Capacity", "20GB");
	DiskItem->AddNVItem("Bus", "USB");
	StorageItem->AddSubItem(DiskItem);

	*NewItem = DataItem;

}

int main(int argc, char* argv[])
{
	NVDataItem * DumpItems;
	CreateDataItems( &DumpItems);

	long Status;

	ScreenTransport DumpDest;
	DumpDest.OpenURI("Screen:");
	DumpDest.TransmitData (DumpItems);
	DumpDest.Close();

	FileTransport FileDest;
	FileDest.OpenURI("File://c:/dumpdata.txt");
	FileDest.TransmitData(DumpItems);
	FileDest.Close();

#if 1
	SyslistHTTPTransport<HTTPSecureTransport> HTTPDest;
	Status = HTTPDest.OpenURI("HTTPS://yoda/");

	if (Status == ERROR_SUCCESS)
		Status = HTTPDest.TransmitData(DumpItems);

	HTTPDest.Close();
#else
	HTTPSecureTransport HTTPSecDest;
	Status = HTTPSecDest.OpenURI("HTTPS://yoda/mason/AgentReport.html");

	if (Status == ERROR_SUCCESS)
		Status = HTTPSecDest.TransmitData(DumpItems);
	HTTPSecDest.Close();
#endif

	long wait;
	cin >> wait;

	return 0;
}

