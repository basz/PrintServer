#!/usr/bin/env bash



bin=`which lpq`

SOFTWARE= LANG=C lpstat -s $bin -P $1

