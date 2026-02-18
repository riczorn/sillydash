#!/bin/bash
DATE=`date '+%Y-%m-%d'` 
BASEFOLDER=/home/s11/public_html/utils/files 
VMFILE=$BASEFOLDER/virtualmin-$DATE.list
echo   Processing $1
echo $1 >> $VMFILE
DB=`virtualmin list-databases --domain $1 --name-only | tr '\n' ',' `
USER=`virtualmin list-domains --domain $1 --simple-multiline | egrep 'Username|Home directory' | grep -v 'Username for my' `
echo "    DB: $DB" >> $VMFILE
echo "$USER" >> $VMFILE


