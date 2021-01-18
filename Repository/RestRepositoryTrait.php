<?php

namespace sacrpkg\RestapiBundle\Repository;

use sacrpkg\RestapiBundle\Model\Paginator;
use sacrpkg\RestapiBundle\Model\Filter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

trait RestRepositoryTrait
{
    public function getResrApiData(Paginator $paginator, Filter $filter)
    {
		$sortby = 'p.'.$paginator->getSortBy();
        
        $this->beforeRestCreateQueryBuilder($paginator, $filter);
        
		$qb = $this->createQueryBuilder('p');
			
        $qb = $this->afterRestCreateQueryBuilder($qb, $paginator, $filter);
            
		if ($paginator) {
            $offset = $paginator->getCurrPage()*$paginator->getItemsOnPage();
            $limit = $paginator->getItemsOnPage();   
			$qb->setMaxResults($limit)
                ->orderBy($sortby, $paginator->getSortType())
				->setFirstResult($offset);   
		}
        
        $qb = $this->afterRestApplyPaginator($qb, $paginator, $filter);
        
        if ($filter->isUseFilter()) {
            $qb = $this->applyRestFilter($qb, $filter);
        }
/*
		if ($filter && count($filter)) {
			foreach ($filter as $key => $val) {
				$qb->andWhere('p.' . $key . ' = :val')
				->setParameter('val', $val);
			}
		}
*/		
        $qb = $this->afterRestApplyFilter($qb, $paginator, $filter);

//var_dump($qb->getQuery()->getSQL());

        return $qb->getQuery()
            ->getResult();
    }
    
/*
			foreach ($filter as $key => $val) {
                if ($key == 'language_id') {
                    ;
                } elseif ($key == 'project_id') {
                    $qb->andWhere('base.project_id in (:val'.$key.')')
                        ->setParameter('val'.$key, $val);
                } elseif ($key == 'base_id') {
                    $qb->andWhere('IDENTITY(dtab.data_base) in (:val'.$key.')')
                        ->setParameter('val'.$key, $val);
                } elseif ($key == 'table_id') {
                    $qb->andWhere('IDENTITY(p.data_table) in (:val'.$key.')')
                        ->setParameter('val'.$key, $val);                        
                } else {
                    if (is_array($val)) {
                        if (is_string($val[0])) {
                            $qb->andWhere('lower(p.' . $key . ') in (:val'.$key.')')
                                ->setParameter('val'.$key, array_map('strtolower', $val));
                        } else {
                            $qb->andWhere('p.' . $key . ' in (:val'.$key.')')
                                ->setParameter('val'.$key, $val);
                        } 
                    } elseif (is_object($val)) {
                        $qb->andWhere('p.' . $key . ' = :val'.$key)
                            ->setParameter('val'.$key, $val);
                    } elseif (is_string($val)) {
                        $qb->andWhere('lower(p.' . $key . ') = :val'.$key)
                            ->setParameter('val'.$key, strtolower($val));
                    } else {
                        $qb->andWhere('p.' . $key . ' = :val'.$key)
                            ->setParameter('val'.$key, $val);
                    }
				}
			}
*/            
    
    protected function beforeRestCreateQueryBuilder(Paginator $paginator, Filter $filter): ServiceEntityRepository
    {
        return $this;
    }
    
    protected function afterRestCreateQueryBuilder(QueryBuilder $qb, Paginator $paginator, Filter $filter): QueryBuilder
    {
        return $qb;
    }
    
    protected function afterRestApplyPaginator(QueryBuilder $qb, Paginator $paginator, Filter $filter): QueryBuilder
    {
        return $qb;
    }

    protected function applyRestFilter(QueryBuilder $qb, Filter $filter): QueryBuilder
    {
        foreach ($filter->getData() as $key => $item) {
            $typemethod = str_replace('_', '', ucwords($item['type'], '_'));
            $method = str_replace('_', '', strtolower($key)).$typemethod;

            if (!method_exists($this, $method)) {
                $method = 'default'.$typemethod;
            }

            $qb = $this->$method($qb, $key, $item['value']);
        }
        
        return $qb;
    }    
    
    protected function defaultArrayOption(QueryBuilder $qb, string $key,
                            $value, $fieldname = null): QueryBuilder
    {
        return $this->defaultArrayStr($qb, $key, $value, $fieldname);
    }

    protected function defaultArrayStr(QueryBuilder $qb, string $key,
                            $value, $fieldname = null): QueryBuilder
    {
        if (!$fieldname)
            $fieldname = 'p.' . $key;
        $qb->andWhere('lower(' . $fieldname . ') in (:val'.$key.')')
            ->setParameter('val'.$key, array_map(function($item){return mb_strtolower($item, 'UTF-8');}, $value));
            
        return $qb;
    }

    protected function defaultString(QueryBuilder $qb, string $key,
                            $value, $fieldname = null): QueryBuilder
    {
        if (!$fieldname)
            $fieldname = 'p.' . $key;
        $qb->andWhere('lower(' . $fieldname . ') = :val'.$key)
            ->setParameter('val'.$key, mb_strtolower($value, 'UTF-8'));
            
        return $qb;
    }
 
    protected function defaultArrayInt(QueryBuilder $qb, string $key,
                            $value, $fieldname = null): QueryBuilder
    {
        if (!$fieldname)
            $fieldname = 'p.' . $key;
        $qb->andWhere($fieldname . ' in (:val'.$key.')')
            ->setParameter('val'.$key, $value);
        
        return $qb;
    }
 
    protected function defaultInt(QueryBuilder $qb, string $key,
                            $value, $fieldname = null): QueryBuilder
    {
        if (!$fieldname)
            $fieldname = 'p.' . $key;
        $qb->andWhere($fieldname . ' = :val'.$key)
            ->setParameter('val'.$key, $value);
        
        return $qb;
    }

    /*
        q[accuracy=] 	Поиск по полю _tr.name
        Значениями accuracy могут быть значения:

            exact - точное совпадение значения
            start_with - начинается с указанного значения
            includes - включает в себя указанное значение в любом месте


        Если accuracy не указан, поиск по умолчанию.
    */
    protected function defaultSearch(QueryBuilder $qb, string $key, $value, $fieldname = null): QueryBuilder
    {
        if (!$fieldname)
            $fieldname = 'p.' . $key;

        $val = str_replace(['_', '%'], ['\_', '\%'], $value['value']);

        switch ($value['type_serach']) {
            case 'exact' :
                    $qb->andWhere('lower('.$fieldname. ') = :val'.$key)
                        ->setParameter('val'.$key, mb_strtolower($val, 'UTF-8'));
                    break;
            case 'start_with' :
                    $qb->andWhere('lower('.$fieldname. ') like :val'.$key)
                        ->setParameter('val'.$key, mb_strtolower($val, 'UTF-8').'%');
                    break;
            case 'includes' :
                    $qb->andWhere('lower('.$fieldname. ') like :val'.$key)
                        ->setParameter('val'.$key, '%'.mb_strtolower($val, 'UTF-8').'%');
                    break;
        }

        return $qb;
    }
    
    protected function afterRestApplyFilter(QueryBuilder $qb, Paginator $paginator, Filter $filter): QueryBuilder
    {
        return $qb;
    }    
}
