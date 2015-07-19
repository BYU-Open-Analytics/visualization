# Basic LTI PHP app
Uses BLTI library from [IMS Global](http://developers.imsglobal.org/phpcode.html)

## Requirements
- Apache
- PHP v5.4 or newer
- Phalcon PHP extension ([installation instructions](https://phalconphp.com/en/download))
- Database connection (currently configured with MySQL)

## Configuration
- Copy `app/config/config.example.ini` to `app/config.ini` and change
	- Database credentials
	- LTI consumer key/shared secret pair
	- Site base URI

## LTI integration
- Basic LTI POST requests should be sent to {base URI}/launch.php
- When logged in, LTI session information can be viewed at {base URI}/ltiinfo
