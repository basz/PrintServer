#!/bin/bash
#!/bin/bash
CURDIR=`dirname "$0"`
PREFIX=`cd "$CURDIR/.."; pwd`

rm -f $PREFIX/data/database.sqlite && \
php $PREFIX/bin/doctrine-orm.php orm:schema-tool:create && \
$PREFIX/bin/fixPermissions.sh development && \
php $PREFIX/bin/pooler.php --action=api.setup