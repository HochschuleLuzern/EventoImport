<?php declare(strict_types=1);

namespace EventoImport\administration\scripts;

use ILIAS\UI\Factory;
use ILIAS\UI\Component\Modal\Modal;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;
use Psr\Http\Message\ServerRequestInterface;
use EventoImport\import\data_management\repository\model\IliasEventoEvent;

class SwitchIliasObjectForEventoEvent implements AdminScriptInterface
{
    use AdminScriptCommonMethods;

    private const FORM_EVENTO_ID = 'evento_id';
    private const FORM_TARGET_REF_ID = 'target_ref_id';

    private const CMD_CHECK_AND_SHOW_SWITCH = 'check_and_show_switch';
    private const CMD_APPROVE_SWITCH = 'approve_switch';

    private \ilDBInterface $db;
    private \ilCtrl $ctrl;
    private ServerRequestInterface $request;
    private \ilTree $tree;
    private IliasEventoEventObjectRepository $repo;

    public function __construct(\ilDBInterface $db, \ilCtrl $ctrl, ServerRequestInterface $request, \ilTree $tree)
    {
        $this->db = $db;
        $this->ctrl = $ctrl;
        $this->request = $request;
        $this->tree = $tree;

        $this->repo = new IliasEventoEventObjectRepository($this->db);
    }

    public function getTitle() : string
    {
        return 'Switch ILIAS Object for Evento Event';
    }

    public function getScriptId() : string
    {
        return 'switch_ilias_obj_for_evento_event';
    }

    public function getParameterFormUI() : \ilPropertyFormGUI
    {
        $this->ctrl->setParameterByClass(\ilEventoImportUIRoutingGUI::class, 'script', $this->getScriptId());
        $url = $this->ctrl->getFormActionByClass(\ilEventoImportUIRoutingGUI::class);
        $form = new \ilPropertyFormGUI();
        $form->setFormAction($url);

        $evento_id_input = new \ilNumberInputGUI('Evento ID', self::FORM_EVENTO_ID);
        $evento_id_input->setInfo('Evento ID of the Evento Event which should be set to another ILIAS Object');
        $form->addItem($evento_id_input);

        $target_ref_id_input = new \ilNumberInputGUI('Target Ref ID', self::FORM_TARGET_REF_ID);
        $target_ref_id_input->setInfo('Reference ID of target object which will will be synchronized with given Evento Event');
        $form->addItem($target_ref_id_input);

        $form->addCommandButton(self::CMD_CHECK_AND_SHOW_SWITCH, 'Check');

        return $form;
    }

    public function getResultModalFromRequest(string $cmd, Factory $f) : Modal
    {
        switch($cmd) {
            case self::CMD_CHECK_AND_SHOW_SWITCH:
                return $this->showSelectedObjectsForSwitch($f);

            case self::CMD_APPROVE_SWITCH:
                return $this->switchEventoEventToIliasObject($f);
        }

        throw new \InvalidArgumentException('Invalid command for script given: ' . htmlspecialchars($cmd));
    }

    private function showSelectedObjectsForSwitch(Factory $f) : Modal
    {
        $form = $this->getParameterFormUI();

        if (!$form->checkInput()) {
            throw new \InvalidArgumentException($this->getTitle(), 'Invalid Form Input');
        }

        $evento_id = (int) $form->getInput(self::FORM_EVENTO_ID);
        $ilias_evento_event = $this->repo->getEventByEventoId($evento_id);
        $current_ilias_object = $this->getIliasObjectByRefId($ilias_evento_event->getRefId());

        $ref_id = (int) $form->getInput(self::FORM_TARGET_REF_ID);
        $target_ilias_object = $this->getIliasObjectByRefId($ref_id);

        $display_text = "<em>Selected Event Object:</em><br>" . $this->getIliasEventoEventAsPrintableHTMLString($ilias_evento_event, false);
        $display_text .= "<em>Currently Connected ILIAS Object</em>:<br>-> Object ID = ".$current_ilias_object->getId()
            ."<br>-> Reference ID = ". $current_ilias_object->getRefId()
            ."<br>-> Title = ".htmlspecialchars($current_ilias_object->getTitle())
            ."<br>-> Description = ".htmlspecialchars($current_ilias_object->getDescription()).'<br><hr><br>';

        $display_text .= "<em>Selected Target ILIAS Object:</em><br>-> Object ID = ".$target_ilias_object->getId()
            ."<br>-> Reference ID = ". $target_ilias_object->getRefId()
            ."<br>-> ILIAS Object Title = ".htmlspecialchars($target_ilias_object->getTitle())
            ."<br>-> ILIAS Object Description = ".htmlspecialchars($target_ilias_object->getDescription());

        $this->ctrl->setParameterByClass(\ilEventoImportUIRoutingGUI::class, 'script', $this->getScriptId());
        $this->ctrl->setParameterByClass(\ilEventoImportUIRoutingGUI::class, 'evento_id', $evento_id);
        $this->ctrl->setParameterByClass(\ilEventoImportUIRoutingGUI::class, 'target_obj_ref', $ref_id);
        $url = $this->ctrl->getFormActionByClass(\ilEventoImportUIRoutingGUI::class, self::CMD_APPROVE_SWITCH);
        $modal = $f->modal()->interruptive($this->getTitle() . ': ' . htmlspecialchars($ilias_evento_event->getEventoTitle()),$display_text,$url);
        return $modal->withActionButtonLabel('Switch');
    }

    private function switchEventoEventToIliasObject(Factory $f) : Modal
    {
        $query_params = $this->request->getQueryParams();

        $evento_id = (int) $query_params['evento_id'];
        $ilias_evento_event = $this->repo->getEventByEventoId($evento_id);
        $current_ilias_object = $this->getIliasObjectByRefId($ilias_evento_event->getRefId());

        $target_obj_ref_id = (int) $query_params['target_obj_ref'];
        $target_ilias_object = $this->getIliasObjectByRefId($target_obj_ref_id);

        if ($target_ilias_object instanceof \ilObjCourse) {
            $changed_ilias_evento_event = $ilias_evento_event->switchToAnotherIliasObejct($target_ilias_object);
            $this->repo->updateIliasEventoEvent($changed_ilias_evento_event);
        } elseif ($target_ilias_object instanceof \ilObjGroup) {
            if (!$this->isChildOfCourseObject($target_ilias_object)) {
                throw new \InvalidArgumentException('Target group object is not child of course object. Events MUST be a course object or a group object, which is in a course object');
            }

            $importer = $this->buildEventImporter($this->db);
            $was_auto_created = false;
            $parent_key = '';
            try {
                $evento_event = $importer->fetchEventDataRecordById($ilias_evento_event->getEventoEventId());
                if (!is_null($evento_event) && $evento_event->getGroupUniqueKey()) {
                    $parent_event = $this->repo->getParentEventbyGroupUniqueKey($evento_event->getGroupUniqueKey());
                    if (isset($parent_event) && $this->tree->isGrandChild($parent_event->getRefId(), $target_ilias_object->getRefId())) {
                        $parent_key = $parent_event->getGroupUniqueKey();
                        $was_auto_created = true;
                    }
                }
            } catch (\Exception $e) {
                // Whatever fails here (probably REST-Request) doesn't matter. This Try-Block just "tries" to find a parent event
            }

            $changed_ilias_evento_event = $ilias_evento_event->switchToAnotherIliasObejct($target_ilias_object, $was_auto_created, $parent_key);
            $this->repo->updateIliasEventoEvent($changed_ilias_evento_event);
        } else {
            throw new \InvalidArgumentException('Target Object ist not of type Course or Group');
        }

        $display_text = "<em>Values BEFORE the switch:</em><br>" . $this->getIliasEventoEventAsPrintableHTMLString($ilias_evento_event, true) . '<br><br>';
        $display_text .= "<em>Values AFTER the switch:</em><br>" . $this->getIliasEventoEventAsPrintableHTMLString($changed_ilias_evento_event, true);

        return $this->buildModal($this->getTitle() . ': ' . $ilias_evento_event->getEventoTitle(), $display_text, $f);
    }

    private function getIliasObjectByRefId(int $ref_id) : \ilObject
    {
        $type = \ilObject::_lookupType($ref_id, true);
        if ($type == 'crs') {
            return new \ilObjCourse($ref_id, true);
        } elseif ($type == 'grp') {
            return new \ilObjGroup($ref_id, true);
        }

        throw new \InvalidArgumentException('Given ref_id was of Type ' . htmlspecialchars($type) . '. Evento Event must be of Type Course or Group.');
    }

    private function isChildOfCourseObject(\ilObjGroup $obj) : bool
    {
        $current_ref = $obj->getRefId();
        $deadlock_prevention = 10;

        do {
            $current_ref = $this->tree->getParentId($current_ref);
            $type = \ilObject::_lookupType($current_ref, true);
            if ($type == 'crs') {
                return true;
            } else if ($type == 'cat' || $type == 'root') {
                return false;
            }
        } while ($deadlock_prevention > 0);

        throw new \ilException('');
    }

    private function getIliasEventoEventAsPrintableHTMLString(IliasEventoEvent $ilias_evento_event, bool $with_ilias_obj_infos) : string
    {
        $r = "-> Evento Event ID = ".$ilias_evento_event->getEventoEventId()
            ."<br>-> Evento Parent Key = ". $ilias_evento_event->getParentEventKey()
            ."<br>-> Evento Title = ".htmlspecialchars($ilias_evento_event->getEventoTitle())
            ."<br>-> Evento Description = ".htmlspecialchars($ilias_evento_event->getEventoDescription())
            ."<br>-> Auto Created = " . ($ilias_evento_event->wasAutomaticallyCreated() ? "True" : "False");
        if ($with_ilias_obj_infos) {
            $ilias_obj = $this->getIliasObjectByRefId($ilias_evento_event->getRefId());
            $r .= "<br>-> Reference ID = ". $ilias_obj->getRefId()
            ."<br>-> ILIAS Object Title = ".htmlspecialchars($ilias_obj->getTitle())
            ."<br>-> ILIAS Object Description = ".htmlspecialchars($ilias_obj->getDescription());
        }

        $r .= '<br><br>';

        return $r;
    }
}