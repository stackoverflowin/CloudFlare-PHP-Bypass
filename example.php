<?php
// Include the library..
require_once "cloudflare.class.php";
// Make a new instance of the CloudFlare class, save cookies to file 'faggot.txt' so we don't have to wait the eight seconds again
$cloudflare = new \Stack\Bypass\CloudFlare("http://libc.tech", [true, "faggot.txt"]);
// Do a request to /, display result.
echo $cloudflare->get("/");
