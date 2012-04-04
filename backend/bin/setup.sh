#!/bin/bash
#!/bin/bash
CURDIR=`dirname "$0"`
PREFIX=`cd "$CURDIR/.."; pwd`

# Make sure only root can run our script
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root (try sudo "$0" "$@")" 1>&2
   exit 1
fi

rm -f $PREFIX/data/database.sqlite && \
php $PREFIX/bin/doctrine-orm.php orm:schema-tool:create && \
$PREFIX/bin/fixPermissions.sh development && \
php $PREFIX/bin/pooler.php --action=api.setup