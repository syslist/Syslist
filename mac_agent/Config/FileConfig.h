/*
 *  FileConfig.h
 *  SyslistAgentXCode3
 *
 *  Created by Karl Holland on 1/26/08.
 *  Copyright 2008. All rights reserved.
 *
 */

#define CONF_STR_LEN 512
#define CONF_NUM_STR_LEN 32

typedef char ConfStr[CONF_STR_LEN];
typedef char ConfNum[CONF_NUM_STR_LEN];

struct FileConfig {
    ConfStr AcctName;
    ConfStr AcctPwd;
    ConfStr AcctCode;
    ConfStr ServerURL;
    ConfNum ServerPort;
    ConfStr ProxyServer;
    ConfNum ProxyPort;
    ConfStr ProxyMethod;
    ConfStr ScanFreq;
};

int ReadFileConf(char * confPath, FileConfig * dest);
int WriteFileConf(char * confPath, FileConfig * source);
