/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Miguel
 * Created: 18-sep-2019
 */

CREATE DATABASE IF NOT EXISTS api_rest_laravel;
USE api_rest_laravel;

CREATE TABLE users(
id                  int(255) auto_increment NOT NULL,
name                varchar(50) NOT NULL,
surname             varchar(100),
role                varchar(20),
email               varchar(255) NOT NULL,
password            varchar(255) NOT NULL,
description         text,
image               varchar(255),
created_at          datetime DEFAULT NULL,
updated_at          datetime DEFAULT NULL,
remember_token      varchar(255),
CONSTRAINT pk_users PRIMARY KEY(id)
)ENGINE=InnoDB;

CREATE TABLE categories(
id                  int(255) auto_increment NOT NULL,
name                varchar(100) NOT NULL,
created_at          datetime DEFAULT NULL,
updated_at          datetime DEFAULT NULL,
CONSTRAINT pk_categories PRIMARY KEY(id)
)ENGINE=InnoDB;

CREATE TABLE posts(
id                  int(255) auto_increment NOT NULL,
user_id             int(255) NOT NULL,
category_id         int(255) NOT NULL,
title               varchar(255) NOT NULL,
content             text NOT NULL,
image               varcha(255),
created_at          datetime DEFAULT NULL,
updated_at          datetime DEFAULT NULL,
CONSTRAINT pk_posts PRIMARY KEY(id),
CONSTRAINT pk_post_user     FOREIGN KEY(user_id) REFERENCES users(id),
CONSTRAINT pk_post_category FOREIGN KEY(category_id) REFERENCES categories(id),
)ENGINE=InnoDB;
