# bluebinary queue 

### Requirements:
- php ^8.4
- codeigniter dependencies
### Install:
- create `.env` using default `env` file located in the project's root directory
- fill `app_baseUrl, CI_ENVIRONMENT, REDIS_HOST, REDIS_PORT` with your values
- run commands:
```shell
  composer install
``` 
- development server: `php spark serve`

### Install docker: 
```shell
  docker compose --up build
```