<?php

class ilEventoImportApiTesterGUI
{
    public function __construct($parent_gui, ilSetting $settings = null, ilCtrl $ctrl = null)
    {
        global $DIC;

        $this->settings = $setttings ?? new ilSetting('crevento');
        $this->tree = $tree ?? $DIC->repositoryTree();
        $this->ctrl = $ctrl ?? $DIC->ctrl();
    }

    public function getApiTesterFormAsString()
    {
        return $this->initDataRecordForm()->getHTML() . $this->initDataSetForm()->getHTML();
    }

    private function initDataRecordForm() : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle('Fetch Data Record');
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->addCommandButton('fetch_record_user', 'Fetch User');
        $form->addCommandButton('fetch_record_event', 'Fetch Event');
        $form->addCommandButton('fetch_user_photo', 'Fetch Photo');
        $form->addCommandButton('fetch_ilias_admins_for_event', 'Fetch Admins for Event');

        $take = new ilNumberInputGUI('Id', 'record_id');
        $form->addItem($take);

        return $form;
    }

    private function initDataSetForm() : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle('Fetch Data Set');
        $form->setFormAction($this->ctrl->getFormAction($this, 'fetch_data_set'));
        $form->addCommandButton('fetch_data_set_users', 'Fetch Users');
        $form->addCommandButton('fetch_data_set_events', 'Fetch Events');

        $take = new ilNumberInputGUI('Take', 'take');
        $form->addItem($take);

        $skip = new ilNumberInputGUI('Skip', 'skip');
        $form->addItem($skip);

        return $form;
    }


}