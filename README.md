# bluebinary queue 

### Requirements:
- php ^8.4
- codeigniter dependencies
## Install for local development:
- create `.env` using default `env` file located in the project's root directory
- fill `app_baseUrl, CI_ENVIRONMENT, REDIS_HOST, REDIS_PORT` with your values
- run commands:
```shell
  composer install
``` 
- development server: `php spark serve`
- monitor console: `php spark app:monitor` - run in main directory of project.

## Install docker: 
- Fill docker-compose with redis environment settings.
```shell
  docker compose up --build -d
```
- monitor production:
```shell 
  docker exec -it production_fpm /bin/bash -c "php spark app:monitor"
```
- monitor development:
```shell 
  docker exec -it development_fpm /bin/bash -c "php spark app:monitor"
```