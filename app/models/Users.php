<?php

use Phalcon\Mvc\Model;

/* postgresql table created with
CREATE TABLE "users" (
id SERIAL PRIMARY KEY,
name varchar(70) NOT NULL,
email varchar(70) NOT NULL
);
Then still have to grant all privileges on database *and* users table to lti user.
*/

class Users extends Model
{
	public $id;
	public $name;
	public $email;
}
