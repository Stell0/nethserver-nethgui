<?php
namespace Nethgui\Test\Unit\Nethgui\Serializer;
class ArrayAccessSerializerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Nethgui\Serializer\ArrayAccessSerializer
     */
    protected $tests = array();
    protected $data;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $a = array(
            'A' => array('f0' => 'a0', 'f1' => 'a1', 'f2' => 'a2'),
            'B' => array('f0' => 'b0', 'f1' => 'b1', 'f2' => 'b2'),
            'C' => array('f0' => 'c0', 'f1' => 'c1', 'f2' => 'c2'),
        );

        $this->data = new \ArrayObject($a);

        foreach ($a as $rowKey => $row) {
            foreach ($row as $fieldKey => $value) {
                $this->tests[] = array(
                    $rowKey,
                    $fieldKey,
                    $value,
                    new \Nethgui\Serializer\ArrayAccessSerializer($this->data, $rowKey, $fieldKey)
                );
            }
        }
    }

    public function testRead()
    {
        foreach ($this->tests as $args) {
            list($rowKey, $fieldKey, $value, $object) = $args;
            $this->assertEquals($value, $object->read());
        }
    }

    public function testReadNonExistingRow()
    {
        $object = new \Nethgui\Serializer\ArrayAccessSerializer($this->data, 'X', 'f0');        
        $this->assertNull($object->read());
    }

    public function testReadNonExistingField()
    {
        $object = new \Nethgui\Serializer\ArrayAccessSerializer($this->data, 'A', 'x0');        
        $this->assertNull($object->read());
    }
        
    public function testWriteUpdate()
    {
        foreach ($this->tests as $args) {
            list($rowKey, $fieldKey, $value, $object) = $args;
            $this->assertEquals($value, $this->data[$rowKey][$fieldKey]);  
            $object->write('UU');
            $this->assertEquals('UU', $this->data[$rowKey][$fieldKey]);            
        }
    }

    public function testWriteAppend()
    {
        $object = new \Nethgui\Serializer\ArrayAccessSerializer($this->data, 'D', 'f0');
        $object->write('AA');
        $this->assertEquals('AA', $this->data['D']['f0']);
        $this->assertEquals(array('f0'=>'AA'), $this->data['D']);
    }

}
