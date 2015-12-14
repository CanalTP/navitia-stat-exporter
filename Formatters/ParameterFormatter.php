<?php
namespace CanalTP\NavitiaStatExporter\Formatters;

class ParameterFormatter
{
    public function format(array $data)
    {
        $result = [];
        foreach($data as $param) {
            $result[] = [ 'key' => $param['param_key'], 'value' => $param['param_value']];
        }

        return $result;
    }
}