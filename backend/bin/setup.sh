#!/bin/bash

rm -f ../data/database.sqlite && php doctrine-orm.php orm:schema-tool:create && ./fixPermissions.sh development && php ./pooler.php --action=api.setup