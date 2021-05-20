# GAB
GAB: Game Assisted Brainstorming. A new approach to brainstorming with multiple people.

Installation instructions:

Create database:
Run the db\\gab.sql script on a mysql database.
host: same server as REST API server
database name: "gab
database username: "root
database password: ""

Download composer:
curl -sS https://getcomposer.org/installer | php

Installe composer componets in directory api:
cd api
composer install

Run swagger documentation:
http://{hostname}/api/documentation/

Test REST-API for MODERATOR or FACILITATOR:
1) /api/user/register/
2) /api/user/login/
3) copy token to Authorize (Bearer Authorization)
4) execute any rest call for moderator tool

Test REST-API for PARTICIPANT:
1) /api/participant/connect/
3) copy token to Authorize (Bearer Authorization)
4) execute any rest call for client tool
