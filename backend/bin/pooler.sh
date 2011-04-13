#!/usr/bin/env bash

#
# This pooler script is intended to run a php script periodicly (less then a minute) 
#
# It will exit if it is allready running.
# It will exit when there is an error
#
# To start it run it with cron every minute or so as root please.
#
# *       *       *       *       *       [PREFIX]bin/pooler.sh >> [PREFIX]/data/logs/pooler.log

# Let shell functions inherit ERR trap.  Same as `set -E'.
set -o errtrace 
# Trigger error when expanding unset variables.  Same as `set -u'.
set -o nounset
#  Trap non-normal exit signals: 1/HUP, 2/INT, 3/QUIT, 15/TERM, ERR
#  NOTE1: - 9/KILL cannot be trapped.
#+        - 0/EXIT isn't trapped because:
#+          - with ERR trap defined, trap would be called twice on error
#+          - with ERR trap defined, syntax errors exit with status 0, not 2
#  NOTE2: Setting ERR trap does implicit `set -o errexit' or `set -e'.
trap onexit 1 2 3 15 ERR

PREFIX=`dirname $(cd ${0%/*} && echo $PWD/${0##*/})`

# find php: pear first, command -v second, straight up php lastly
if test "/opt/local/bin/php" != '@'php_bin'@'; then
    PHP_BIN="/opt/local/bin/php"
elif command -v php 1>/dev/null 2>/dev/null; then
    PHP_BIN=`command -v php`
else
    PHP_BIN=php
fi

function onexit() {
    # cleanup
    local exit_status=${1:-$?}
    echo Exiting `basename "$0"` with $exit_status

    rmdir /tmp/pooler.lock
    
    exit $exit_status
}

if mkdir /tmp/pooler.lock 2>/dev/null
then
   echo "starting pooling"
   
   while [ 1 ];
   do 
      sudo -u _www $PHP_BIN -d safe_mode=Off -f $PREFIX/pooler.php -- "--action=cli.update-status"

      sleep 5;
   done

else
   #echo "pooling allready in place"
   exit 0
fi