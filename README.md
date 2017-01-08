Episodes
========

This application organize your downloaded tv shows and can download their subtitles.
The subtitles are downloaded thanks to the [OpenSubtitles](http://www.opensubtitles.org/) API.

## Usage
- Move episodes to the tv shows directory

    ``` sh
    $ bin/episodes episodes:move
    ```
- Search subtitles for a specific file

    ``` sh
    $ bin/episodes subtitles:search path_to_file.mkv
    ```
    
- Search all missing subtitles (stored in the database)

    ``` sh
    $ bin/episodes subtitles:missing
    ```

## Installation
1. Download the [`composer.phar`](https://getcomposer.org/composer.phar) executable or use the installer.

    ``` sh
    $ curl -sS https://getcomposer.org/installer | php
    ```

2. Install the dependencies

    ``` sh
    $ php composer.phar install --no-dev
    ```

3. Edit the configuration file (settings.yml)

4. Initiate the database

    ``` sh
    $ bin/episodes subtitles:db:init
    ```
