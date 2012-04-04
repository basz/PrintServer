#!/bin/bash
CURDIR=`dirname "$0"`
SOURCE_DIR=`cd "$CURDIR/backend"; pwd`

rsync --verbose --progress --compress --archive --delete-after \
	  --exclude=".DS_Store" \
	  --exclude="/public/index.php" \
	  --exclude="/public/.htaccess" \
	  --exclude="/data/logs/*" \
	  --exclude="/data/tmp/*" \
	  --exclude="/application/configs/application.ini" \
	  --exclude="/application/configs/printers-*.ini" \
	  --exclude="/application/models/entities/proxies/*" \
	  --exclude="/data/database.sqlite" \
	  "$SOURCE_DIR/" \
	  anna@server.restaurantanna.nl:/Sites/nl.restaurantanna.print

