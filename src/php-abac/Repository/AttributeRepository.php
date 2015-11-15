<?php

namespace PhpAbac\Repository;

use PhpAbac\Model\Attribute;
use PhpAbac\Model\EnvironmentAttribute;

class AttributeRepository extends Repository {
    /**
     * @param integer $attributeId
     * @return Attribute
     */
    public function findAttribute($attributeId) {
        $statement = $this->query(
            'SELECT ad.name, ad.slug, a.table_name, a.column_name, a.criteria_column, ad.created_at, ad.updated_at ' .
            'FROM abac_attributes_data ad INNER JOIN abac_attributes a ON a.id = ad.id WHERE ad.id = :id'
        , ['id' => $attributeId]);
        $data = $statement->fetch();
        
        return
            (new Attribute())
            ->setName($data['name'])
            ->setSlug($data['slug'])
            ->setTable($data['table_name'])
            ->setColumn($data['column_name'])
            ->setCriteriaColumn($data['criteria_column'])
            ->setCreatedAt($data['created_at'])
            ->setUpdatedAt($data['updated_at'])
        ;
    }
    
    /**
     * @param Attribute &$attribute
     * @param mixed $criteria
     */
    public function retrieveAttribute(Attribute $attribute, $criteria) {
        $statement = $this->query(
            "SELECT {$attribute->getColumn()} FROM {$attribute->getTable()} WHERE {$attribute->getCriteriaColumn()} = {$criteria}"
        );
        $data = $statement->fetch();
        $attribute->setValue($data[$attribute->getColumn()]);
    }
    
    /**
     * @param Attribute $attribute
     */
    public function createAttribute(Attribute $attribute) {
        $datetime = new \DateTime();
        $formattedDatetime = $datetime->format('Y-m-d H:i:s');
        $slug = $this->slugify($attribute->getName());
        
        $this->insert(
            'INSERT INTO abac_attributes_data (created_at, updated_at, name, slug) ' .
            'VALUES(:created_at, :updated_at, :name, :slug);' .
            'INSERT INTO abac_attributes (id, table_name, column_name, criteria_column) ' .
            'VALUES(LAST_INSERT_ID(), :table_name, :column_name, :criteria_column);'
        , [
            'name' => $attribute->getName(),
            'slug' => $slug,
            'table_name' => $attribute->getTable(),
            'column_name' => $attribute->getColumn(),
            'criteria_column' => $attribute->getCriteriaColumn(),
            'created_at' => $formattedDatetime,
            'updated_at' => $formattedDatetime
        ]);
        $attribute
            ->setId($this->connection->lastInsertId('abac_attributes'))
            ->setSlug($slug)
            ->setCreatedAt($datetime)
            ->setUpdatedAt($datetime)
        ;
    }
    
    /**
     * @param EnvironmentAttribute $attribute
     */
    public function createEnvironmentAttribute(EnvironmentAttribute $attribute) {
        $datetime = new \DateTime();
        $formattedDatetime = $datetime->format('Y-m-d H:i:s');
        $slug = $this->slugify($attribute->getName());
        
        $this->insert(
            'INSERT INTO abac_attributes_data (created_at, updated_at, name, slug) ' .
            'VALUES(:created_at, :updated_at, :name, :slug);' .
            'INSERT INTO abac_environment_attributes (id, variable_name) ' .
            'VALUES(LAST_INSERT_ID(), :variable_name);'
        , [
            'name' => $attribute->getName(),
            'slug' => $slug,
            'variable_name' => $attribute->getVariableName(),
            'created_at' => $formattedDatetime,
            'updated_at' => $formattedDatetime
        ]);
        $attribute
            ->setId($this->connection->lastInsertId('abac_attributes'))
            ->setSlug($slug)
            ->setCreatedAt($datetime)
            ->setUpdatedAt($datetime)
        ;
    }
    
    /*
     * @param string $name
     * @return string
     */
    function slugify($name) {
        // replace non letter or digits by -
        $name = trim(preg_replace('~[^\\pL\d]+~u', '-', $name), '-');
        // transliterate
        if (function_exists('iconv'))
        {
            $name = iconv('utf-8', 'us-ascii//TRANSLIT', $name);
        }
        // remove unwanted characters
        $name = preg_replace('~[^-\w]+~', '', strtolower($name));
        if (empty($name))
        {
            return 'n-a';
        }
        return $name;
    }
}
