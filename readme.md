# Ad Finder
This works with the Duplitron API to automatically find potential ad candidates from the Internet Archive's TV archive corpus.  You need access to the corpus for this code to be useful (but could modify the IngestVideo task to use your own video!).


## Dependencies

To run this code you need:

* PHP >= 5.5.9
* OpenSSL PHP Extension
* PDO PHP Extension
* Mbstring PHP Extension
* Tokenizer PHP Extension
* [Composer](https://getcomposer.org/) for dependency management
* [PSQL](http://www.postgresql.org/)

## Installing

1. Install [Composer](https://getcomposer.org/)

2. Clone this repository into a living breathing live php enabled directory mod rewrite should be enabled

3. Install composer dependencies

	```shell
		cd /path/to/your/clone/here
		composer install
	```

4. Set up a psql database

5. Copy .env.example to .env in the root directory, then edit it

	```shell
		cd /path/to/your/clone/here
		cp .env.example .env
		vi .env
	```

	* RSYNC_IDENTITY_FILE: a path to a private key that web root has 500 access to, with any media files you plan on importing
	* FPRINT_STORE: a path to the /storage/audfprint director inside of the repository
	* DOCKER_HOST: the location and port of the docker you set up for audfprint
	*


6. Install supervisor and [enable the job queue](http://laravel.com/docs/5.1/queues#running-the-queue-listener).

	```shell
		cp adfinder-worker.conf.example /etc/supervisor/conf.d/adfinder-worker.conf
		vi /etc/supervisor/conf.d/adfinder-worker.conf
		sudo supervisorctl reread
		sudo supervisorctl update
		sudo supervisorctl start adfinder-worker:*
	```
