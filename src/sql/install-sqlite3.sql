-- apt-get install php5-sqlite sqlite3
-- sqlite3 /var/padocc/db.sqlite3 < /var/www/padocc/src/sql/install-sqlite3.sql

PRAGMA encoding = 'UTF-8';
CREATE TABLE deployments (
    exec_id varchar(255) NOT NULL PRIMARY KEY,
    xml_path text NOT NULL,
    project_name varchar(255) NOT NULL,
    env_name varchar(255) NOT NULL,
    external_properties text NOT NULL,
    status varchar(255) NOT NULL CHECK (status IN ('queued', 'in progress', 'failed', 'warning', 'successful')),
    nb_warnings int NOT NULL,
    date_queue text,   -- "YYYY-MM-DD HH:MM:SS.SSS"
    date_start text,   -- "YYYY-MM-DD HH:MM:SS.SSS"
    date_end text,     -- "YYYY-MM-DD HH:MM:SS.SSS"
    is_rollbackable tinyint NOT NULL
);
