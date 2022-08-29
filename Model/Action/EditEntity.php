<?php

namespace sacrpkg\RestapiBundle\Model\Action;

class EditEntity extends ActionAbstract
{
    private $id;
    
    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }
    
    public function execute(): ?string
    {       
        $data = $this->getRequestAsArray();

        
        $em = $this->doctrine->getManager();
        $item = $em->getRepository($this->entity)
                ->find($this->id);
              
        if ($item) {
            foreach ($this->params as $field) {
                if (!is_null($data[$field['name']] ?? null)) {
                    $method = 'set' . str_replace('_', '', ucwords($field['name'], '_'));
                    if (is_callable([$item, $method]))
                        $item->$method($data[$field['name']]);
                }
            }
            
            $errors = $this->validator->validate($item);
            if (!count($errors)) {
                
				try {
					$em->getConnection()->beginTransaction();
					$errors = $this->beforeSaveExecute($em, $item);
                    if ($errors) {
                        $em->getConnection()->rollback();
                        return (string) $errors;
                    }
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
        } else
            return 'Not found item';
    }
}

