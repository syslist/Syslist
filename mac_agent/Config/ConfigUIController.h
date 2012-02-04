/* ConfigUIController */

#import <Cocoa/Cocoa.h>

@interface ConfigUIController : NSObject
{
    IBOutlet NSButton *btnInventory;
    IBOutlet NSButton *btnOK;
    IBOutlet NSTextField *lblFrequency;
    IBOutlet NSTextField *lblPCStatus;
    IBOutlet NSTextField *lblProxyMode;
    IBOutlet NSTextField *lblProxyPort;
    IBOutlet NSTextField *lblProxyServer;
    IBOutlet NSTextField *lblServerPort;
    IBOutlet NSTextField *lblSyslistCode;
    IBOutlet NSTextField *lblSyslistPwd;
    IBOutlet NSTextField *lblSyslistServer;
    IBOutlet NSTextField *lblSyslistUser;
    IBOutlet NSTextField *lblVersionText;
    IBOutlet NSPopUpButton *popFrequency;
    IBOutlet NSPopUpButton *popProxyMode;
    IBOutlet NSTextField *txtPCStatus;
    IBOutlet NSTextField *txtProxyPort;
    IBOutlet NSTextField *txtProxyServer;
    IBOutlet NSTextField *txtServerPort;
    IBOutlet NSTextField *txtSyslistCode;
    IBOutlet NSSecureTextField *txtSyslistPwd;
    IBOutlet NSTextField *txtSyslistServer;
    IBOutlet NSTextField *txtSyslistUser;
}

- (IBAction)btnCancelPushed:(id)sender;
- (IBAction)btnInventoryPushed:(id)sender;
- (IBAction)btnOKPushed:(id)sender;
- (IBAction)popProxyChanged:(id)sender;

- (BOOL) applicationShouldTerminateAfterLastWindowClosed:(NSApplication *)theApplication;
- (void) awakeFromNib;

- (void) fillUIFromPrefs:(BOOL) resync;
- (void) fillPrefsFromUI:(BOOL) resync;
- (BOOL) canExecuteOK;
- (void) setDependentUIItems;

- (long) ExecuteCollector;
- (long) showBrowser;

- (void) controlTextDidChange:(NSNotification *)aNotification;
@end
