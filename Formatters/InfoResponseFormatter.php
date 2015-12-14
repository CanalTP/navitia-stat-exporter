<?php
namespace CanalTP\NavitiaStatExporter\Formatters;

class InfoResponseFormatter
{
    public function format(array $data)
    {
        if (count($data) == 0) {
            # Should never happen
            return null;
        }

        return [ 'object_count' => $data[0]['object_count'] ];
    }
}
