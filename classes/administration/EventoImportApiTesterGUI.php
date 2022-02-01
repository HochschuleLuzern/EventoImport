<?php declare(strict_types = 1);

namespace EventoImport\administration;

use ILIAS\DI\UIServices;

class EventoImportApiTesterGUI
{
    private \ilEventoImportConfigGUI $parent_gui;
    private UIServices $ui_services;
    private \ilSetting $settings;
    private \ilTree $tree;
    private \ilCtrl $ctrl;
    private \ILIAS\UI\Factory $ui_factory;
    private \ILIAS\UI\Renderer $ui_renderer;
    private EventoImportApiTester $api_tester;

    public function __construct(
        \ilEventoImportConfigGUI $parent_gui,
        \ilSetting $settings,
        UIServices $ui_services,
        \ilCtrl $ctrl,
        \ilTree $tree
    ) {
        $this->parent_gui = $parent_gui;
        $this->ui_services = $ui_services;
        $this->ui_factory = $this->ui_services->factory();
        $this->ui_renderer = $this->ui_services->renderer();
        $this->settings = $settings;
        $this->tree = $tree;
        $this->ctrl = $ctrl;
        $this->api_tester = new EventoImportApiTester($this->settings);
    }

    public function getApiTesterFormAsString() : string
    {
        $link = $this->ctrl->getLinkTarget($this->parent_gui, 'fetch_all_ilias_admins');
        $ui_components[] = $this->ui_services->factory()->button()->standard("Fetch all ILIAS Admins", $link);

        return $this->ui_renderer->render($ui_components) . $this->initDataRecordForm()->getHTML() . $this->initDataSetForm()->getHTML();
    }

    public function fetchDataRecordFromFormInput(string $cmd) : string
    {
        $form = $this->initDataRecordForm();
        if ($form->checkInput()) {
            $id_from_form = (int) $form->getInput('record_id');

            try {
                $model = $this->api_tester->fetchDataRecord($cmd, $id_from_form);
                $cmd = htmlspecialchars($cmd);
                $data = htmlspecialchars(print_r($model->getDecodedApiData(), true));
                return $this->buildMessageForNextPage("CMD = $cmd, ID = $id_from_form", $data);
            } catch (\ilEventoImportApiDataException $e) {
                \ilUtil::sendFailure('Delivered Data from API was invalid: ' . $e->getMessage(), true);
            } catch (\ilEventoImportCommunicationException $e) {
                \ilUtil::sendFailure('Communication error with API occured: ' . $e->getMessage(), true);
            } catch (\Exception $e) {
                \ilUtil::sendFailure("Error occured for paramerers CMD = $cmd and ID = $id_from_form", true);
            }
        } else {
            throw new \InvalidArgumentException('Error in form input');
        }

        return '';
    }

    public function fetchDataSetFromFormInput(string $cmd) : string
    {
        $form = $this->initDataSetForm();
        if ($form->checkInput()) {
            $skip = (int) $form->getInput('skip');
            $take = (int) $form->getInput('take');

            try {
                $data = '';
                foreach ($this->api_tester->fetchDataSet($cmd, $skip, $take) as $data_record) {
                    $data = htmlspecialchars(print_r($data_record, true));
                }

                $cmd = htmlspecialchars($cmd);
                return $this->buildMessageForNextPage("CMD = $cmd, Skip = $skip, Take = $take", $data);
            } catch (\ilEventoImportApiDataException $e) {
                \ ilUtil::sendFailure('Delivered Data from API was invalid: ' . $e->getMessage(), true);
            } catch (\ilEventoImportCommunicationException $e) {
                \ilUtil::sendFailure('Communication error with API occured: ' . $e->getMessage(), true);
            } catch (\Exception $e) {
                \ilUtil::sendFailure("Error occured for paramerers CMD = $cmd, Skip = $skip, Take = $take", true);
            }
        } else {
            \ilUtil::sendFailure('Invalid form input for cmd fetch data set', true);
        }

        return '';
    }

    public function fetchParameterlessDataset(string $cmd) : string
    {
        try {
            $data = '';
            foreach ($this->api_tester->fetchParameterlessDataset($cmd) as $data_record) {
                $data .= htmlspecialchars(print_r($data_record, true));
            }

            $cmd = htmlspecialchars($cmd);
            return $this->buildMessageForNextPage("CMD = $cmd", $data);
        } catch (\ilEventoImportApiDataException $e) {
            \ilUtil::sendFailure('Delivered Data from API was invalid: ' . $e->getMessage(), true);
        } catch (\ilEventoImportCommunicationException $e) {
            \ilUtil::sendFailure('Communication error with API occured: ' . $e->getMessage(), true);
        } catch (\Exception $e) {
            \ilUtil::sendFailure("Error occured for paramerers CMD = $cmd, Skip = $skip, Take = $take", true);
        }

        return '';
    }

    private function initDataRecordForm() : \ilPropertyFormGUI
    {
        $form = new \ilPropertyFormGUI();
        $form->setTitle('Fetch Data Record');
        $form->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $form->addCommandButton('fetch_record_user', 'Fetch User');
        $form->addCommandButton('fetch_record_event', 'Fetch Event');
        $form->addCommandButton('fetch_user_photo', 'Fetch Photo');
        $form->addCommandButton('fetch_ilias_admins_for_event', 'Fetch Admins for Event');

        $take = new \ilNumberInputGUI('Id', 'record_id');
        $form->addItem($take);

        return $form;
    }

    private function initDataSetForm() : \ilPropertyFormGUI
    {
        $form = new \ilPropertyFormGUI();
        $form->setTitle('Fetch Data Set');
        $form->setFormAction($this->ctrl->getFormAction($this->parent_gui, 'fetch_data_set'));
        $form->addCommandButton('fetch_data_set_users', 'Fetch Users');
        $form->addCommandButton('fetch_data_set_events', 'Fetch Events');

        $take = new \ilNumberInputGUI('Take', 'take');
        $form->addItem($take);

        $skip = new \ilNumberInputGUI('Skip', 'skip');
        $form->addItem($skip);

        return $form;
    }

    private function buildMessageForNextPage(string $infos, string $output) : string
    {
        $message = "$infos<br><br>Output from request:<br><pre>$output</pre></div></div>";

        return $message;
    }
}
