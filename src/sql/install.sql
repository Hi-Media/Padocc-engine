-- mysql  --user=root --password=xxx
-- CREATE DATABASE padocc CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- CREATE USER padocc IDENTIFIED BY 'xxx';
-- GRANT ALL ON padocc.* TO padocc;
-- mysql --user=padocc --password=xxx padocc < /var/www/padocc/src/sql/install.sql

CREATE TABLE deployments (
    exec_id varchar(255) NOT NULL PRIMARY KEY,
    xml_path text NOT NULL,
    project_name varchar(255) NOT NULL,
    env_name varchar(255) NOT NULL,
    external_properties text NOT NULL,
    status varchar(255) NOT NULL CHECK (status IN ('queued', 'in progress', 'failed', 'warning', 'successful')),
    nb_warnings int NOT NULL,
    date_queue timestamp NULL,
    date_start timestamp NULL,
    date_end timestamp NULL,
    is_rollbackable tinyint(1) NOT NULL
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;
