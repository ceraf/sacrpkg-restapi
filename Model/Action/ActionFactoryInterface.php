<?php

namespace sacrpkg\RestapiBundle\Model\Action;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;

interface ActionFactoryInterface
{
    public function __construct (ManagerRegistry $doctrine, RequestStack $requestStack, ValidatorInterface $validator);
    
    public function get(string $action): ?ActionInterface;
}
