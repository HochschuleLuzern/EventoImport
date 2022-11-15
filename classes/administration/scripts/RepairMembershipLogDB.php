<?php declare(strict_types=1);

namespace EventoImport\administration\scripts;

use ILIAS\UI\Factory;
use ILIAS\UI\Component\Modal\Modal;
use EventoImport\import\data_management\repository\IliasEventoUserRepository;
use EventoImport\import\data_management\repository\model\IliasEventoUser;

class RepairMembershipLogDB implements AdminScriptInterface
{
    use AdminScriptCommonMethods;

    const CMD_EXECUTE_REPAIR = 'execute_repair';

    public function __construct(\ilDBInterface $db, \ilCtrl $ctrl)
    {
        $this->db = $db;
        $this->ctrl = $ctrl;

        $this->repo = new IliasEventoUserRepository($this->db);
    }
    public function getTitle() : string
    {
        return "Repair Membership Log-DB";
    }

    public function getScriptId() : string
    {
        return 'rep_member_log_db';
    }

    public function getParameterFormUI() : \ilPropertyFormGUI
    {
        $this->ctrl->setParameterByClass(\ilEventoImportUIRoutingGUI::class, 'script', $this->getScriptId());
        $url = $this->ctrl->getFormActionByClass(\ilEventoImportUIRoutingGUI::class);
        $form = new \ilPropertyFormGUI();
        $form->setFormAction($url);

        $form->addCommandButton(self::CMD_EXECUTE_REPAIR, 'Execute Repair');

        return $form;
    }

    public function getResultModalFromRequest(string $cmd, Factory $f) : Modal
    {
        if ($cmd != self::CMD_EXECUTE_REPAIR) {
            return $this->buildModal($this->getTitle(), 'Unknown Command', $f);
        }

        $q = "SELECT * FROM crevento_log_members WHERE update_info_code IN (104, 105)";
        $result = $this->db->query($q);

        $changed = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $ilias_user_id = (int) $row['evento_user_id'];
            $evento_event_id = (int) $row['evento_event_id'];
            $ilias_evento_user = $this->repo->getIliasEventoUserByIliasUserId($ilias_user_id);
            if (!is_null($ilias_evento_user)) {

                $already_existing_entry = $this->getLogEntry($evento_event_id, $ilias_evento_user->getEventoUserId());
                if (!is_null($already_existing_entry)) {
                    if (date($row['last_import_date']) > date($already_existing_entry['last_import_date'])) {
                         $this->removeLogEntry((int) $already_existing_entry['evento_event_id'], (int) $already_existing_entry['evento_user_id']);
                         $this->switchUserIdInLogEntry($evento_event_id, $ilias_evento_user);
                    } else {
                        $this->removeLogEntry($evento_event_id, $ilias_evento_user->getIliasUserId());
                    }
                } else {
                    $this->switchUserIdInLogEntry($evento_event_id, $ilias_evento_user);
                }

                $changed[] = "ILIAS User ID " . $ilias_user_id . " -> Evento User ID " . $ilias_evento_user->getEventoUserId();

            }

        }

        return $this->buildModal($this->getTitle(), implode('<br>', $changed), $f);
    }

    private function getLogEntry(int $evento_event_id, int $evento_user_id)
    {
        $q = "SELECT * FROM crevento_log_members"
            ." WHERE evento_event_id = " . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER)
            . " AND evento_user_id = " . $this->db->quote($evento_user_id, \ilDBConstants::T_INTEGER);

        $res2 = $this->db->query($q);
        return $this->db->fetchAssoc($res2);
    }

    private function removeLogEntry(int $evento_event_id, int $user_id)
    {
        $q = "DELETE FROM crevento_log_members WHERE evento_event_id = " . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER)
            . " AND evento_user_id = " . $this->db->quote($user_id, \ilDBConstants::T_INTEGER);
        $this->db->manipulate($q);
    }

    private function switchUserIdInLogEntry(int $evento_event_id, IliasEventoUser $ilias_evento_user)
    {
        $this->db->update(
            'crevento_log_members',
            [
                'evento_user_id' => [\ilDBConstants::T_INTEGER, $ilias_evento_user->getEventoUserId()],
            ],
            [
                'evento_event_id' => [\ilDBConstants::T_INTEGER, $evento_event_id],
                'evento_user_id' => [\ilDBConstants::T_INTEGER, $ilias_evento_user->getIliasUserId()]
            ]
        );
    }
}