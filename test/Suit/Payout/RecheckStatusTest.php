<?php

namespace Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\Payout;

use Plus\PaymentSystem\Interfaces\Request as IRequest;
use Plus\PaymentSystem\ProcessingFactory;
use Plus\PaymentSystem\Response\ArrayResponse;
use Plus\Service\GateSettings;
use Plus\Service\Processing\PaymentSystem as ProcessingService;
use Plus\Service\RequestConstructor;
use Plus\Service\RequestConstructor\WithOperationData;
use Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\AbstractTest;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class RecheckStatusTest extends AbstractTest
{
    private const LOCAL_PATH = '/Payout/recheck_status/';

    /**
     * @param array $plusRequest
     * @param array $plusState
     *
     * @dataProvider dataProvider
     */
    public function test(array $plusRequest, array $plusState): void
    {
        $plusRequest = $this->preparePlusRequest($plusRequest);
        $response = (new ProcessingService(
            $plusRequest,
            (new GateSettings())->paymentSystem($plusRequest),
            new ProcessingFactory()
        ))->run();
        $actualResponse = (new ArrayResponse($response))->prepare();
        $this->assertEquals($plusState['response'], $actualResponse);
        $this->assertNotEmpty($this->eventQueue);
        $event = $this->eventQueue[0];
        $this->assertEquals($plusState['check_status_event'], get_class($event));
    }

    /**
     * @return array[]
     */
    public function dataProvider(): array
    {
        return [
            'recheck' => [
                'core_to_plus'  => $this->prepareArrayFromJson(self::LOCAL_PATH . 'core_to_plus.json'),
                'plus_response' => $this->prepareArrayFromJson(self::LOCAL_PATH . 'plus_response.json'),
            ],
        ];
    }

    /**
     * @param array $plusRequest
     *
     * @return IRequest | IRequest\WithOperationData
     */
    protected function preparePlusRequest(array $plusRequest): IRequest
    {
        $httpRequest = new HttpRequest([], $plusRequest);

        if ($httpRequest->request->has(IRequest::FIELD_OPERATION_DATA)) {
            return (new WithOperationData($httpRequest))->run();
        }

        return (new RequestConstructor($httpRequest))->run();
    }
}
