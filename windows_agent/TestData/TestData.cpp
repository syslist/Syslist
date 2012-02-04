// TestData.cpp : Defines the entry point for the console application.
//

#include "stdafx.h"
#include "DataItem.h"
#include <iostream>
#include <typeinfo>

static const type_info& DataItemType = typeid(DataItem);
static const type_info& NameValueType = typeid(NameValueData);
static const type_info& ListType = typeid (DataList);

DataList * CreateItems()
{
	DataList * ReturnList = new DataList("Master List");
	
	DataList * CurrSubList;

	CurrSubList = new DataList("List 1");
	CurrSubList->Add (new NameValueData ("Item 1", "Value a"));
	CurrSubList->Add (new NameValueData ("Item 2", "Value b"));
	CurrSubList->Add (new NameValueData ("Item 3", "Value c"));
	CurrSubList->Add (new NameValueData ("Item 4", "Value d"));
	
	ReturnList->Add (CurrSubList);

	CurrSubList = new DataList("List 2");
	CurrSubList->Add (new NameValueData ("Item 7", "Value z"));
	CurrSubList->Add (new NameValueData ("Item 8", "Value x"));
	CurrSubList->Add (new NameValueData ("Item 9", "Value y"));
	CurrSubList->Add (new NameValueData ("Item 11", "Value w"));

	ReturnList->Add (CurrSubList);

	ReturnList->Add (new NameValueData("SomeName", "SomeValue"));

	ReturnList->Add (new DataItem("DataItemName"));

	return ReturnList;

}

void DumpPrefix (long Depth)
{
	long Prefix;

	for (Prefix = 0; Prefix < Depth; Prefix ++)
		cout << '\t';
}

void DumpItems(DataItem * DumpItem, long DumpDepth = 0)
{
	DumpPrefix (DumpDepth);
	
	const type_info & DumpType = typeid(*DumpItem);

	if (DumpType == DataItemType) {
		cout << "(Data Item) Name = " << DumpItem->Name() << endl;
	}
	else if (DumpType == NameValueType) {
		NameValueData * NVItem = dynamic_cast<NameValueData *>(DumpItem);

		cout << "(Name/Val) Name = " << NVItem->Name() << ", Value = " << NVItem->Value() << endl;
	}
	else if (DumpType == ListType) {

		cout << "(Data List) Name = " << DumpItem->Name() << endl;

		DataList * DLItem = dynamic_cast<DataList *>(DumpItem);

		long ListIndex;
				
		for (ListIndex = 0; ListIndex < DLItem->ItemCount() ; ListIndex ++) {

			DumpItems (DLItem->GetItem(ListIndex), DumpDepth + 1);
		}
	}
}

void CreateNewDataItems (NVDataItem ** NewItem)
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
	DiskItem->AddNVItem("Manufacturer","IBM");
	DiskItem->AddNVItem("Model", "120GXP");
	DiskItem->AddNVItem("Revision", "0439a");
	DiskItem->AddNVItem("Capacity", "40GB");
	DiskItem->AddNVItem("Bus", "IDE");
	StorageItem->AddSubItem(DiskItem);

	DiskItem = new NVDataItem("Disk");
	DiskItem->AddNVItem("Manufacturer","Creative");
	DiskItem->AddNVItem("Model", "uber12");
	DiskItem->AddNVItem("Revision", "1223");
	DiskItem->AddNVItem("Capacity", "20GB");
	DiskItem->AddNVItem("Bus", "USB");
	StorageItem->AddSubItem(DiskItem);

	*NewItem = DataItem;

}

void DumpNewItems(NVDataItem * DataItem, long Depth = 0)
{

	DumpPrefix (Depth);

	const char * CurrTag;
	DataItem->GetItemTag(&CurrTag);
	cout << "<" << CurrTag << " ";

	if (DataItem->NVCount() > 0)
		cout << endl;

	Depth++;

	long NVIndex;
	const char * CurrName;
	const char * CurrVal;

	for (NVIndex = 0; NVIndex < DataItem->NVCount(); NVIndex ++) {
		DataItem->GetNVItem( NVIndex, &CurrName, &CurrVal);
		DumpPrefix (Depth);
		cout << CurrName << "=\"" << CurrVal << "\"" << endl;
	}

	DumpPrefix(Depth);
	if (NVIndex == 0)
		cout << "\\>" << endl;
	else
		cout << ">" << endl;

	NVDataItem * SubDataItem;
	long SubIndex;

	for (SubIndex = 0; SubIndex < DataItem->SubItemCount(); SubIndex ++) {
		DataItem->GetSubItem(SubIndex, & SubDataItem);
		DumpNewItems(SubDataItem, Depth);
	}
	
	Depth --;

	if (SubIndex > 0) {
		DumpPrefix(Depth);
		cout << "<\\" << CurrTag << ">" << endl;
	}
}
		
int main(int argc, char* argv[])
{
	cout << "Create Simple Data List" << endl;

	DataList * ItemList = NULL;
	ItemList = CreateItems();

	cout << "Dumping Simple Data List" << endl;

	DumpItems(ItemList);
	
	cout << "Create New Data List" << endl;

	NVDataItem * NewItemList = NULL;
	CreateNewDataItems (&NewItemList);

	cout << "Dumping New Data List" << endl;

	DumpNewItems(NewItemList);

	getchar();

	return 0;
}

