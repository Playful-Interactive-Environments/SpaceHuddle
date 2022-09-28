# SpaceHuddle
SpaceHuddle: Game Assisted Brainstorming. A new approach to brainstorming with multiple people.

## SpaceHuddle API

SpaceHuddle uses a REST API for exchanging data between the different parts of the application (backend, moderator, client). The API's code is located in the `api` directory.

### Requirements

The SpaceHuddle API requires the following setup:

- a web server: tested with [Apache 2.4](https://httpd.apache.org/),
- an SQL database instance: tested with [MariaDB 10.4](https://mariadb.org/),
- [PHP 8.0](https://www.php.net/),
- [Composer](https://getcomposer.org/) dependency manager.

### Installation and running of the API

1. Install a local web development environment that meets the requirements above. [XAMPP](https://www.apachefriends.org/) works well, setups using [Vagrant](https://www.vagrantup.com/), [Docker](https://www.docker.com/), or other virtual machines will work as well.
2. Check out the GitHub project on your webserver (for XAMPP for example in the directory `\xampp\htdocs\SpaceHuddle`).
3. If not included in your setup, install Composer following the [instructions](https://getcomposer.org/download/) for your operating system.
4. Open a shell/terminal/command prompt, change to the `api` directory and install the dependencies by calling `composer install`.
5. Import `api/resources/schema.sql` into your database. This will create a database called "spacehuddle". Create a MySQL user with full permissions on the database. Enter your database credentials in `api/config/database.php`.
6. Create a public and private key and copy them into the directory `api/resources/keys`
    -  `openssl genrsa -out private.pem 2048`
    -  `openssl rsa -in private.pem -outform PEM -pubout -out public.pem`
7. Copy `api/config/env.example.php` to `api/config/env.php` and adjust the properties
8. Start web server and database

### API Documentation

API documentation and testing are done using [Swagger](https://swagger.io/). The documentation is located in `api/documentation`.

To run it, point your browser to <http://{hostname}/{path}/api/documentation/>, e.g., <http://localhost/api/documention> or <http://localhost/SpaceHuddle/api/documentation>.

To test the various API endpoints, select one from the list, adapt the proposed request body if necessary and press "Execute". You will see the server response below.

To test for the *moderator* or *facilitator*, use the following steps:

1. call `/api/user/register/` and  enter any details for `username`, `password` and `password_confirmation`,
2. call`/api/user/login` and enter your username and password,
3. copy the value for `access_token`, click the button "Authorize" in the upper right corner and enter the token in the field for `bearerAuth`,
4. execute any arbitrary REST call for the moderator tool.

To test for a *participant*, use the following steps:

1. call `api/session` to create a new session with given details and copy the received value for `connection_key`,
2. call `/api/participant/connect/` and enter the key in `session_key`,
3. copy the value for `access_token`, click the button "Authorize" in the upper right corner and enter the token in the field for `bearerAuth`,
4. execute any arbitrary REST call for the client tool.

## SpaceHuddle frontend

### Installation
1. Adjust the properties in the `frontend/.env` file
2. Install dependencies with: `npm install`

#### Compiles and hot-reloads for development
You can use either `npm start` or `npm run serve`.

#### Compiles and minifies for production
```
npm run build
```

#### Run your unit tests
```
npm run test:unit
```

#### Lints and fixes files
```
npm run lint
```

#### Customize configuration
See [Configuration Reference](https://cli.vuejs.org/config/).
