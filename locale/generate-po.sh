#!/bin/sh
cd /var/www/112help/
xgettext --from-code=utf-8 -F -L PHP -j "./locale/fr_BE.UTF-8/LC_MESSAGES/112help.po" -d 112help -p "./locale/fr_BE.UTF-8/LC_MESSAGES" *.php
xgettext --from-code=utf-8 -F -L PHP -j "./locale/nl_BE.UTF-8/LC_MESSAGES/112help.po" -d 112help -p "./locale/nl_BE.UTF-8/LC_MESSAGES" *.php
