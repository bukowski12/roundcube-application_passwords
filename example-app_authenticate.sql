DELIMITER $$

DROP PROCEDURE `app_authenticate`;

CREATE PROCEDURE `app_authenticate`(
    IN vUsername varchar(128),
    IN vPassword varchar(128),
	IN vService varchar(100),
	IN vRemoteAddress varchar(100),
    IN vOutFormat varchar(100),
	IN vIgnoreWhitespace bit
)
BEGIN

	DECLARE vResetBadPasswordCountAfter int;
	DECLARE vLockoutDuration int;
	DECLARE vMaxPasswordAttempts int;
    DECLARE MatchUser_ID int(10);
    DECLARE MatchApp_ID int(10);
	DECLARE PasswordAccessResult int;

	SET vMaxPasswordAttempts = 10;
	SET vResetBadPasswordCountAfter = 10;
	SET vLockoutDuration = 10;

	IF (vIgnoreWhitespace = 1) THEN
		SET vPassword = REPLACE(vPassword,' ','');		
	END IF;

    #find the user by name
    SELECT	user_id
    FROM	users
    WHERE	username = vUsername			
    INTO	MatchUser_ID;

    IF (MatchUser_ID IS NOT NULL) THEN 
    
		IF NOT EXISTS(SELECT * FROM app_user WHERE user_id = MatchUser_ID) THEN
			INSERT INTO app_user (user_id, badpasswordcount, islockedout) 
			VALUES(MatchUser_ID, 0, 0);
		END IF;
	
        #reset bad password account if it has been long enough
        UPDATE	app_user
        SET		badpasswordcount = 0            
        WHERE   user_id = MatchUser_ID
                AND TIMESTAMPDIFF(MINUTE, lastbadpassword, UTC_TIMESTAMP()) 
                    > vResetBadPasswordCountAfter;

        #check if the password matches any UserPassword record
        SELECT 	ap.app_id
        FROM 	app_user au
				INNER JOIN app_password ap ON ap.user_id = au.user_id
        WHERE	au.user_id = MatchUser_ID
                AND (au.islockedout = 0 OR TIMESTAMPDIFF(MINUTE, au.lastbadpassword, UTC_TIMESTAMP()) > vLockoutDuration)
		        AND (ap.password = SHA2(CONCAT(vPassword,ap.salt), '256'))
        INTO MatchApp_ID;

        #if the password is incorrect, and the username is not
        IF (MatchApp_ID IS NULL AND MatchUser_ID > 0) THEN
           
			#incr account lockout
            UPDATE  app_user
            SET		lastbadpassword = UTC_TIMESTAMP(),
                    badpasswordcount = badpasswordcount + 1
            WHERE   user_id = MatchUser_ID;
			
			#lockout account after too many bad password attempts
			UPDATE 	app_user
			SET		islockedout = 1
            WHERE   user_id = MatchUser_ID
                    AND islockedout = 0
					AND badpasswordcount >= vMaxPasswordAttempts;

			INSERT INTO `app_log` (logdatetime,username,user_id,service,remoteaddress,message)
			VALUES(UTC_TIMESTAMP(), vUsername, MatchUser_ID, vService, vRemoteAddress, 'Bad password.');

        #else if the password is correct, and the user is matched
        ELSEIF (MatchApp_ID > 0 AND MatchUser_ID > 0) THEN            
            #clear the lockout flag and set the last login date time if the login was successful         
            UPDATE 	app_user
            SET		islockedout = 0,
                    lastlogin = UTC_TIMESTAMP()
            WHERE 	user_id = MatchUser_ID;                
            
            # update the last used date on UserPassword
            UPDATE  app_password
            SET     lastlogin = UTC_TIMESTAMP(),
					lastaddress = vRemoteAddress
            WHERE   app_id = MatchApp_ID;

			INSERT INTO `app_log` (logdatetime,username,user_id,service,remoteaddress,message)
			VALUES(UTC_TIMESTAMP(), vUsername, MatchUser_ID, vService, vRemoteAddress, 'Logged in');
		END IF;

    ELSE
		INSERT INTO `app_log` (logdatetime,username,user_id,service,remoteaddress,message)
		VALUES(UTC_TIMESTAMP(), vUsername, MatchUser_ID, vService, vRemoteAddress, 'Bad username.');
	END IF;

    IF (vOutFormat = 'dovecot') THEN
        #dovecot format
        SELECT NULL AS password,'Y' as nopassword, u.username AS user
        FROM    users u
        WHERE	u.user_id = MatchUser_ID 
				AND MatchApp_ID IS NOT NULL;
    ELSE
        #generic format where 0 returned rows = fail, nonzero rows equals success
        SELECT 	username
		FROM    users u
        WHERE	u.user_id = MatchUser_ID
				AND MatchApp_ID IS NOT NULL;
    END IF;
END$$

DELIMITER ;