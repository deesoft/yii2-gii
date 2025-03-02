yii2-generators
===============

Yii2 custom generator

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require deesoft/yii2-gii "~1.0"
```

or add

```
"deesoft/yii2-gii": "~1.0"
```

to the require section of your `composer.json` file.

Usage
-----

Once the extension is installed, simply modify your application configuration as follows:

```php
...
if (!YII_ENV_TEST) {
//     configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'generators' => [
            'crud' => ['class' => 'dee\gii\generators\crud\Generator'],
            'inertia' => ['class' => 'dee\gii\generators\inertia\Generator'],
            'mvc' => ['class' => 'dee\gii\generators\mvc\Generator'],
            'migration' => ['class' => 'dee\gii\generators\migration\Generator'],
        ]
    ];
}

```
