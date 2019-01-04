<?php

namespace Disaytid;

/**
 * Для работы с API нужно создать входящий webhook в разделе Приложения, передав при создании
 * объекта токен и URL
 *
 * Class Bitrix
 * @package Disaytid
 */
class Bitrix
{
    /**
     * Webhook
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $api_url;

    /**
     * Передайте токен и URL для работы с Rest API
     * Их можно генерировать в меню Приложения -> Вебхуки -> Входящие
     *
     * @param string $token
     * @param string $url
     */
    public function __construct(string $token = 'abc123456789', string $url = 'https://portal.bitrix24.ru/rest/1/')
    {
        $this->token = $token;
        $this->api_url = $url;
    }

    /**
     * @param $method
     * @param array $params
     * @param string|null $token
     * @return mixed
     */
    public function query($method, array $params = [], string $token = null)
    {
        if(is_null($token))
            $token = $this->token;

        return $this->curl($this->api_url.$token.'/'.$method.'/', $params);
    }

    /**
     * Отправлять запрос до тех пор, пока не будет получен ответ
     * Нужно для обхода лимита на запросы.
     * Осторожно: он будет долбить до второго пришествия. Установите временной лимит
     *
     * @param $method
     * @param array $params
     * @param int $tries
     * @return mixed|null
     */
    public function queryForever($method, array $params = [], int $tries = 0)
    {
        if($tries >= 20)
            return null;

        $list = $this->query($method, $params);

        if(isset($list['error'])) {
            sleep(2);
            return $this->queryForever($method, $params, $tries+1);
        }

        return $list;
    }

    /**
     * Получить список нужных сущностей (напр. лидов) и применить к ним callback
     * @param $method
     * @param array $params
     * @param callable $func
     * @return bool
     */
    public function getCallback($method, array $params, callable $func)
    {
        $list = $this->query($method, $params);

        if(isset($list['error'])) {
            $func(false);
            sleep(2);
            return $this->getCallback($method, $params, $func);
        }

        if(!is_array($list) || !isset($list['result']) || !count($list['result']))
            return false;

        foreach($list['result'] as $res) {
            $func($res);
        }

        if(isset($list['next'])) {
            $params['start'] = $list['next'];

            if(is_array($child = $this->getCallback($method, $params, $func)) && isset($child['result']) && count($child['result']))
                $func($child['result']);
        }

        return true;
    }

    /**
     * Отправить API-запрос и получить массив-список
     * Используется только для методов crm.*.list
     * @param $method
     * @param array $params
     * @return array|mixed
     */
    public function getList($method, array $params)
    {
        $list = $this->queryForever($method, $params);

        if(!is_array($list) || !isset($list['result']) || !count($list['result']))
            return [];

        if(isset($list['next'])) {
            $params['start'] = $list['next'];

            if(is_array($child = $this->getList($method, $params)) && count($child['result']))
                $list['result'] = array_merge($list['result'], $child['result']);
        }

        return $list;
    }

    /**
     * Прокладка для CURL
     * @param $url
     * @param $params
     * @return mixed
     */
    protected function curl($url, $params)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => http_build_query($params),
        ]);

        $result = curl_exec($curl);
        curl_close($curl);
        return json_decode($result, 1);
    }
}