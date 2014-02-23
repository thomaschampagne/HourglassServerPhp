rm -rf ../../coverageReport
phpunit --configuration unitTestConfig.xml --coverage-html ../../coverageReport TestSuite.php
chown -R www-data. ../../coverageReport
chmod -R 755 ../../coverageReport

