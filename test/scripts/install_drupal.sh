#!/bin/bash

#
# Use defined variables, but otherwise provide defaults.
#
: ${DB_USER:=ex_test_user}
: ${DB_PW:=entity_xliff_test_password}
: ${DB_HOST:=127.0.0.1}
: ${DB_NAME:=entity_xliff_test}
: ${BUILD_DIR:=build}
WORKING_DIR=`pwd`

# Clear out any existing Drupal site build.
rm -rf $BUILD_DIR/drupal

# Ensure we have a database to install to.
if ! mysql --user=$DB_USER --password=$DB_PW --host=$DB_HOST -e "use $DB_NAME"; then
  # Try creating database.
   if ! mysql --user=$DB_USER --password=$DB_PW --host=$DB_HOST -e "create database $DB_NAME"; then
     echo -e "\n##"
     echo -e "# It's possible that MySQL credentials have not been configured as expected."
     echo -e "# Please ensure they're configured and try again. You may need to run:"
     echo -e "#"
     echo -e "# mysql -e \"CREATE USER '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$DB_PW';\""
     echo -e "# mysql -e \"GRANT ALL PRIVILEGES ON $DB_NAME . * TO '$DB_USER'@'$DB_HOST';\""
     echo -e "#"
     echo -e "# ...As a sufficiently priveleged user."
     echo -e "##"
     exit 1
   fi
fi

# Install Drupal into: project_root/build/drupal
/usr/bin/env PHP_OPTIONS="-d sendmail_path=`which true`" drush --yes qd --profile=standard --no-server --browser=0 --db-url=mysql://$DB_USER:$DB_PW@$DB_HOST/$DB_NAME $BUILD_DIR --verbose --debug

# To pin a version of drupal, use the --core line as part of drush qd
# and make the version match on the mv line.
# --core=drupal-7.50
#mv $BUILD_DIR/drupal-7.50 $BUILD_DIR/drupal

# Place this module into the sites/all/modules directory and enable it.
rsync -aq "`pwd`" "`pwd`/$BUILD_DIR/drupal/sites/all/modules/entity_xliff" --exclude build
# pin entity-7.x-1.7 until this gets fixed: https://www.drupal.org/node/2807275
# Pin references-7.x-2.1 since the module is unsupported do to unresolved security issues.
pushd $BUILD_DIR/drupal
  drush --yes dl composer-8.x-1.2 composer_manager link entity-7.x-1.7 field_collection paragraphs entityreference entity_translation references-7.x-2.1 workbench_moderation-7.x-1.4
  # Don't enable entity_translation until needed.
  drush --yes en  composer_manager translation link  field_collection paragraphs_i18n entityreference node_reference workbench_moderation
  drush cc drush
  drush --yes en entity_xliff
  drush cc all
popd

# Patch field collection module.
pushd $BUILD_DIR/drupal/sites/all/modules/field_collection
  #curl https://www.drupal.org/files/issues/1937866-field_collection-metadata-setter-6.patch | patch -p1
  #curl https://www.drupal.org/files/issues/field_collection-logic-issue-with-fetchHostDetails-2382089-23.patch | patch -p1
popd

# Patch paragraphs module.
pushd $BUILD_DIR/drupal/sites/all/modules/paragraphs
  curl https://www.drupal.org/files/issues/paragraphs-metadata_wrapper_set_revision-2621866-3.patch | patch -p1
popd

# Patch workbench moderation module.
pushd $BUILD_DIR/drupal/sites/all/modules/workbench_moderation
  curl https://www.drupal.org/files/issues/workbench_moderation-transaction_rollback_shutdown_sanity-2664018-2.patch | patch -p1
  #curl https://www.drupal.org/files/issues/1436260-workbench_moderation-states-node_save-74.patch | patch -p1 -R
popd

# Run a composer install because it may have failed above
pushd $BUILD_DIR/drupal/sites/default/files/composer
  composer install --prefer-dist --no-dev
popd

# Install a language and enable translation on the "page" content type
pushd $BUILD_DIR/drupal
  drush scr $WORKING_DIR/test/scripts/setup_langs.php
  drush cc all
popd
