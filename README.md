# GAB
GAB: Game Assisted Brainstorming. A new approach to brainstorming with multiple people.

## GAB API

GAB uses a REST API for exchanging data between the different parts of the application (backend, moderator, client). The API's code is located in the `api` directory.

### Requirements

The GAB API requires the following setup:

- a web server: tested with [Apache 2.4](https://httpd.apache.org/),
- an SQL database instance: tested with [MariaDB 10.4](https://mariadb.org/),
- [PHP 8.0](https://www.php.net/),
- [Composer](https://getcomposer.org/) dependency manager.

### Installation

1. Install a local web development environment that meets the requirements above. [XAMPP](https://www.apachefriends.org/) works well, setups using [Vagrant](https://www.vagrantup.com/), [Docker](https://www.docker.com/), or other virtual machines will work as well.
2. If not included in your setup, install Composer following the [instructions](https://getcomposer.org/download/) for your operating system.
3. Open a shell/terminal/command prompt, change to the `api` directory and install the dependencies by calling `composer install`.
4. Import `db/gab.sql` into your database. This will create a database called "gab". Create a MySQL user with full permissions on the database. Enter your database credentials in `api/config/database.php`.

### API Documentation

API documentation and testing are done using [Swagger](https://swagger.io/). The documentation is located in `api/documentation`.

To run it, point your browser to <http://{hostname}/{path}/api/documentation/>, e.g., <http://localhost/api/documention> or <http://localhost/GAB/api/documentation>.

To test the various API endpoints, select one from the list, adapt the proposed request body if necessary and press "Execute". You will see the server response below.

To test for the *moderator* or *facilitator*, use the following steps:

1. call `/api/user/register/`,
2. call`/api/user/login`,
3. copy the token to authorize (bearer authorization),
4. execute any arbitrary REST call for the moderator tool.

To test for a *participant*, use the following steps:

1. call `/api/participant/connect/`,
2. copy the token to authorize (bearer authorization),
3. execute any arbitrary REST call for the client tool.
