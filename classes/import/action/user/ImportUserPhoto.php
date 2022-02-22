<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\import\service\IliasUserServices;
use EventoImport\communication\api_models\EventoUserPhoto;

trait ImportUserPhoto
{
    public function importAndSetUserPhoto(int $evento_id, \ilObjUser $user, EventoUserPhotoImporter $photo_importer, IliasUserServices $user_facade)
    {
        try {
            $photo_import = $photo_importer->fetchUserPhotoDataById($evento_id);

            if (is_null($photo_import)) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        if ($photo_import->getHasPhoto() && $photo_import->getImgData() && strlen($photo_import->getImgData()) > 10) {
            $user_facade->saveEncodedPersonalPictureToUserProfile($user, $photo_import->getImgData());
        }
    }
}
