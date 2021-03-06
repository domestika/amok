<?php

 /**
  * Amok is a simple and easy to use mock for delightful unittesting.
  * It has no external dependencies and will work with any unittesting
  * framework. 
  *
  * See testAmok.php for examples of usage.
  *
  */
class Amok
{
  private $_name, $_expectations;
  private static $_mocks = array();
  
  public static function verifyAll() 
  {
    foreach(self::$_mocks as $mock) {
      try {
        $mock->verify();
      } catch (Exception $e) {
        self::reset();
        throw($e);
      }
    }
    self::reset();
  }
  
  public static function reset()
  {
    self::$_mocks = array();
  }
  
  public function __construct($name = 'Mock')
  {
    $this->_name = $name;
    $this->_expectations = array();
    self::$_mocks[] = $this;
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
      if(is_null($expectation->get_arguments())) {
        $error .= "Mock {$this->_name} expected {$expectation->get_function()} with any arguments";
      } else {
        $error .= "Mock {$this->_name} expected {$expectation->get_function()} with arguments: ". print_r($expectation->get_arguments(), true). "\n";
      }
    }
    throw new AmokNoMatchException($error);
  }
  
  function __call($function, $arguments) {
    foreach($this->_expectations as $expectation) {
      if($expectation->checkMatch($function,$arguments)) {
        
        return $expectation->execute();
      }
    }
    foreach($this->_expectations as $expectation) {
      $expectation->checkMatch($function,$arguments,false);
    }
    throw new AmokNoMatchException("{$this->_name}: No match for method $function with args: ". print_r($arguments, true) . "\nExpectations: {$this->list_expectations()}");
  }
  
  private function list_expectations()
  {
    $list = '';
    foreach($this->_expectations as $expectation) {
      $list .= "Function {$expectation->get_function()} with arguments: ". print_r($expectation->get_arguments(), true) ."\n";        
    }
    return $list;
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
    $this->arguments = null;
    $this->_arg_hash = null;
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
    $this->_arguments = $this->_recursiveSort(func_get_args());
    $this->_arg_hash = $this->_hashArguments($this->_arguments);
    return $this;
  }
  
  public function times($number_or_string) {
    if(is_numeric($number_or_string)){
      $this->_times = $number_or_string;
    } else {
      switch($number_or_string) {
        case 'any':
          $this->_times = 'any';
          $this->_matched = true;
          break;
        case 'never':
          $this->_times = 0;
          break;
        case 'once':
          $this->_times = 1;
          break;
        default:
          throw new Exception("times doesn't take the parameter $number_or_string");
      }
    }
    if(is_numeric($this->_times) && $this->_times <= 0) {
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
    if($only_non_matched && is_numeric($this->_times) && $this->_matched) {
      return false;
    }
    if($this->_function == $function && (is_null($this->_arg_hash) || $this->_arg_hash == $this->_hashArguments($args))) {
      $this->_number_of_calls++;
      if($this->_times == 'any' || $this->_number_of_calls == $this->_times) {
        $this->_matched = true;
      } else if(is_numeric($this->_times) && $this->_number_of_calls > $this->_times) {
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
  
  private function _hashArguments($args) {
    foreach($args as $key => $value) {
      if(is_array($value)) {
        $args[$key] = $this->_recursiveSort($value);
      }
    }
    return md5(print_r($args,true));
  }
  
  private function _recursiveSort($array) {
    ksort($array);
    foreach($array as $key => $item) {
      if(is_array($item)) {
        $array[$key] = $this->_recursiveSort($item);
      }
    }
    return $array;
  }
}

class AmokNoMatchException extends Exception {
  public function __construct($msg, $code = null) {
    // Make sure all amok expectations are always reset when an
    //  exceptions occurs
    Amok::reset();
    parent::__construct($msg, $code);
  }
}

?>
