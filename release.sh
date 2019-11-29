#!/bin/bash
set -e

git pull -r

# Remove '-dev' from the version file to prepare for release.
sed -e 's/-dev$//' VERSION > VERSION.tmp
mv -f VERSION.tmp VERSION

# Tag a release
ver="$(cat VERSION)"
git add VERSION
git commit -m "Version $ver"
git tag "$ver"
git push origin "$ver"

# Advance to the next patch release, add the '-dev' suffix back on, and commit the result.
#a=( ${ver//./ } ) && ((a[2]++))
#echo "${a[0]}.${a[1]}.${a[2]}-dev" > VERSION
#git add VERSION
#git commit -m "Back to -dev: $(cat VERSION)"
#git push origin master