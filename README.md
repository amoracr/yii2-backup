Yii2-backup
===========
Backup and Restore functionality for Yii2 applications.

This extension is based on the extensions [yii2-backup](https://github.com/demisang/yii2-backup) by [Ivan Orlov](https://github.com/demisang), and [yii2-backup](https://github.com/elleracompany/yii2-backup) by [Ellera](https://github.com/elleracompany). I combined those extensions and made a more powerful and easier to use extension.


Supported databases:
- MySQL
- MariaDB
- SQLite

Current limitations:
- Requires a linux system.
- Currently only MySQL on localhost is supported.


Getting started
------------

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist amoracr/yii2-backup "*"
```

or add

```
"amoracr/yii2-backup": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply add it in your config file:

Basic ```config/console.php```

Advanced ```console/config/main.php```

Minimal Config
```php
'components' => [
    ...
    'backup' => [
            'class' => 'amoracr\backup\Backup',
            // Path for storing the backups
            'backupDir' => '@app/backups',
            // Directories that will be added to backup
            'directories' => [
                // format: <inner backup filename> => <path/to/dir>
                'images' => '@frontend/web/images',
            ],
        ],
]
```
Will create backup for:
directories:
/frontend/web/images/*
database:
Yii::$app->db

Result:
/backups/2020-06-29T182436-0600_backup.tar/
>images/
>sql/db.sql

Advanced Config
```php
'components' => [
    ...
    'backup' => [
            'class' => 'amoracr\backup\Backup',
            // Name for the backup
            'fileName' => 'myapp-backup',
            // Maximum age in seconds for a valid backup.
            // Older files are considered deprecated and can be deleted.
            // Minimum age is 86400 secs (1 day) and maximum age is 31536000 secs (1 year)
            'expireTime'=> 86400 * 3,
            // Path for storing the backups
            'backupDir' => '@app/backups',
            // Database components to backup
            'databases' => ['db', 'db1'],
            // Directories that will be added to backup
            'directories' => [
                // format: <inner backup filename> => <path/to/dir>
                'images' => '@frontend/web/images',
                'uploads' => '@frontend/web/uploads',
            ],
            // Files to avoid in backup accross all directories
            'skipFiles' => [
                '.gitignore',
            ]
        ],
]
```
Result:
/backups/2020-06-29T182436-0600_myapp-backup.tar/
>images/
>uploads/
>sql/db.sql
>sql/db1.sql
