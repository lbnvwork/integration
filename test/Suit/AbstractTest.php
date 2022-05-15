<?php

namespace Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Plus\PaymentSystem\Interfaces\GateSettings as IGateSettings;
use Plus\PaymentSystem\Request;
use Plus\Provider;
use Plus\Provider\Validator;
use Plus\Proxy;
use Plus\Service;
use Plus\Service\EnqueueEvent;
use Plus\Service\GateCallback;
use Plus\Service\HttpClient\ClientDecorated;
use Plus\Service\RequestConstructor;
use Plus\Service\RequestConstructor\WithOperationData;
use Plus\Service\SequenceGenerator;
use Plus\Test\Base\TestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

use function GuzzleHttp\json_decode as guzzle_json_decode;
use function GuzzleHttp\json_encode as guzzle_json_encode;

abstract class AbstractTest extends TestCase
{
    public const FIXTURE_PATH = parent::FIXTURE_PATH . '/PaymentSystem/SepaViaGeDetails';

    /** @var  array */
    protected $eventQueue;

    /** @var  array */
    protected $gateCallbacks;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->eventQueue = [];
        $this->gateCallbacks = [];
        parent::setUp();
        Proxy::init()->getApp()->register(new Validator());
        Proxy::init()->getApp()[Provider\Config::NAME] = $this->prepareConfigMock();
        Proxy::init()->getApp()[Provider\EventQueue::QUEUE] = $this->prepareEventQueueMock();
        Proxy::init()->getApp()[Provider\GateCallback::NAME] = $this->prepareGateCallbackMock();
        Proxy::init()->getApp()[Provider\GateSettings::NAME] = new Service\GateSettings();
    }

    /**
     * @param string $responseBody
     *
     * @return array
     */
    protected function prepareArrayFromJson(string $responseBody): array
    {
        return guzzle_json_decode($this->getFixture($responseBody), true);
    }

    /**
     * @param string $responseBody
     * @param int    $responseStatusCode
     *
     * @return GuzzleResponse
     */
    protected function preparePsResponse(string $responseBody, int $responseStatusCode): GuzzleResponse
    {
        return new GuzzleResponse($responseStatusCode, [], $this->getFixture($responseBody));
    }

    /**
     * @param array $psToPlusResponses
     */
    protected function createHttpClientMock(array $psToPlusResponses): void
    {
        Proxy::init()->getApp()[Provider\HttpClient::NAME] = $this->getHttpClientMock($psToPlusResponses);
    }

    /**
     * @param array $psToPlusResponses
     *
     * @return MockObject
     */
    private function getHttpClientMock(array $psToPlusResponses): MockObject
    {
        $httpClient = $this->getMockBuilder(ClientDecorated::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['request', 'withHeadersFormatter'])
            ->getMock();

        $expectIndex = 0;

        $httpClient
            ->expects($this->any())
            ->method('withHeadersFormatter')
            ->willReturnSelf();

        foreach ($psToPlusResponses as $psToPlusResponse) {
            $expectIndex++;
            $this->prepareResponse($httpClient, $psToPlusResponse, $expectIndex++);
        }

        return $httpClient;
    }

    /**
     * @param MockObject $httpClient
     * @param array      $psToPlusResponse
     * @param int        $expects
     *
     * @return MockObject
     */
    protected function prepareResponse(
        MockObject $httpClient,
        array $psToPlusResponse,
        int $expects
    ): MockObject {
        if (isset($psToPlusResponse['exception'])) {
            $httpClient
                ->expects($this->at($expects))
                ->method('request')
                ->willThrowException($psToPlusResponse['exception']);
        } else {
            $httpClient
                ->expects($this->at($expects))
                ->method('request')
                ->willReturn($psToPlusResponse['ps_response']);
        }

        return $httpClient;
    }

    /**
     * @return array
     */
    protected function prepareConfigMock(): array
    {
        return [
            'processing'              => [],
            'gates'                   => [
                'eco_dev' => [
                    IGateSettings::CALLBACK_URL => 'https://gate.test/v2/payment/plus/callback',
                    IGateSettings::RETURN_URL   => 'https://plus.test/test/gateIn',
                ],
            ],
            'plusUrl'                 => 'https://plus.test',
            'defaultHighRiskPlusHost' => 'plus.mir',
        ];
    }

    /**
     * @return MockObject
     */
    protected function prepareEventQueueMock(): MockObject
    {
        $mock = $this->getMockForAbstractClass(EnqueueEvent::class);

        $mock
            ->expects($this->atMost(1))
            ->method('addEvent')
            ->willReturnCallback([$this, 'interceptCheckStatusEvent']);

        return $mock;
    }

    /**
     * @return MockObject|GateCallback
     */
    protected function prepareGateCallbackMock()
    {
        $mock = $this->createMock(GateCallback::class);

        $mock
            ->expects($this->atMost(1))
            ->method('run')
            ->willReturnCallback([$this, 'interceptGateCallback']);

        return $mock;
    }

    /**
     * @param string $body
     * @param bool   $withOperationData
     *
     * @return \Plus\PaymentSystem\Interfaces\Request|Request
     */
    protected function prepareGatePlusRequest(
        string $body,
        bool $withOperationData = false
    ) {
        $body = guzzle_json_decode($body, true);
        $httpRequest = new HttpRequest(['Content-Type' => 'application/json'], $body);

        if ($withOperationData) {
            return (new WithOperationData($httpRequest))->run();
        }

        return (new RequestConstructor($httpRequest))->run();
    }

    /**
     * @return MockObject | IGateSettings
     */
    protected function createGSMock()
    {
        return $this->getMockForAbstractClass(IGateSettings::class);
    }

    /**
     * @param bool $expect
     *
     * @return $this
     */
    protected function registerSequenceGenerator(bool $expect = true): self
    {
        $mock = Proxy::init()->getApp()[Provider\SequenceGenerator::NAME] = $this->createPartialMock(
            SequenceGenerator::class,
            ['generateRandomString']
        );

        $mock
            ->expects($expect ? $this->once() : $this->never())
            ->method('generateRandomString')
            ->willReturn(...$this->getArrayFromFixture('UniqueString.json'));

        return $this;
    }

    /**
     * @param string $fixtureName
     *
     * @return array
     */
    protected function getArrayFromFixture(string $fixtureName): array
    {
        return guzzle_json_decode($this->getFixture($fixtureName), true);
    }

    /**
     * @param object $object
     *
     * @return array
     */
    protected function objectToArray(object $object): array
    {
        return guzzle_json_decode(guzzle_json_encode($object), true);
    }

    /**
     * @param bool   $isAddCheckStatusEvent
     * @param bool   $isCancelCheckStatusEvent
     * @param string $checkStatusEventClass
     *
     * @return $this
     */
    protected function registerEventQueueMock(
        bool $isAddCheckStatusEvent,
        bool $isCancelCheckStatusEvent,
        string $checkStatusEventClass
    ): self {
        $mock = Proxy::init()->getApp()[Provider\EventQueue::QUEUE] = $this->getMockForAbstractClass(
            EnqueueEvent::class
        );

        $mock
            ->expects($isAddCheckStatusEvent ? $this->once() : $this->never())
            ->method('addEvent')
            ->with($this->isInstanceOf($checkStatusEventClass));

        $mock
            ->expects($isCancelCheckStatusEvent ? $this->once() : $this->never())
            ->method('cancelEventByOperationId')
            ->with(
                $this->identicalTo($checkStatusEventClass)
            );

        return $this;
    }

    /**
     * @param $callback
     *
     * @return void
     */
    public function interceptGateCallback($callback): void
    {
        $this->gateCallbacks[] = $callback;
    }

    /**
     * @param $event
     *
     * @return string
     */
    public function interceptCheckStatusEvent($event): string
    {
        $this->eventQueue[] = $event;

        return '';
    }
}
