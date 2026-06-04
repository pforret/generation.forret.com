#!/usr/bin/env bash
valet php artisan static:generate --clean
pushd static || exit
mkdox2 -G build
popd || exit
setver ap
