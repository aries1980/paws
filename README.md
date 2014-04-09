PAWS
====
Home exercise to demo a small, component-based architecture in PHP.

Installation
============
* Clone / download it from Github.
* Adjust your webserver settings.
* Install dependencies via Composer (composer.phar install)
* Import the paws.sql into your database. E.g. mysql -u paws -ppaws paws < paws.sql .
* Create a copy of common.yml.dist to common.yml in app/config . Edit the values.

TODO
====
* Low-level checks, such as PHP version check, writable directories, components has been installed via Composer, etc.
* Caching storage engine for sessions, ESIs.
* Organize out the controller functionality from entities to controllers. Pass the DI to the controller only.
* End-to-end tests with Behat.
* Build with Phing - initialize the yaml config files, import the database.
* ... or build and provision with Puppet.
