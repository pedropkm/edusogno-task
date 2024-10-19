CREATE TABLE IF NOT EXISTS users (
id int NOT NULL AUTO_INCREMENT,
name varchar(45),
lastname varchar(45),
email varchar(255),
password varchar(255),
PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS events (
id int NOT NULL AUTO_INCREMENT,
attendees text,
name varchar(255),
data_event datetime,
PRIMARY KEY (id)
);

INSERT INTO `event`(`attendees`, `name_event`, `data_event`) VALUES ('ulysses200915@varen8.com,qmonkey14@falixiao.com,mavbafpcmq@hitbase.net','Test Edusogno 1', '2022-10-13 14:00'), ('dgipolga@edume.me,qmonkey14@falixiao.com,mavbafpcmq@hitbase.net','Test Edusogno 2', '2022-10-15 19:00'), ('dgipolga@edume.me,ulysses200915@varen8.com,mavbafpcmq@hitbase.net','Test Edusogno 2', '2022-10-15 19:00')