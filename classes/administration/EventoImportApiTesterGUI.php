<?php declare(strict_types = 1);

namespace EventoImport\administration;

use ILIAS\DI\UIServices;
use ILIAS\UI\Component\Input\Container\Form\Form;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\Refinery\Factory;

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
    private ServerRequestInterface $request;
    private Factory $refinery;

    public function __construct(
        \ilEventoImportConfigGUI $parent_gui,
        EventoImportApiTester $api_tester,
        \ilSetting $settings,
        UIServices $ui_services,
        \ilCtrl $ctrl,
        \ilTree $tree,
        ServerRequestInterface $request,
        Factory $refinery
    ) {
        $this->parent_gui = $parent_gui;
        $this->ui_services = $ui_services;
        $this->ui_factory = $this->ui_services->factory();
        $this->ui_renderer = $this->ui_services->renderer();
        $this->settings = $settings;
        $this->tree = $tree;
        $this->ctrl = $ctrl;
        $this->request = $request;
        $this->refinery = $refinery;
        $this->api_tester = $api_tester;
    }

    public function getApiTesterFormAsString() : string
    {
        $link = $this->ctrl->getLinkTarget($this->parent_gui, 'parameterless');
        $ui_components[] = $this->ui_services->factory()->button()->standard("Fetch all ILIAS Admins", $link);
        $ui_components[] = $this->initDataRecordForm();
        $ui_components[] = $this->initDataSetForm();

        return $this->ui_renderer->render($ui_components);
    }

    public function fetchDataRecordFromFormInput(string $type, int $id) : string
    {
        try {
            $model = $this->api_tester->fetchDataRecord($type, $id);
            $cmd = htmlspecialchars('Fetch record by ID');
            $data = $model ? htmlspecialchars(print_r($model->getDecodedApiData(), true)) : 'No object received from API';
            return $this->buildMessageForNextPage("CMD = $cmd", $data);
        } catch (\ilEventoImportApiDataException $e) {
            \ilEventoImportPlugin::sendFailure('Delivered Data from API was invalid: ' . $e->getMessage(), true);
        } catch (\ilEventoImportCommunicationException $e) {
            \ilEventoImportPlugin::sendFailure('Communication error with API occured: ' . $e->getMessage(), true);
        } catch (\Exception $e) {
            \ilEventoImportPlugin::sendFailure("Error occured for paramerers: ", true);
        }

        return '';
    }

    public function fetchDataSetFromFormInput(string $type, int $skip, int $take) : string
    {
        try {
            $cmd = 'Fetch Data Set';
            $ret = '';
            foreach ($this->api_tester->fetchDataSet($type, $skip, $take) as $data_record) {
                $ret .= htmlspecialchars(print_r($data_record, true));
            }

            return $this->buildMessageForNextPage("CMD = $cmd, Skip = $skip, Take = $take", $ret);
        } catch (\ilEventoImportApiDataException $e) {
            \ilEventoImportPlugin::sendFailure('Delivered Data from API was invalid: ' . $e->getMessage(), true);
        } catch (\ilEventoImportCommunicationException $e) {
            \ilEventoImportPlugin::sendFailure('Communication error with API occured: ' . $e->getMessage(), true);
        } catch (\Exception $e) {
            \ilEventoImportPlugin::sendFailure("Error occured for paramerers CMD = $cmd, Skip = $skip, Take = $take", true);
        }

        return '';
    }

    public function fetchParameterlessDataset() : string
    {
        try {
            $data = '';
            $cmd = 'Fetch parameterless Data Set';

            foreach ($this->api_tester->fetchParameterlessDataset() as $data_record) {
                $data .= htmlspecialchars(print_r($data_record, true));
            }

            return $this->buildMessageForNextPage("CMD = $cmd", $data);
        } catch (\ilEventoImportApiDataException $e) {
            \ilEventoImportPlugin::sendFailure('Delivered Data from API was invalid: ' . $e->getMessage(), true);
        } catch (\ilEventoImportCommunicationException $e) {
            \ilEventoImportPlugin::sendFailure('Communication error with API occured: ' . $e->getMessage(), true);
        } catch (\Exception $e) {
            \ilEventoImportPlugin::sendFailure("Error occured for paramerers CMD = $cmd", true);
        }

        return '';
    }

    private function initDataRecordForm() : Form
    {
        $f = $this->ui_services->factory();
        $field = $f->input()->field();

        $inputs = [];
        $fetch_methods = [
            'user' => 'Fetch User by ID',
            'event' => 'Fetch Event by ID',
            'photo' => 'Fetch Photo by ID',
            'admin' => 'Fetch Admins for Event by Event ID'
        ];

        $inputs[] = $field->select(
            'Fetch Data Record',
            $fetch_methods
        )->withRequired(true)
         ->withAdditionalTransformation(
             $this->refinery->custom()->constraint(
                 function($v) use ($fetch_methods){
                     return isset($fetch_methods[$v]);
                 },
                 'Fetch Method not supported'
             )
         );

        $inputs[] = $field->numeric('ID')->withRequired(true);
        $section = $field->section($inputs, 'Fetch from Evento API by ID');

        return $f->input()->container()->form()->standard($this->ctrl->getFormAction($this->parent_gui, 'by_id'), [$section]);
    }

    private function initDataSetForm() : Form
    {
        $f = $this->ui_services->factory();
        $field = $f->input()->field();

        $inputs = [];
        $fetch_methods = [
            'user' => 'Fetch Users',
            'event' => 'Fetch Events'
        ];

        $valid_number = $this->refinery->int()->isGreaterThan(0);

        $inputs[] = $field->select(
            'Fetch Data Set',
            $fetch_methods
        )->withRequired(true)
         ->withAdditionalTransformation(
             $this->refinery->custom()->constraint(
                 function($v) use ($fetch_methods){
                     return isset($fetch_methods[$v]);
                 },
                 'Fetch Method not supported'
             )
         );

        $inputs[] = $field->numeric('Take')->withRequired(true)->withAdditionalTransformation($valid_number);
        $inputs[] = $field->numeric('Skip')->withRequired(true)->withAdditionalTransformation($valid_number);;
        $section = $field->section($inputs, 'Fetch data set from Evento API');

        return $f->input()->container()->form()->standard($this->ctrl->getFormAction($this->parent_gui, 'data_set'), [$section]);
    }

    private function buildMessageForNextPage(string $infos, string $output) : string
    {
        return "$infos<br><br>Result from request:<br><pre>$output</pre></div></div>";
    }

    public function getApiDataAsString($cmd) : string
    {
        if ($cmd === 'by_id') {
            $form = $this->initDataRecordForm()->withRequest($this->request);
            $data = $form->getData();

            if ($data) {
                return $this->fetchDataRecordFromFormInput(...$data[0]);
            }

            return "Form data Invalid";
        }

        if ($cmd === 'data_set') {
            $form = $this->initDataSetForm()->withRequest($this->request);
            $data = $form->getData();

            if ($data) {
                return $this->fetchDataSetFromFormInput(...$data[0]);
            }

            return "Form data Invalid";
        }

        if ($cmd === 'parameterless') {
            return $this->fetchParameterlessDataset();
        }

        return "Invalid command given: " . htmlspecialchars($cmd);
    }
}
