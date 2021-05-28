# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Support for using regex for files to include in backup file.
- Support for using regex for files to skip in backup file.
- Support for MSSQL databases.

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
