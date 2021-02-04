# Docker + Symfony Stack

![PHP 7.4](https://img.shields.io/badge/PHP-7.4-8892BF.svg)
![MariaDB](https://img.shields.io/badge/Database_Server-MariaDB_10.5-c0765a.svg)
![nginx](https://img.shields.io/badge/Webserver-nginx_1.19-009447.svg)
![Redis](https://img.shields.io/badge/Cache_Engine-Redis_6-D92A2A.svg)


## Basics

This repo contains basic docker images to run Symfony app. If you want to run this repo just: 

1. Download contents
2. Go into a directory with docker-compose.yml
3. Add .env file with at least `DATABASE_URL=mysql://user:password@db:3306/database` row
4. Run ``docker-compose up`` in your terminal 
5. Connect to php pod using ``docker-compose exec php bash``
6. Install vendor using ``composer install`` in app folder
7. Now you can access Symfony app on localhost.
8. Optional: you can fill elasticsearch by using command inside of php node: ``bin/console feed_elastic_products``

Note: Be aware, that this is only <strong>mere example</strong> thus there is set only this paths: 

http://localhost/product/create/{content}

http://localhost/product/detail/{id}

For simplicity and ease of use both URIs accepting GET requests. From default system example is 
configured to cache responses for 3600s. Content could be anything, created entity is stored in both DBs.
Id is last id incremented by one same in both DBs. Maybe this is not optimal solution,
because there could be as switch in .env file (will be added later).   

<strong>Working ids for elastic after 8th step: 1, 2, 3</strong>

Thank you for feedback.