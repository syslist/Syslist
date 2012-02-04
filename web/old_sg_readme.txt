--- License Information ---

Please read 'license.txt', which contains the terms and conditions that you must abide by as a purchaser of Syslist.

Your license key will permit you to inventory a certain number of systems. Once you exceed this number, you will no longer be able to manually add systems to the database, and the Syslist Companion Agent (which automatically inventories Windows PCs) will fail to create new systems in the database. To remedy this, you can purchase an additional license, or manually delete systems from Syslist to bring yourself back below the allowed limit. If you choose to purchase an additional license, please contact sales@syslist.com.


--- Installation ---


1) Edit Includes\db.inc.php to utilize the correct hostname, database name, username, and password. 

2) Edit Includes\global.inc.php to alter Syslist's global settings and preferences.

3) Use dump.sql to recreate the database structure for Syslist ("phpMyAdmin", a free and open-source mysql database management utility, is very useful for this and other tasks. It can be downloaded from: http://www.phpmyadmin.net)

4) Make sure your PHP installation meets the following requirements:

  - You must be running PHP version 4.2.1 or greater. If you are not, please upgrade.  
  - register_globals must be enabled (set to 'on') in PHP (php.ini).
  - Short tags must be enabled in PHP.
  - Dynamic Library Support must be enabled in PHP.

5) Change the permissions on the Images\Systems and Images\Users folders to 667 (i.e. in Linux: 'chmod 667 Images/Systems')

6) If you wish to import users into Syslist from an LDAP database, you must enable LDAP support in PHP. You will need to use the --with-ldap[=DIR] configuration option when compiling PHP. DIR is the LDAP base install directory.
 
7) Check the "ixed" folder to make sure that it contains a file that matches your server / version of PHP. The important part of the file name is the three letter server abbreviation (ie "lin" for "linux") and the version number. For example: "ixed.lin.4.2.2.pxp". If your version of PHP is so new that there is not a matching file in there for it,  you can always download the latest from: http://www.sourceguardian.com/ixeds

8) Finally, log into Syslist (default username is "admin", password is "password"). Once logged in, you will automatically be forwarded to the "profile" page - please input a valid email address and password for yourself at that time. Do not skip this step, otherwise important system emails may be lost in the future.

Windows Web Server users, please continue reading below. Otherwise, that completes the installation. We recommend that you now read 'instructions.doc', which pertains to the automatic inventory funcionality in Syslist. Thank you for your business! 

Sincerely,

Syslist Support (support@syslist.com)


--- WINDOWS WEB SERVER USERS, PLEASE NOTE ---


Syslist will not function correctly if you have not made all the changes listed below:

1) Rename the php.ini.dis to php.ini

2) Change register_globals to On

3) Change session.save_path to point to a temp directory that is world-writeable (total control to group "everyone"). In Windows, session_start() will not work unless you change this.

4) If you wish to import users into Syslist from an LDAP database, you must copy several files from the DLL folder of the PHP/Win32 binary package to the SYSTEM folder of your windows machine. (Ex: C:\WINNT\SYSTEM32, or C:\WINDOWS\SYSTEM). For PHP <= 4.2.0 copy libsasl.dll, for PHP >= 4.3.0 copy libeay32.dll and ssleay32.dll to your SYSTEM folder. 

Then to enable the module, remove the semicolon from the line in your php.ini file that reads ";extension=php_ldap.dll" so that it now reads "extension=php_ldap.dll"

5) Create a subdirectory called "ixed" in the folder where the php.exe is located. Then, look through the "ixed" directory included with Syslist to find the file that matches your version of PHP (this will look something like "ixed.win.4.2.1.pxp" - "win" standing for "windows"). Copy this file into the new "ixed" folder. If your version of PHP is older than version 4.2.1, you must upgrade it. If it is so new that the "ixed" folder does not have a matching file, you can always download the latest from: http://www.sourceguardian.com/ixeds

- If Syslist does not function correctly after you have performed all these steps, check to see if you are using the E_ALL option for error reporting in your php.ini. Turning this option off may correct the problem.

Thanks again!
