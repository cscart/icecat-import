
## Генерация демо-данных
Для импорта данных необходимо выполнить консольную команду:

`php artisan cs:data-importer:products --login=<icecat_login> --password=<icecat_password> --limit=<products_count_limt> --lang=<two_digit_lang_code> {--without-images}`

Необязательный флаг `—without-images` следует указывать, если не нужно скачивать картинки.

Для отдельного скачивания картинок необходимо выполнить команду:

`php artisan cs:data-importer:images --login=<icecat_login> --password=<icecat_password> --limit=<products_count_limt> --lang=<two_digit_lang_code>`

Для ручного снятия/разворачивания бэкапа с демо-данными (команды выполняются из корня CS Cart)

1. Создать архив с картинками.

   `tar cf - storage/app/images/ | pigz -9 -p 4 > images.tar.gz`

   (pigz позволяет в многопоточность, параметр -p - кол-во потоков https://ostechnix.com/pigz-compress-and-decompress-files-in-parallel-in-linux/)

2. Снять дамп БД

   MySQL:
   `mysqldump -u<username> -p<password> <dbname> > <dbname>.sql`

   Postgres:
   `pg_dump -U <username> <dbname> > <dbname>.sql`

3. Перенести архив с картинками и распаковать.

   `tar xzf images.tar.gz`

4. Перенести дамп БД и восстановить.

   MySQL:
   `mysql -u<username> -p<password> <dbname> < <dbname>.sql`

   Postgres:
   `psql -f <dbname>.sql -d <dbname> -U <username>`
