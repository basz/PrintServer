<?php

namespace Application\Model\Entity;

/**
 * @Entity
 * @Table(name="printer_definition")
 */
class PrinterDefinition
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;
    
    /**
     * @Column(length=50)
     */
    private $name; // type defaults to string

    /**
     * @Column(length=50)
     */
    private $cupsName; // type defaults to string

    /**
     * @Column(type="array", nullable=true)
     */
    private $defaultOptions; // type defaults to string

    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; }
    public function getCupsName() { return $this->cupsName; }
    public function setCupsName($name) { $this->cupsName = $name; }
    public function getDefaultOptions() { return $this->defaultOptions; }
    public function setDefaultOptions($defaultOptions) { $this->defaultOptions = $defaultOptions; }
    public function getDefaultOption($key) { return (isset($this->defaultOptions[$key]))?$this->defaultOptions[$key]:null; }
    
}