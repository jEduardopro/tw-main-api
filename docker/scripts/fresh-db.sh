echo "artisan config:clear"
php artisan config:clear

echo "artisan migrate:fresh"
php artisan migrate:fresh

echo "artisan passport:install"
php artisan passport:install

rm -rf public/media/tweet
rm -rf public/media/user

echo "artisan db:seed"
php artisan db:seed

echo "db refresh finished :)"
