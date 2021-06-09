#!/bin/bash

#mkdir ../public/swagger
php ../vendor/zircote/swagger-php/bin/openapi --bootstrap ./swagger-constants.php --output ../public/swagger ./swagger-v1.php ../app/
