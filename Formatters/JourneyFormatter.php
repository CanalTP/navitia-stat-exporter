<?php
namespace CanalTP\NavitiaStatExporter\Formatters;

class JourneyFormatter
{
    public function format(array $data, array $sections)
    {
        $journeySectionFormatter = new JourneySectionFormatter;

        $fields = [
            "duration" => "duration",
            "nb_transfers" => "nb_transfers",
            "type" => "type",
            "first_pt_id" => "first_pt_id",
            "first_pt_name" => "first_pt_name",
            "first_pt_admin_id" => "first_pt_admin_id",
            "first_pt_admin_name" => "first_pt_admin_name",
            "last_pt_id" => "last_pt_id",
            "last_pt_name" => "last_pt_name",
            "last_pt_admin_id" => "last_pt_admin_id",
            "last_pt_admin_name" => "last_pt_admin_name",
        ];
        $result = [];

        foreach($data as $journey) {
            $journeyForResult = [];
            $journeyForResult['requested_date_time'] = \DateTime::createFromFormat('Y-m-d H:i:s', $journey['requested_date_time'], new \DateTimeZone('UTC'))->getTimestamp();
            $journeyForResult['departure_date_time'] = \DateTime::createFromFormat('Y-m-d H:i:s', $journey['departure_date_time'], new \DateTimeZone('UTC'))->getTimestamp();
            $journeyForResult['arrival_date_time'] = \DateTime::createFromFormat('Y-m-d H:i:s', $journey['arrival_date_time'], new \DateTimeZone('UTC'))->getTimestamp();
            foreach($fields as $dbField => $targetField) {
                if(!is_null($journey[$dbField])) {
                    $journeyForResult[$targetField] = $journey[$dbField];
                }
            }
            if ($journey['first_pt_x'] != 0 || $journey['first_pt_y'] != 0) {
                $journeyForResult['first_pt_coord'] = [
                    'lon' => (double) $journey['first_pt_x'],
                    'lat' => (double) $journey['first_pt_y'],
                ];
            }
            if ($journey['last_pt_x'] != 0 || $journey['last_pt_y'] != 0) {
                $journeyForResult['last_pt_coord'] = [
                    'lon' => (double) $journey['last_pt_x'],
                    'lat' => (double) $journey['last_pt_y'],
                ];
            }

            $journeySections = isset($sections[$journey['id']]) ? $sections[$journey['id']] : [];
            $journeyForResult['sections'] = $journeySectionFormatter->format($journeySections);

            $result[] = $journeyForResult;
        }

        return $result;
    }
}