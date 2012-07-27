#Backend for gw2cartographers.com


#How to install
Run the following commands
```curl -s http://getcomposer.org/installer | php
php composer.phar install```

To update the submodule, just run ```git submodule update --init --recursive``` after the clone.

Then you must install the database. Import the structure.sql file to your database.

Now go to /admin/users you should be able to log in with gw2c/gw2c. We strongly recommend you to create a new user and to delete the gw2c user.
