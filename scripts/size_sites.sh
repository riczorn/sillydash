
TMPFILE=/tmp/sizes.list
DATE=`date '+%Y-%m-%d_%H:%M'`
BASEFOLDER=/home/s11/public_html/utils/files
SIZEFILE=$BASEFOLDER/sizes-$DATE.list
SPAMFILE=$BASEFOLDER/spam-$DATE.list
DBFILE=$BASEFOLDER/db-$DATE.list
LOGFILE=$BASEFOLDER/log-$DATE.list
MAILFILE=$BASEFOLDER/mail-$DATE.list

/usr/bin/du /home/*/domains/ --max-depth=1 -h | egrep -v "domains/$" > $TMPFILE
/usr/bin/du /home/ --max-depth=1 -h >> $TMPFILE
cat $TMPFILE | sort -h > $SIZEFILE

/usr/bin/du /home/*/domains/*/homes/*/Maildir/.spam --max-depth=0 -h > $TMPFILE
/usr/bin/du /home/*/homes/*/Maildir/.spam --max-depth=0 -h >> $TMPFILE
cat $TMPFILE | sort -h > $SPAMFILE

/usr/bin/du /home/*/domains/*/homes --max-depth=0 -h > $TMPFILE
/usr/bin/du /home/*/homes --max-depth=0 -h >> $TMPFILE
cat $TMPFILE | sort -h > $MAILFILE

/usr/bin/du /var/lib/mysql --max-depth=1 -h > $TMPFILE
cat $TMPFILE | sort -h > $DBFILE

/bin/ls /var/log/virtualmin/ -gah --time-style long-iso | grep -v .gz | sed 's/^[-rwx]\+  1 [a-z-]\+ //g' | egrep -v '^ +0 ' | sort -h  > $LOGFILE
