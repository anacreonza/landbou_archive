## Landbou Archive Site

A site to provide access to the LandbouWeekblad text archive.

This site uses  to index the LBW text archive.

It is a PHP site built on the [Laravel](https://www.laravel.com) framework.

## Set up instructions

1. Install a minimum of PHP 7.3 with the following plugins: mbstring, curl, ziparchive
    - For IIS see this [link](https://docs.microsoft.com/en-us/iis/application-frameworks/install-and-configure-php-on-iis/install-and-configure-php#Extensions_1)

2. Install [Composer](https://getcomposer.org) and then run composer update.

3. Install [git](https://git.org) and do a git clone of this repo into the desired location.

4. Install [Elasticsearch](https://www.elastic.co/elasticsearch/). Elasticsearch needs a JVM, although it includes one in the zip file. On Windows copy this into c:\jvm and update the JAVA_HOME environment variable to point to c:\jvm (better to use a dir without spaces in the name).

6. Set up the .env file for the site.

7. Install [pandoc](https://pandoc.org/installing.html#) to allow site to convert .docx files into html.