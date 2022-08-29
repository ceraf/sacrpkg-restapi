<?php

namespace sacrpkg\RestapiBundle\Model\Action;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

interface ActionInterface
{
    public function __construct (ManagerRegistry $doctrine, Request $request, ValidatorInterface $validator);
}
