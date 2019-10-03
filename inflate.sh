#!/bin/bash

#This script downloads the latest code based on the input branch information
#Then it installs all the relavent packages required for the code (composer install)
#does this happens in docker container ? 

apt-get update
apt-get install php-mongodb

echo "Check if the path is available for dependencies installation"

#if [[ "$1" == "" || "$2" == "" ]]
if [[ "$1" == "" ]]
then
  echo "Please pass the dependencies installation path"
  echo $1 "*******" $2
  exit 1
fi


base_path=$1
installation_dir=$2


composer_install(){
echo "Check if composer is installed locally"
  # Test if composer is available
COMPOSER_CMD=$(which composer)
PHP_CMD=$(which php)
if [[ "$COMPOSER_CMD" == "" ]]
then
  curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer
  if [ $? -eq 0 ]
  then
  echo "composer installation successful."
  else
  echo "composer installation failed."
  exit 1
  fi
else
  echo "Composer is installed $COMPOSER_CMD"
fi
}


gulp_check(){

echo "Check if gulp is installed"
    # Test if composer is available
    GULPCMD=$(which gulp)
if [[ "$GULPCMD" == "" ]]
then
  echo "gulp is not installed on the system, please install to continue..."
  exit 1
else
  echo "gulp is installed $GULPCMD"
fi

}


#-- install npm install

npm_check(){

echo "Check if NPM and node are installed locally"

NODE_CMD=$(which node)
if [[ "$NODE_CMD" == "" ]]
then
    echo "node is not installed on the system please install.."
    exit 1
else
    echo "Node is already installed $NODE_CMD"
fi

}

install_dep(){
echo "Installig php dependencies"
#cd $1/$2
cd $base_path
pwd
echo $1 "************"
rm -rf vendor
echo $1 "************"
$COMPOSER_CMD install --prefer-dist
echo $1 "************"
if [ $? -eq 0 ]
then
echo "dependencies installation successful."
#sudo ln -s $1/$2/vendor/drush/drush/drush /usr/local/bin/
else
echo "Composer dependencies installation failed."
exit 1
fi
}


update_dep(){
echo "Installig php dependencies"
#cd $1/$2
cd $base_path
pwd
echo $1 "************"
rm -rf vendor
echo $1 "************"
$COMPOSER_CMD update
echo $1 "************"
if [ $? -eq 0 ]
then
echo "dependencies installation successful."
#sudo ln -s $1/$2/vendor/drush/drush/drush /usr/local/bin/
else
echo "Composer dependencies installation failed."
exit 1
fi
}




#https://another.ink/journal/installing-drush-on-debian-cloudscale-ch


node_install(){
echo "Installig node modules"
NPM_CMD=$(which npm)
cd $base_path
$NPM_CMD install
if [ $? -eq 0 ]
then
echo "node module installation successful."
else
echo "node module installation failed."
exit 1
fi
}

composer_install
gulp_check
npm_check
#install_dep
update_dep
