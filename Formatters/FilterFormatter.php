<?php
namespace CanalTP\NavitiaStatExporter\Formatters;

class FilterFormatter
{
    public function format(array $data)
    {
        $result = [];
        $fields = [
            'object' => 'object',
            'attribute' => 'attribute',
            'operator' => 'operator',
            'value' => 'value',
        ];
        foreach($data as $filter) {
            $filterForResult = [];
            foreach($fields as $dbField => $targetField) {
                if(!is_null($filter[$dbField])) {
                    $filterForResult[$targetField] = $filter[$dbField];
                }
            }

            $result[] = $filterForResult;
        }

        return $result;
    }
}