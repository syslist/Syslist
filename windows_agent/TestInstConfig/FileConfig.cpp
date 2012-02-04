/*
 *  FileConfig.cpp
 *  SyslistAgentXCode3
 *
 *  Created by Karl Holland on 1/26/08.
 *  Copyright 2008 __MyCompanyName__. All rights reserved.
 *
 */

#include "FileConfig.h"
#include "sys/stat.h"
#include "openssl/evp.h"
#include <vector>
#include <fstream>

using namespace std;

const int CONFIG_SIZE = sizeof(FileConfig);
const unsigned long MAX_CONF_SIZE = 65536;

const unsigned char kSLCryptKey[] = "ToroAlphaToroBetaToroOmegaEqualsUnmatched";
const unsigned char kSLCryptIV[] = "QuantumIota";


int ReadFileConf(char * confPath, FileConfig * dest)
{
    struct stat fileInfo;
    if (stat(confPath, & fileInfo)) 
        return 1;
    
    unsigned long fSize = fileInfo.st_size;
    
    if (fSize == 0 || fSize > MAX_CONF_SIZE)
        return 2;
        
    ifstream inputFile(confPath, ios::binary);
        
    if (!inputFile || inputFile.eof())
        return 1;
        
    // sneaky way to get auto-destroy char buffers
    vector<unsigned char> cryptBuf(fSize);
    unsigned char * cryptBufPtr = &(cryptBuf[0]);
    vector<unsigned char> plainBuf(fSize);
    unsigned char * plainBufPtr = &(plainBuf[0]);
    
    if (cryptBufPtr == NULL || plainBufPtr == NULL)
        return 1;

    if (!inputFile.read((char *) cryptBufPtr, fSize))
        return 1;
            
    EVP_CIPHER_CTX ctx;
    EVP_CIPHER_CTX_init(&ctx);
    EVP_DecryptInit(&ctx, EVP_bf_cbc(), 
        const_cast<unsigned char *>(kSLCryptKey), 
        const_cast<unsigned char *>(kSLCryptIV));

    int RealPlainDataLen = 0;
    
    if(!EVP_DecryptUpdate(&ctx, plainBufPtr, &RealPlainDataLen,
        cryptBufPtr, fSize)) {
        return 1;
    }
        
    /* Buffer passed to EVP_EncryptFinal() must be after data just
     * encrypted to avoid overwriting it.
     */
    int CleanupLen = 0;
    
    if(!EVP_DecryptFinal(&ctx, plainBufPtr + RealPlainDataLen,
        &CleanupLen)) {
        return 1;
    }        
        
    RealPlainDataLen += CleanupLen;
    
    if (RealPlainDataLen != CONFIG_SIZE)
        return 1;
        
    memcpy(dest, plainBufPtr, CONFIG_SIZE);
    
    return 0;
}

int WriteFileConf(char * confPath, FileConfig * source)
{
    EVP_CIPHER_CTX ctx;
    EVP_CIPHER_CTX_init(&ctx);
        
    if (!EVP_EncryptInit(&ctx, EVP_bf_cbc(), 
            const_cast<unsigned char *> (kSLCryptKey), 
            const_cast<unsigned char *> (kSLCryptIV)))
        return 1;

    long PossibleCryptDataLen = CONFIG_SIZE 
            + (EVP_CIPHER_CTX_block_size(&ctx) * 2);
            
    vector<unsigned char> cryptData(PossibleCryptDataLen);
    unsigned char * cryptDataPtr = &(cryptData[0]);

    int RealCryptDataLen = 0;
    
    if(!EVP_EncryptUpdate(&ctx, cryptDataPtr, &RealCryptDataLen,
            (unsigned char *) source, CONFIG_SIZE))
        return 1;
        
    /* Buffer passed to EVP_EncryptFinal() must be after data just
     * encrypted to avoid overwriting it.
     */
    int CleanupLen = 0;
    
    if(!EVP_EncryptFinal(&ctx, cryptDataPtr + RealCryptDataLen, &CleanupLen))
        return 1;
        
    RealCryptDataLen += CleanupLen;
    
    ofstream destFile(confPath, ios::binary);
    
    if (!destFile.write((char *) cryptDataPtr, RealCryptDataLen))
        return 1;
    
    return 0;
}

