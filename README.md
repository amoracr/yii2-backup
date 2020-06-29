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

Once the extension is installed, simply add it in your config by  :

Basic ```config/console.php```

Advanced ```console/config/main.php```
```php
'components' => [
    ...
    'backup' => [
            'class' => 'amoracr\yii2-backup\Backup',
            'fileName' => 'backup', // name for the backup file
            'backupDir' => '@app/backups', // path for storing the backups
            'directories' => [
                'images' => '@frontend/web/images', 
                'uploads' => '@backend/uploads',
            ],
            'skipFiles' => [
                '.gitignore',
            ]
        ],
]
```


