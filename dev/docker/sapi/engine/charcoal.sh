#!/bin/bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

PHP=`which php`
BIN=$(dirname $0)/charcoal.php

old="${IFS}"
IFS=";"
ARGS="'$*'"
IFS=${old}

if [[ -f "${BIN}" ]]
then
${PHP} -f ${BIN} ${ARGS}
else
echo -e "\e[31mUnable to locate charcoal burner script\e[0m"
fi
