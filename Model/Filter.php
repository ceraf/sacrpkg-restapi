<?php

namespace sacrpkg\RestapiBundle\Model;

use Symfony\Component\HttpFoundation\RequestStack;

class Filter
{
    const TYPE_SEARCH = 'search';
    const TYPE_INT = 'int';
    const TYPE_ARRAY_INT = 'array_int';
    const TYPE_STRING = 'string';
    const TYPE_ARRAY_STR = 'array_str';
    const TYPE_ARRAY_OPTION = 'array_option';
    
    private $request;
    private $settings = null;
    private $data = null;
    
    public function __construct (RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function init(Array $settings): self
    {
        $this->settings = $settings;
/*
            foreach (self::FILTER_PARAMS as $key => $item) {
                if ($request->get($key, null)) {
                    if ($item['type'] == 'array')
                       $filter[$key] = explode(',', $request->get($key, null));
                    elseif ($item['type'] == 'array_int')
                        $filter[$key] = array_map('intval', explode(',', $request->get($key, null)));
                    elseif  ($item['type'] == 'range') {
                        if (($request->get($key))['from'] ?? null)
                            $filter[$key.'_from'] = ($request->get($key))['from'];
                        if (($request->get($key))['to'] ?? null)
                            $filter[$key.'_to'] = ($request->get($key))['to'];
                    } elseif  ($item['type'] == 'bool') {
                        $boolfield = $request->get($key, null);
                        if (in_array($boolfield, ['true', 'false'])) {
                            $boolfield = ($boolfield == 'true') ? true : false;
                            $filter[$key] = $boolfield;
                        }
                    } else
                        $filter[$key] = $request->get($key, null); 
                }
            }
*/
        if (!empty($this->settings)) {
            foreach ($this->settings as $key => $item) {
                $request_value = $this->request->get($key, null);
                if (!is_null($request_value)) {
                    $method = 'to' . str_replace('_', '', ucwords($item['type'], '_'));
                    $this->data[$item['name']] = ['type' => $item['type'], 
                            'value' => $this->$method($request_value, $item)];
                } elseif ($item['default'] ?? null) {
                    $this->data[$item['name']] = ['type' => $item['type'], 'value' => $item['default']];
                }
            }
        }

        return $this;
    }
    
    public function isUseFilter(): bool
    {
        return !is_null($this->data);
    }

    public function getData()
    {
        return $this->data;
    }

    public function hasField($field_name)
    {
        return isset($this->data[$field_name]);
    }

    protected function toArrayOption($value, $params = [])
    {
        return array_filter(explode(',', $value), function($item) use ($params){
            return in_array($item, $params['options'] ?? []);
        });
    }

    protected function toArrayStr($value, $params = [])
    {
        return explode(',', $value);
    }

    protected function toString($value, $params = [])
    {
        return (string)$value;
    }
 
    protected function toArrayInt($value, $params = [])
    {
        return array_map('intval', explode(',', $value));
    }
 
    protected function toInt($value, $params = [])
    {
        return (int)$value;
    }
    
    protected function toSearch($value, $params = [])
    {
        $default_accuracy = 'includes';
        if (is_array($value)) {
            foreach(array_keys($value) as $key) {
                if (strpos($key, 'accuracy') !== false) {
                    list($accuracy, $typeaccuracy) = explode('=', $key);
                    if (in_array($typeaccuracy ?? '', ['exact', 'start_with', 'includes'])) {
                        $res = ['type_serach' => $typeaccuracy, 
                                'value' => $this->toString($value[$key])
                        ];
                        break;
                    }
                }
                $res = ['type_serach' => $default_accuracy, 'value' => $this->toString($value[$key])];
            }
        } else
            $res = ['type_serach' => $default_accuracy, 'value' => $this->toString($value)];
        
        return $res;
    }    
}
