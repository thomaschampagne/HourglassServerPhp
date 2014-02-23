Hourglass Server
=================

Provides a simple REST JSON API able to synchronize files toward many clients to reach 'the' latest server revision

Completly made with TDD. I ensure +95% of unit/integration tests

An Android Hourglass CLIENT (Library + Sample) is available here: https://github.com/thomaschampagne/HourglassClientAndroid

Debian based install exemple
------
```
apt-get update
apt-get upgrade
apt-get install git unzip
```

Get the code on your server
-----
```
cd /var/www/html/
wget https://github.com/thomaschampagne/HourglassServer/archive/master.zip
unzip master.zip
mv HourglassServer-master hourglass
rm master.zip
cd hourglass/
```

Php composer dependencies installation
-----
- Get composer.phar from internet
. 
```
$ cd hourglass/
$ php -r "readfile('https://getcomposer.org/installer');" | php
```
- Install project dependencies
```
$ php composer.phar install
$ rm -f composer.phar
```

Apply proper files rights
-----
Your system web user (eg. 'www-data' on a debian distribution) must have "read/write/execute" rights on hourglass folder structure. 
```
chmod 755 -R .
chown www-data. -R .
```

Execute unit tests (+95% code coverage)
-----
```
cd src/Tests/
../../vendor/phpunit/phpunit/phpunit TestSuite.php

PHPUnit 4.1.3 by Sebastian Bergmann.

....................................................

Time: 571 ms, Memory: 6.50Mb
OK (52 tests, 300 assertions)
```

Let's hit the webservice endpoint to test
-----

The webservice is reacheable through the url below: 
```
http://[YOUR_IP_ADDR]/hourglass/endpoint/call.php
```
You will need to add HTTP GET parameter named **q**. This parameter hold the **json** request made to hourglass.

Just for testing purpose, try to do a simple **checkout** by hitting the following url in your web browser:
```
http://[YOUR_IP_ADDR]/hourglass/endpoint/call.php?q={"method":"checkout"}
```
You should get the following result

```
{
    "response": {
        "latestRevision": 1,
        "latestRevisionDate": 1409557608,
        "filesToDelete": [],
        "archive": {
            "archiveFilesCount": 1,
            "archiveFileList": ["content\/your_files_here.txt"],
            "archiveFileSizeBytes": 225,
            "archiveMd5FingerPrint": "d1816d510efd55ed84383a6d0874e8d4",
            "archiveBinaryLink": "http:\/\/[YOUR_IP_ADDR]\/hourglass\/tmp\/d1816d510efd55ed84383a6d0874e8d4_1ecc319c376c3670100a1f03659063700a6e6bf25bfe290f2d57eba0fcc618e1_1.zip",
            "archiveFromCache": true
        },
        "revisionInfo": null
    },
    "error": null
}
```

Let's drop your files to be synced
-----

Let's create a sample text file inside the default versionned folder 'res/main/content/'

```
root@hourglass:/var/www/html/hourglass# echo 'Hello World' > res/main/content/helloworld.txt
```

The global revision will up to value **2** because a new file has been detected, here's the web service output of a 'checkout':
```
{
    "response": {
        "latestRevision": 2,
        "latestRevisionDate": 1409558351,
        "filesToDelete": [],
        "archive": {
            "archiveFilesCount": 2,
            "archiveFileList": [
                "content/your_files_here.txt",
                "content/helloworld.txt"
            ],
            "archiveFileSizeBytes": 359,
            "archiveMd5FingerPrint": "da3f71d5ecc3b18bb65f6b48a9a1157f",
            "archiveBinaryLink": "http://[YOUR_IP_ADDR]/hourglass/tmp/da3f71d5ecc3b18bb65f6b48a9a1157f_1ecc319c376c3670100a1f03659063700a6e6bf25bfe290f2d57eba0fcc618e1_2.zip",
            "archiveFromCache": false
        },
        "revisionInfo": null
    },
    "error": null
}

```

Try to add/edit/remove files, the **latestRevision** will up, the file given in **archive** will change.

Erase and reset versionning database (you will not loose your files)
-----
```
root@hourglass:/var/www/html/hourglass# rm -f res/main/history.json
```
At this step the global revision should return to value: **1**

Requests examples
-----
Hourglass request are given through the HTTP GET parameter **q**. For example:
```
http://[YOUR_IP_ADDR]/hourglass/endpoint/call.php?q=MY_JSON_REQUEST
```
Then just replace **MY_JSON_REQUEST** by yours.

Here are some json requests you can call

* **Classic checkout**
```
{"method":"checkout"}
```
*Will return all the files versionned*
* **Pull from revision 4**
```
{"method":"pullFromRevision","params":{"revision":4}}
```
*Will return all the files versionned since revision _5_ included. The files added/edited from revision 1, 2, 3 and 4 will not be returned in archive.*

* **Get latest revision number**
```
{"method":"getRevNumber"}
```
* **Get versionned file count**
```
{"method":"countVersionnedFiles"}
```
* **Checkout only files which are in a 'fr/' folder (using regular expressions)**
```
{"method":"checkout","params":{"filterWithRegex":"fr\/"}}
```
* **Checkout only files which are in a 'fr/' folder and don't retreive JPG file inside (using regular expressions)**
```
{"method":"checkout","params":{"filterWithRegex":"fr\/","filterWithoutRegex":".jpg"}}
```
* **Pull from revision 2 all .mp3 and .wav file without getting them into german sound folder (using regular expressions)**
```
{"method":"pullFromRevision","params":{"revision":2,"filterWithRegex":".*.mp3|.*.wav","filterWithoutRegex":"sound\/german\/"}}
```
*Other audio file types like .ogg, .flac, ... will not be repatriate*

Setup cache generation
-----
In order to save time on upcoming requests you can generate archive cache for your recurrents requests.

Theses recurrents requests must be defined in the file **endpoint/cacheGen.php**. Add/edit/remove yours in ! The idea is to put your json string request into **$REQUESTS_TO_BE_CACHED** PHP Array, That's all ! All requests archive will be generated on **endpoint/cacheGen.php** webservice hit !

Here an example of the *checkout* reccurent request.

```
// [Example 1] the classic 'checkout'...
array_push($REQUESTS_TO_BE_CACHED, '{"method":"checkout"}');
```
Another example... If you need to generate pre-cache archive for request which get files in a 'fr/' folder only. Then add the following line:

```
/**
*	===================================================================================
* 	BEGIN: Enter below the requests you want to be cached into $REQUESTS_TO_BE_CACHED Array
*	===================================================================================
*/


// Yes that line below !
array_push($REQUESTS_TO_BE_CACHED, '{"method":"checkout","params":{"filterWithRegex":"fr\/"}}');



/**
*	===================================================================================
* 	END cachec request declaration
*	===================================================================================
*/
```

Now just hit the cache generation webservice in your browser to generate cache archives. Hit the following URL:
```
http://[YOUR_IP_ADDR]/hourglass/endpoint/cacheGen.php
```

To automate this simply create a cron task. Here is a sample for a linux server:

```
# Place this file into your linux cron trigger system to generate Hourglass cached archives and system hash finger
prints for files versionned
# Edit the file endpoint/cacheGen.php to add/edit/remove your own requests to be cached

# This example below will generate cache each hour when the minute <30> will pop.
# Change the URL by yours depending on your configuration.
30 * * * * wget http://localhost/hourglass/endpoint/cacheGen.php -qO /dev/null
```

*Note:* You may also found this file into **cacheGenerator/hourglassCacheGen**.
