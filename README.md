DB BackUp and Restore Class

#### Issues and pull requests welcome.

A PHP Class that helps take backup of DB as well as restore from backup.

### Table of Contents
* [Installation](#installation)
* [Usage](#usage)
* [Acknowledgement](#acknowledgement)
* [Contribute](#contribute)

## Installation
You will need [PHP 7.x](https://www.php.net/) and [composer](https://getcomposer.org/download/).

Install using composer: `composer require djunehor/db-backup-restore`

## Usage

```php
use \Djunehor\DB\BackUp;

/**
	 *
	 *
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param string $charset
	 * @param string $lang
	 */
$db = new BackUp( 'localhost', 'root', '', 'test', 'utf8', 'en' );

// To backup DB
$db->backup ();

//To restore from backup
$db->restore ( __DIR__.'/backup/20121027194215_all_v1.sql')

```

## Acknowledgment
Adapted from the class created by [yanue](https://github.com/yanue/Dbmanage.git )

## Contribute
Check out the issues on GitHub and/or make a pull request to contribute!
