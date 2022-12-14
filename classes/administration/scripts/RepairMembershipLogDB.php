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

    private function evaluatePossibleDuplicateAndTryToSwitch(int $evento_event_id, int $evento_user_id, int $ilias_user_id, $last_import_date)
    {
        $already_existing_entry = $this->getLogEntry($evento_event_id, $evento_user_id);
        if (!is_null($already_existing_entry)) {
            if (date($last_import_date) > date($already_existing_entry['last_import_date'])) {
                $this->removeLogEntry((int) $already_existing_entry['evento_event_id'], (int) $already_existing_entry['evento_user_id']);
                $this->switchUserIdInLogEntryForIds($evento_event_id, $evento_user_id, $ilias_user_id);
            } else {
                $this->removeLogEntry($evento_event_id, $ilias_user_id);
            }
        } else {
            $this->switchUserIdInLogEntryForIds($evento_event_id, $evento_user_id, $ilias_user_id);
        }
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

        $errors = [];
        $changed = [];
        $not_found = [];
        $cache = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $ilias_user_id = (int) $row['evento_user_id'];
            $evento_event_id = (int) $row['evento_event_id'];
            try {
                if(isset($cache[$ilias_user_id]) && $cache[$ilias_user_id] > 0) {
                    $this->evaluatePossibleDuplicateAndTryToSwitch($evento_event_id, $cache[$ilias_user_id], $ilias_user_id, $row['last_import_date']);
                    $changed[] = "ILIAS User ID " . $ilias_user_id . " -> Evento User ID " . $cache[$ilias_user_id];
                } else {
                    $ilias_evento_user = $this->repo->getIliasEventoUserByIliasUserId($ilias_user_id);
                    if (!is_null($ilias_evento_user)) {
                        $cache[$ilias_user_id] = $ilias_evento_user->getEventoUserId();
                        $this->evaluatePossibleDuplicateAndTryToSwitch($evento_event_id, $ilias_evento_user->getEventoUserId(), $ilias_evento_user->getIliasUserId(), $row['last_import_date']);
                        $changed[] = "ILIAS User ID " . $ilias_user_id . " -> Evento User ID " . $ilias_evento_user->getEventoUserId();

                    } else {
                        if (\ilObject::_lookupType($ilias_user_id) == 'usr') {
                            $user_obj = new \ilObjUser($ilias_user_id);
                            $mat_nr = (int) trim(str_replace('Evento:', '', $user_obj->getMatriculation()));
                            $ext_acc = (int) trim(str_replace('@hslu.ch', '', $user_obj->getExternalAccount()));
                            if($mat_nr == $ext_acc && $mat_nr > 0) {
                                $this->evaluatePossibleDuplicateAndTryToSwitch($evento_event_id, $mat_nr, $ilias_user_id, $row['last_import_date']);
                                $cache[$ilias_user_id] = $mat_nr;
                                $changed[] = "ILIAS User ID " . $ilias_user_id . " -> Evento User ID " . $mat_nr;
                            } else if ($mat_nr > 0 && ($ext_acc <= 0 || $ext_acc == null)) {
                                $this->evaluatePossibleDuplicateAndTryToSwitch($evento_event_id, $mat_nr, $ilias_user_id, $row['last_import_date']);
                                $cache[$ilias_user_id] = $mat_nr;
                                $changed[] = "ILIAS User ID " . $ilias_user_id . " -> Evento User ID " . $mat_nr;
                            } else if ($ext_acc > 0 && ($mat_nr <= 0 || $mat_nr == null)) {
                                $this->evaluatePossibleDuplicateAndTryToSwitch($evento_event_id, $ext_acc, $ilias_user_id, $row['last_import_date']);
                                $cache[$ilias_user_id] = $ext_acc;
                                $changed[] = "ILIAS User ID " . $ilias_user_id . " -> Evento User ID " . $ext_acc;
                            } else {
                                $not_found[] = "Invalid MatNr ($mat_nr) or ExtAcc ($ext_acc) for Ilias ID " . $ilias_user_id;
                            }
                        } else {
                            $not_found[] = "No ILIAS User found for ID " . $ilias_user_id;
                        }
                    }
                }

            } catch (\Exception $e) {
                $errors[] = "Exception for Event ($evento_event_id) and user ($ilias_user_id) occured: " . $e->getMessage();
            }
        }

        $lists_as_str = 'Error:<br>'.implode('<br>', $errors).'<br><hr><br>Not Found:<br>'.implode('<br>', $not_found) . '<br><hr><br>Changed:<br>' . implode('<br>', $changed);

        return $this->buildModal($this->getTitle(), $lists_as_str, $f);
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

    private function switchUserIdInLogEntryForIds(int $evento_event_id, int $evento_user_id, int $ilias_user_id)
    {
        $this->db->update(
            'crevento_log_members',
            [
                'evento_user_id' => [\ilDBConstants::T_INTEGER, $evento_user_id],
            ],
            [
                'evento_event_id' => [\ilDBConstants::T_INTEGER, $evento_event_id],
                'evento_user_id' => [\ilDBConstants::T_INTEGER, $ilias_user_id]
            ]
        );
    }
}