#ifndef KEY_DECODE_H_INCLUDED
#define KEY_DECODE_H_INCLUDED

const int kDecodeKeyLen = 25;

extern long DecodeMSKeyReg(char *dest, char *source);
extern long DecodeMSKey(char *dest, char *source);

#endif