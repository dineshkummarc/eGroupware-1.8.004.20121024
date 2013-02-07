#!/bin/sh
vdate=`date "+%Y%m%d"`
cd /opt1/www/noc.ipfw.org/
tar --create --gzip --verbose --exclude='config.php' --exclude='CVS' --exclude='tools' --exclude='sample' --file=/tmp/phpsysinfo-$vdate.tar.gz phpsysinfo-dev
