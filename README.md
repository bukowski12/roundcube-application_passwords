Application Passwords Plugin for Roundcube
==========================================
This RoundCube plugin adds support for managing application-specific
passwords to Roundcube's settings task. Currently only SQL-based stores
for application-specific passwords are supported.

Application-specific passwords allow users to create a unique password for 
each application interacting with the email server. Additionally the usage 
of regular user passwords may be completely disabled for email protocols 
such as SMTP, IMAP or POP3 to lower the risk of stealing username and 
password combination by eavesdropping in insecure networks.

Installation
------------
- Clone from github:
    HOME_RC/plugins$ git clone [https://github.com/dweuthen/roundcube-application_passwords.git](https://github.com/dweuthen/roundcube-application_passwords.git) application_passwords

Configuration
-------------

This plugin has been developed on a Debian-based Exim/Dovecot email server 
where domains and users are managed in MySQL. 

First create a database table (for this example, we are assuming in the Roundcube database)

```
CREATE TABLE app_password (app_id int NOT NULL AUTO_INCREMENT, application varchar(100) NOT NULL, username varchar(100) NOT NULL, domain varchar(100) NOT NULL, password varchar(64) NULL, salt varchar(16) NULL, created datetime NOT NULL, lastlogin datetime NULL);
```

Extract the plugin contents into the Roundcube /plugins/ folder with the folder name application_passwords.

Rename the application_passwords config.inc.php.dist file to config.inc.php and verify the settings as follows. This example uses the SHA256 algorithm, but in theory any MySQL hashing function can be used. 

```
// SQL query for displaying list of applications for which a password is set
$config['application_passwords_select_query'] = 'SELECT app_id, application, created, lastlogin FROM app_password WHERE username=%l';

// SQL query for storing new application-specific password
$config['application_passwords_insert_query'] = 'INSERT INTO app_password (username, application, password, salt, created, lastlogin) VALUES (%l, %a, SHA2(CONCAT(%s+%p),512), %s, now())';

// SQL query for deleting an application-specific password
$config['application_passwords_delete_query'] = 'DELETE FROM app_password WHERE username=%l AND app_id=%i';
```

Enable the plugin in Roundcube's config.inc.php:

```
// ----------------------------------
// PLUGINS
// ----------------------------------
// List of active plugins (in plugins/ directory)
$config['plugins'] = array('application_passwords',''markasjunk','attachment_reminder','vcard_attachments');
```

Configure a Dovecot Authentcation database to validate passwords.

```
# Database driver: mysql, pgsql, sqlite
driver = mysql
connect = "host=localhost dbname=roundcube user=dovecot password=password"
default_pass_scheme = PLAIN
password_query = SELECT username, NULL AS password, 'Y' as nopassword FROM app_password WHERE username = '%n' AND password = SHA2(CONCAT(salt,'%w'),512)
```

See http://wiki.dovecot.org/AuthDatabase/SQL for more information on Dovecot Authentication Databases.

For a more complete example demonstrating account lockout, basic logging, etc. look at example.sql and example-app_authenticate.sql.

Notes
-----
Tested with RoundCube 1.4.13 on Centos (PHP 7.4.19, MySQL 5.5.5-10.3.28-MariaDB)
Parts of the code was taken from Aleksander Machniak's Password Plugin for 
Roundcube and the author was inspired by Google's implementation of 
application-specific passwords.

License
-------
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see http://www.gnu.org/licenses/.

