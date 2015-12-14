<?php
namespace CanalTP\NavitiaStatExporter\Formatters;

class CoverageFormatter
{
    public function format(array $data)
    {
        if (count($data) == 0) {
            # Special case to fit with existing code
            return [new \stdClass()];
        }

        $result = [];
        foreach($data as $cov) {
            $result[] = [ 'region_id' => $cov['region_id']];
        }

        return $result;
    }
}