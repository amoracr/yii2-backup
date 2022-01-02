# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Support for using regex for files to skip in backup file.
- Custom command for MySQL databases.
- Custom command for MariaDB databases.
- Custom command for PostgreSQL databases
- Support for MSSQL databases.

## [1.4.0] 2022-01-01
### Added
- Option for skipping databases in backup.

## [1.3.3] 2021-09-09
### Changed
- Validation for backupDir property.
- Validation for compression property.
- Validation for databases property.
- Validation for directories property.
- Validation for expireTime property.
- Validation for fileName property.
- Internal documentation.

## [1.3.2] 2021-09-09
### Fixed
- [Can't backup database, error in source files](https://github.com/amoracr/yii2-backup/issues/4)

## [1.3.1] 2021-09-01
### Changed
- Readme file.

## [1.3.0] 2021-08-31
### Added
- Support for using regex for files to include in backup file.
- Support for using regex for restoring files from backup file.

## [1.2.6] 2021-06-19
### Added
- Support for PHP 5.1 and later versions.

### Changed
- Internal documentation.
- Command for creating backup of MySQL databases.

## [1.2.5] 2021-05-27
### Added
- Plugins for checking source code with Phan.

### Changed
- Database handler for SQLite databases.

## [1.2.4] 2021-05-26
### Changed
- Extension details in composer.json.

## [1.2.2] 2021-05-24
### Added
- Template file for pull request.

### Changed
- Code of conduct file.
- Contributing file.
- Template file for bug.
- Template file for feature request.

### Removed
- Todo file.

## [1.2.1] 2021-05-21
### Added
- Funding file.
- Funding section.

## [1.2.0] 2021-05-07
### Added
- Compatibility check for PHP 8.
- Template file for bug.
- Template file for feature request.

### Changed
- Internal documentation.

## [1.1.4] 2021-02-22
### Added
- Code of conduct file.

### Changed
- Readme file.
- Contributing file.
- License file.

## [1.1.3] 2021-02-19
### Added
- Todo file.

### Changed
- Validation for dumpCommand property.
- Validation for loadCommand property.
- Internal documentation.

## [1.1.2] 2020-12-23
### Added
- Contributing file.

## [1.1.1] 2020-12-22
### Added
- Repository for packagist assets.
- Code Of Conduct file.

### Changed
- Supported PHP versions in composer.json.
- Internal documentation.
- Database handler for MySQL databases.
- Database handler for MariaDB databases.
- Database handler for SQLite databases.
- Database handler for PostgreSQL databases.

## [1.1.0] 2020-12-19
### Added
- Support for dumping and restoring data for PostgreSQL databases.

### Changed
- Configuration of commands for MySQL databases.
- Commands for MySQL databases.

## [1.0.2] 2020-11-30
### Added
- Changelog file.

## [1.0.1] 2020-11-29
### Changed
- Validation for backupDir property.

## [1.0.0] 2020-07-11
### Added
- Component for making backups.
- Support for dumping and restoring data for MySQL databases.
- Support for dumping and restoring data for MariaDB databases.
- Support for dumping and restoring data for SQLite databases.
- Functionality for creating backup file in tar format.
- Support for compressing backup file in Bzip2 format.
- Support for compressing backup file in Gzip format.
- Support for compressing backup file in Zip format.
