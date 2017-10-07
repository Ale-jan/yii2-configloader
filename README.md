Yii2 Configloader
=================

Build configuration array from config files for different app parts.

## Installation

Install the package with [composer](http://getcomposer.org):

    composer require alejan/yii2-configloader

## Features

You can use this extension to solve some or all of the following tasks:

 * Build Yii2 configuration arrays for different app parts
 * Load environment variables from a `.env` file
 * Get config options from environment variables
 * Load environment and local configuration overrides

## Usage

### 1. Initializing

Override the index.php file like this

```php
<?php

use alejan\yii2confload\Config;

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../../common/config/bootstrap.php');
require(__DIR__ . '/../config/bootstrap.php');

$config = new Config(__DIR__ . '/../../config', ['db', 'params']);

(new yii\web\Application($config->frontend))->run();

```

### 2. Loading configuration
If you override index.php like example from paragraph 1 
this extension will be load configuration from such files in configuration folder

 * `main.php` - Load as default common config file if file exist
 * `db.php` - Setted when create new Config object. Load if file exist
 * `params.php` - Setted when create new Config object. Load if file exist
 * `frontend.php` - Setted when call $config->frontend. When call $config->backend will be load `backend.php`
 * `frontend_dev.php` - Load if file exist. The 'dev' part is defined by the variable YII_ENV
 * `local_frontend.php` - Load if file exist 
 * `local_frontend_dev.php` - Load if file exist 


#### 2.1 Local configuration

By default local configuration files are not loaded. 
To activate this feature you can either set the `ENABLE_LOCALCONF` environment
variable (either in your server environment or in `.env`):

```
ENABLE_LOCALCONF=1
```
