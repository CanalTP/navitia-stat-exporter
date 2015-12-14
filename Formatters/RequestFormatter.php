<?php
namespace CanalTP\NavitiaStatExporter\Formatters;

class RequestFormatter
{
    public function format(array $data)
    {
        $result = [];
        $result['request_date'] = \DateTime::createFromFormat('Y-m-d H:i:s', $data['request_date'], new \DateTimeZone('UTC'))->getTimestamp();
        $fields = [
            'user_id' => 'user_id',
            'user_name' => 'user_name',
            'app_id' => 'application_id',
            'app_name' => 'application_name',
            'request_duration' => 'request_duration',
            'api' => 'api',
            'host' => 'host',
            'client' => 'client',
            'response_size' => 'response_size',
            'end_point_id' => 'end_point_id',
            'end_point_name' => 'end_point_name',
        ];
        foreach($fields as $dbField => $targetField) {
            if(!is_null($data[$dbField])) {
                $result[$targetField] = $data[$dbField];
            }
        }

        return $result;
    }
}