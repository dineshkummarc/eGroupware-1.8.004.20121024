#!/bin/sh

rcs2log \
  -u "precision	Uriah Welcome	precision at users.sf.net" \
  -u "jengo	Joseph Engo	jengo at users.sf.net" \
  -u "neostrider	Joseph King	neostrider at users.sf.net" \
  -u "webbie	Webbie	webbie at ipfw.org" \
`find . -type f | egrep -v "(CVS|README|ChangeLog|.gif)" | xargs | sed -e 's/\.\///g'` \
> ChangeLog
