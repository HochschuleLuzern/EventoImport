<?php declare(strict_types = 1);

namespace EventoImport\communication\api_models;

class EventoUserPhoto extends ApiDataModelBase
{
    public const JSON_ID_ACCOUNT = 'idAccount';
    public const JSON_HAS_PHOTO = 'hasPhoto';
    public const JSON_IMG_DATA = 'imgData';

    private ?int $id_account;
    private ?bool $has_photo;
    private ?string $img_data;

    public function __construct(array $data_set)
    {
        $this->id_account = $this->validateAndReturnNumber($data_set, self::JSON_ID_ACCOUNT);
        $this->has_photo = $this->validateAndReturnBoolean($data_set, self::JSON_HAS_PHOTO);
        $this->img_data = $this->validateAndReturnString($data_set, self::JSON_IMG_DATA);

        $this->decoded_api_data = $data_set;
        $this->checkErrorsAndMaybeThrowException();
    }

    public function getIdAccount() : int
    {
        return $this->id_account;
    }

    public function getHasPhoto() : bool
    {
        return $this->has_photo;
    }

    public function getImgData() : string
    {
        return $this->img_data;
    }

    public function getDecodedApiData() : array
    {
        return $this->decoded_api_data;
    }
}
