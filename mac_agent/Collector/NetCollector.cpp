#include <NetCollector.h>
#include <MacUtil/IOUtil.h>
#include <map>

#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <net/if_dl.h>
#include <arpa/inet.h>
#include <net/if.h>
#include <ifaddrs.h>

#include <sstream>
#include <iomanip>

static const char * NCL_TAG = "NetAdapterList";
static const char * NCI_TAG = "NetAdapter";

//static const char * NCI_TYPE = "Type";
static const char * NCI_MFR = "Manufacturer";
static const char * NCI_MODEL = "Description";
static const char * NCI_PRODUCT = "ProductName";
//static const char * NCI_CAPTION = "Caption";
static const char * NCI_MACADDR = "MACAddress";
static const char * NCI_IPADD = "IPAddress";
//static const char * NCI_IPSUBNET = "IPSubNet";

typedef  map<string, string> IFNameIPMap_t;

long CatalogIPAddresses(IFNameIPMap_t & AddrStringMap)
{
	long Status;
	
	struct ifaddrs* Interfaces = NULL;
	
	Status = getifaddrs(&Interfaces);
	if (Status != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;
	
	struct ifaddrs* CurrIntf = Interfaces;
	
	while (CurrIntf != NULL) {
	
		if ( !(CurrIntf->ifa_flags & IFF_LOOPBACK) 
			&& !(CurrIntf->ifa_flags & IFF_POINTOPOINT)
			&& (CurrIntf->ifa_addr->sa_family == AF_INET)) {
										
			struct sockaddr_in * InetData;
			InetData = reinterpret_cast<sockaddr_in *> (CurrIntf->ifa_addr);
			
			std::string AddrBuf(inet_ntoa(InetData->sin_addr));
			std::string IFNameBuf(CurrIntf->ifa_name);
			
			AddrStringMap[IFNameBuf] = AddrBuf;
		}
		
		CurrIntf = CurrIntf->ifa_next;
	}
	
	freeifaddrs(Interfaces);
#ifdef TRACE
	printf("Net Complete\n");
#endif	
	return ERROR_SUCCESS;
}

void ExtractItemPropString(IOServiceUtil & Item, char * QueryKey, const char * DataTag, NVDataItem * Target)
{
	CFAuto<CFStringRef> ItemCFProp;
		
	ItemCFProp.Attach((CFStringRef) Item.Query(QueryKey));
	if (ItemCFProp.IsEmpty()
		|| CFGetTypeID(ItemCFProp) != CFStringGetTypeID())
			return;
	
	std::string ItemPropString;
	ItemCFProp.GetCString(ItemPropString);
	
	if (ItemPropString.length() > 0)
		Target->AddNVItem(DataTag, ItemPropString.c_str());
}

void ExtractMACAddress (IOServiceUtil & Item, char * QueryKey, const char *DataTag, NVDataItem * Target)
{
	CFAuto<CFDataRef> ItemCFProp;
	long DataSize;
	
	ItemCFProp.Attach((CFDataRef) Item.Query(QueryKey));
	if (ItemCFProp.IsEmpty()
		|| CFGetTypeID(ItemCFProp) != CFDataGetTypeID())
		return;
		
	ostringstream MACBuffer;

	
	DataSize = CFDataGetLength(ItemCFProp);
	unsigned char * MACDataBuf;
	
	MACDataBuf = (unsigned char *) CFDataGetBytePtr(ItemCFProp);
	if (MACDataBuf == NULL)
		return;
		
	long CurrIndex;	
	for (CurrIndex = 0; CurrIndex < DataSize; CurrIndex ++) {
		
		if (CurrIndex != 0)
			MACBuffer << ":";
		
		MACBuffer << std::hex << std::setw(2) << std::setfill('0') << (unsigned long) MACDataBuf[CurrIndex];
	}
	
	Target->AddNVItem (DataTag, MACBuffer.str().c_str());
}

void FindIPAttr( IOServiceUtil & Item, IFNameIPMap_t & IFMap, NVDataItem * Target)
{
	if (!IOObjectConformsTo(Item,"IOEthernetInterface"))
		return;
			
	CFAuto<CFStringRef> BSDCFName;
	
	BSDCFName.Attach ((CFStringRef) Item.Query("BSD Name"));
	
	if (BSDCFName.IsEmpty() || CFGetTypeID (BSDCFName) != CFStringGetTypeID())
		return;
		
	std::string BSDName;
	BSDCFName.GetCString(BSDName);
	
	IFNameIPMap_t::iterator IFNameIT = IFMap.find (BSDName);
	
	if (IFNameIT == IFMap.end())
		return;
		
	Target->AddNVItem(NCI_IPADD, IFNameIT->second.c_str());
		
}

long NetCollector::Collect(NVDataItem ** ReturnItem)
{
	auto_ptr<NVDataItem> NetIntfList (new NVDataItem(NCL_TAG));
	
	long Status;
	IFNameIPMap_t LocalAddrStrings;
	
	Status = CatalogIPAddresses(LocalAddrStrings);
	if (Status != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;
		
	IOServiceListUtil NetInterfaces("IONetworkInterface");
	
	IOServiceUtil CurrIntf;
	
	while (CurrIntf.Attach(NetInterfaces.Next()), CurrIntf.IsEmpty() != true) {
	
		IOServiceUtil ParentController;
		kern_return_t ParentStatus;
		
		ParentStatus = IORegistryEntryGetParentEntry(CurrIntf, kIOServicePlane, & ParentController);
		if (ParentStatus != KERN_SUCCESS || ParentController.IsEmpty())
			continue;
		
		auto_ptr<NVDataItem> NetIntfItem (new NVDataItem(NCI_TAG));
					
		ExtractItemPropString(ParentController, "IOVendor", NCI_MFR, NetIntfItem.get());
		ExtractItemPropString(ParentController, "IOModel", NCI_MODEL, NetIntfItem.get());
		ExtractItemPropString(ParentController, "IOClass", NCI_PRODUCT, NetIntfItem.get());
		ExtractMACAddress (ParentController, "IOMACAddress", NCI_MACADDR, NetIntfItem.get());
		
		FindIPAttr(CurrIntf,LocalAddrStrings,NetIntfItem.get());

		if (NetIntfItem->NVCount() > 0)
			NetIntfList->AddSubItem (NetIntfItem.release());
	}
	
	*ReturnItem = NetIntfList.release();
	
	return ERROR_SUCCESS;
}


