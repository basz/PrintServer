#!/bin/bash

PREFIX=`dirname $(cd ${0%/*} && echo $PWD/${0##*/})`

APPLICATION_ENV=$1

case "$APPLICATION_ENV" in
    'development' )

        find $PREFIX"/../data" -maxdepth 1 -type d -print -exec chmod ugo+rwx {} \;
        find $PREFIX"/../data" -type f -print -exec chmod ugo+rwx {} \;

        find $PREFIX"/../bin/cups" -type f -print -exec chmod ugo+rwx {} \;

        chmod ugo+rw,ugo-x $PREFIX"/../data/database.sqlite"

        #
        chmod ugo+x $PREFIX"/../bin/doctrine"
        chmod ugo+x $PREFIX"/../bin/pooler.sh"
        chmod ugo+x $PREFIX"/../bin/pooler.php"
        chmod ugo+x $PREFIX"/../bin/cups/"*

        chmod ugo+w $PREFIX"/../application/models/entities/proxies"
        ;;
    'testing' )
        find $PREFIX"/../data" -maxdepth 1 -type d -print -exec chmod ugo+rwx {} \;
        ;;
    'staging' )
        find $PREFIX"/../data" -maxdepth 1 -type d -print -exec chmod ugo+rwx {} \;
        ;;
    'production' )
        find $PREFIX"/../data" -maxdepth 1 -type d -print -exec chmod ugo+rwx {} \;
        ;;
    * )
        echo "Incorrect APPLICATION_ENV in fixPermissions";;
esac
