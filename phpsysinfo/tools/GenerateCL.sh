#!/bin/sh
# run this in phpsysinfo home dir
#
rm -f ChangeLog.bak ChangeLog /tmp/xx.txt /tmp/ChangeLog
find . -type f | sed -e 's/\.\///g' | grep -v -w CVS | grep -v -x 'config.php'| grep -v '^tools/' | grep -v -x 'genlog.sh' > /tmp/xx.txt
cat /tmp/xx.txt | xargs ./tools/cvs2cl.pl -t -f /tmp/ChangeLog
sed -e 's/webbie$/webbie (webbie at ipfw dot org)/g' \
    -e 's/precision$/precision    Uriah Welcome (precision at users.sf.net)/g' \
    -e 's/jengo$/jengo    Joseph Engo (jengo at users.sf.net)/g' \
    -e 's/neostrider$/neostrider    Joseph King (neostrider at users.sf.net)/g' \
    -e 's/bigmichi1$/bigmichi1     Michael Cramer (bigmichi1 at users.sf.net)/g' \
/tmp/ChangeLog > ChangeLog
rm -f /tmp/xx.txt /tmp/ChangeLog
