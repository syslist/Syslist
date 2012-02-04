#ifndef DATA_ITEM_H_INCLUDED
#define DATA_ITEM_H_INCLUDED

#include <vector>
#include <map>
#include <string>

#include "TextConv.h"

#pragma warning (push)
#pragma warning(disable:4786 )

using namespace std;

// base for all Data Items
class DataItem 
{
public:
	DataItem(char * ItemName = NULL)
	{
		if (ItemName != NULL) 
			m_ItemName = ItemName;
	};

	char * Name()
	{
		return (char *) m_ItemName.data();
	}

	char * SetName(char * NewName)
	{
		m_ItemName = NewName;
	}

	virtual ~DataItem () {};
private:
	string m_ItemName;
};

// A Leaf in a data tree.
class NameValueData:
	public DataItem
{
public:
	NameValueData(char * ItemName = NULL): DataItem(ItemName) {};

	NameValueData(char * ItemName, char * ItemValue) 
		: DataItem (ItemName)
	{
		m_ItemValue = ItemValue;
	}

	char * Value ()
	{
		return (char *) m_ItemValue.data();;
	}

	void SetValue (char * NewValue)
	{
		m_ItemValue = NewValue;
	}

	~NameValueData() {};

private:
	string m_ItemValue;
};

typedef vector<DataItem *>::iterator ListIterator;


// A list of items, forks out data tree
class DataList:
	public DataItem
{
public:
	DataList (char * ItemName = NULL): DataItem(ItemName) {};

	long ItemCount ()
	{
		return m_ItemList.size();
	}
	
	void Add(DataItem * NewItem)
	{
		m_ItemList.push_back(NewItem);
	}

	DataItem * GetItem (long ItemIndex) 
	{
		if (ItemIndex < m_ItemList.size()) {

			return m_ItemList[ItemIndex];
		}

		return NULL;
	}

	void ClearList() 
	{
		m_ItemList.clear();
	}

	~DataList()
	{
		ListIterator ListIT;

		for (ListIT = m_ItemList.begin(); ListIT != m_ItemList.end(); ListIT ++)
			delete (* ListIT);
		
		m_ItemList.clear();
	}

private:
	
	vector<DataItem *> m_ItemList;

};

class NVDataItem
{
public:
	NVDataItem(const char * ItemTag): m_ItemTag(ItemTag) {};

	long GetItemTag(const char ** ReturnName)
	{
		*ReturnName = m_ItemTag.data();
		return 1;
	}

	void SetItemTag (const char *NewTag) { m_ItemTag = NewTag; };

	inline long NVCount() { return m_NVList.size(); };

	long GetNVItem(long Index, const char ** RetItemName, const char **RetItemValue)
	{
		if (Index > NVCount()) {
			*RetItemName = *RetItemValue = NULL;
			return 0;
		}

		*RetItemName = m_NVList[Index].m_Name.data();
		*RetItemValue = m_NVList[Index].m_Value.data();

		return 1;
	};

	long GetValueByName (const char * ItemName,  const char ** RetItemValue)
	{
		map<string, long>::iterator NVMapIT;

		NVMapIT = m_NVMap.find(ItemName);
		if (NVMapIT == m_NVMap.end()) {
			*RetItemValue = NULL;
			return 0;
		}

		*RetItemValue = m_NVList[(*NVMapIT).second].m_Value.c_str();

		return 1;
	};
	
	long AddNVItem(char * ItemName, const char * ItemValue)
	{
		m_NVMap[ItemName] = m_NVList.size();
		m_NVList.push_back(NVItem(ItemName, ItemValue));
		return 1;
	};

	long AddNVItem(char * ItemName, wchar_t * ItemValue)
	{
		char * cItemValue = WStringToCString(ItemValue);
		long retVal = AddNVItem(ItemName, cItemValue);
		delete [] cItemValue;
		return retVal;
	};
	
	long AddNVItem( wchar_t * ItemName, wchar_t * ItemValue)
	{
		char * cItemName = WStringToCString(ItemName);
		char * cItemValue = WStringToCString(ItemValue);
		long retVal = AddNVItem(cItemName, cItemValue);
		delete [] cItemName;
		delete [] cItemValue;
		return retVal;
	};

	long DeletebyName ( const char * ItemName)
	{
		map<string, long>::iterator NVMapIT;
		
		// Find Ordinal
		NVMapIT = m_NVMap.find(ItemName);
		
		if (NVMapIT == m_NVMap.end())
			return 1;

		long FoundIndex = NVMapIT->second;

		// now delete in the list using ordinal
		vector<NVItem>::iterator FoundItem;
		FoundItem =  m_NVList.begin() + FoundIndex;
		m_NVList.erase(FoundItem);

		// Erase from name map
		m_NVMap.erase(NVMapIT);

		// Now adjust all pointers in the map;
		for (NVMapIT = m_NVMap.begin(); NVMapIT != m_NVMap.end(); NVMapIT++) {
			if (NVMapIT->second > FoundIndex) {
				(NVMapIT->second) --;
			}
		}

		return 0;
	};

	inline long SubItemCount() { return m_SubItemList.size(); }

	long GetSubItem (long index, NVDataItem * * ReturnItem)
	{
		if (index > SubItemCount())
			return 0;

		*ReturnItem = m_SubItemList[index];

		return 1;
	};

	long AddSubItem (NVDataItem * NewSubItem) 
	{
		m_SubItemList.push_back (NewSubItem);
		return 1;
	};

private:
	string m_ItemTag;
	string m_ItemText;

	typedef struct NVItem_t {
		string m_Name;
		string m_Value;

		NVItem_t(const char * Name, const char * Value): m_Name(Name), m_Value(Value){};
	} NVItem;

	vector <NVItem> m_NVList;
	map <string, long> m_NVMap;
	vector <NVDataItem *> m_SubItemList;
};

#pragma warning (pop)

#endif