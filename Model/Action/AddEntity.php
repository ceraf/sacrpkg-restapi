<?php

namespace sacrpkg\RestapiBundle\Model\Action;

class AddEntity extends ActionAbstract
{
    private $id;
    
    public function getItemId()
    {
        return $this->id ?? null;
    }
    
    public function execute(): ?string
    {       
        $data = $this->getRequestAsArray();

        $em = $this->doctrine->getManager();
        $classname = $this->entity;
        $item = new $classname();
              

            foreach ($this->params as $field) {

                if (!is_null($data[$field['name']] ?? null)) {
                    $method = 'set' . str_replace('_', '', ucwords($field['name'], '_'));
                    $item->$method($data[$field['name']]);
                }
            } 
            
            $errors = $this->beforePersistExecute($em, $item);
            if ($errors)
                return (string) $errors;
            
            $errors = $this->validator->validate($item);

            if (!count($errors)) {
				try {
					$em->getConnection()->beginTransaction(); 
                    
                    $em->persist($item);

					$errors = $this->beforeSaveExecute($em, $item);

                    if ($errors) {
                        $em->getConnection()->rollback();
                        return (string) $errors;
                    }
                    
                    $this->id = $item->getId();
					$em->flush();
					$em->getConnection()->commit();
                    $this->afterSaveExecute($em, $item);
                    return null;
				} catch (\Exception $e) {
					$em->getConnection()->rollback();
                    throw $e;
				}	
            } else {
                return (string) $errors;
            }

    }
}
