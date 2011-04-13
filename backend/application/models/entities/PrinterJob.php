<?php

namespace Application\Model\Entity;

/**
 * @Entity
 * @Table(name="printer_job")
 * @HasLifeCycleCallbacks
 */
class PrinterJob
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;
    
    /**
     * @Column(type="string")
     */
    private $status;

    /**
     * @Column(type="string", nullable=true)
     */
    private $basename;

    /**
     * @Column(type="string", nullable=true)
     */
    private $cupsJobId;
    
    /**
     * @ManyToOne(targetEntity="Application\Model\Entity\PrinterDefinition")
     * @JoinColumn(name="printer_id", referencedColumnName="id")
     */
    private $printer;
    
    public function getId() { return $this->id; }
    public function getPrinter() { return $this->printer; }
    public function setPrinter($printer) { $this->printer = $printer; }
    public function getStatus() { return $this->status; }
    public function setStatus($status) { $this->status = $status; }
    public function getBasename() { return $this->basename; }
    public function setBasename($basename) { $this->basename = $basename; }
    public function setCupsJobId($cupsJobId) { $this->cupsJobId = $cupsJobId; }
    public function getCupsJobId() { return $this->cupsJobId; }

    /**
     * @PrePersist
     */
    public function onPrePersist() {
        $this->status = 'new';
    }

}