<?php

namespace sacrpkg\RestapiBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use sacrpkg\RestapiBundle\Model\ListData;

abstract class AbstractRestController extends AbstractFOSRestController
{
    protected $site;
    
    protected function getView($data, $code): View 
    {
        return $this->view($data, $code, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Authorization, DNT, X-User-Token, Keep-Alive, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type',
                'Access-Control-Max-Age' => 1728000,
        ]);
    }
    
	protected function getResponseList(ListData $list)
	{
        $view = $this->view($list->getData(), Response::HTTP_OK);
        return $this->handleView($view);                
	}
}
