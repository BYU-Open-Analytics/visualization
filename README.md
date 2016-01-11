# Visualization App

## Requirements
- Apache
- PHP *v5.5* or newer
- Phalcon PHP extension ([installation instructions](https://phalconphp.com/en/download))
- MongoDB PHP extension ([installation instructions](http://us3.php.net/manual/en/mongo.installation.php))
- Phalcon developer tools ([installation instructions](http://phalcon-php-framework-documentation.readthedocs.org/en/latest/reference/tools.html))
- Database for storing settings and calculations. Currently configured with PostgreSQl, which requires PostgreSQL PHP extension.
- Direct mongo connection to Learning Locker's database

## Configuration
- Copy `app/config/config.example.php` to `app/config.php` and change:
	- PostgreSQL database configuration
	- Database credentials for LRS databases
	- LTI configurations: for launching this app, and for launching others
	- Learning Record Store details: for fetching statements and sending statements to multiple LRSs
	- Site base URI
- Execute the following commands on the PostgreSQL database
	- `create table students (
	time decimal,
	activity decimal,
	consistency decimal,
	awareness decimal,
	deep_learning decimal,
	persistence_attempts decimal,
	persistence_watched decimal,
	email varchar(254) constraint firstkey primary key
	);`
 
	- `create table skill_history (
	time decimal,
	activity decimal,
	consistency decimal,
	awareness decimal,
	deep_learning decimal,
	persistence_attempts decimal,
	persistence_watched decimal,
	time_stored timestamp default now(),
	id serial primary key,
	email varchar(254) references students(email)
	);`
	 
	- `create table question_attempts (
	count integer,
	question_id varchar(10),
	id serial primary key,
	email varchar(254) references students(email)
	);`

	- `create table feedback (
	type text,
	feedback text,
	email varchar(254),
	time_stored timestamp default now(),
	id serial primary key
	);`

	- `create table student_mastery_history (
	unit1 decimal,
	unit2 decimal,
	unit3 decimal,
	unit4 decimal,
	recent_average decimal,
	time_stored timestamp default now(),
	id serial primary key,
	email varchar(254) references students(email)
	);`

	- `create table class_concept_history (
	time_stored timestamp default now(),
	concept_id integer,
	average_mastery decimal
	);`


## LTI integration
- Basic LTI POST requests should be sent to `{base URI}/launch.php`
- When logged in, LTI session information can be viewed at `{base URI}/ltiinfo`

### This app uses
- BLTI PHP library from [IMS Global](http://developers.imsglobal.org/phpcode.html)
- [Phalcon framework](https://phalconphp.com/en/)
- [D3.js](http://d3js.org)
- [Bootstrap](http://getbootstrap.com/)

## License
<a rel="license" href="http://creativecommons.org/licenses/by/4.0/"><img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by/4.0/80x15.png" /></a><br />This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by/4.0/">Creative Commons Attribution 4.0 International License</a>.
