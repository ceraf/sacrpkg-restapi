<?php

namespace sacrpkg\RestapiBundle\Model;

use Symfony\Component\HttpFoundation\RequestStack;

class Paginator
{
    private $request;
    private $items_on_page;
    private $curr_page;
    private $sortby;
    private $sorttype;
    private $sort_fields = ['id', 'uri'];
    protected $params;
    
    public function __construct (RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }
    
    public function setSortFields(Array $sort_fields): self
    {
        $this->sort_fields = $sort_fields;
        
        return $this;
    }
    
    public function getParam($name)
    {
        return $this->params[$name] ?? null;
    }
    
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        
        return $this;
    }
    
    public function init($items_on_page, $sortby, $sorttype)
    {
		$count = (int)$this->request->get('count', 0);
		$this->items_on_page = ($count && ($count <= 10000)) ? 
                            $count : 0;
        $this->items_on_page = ($this->items_on_page) ? $this->items_on_page : $items_on_page;
        
        $this->curr_page = (int)$this->request->get('page', 0);
		if (!$this->curr_page)
			$this->curr_page = 1;
		
		$this->curr_page--;

        $sort_param = $this->request->get('sort', null);
        if (in_array($sort_param[0] ?? null, ['-', '+'])) {
            $this->sorttype = ($sort_param[0] == '+') ? 'ASC' : 'DESC';
            $sort_param = substr($sort_param, 1);
        }

        $this->sortby = (in_array($sort_param, $this->sort_fields)) ? $sort_param : $sortby;
        $this->sorttype = $this->sorttype ?? $sorttype;
    }
    
    public function getSortBy(): string
    {
        return $this->sortby;
    }
    
    public function getSortType(): string
    {
        return $this->sorttype;
    }
    
    public function getCurrPage(): int
    {
        return $this->curr_page;
    }
    
    public function getItemsOnPage(): int
    {
        return $this->items_on_page;
    }
}
