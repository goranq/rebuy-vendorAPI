# rebuy-vendorAPI

Test assignment for Junior Web Developer position at Rebuy

## Usage

To start the web server serving API, from project root directory, run
```bash
docker-compose up
```

## API Endpoints

This API uses Bearer/Token authentication (see **Database** section for
credentials/API token). All API endpoints require authentication.  

The following endpoints are available:
- `GET /product/` - get collection of products (all products in the database)
- `GET /product/{id}` - get product identified by `{id}`
- `POST /product/` - create a new product (product data in JSON format)
- `PUT /product/{id}` - update a product identified by `{id}` (product data in JSON format)
- `DELETE /product/{id}` - delete a product identified by `{id}`

API call and response samples are provided in [API_samples.md](API_samples.md) file.

## Project structure

### Docker image
This project uses [official PHP docker image](https://hub.docker.com/_/php) which is built upon Apache web
server (`php:8.2.8-apache`). However, to enable Apache's `mod_rewrite`, a custom
docker image is needed, which is defined in `Dockerfile`. In case a different
PHP version is needed, `Dockerfile` should be edited. `docker-compose.yml` is
written so that it uses this slightly customized PHP image. If needed,
additional services such as database server could be added to 
`docker-compose.yml` file.  

Used PHP docker image uses `php.ini` suitable for development process. To use
version suitable for production, the following command should be added to
`Dockerfile`:

```dockerfile
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
```

### Database
`sqlite` is used as a database engine. `PDO` is used for interfacing with the
database. Database structure, sample user and some product data will be
automatically created. Generated username and password are the following:
```
username: rebuy
password: rocks
```
API token is generated randomly. Also, for simplicity, token does not expire,
which should not be the case in production application. To avoid creating sample
data, comment out the following line in `app/index.php` file:

```php
$db->_bootstrapDB();
```

#### Important

---

Please note, if no sample data is created, tables need to be created manually.
Additionally, there is currently no way to create user account, so effectively,
application can't be used without sample data.

---

All data about the products is kept in a single table, no additional tables and
relations are used. The only reason for such database structure is simplicity.
Ideally, information about product manufacturer and product category should be
kept in a separate tables and relations to main (product) table should be set
up.

### Files

- `.htaccess` - Apache web server configuration file used to enable REST API
friendly URLs (route all requests to `index.php` file).
- `docker-compose.yml` - Used by `docker-compose` command. Additioanl services
can be added here.
- `Dockerfile` - Used to build custom PHP docker image (to enable
`mod_rewrite`). Consumed in `docker-compose.yml` file.
- `app/db.php` - File containing database oriented PHP code.
- `app/index.php` - File containing main API logic, routing all the API calls.
- `app/rebuy.sqlite` - SQLite database file, contains all the product and user
data.