#!/bin/bash
CURDIR=`dirname "$0"`
SOURCE_DIR=`cd "$CURDIR/backend"; pwd`

rsync --verbose --progress --compress --archive --delete-after \
	  --exclude=".DS_Store" \
	  --exclude="/public/index.php" \
	  --exclude="/public/.htaccess" \
	  --exclude="/data/logs/*" \
	  --exclude="/data/tmp/*" \
	  --exclude="/application/models/entities/proxies/*" \
          --exclude="/application/configs/application.ini" \
          --exclude="/data/database.sqlite" \
	  "$SOURCE_DIR/" \
	  frascati@print.theatercafefrascati.nl:/Library/Server/Web/Data/Sites/nl.theatercafefrascati.print

	  