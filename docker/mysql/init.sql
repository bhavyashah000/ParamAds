-- ParamAds Database Initialization
-- Creates the analytics database alongside the main OLTP database

CREATE DATABASE IF NOT EXISTS paramads CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS paramads_analytics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON paramads.* TO 'paramads'@'%';
GRANT ALL PRIVILEGES ON paramads_analytics.* TO 'paramads'@'%';
FLUSH PRIVILEGES;
