docker run -d \
  --name argoos-redis \
  -p 6379:6379 \
  redis:7-alpine

  Poi in server/.env cambiare:

CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0
REDIS_CACHE_DB=1

php artisan serve usa 127.0.0.1 e il container mappa 0.0.0.0:6379→6379, quindi si connettono direttamente. Verificare la connessione con: php artisan tinker → Cache::store('redis')->put('test', 1, 10);