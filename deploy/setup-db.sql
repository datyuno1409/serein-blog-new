DROP DATABASE IF EXISTS serein_db;
DROP USER IF EXISTS serein_user;
CREATE DATABASE serein_db;
CREATE USER serein_user WITH PASSWORD 'SereinBlog@2310';
GRANT ALL PRIVILEGES ON DATABASE serein_db TO serein_user;
ALTER DATABASE serein_db OWNER TO serein_user;
