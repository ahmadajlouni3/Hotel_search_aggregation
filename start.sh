#!/bin/bash
# go to CI4 folder
cd hotel-aggregator

# install Composer locally if vendor/ missing
if [ ! -d "vendor" ]; then
    curl -sS https://getcomposer.org/installer | php
    php composer.phar install --no-dev
fi

# start PHP server on Railway port
php spark serve --host=0.0.0.0 --port=8080
