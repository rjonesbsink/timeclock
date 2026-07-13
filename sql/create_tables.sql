
-- if you would like to utilize a table prefix when creating these tables,
-- be sure to reflect that in config.inc.php so the program will be aware
-- of it. this option is $db_prefix. if you are unaware of what is meant by
-- utilizing a 'table prefix', then please disregard.


-- --------------------------------------------------------
--
-- Table structure for table `audit`
--

CREATE TABLE `audit` (
  `modified_when` bigint(14),
  `modified_from` bigint(14) NOT NULL,
  `modified_to` bigint(14) NOT NULL,
  `modified_by_ip` varchar(39) COLLATE utf8_bin NOT NULL DEFAULT '',
  `modified_by_user` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `modified_why` varchar(250) COLLATE utf8_bin NOT NULL DEFAULT '',
  `user_modified` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE INDEX audit_modified_when ON audit (modified_when);


-- --------------------------------------------------------
--
-- Table structure for table `dbversion`
--

CREATE TABLE `dbversion` (
  `dbversion` decimal(5,1) PRIMARY KEY
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- --------------------------------------------------------
--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `empfullname` varchar(50) PRIMARY KEY COLLATE utf8_bin,
  `tstamp` bigint(14) DEFAULT NULL,
  `employee_passwd` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `displayname` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `email` varchar(75) COLLATE utf8_bin NOT NULL DEFAULT '',
  `barcode` varchar(75) COLLATE utf8_bin UNIQUE,
  `groups` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `office` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `reports` tinyint(1) NOT NULL DEFAULT '0',
  `time_admin` tinyint(1) NOT NULL DEFAULT '0',
  `disabled` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- --------------------------------------------------------
--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `groupid` int(10)  AUTO_INCREMENT PRIMARY KEY,
  `groupname` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `officeid` int(10) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- --------------------------------------------------------
--
-- Table structure for table `info`
--

CREATE TABLE `info` (
  `fullname` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `inout` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `timestamp` bigint(14) DEFAULT NULL,
  `notes` varchar(250) COLLATE utf8_bin DEFAULT NULL,
  `ipaddress` varchar(39) COLLATE utf8_bin NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE INDEX info_fullname  ON info (fullname);
CREATE INDEX info_timestamp ON info (`timestamp`);


-- --------------------------------------------------------
--
-- Table structure for table `metars`
--

CREATE TABLE `metars` (
  `station` varchar(4) PRIMARY KEY COLLATE utf8_bin,
  `metar` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `timestamp` timestamp NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- --------------------------------------------------------
--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `officeid` int(10) AUTO_INCREMENT PRIMARY KEY,
  `officename` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- --------------------------------------------------------
--
-- Table structure for table `punchlist`
--

CREATE TABLE `punchlist` (
  `punchitems` varchar(50) PRIMARY KEY COLLATE utf8_bin,
  `punchnext` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `color` varchar(7) COLLATE utf8_bin NOT NULL DEFAULT '',
  `in_or_out` tinyint(1) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- --------------------------------------------------------
--
-- Table structure for table `schedules`
--
-- An employee's recurring weekly schedule: one row per day they're
-- scheduled to work. day_of_week follows PHP's date('w') convention
-- (0 = Sunday .. 6 = Saturday); a day with no row is a day off. An
-- end_time earlier than start_time means the shift crosses midnight.
--

CREATE TABLE `schedules` (
  `scheduleid` int(10) AUTO_INCREMENT PRIMARY KEY,
  `empfullname` varchar(50) COLLATE utf8_bin NOT NULL,
  `day_of_week` tinyint(1) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  UNIQUE KEY `schedules_emp_day` (`empfullname`, `day_of_week`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- --------------------------------------------------------
--
-- Insert default data. Version, etc.
--
-- The initial admin account is created by setup.php with an
-- installer-chosen password, not seeded here with a fixed default
-- credential.

INSERT INTO dbversion VALUES ('1.6');
INSERT INTO punchlist VALUES ('in', '', '#009900', 1);
INSERT INTO punchlist VALUES ('out', '', '#FF0000', 0);
INSERT INTO punchlist VALUES ('break', '', '#FF9900', 0);
INSERT INTO punchlist VALUES ('lunch', '', '#0000FF', 0);
