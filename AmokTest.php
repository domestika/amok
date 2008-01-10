<?php
require 'Amok.php';

class AmokTest extends PHPUnit_Framework_TestCase
{
  public function test_mock_with_2_calls_to_some_call()
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_call')->times(2)->with(24)->returns(200);
    
    $this->assertEquals($my_mock->some_call(24),200);
    $this->assertEquals($my_mock->some_call(24),200);
    $this->assertTrue($my_mock->verify());
  }
  
  public function test_mock_with_2_calls_to_some_call_that_expects_to_be_called_any_time()
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_call')->times('any')->with(24)->returns(200);
    
    $this->assertEquals($my_mock->some_call(24),200);
    $this->assertEquals($my_mock->some_call(24),200);
    $this->assertTrue($my_mock->verify());
  }  
  
  public function test_mock_with_too_many_calls_to_some_call()
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_call')->times(2)->with(24)->returns(200);
    
    $this->assertEquals($my_mock->some_call(24),200);
    $this->assertEquals($my_mock->some_call(24),200);
    
    try {
      $my_mock->some_call(24);    
      $this->fail();
    } catch(AmokNoMatchException $e) {
      $this->assertTrue(true);
    }
    
  }
  
  public function test_mock_with_no_calls_to_some_call()
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_call')->with(24)->returns(200);
    
    try {
      $my_mock->verify();
      $this->fail();
    } catch(AmokNoMatchException $e) {
      $this->assertTrue(true);
    }
  }
  
  public function test_mock_with_raises_set()
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_call')->with('23',array(1,2,3,4))->raises(new Exception('This is an error!'));
    
    try {
      $my_mock->some_call('23',array(1,2,3,4));
      $this->fail("some_call should raise an exception");
    } catch(Exception $e) {
      $this->assertEquals($e->getMessage(), 'This is an error!');
    }
  }
  
  public function test_mock_with_various_expectations_for_the_same_method()
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_call')->with('23')->returns(array(1,2,3,4));
    $my_mock->expects('some_call')->with('24')->returns(array(5,6,7,8));
    
    $this->assertEquals($my_mock->some_call('24'),array(5,6,7,8));
    $this->assertEquals($my_mock->some_call('23'),array(1,2,3,4));
    
    $this->assertTrue($my_mock->verify());
  }
  
  public function test_mock_with_expectation_with_no_args_specified() 
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_call')->returns(array(1,2,3,4));

    $this->assertEquals($my_mock->some_call(),array(1,2,3,4));
    $this->assertTrue($my_mock->verify());
  }
  
  public function test_mock_with_call_to_unexpected_method()
  {
    $my_mock = new Amok('Thingy');
    
    try {
      $my_mock->unexpected_method();
      $this->fail();
    } catch(AmokNoMatchException $e) {
      $this->assertTrue(true);
    }
  }
}

?>