

all:
	clear
	-rm -f /tmp/test0001.php
	-rm -f WizTrader.phar
	/php7/bin/php builder.php
	#/php7/bin/php  /php7/WizTrader/WizTrader.phar
	/php7/bin/php  /php7/WizTrader/WizTrader.phar ./test0001.job 
	cat /tmp/test0001.php
	#./test0001.plc 
	#./test0001.mth 
	#./test0001.ds 
	
	
