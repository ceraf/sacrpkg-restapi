<?php

namespace sacrpkg\RestapiBundle\Model;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class ListData
{
	protected $sortfield_default = 'id';
	protected $sorttype_default = 'ASC';
    protected $doctrine;
    protected $entityname;
	protected $collection;
	protected $fields;
	protected $params;
    protected $sortby;
    protected $sorttype;	
	protected $use_translate = false;
	protected $filter;
	protected $request;
    protected $paginator;
	protected $itemsonpage = 50;

	abstract protected function init(): self;
	
    public function __construct (ManagerRegistry $doctrine,
                RequestStack $requestStack, Filter $filter, Paginator $paginator)
    {
        $this->doctrine = $doctrine;
        $this->request = $requestStack->getCurrentRequest();
        $this->filter = $filter;
        $this->paginator = $paginator;
		$this->init();
        $this->afterInit();
        $this->paginator->init($this->itemsonpage, $this->sortfield_default,
                                $this->sorttype_default);                      
    }
	
    public function setEntity($entity)
    {
        $this->entityname = $entity;
        return $this;
    }

    public function getData()
    {
        if (!$this->collection)
            $this->fetch();
            
		$res = [];	

		$res['url'] = $this->request->server->get('REQUEST_URI');	
		$res['data'] = $this->prepareCollection();
		$res = $this->addDataToResult($res);
        
        return $res;
    }
	
    protected function afterInit(): self
    {
        return $this;
    }
    
    protected function addDataToResult($data)
    {
        return $data;
    }
    
    public function setParam(string $name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }		
	
	public function setFilter(?array $filter)
	{
        $this->filter = $filter;
        return $this;
	}	
	
	protected function prepareCollection()
	{
		$res = [];

		if ($this->collection) {
			foreach ($this->collection as $item) {
				$line = [];

				foreach ($this->fields as $key => $field) {
					$method = 'get' . str_replace('_', '', ucwords($field['name'], '_'));
					$value = $item->$method() ?? '';
					if ($value instanceof Collection) {
						$childreen = [];
						if ($value->count()) {
							foreach ($value as $listitem) {
								$childreen[] = $this->getSimpleSettings($listitem, $field['collection_fields'] ?? null);
							}
						}
						$lineitem = $childreen;
					} elseif ($value instanceof $this->entityname) {
						$lineitem = $this->getSimpleSettings($value, $field['collection_fields'] ?? null);
					} elseif (gettype($value) === 'object'){
						$lineitem = $this->getSimpleSettings($value, $field['collection_fields'] ?? null);
					} else {
                        if (is_int($value))
                            $value = (string)$value;
						$lineitem = $value;
                    }
                    if (strpos($key, '::') !== false) {
                        $keys = explode('::', $key);
                        $key = $keys[0];
                        $keys = array_reverse(array_slice($keys, 1));
                        foreach($keys as $k)
                           $lineitem = [$k => $lineitem];
                    }
                    $lineitem = $this->modifyCollection($lineitem, $key);
                        
                    if ($line[$key] ?? null)
                        $line[$key] = array_merge($line[$key], $lineitem);
                    else   
                        $line[$key] = $lineitem;                   
				}

                $line = $this->modifyCollection($line);

				$res[] = $line;
			}
		}
		return $res;
	}
	
    protected function modifyCollection($line, $field = null)
    {
        return $line;
    }
    
	protected function getSimpleSettings($listitem, $fields = null)
	{
		$child = null;
		
        if (!$fields)
            $fields = $this->fields;
        
		foreach ($fields as $listitemkey => $listitemfield) {
			$listitemmethod = 'get' . str_replace('_', '', ucwords($listitemfield['name'], '_'));
			if (method_exists($listitem, $listitemmethod)) {
				$listitemvalue = $listitem->$listitemmethod() ?? '';
				if (!($listitemvalue instanceof Collection) && 
						!($listitemvalue instanceof $this->entityname && !is_object($listitemvalue)))
                {
					if (gettype($listitemvalue) != 'object')
						$child[$listitemkey] = $listitemvalue;
                } elseif ($listitemvalue instanceof Collection) {
					$childreen = [];
                    if ($listitemvalue->count()) {
						foreach ($listitemvalue as $listsubitem) {
                            $childreen[] = $this->getSimpleSettings($listsubitem, $listitemfield['collection_fields'] ?? null);
						}
					}
                    $child[$listitemkey] = $childreen;
                }
                $child[$listitemkey] = $this->modifyCollection($child[$listitemkey], $listitemkey);
			}
		}

		return $child;
	}    	
    protected function beforeGetCollection()
    {
    }

    protected function afterGetCollection()
    {
    }
    
    protected function fetch()
    {   
        try {
            $repository = $this->doctrine->getRepository($this->entityname);
                            
            if (method_exists($repository, 'getResrApiData')) {
                $this->beforeGetCollection();
                $this->collection = $repository->getResrApiData($this->paginator, $this->filter);
                $this->afterGetCollection();        
                
            } else {
                throw new \Exception('Database error.');
            }
        } catch (Exception $e) {
            throw new \Exception('Database error.');
        }
        return $this;
    }
}
