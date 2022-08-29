<?php

namespace sacrpkg\RestapiBundle\Model\Action;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ActionFactory implements ActionFactoryInterface
{
    protected $doctrine;
    protected $request;
    protected $validator;
    
    const ACTIONS = [
        'edit.entity' => EditEntity::class,
        'add.entity' => AddEntity::class,
    ];
    
    public function __construct (ManagerRegistry $doctrine, RequestStack $requestStack, ValidatorInterface $validator)
    {
        $this->doctrine = $doctrine;
        $this->request = $requestStack->getCurrentRequest();
        $this->validator = $validator;
    }
    
    public function get(string $action): ?ActionInterface
    {
        $classname = self::ACTIONS[$action] ?? null;
        if (!$classname)
            return null;
        
        return new $classname($this->doctrine, $this->request, $this->validator);
    }
}
