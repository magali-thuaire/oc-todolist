# TODOLIST

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/24620ce050ad40cd9afc507fdd3de9e8)](https://www.codacy.com/gh/magali-thuaire/oc-todolist/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=magali-thuaire/oc-todolist&amp;utm_campaign=Badge_Grade)

## About

Symfony application which needs to be upgraded, tested and documented.  
Work carried out as part of the training course "Application Developer - PHP / Symfony" on OpenClassrooms.

**Framework and libraries**

Framework: <span style="color:pink">**Symfony 6.0.9**</span>  
Dependencies manager: **Composer 2.3.6**

## Setup

**Get the git Repository**

Clone over SSH

```
git clone git@github.com:magali-thuaire/oc-todolist.git
```

Clone over HTTPS

```
git clone https://github.com/magali-thuaire/oc-todolist.git
```

**Download Composer dependencies**

Make sure you have [Composer installed](https://getcomposer.org/download/)
and then run:

```
composer install
```

**Server Setup**

Configure your <span style="color:green">**php.ini**</span> file.

```
Apache 2.4.46
PHP version >=8.0.2
MySQL version >=8.0.28
```

**Database Setup**

The code comes with a `docker-compose.yaml` file.
You will still have PHP installed
locally, but you'll connect to a database inside Docker.

First, make sure you have [Docker installed](https://docs.docker.com/get-docker/)
and running. To start the container, run:

```
docker-compose up -d
```

Next, build the database and execute the migrations with:

```
# "symfony console" is equivalent to "bin/console"
# but its aware of the database container
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate
symfony console doctrine:fixtures:load
```

(If you get an error about "MySQL server has gone away", just wait
a few seconds and try again - the container is probably still booting).

If you do *not* want to use Docker, just make sure to start your own
database server and update the `DATABASE_URL` environment variable in
`.env` or `.env.local` before running the commands above.

**Start the Symfony web server**

You can use Nginx or Apache, but Symfony's local web server
works even better.

To install the Symfony local web server, follow
"Downloading the Symfony client" instructions found
here: [Symfony CLI](https://symfony.com/download) - you only need to do this
once on your system.

Then, to start the web server, open a terminal, move into the
project, and run:

```
symfony serve -d
```

(If this is your first time using this command, you may see an
error that you need to run `symfony server:ca:install` first).

Now check out the site at `https://localhost:8000`

**Optional: Webpack Encore Assets**

This app uses Webpack Encore for the CSS, JS and image files. But
to keep life simple, the final, built assets are already inside the
project. So... you don't need to do anything to get thing set up!

If you *do* want to build the Webpack Encore assets manually, you
totally can! Make sure you have [yarn](https://yarnpkg.com/lang/en/)
installed and then run:

```
yarn install
yarn encore dev --watch
```

## Tests

**Configure PHP Unit**

If you want to change PHP Unit configuration, use phpunit.xml.dist file and rename it phpunit.xml.
You must define DATABASE_URL environment variable for tests. By default, tests use Docker integration.
```
<env name="DATABASE_URL" value="mysql://root:root@127.0.0.1:8889/todolist_test?serverVersion=5.7" />
```

**Run the tests**

To run all tests, use the following command:
```
symfony run bin/phpunit
```

See more details and options about command-line test runner in  [PHP Unit documentation - EN / FR.](https://phpunit.readthedocs.io/en/latest/textui.html)

## Default Connexions

```
ROLE USER
login: user
password: todolist
```