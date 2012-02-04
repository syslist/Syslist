#include "ProcessorCollector.h"
#include <sys/sysctl.h>
#include <mach/mach.h>
#include <mach/mach_host.h>
#include <mach/host_info.h>
#include <mach/machine.h>
#include <MacUtil/CFUtil.h>
#include <sstream>

#ifndef CPU_SUBTYPE_POWERPC_970
#define CPU_SUBTYPE_POWERPC_970 ((cpu_subtype_t) 100)
#endif

#if 0
/*
 *	CPU families (sysctl hw.cpufamily)
 *
 * These are meant to identify the CPU's marketing name - an
 * application can map these to (possibly) localized strings.
 * NB: the encodings of the CPU families are intentionally arbitrary.
 * There is no ordering, and you should never try to deduce whether
 * or not some feature is available based on the family.
 * Use feature flags (eg, hw.optional.altivec) to test for optional
 * functionality.
 */
#define CPUFAMILY_UNKNOWN    0
#define CPUFAMILY_POWERPC_G3 0xcee41549
#define CPUFAMILY_POWERPC_G4 0x77c184ae
#define CPUFAMILY_POWERPC_G5 0xed76d8aa
#define CPUFAMILY_INTEL_6_13 0xaa33392b
#define CPUFAMILY_INTEL_6_14 0x73d67300  /* "Intel Core Solo" and "Intel Core Duo" (32-bit Pentium-M with SSE3) */
#define CPUFAMILY_INTEL_6_15 0x426f69ef  /* "Intel Core 2 Duo" */
#define CPUFAMILY_INTEL_6_23 0x78ea4fbc  /* Penryn */
#define CPUFAMILY_INTEL_6_26 0x6b5a4cd2  /* Nehalem */
#define CPUFAMILY_ARM_9      0xe73283ae
#define CPUFAMILY_ARM_11     0x8ff620d8

#define CPUFAMILY_INTEL_YONAH	CPUFAMILY_INTEL_6_14
#define CPUFAMILY_INTEL_MEROM	CPUFAMILY_INTEL_6_15
#define CPUFAMILY_INTEL_PENRYN	CPUFAMILY_INTEL_6_23
#define CPUFAMILY_INTEL_NEHALEM	CPUFAMILY_INTEL_6_26

#define CPUFAMILY_INTEL_CORE	CPUFAMILY_INTEL_6_14
#define CPUFAMILY_INTEL_CORE2	CPUFAMILY_INTEL_6_15

#endif

static const char* PCL_TAG = "ProcessorList";
static const char* PC_TAG = "CPU";
static const char* PC_PROC_COUNT = "Count";

//static const char * PC_VENDOR = "Vendor";
static const char * PC_NAME = "Name";
static const char * PC_DESC = "ID";
static const char * PC_SPEED = "Speed";
static const char * PC_RAWSPEED = "RawSpeed";


long ProcessorCollector::GetCPUNames (long MainType, long SubType, 
	std::string & ReturnName, std::string & ReturnDesc)
{
	ostringstream DescStream;
	
	switch (MainType & 0x00FFFFFF) {
	
	case CPU_TYPE_POWERPC:
		DescStream << "PowerPC ";
	
		switch (SubType) {
		
		case CPU_SUBTYPE_POWERPC_601:
			ReturnName = "PowerPC 601";
			break;
		
		case CPU_SUBTYPE_POWERPC_602:
			ReturnName = "PowerPC 601";
			break;
		
		case CPU_SUBTYPE_POWERPC_603:
			ReturnName = "PowerPC 603 (G3)";
			break;
		
		case CPU_SUBTYPE_POWERPC_603e:
			ReturnName = "PowerPC 603e (G3)";
			break;
			
		case CPU_SUBTYPE_POWERPC_604:
			ReturnName = "PowerPC 604 (G3)";
			break;
		
		case CPU_SUBTYPE_POWERPC_7400:
			ReturnName = "PowerPC 7400 (G4)";
			break;
			
		case CPU_SUBTYPE_POWERPC_7450:
			ReturnName = "PowerPC 7450 (G4)";
			break;
			
		case CPU_SUBTYPE_POWERPC_750:
			ReturnName = "PowerPC 750 (G4)";
			break;
			
		case CPU_SUBTYPE_POWERPC_970:
			ReturnName = "PowerPC 970 (G5)";
			break;
		
		default:
			ReturnName = "PowerPC Unknown Type";
			break;
		}
		
		break;
#ifndef __ppc__
    case CPU_TYPE_X86:
        DescStream << "x86 Intel";
        break;
#endif

	default:
		DescStream << "Unknown ";
		ReturnName = "Unknown Type";
		break;
	}
	
	DescStream << MainType << "," << SubType;
	
	ReturnDesc = DescStream.str();

	return ERROR_SUCCESS;
}

long ProcessorCollector::Collect(NVDataItem ** ReturnItem)
{
	auto_ptr<NVDataItem> ProcessorData(new NVDataItem (PCL_TAG));
	
	long Status;
		
	ostringstream NumberConv;
	
	host_basic_info_data_t hostInfo;
	mach_msg_type_number_t infoCount;

	infoCount = HOST_BASIC_INFO_COUNT;
	host_info(mach_host_self(), HOST_BASIC_INFO, 
		(host_info_t)&hostInfo, &infoCount);
	
	std::string CPUDesc;
	std::string CPUName;

    size_t cpuNameSize = 1024;
    char cpuNameBuf[1024];
    
    Status = sysctlbyname("machdep.cpu.brand_string", 
                cpuNameBuf, &cpuNameSize, NULL, 0);
    
    if (Status == ERROR_SUCCESS) {
        CPUName.assign(cpuNameBuf, cpuNameSize);
    }
    else {
        GetCPUNames(hostInfo.cpu_type, hostInfo.cpu_subtype, CPUName, CPUDesc);
    }
    
	NumberConv << hostInfo.max_cpus;
	
	ProcessorData->AddNVItem(PC_PROC_COUNT, NumberConv.str().c_str());	
		
	unsigned long ProcSpeed;
	unsigned long RawProcSpeed;
	
	int SearchMIB[2];
	size_t DataSize;

	SearchMIB[0] = CTL_HW;	
	SearchMIB[1] = HW_CPU_FREQ;
	DataSize = sizeof(ProcSpeed);	
	
	Status = sysctl(SearchMIB, 2, &ProcSpeed, &DataSize, NULL, 0);
	if (Status != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;
	
	ProcSpeed /= 1000000; // divide by a million to get to Mhz
	RawProcSpeed = ProcSpeed;
	
	long RoundFactor  = 1;
	if (ProcSpeed > 1000)
		RoundFactor = 10;
	else if (ProcSpeed > 100)
		RoundFactor = 5;
	
	ProcSpeed = (((ProcSpeed + (RoundFactor/2)) / RoundFactor)) * RoundFactor;
	
	ostringstream ProcSpeedConv;
	ProcSpeedConv << ProcSpeed;
	
	ostringstream RawProcSpeedConv;
	RawProcSpeedConv << RawProcSpeed;
	
	///////////////////////////////////////////////////////////
	// 
	// Formatting for compatibility with orginal version 
	// - all data is the same though!
	//
	long ProcessorIndex;
	
	for (ProcessorIndex = 0; ProcessorIndex < hostInfo.max_cpus; ProcessorIndex ++){
	
		auto_ptr<NVDataItem> CurrProcessor (new NVDataItem(PC_TAG));
		
		CurrProcessor->AddNVItem(PC_NAME, CPUName.c_str());
		CurrProcessor->AddNVItem(PC_DESC, CPUDesc.c_str());
		CurrProcessor->AddNVItem(PC_SPEED, ProcSpeedConv.str().c_str());
		CurrProcessor->AddNVItem(PC_RAWSPEED, RawProcSpeedConv.str().c_str());
		
		ProcessorData->AddSubItem(CurrProcessor.release());
	}
	
	*ReturnItem = ProcessorData.release();
#ifdef TRACE
	printf("processor Complete\n");
#endif	
	return ERROR_SUCCESS;
}
	
