<?php

/**
 * Builder d'appels cURL.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Curl
{

    /**
     * Liste de user agents potentiels.
     * @var array
     * @see disguiseCurl()
     */
    public static $aUserAgents = array(
        'FireFox3' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; fr; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (.NET CLR 3.5.30729)',
        'GoogleBot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'IE7' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
        'Netscape' => 'Mozilla/4.8 [en] (Windows NT 6.0; U)',
        'Opera' => 'Opera/9.25 (Windows NT 6.0; U; en)'
    );

    /**
     * Classe outils.
     */
    private function __construct ()
    {
    }

    /**
     * Réalise un appel cURL selon les options spécifiées.
     *
     * Options par défaut :
     * array(
     *     'url' => '',
     *     'timeout' => 10,
     *     'post_fields' => NULL,
     *     'login' => NULL,
     *     'password' => NULL,
     *     'content_type' => 'text/plain',
     *     'user_agent' => self::$aUserAgents['GoogleBot'],
     *     'referer' => 'http://www.google.com',
     *     'header' => NULL,
     *     'return_header' => 1,
     *     'file' => NULL,
     * );
     *
     * Un header sera généré si acun n'est spécifié.
     * La clé 'post_fieds' est soit un array soit une chaîne URL-encodée (attention à l'@).
     * Si une exception est générée, elle sera retournée dans la clé 'curl_error' du tableau de retour.
     *
     * @param array $aOptions tableau associatif d'options pour l'appel cURL
     * @return array array(
     *      'header' => NULL|(string),
     *      'body' => NULL|(string),
     *      'curl_error' => NULL|(string),
     *      'http_code' => NULL|(string),
     *      'last_url' => NULL|(string),
     * );
     */
    public static function disguiseCurl (array $aOptions=array())
    {
        $aDefaultOptions = array(
            'url' => '',
            'timeout' => 10,
            'post_fields' => NULL,
            'login' => NULL,
            'password' => NULL,
            'content_type' => 'text/plain',
            'user_agent' => self::$aUserAgents['GoogleBot'],
            'referer' => 'http://www.google.com',
            'header' => NULL,
            'return_header' => 1,
            'file' => NULL,
        );
        $aOptions = array_merge($aDefaultOptions, $aOptions);

        $curl = curl_init();

        if ($aOptions['header'] === NULL) {
            // Setup headers - I used the same headers from Firefox version 2.0.0.6
            // below was split up because php.net said the line was too long. :/
            $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
            $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
            $header[] = "Content-type: " . $aOptions['content_type'];
            $header[] = "Cache-Control: max-age=0";
            $header[] = "Connection: keep-alive";
            $header[] = "Keep-Alive: 300";
            $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
            $header[] = "Accept-Language: en-us,en;q=0.5";
            $header[] = "Pragma: "; // browsers keep this blank.
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $aOptions['header']);
        }

        curl_setopt($curl, CURLOPT_URL, $aOptions['url']);
        curl_setopt($curl, CURLOPT_USERAGENT, $aOptions['user_agent']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_REFERER, $aOptions['referer']);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//		curl_setopt($curl, CURLOPT_VERBOSE, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $aOptions['timeout']);
        curl_setopt($curl, CURLOPT_HEADER, $aOptions['return_header']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1); // don't use a cached version of the url

        if ($aOptions['file'] !== NULL) {
            curl_setopt($curl, CURLOPT_FILE, $aOptions['file']);
        }

        if ($aOptions['post_fields'] !== NULL) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $aOptions['post_fields']);
        }

        if ($aOptions['login'] !== NULL && $aOptions['password'] !== NULL) {
            curl_setopt($curl, CURLOPT_USERPWD, $aOptions['login'] . ':' . $aOptions['password']);
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
            $sHeaderSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $result['header'] = substr($response, 0, $sHeaderSize);
            $result['body'] = substr($response, $sHeaderSize);
            $result['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $result['last_url'] = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        }

        curl_close($curl);
        return $result;
    }
}