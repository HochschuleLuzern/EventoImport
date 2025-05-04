<?php declare(strict_types=1);

namespace EventoImport\administration\scripts;

use ILIAS\UI\Factory;
use ILIAS\UI\Component\Modal\Modal;
use CourseWizard\CustomUI\TemplateSelection\RadioOptionGUI;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;
use EventoImport\import\Logger;
use EventoImport\import\data_management\repository\IliasEventoUserRepository;
use EventoImport\import\data_management\ilias_core_service\IliasUserServices;
use Psr\Http\Message\ServerRequestInterface;
use EventoImport\import\data_management\ilias_core\MembershipablesEventInTreeSeeker;
use EventoImport\import\data_management\repository\model\IliasEventoEvent;

class ReAddRemovedEventParticipants implements AdminScriptInterface
{
    use AdminScriptCommonMethods;

    private const FORM_RADIO_POSTVAR = 'radio_postvar';
    private const FORM_RADIO_BY_EVENTO_ID = 'radio_by_evento_id';
    private const FORM_RADIO_BY_EVENTO_TITLE = 'radio_by_evento_title';
    private const FORM_INPUT_EVENTO_ID = 'input_evento_id';
    private const FORM_INPUT_EVENTO_TITLE = 'input_evento_title';
    private const READD_FORM_ROLE = 'role_selection';
    private const READD_FORM_USER_ = 'user_';
    private const READD_FORM_EMPLOYEE = 'employee';
    private const READD_FORM_STUDENT = 'student';

    private const CMD_LIST_PARTICIPANTS = 'list_removed_participants';
    private const CMD_READD_PARTICIPANTS = 'readd_participants';

    private \ilDBInterface $db;
    private \ilCtrl $ctrl;
    private ServerRequestInterface $request;
    private MembershipablesEventInTreeSeeker $tree_seeker;
    private IliasEventoEventObjectRepository $repo;

    public function __construct(\ilDBInterface $db, \ilCtrl $ctrl, ServerRequestInterface $request, MembershipablesEventInTreeSeeker $tree_seeker)
    {
        $this->db = $db;
        $this->ctrl = $ctrl;
        $this->request = $request;
        $this->tree_seeker = $tree_seeker;
        $this->repo = new IliasEventoEventObjectRepository($this->db);
    }

    public function getTitle() : string
    {
        return 'Re Add removed Participants';
    }

    public function getScriptId() : string
    {
        return 're_add_removed_participants';
    }

    public function getParameterFormUI() : \ilPropertyFormGUI
    {
        $this->ctrl->setParameterByClass(\ilEventoImportUIRoutingGUI::class, 'script', $this->getScriptId());
        $url = $this->ctrl->getFormActionByClass(\ilEventoImportUIRoutingGUI::class);

        $form = new \ilPropertyFormGUI();
        $form->setFormAction($url);

        $radio = new \ilRadioGroupInputGUI('Identify Event by...', self::FORM_RADIO_POSTVAR);

        $by_evento_id = new \ilRadioOption('... Evento ID', self::FORM_RADIO_BY_EVENTO_ID);
        $by_evento_id->addSubItem(new \ilTextInputGUI('Evento ID', self::FORM_INPUT_EVENTO_ID));
        $radio->addOption($by_evento_id);

        $by_evento_title = new \ilRadioOption('... Evento Title', self::FORM_RADIO_BY_EVENTO_TITLE);
        $by_evento_title->addSubItem(new \ilTextInputGUI('Evento Title: ', self::FORM_INPUT_EVENTO_TITLE));
        $radio->addOption($by_evento_title);

        $form->addItem($radio);

        $form->addCommandButton(self::CMD_LIST_PARTICIPANTS, 'List removed participants');

        return $form;
    }

    public function getResultModalFromRequest(string $cmd, Factory $f) : Modal
    {
        switch($cmd) {
            case self::CMD_LIST_PARTICIPANTS:
                return $this->listParticipantsInModal($f);

            case self::CMD_READD_PARTICIPANTS:
                return $this->reAddParticipants($f);

        }

        throw new \InvalidArgumentException('Invalid command for script given: ' . htmlspecialchars($cmd));
    }

    private function listParticipantsInModal(Factory $f) : Modal
    {
        $repo = new IliasEventoEventObjectRepository($this->db);

        $form = $this->getParameterFormUI();
        if (!$form->checkInput()) {
            throw new \InvalidArgumentException('Invalid form input');
        }

        $radio_val = $form->getInput(self::FORM_RADIO_POSTVAR);

        if ($radio_val == self::FORM_RADIO_BY_EVENTO_TITLE) {
            $title = $form->getInput(self::FORM_INPUT_EVENTO_TITLE);
            $events = $repo->getIliasEventoEventsByTitle($title, false);
            if (count($events) != 1) {
                throw new \InvalidArgumentException(
                    'Error in searching Event by title! ' .
                        (
                            count($events) > 1
                            ? 'Duplicate in searched Evento Title'
                            : 'No Event found'
                        )
                );
            }

            $event = array_pop($events);
        } else if ($radio_val == self::FORM_RADIO_BY_EVENTO_ID) {
            $event = $repo->getEventByEventoId((int) $form->getInput(self::FORM_INPUT_EVENTO_ID));
        }

        if (is_null($event)) {
            throw new \InvalidArgumentException('No event found for given arguments');
        }

        $readd_form = $this->getFormWithRemovedUsers($event);

        return $this->buildModal($this->getTitle().': '. htmlspecialchars($event->getEventoTitle())."({$event->getEventoEventId()})", $readd_form->getHTML(), $f);
    }

    private function reAddParticipants($f) : Modal
    {
        $query_params = $this->request->getQueryParams();
        if (!isset($query_params['readd_to_event'])) {
            throw new \InvalidArgumentException('No Event ID to readd participants');
        }
        $event = $this->repo->getEventByEventoId((int) $query_params['readd_to_event']);

        $readd_form = $this->getFormWithRemovedUsers($event);
        if (!$readd_form->checkInput()) {
            throw new \InvalidArgumentException('Invalid form input');
        }

        $role = $readd_form->getInput(self::READD_FORM_ROLE);
        $users_to_add = [];
        foreach($this->getRemovedParticipantsForEventWithId($event) as $user) {
            $checked = $readd_form->getInput(self::READD_FORM_USER_ . $user->getId());
            if ($checked) {
                $users_to_add[] = $user;
            }
        }

        $this->addUserListToObject($event->getRefId(), $users_to_add, $role);
        foreach ($this->tree_seeker->getRefIdsOfParentMembershipables($event->getRefId()) as $ref_id) {
            $this->addUserListToObject($ref_id, $users_to_add, $role);
        }

        $modal_content = htmlspecialchars("Following Users have been successfully added as $role to course {$event->getEventoTitle()}({$event->getEventoEventId()})").'<br><ul>';
        foreach ($users_to_add as $user) {
            $modal_content .= '<li>' . htmlspecialchars("{$user->getFirstname()} {$user->getLastname()} ({$user->getLogin()})") . '</li>';
        }
        $modal_content .= '</ul>';

        return $this->buildModal($this->getTitle().': '. htmlspecialchars($event->getEventoTitle() . "({})"), $modal_content, $f);
    }

    private function addUserListToObject(int $ref_id, array $user_list, string $role)
    {
        $participants_obj = \ilParticipants::getInstance($ref_id);
        $role_code = $this->getRoleCodeForObjRefAndSelectedRole($role, $ref_id);

        foreach ($user_list as $user) {
            $participants_obj->add($user->getId(), $role_code);
        }
    }

    private function getRoleCodeForObjRefAndSelectedRole(string $role, int $ref_id) : int
    {
        $type = \ilObject::_lookupType($ref_id, true);
        if ($type == 'crs') {
            return $role == self::READD_FORM_EMPLOYEE ? IL_CRS_ADMIN : IL_CRS_MEMBER;
        } else if ($type == 'grp' ) {
            return $role == self::READD_FORM_EMPLOYEE ? IL_GRP_ADMIN : IL_GRP_MEMBER;
        }

        throw new \Exception('Object type is not membershipable: ' . $type);
    }

    private function getFormWithRemovedUsers(IliasEventoEvent $event) : \ilPropertyFormGUI
    {
        $this->ctrl->setParameterByClass(\ilEventoImportUIRoutingGUI::class, 'script', $this->getScriptId());
        $this->ctrl->setParameterByClass(\ilEventoImportUIRoutingGUI::class, 'readd_to_event', $event->getEventoEventId());
        $url = $this->ctrl->getFormActionByClass(\ilEventoImportUIRoutingGUI::class);
        $readd_form = new \ilPropertyFormGUI();
        $readd_form->setFormAction($url);

        $role_type =  new \ilSelectInputGUI('Role', self::READD_FORM_ROLE);
        $role_type->setOptions(
            [
                self::READD_FORM_STUDENT => 'Student',
                self::READD_FORM_EMPLOYEE => 'Employee'
            ]
        );
        $readd_form->addItem($role_type);

        foreach ($this->getRemovedParticipantsForEventWithId($event) as $user) {
            $checkbox = new \ilCheckboxInputGUI(
                "{$user->getFirstname()} {$user->getLastname()} ({$user->getLogin()})"
                , self::READD_FORM_USER_ . $user->getId()
            );
            $checkbox->setChecked(true);
            $readd_form->addItem($checkbox);
        }

        $readd_form->addCommandButton(self::CMD_READD_PARTICIPANTS, 'Re Add Users to Event');

        return $readd_form;
    }

    private function getRemovedParticipantsForEventWithId(IliasEventoEvent $event) : array
    {
        $participants_obj = \ilParticipants::getInstance($event->getRefId());
        $query = 'SELECT * FROM ' . Logger::TABLE_LOG_MEMBERSHIPS
            . ' WHERE evento_event_id = ' . $this->db->quote($event->getEventoEventId(), \ilDBConstants::T_TEXT)
            . ' AND (update_info_code = ' . Logger::CREVENTO_SUB_REMOVED
            . ' OR update_info_code = '.Logger::CREVENTO_SUB_ALREADY_DEASSIGNED . ')';

        $result = $this->db->query($query);
        $ids = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $user_id = (int) $row['evento_user_id'];
            if (!$participants_obj->isAssigned($user_id)) {
                $ids[] = new \ilObjUser($user_id);
            }
        }

        return $ids;
    }
}