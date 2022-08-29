<?php

namespace sacrpkg\RestapiBundle\Model\Action;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManager;
use App\Model\JsonRequest;

abstract class ActionAbstract implements ActionInterface
{
    protected $doctrine;
    protected $request;
    protected $entity;
    protected $params;
    protected $validator;
    protected $beforesave;
    protected $aftersave;
    protected $beforepersist;
    protected $jsonrequest;
    
    public function __construct (ManagerRegistry $doctrine, Request $request, ValidatorInterface $validator)
    {
        $this->doctrine = $doctrine;
        $this->request = $request;
        $this->validator = $validator;
    }
    
    public function setEntity(string $entityclass): ActionInterface
    {
        $this->entity = $entityclass;
        return $this;
    }
    
    public function setParams(array $data): ActionInterface
    {
        $this->params = $data;
        return $this;
    }

    abstract public function execute(): ?string;
    
    protected function getRequestAsArray(): array
    {
        $this->jsonrequest = JsonRequest::createFromRequest($this->request, $this->params);
        return $this->jsonrequest->getSimpleData();
    }
    
    public function setBeforeSave($beforesave): self
    {
        $this->beforesave = $beforesave;
        return $this;
    }

    public function setAfterSave($aftersave): self
    {
        $this->aftersave = $aftersave;
        return $this;
    }    
    
    public function setBeforePersist($beforepersist): self
    {
        $this->beforepersist = $beforepersist;
        return $this;
    }    

    protected function beforePersistExecute(EntityManager $em, $item)
    {
        $func = $this->beforepersist;
        if (is_callable($func)) {
            $res = $func($em, $item, $this->jsonrequest);
        }
        
        return $res ?? null;
    }
    
    protected function beforeSaveExecute(EntityManager $em, $item)
    {
        $func = $this->beforesave;
        if (is_callable($func)) {
            $classname = (new \ReflectionClass(static::class))->getShortName();
            $res = $func($em, $item, $this->jsonrequest, $classname);
        }
        
        return $res ?? null;
    }
    
    protected function afterSaveExecute(EntityManager $em, $item): self
    {
        $func = $this->aftersave;
        if (is_callable($func)) {
            $func($em, $item, $this->jsonrequest);
        }
        
        return $this;
    } 
}
