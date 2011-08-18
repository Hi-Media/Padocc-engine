<?php

class Curl {

    public static $USER_AGENTS = array(
        'FireFox3' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; fr; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (.NET CLR 3.5.30729)',
        'GoogleBot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'IE7' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
        'Netscape' => 'Mozilla/4.8 [en] (Windows NT 6.0; U)',
        'Opera' => 'Opera/9.25 (Windows NT 6.0; U; en)'
    );

    private function __construct () {}

    // disguises the curl using fake headers and a fake user agent.
    // postfields = array ou urlencodedstring => attention au @
    public static function disguiseCurl (array $options=array()) {
        $aDefaultOptions = array(
            'url' => '',
            'timeout' => 10,
            'post_fields' => NULL,
            'login' => NULL,
            'password' => NULL,
            'content_type' => 'text/plain',
            'user_agent' => self::$USER_AGENTS['GoogleBot'],
            'referer' => 'http://www.google.com',
            'header' => NULL,
            'return_header' => 1,
            'file' => NULL,
        );
        $options = array_merge($aDefaultOptions, $options);

        $curl = curl_init();

        if ($options['header'] === NULL) {
            // Setup headers - I used the same headers from Firefox version 2.0.0.6
            // below was split up because php.net said the line was too long. :/
            $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
            $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
            $header[] = "Content-type: " . $options['content_type'];
            $header[] = "Cache-Control: max-age=0";
            $header[] = "Connection: keep-alive";
            $header[] = "Keep-Alive: 300";
            $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
            $header[] = "Accept-Language: en-us,en;q=0.5";
            $header[] = "Pragma: "; // browsers keep this blank.
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['header']);
        }

        curl_setopt($curl, CURLOPT_URL, $options['url']);
        curl_setopt($curl, CURLOPT_USERAGENT, $options['user_agent']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_REFERER, $options['referer']);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//		curl_setopt($curl, CURLOPT_VERBOSE, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $options['timeout']);
        curl_setopt($curl, CURLOPT_HEADER, $options['return_header']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1); // don't use a cached version of the url

        if ($options['file'] !== NULL) {
            curl_setopt($curl, CURLOPT_FILE, $options['file']);
        }

        if ($options['post_fields'] !== NULL) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $options['post_fields']);
        }

        if ($options['login'] !== NULL && $options['password'] !== NULL) {
            curl_setopt($curl, CURLOPT_USERPWD, $options['login'] . ':' . $options['password']);
        }

        try {
            $response = curl_exec($curl);
            $error = curl_error($curl);
        } catch (Exception $e) {
            $error = $e->__toString();
        }
        $result = array(
            'header' => NULL,
            'body' => NULL,
            'curl_error' => NULL,
            'http_code' => NULL,
            'last_url' => NULL,
        );
        if ($error != '') {
            $result['curl_error'] = $error;
        } else {
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $result['header'] = substr($response, 0, $header_size);
            $result['body'] = substr($response, $header_size);
            $result['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $result['last_url'] = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        }

        curl_close($curl);
        return $result;
    }
}