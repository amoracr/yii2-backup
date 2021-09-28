# Yii2-backup
Backup and Restore functionality for Yii2 applications.

This extension is based on:
- [yii2-backup](https://github.com/demisang/yii2-backup) by [Ivan Orlov](https://github.com/demisang),
- [yii2-backup](https://github.com/elleracompany/yii2-backup) by [Ellera](https://github.com/elleracompany).
- [yii2-postgresql-backup](https://github.com/vitalik74/yii2-postgresql-backup) by [Vitaliy Tsibikov](https://github.com/vitalik74)
- [MySQL Dump Utility](https://github.com/dg/MySQL-dump) by [David Grudl](https://github.com/dg)
- [php-sqlite-dump](https://github.com/ephestione/php-sqlite-dump) by [ephestione](https://github.com/ephestione)
- [show-only-views-in-sqlite](https://stackoverflow.com/questions/9479540/show-only-views-in-sqlite)
- [extract-folder-content-using-ziparchive](https://stackoverflow.com/questions/8102379/extract-folder-content-using-ziparchive)
- [loading-sql-files-from-within-php](https://stackoverflow.com/questions/147821/loading-sql-files-from-within-php)
- [mysql-how-to-show-all-stored-procedures-functions](https://tableplus.com/blog/2018/08/mysql-how-to-show-all-stored-procedures-functions.html)
- [how-do-you-list-all-triggers-in-a-mysql-database](https://stackoverflow.com/questions/47363/how-do-you-list-all-triggers-in-a-mysql-database)
- [how-can-i-list-all-the-triggers-of-a-database-in-sqlite](https://stackoverflow.com/questions/18655057/how-can-i-list-all-the-triggers-of-a-database-in-sqlite)
- [show-only-views-in-sqlite](https://stackoverflow.com/questions/9479540/show-only-views-in-sqlite)
- [how-to-list-all-constraints-of-a-table-in-postgresql](https://stackoverflow.com/questions/62987794/how-to-list-all-constraints-of-a-table-in-postgresql)
- [how-to-list-all-views-in-sql-in-postgresql](https://dba.stackexchange.com/questions/23836/how-to-list-all-views-in-sql-in-postgresql/23837#23837)
- [how-to-list-triggers-in-postgresql-database](https://soft-builder.com/how-to-list-triggers-in-postgresql-database/)
- [postgresql-how-to-show-stored-procedure](https://tableplus.com/blog/2018/08/postgresql-how-to-show-stored-procedure.html)
- [how-to-list-indexes-created-for-table-in-postgres](https://stackoverflow.com/questions/37329561/how-to-list-indexes-created-for-table-in-postgres/37330092)

I combined those sources and made a more powerful and easier to use extension.

Supported databases:
- MySQL
- MariaDB (via MySQL driver)
- SQLite
- PostgreSQL

Supported compression methods:
- Bzip2
- Gzip
- Zip

By default the backup files is a tar file with sql dumps and folders.

Current limitations:
- Requires a linux system.
- Currently only MySQL on localhost is supported.
- Currently only MariaDB on localhost is supported.
- Currently only PostgreSQL on localhost is supported.


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
                // format: <inner backup filename> => array('path'=><path/to/dir>,'regex'=><regular/expression/>)
                // Key 'path' for setting the directory to include
                // Key 'regex' for setting the regular expression for selecting the files to include
                'pdf' => [
                   'path' => '@app/web/documents',
                   'regex' => '/\.pdf$/',
                ],
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
\>pdf/<br />
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
