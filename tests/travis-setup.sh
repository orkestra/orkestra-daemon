git clone https://github.com/zenovich/runkit.git
sh -c "cd runkit && phpize && ./configure && make && sudo make install"
echo "extension=runkit.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

git clone https://github.com/sebastianbergmann/php-test-helpers.git
sh -c "cd php-test-helpers && phpize && ./configure && make && sudo make install"
echo "zend_extension=`php -r "echo ini_get('extension_dir');"`/test_helpers.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

composer install
