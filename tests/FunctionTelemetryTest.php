<?php

namespace CustomAutoOpenTelemetry\Tests;

use CustomAutoOpenTelemetry\FunctionTelemetry;
use CustomAutoOpenTelemetry\MonitoredFunctionRegistry;
use CustomAutoOpenTelemetry\FunctionHookRegistrar;
use CustomAutoOpenTelemetry\HookConfiguration;
use CustomAutoOpenTelemetry\ArgumentSanitizer;
use OpenTelemetry\API\Common\Instrumentation\CachedInstrumentation;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for FunctionTelemetry class
 */
class FunctionTelemetryTest extends TestCase
{
    private FunctionTelemetry $oTelemetry;
    private MockObject $oInstrumentationMock;
    private MockObject $oMonitoredFunctionRegistryMock;
    private MockObject $oFunctionHookRegistrarMock;
    private MockObject $oArgumentSanitizerMock;

    protected function setUp(): void
    {
        $this->oInstrumentationMock = $this->createMock(CachedInstrumentation::class);
        $this->oMonitoredFunctionRegistryMock = $this->createMock(MonitoredFunctionRegistry::class);
        $this->oFunctionHookRegistrarMock = $this->createMock(FunctionHookRegistrar::class);
        $this->oArgumentSanitizerMock = $this->createMock(ArgumentSanitizer::class);

        $this->oTelemetry = new FunctionTelemetry(
            $this->oMonitoredFunctionRegistryMock,
            $this->oFunctionHookRegistrarMock,
            $this->oArgumentSanitizerMock
        );
    }

    /**
     * Tests initialization of telemetry system
     */
    public function testInitSetsInstrumentation(): void
    {
        FunctionTelemetry::init('test_instrumentation');
        $this->assertInstanceOf(CachedInstrumentation::class, $this->getInstrumentation());
    }

    /**
     * Tests registering multiple functions
     */
    public function testRegisterFunctions(): void
    {
        $aFunctionNames = ['test_function1', 'test_function2'];
        $aOptions = [
            'test_function1' => ['span_name' => 'custom.span'],
            'test_function2' => ['capture_args' => false]
        ];

        $this->oMonitoredFunctionRegistryMock
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(['test_function1'], ['test_function2']);

        $this->oFunctionHookRegistrarMock
            ->expects($this->exactly(2))
            ->method('register')
            ->with($this->callback(function ($sFunctionName) use ($aFunctionNames) {
                return in_array($sFunctionName, $aFunctionNames);
            }), $this->isInstanceOf(HookConfiguration::class));

        $this->oTelemetry->registerFunctions($aFunctionNames, $aOptions);
    }

    /**
     * Tests registering a single function
     */
    public function testRegisterFunction(): void
    {
        $sFunctionName = 'test_function';
        $aOptions = ['span_name' => 'custom.span', 'capture_args' => true];

        $this->oMonitoredFunctionRegistryMock
            ->expects($this->once())
            ->method('add')
            ->with($sFunctionName);

        $this->oFunctionHookRegistrarMock
            ->expects($this->once())
            ->method('register')
            ->with($sFunctionName, $this->isInstanceOf(HookConfiguration::class));

        $result = $this->oTelemetry->registerFunction($sFunctionName, $aOptions);
        $this->assertSame($this->oTelemetry, $result);
    }

    /**
     * Tests registering non-existent function
     */
    public function testRegisterNonExistentFunction(): void
    {
        $sFunctionName = 'non_existent_function';
        $this->oMonitoredFunctionRegistryMock
            ->expects($this->never())
            ->method('add');

        $this->oFunctionHookRegistrarMock
            ->expects($this->never())
            ->method('register');

        $result = $this->oTelemetry->registerFunction($sFunctionName);
        $this->assertSame($this->oTelemetry, $result);
    }

    /**
     * Tests checking if a function is monitored
     */
    public function testIsMonitored(): void
    {
        $sFunctionName = 'test_function';
        $this->oMonitoredFunctionRegistryMock
            ->expects($this->once())
            ->method('has')
            ->with($sFunctionName)
            ->willReturn(true);

        $this->assertTrue($this->oTelemetry->isMonitored($sFunctionName));
    }

    /**
     * Tests getting all monitored functions
     */
    public function testGetMonitoredFunctions(): void
    {
        $aExpectedFunctions = ['test_function1', 'test_function2'];
        $this->oMonitoredFunctionRegistryMock
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($aExpectedFunctions);

        $this->assertEquals($aExpectedFunctions, $this->oTelemetry->getMonitoredFunctions());
    }

    /**
     * Gets the instrumentation instance using reflection
     *
     * @return \OpenTelemetry\API\Instrumentation\CachedInstrumentation|null
     */
    private function getInstrumentation(): ?CachedInstrumentation
    {
        $oReflection = new \ReflectionClass(FunctionTelemetry::class);
        $oProperty = $oReflection->getProperty('oInstrumentation');
        $oProperty->setAccessible(true);
        return $oProperty->getValue();
    }
}

/**
 * Tests for MonitoredFunctionRegistry class
 */
class MonitoredFunctionRegistryTest extends TestCase
{
    private MonitoredFunctionRegistry $oRegistry;

    protected function setUp(): void
    {
        $this->oRegistry = new MonitoredFunctionRegistry();
    }

    /**
     * Tests adding and checking monitored functions
     */
    public function testAddAndHas(): void
    {
        $sFunctionName = 'test_function';
        $this->oRegistry->add($sFunctionName);
        $this->assertTrue($this->oRegistry->has($sFunctionName));
        $this->assertFalse($this->oRegistry->has('other_function'));
    }

    /**
     * Tests getting all monitored functions
     */
    public function testGetAll(): void
    {
        $aFunctions = ['test_function1', 'test_function2'];
        foreach ($aFunctions as $sFunction) {
            $this->oRegistry->add($sFunction);
        }
        $this->assertEquals($aFunctions, $this->oRegistry->getAll());
    }
}

/**
 * Tests for ArgumentSanitizer class
 */
class ArgumentSanitizerTest extends TestCase
{
    private ArgumentSanitizer $oSanitizer;

    protected function setUp(): void
    {
        $this->oSanitizer = new ArgumentSanitizer();
    }

    /**
     * Tests sanitizing various argument types
     */
    public function testSanitizeArguments(): void
    {
        $aArgs = [
            'string',
            123,
            ['array'],
            new \stdClass(),
            fopen('php://memory', 'r')
        ];

        $sResult = $this->oSanitizer->sanitizeArguments($aArgs);
        $aDecoded = json_decode($sResult, true);

        $this->assertEquals('string', $aDecoded[0]);
        $this->assertEquals(123, $aDecoded[1]);
        $this->assertEquals('array(1)', $aDecoded[2]);
        $this->assertEquals('stdClass object', $aDecoded[3]);
        $this->assertStringStartsWith('resource(stream)', $aDecoded[4]);
    }

    /**
     * Tests sanitizing various return value types
     */
    public function testSanitizeReturnValue(): void
    {
        $this->assertEquals('"string"', $this->oSanitizer->sanitizeReturnValue('string'));
        $this->assertEquals('123', $this->oSanitizer->sanitizeReturnValue(123));
        $this->assertEquals('array(1)', $this->oSanitizer->sanitizeReturnValue(['array']));
        $this->assertEquals('stdClass object', $this->oSanitizer->sanitizeReturnValue(new \stdClass()));
        $this->assertStringStartsWith('resource(stream)', $this->oSanitizer->sanitizeReturnValue(fopen('php://memory', 'r')));
    }
}