h1. COMPOSER

Composer is the PHP package manager that allows you to specify application dependencies and automatically download what is required. 

You can also lock dependencies so your app can remain stable ( provided your app is tested and stable ).r



h2. Installation (Run once at the start of your project)

The following command will installs composer.phar to the root of this app (run in Terminal):

$ curl -s https://getcomposer.org/installer | php -- --install-dir=bin

Initialize composer and follow the prompts ( if composer.json does not already exist )
bin/composer.phar init

This will create a 'composer.json' file in the root of your application.



h2. Managing dependencies

Add any required dependencies to composer.json under the 'require' namespace.

Run the following command to download dependencies before development

$ composer.phar update

This will create and download all dependencies to the 'vendor' dir.



h2. Development Dependencies

These are libraries that are only required whilst developing your app not in production mode.

In composer.json, list such libraries under the 'require-dev' namespace.

To install run the following command in your terminal:

$ composer.phar update --dev