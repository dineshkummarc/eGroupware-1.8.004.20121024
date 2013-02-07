#!/bin/sh

# remove the CVS directories
find . -type d -name CVS -exec rm -fr {} \;

echo "Remeber to Bump the version number in README and index.php"
