<?php
namespace CanalTP\NavitiaStatExporter\Formatters;

class InterpretedParameterFormatter
{
    public function format(array $data, array $filters)
    {
        $filterFormatter = new FilterFormatter;
        $result = [];

        foreach($data as $param) {
            $interpretedParameterForResult = [ 'key' => $param['param_key'], 'value' => $param['param_value']];

            if (isset($filters[$param['id']]) && count($filters[$param['id']]) > 0) {
                $interpretedParameterForResult['filters'] = $filterFormatter->format($filters[$param['id']]);
            }
            $result[] = $interpretedParameterForResult;
        }

        return $result;
    }
}