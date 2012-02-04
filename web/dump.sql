



CREATE TABLE comments (
  commentID int(11) NOT NULL auto_increment,
  subjectID int(11) default NULL,
  authorID int(11) NOT NULL,
  assignedUserID int(11) default NULL,
  subjectType enum('h','p','s','u') CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  commentDate datetime default NULL,
  commentText text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  commentPriority enum('1','2','3','4','5') default NULL,
  commentStatus enum('Open','In Progress','Resolved') CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  identityCode varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  categoryID int(11) default NULL,
  commentLocationID int(11) default NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY (commentID),
  KEY subjectID (subjectID),
  KEY authorID (authorID),
  KEY assignedUserID (assignedUserID),
  KEY categoryID (categoryID),
  KEY commentLocationID (commentLocationID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE commentCategories (
  categoryID int(11) NOT NULL auto_increment,
  categoryName varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY (categoryID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE locations (
  locationID int(11) NOT NULL AUTO_INCREMENT,
  locationCode varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationName varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationAddress1 varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationAddress2 varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationCity varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationState char(2) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationZip varchar(9) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationCountry char(3) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationPhone varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationPhoneCode varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationPhoneExt varchar(6) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  accountID int(11) DEFAULT '1',
  PRIMARY KEY (locationID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE hardware (
  hardwareID int(11) NOT NULL auto_increment,
  hardwareTypeID int(11) NOT NULL,
  hostname varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  locationID int(11) default NULL,
  roomName varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  serial varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  userID int(11) default NULL,
  sparePart enum('3','2','1','0') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '0',
  hardwareStatus enum('n','i','w') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default 'w',
  ipAddress varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  assetTag varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  nicMac1 varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  nicMac2 varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  lastManualUpdate date default NULL,
  lastManualUpdateBy int(11) default NULL,
  lastAgentUpdate date default NULL,
  dueDate date default NULL,
  purchaseDate date default NULL,
  purchasePrice decimal(10,2) unsigned default NULL,
  picURL varchar(150) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  warrantyEndDate date default NULL,
  other1 varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  vendorID int(11) default NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (hardwareID),
  KEY hardwareTypeID (hardwareTypeID),
  KEY locationID (locationID),
  KEY userID (userID),
  KEY sparePart (sparePart),
  KEY hardwareStatus (hardwareStatus),
  KEY accountID (accountID)
) TYPE=MyISAM;


CREATE TABLE hardware_types (
  hardwareTypeID int(11) NOT NULL auto_increment,
  description varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  visDescription varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  manufacturer varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  visManufacturer varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  notes TEXT CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  universalVendorID int(11) default NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (hardwareTypeID),
  KEY description (description),
  KEY visDescription (visDescription),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE hardware_type_defaults (
  hardwareTypeDefaultID int(11) NOT NULL auto_increment,
  hardwareTypeID int(11) NOT NULL,
  objectID int(11) NOT NULL,
  objectType enum('p','s') NOT NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (hardwareTypeDefaultID),
  KEY hardwareTypeID (hardwareTypeID),
  KEY objectID (objectID),
  KEY accountID (accountID)
) TYPE=MyISAM;


CREATE TABLE peripheral_traits (
  peripheralTraitID int(11) NOT NULL auto_increment,
  visManufacturer varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  visModel varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  visDescription varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  visTypeClass enum('processor', 'opticalStorage', 'diskStorage', 'netAdapter', 'keyboard', 'pointingDevice', 'printer', 'displayAdaptor', 'RAM', 'soundCard', 'monitor') CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  hidden enum('1','0') NOT NULL default '0',
  preserve enum('1','0') NOT NULL default '0',
  notify enum('1','0') NOT NULL default '0',
  universalVendorID int(11) default NULL,
  accountID int(11) NOT NULL default '1',
  notes text CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  PRIMARY KEY  (peripheralTraitID),
  KEY visDescription (visDescription),
  KEY visTypeClass (visTypeClass),
  KEY accountID (accountID)
) TYPE=MyISAM;


CREATE TABLE peripheral_types (
  peripheralTypeID int(11) NOT NULL auto_increment,
  manufacturer varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  model varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  description varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  typeClass enum('processor', 'opticalStorage', 'diskStorage', 'netAdapter', 'keyboard', 'pointingDevice', 'printer', 'displayAdaptor', 'RAM', 'soundCard', 'monitor') CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  peripheralTraitID int(11) NOT NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (peripheralTypeID),
  KEY description (description),
  KEY peripheralTraitID (peripheralTraitID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE peripherals (
  peripheralID int(11) NOT NULL auto_increment,
  hardwareID int(11) default NULL,
  serial varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  peripheralTypeID int(11) default NULL,
  peripheralTraitID int(11) NOT NULL,
  sparePart enum('1','0') NOT NULL default '0',
  peripheralStatus enum('n','i','w','d') NOT NULL default 'w',
  locationID int(11) default NULL,
  roomName varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  hidden enum('1','0') NOT NULL default '0',
  vendorID int(11) default NULL,
  macAddress varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  purchaseDate date default NULL,
  purchasePrice decimal(10,2) unsigned default NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (peripheralID),
  KEY hardwareID (hardwareID),
  KEY peripheralTypeID (peripheralTypeID),
  KEY peripheralTraitID (peripheralTraitID),
  KEY locationID (locationID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE peripheral_actions (
  peripheralActionID int(11) NOT NULL auto_increment,
  peripheralTraitID int(11) NOT NULL,
  hardwareID int(11) default NULL,
  actionType enum('agentDel','userDel','userMove') NOT NULL,
  actionDate datetime NOT NULL,
  userID int(11) default NULL,
  movedToID int(11) default NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (peripheralActionID),
  KEY peripheralTraitID (peripheralTraitID),
  KEY hardwareID (hardwareID),
  KEY userID (userID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE software (
  softwareID int(11) NOT NULL auto_increment,
  serial varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  hardwareID int(11) default NULL,
  softwareTypeID int(11) default NULL,
  softwareTraitID int(11) NOT NULL,
  sparePart enum('1','0') NOT NULL default '0',
  locationID int(11) default NULL,
  roomName varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  hidden enum('2','1','0') NOT NULL default '0',
  vendorID int(11) default NULL,
  creationDate date NOT NULL default '0000-00-00',
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (softwareID),
  KEY hardwareID (hardwareID),
  KEY softwareTypeID (softwareTypeID),
  KEY softwareTraitID (softwareTraitID),
  KEY locationID (locationID),
  KEY accountID (accountID)
) TYPE=MyISAM;


CREATE TABLE software_licenses (
  licenseID int(11) NOT NULL auto_increment,
  licenseType enum('peruser','persystem') NOT NULL,
  numLicenses int(11) NOT NULL,
  pricePerLicense decimal(10,2) unsigned default NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (licenseID),
  KEY accountID (accountID)
) TYPE=MyISAM;


CREATE TABLE software_traits (
  softwareTraitID int(11) NOT NULL auto_increment,
  visName varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  visMaker varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  visVersion varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  canBeMoved enum('1','0') NOT NULL default '1',
  operatingSystem enum('0','1') NOT NULL default '0',
  universalSerial varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  hidden enum('1','0') NOT NULL default '0',
  installNotify enum('1','0') NOT NULL default '0',
  uninstallNotify enum('1','0') NOT NULL default '0',
  universalVendorID int(11) default NULL,
  isBanned enum('1','0') NOT NULL default '0',
  bannedReason text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  accountID int(11) NOT NULL default '1',
  notes text default NULL,
  PRIMARY KEY  (softwareTraitID),
  KEY visName (visName),
  KEY accountID (accountID)
) TYPE=MyISAM;


CREATE TABLE software_traits_licenses (
  softTraitLicID int(11) NOT NULL auto_increment,
  licenseID int(11) NOT NULL,
  softwareTraitID int(11) NOT NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (softTraitLicID),
  KEY licenseID (licenseID),
  KEY softwareTraitID (softwareTraitID),
  KEY accountID (accountID)
) TYPE=MyISAM;


CREATE TABLE software_types (
  softwareTypeID int(11) NOT NULL auto_increment,
  Name varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  Maker varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  Version varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  softwareTraitID int(11) NOT NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (softwareTypeID),
  KEY Name (Name),
  KEY softwareTraitID (softwareTraitID),
  KEY accountID (accountID)
) TYPE=MyISAM;


CREATE TABLE tblSecurity (
  id int(11) NOT NULL auto_increment,
  userID varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  password varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  firstName varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  middleInit char(1) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  lastName varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  email varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  securityLevel tinyint(4) NOT NULL,
  lastLogin datetime default NULL,
  userLocationID int(11) default NULL,
  picURL varchar(150) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  hidden enum('1','0') NOT NULL default '0',
  stuckAtLocation enum('1','0') NOT NULL default '0',
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (id),
  KEY lastName (lastName),
  KEY userLocationID (userLocationID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE faq_content (
  faqContentID int(11) NOT NULL auto_increment,
  faqCatID int(11) NOT NULL,
  faqQuestion varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  faqAnswer text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (faqContentID),
  KEY faqCatID (faqCatID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE faq_categories (
  faqCatID int(11) NOT NULL auto_increment,
  faqCatName varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (faqCatID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE account_settings (
  accountSettingsID int(11) NOT NULL auto_increment,
  accountID int(11) NOT NULL,
  primaryHelpdeskUserID int(11) NOT NULL,
  systemAlertUserID int(11) NOT NULL,
  ccTicketCreate enum('0','1') NOT NULL default '0',
  ccTicketUpdate enum('0','1') NOT NULL default '0',
  alertSoftwareTypeCreate enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (accountSettingsID),
  KEY accountID (accountID),
  KEY primaryHelpdeskUserID (primaryHelpdeskUserID),
  KEY systemAlertUserID (systemAlertUserID)
) TYPE=MyISAM;

CREATE TABLE ip_history (
  ipHistoryID int(11) NOT NULL auto_increment, 
  ipAddress varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  hardwareID int(11) NOT NULL,
  firstReportedDate datetime NOT NULL,
  accountID int(11) NOT NULL,
  PRIMARY KEY (ipHistoryID)
) TYPE=MyISAM;

CREATE TABLE vendors (
  vendorID int(11) NOT NULL AUTO_INCREMENT,
  vendorName varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  vendorAddress1 varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  vendorAddress2 varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  vendorCity varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  vendorState char(2) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  vendorZip varchar(9) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  vendorCountry char(3) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  vendorPhone varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  vendorPhoneCode varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  vendorPhoneExt varchar(6) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  contractInfo text CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  notes text CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  accountID int(11),
  PRIMARY KEY (vendorID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE software_actions (
  softwareActionID int(11) NOT NULL auto_increment,
  softwareTraitID int(11) NOT NULL default '0',
  hardwareID int(11) default NULL,
  actionType enum('agentDel','userDel','userMove') NOT NULL default 'agentDel',
  actionDate datetime NOT NULL default '0000-00-00 00:00:00',
  userID int(11) default NULL,
  movedToID int(11) default NULL,
  accountID int(11) NOT NULL default '1',
  PRIMARY KEY  (softwareActionID),
  KEY peripheralTraitID (softwareTraitID),
  KEY hardwareID (hardwareID),
  KEY userID (userID),
  KEY accountID (accountID)
) TYPE=MyISAM;

CREATE TABLE logicaldisks (
  logicalDiskID mediumint(11) NOT NULL auto_increment,
  hardwareID mediumint(11) NOT NULL,
  name varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  volumeName varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  fileSystem varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  size varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  freeSpace varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci default NULL,
  accountID int(11) NOT NULL,
  PRIMARY KEY  (logicalDiskID),
  KEY hardwareID (hardwareID),
  KEY accountID (accountID)
) TYPE=MyISAM;

INSERT INTO tblSecurity VALUES (1, 'admin', '5f4dcc3b5aa765d61d8327deb882cf99', 'First', NULL, 'Last', 'name@address.com', 0, '2002-08-15 12:36:58', NULL, NULL, '0', '0', 1);
INSERT INTO locations VALUES (1, NULL, 'Default Location', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1);
INSERT INTO account_settings (accountSettingsID, accountID, primaryHelpdeskUserID, systemAlertUserID) VALUES (1, 1, 1, 1);
