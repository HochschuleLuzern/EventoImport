<?php declare(strict_type=1);

namespace EventoImport\config;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\config\locations\EventLocationsRepository;
use EventoImport\config\locations\RepositoryLocationSeeker;
use EventoImport\config\locations\EventLocationCategoryBuilder;

class EventLocations
{
    private array $locations;
    private EventLocationsRepository $locations_repo;
    private RepositoryLocationSeeker $location_seeker;
    private EventLocationCategoryBuilder $event_location_builder;

    public function __construct(EventLocationsRepository $location_repo, RepositoryLocationSeeker $location_seeker, EventLocationCategoryBuilder $event_location_builder)
    {
        $this->locations_repo = $location_repo;
        $this->locations = $location_repo->getAllLocationsAsHirarchicalArray();
        $this->location_seeker = $location_seeker;
        $this->event_location_builder = $event_location_builder;
    }

    public function getLocationRefIdForParameters(string $department, string $kind, int $year) : int
    {
        if (!isset($this->locations[$department])) {
            throw new \ilEventoImportEventLocationNotFoundException(
                "Location for department '$department' not found",
                \ilEventoImportEventLocationNotFoundException::MISSING_DEPARTMENT
            );
        }

        if (!isset($this->locations[$department][$kind])) {
            throw new \ilEventoImportEventLocationNotFoundException(
                "Location for kind '$kind' in department '$department' not found",
                \ilEventoImportEventLocationNotFoundException::MISSING_KIND
            );
        }

        if (!isset($this->locations[$department][$kind][$year])) {
            throw new \ilEventoImportEventLocationNotFoundException(
                "Location for year '$year' in kind '$kind' in department '$department' not found",
                \ilEventoImportEventLocationNotFoundException::MISSING_YEAR
            );
        }

        return $this->locations[$department][$kind][$year];
    }

    public function getLocationRefIdForEventoEvent(EventoEvent $evento_event, bool $create_year_cat_if_not_existing) : ?int
    {
        $event_year = (int) $evento_event->getStartDate()->format('Y');

        try {
            $ref_id = $this->getLocationRefIdForParameters(
                $evento_event->getDepartment(),
                $evento_event->getKind(),
                $event_year
            );
        } catch (\ilEventoImportEventLocationNotFoundException $e) {
            if ($e->getCode() == \ilEventoImportEventLocationNotFoundException::MISSING_YEAR && $create_year_cat_if_not_existing) {
                $ref_id = $this->tryToCreateLocationForEventoEvent(
                    $evento_event->getDepartment(),
                    $evento_event->getKind(),
                    $event_year
                );
            } else {
                $ref_id = null;
            }
        } catch (\Exception $e) {
            $ref_id = null;
        }

        return $ref_id;
    }

    private function tryToCreateLocationForEventoEvent(string $department_short_name, string $kind, int $year) : ?int
    {
        try {
            $kind_ref_id = $this->location_seeker->searchRefIdOfKindCategory(
                $this->getMappedDepartmentCatName($department_short_name),
                $kind
            );

            if (!is_null($kind_ref_id)) {
                $cat_ref_id = $this->event_location_builder->createNewLocationObjectAndReturnRefId($kind_ref_id, "$year");

                $this->locations_repo->addNewLocation(
                    $department_short_name,
                    $kind,
                    $year,
                    $cat_ref_id
                );
                $this->locations[$department_short_name][$kind][$year] = $cat_ref_id;

                return $cat_ref_id;
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    private function getMappedDepartmentCatName(string $department_short) : string
    {
        $hardcoded_locations = [
            "HSLU" => "Hochschule Luzern",
            "DK" => "Design & Kunst",
            "I" => "Informatik",
            "M" => "Musik",
            "SA" => "Soziale Arbeit",
            "TA" => "Technik & Architektur",
            "W" => "Wirtschaft"
        ];

        if (!isset($hardcoded_locations[$department_short])) {
            throw new \Exception('Given short department does not exists');
        }

        return $hardcoded_locations[$department_short];
    }
}
