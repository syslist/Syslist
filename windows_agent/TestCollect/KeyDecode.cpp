#include "stdafx.h"

static char Digits[] = { 'B', 'C', 'D', 'F', 'G', 'H', 'J', 
						   'K', 'M', 'P', 'Q', 'R', 'T', 'V', 
						   'W', 'X', 'Y', '2', '3', '4', '6',
						   '7', '8', '9'};

const int kDecodeKeyLen = 25;
const int kBinKeyLen = 15;
const int kRegBinKeyStart = 0x34;

long DecodeMSKey(char *dest, char *source)
{

	int i,j,k;
	
	unsigned char WorkSrc[kBinKeyLen];
	memcpy(WorkSrc, source, kBinKeyLen);

	for (i = kDecodeKeyLen - 1; i >= 0 ; i--) {
		
		k = 0;
		
		for (j = kBinKeyLen - 1; j >= 0 ; j--) {

			k = (k << 8) + WorkSrc[j];
			WorkSrc[j] = (k / 24) & 0xFF;
			k = k % 24;
		}

		dest[i] = Digits[k];
	}

	dest[kDecodeKeyLen] = 0;

	return ERROR_SUCCESS;
}


long DecodeMSKeyReg(char *dest, char *source)
{
	return DecodeMSKey(dest, source + kRegBinKeyStart);
}