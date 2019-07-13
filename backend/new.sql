-- Note - this information is all in tables.sql. This file contains only the new SQL to be executed for the new additions

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

ALTER TABLE commons_users ADD is_instructor INT DEFAULT 0;
