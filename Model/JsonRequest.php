<?php

namespace sacrpkg\RestapiBundle\Model;

use Symfony\Component\HttpFoundation\Request;

class JsonRequest
{
    private $request;
    private $data;
    private $params;
    
    public static function createFromRequest(Request $request, array $params)
    {
        return new self($request, $params);
    }
    
    public function __construct (Request $request, array $params)
    {
        $this->request = $request;
        $this->params = $params;
        $this->init();
    }
    
    public function setSimpleParam($name, $value): self
    {
        $this->simpledata[$name] = $value;
        return $this;
    }
    
    public function getRequest(): Request
    {
        return $this->request;
    }
    
    public function getSimpleData(): array
    {
        return $this->simpledata;
    }
    
    public function getObject($name): array
    {
        return $this->$name;
    }
    
    public function getData($name = null)
    {
        if ($name)
            return $this->data[$name];
        else
            return $this->data;
    }
    
    private function init(): self
    {
        $this->simpledata = [];
        $jsondata = json_decode($this->request->getContent(), true);

        $data = $jsondata['data'] ?? null;
        $this->data = $data;

        if ($this->params && is_array($this->params)) {
            foreach ($this->params as $key => $value) {
                if (strpos($key, '.') !== false) {
                    list($objectname, $paramname) = explode('.', $key);
                    $val = $data[$objectname][$paramname] ?? null;
                    if (!isset($this->$objectname))
                        $this->$objectname = [];
                    if (!is_null($val))
                        $this->$objectname[$value['name']] = $val;
                    if ($value['simple'] ?? null)
                        $this->simpledata[$value['simple']] = $val;
                } else {
                    $val = $data[$key] ?? null;
                    if (!is_null($val)) {
                        if ($value['type'] == 'bool')
                            $val = ($val == true) ? true : false;

                        $this->simpledata[$value['name']] = $val;
                    }
                }
            }
        }

        return $this;
    }
}
