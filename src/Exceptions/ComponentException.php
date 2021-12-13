<?php
namespace Sohris\Core\Exceptions;

class ComponentException extends \Exception {
    protected $details;
  
    public function __construct($details) {
        $this->details = $details;
        parent::__construct();
    }
  
    public function __toString() {
      return '[ComponentException]: ' . $this->details;
    }
  }