<?php
namespace CanalTP\NavitiaStatExporter\Formatters;

class JourneyRequestFormatter
{
    public function format(array $data)
    {
        if (count($data) == 0) {
            # Should not happen
            return null;
        }

        $result = [];
        $result['requested_date_time'] = \DateTime::createFromFormat('Y-m-d H:i:s', $data[0]['requested_date_time'], new \DateTimeZone('UTC'))->getTimestamp();
        $result['clockwise'] = (bool) $data[0]['clockwise'];
        $fields = [
            'departure_insee' => 'departure_insee',
            'departure_admin' => 'departure_admin',
            'arrival_insee' => 'arrival_insee',
            'arrival_admin' => 'arrival_admin',
            'departure_admin_name' => 'departure_admin_name',
            'arrival_admin_name' => 'arrival_admin_name',
        ];
        foreach($fields as $dbField => $targetField) {
            if(!is_null($data[0][$dbField])) {
                $result[$targetField] = $data[0][$dbField];
            }
        }

        return $result;
    }
}