Для локального запуска в .env  добавить:
FILE_UID=1000
FILE_GID=1000

WWW_PORT_VITE=5173
WWW_PORT=8001
MYSQL_PORT=3306
PHPMYADMIN_PORT=8000

после запустить docker composer up
сайт будет доступен по ссылке http://localhost:8001/
phpmyadmin будет доступен по ссылке http://localhost:8000/