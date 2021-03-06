<?php
require 'Amok.php';

/**
 * These tests are suitable as examples of using
 * amok...
 **/
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
  
  public function test_mock_with_0_calls_to_some_call_that_expects_to_be_called_any_time()
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_call')->times('any')->with(24)->returns(200);
    
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
  
  public function test_mock_should_list_expectations_when_no_match_for_a_method_is_found()
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_call')->times(2)->with(24)->returns(200);
        
    $expected_message = <<<MESSAGE
Thingy: No match for method some_other_call with args: Array
(
    [0] => 22
)

Expectations: Function some_call with arguments: Array
(
    [0] => 24
)
MESSAGE;
        
    try {
      $my_mock->some_other_call(22);    
      $this->fail();
    } catch(AmokNoMatchException $e) {
      $this->assertEquals(trim($e->getMessage()), trim($expected_message));
    }
  }
  
  public function test_mock_should_respect_order_of_arguments() {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_call')->with(1,2)->returns(200);
    
    try {
      $my_mock->some_call(2,1);
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
  
  public function test_mock_should_handle_array_parameters()
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('get')->with('Items.GetList',array(
    				                    'item_type' => 'Post', 
    				                    'lang_code' => 'ENG', 
    				                    'include_info' => '1', 
    				                    'comments_count' => '1',
    				                    'page' => '1',
    				                    'per_page' => '2',
    				                    'order_by' => 'date_create DESC',
    				                    'status' => 'A',
    				                    'include_isets' => '1',
    				                    'require_lang_code' => 'on',
    				                  	 'from_date' => '2007-12-01',
    				                    'to_date' => '2007-12-31 23:59:59'))->returns('test');
    
    $this->assertEquals('test',$my_mock->get('Items.GetList', array('item_type' => 'Post', 
                              				                    'lang_code' => 'ENG',  
                              				                    'comments_count' => '1',
                              				                    'page' => '1',
                              				                    'per_page' => '2',
                              				                    'include_info' => '1',
                              				                    'order_by' => 'date_create DESC',
                              				                    'status' => 'A',
                              				                    'include_isets' => '1',
                              				                    'require_lang_code' => 'on',
                              				                  	 'from_date' => '2007-12-01',
                              				                    'to_date' => '2007-12-31 23:59:59')));
  }
  
  public function test_mock_with_no_parameter_expectation_set()
  {
    $my_mock = new Amok('Thingy');
    $my_mock->expects('some_method')->returns('Helo');
    
    $this->assertEquals('Helo',$my_mock->some_method(1,2,3,4,5,6,7));
  }
  // We must find a way to validate this kind of expectation
  // The problem is the order in which the expectations are met
  public function _test_mock_with_mock_parameter() {
    $my_mock = new Amok('Thingy');
    $passed_mock = new Amok('More stuff');

    $passed_mock->expects('method');
    $my_mock->expects('test')->with($passed_mock);

    $passed_mock->method();
    $my_mock->test($passed_mock);
    Amok::verifyAll();
  }
  
  public function test_verifyAll_should_verify_all_mocks()
  {
    Amok::reset();
    $mock1 = new Amok('Mock 1');
    $mock2 = new Amok('Mock 2');
    
    $mock1->expects('some_call')->with('hello')->returns('world');
    $mock2->expects('another_call')->with('hej')->returns('med dig');
    
    $mock1->some_call('hello');
    
    try {
      Amok::verifyAll();
      $this->fail();
    } catch(AmokNoMatchException $e) {
      $this->assertTrue(strpos($e->getMessage(),'Mock Mock 2 expected another_call')===0);
    }
    
    // Verify all should reset Amok
    Amok::verifyAll();
  }
}

?>
