#!/usr/bin/env bash

php_version=`php -r 'echo PHP_VERSION_ID;'`

# if php 5.2, let's use pear
if [ $php_version -lt 50300 ]; then
    pear channel-discover pear.bovigo.org
    pear install bovigo/vfsStream-beta
    phpenv rehash

# else, let's use composer !
else
    curl -s http://getcomposer.org/installer | php
    php composer.phar install --dev
fi

