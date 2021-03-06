Scan site
===========

Scaning site. Getting home links. Getting home elements of forms.

This library requires PHP extensions: tidy, mbstring.


Installation
==============
The recommended way to install scan-site is through Composer. Just create a composer.json file and run the php composer.phar install command to install it:
``` json

{
    "require": {
        "scan-site/scan-site":"dev-master"
    }
}
```

Usage
=======
``` php
require_once(__DIR__ . '/vendor/autoload.php');

defined('VENDOR_PATH') || define('VENDOR_PATH', (getenv('VENDOR_PATH') ? getenv('VENDOR_PATH') : __DIR__ . "/vendor"));

$scan = new Scan("http://site.com");

$result = $scan->scan();
```

License
========

The MIT License (MIT)

Coryright (C) 2014 Kirill Parasotchenko

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.