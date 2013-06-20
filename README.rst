=========================================================
 django-php-bridge - Authentication betwen Django and PHP
=========================================================

*******
Purpose
*******

`Django-PHP-Bridge`_ is a Django authentication backend that allows your Django
project to seemlessly pass users to and from a PHP application. This allows
you to build an application with both PHP and Django components while keeping a
solid user experience.

Whether you're porting from PHP to Django, from Django to PHP, integrating two
distinct applications or building a hybrid app, `Django-PHP-Bridge`_ aims to
help make the distinction irrelevant to your users.

****************
General Approach
****************

There are several different ways to approach this problem, mostly revolving
around which side's default behavior you use and which side needs more
customization. In general, we've taken (and this documentation assumes) that
Django's default behavior should be used where possible. However, it is
completely possible to use the provided session backend as part of a more
PHP-centric approach.

Django Defaults Used
====================

* ``django.contrib.auth.models.User`` is used to store Users, with the standard
  django Profile extension recommended for additional fields.
* The database is used as the session store, but this could easily be
  customized.

PHP Defaults Used
=================

* ``django-php-bridge.backends.db`` uses PHP's native serialization format to
  store session data.

*****
Usage
*****

This usage guide assumes a few things about your setup.

1. You're using Django's ``django.contrib.auth.backends.ModelBackend`` as your
  authentication backend and you want to use ``django.contrib.auth.models.User``
  for storing basic user information.
2. Your PHP and Django projects share a database. That's how the session
  coordination is accomplished.
3. If you had a custom schema to store your user and profile information,
  you've already converted it to Django's schema.

Usage: Django Side
==================

On the django side of your project, installation is fairly simple.

Install `Django-PHP-Bridge`_::

  $ pip install django-php-bridge

Configure your Django project to use the PHP-compatible session backend by
adding the following to your ``settings.py`` ::

  SESSION_ENGINE = 'django_php_bridge.backends.db'
  SESSION_COOKIE_NAME = 'PHPSESSID'

Let your Django project know that you'll be using the PHP side of your project
to do actual logins. You do this by setting the ``LOGIN_URL`` setting in
``settings.py`` to point to the PHP-served URL that will be handling your
login. eg.::

  LOGIN_URL = '/'

Usage: PHP Side
===============

Installation and setup on the PHP side is complicated by the fact that PHP
applications are all generally very different. A helper/guide for using
Django-PHP-Bridge with common PHP frameworks like `CakePHP`_ and `Symfony`_
would be easier to write (and would be an appreciated contribution).

In general, the steps involved are:

Create and Use a Compatible Session Table
-----------------------------------------

The session table you use needs to be compatible with the schema that Django
expects. The exact SQL to create the table will vary, but the Django Docs on
the `sql command`_ show us an easy way to obtain the SQL from your django
project by running::

  $ django-admin.py sql sessions

If you're using MySQL, you can use ``contrib/mysql/django_session_table.sql``

Alternatively, you can use Django's syncdb to create the table::

  $ manage.py syncdb

.. _`sql command`: http://docs.djangoproject.com/en/dev/ref/django-admin/#sql-appname-appname

Place the Appropriate Session-Handler on Every Page
---------------------------------------------------

PHP allows for `custom session handlers`_ to be defined, which allows us to
use the django_session table we created above. The session handler you use will
need to be aware of the django_session table's schema and you'll need to
register this session handler on every page *before* calling ``session_start();``.

An example session handler class is provided in
``contrib/php/djangoSession.class.php``.

.. _`custom session handlers`: http://php.net/manual/en/session.customhandler.php

Create and Use a Compatible User Table
--------------------------------------

In order for any reasonable level of integration, most projects will need to
know who users are on both the PHP and Django side. Because most general
PHP projects vary greatly in how they store their user information, if coming
from an existing PHP project, this will probably require some custom work to
convert user data. Django applications generally use a User model plus a
Profile model to store user data. See the `Django Auth Documentation`_ for
details.

Included is an example of a PHP class that relies on the same schema as
``django.contrib.auth.models.User`` as an example and starting point. It knows
a little bit about how Django stores passwords and what fields are necessary,
but it will certainly need tweaking to work with your existing PHP
project. The file is located at ``contrib/php/user.class.php``.

Suggestions and contributions to make this part of the integration process
easier are welcome.

.. _`Django Auth Documentation`: http://docs.djangoproject.com/en/1.3/topics/auth/

Configure URLs Handled by PHP vs Django
---------------------------------------

The final piece of integration will be to tell your web server how to determine
if a given request should be resolved by the Django side or by the PHP side.
This means changing your configuration so that for example, everything at
``/account`` is served by Django and everything at ``/blog`` is served by PHP.
If you're using different domains or subdomains to separate the side of your app,
then you can ignore this step.

Generally, to keep this part sane, you'll want to file good URL practices and
separate which side of your project handles particular tasks and domain objects.
Django's application-centric ``urls.py`` configuration makes this easy.
Particular attention should be paid with regards to which side of your project
should handle logging in and logging out. It's generally simpler if either
only Django or only PHP handles both logging in and logging out users and
probably simpler if that same side handles registration and account editing.

In the case of `Apache2`_ running `mod_wsgi`_ for Django and mod_php (or
similar) for PHP, the separation can be accomplished inside a VirtualHost file.
An example vhost file is provided at ``contrib/apache2/vhost_conf``.

*******
History
*******

This authentication backend was extracted from code used in production by
a saas policy management start called `PolicyStat`_ during their multi-year
conversion from a PHP application to a `Django`_ application. You can read
a bit about their `PHP to Django Conversion`_.

`PolicyStat`_ has sense converted to 100% Django and is no longer using this
approach in production, but the hope is that someone who is will be interested
in taking an active role in this project.

************
Contributing
************

All development on Django-PHP-Bridge happens at Github: http://github.com/winhamwr/django-php-bridge

You are highly encourage to contribute to the improvement of Django-PHP-Bridge.
We would especially love contributions along the lines of how to integrate with
specific PHP frameworks.

***********
Bug tracker
***********

If you have any suggestions, bug reports or questions please report them
to our issue tracker at http://github.com/winhamwr/django-php-bridge/issues/

Also feel free to tweet @weswinham on twitter.


.. For full documenation, you can build the `sphinx`_ documentation yourself or
.. vist the `online Django-PHP-Bridge documentation`_

.. _`Django-PHP-Bridge`: http://github.com/winhamwr/django-php-bridge/
.. _`Policystat`: http://policystat.com
.. _`Django`: http://www.djangoproject.com/
.. _`CakePHP`: http://cakephp.org/
.. _`Symfony`: http://www.symfony-project.org/
.. _`Apache2`: http://httpd.apache.org/
.. _`mod_wsgi`: http://www.modwsgi.org/
.. _`PHP to Django Conversion`: http://devblog.policystat.com/php-to-django-changing-the-engine-while-the-c
.. _`sphinx`: http://sphinx.pocoo.org/
.. _`online Django-PHP-Bridge documentation`: http://readthedocs.org/projects/django-php-bridge/

