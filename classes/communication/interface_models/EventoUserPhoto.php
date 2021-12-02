<?php

namespace EventoImport\communication\api_models;

class EventoUserPhoto extends ApiDataModelBase
{
    public const JSON_ID_ACCOUNT = 'idAccount';
    public const JSON_HAS_PHOTO = 'hasPhoto';
    public const JSON_IMG_DATA = 'imgData';

    private $id_account;
    private $has_photo;
    private $img_data;
    protected $decoded_api_data;

    public function __construct(array $data_set)
    {
        $this->id_account = $this->validateAndReturnNumber($data_set, self::JSON_ID_ACCOUNT);
        $this->has_photo = $this->validateAndReturnBoolean($data_set, self::JSON_HAS_PHOTO);
        $this->img_data = $this->validateAndReturnString($data_set, self::JSON_IMG_DATA);

        $this->checkErrorsAndMaybeThrowException();
        $this->decoded_api_data = $data_set;
    }

    /**
     * @return int
     */
    public function getIdAccount() : int
    {
        return $this->id_account;
    }

    /**
     * @return bool
     */
    public function getHasPhoto() : bool
    {
        return $this->has_photo;
    }

    /**
     * @return string
     */
    public function getImgData() : string
    {
        return $this->img_data;
    }

    /**
     * @return array
     */
    public function getDecodedApiData() : array
    {
        return $this->decoded_api_data;
    }
}
