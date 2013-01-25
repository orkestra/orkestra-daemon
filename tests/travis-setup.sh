git clone https://github.com/zenovich/runkit.git
sh -c "cd runkit && phpize && ./configure && make && sudo make install"
PHP_INI_PATH=`php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
echo "extension=runkit.so" >> $PHP_INI_PATH
echo "runkit.internal_override=1" >> $PHP_INI_PATH

git clone https://github.com/sebastianbergmann/php-test-helpers.git
sh -c "cd php-test-helpers && phpize && ./configure && make && sudo make install"
echo "zend_extension=`php -r "echo ini_get('extension_dir');"`/test_helpers.so" >> `php --ini | grep "Scan for additional .ini files in:" | sed -e "s|.*:\s*||"`/z_test_helpers.ini

composer install --dev
