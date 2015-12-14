<?php
namespace CanalTP\NavitiaStatExporter\Formatters;

class ErrorFormatter
{
    public function format(array $data)
    {
        if (count($data) == 0) {
            # Special case to fit with existing code
            return null;
        }

        return [ 'message' => $data[0]['message'], 'id' => $data[0]['id'] ];
    }
}
