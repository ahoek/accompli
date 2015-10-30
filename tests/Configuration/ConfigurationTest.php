<?php

namespace Accompli\Test;

use Accompli\Configuration\Configuration;
use Accompli\Deployment\Host;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use UnexpectedValueException;

/**
 * ConfigurationTest.
 *
 * @author  Niels Nijens <nijens.niels@gmail.com>
 */
class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    /**
     * testLoadWithValidJSON.
     */
    public function testLoadWithValidJSON()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli.json');
    }

    /**
     * testLoadWithNonExistingJSONThrowsRuntimeException.
     *
     * @expectedException RuntimeException
     */
    public function testLoadWithNonExistingJSONThrowsRuntimeException()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli-non-existing.json');
    }

    /**
     * testLoadWithInvalidSyntaxJSONThrowsParsingException.
     *
     * @expectedException Seld\JsonLint\ParsingException
     */
    public function testLoadWithInvalidSyntaxJSONThrowsParsingException()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli-syntax-invalid.json');
    }

    /**
     * testLoadWithInvalidSchemaJSONThrowsJSONValidationException.
     *
     * @expectedException Accompli\Exception\JSONValidationException
     */
    public function testLoadWithInvalidSchemaJSONThrowsJSONValidationException()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli-schema-invalid.json');
    }

    /**
     * testLoadWithExtendedSchema.
     */
    public function testLoadWithExtendedSchema()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli-extended.json');

        $this->assertArrayHasKey('deployment', $configuration->toArray());
    }

    /**
     * testGetHostsReturnsArrayWhenConfigurationNotLoaded.
     */
    public function testGetHostsReturnsArrayWhenConfigurationNotLoaded()
    {
        $configuration = new Configuration();

        $this->assertInternalType('array', $configuration->getHosts());
    }

    /**
     * testGetHostsReturnsArrayWhenConfigurationLoaded.
     */
    public function testGetHostsReturnsArrayWhenConfigurationLoaded()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli.json');

        $this->assertInternalType('array', $configuration->getHosts());
        $this->assertNotEmpty($configuration->getHosts());
        $this->assertInstanceOf('Accompli\Deployment\Host', current($configuration->getHosts()));
    }

    /**
     * testGetHostsByStageThrowsUnexpectedValueExceptionOnInvalidStage.
     *
     * @expectedException        UnexpectedValueException
     * @expectedExceptionMessage 'invalid' is not a valid stage.
     */
    public function testGetHostsByStageThrowsUnexpectedValueExceptionOnInvalidStage()
    {
        $configuration = new Configuration();
        $configuration->getHostsByStage('invalid');
    }

    /**
     * testGetHostsByStageReturnsEmptyArrayWhenConfigurationNotLoaded.
     */
    public function testGetHostsByStageReturnsEmptyArrayWhenConfigurationNotLoaded()
    {
        $configuration = new Configuration();

        $result = $configuration->getHostsByStage(Host::STAGE_TEST);
        $this->assertInternalType('array', $result);
        $this->assertCount(0, $result);
    }

    /**
     * testGetHostsByStageReturnsEmptyArrayWhenConfigurationLoadedWithNoHostsConfiguredForStage.
     */
    public function testGetHostsByStageReturnsEmptyArrayWhenConfigurationLoadedWithNoHostsConfiguredForStage()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli.json');

        $result = $configuration->getHostsByStage(Host::STAGE_ACCEPTANCE);
        $this->assertInternalType('array', $result);
        $this->assertCount(0, $result);
    }

    /**
     * testGetHostsByStageReturnsArrayWhenConfigurationLoadedWithHostConfiguredForStage.
     */
    public function testGetHostsByStageReturnsArrayWhenConfigurationLoadedWithHostConfiguredForStage()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli.json');

        $this->assertInternalType('array', $configuration->getHostsByStage(Host::STAGE_TEST));
        $this->assertNotEmpty($configuration->getHostsByStage(Host::STAGE_TEST));
        $this->assertInstanceOf('Accompli\Deployment\Host', current($configuration->getHostsByStage(Host::STAGE_TEST)));
    }

    /**
     * testGetEventSubscribersReturnsEmptyArrayWhenConfigurationNotLoaded.
     */
    public function testGetEventSubscribersReturnsEmptyArrayWhenConfigurationNotLoaded()
    {
        $configuration = new Configuration();

        $result = $configuration->getEventSubscribers();
        $this->assertInternalType('array', $result);
        $this->assertCount(0, $result);
    }

    /**
     * testGetEventSubscribersReturnsArrayWhenConfigurationLoaded.
     */
    public function testGetEventSubscribersReturnsArrayWhenConfigurationLoaded()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli.json');

        $result = $configuration->getEventSubscribers();
        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);
    }

    /**
     * testGetEventSubscribersAlwaysReturnsArrayOfArraysWithClassKeyWhenConfigurationLoaded.
     *
     * @depends testGetEventSubscribersReturnsArrayWhenConfigurationLoaded
     */
    public function testGetEventSubscribersAlwaysReturnsArrayOfArraysWithClassKeyWhenConfigurationLoaded()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli.json');

        $result = $configuration->getEventSubscribers();
        foreach ($result as $resultItem) {
            $this->assertArrayHasKey('class', $resultItem);
        }
    }

    /**
     * testGetEventListenersReturnsEmptyArrayWhenConfigurationNotLoaded.
     */
    public function testGetEventListenersReturnsEmptyArrayWhenConfigurationNotLoaded()
    {
        $configuration = new Configuration();

        $result = $configuration->getEventListeners();
        $this->assertInternalType('array', $result);
        $this->assertCount(0, $result);
    }

    /**
     * testGetEventListenersReturnsArrayWhenConfigurationLoaded.
     */
    public function testGetEventListenersReturnsArrayWhenConfigurationLoaded()
    {
        $configuration = new Configuration();
        $configuration->load(__DIR__.'/../Resources/accompli.json');

        $result = $configuration->getEventListeners();
        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);
    }
}