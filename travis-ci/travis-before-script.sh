#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# Download module dependencies.
mkdir -p "$DRUPAL_TI_DRUPAL_DIR/$DRUPAL_TI_MODULES_PATH"
cd "$DRUPAL_TI_DRUPAL_DIR/$DRUPAL_TI_MODULES_PATH"
git clone --branch 8.x-1.x http://git.drupal.org/project/composer_manager.git --depth 1
php composer_manager/scripts/init.php

# Ensure the module is linked into the codebase.
drupal_ti_ensure_module_linked

cd $DRUPAL_TI_DRUPAL_DIR

# Update composer dependencies.
export COMPOSER_EXIT_ON_PATCH_FAILURE=1
composer drupal-rebuild
composer update -n --lock --verbose

# Check code style using Drupal Coder review.
composer global require "drupal/coder ^8.2"
~/.composer/vendor/bin/phpcs --config-set installed_paths ~/.composer/vendor/drupal/coder/coder_sniffer
cd $DRUPAL_TI_DRUPAL_DIR/modules/decoupled_auth
~/.composer/vendor/bin/phpcs . -np --standard=Drupal --colors

cd $DRUPAL_TI_DRUPAL_DIR

#TEMP: Delete broken test from address module.
rm modules/address/tests/src/Unit/Plugin/Validation/Constraint/CountryConstraintValidatorTest.php

# Require that decoupled_auth is always enabled when the user module is enabled.
git apply -v $DRUPAL_TI_DRUPAL_DIR/modules/decoupled_auth/travis-ci/decoupled_auth_user_modules_installed.patch

# Allow different simpletests to be run for pull requests by drupal_ti
cd ~/.composer/vendor/lionsad/drupal_ti
git apply -v $DRUPAL_TI_DRUPAL_DIR/modules/decoupled_auth/travis-ci/drupal_ti_pull_simpletest.patch
