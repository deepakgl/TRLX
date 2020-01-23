#!/bin/bash


#DRUSHCMD=$(which drush)

if [[ "$1" == "" ]]
then
  echo "Please pass the dependencies installation path"
  echo $1 "*******"
  exit 1
fi

CODE_PATH=$1
rundrush=$2
#DRUSHCMD="$CODE_PATH/../vendor/drush/drush/drush"
DRUSHCMD="$CODE_PATH/vendor/drush/drush/drush"


enable_modules(){
        cd $build_path
        echo "Installing modules"
        cd $CODE_PATH
        $DRUSHCMD cr
        $DRUSHCMD cim -y
        $DRUSHCMD cr
        if [ $? != 0 ]
        then
        echo "Enabling standard distribution modules failed."
        exit 1
        else
        echo "Standard  distribution modules enabled successfully."
        fi
}


# Function to clear the cache.
clearCache() {
  echo "Clearing all caches..."
  cd $CODE_PATH
  $DRUSHCMD cr
  if [ $? -eq 0 ]
  then
    echo "drush cc all successful!"
  else
    echo "Build failed: error in running drush cc all!"
    exit 1
  fi
}


getVersion() {
  echo "Clearing all caches..."
  cd $CODE_PATH
  $DRUSHCMD version
  if [ $? -eq 0 ]
  then
    echo "drush command exec all successful!"
  else
    echo "Build failed: error in running drush command all!"
    exit 1
  fi
}

if [ $rundrush -eq 1 ]
then
enable_modules
clearCache
getVersion
else
getVersion
fi
