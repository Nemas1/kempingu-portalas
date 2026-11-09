FROM mysql:8.4

ENV MYSQL_DATABASE=kempingai MYSQL_USER=stud

# Lenteles sukuria MySQL pirmą kartą paleidus tuščią duomenų bazę.
COPY database/schema.sql /docker-entrypoint-initdb.d/01-schema.sql

# PHP pradedamas tik tada, kai MySQL jau priima užklausas į mūsų lenteles.
HEALTHCHECK --interval=5s --timeout=5s --start-period=20s --retries=40 \
    CMD MYSQL_PWD="$MYSQL_PASSWORD" mysql -h 127.0.0.1 -u"$MYSQL_USER" "$MYSQL_DATABASE" -e 'SELECT 1 FROM naudotojai LIMIT 1' >/dev/null 2>&1
