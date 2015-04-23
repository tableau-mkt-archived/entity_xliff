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
rm -rfd $BUILD_DIR/drupal

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
drush --yes qd --profile=standard --no-server --browser=0 --db-url=mysql://$DB_USER:$DB_PW@$DB_HOST/$DB_NAME $BUILD_DIR

# Place this module into the sites/all/modules directory and enable it.
rsync -aq "`pwd`" "`pwd`/$BUILD_DIR/drupal/sites/all/modules/entity_xliff" --exclude build
pushd $BUILD_DIR/drupal
  drush --yes dl composer_manager link
  drush --yes en composer_manager translation link
  drush --yes en entity_xliff
  drush cc all
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
