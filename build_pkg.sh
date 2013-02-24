#!/bin/bash

DIR=/tmp/`date +%s`
mkdir -p $DIR/mx_router

for I in `ls -1 | grep -v build_pkg.sh`; do
  if [ "$I" != "." ]; then
    cp -r $I $DIR/mx_router/
  fi
done

cd $DIR
for I in `find . -name ".svn"`; do
  rm -rf $I
done
tar cpzf mx_router.tar.gz mx_router

cd -
mv $DIR/mx_router.tar.gz .

rm -rf $DIR
