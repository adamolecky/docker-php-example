# Docker + Symfony Stack

![PHP 7.4](https://img.shields.io/badge/PHP-7.4-8892BF.svg)
![MariaDB](https://img.shields.io/badge/Database_Server-MariaDB_10.5-c0765a.svg)
![nginx](https://img.shields.io/badge/Webserver-nginx_1.19-009447.svg)
![Redis](https://img.shields.io/badge/Cache_Engine-Redis_6-D92A2A.svg)


## Basics

This repo contains basic docker images to run Symfony app. If you want to run this repo just: 

1. Download contents
2. Go into a directory with docker-compose.yml
3. Add .env file with at least `DATABASE_URL=` row
4. Create folder ``Entity`` in ``./src`` (Complete path './src/Entity')
5. Run ``docker-compose up`` in your terminal 
6. Connect to php pod using ``docker-compose exec php bash``
7. Install vendor using ``composer install`` in app folder
8. Now you can access Symfony app on localhost.

Note: Be aware, that this is only <strong>mere example</strong> thus there is set only one path: 

http://localhost/{id}

<strong>Working ids: 1, 2</strong>

Also there is no connector to DB & Redis, but setting is really easy. 

Thank you for feedback.