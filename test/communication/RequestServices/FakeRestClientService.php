<?php

namespace EventoImport\communication\request_services;

class FakeRestClientService extends RestClientService
{
    private $base_uri;
    private $timeout_in_seconds;
    private $file_path;
    private $has_more;

    public function __construct()
    {
        // We don't care about the given URL
        $this->file_path = "/var/www/json_mocks/";
        $this->has_more = 'false';
    }

    public function sendRequest(string $path, array $request_params) : string
    {
        $take = $request_params["take"];
        $skip = $request_params["skip"];
        $id = $request_params['id'];
        if ($path == 'GetAccounts') {
            $file = $this->file_path . 'users/users_s' . $skip . '_t' . $take . '.json';
        } elseif ($path == 'GetEvents') {
            $file = $this->file_path . 'events/events_s' . $skip . '_t' . $take . '.json';
        } elseif ($path == 'GetAccountById') {
            if (rand(0, 1) % 2) {
                return '{"success":true,"hasMoreData":false,"message":"OK","data":[{"idAccount": ' . $id . ', "lastName": "Oczuk", "firstName": "Aggypu", "gender": "X", "loginName": "hslu_login_oczuk", "email": "aggypu.oczuk@stud.hslu.ch", "email2": "aggypu.oczuk@uuvlzr.random.domain", "email3": "", "roles": [2]}]}';
            } else {
                return '{"success":true,"hasMoreData":false,"message":"OK","data":[]}';
            }
        } elseif ($path == 'GetPhotoById') {
            $file = $this->file_path . 'photos/photo.json';
        }

        $file_content = file_get_contents($file);
        //$file_content = '{"success":true,"hasMoreData":'.$this->has_more.',"message":"OK","data":' . $file_content . '}';
        //$this->has_more = 'false';
        return $file_content;
    }
}
