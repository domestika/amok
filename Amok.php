<?php

class Amok
{
  private $_name, $_expectations;
  
  public function __construct($name = 'Mock')
  {
    $this->_name = $name;
    $this->_expectations = array();
  }
  
  public function expects($function) {
    $expectation = new AmokExpectation($function);
    $this->_expectations[] =  $expectation;
    return $expectation;
  }
  
  public function verify()
  {
    $no_matches = array();
    foreach($this->_expectations as $expectation) {
      if(!$expectation->hasBeenMatched()) {
        $no_matches[] = $expectation;
      }
    }
    if(empty($no_matches)) {
      return true;
    }
    $error = "";
    foreach($no_matches as $expectation) {
      $error .= "Mock {$this->_name} expected {$expectation->get_function()} with arguments: ". print_r($expectation->get_arguments(), true). "\n";
    }
    throw new AmokNoMatchException($error);
  }
  
  function __call($function, $arguments) {
    foreach($this->_expectations as $expectation) {
      if($expectation->checkMatch($function,$arguments)) {
        
        return $expectation->execute();
      }
    }
    // No matches - we take another run to see if any method has already been matched
    foreach($this->_expectations as $expectation) {
      $expectation->checkMatch($function,$arguments,false);
    }    
    throw new AmokNoMatchException("No match for method $function with args: ". print_r($arguments, true));
  }
}

class AmokExpectation 
{
  private $_function, $_arguments, $_arg_hash, $_times, $_number_of_calls, $_return_value, $_raises, $_matched;
  
  function __construct($function) {
    $this->_function = $function;
    $this->_times = 1;
    $this->_number_of_calls = 0;
    $this->_raises = false;
    $this->_matched = false;
    $this->arguments = array();
    $this->_arg_hash = md5(print_r(array(),true));
  }
  
  public function get_function()
  {
    return $this->_function;
  }
  
  public function get_arguments()
  {
    return $this->_arguments;
  }
  
  public function with($args) {
    $this->arguments = func_get_args();
    $this->_arg_hash = md5(print_r(func_get_args(),true));
    return $this;
  }
  
  public function times($number) {
    $this->_times = $number;
    if($number <= 0) {
      $this->_matched = true;
    }
    return $this;
  }
  
  public function returns($value)
  {
    $this->_return_value = $value;
    return $this;
  }
  
  public function raises($exception)
  {
    $this->_raises = true;
    $this->_return_value = $exception;
    return $this;
  }

  public function execute() {
    if($this->_raises) {
      throw $this->_return_value;
    }
    return $this->_return_value;
  }
  
  public function checkMatch($function, $args, $only_non_matched = true)
  {
    if($only_non_matched && $this->_matched) {
      return false;
    }
    if($this->_function == $function && $this->_arg_hash == md5(print_r($args,true))) {
      $this->_number_of_calls++;
      if($this->_number_of_calls == $this->_times) {
        $this->_matched = true;
      } else if($this->_number_of_calls > $this->_times) {
        $this->_matched = false;
        throw new AmokNoMatchException("Function $function with args: ". print_r($args,true) ."\n Was called {$this->_number_of_calls} times, only {$this->_times} calls expected");
      }
      return true;
    }
    return false;
  }
  
  public function hasBeenMatched()
  {
    return $this->_matched;
  }
}

class AmokNoMatchException extends Exception {}

?>