dhtmlxGantt with PHP backend
------------

Implementing backend for dhtmlxGantt using Slim-framework and PDO

### Requirements

- PHP 5.6+
- [Composer](https://getcomposer.org/)
- MySQL

### Setup

1. Create database and import **schema.sql**
2. Update connection settings in **app/gantt.php**
3. run `composer install`

### Run

- `php -S 0.0.0.0:8080 -t public public/index.php`

### Run with Docker

- `docker-compose up -d`
- http://localhost:8080 in your browser.

### Tutorial

A complete tutorial here https://docs.dhtmlx.com/gantt/desktop__howtostart_php.html
