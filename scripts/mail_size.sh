
TMPFILE=/tmp/sizes.list
DATE=`date '+%Y-%m-%d_%H:%M'`
BASEFOLDER=/home/s11/public_html/utils/files
SIZEFILE=$BASEFOLDER/sizes-$DATE.list
SPAMFILE=$BASEFOLDER/spam-$DATE.list
DBFILE=$BASEFOLDER/db-$DATE.list
LOGFILE=$BASEFOLDER/log-$DATE.list
MAILFILE=$BASEFOLDER/mail-$DATE.list

/usr/bin/du /home/*/domains/*/homes --max-depth=0 -h > $TMPFILE
/usr/bin/du /home/*/homes --max-depth=0 -h >> $TMPFILE
cat $TMPFILE | sort -h > $MAILFILE


