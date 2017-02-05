# CloudFlare PHP Bypass
This class will allow you to bypass CloudFlare's UAM page and do web requests as normal.
```
// Include the library..
require_once "cloudflare.class.php";

// Make a new instance of the CloudFlare class, save cookies to file 'x.txt' so we don't have to wait the eight seconds again
$cloudflare = new \Stack\Bypass\CloudFlare("http://libc.tech", [true, "x.txt"]);

// Do a request to /, display result.
echo $cloudflare->get("/");
```
