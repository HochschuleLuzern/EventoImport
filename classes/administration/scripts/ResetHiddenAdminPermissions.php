<?php declare(strict_types=1);

namespace EventoImport\administration\scripts;

use ILIAS\DI\RBACServices;
use ILIAS\UI\Factory;
use ILIAS\UI\Component\Modal\Modal;
use EventoImport\import\data_management\repository\HiddenAdminRepository;
use EventoImport\db\HiddenAdminsTableDef;

class ResetHiddenAdminPermissions implements AdminScriptInterface
{
    use AdminScriptCommonMethods;

    private const FORM_TITLE = 'evento_title';

    private const CMD_SET_PERMISSION = 'set_permissions';

    private $db;
    private $ctrl;
    private $tree;

    public function __construct(\ilDBInterface $db, \ilCtrl $ctrl, \ilTree $tree)
    {
        $this->db = $db;
        $this->ctrl = $ctrl;
        $this->tree = $tree;
    }

    public function getTitle() : string
    {
        return "Set the permissions for all Hidden Admin roles including subobjects";
    }

    public function getScriptId() : string
    {
        return 'set_hidden_roles_permission_for_subobjs';
    }

    public function getParameterFormUI() : \ilPropertyFormGUI
    {
        $this->ctrl->setParameterByClass(\ilEventoImportUIRoutingGUI::class, 'script', $this->getScriptId());
        $url = $this->ctrl->getFormActionByClass(\ilEventoImportUIRoutingGUI::class);

        $form = new \ilPropertyFormGUI();
        $form->setFormAction($url);

        $form->addCommandButton(self::CMD_SET_PERMISSION, 'Set Permissions');

        return $form;
    }

    public function getResultModalFromRequest(string $cmd, Factory $f) : Modal
    {
        $form = $this->getParameterFormUI();
        if(!$form->checkInput() || $cmd != self::CMD_SET_PERMISSION) {
            throw new \InvalidArgumentException("Invalid Form Input!");
        }

        $successfull_changes = [];
        $errors = [];

        $sql = "SELECT * FROM " . HiddenAdminsTableDef::TABLE_NAME;
        $result = $this->db->query($sql);
        while ($row = $this->db->fetchAssoc($result)) {
            try {
                $role_id = (int) $row[HiddenAdminsTableDef::COL_HIDDEN_ADMIN_ROLE_ID];
                $obj_ref_id = (int) $row[HiddenAdminsTableDef::COL_OBJECT_REF_ID];
                if (!\ilObject::_exists($role_id)) {
                    $errors[] = "Role with role_id $role_id does not exist";
                } else if (!\ilObject::_exists($obj_ref_id, true)) {
                    $errors[] = "Object with ref_id $obj_ref_id does not exist";
                } else if (!$this->tree->isInTree($obj_ref_id)) {
                    $errors[] = "Object with ref_id $obj_ref_id is not in tree";
                } else {
                    $this->resetPermissionsForRole(
                        $role_id,
                        $obj_ref_id
                    );
                    $successfull_changes[] = "Successfull for role_id = $role_id and ref_id = $obj_ref_id";
                }
            } catch (\Exception $e) {
                $errors[] = "Error for role_id= $role_id: " . $e->getMessage();
            }

        }

        $lists_as_str = 'Error:<br>'.implode('<br>', $errors)
            .'<br><hr><br>Successfull:<br>' . implode('<br>', $successfull_changes);


        return $f->modal()->lightbox(
            $f->modal()->lightboxTextPage(
                $lists_as_str,
                $this->getTitle()
            )
        );
    }

    private function resetPermissionsForRole(int $role_id, int $obj_ref_id)
    {
        $role_object = new \ilObjRole($role_id, false);

        $role_object->changeExistingObjects(
            $obj_ref_id,
            \ilObjRole::MODE_UNPROTECTED_KEEP_LOCAL_POLICIES,
            ['all']
        );
    }
}