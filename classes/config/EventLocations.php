<?php declare(strict_type=1);

namespace EventoImport\config;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\config\EventLocationsRepository;

class EventLocations
{
    private array $locations;

    public function __construct(EventLocationsRepository $location_repo)
    {
        $this->locations = $location_repo->getAllLocationsAsHirarchicalArray();
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

    public function getLocationRefIdForEventoEvent(EventoEvent $evento_event) : int
    {
        return $this->getLocationRefIdForParameters(
            $evento_event->getDepartment(),
            $evento_event->getKind(),
            (int) $evento_event->getStartDate()->format('Y')
        );
    }
}
