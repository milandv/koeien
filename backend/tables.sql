DROP TABLE IF EXISTS commons_instances;
DROP TABLE IF EXISTS commons_users;
DROP TABLE IF EXISTS commons_users_alerts;
DROP TABLE IF EXISTS commons_users_cash_summary;
DROP TABLE IF EXISTS commons_users_cash_production;
DROP TABLE IF EXISTS commons_users_cash_purchases;
DROP TABLE IF EXISTS commons_users_cash_sales;
DROP TABLE IF EXISTS commons_cows;
DROP TABLE IF EXISTS commons_cows_current_production;
DROP TABLE IF EXISTS commons_cows_production_history;
DROP TABLE IF EXISTS commons_cows_current_health;
DROP TABLE IF EXISTS commons_cows_health_history;
DROP TABLE IF EXISTS commons_herds;
DROP TABLE IF EXISTS commons_global_health;
DROP TABLE IF EXISTS commons_global_stats;
DROP TABLE IF EXISTS commons_grazing_orders;
DROP TABLE IF EXISTS commons_login_log;
DROP TABLE IF EXISTS posts;

CREATE TABLE commons_instances (
 instance_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 hash VARCHAR(15),
 name VARCHAR(100),
 timezone INT,
 start_date TIMESTAMP,
 end_date TIMESTAMP,
 admin_email VARCHAR(200),
 include_weekends INT,
 default_graze INT
);

CREATE TABLE commons_users (
 user_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 hash VARCHAR(15),
 instance_id INT,
 student_id VARCHAR(20), 
 firstname VARCHAR(50),
 lastname VARCHAR(50),
 login VARCHAR(100),
 email VARCHAR(100),
 passwd VARCHAR(32),
 is_instructor INT DEFAULT 0,
 approved INT DEFAULT 1,
 ts TIMESTAMP
);

CREATE TABLE commons_users_alerts (
 user_id INT PRIMARY KEY
);

CREATE TABLE commons_users_cash_summary (
 user_id INT,
 ts DATE,
 cash DECIMAL(12,2),
 PRIMARY KEY (user_id, ts)
);

CREATE TABLE commons_users_cash_production (
 user_id INT,
 ts DATE,
 cows INT,
 liters DECIMAL(12,2),
 amt DECIMAL(12,2),
 PRIMARY KEY (user_id, ts)
);

CREATE TABLE commons_users_cash_purchases (
 user_id INT, 
 ts DATE,
 cows INT,
 amt DECIMAL(12,2),
 PRIMARY KEY (user_id, ts)
);

CREATE TABLE commons_users_cash_sales (
 user_id INT, 
 ts DATE,
 cows INT,
 amt DECIMAL(12,2),
 PRIMARY KEY (user_id, ts)
);

CREATE TABLE commons_cows (
 cow_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id INT,
 date_created DATE,
 is_active INT DEFAULT 1
);

CREATE TABLE commons_cows_current_health (
 cow_id INT PRIMARY KEY,
 cow_health DECIMAL(12,2) 
);

CREATE TABLE commons_cows_current_production (
 cow_id INT PRIMARY KEY,
 cow_production DECIMAL(12,2)
);

CREATE TABLE commons_cows_health_history (
 cow_id INT,
 ts DATE,
 cow_health DECIMAL(12,2),
 PRIMARY KEY (cow_id, ts)
);

CREATE TABLE commons_cows_production_history (
 cow_id INT,
 ts DATE,
 cow_production DECIMAL(12,2),
 PRIMARY KEY (cow_id, ts)
);

CREATE TABLE commons_cows_production_log (
 cow_id INT,
 ts DATE,
 liters DECIMAL(12,2),
 PRIMARY KEY (cow_id, ts)
); 

CREATE TABLE commons_herds (
 user_id INT,
 ts DATE,
 herd_size INT,
 avg_health DECIMAL(12,2),
 avg_production DECIMAL(12,2),
 PRIMARY KEY (user_id, ts)
);

CREATE TABLE commons_global_stats (
 instance_id INT,
 ts DATE,
 commons_size INT,
 global_health DECIMAL(12,2),
 self_set INT,
 PRIMARY KEY (instance_id, ts)
);

CREATE TABLE commons_grazing_orders (
 user_id INT,
 ts DATE,
 cows INT,
 self_set INT,
 PRIMARY KEY (user_id, ts)
);

CREATE TABLE commons_login_log (
 login_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id INT,
 ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 KEY u (user_id)
);

CREATE TABLE commons_reset_requests (
 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id INT, 
 hash VARCHAR(20),
 ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
post_id				INT(8) NOT NULL AUTO_INCREMENT,
post_title			TEXT NOT NULL,
post_text			TEXT NOT NULL,
img_link			VARCHAR(300),
post_date			DATETIME NOT NULL,
post_author			INT NOT NULL,
commons_instance	INT NOT NULL,
PRIMARY KEY (post_id)
);

ALTER TABLE posts ADD FOREIGN KEY(post_author) REFERENCES commons_users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE posts ADD FOREIGN KEY(commons_instance) REFERENCES commons_instances(instance_id) ON DELETE RESTRICT ON UPDATE CASCADE;

