<h1 align="center">TW Main API (Twitter Clone)</h1>


## Setup Installation

### Before docker-compose up
- Get copy the .env.example to .env
- Set Mail variables
- Set Pusher Variables with your api keys of <a href="https://pusher.com">Pusher</a>
- Set Twilio Variables with your api credentials of <a href="https://twilio.com">Twilio</a>
- Set the following variables to run tests correctly:
```
    IP_TEST_LOCATION=
    PHONE_NUMBER_TEST=
    PHONE_NUMBER_VALIDATED_TEST=
    COUNTRY_CODE_TEST=
    MEDIA_DISK=
    SCOUT_DRIVER=
```

### After docker-dompose up
- Enter in tw-main-api container with following command 
```
    docker exec -it tw-main-api bash
```
- Within the container run these commands:
```
    composer install
    artisan key:generate
    fresh-db
```

### Extra
- Run tests within container
```
    vendor/bin/phpunit
```
- Run queues within container
```
    artisan queue:work --queue=default,likes,tweets,replies,retweets,mentions
```
