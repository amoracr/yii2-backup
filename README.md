Yii2-backup
===========
Backup and Restore functionality for Yii2 applications.

This extension is based on:
- [yii2-backup](https://github.com/demisang/yii2-backup) by [Ivan Orlov](https://github.com/demisang),
- [yii2-backup](https://github.com/elleracompany/yii2-backup) by [Ellera](https://github.com/elleracompany)
- [php-sqlite-dump](https://github.com/ephestione/php-sqlite-dump) by [ephestione](https://github.com/ephestione)
- [loading-sql-files-from-within-php](https://stackoverflow.com/questions/147821/loading-sql-files-from-within-php)

I combined those sources and made a more powerful and easier to use extension.

Supported databases:
- MySQL
- MariaDB
- SQLite

Supported comprenssion methods:
- None
- Bzip2
- Gzip
- Zip

Current limitations:
- Requires a linux system.
- Currently only MySQL on localhost is supported.
- Currently only MariaDB on localhost is supported.


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


Configuration
-------------

Once the extension is installed, add it in your config file:

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
                'images' => '@app/web/images',
                'uploads' => '@app/web/uploads',
            ],
        ],
]
```
#### Will create backup for:
**directories:**<br />
_/web/images/\*_<br />
_/web/uploads/\*_<br />
**database:**<br />
_Yii::$app->db_

#### Result:
**/backups/2020-06-29T182436-0600_backup.tar/**<br />
\>images/<br />
\>uploads/<br />
\>sql/db.sql

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
            // Minimum age is 86400 secs (1 day).
            // Maximum age is 31536000 secs (1 year).
            'expireTime'=> 86400 * 3,
            // Path for storing the backups
            'backupDir' => '@app/backups',
            // Database components to backup
            'databases' => ['db', 'db1'],
            // Compression method to apply to backup file.
            // Available options:
            // 'none' or 'tar' for tar files, backup file is not compressed.
            // 'bzip2' for tar.bz2 files, backup file is compressed with Bzip2 compression.
            // 'gzip' for tar.gz files, backup file is compressed with Gzip compression.
            // 'zip' for zip files, backup file is compressed with Zip compression.
            'compression' => 'zip',
            // Directories that will be added to backup
            'directories' => [
                // format: <inner backup filename> => <path/to/dir>
                'images' => '@app/web/images',
                'uploads' => '@app/web/uploads',
            ],
            // Files to avoid in backup accross all directories
            'skipFiles' => [
                '.gitignore',
            ]
        ],
]
```
#### Result:
**/backups/2020-06-29T182436-0600_myapp-backup.zip/**<br />
\>images/<br />
\>uploads/<br />
\>sql/db.sql<br />
\>sql/db1.sql<br />


Usage
-----
You can use this component in a console command.<br />

**/console/controllers/BackupController.php:/**<br />
```php
<?php
namespace console\controllers;

class BackupController extends \yii\console\Controller
{
    public function actionBackup()
    {
        $backup = \Yii::$app->backup;
        $databases = ['db', 'db1', 'db2'];
        foreach ($databases as $k => $db) {
            $index = (string)$k;
            $backup->fileName = 'myapp-part';
            $backup->fileName .= str_pad($index, 3, '0', STR_PAD_LEFT);
            $backup->directories = [];
            $backup->databases = [$db];
            $file = $backup->create();
            $this->stdout('Backup file created: ' . $file . PHP_EOL, \yii\helpers\Console::FG_GREEN);
        }
    }
}
```
