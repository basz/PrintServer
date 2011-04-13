#!/usr/bin/env bash

# lprm -P Beehives_HP 355

bin=`which lprm`

$bin -P $1 $2

