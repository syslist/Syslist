
#ifndef CONFIG_TASK_SCHED_H_INCLUDED
#define CONFIG_TASK_SCHED_H_INCLUDED

#include "SyslistMethod.h"

extern long StartLocalTaskSched();
extern long ConfigCollectorTask(CmdMethodIndex MethodChoice, char * MachUser, char * MachPwd, char * InstallPath);
extern long SchedError;
extern long DestroyCollectorTask();

#endif