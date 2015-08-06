# Visualization App

## Requirements
- Apache
- PHP v5.4 or newer
- Phalcon PHP extension ([installation instructions](https://phalconphp.com/en/download))
- Database connection (currently configured with MySQL)

## Configuration
- Copy `app/config/config.example.php` to `app/config.php` and change
	- Database credentials
	- LTI configurations: for launching this app, and for launching others
	- Learning Record Store details: for fetching statements and sending statements to multiple LRSs
	- Site base URI

## LTI integration
- Basic LTI POST requests should be sent to `{base URI}/launch.php`
- When logged in, LTI session information can be viewed at `{base URI}/ltiinfo`

### This app uses
- BLTI PHP library from [IMS Global](http://developers.imsglobal.org/phpcode.html)
- [Phalcon framework](https://phalconphp.com/en/)
- [D3.js](http://d3js.org)
- [Bootstrap](http://getbootstrap.com/)
