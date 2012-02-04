#ifndef PRINTER_COLLECT_H_INCLUDED
#define PRINTER_COLLECT_H_INCLUDED

#include "CollectProto.h"
#include "WMIUtil.h"
#include "WinSetupUtil.h"

class PrinterCollector:
	public AutoCreateDataCollector<PrinterCollector>,
	public WMIUtilLocal<PrinterCollector>,
	public WinSetupClientLocal<PrinterCollector>
{
public:
	PrinterCollector() 
	{
	}

	long CollectFallback(NVDataItem *DataItems);
	long PreferredCollect(NVDataItem *DataItems);
	long Collect(NVDataItem ** ReturnItem);
	virtual ~PrinterCollector() {};

private:
	long CollectSinglePrinter (IWbemClassObject * WMIPrinter, NVDataItem *TargetItem, bool * KeepItem  );


private:

};
#endif
