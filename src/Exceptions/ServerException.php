<?php
namespace Sohris\Core\Exceptions;

class ServerException extends \Exception {
    protected $details;
  
    public function __construct($details) {
        $this->details = $details;
        parent::__construct();
    }
  
    public function __toString() {
      return '[ServerException]: ' . $this->details;
    }
  }