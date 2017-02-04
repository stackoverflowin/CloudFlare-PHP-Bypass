<?php
namespace Stack\Bypass;

/**
 * Require the composer packages
 */
require "vendor/autoload.php";

/**
 * @package CloudFlare
 * @author Stack
 */
class CloudFlare
{
  /**
   * $target stores the string of the URL in question
   * @var string
   */
  private $target;

  /**
   * $client stores an instance of \GuzzleHttp\Client
   * @var [type]
   */
  private $client;

  /**
   * $client stores an instance of \GuzzleHttp\Request
   * @var \GuzzleHttp\Request
   */
  private $request;

  /**
   * $cookieJar stores an instance of \GuzzleHttp\Cookie\FileCookieJar
   * @var \GuzzleHttp\Cookie\FileCookieJar
   */
  private $cookieJar;

  /**
   * $cookieExists for use when calculating if we should get the cookie again
   * @var [type]
   */
  private $cookieExists = false;

  /**
   * WAIT_RESPONSE_CODE this is the response code which CloudFlare throws when UAM is active
   * @var int
   */
  const WAIT_RESPONSE_CODE = 503;

  /**
   * SERVER_NAME name of the server which CloudFlare uses
   * @var string
   */
  const SERVER_NAME = "cloudflare-nginx";

  /**
   * USER_AGENT user agent to use in the requests
   * @var string
   */
  const USER_AGENT = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/50.0.2661.102 Chrome/50.0.2661.102 Safari/537.36";

  /**
   * REFRESH_EXPRESSION regular expression used to parse the "Refresh" header
   * @var string
   */
  const REFRESH_EXPRESSION = "/8;URL=(\/cdn-cgi\/l\/chk_jschl\?pass=[0-9]+\.[0-9]+-.*)/";

  /**
   * __construct - constructor for the class
   * @param \GuzzleHttp\Cookie\FileCookieJar $response instance of response class
   * @return bool          protected or not
   */
  public function __construct($targetSite, $cookies = [false, "cookie.txt"])
  {
    $this->target = $targetSite;

    $config = [
      "cookies" => true,
      "http_errors" => false
    ];

    if(isset($cookies[0]) && $cookies[0])
    {
      if(empty($cookies[1]))
      	return;
      
      $file = $cookies[1];
      $this->cookieJar = new \GuzzleHttp\Cookie\FileCookieJar($file);
      $config = [
        "cookies" => $this->cookieJar,
        "http_errors" => false
      ];
      if(file_exists($file))
      {
        $this->cookieExists = true;
        $this->cookieJar->load($file);
      }
    }
    $this->client = new \GuzzleHttp\Client($config);
    $this->initialRequest();
  }

  /**
   * verifyPage - this will verify that the page is protected by CloudFlare
   * @param  \GuzzleHttp\Client $response instance of response class
   * @return bool          protected or not
   */
  private function verifyPage($response)
  {
    return ($response->getHeader("Server")[0] == self::SERVER_NAME && $response->getStatusCode() == self::WAIT_RESPONSE_CODE);
  }

  /**
   * getCookie will retrive the cookie for bypassing
   */
  private function getCookie()
  {
    $refreshHeader = $this->request->getHeader("Refresh")[0];
    $followLocation = $this->target.$this->parseRefresh($refreshHeader);
    $this->request = $this->client->request(
      "GET",
      $followLocation,
      [
        "User-Agent" => self::USER_AGENT,
        "Accept" => "*/*",
        "Accept-Encoding" => "gzip, deflate, sdch",
        "Accept-Language" => "en-GB,en-US;q=0.8,en;q=0.6",
        "Referer" => $this->target
      ]
    );
  }

  /**
   * initialRequest this does the inital request, makes sure the page is CloudFlare et.c
   */
  private function initialRequest()
  {
    $this->request = $this->client->request(
      "GET",
      $this->target,
      [
        "User-Agent" => self::USER_AGENT,
        "Accept" => "*/*",
        "Accept-Encoding" => "gzip, deflate, sdch",
        "Accept-Language" => "en-GB,en-US;q=0.8,en;q=0.6"
      ]
    );
    if(!$this->cookieExists)
    {
      if(!$this->verifyPage($this->request))
      {
        throw new \Exception("This website is not protected by CloudFlare or the UAM is not enabled", 1);
      }
      sleep(8);
      $this->getCookie();
    }
  }

  /**
   * parseRefresh parses the "Refresh" header
   * @param  string $header "Refresh: X"
   * @return string        parsed URI
   */
  private function parseRefresh($header)
  {
    $matchURL = preg_match(self::REFRESH_EXPRESSION, $header, $match);
    if($matchURL)
    {
      return $match[1];
    }
    throw new \Exception("Can not seem to parse the refresh header", 2);
  }

  /**
   * get does a GET request to the specified URI
   * @param  string $uri location
   * @return string      body of the request
   */
  public function get($uri)
  {
    $this->request = $this->client->request(
      "GET",
      $this->target,
      [
        "User-Agent" => self::USER_AGENT,
        "Accept" => "*/*",
        "Accept-Encoding" => "gzip, deflate, sdch",
        "Accept-Language" => "en-GB,en-US;q=0.8,en;q=0.6"
      ],
      [
        'allow_redirects' => true
      ]
    );
    return $this->request->getBody();
  }
}
