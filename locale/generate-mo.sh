#!/bin/sh
msgfmt -c -o /var/www/112help/locale/fr_BE.UTF-8/LC_MESSAGES/112help.mo /var/www/112help/locale/fr_BE.UTF-8/LC_MESSAGES/112help.po
msgfmt -c -o /var/www/112help/locale/nl_BE.UTF-8/LC_MESSAGES/112help.mo /var/www/112help/locale/nl_BE.UTF-8/LC_MESSAGES/112help.po
service php7.0-fpm reload