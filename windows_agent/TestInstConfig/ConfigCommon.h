#ifndef CONFIG_COMMON_H_INCLUDED
#define CONFIG_COMMON_H_INCLUDED

#include <queue>
#include <map>
#include <string>

using namespace std;

typedef queue<string> CommandLineQueue;
typedef long (CommandHandler) (CommandLineQueue &CmdQueue);
typedef CommandHandler * pCommandHandler;

typedef struct CommandLineItem {
	char * Command;
	pCommandHandler Handler;
} CommandLineItem_t;



#endif
