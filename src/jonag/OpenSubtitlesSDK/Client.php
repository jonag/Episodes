<?php

namespace jonag\OpenSubtitlesSDK;

use fXmlRpc\Client as RpcClient;
use jonag\OpenSubtitlesSDK\Exception\MethodNotFoundException;
use jonag\OpenSubtitlesSDK\Exception\OpenSubtitlesException;
use jonag\OpenSubtitlesSDK\Exception\UnauthorizedException;

class Client
{
    const OK = '200 OK';

    private $xmlRpcClient;
    private $token;

    public function __destruct()
    {
        $this->logOut();
    }

    /**
     * @return mixed|null
     */
    public function serverInfo()
    {
        return $this->call('ServerInfo');
    }

    /**
     * @return string
     */
    public function getToken()
    {
        if ($this->token === null) {
            $result = $this->call('LogIn', ['', '', 'en', 'jonag\episodes v2.1.1']);
            $this->token = $result['token'];
        }

        return $this->token;
    }

    public function logOut()
    {
        if ($this->token !== null) {
            $this->call('LogOut', [$this->token]);
            $this->token = null;
        }
    }

    public function getSubtitles($language, $options)
    {
        $search = [];
        if (isset($options['hash'])) {
            $search[] = [
                'sublanguageid' => $language,
                'moviehash' => $options['hash']['movieHash'],
                'moviebytesize' => $options['hash']['movieSize']
            ];
        }

        if (isset($options['tag'])) {
            $search[] = [
                'sublanguageid' => $language,
                'tag' => $options['tag']['releaseName'],
            ];
        }

        if (isset($options['query'])) {
            $search[] = [
                'sublanguageid' => $language,
                'query' => $options['query']['showName'],
                'season' => $options['query']['season'],
                'episode' => $options['query']['episode']
            ];
        }

        $result = $this->call(
            'SearchSubtitles',
            [
                $this->getToken(),
                $search,
            ]
        );

        return $result['data'];
    }

    /**
     * @param string $methodName
     * @param array  $params
     * @return mixed|null
     * @throws \jonag\OpenSubtitlesSDK\Exception\OpenSubtitlesException
     */
    private function call($methodName, $params = [])
    {
        if ($this->xmlRpcClient === null) {
            $this->xmlRpcClient = new RpcClient('http://api.opensubtitles.org/xml-rpc');
        }

        $result = $this->xmlRpcClient->call($methodName, $params);

        // The method ServerInfo does not return a status
        if ($methodName === 'ServerInfo') {
            return $result;
        }

        switch ($result['status']) {
            case self::OK:
                return $result;

            case UnauthorizedException::CODE:
                throw new UnauthorizedException($result['status']);
                break;

            case MethodNotFoundException::CODE:
                throw new MethodNotFoundException($result['status']);

            default:
                throw new OpenSubtitlesException($result['status']);
                break;
        }
    }
}
