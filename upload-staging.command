#!/bin/bash
CURDIR=`dirname "$0"`
SOURCE_DIR=`cd "$CURDIR/backend"; pwd`

rsync --progress --compress --archive --delete-after \
	  --exclude=".DS_Store" \
	  --exclude="/public/index.php" \
	  --exclude="/public/.htaccess" \
	  --exclude="/data/logs/*" \
	  --exclude="/data/tmp/*" \
	  --exclude="/data/database.sqlite" \
	  "$SOURCE_DIR/" \
	  anna@server.restaurantanna.nl:/Sites/nl.restaurantanna.print

