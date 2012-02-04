--- License Information ---

Please read 'license.txt', which contains the terms and conditions that you must abide by as a purchaser of Syslist.

Your license key will permit you to inventory a certain number of systems. Once you exceed this number, you will no longer be able to manually add systems to the database, and the Syslist Companion Agent (which automatically inventories Windows PCs) will fail to create new systems in the database. To remedy this, you can purchase an additional license, or manually delete systems from Syslist to bring yourself back below the allowed limit. If you choose to purchase an additional license, please contact sales@syslist.com.


--- Installation ---


1) Edit Includes\db.inc.php to utilize the correct hostname, database name, username, and password. 

2) Edit Includes\global.inc.php to alter Syslist's global settings and preferences.

3) Use dump.sql to recreate the database structure for Syslist ("phpMyAdmin", a free and open-source mysql database management utility, is very useful for this and other tasks. It can be downloaded from: http://www.phpmyadmin.net)

4) Make sure your PHP installation meets the following requirements:

  - You must be running PHP version 4.2.1 or greater. If you are not, please upgrade.  
  - short_open_tag must be enabled in PHP.
  - Dynamic Library Support must be enabled in PHP.
  - magic_quotes_gpc must be enabled in PHP.

5) Change the permissions on the Images\Systems and Images\Users folders to 667 (i.e. in Linux: 'chmod 667 Images/Systems')

6) If you wish to import users into Syslist from an LDAP database, you must enable LDAP support in PHP. You will need to use the --with-ldap[=DIR] configuration option when compiling PHP. DIR is the LDAP base install directory.
 
7) Finally, log into Syslist (default username is "admin", password is "password"). Once logged in, you will automatically be forwarded to the "profile" page - please input a valid email address and password for yourself at that time. Do not skip this step, otherwise important system emails may be lost in the future.

8) If Syslist does not function correctly at this point, the problem might be caused by Ioncube (the source encoding technology used by Syslist). If so, see http://your_syslist_site/ioncube/ioncube-install-assistant.php for instructions. If that fails to help resolve the problem, please refer to http://www.ioncube.com/faq.php#ts1 for assistance.

Windows Server users, please continue reading below. Otherwise, that completes the installation. We recommend that you now read 'instructions.doc', which pertains to the automatic inventory funcionality in Syslist. Thank you for your business! 

Sincerely,
Syslist Support
Email: support@syslist.com


--- WINDOWS SERVER USERS, PLEASE NOTE ---


You may need to make some or all of the changes below to get Syslist to function correctly:

1) Rename the php.ini.dis to php.ini

2) Change session.save_path to point to a temp directory that is world-writeable (total control to group "everyone"). In Windows, session_start() will not work unless you change this.

3) If you wish to import users into Syslist from an LDAP database, you must copy several files from the DLL folder of the PHP/Win32 binary package to the SYSTEM folder of your windows machine. (Ex: C:\WINNT\SYSTEM32, or C:\WINDOWS\SYSTEM). For PHP <= 4.2.0 copy libsasl.dll, for PHP >= 4.3.0 copy libeay32.dll and ssleay32.dll to your SYSTEM folder. Then to enable the module, remove the semicolon from the line in your php.ini file that reads ";extension=php_ldap.dll" so that it now reads "extension=php_ldap.dll"

4) Servers that use safe mode, or have PHP built with thread support, must install Ioncube in the PHP.ini. See http://your_syslist_site/ioncube/ioncube-install-assistant.php for instructions.

5) If Syslist does not function correctly at this point, find error reporting in your php.ini and change it from E_ALL to E_ERROR.
