<?php

class EcomDev_PHPUnitTest_Test_Helper_Observer extends EcomDev_PHPUnit_Test_Case
{
    public function testItStubsObserver()
    {
        $eventData = array(
            'some_key' => 'some_value',
            'another_key' => 'another_value'
        );

        $observer = $this->generateObserver($eventData);

        $this->assertEventData($eventData, $observer);
        $this->assertNull($observer->getEventName());
        $this->assertNull($observer->getEvent()->getName());
    }

    public function testItStubsObserverWithEventName()
    {
        $eventData = array(
            'some_key' => 'some_value',
            'another_key' => 'another_value'
        );

        $observer = $this->generateObserver($eventData, 'my_event_name');

        $this->assertInstanceOf('Varien_Event_Observer', $observer);

        $this->assertEventData($eventData, $observer);
        $this->assertEquals('my_event_name', $observer->getEventName());
        $this->assertEquals('my_event_name', $observer->getEvent()->getName());
    }

    protected function assertEventData($eventData, $observer)
    {
        foreach ($eventData as $key => $value) {
            $this->assertEquals(
                $value,
                $observer->getDataUsingMethod($key)
            );

            $this->assertEquals(
                $value,
                $observer->getEvent()->getDataUsingMethod($key)
            );
        }
    }
}