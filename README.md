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



// TODO 
1) Дропнуть столбцы started_at и created_at там, где они не нужны
2) Реестр компаний
3) Реестр оборудования?
4) Подумать о структуре журнала