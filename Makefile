# Local override
test =

# Simple, but important
install:
	# Copy files into share directory
	cp -R pear /usr/share/php/stj.me
	# Copy ini file into php.d directory: PHP_INI_SCAN_DIR
	sudo cp conf/php.ini /etc/php5/conf.d/stj-core.ini

test:
	phpunit -c tests/unit/phpunit.xml $(test)