#!/usr/bin/env bash

php artisan migrate:fresh --seed --force
php artisan import:data
