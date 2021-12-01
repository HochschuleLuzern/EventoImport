<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\import\db\UserFacade;
use EventoImport\communication\api_models\EventoUserPhoto;

trait ImportUserPhoto
{
    public function importAndSetUserPhoto(int $evento_id, \ilObjUser $user, EventoUserPhotoImporter $photo_importer, UserFacade $user_facade)
    {
        try {
            $photo_import = $photo_importer->fetchDataRecordById($evento_id);
            $photo_import = new EventoUserPhoto($photo_import);
        } catch (\Exception $e) {
            return;
        }

        if ($photo_import->getHasPhoto() && is_string($photo_import->getImgData()) && strlen($photo_import->getImgData()) > 10) {
            try {
                $tmp_file = \ilUtil::ilTempnam();
                imagepng(
                    imagecreatefromstring(
                        $photo_import['imgData'],
                    ),
                    $tmp_file,
                    0
                );
                \ilObjUser::_uploadPersonalPicture($tmp_file, $user->getId());
            } catch (\Exception $e) {
            } finally {
                if (isset($tmp_file)) {
                    unlink($tmp_file);
                }
            }
        }
    }
}
