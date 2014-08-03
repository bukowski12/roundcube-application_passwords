CREATE TABLE `app_user` (
	`user_id` int(10) unsigned NOT NULL,
	`badpasswordcount` int(11) unsigned NOT NULL DEFAULT '0',
	`lastbadpassword` datetime DEFAULT NULL,
	`lastlogin` datetime DEFAULT NULL,
	`islockedout` bit(1) NOT NULL DEFAULT b'0',
	PRIMARY KEY (`user_id`),
	CONSTRAINT user_id_fk_app_users FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `app_password` (
	`app_id` int(11) NOT NULL AUTO_INCREMENT,	
	`user_id` int(10) unsigned NOT NULL,
	`application` varchar(100) NOT NULL,
	`password` varchar(255) DEFAULT NULL,
	`created` datetime NOT NULL,
	`salt` varchar(16) DEFAULT NULL,
	`lastlogin` datetime NULL,
	`lastaddress` varchar(100) NULL,
	PRIMARY KEY (`app_id`),
	CONSTRAINT user_id_fk_app_password FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `app_log` (	
	`log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`logdatetime` datetime NOT NULL,
	`username` varchar(128) NULL,
	`user_id` int(11) NULL,
	`service` varchar(100) NULL,
	`remoteaddress` varchar(100) NULL,	
	`message` nvarchar(1000) NOT NULL,
	PRIMARY KEY (`log_id`)
);

CREATE INDEX `user_id_ix_app_log` ON `app_log` (`user_id`,`logdatetime`);

