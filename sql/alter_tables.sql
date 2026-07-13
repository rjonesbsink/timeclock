# if you would like to utilize a table prefix when upgrading these tables, be sure to use the one you have setup in config.inc.php.
# this option is $db_prefix.  if you are unaware of what is meant by utilizing a 'table prefix', then please disregard.

-- Database upgrades may be performed automatically in the app under
-- "Administration -> Upgrade Database" as long as the database connection
-- used by your web app has the required privileges. Otherwise you must
-- perform the upgrade manually.
--
-- When upgrading from versions older than 1.04, perform the upgrades
-- described in this file in order to reach dbversion 1.4.
--
-- To upgrade from dbversion 1.4 or newer, run the upgrade scripts in this
-- directory, one at a time, until your database is upgraded to the latest
-- version.



###################################################################
#                                                                 #
# If upgrading from version 1.01 or 1.0, run these sql statements #
# below on the PHP Timeclock database.                            #
#                                                                 #
###################################################################

#
# Table structure for table `audit`
#

CREATE TABLE audit (
  modified_by_ip   VARCHAR(39)  NOT NULL DEFAULT '',
  modified_by_user VARCHAR(50)  NOT NULL DEFAULT '',
  modified_when    BIGINT(14)   NOT NULL,
  modified_from    BIGINT(14)   NOT NULL,
  modified_to      BIGINT(14)   NOT NULL,
  modified_why     VARCHAR(250) NOT NULL DEFAULT '',
  user_modified    VARCHAR(50)  NOT NULL DEFAULT '',
  PRIMARY KEY (modified_when),
  UNIQUE KEY modified_when (modified_when)
) TYPE = MyISAM;

# --------------------------------------------------------

#
# dbversion table
#

UPDATE `dbversion`
SET `dbversion` = '1.4';

# --------------------------------------------------------

#
# info table
#

ALTER TABLE `info` ADD `ipaddress` VARCHAR(39) NOT NULL DEFAULT '';

# --------------------------------------------------------

#
# employees table
#

ALTER TABLE `employees` ADD `disabled` TINYINT(1) NOT NULL DEFAULT '0';

# --------------------------------------------------------


########################################################################
#                                                                      #
# If upgrading from version 0.9.4-1 or 0.9.4, run these sql statements #
# below on the PHP Timeclock database.                                 #
#                                                                      #
########################################################################

#
# Table structure for table `audit`
#

CREATE TABLE audit (
  modified_by   VARCHAR(50)  NOT NULL DEFAULT '',
  modified_when BIGINT(14)   NOT NULL,
  modified_from BIGINT(14)   NOT NULL,
  modified_to   BIGINT(14)   NOT NULL,
  modified_why  VARCHAR(250) NOT NULL DEFAULT '',
  PRIMARY KEY (modified_when),
  UNIQUE KEY modified_when (modified_when)
) TYPE = MyISAM;

# --------------------------------------------------------

#
# dbversion table
#

UPDATE `dbversion`
SET `dbversion` = '1.4';

# --------------------------------------------------------

#
# employees table
#

ALTER TABLE `employees` ADD `displayname` VARCHAR(50) NOT NULL DEFAULT '';
ALTER TABLE `employees` ADD `email` VARCHAR(75) NOT NULL DEFAULT '';
ALTER TABLE `employees` ADD `groups` VARCHAR(50) NOT NULL DEFAULT '';
ALTER TABLE `employees` ADD `office` VARCHAR(50) NOT NULL DEFAULT '';
ALTER TABLE `employees` ADD `admin` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `employees` ADD `reports` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `employees` ADD `time_admin` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `employees` ADD `disabled` TINYINT(1) NOT NULL DEFAULT '0';

# This is the only point in this upgrade where the admin/reports/time_admin
# columns (and so the concept of an admin account) exist for the first time,
# so an admin account has to be seeded here -- but not with a fixed default
# password. Before running this INSERT, generate a real password hash and
# substitute it for the placeholder below:
#
#   php -r 'echo password_hash("choose-a-strong-password", PASSWORD_DEFAULT);'
#
# The placeholder below is not a valid hash of anything, so the account it
# creates cannot be logged into until you replace it -- if you forget this
# step, the failure is "no working login", not a live default credential.
INSERT INTO employees VALUES ('admin', NULL, 'REPLACE_WITH_A_REAL_PASSWORD_HASH_BEFORE_RUNNING', 'administrator', '', '', '', 1, 1, 1, '');

# --------------------------------------------------------

#
# groups table
#

CREATE TABLE groups (
  groupname VARCHAR(50) NOT NULL DEFAULT '',
  groupid   INT(10)     NOT NULL AUTO_INCREMENT,
  officeid  INT(10)     NOT NULL DEFAULT '0',
  PRIMARY KEY (groupid)
) TYPE = MyISAM;

# --------------------------------------------------------

#
# info table
#

ALTER TABLE `info` CHANGE `inout` `inout` VARCHAR(50) NOT NULL;
ALTER TABLE `info` ADD `ipaddress` VARCHAR(39) NOT NULL DEFAULT '';

# --------------------------------------------------------

#
# offices table
#

CREATE TABLE offices (
  officename VARCHAR(50) NOT NULL DEFAULT '',
  officeid   INT(10)     NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (officeid)
) TYPE = MyISAM;

# --------------------------------------------------------

#
# punchlist table
#

ALTER TABLE `punchlist` CHANGE `punchitems` `punchitems` VARCHAR(50) NOT NULL;
ALTER TABLE `punchlist` ADD `in_or_out` TINYINT(1) DEFAULT '0' NOT NULL;
UPDATE `punchlist`
SET `in_or_out` = '1'
WHERE `punchitems` = 'in'
LIMIT 1;
UPDATE `punchlist`
SET `in_or_out` = '0'
WHERE `punchitems` = 'out'
LIMIT 1;
UPDATE `punchlist`
SET `in_or_out` = '0'
WHERE `punchitems` = 'break'
LIMIT 1;
UPDATE `punchlist`
SET `in_or_out` = '0'
WHERE `punchitems` = 'lunch'
LIMIT 1;
