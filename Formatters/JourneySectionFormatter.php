<?php
namespace CanalTP\NavitiaStatExporter\Formatters;

class JourneySectionFormatter
{
    public function format(array $data)
    {
        $fields = [
            "duration" => "duration",
            "type" => "type",
            "mode" => "mode",
            "from_embedded_type" => "from_embedded_type",
            "from_id" => "from_id",
            "from_name" => "from_name",
            "from_admin_id" => "from_admin_id",
            "from_admin_name" => "from_admin_name",
            "to_id" => "to_id",
            "to_name" => "to_name",
            "to_admin_id" => "to_admin_id",
            "to_admin_name" => "to_admin_name",
            "vehicle_journey_id" => "vehicle_journey_id",
            "line_id" => "line_id",
            "line_code" => "line_code",
            "route_id" => "route_id",
            "network_id" => "network_id",
            "network_name" => "network_name",
            "physical_mode_id" => "physical_mode_id",
            "physical_mode_name" => "physical_mode_name",
            "commercial_mode_id" => "commercial_mode_id",
            "commercial_mode_name" => "commercial_mode_name",
            "from_admin_insee" => "from_admin_insee",
            "to_admin_insee" => "to_admin_insee",
        ];
        $result = [];
        foreach($data as $journeySection) {
            $journeySectionForResult = [];
            $journeySectionForResult['departure_date_time'] = \DateTime::createFromFormat('Y-m-d H:i:s', $journeySection['departure_date_time'], new \DateTimeZone('UTC'))->getTimestamp();
            $journeySectionForResult['arrival_date_time'] = \DateTime::createFromFormat('Y-m-d H:i:s', $journeySection['arrival_date_time'], new \DateTimeZone('UTC'))->getTimestamp();
            foreach($fields as $dbField => $targetField) {
                if(!is_null($journeySection[$dbField])) {
                    $journeySectionForResult[$targetField] = $journeySection[$dbField];
                }
            }
            if ($journeySection['from_x'] != 0 || $journeySection['from_y'] != 0) {
                $journeySectionForResult['from_coord'] = [
                    'lon' => (double) $journeySection['from_x'],
                    'lat' => (double) $journeySection['from_y'],
                ];
            }
            if ($journeySection['to_x'] != 0 || $journeySection['to_y'] != 0) {
                $journeySectionForResult['to_coord'] = [
                    'lon' => (double) $journeySection['to_x'],
                    'lat' => (double) $journeySection['to_y'],
                ];
            }

            $result[] = $journeySectionForResult;
        }

        return $result;
    }
}