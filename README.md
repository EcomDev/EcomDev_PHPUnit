Magento PHPUnit Integration
===========================

Magento is a quite complex platform without built in unit test suite, so the code is not oriented on running tests over it.

This extension was created especially for resolving this problem and promoting test driven development practices in Magento developers community. It doesn't change core files or brake your Magento instalment database, because all the system objects are replaced during the run-time with the test ones and a separate database connection is used for tests.

System Requirements
-------------------
* PHP 5.3 or higher
* PHPUnit 3.5
* Magento CE1.4.x-1.5.x/PE1.9.x-PE1.10.x/EE1.9.x-1.10.x

Documentation
-------------

* [EcomDev_PHPUnit version 0.2.0](http://www.ecomdev.org/wp-content/uploads/2011/05/EcomDev_PHPUnit-0.2.0-Manual.pdf)

Also you may follow our related [blogposts](http://www.ecomdev.org/tag/phpunit).

Installation
------------


### Git Repository

1. Checkout extension

        $ git clone git://github.com/IvanChepurnyi/EcomDev_PHPUnit.git

2. Copy extension files into Magento root folder or use [Module Manager](https://github.com/colinmollenhour/modman) for auto-updating of the extension on all your installments

3. Open app/etc/local.xml.phpunit in editor that you are comfortable with:

 1. Specify database credentials that will be used for test suite in
**global/resources/default_setup/connection** node

 2. Specify **base_url** for **secure** and **unsecure** requests in **default/web** node. It is
required for proper controller tests.

4. Run the unit tests first time for installing test database. It will take about 3 minutes.

5. If it shows that there was no tests found, it means that extension was successfully
installed. If it shows some errors than it means that your customizations has install
scripts that relay on your current database data so you should fix them.
 
### Magento Connect

1. Get the extension key from the [extension page](http://www.magentocommerce.com/magento-connect/EcomDev/extension/5717/ecomdev_phpunit) and install it via Magento Connect manager.

2. Open app/etc/local.xml.phpunit in editor that you are comfortable with:

 1. Specify database credentials that will be used for test suite in
**global/resources/default_setup/connection** node

 2. Specify **base_url** for **secure** and **unsecure** requests in **default/web** node. It is
required for proper controller tests.

3. Run the unit tests first time for installing test database. It will take about 3 minutes.

4. If it shows that there was no tests found, it means that extension was successfully
installed. If it shows some errors than it means that your customizations has install
scripts that relay on your current database data so you should fix them.


Issue Tracker
-------------
We use github issue tracker only for contributions management. If you want to post an issue please use our [Issue Tracker](http://project.ecomdev.org/projects/mage-unit)

Contributions
-------------

If you want to take a part in improving our extension please create branches based on dev one. 

###Create your contribution branch: 
   
        $ git checkout -b [your-name]/[feature] dev


Then submit them for pull request. 
