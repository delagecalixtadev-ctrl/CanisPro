#!/bin/bash
set -e

echo "==== Installation dépendances ===="
composer install --no-interaction

echo "==== Passage à Symfony 8 ===="

composer config extra.symfony.require "8.0.*"
composer update "symfony/*" --with-all-dependencies --no-interaction

echo "==== Symfony prêt ===="