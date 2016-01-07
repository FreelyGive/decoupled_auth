#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# Ensure the module is linked into the codebase.
drupal_ti_ensure_module_linked

# Require that decoupled_auth is always enabled when the user module is enabled.
cd $DRUPAL_TI_DRUPAL_DIR
git apply -v $DRUPAL_TI_DRUPAL_DIR/modules/decoupled_auth/travis-ci/decoupled_auth_user_modules_installed.patch

# Allow different simpletests to be run for pull requests by drupal_ti
cd ~/.composer/vendor/lionsad/drupal_ti
git apply -v $DRUPAL_TI_DRUPAL_DIR/modules/decoupled_auth/travis-ci/drupal_ti_pull_simpletest.patch
