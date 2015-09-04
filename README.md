# Visualization App

## Requirements
- Apache
- PHP v5.4 or newer
- Phalcon PHP extension ([installation instructions](https://phalconphp.com/en/download))
- MongoDB PHP extension ([installation instructions](http://us3.php.net/manual/en/mongo.installation.php))
- Phalcon developer tools ([installation instructions](http://phalcon-php-framework-documentation.readthedocs.org/en/latest/reference/tools.html))
- Database for storing settings and calculations. Currently configured with SQLite, which requires SQLite PHP extension.
- Direct mongo connection to Learning Locker's database

## Configuration
- Copy `app/config/config.example.php` to `app/config.php` and change:
	- SQLite database location
	- Database credentials for LRS databases
	- LTI configurations: for launching this app, and for launching others
	- Learning Record Store details: for fetching statements and sending statements to multiple LRSs
	- Site base URI
- Execute the following commands on the SQLite database
	- `create table user_settings (userId text, name text, value text);`
	- `create table stored_calculations (userId text, calculationId text, dateStored integer, value real);`

## LTI integration
- Basic LTI POST requests should be sent to `{base URI}/launch.php`
- When logged in, LTI session information can be viewed at `{base URI}/ltiinfo`

### This app uses
- BLTI PHP library from [IMS Global](http://developers.imsglobal.org/phpcode.html)
- [Phalcon framework](https://phalconphp.com/en/)
- [D3.js](http://d3js.org)
- [Bootstrap](http://getbootstrap.com/)
